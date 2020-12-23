<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Receiving class
 */

class Receiving extends CI_Model
{
	public function get_info($receiving_id)
	{
		$this->db->from('receivings');
		$this->db->join('people', 'people.person_id = receivings.supplier_id', 'LEFT');
		$this->db->join('suppliers', 'suppliers.person_id = receivings.supplier_id', 'LEFT');
		$this->db->where('receiving_id', $receiving_id);

		return $this->db->get();
	}

	public function get_receiving_by_reference($reference)
	{
		$this->db->from('receivings');
		$this->db->where('reference', $reference);

		return $this->db->get();
	}

	public function is_valid_receipt($receipt_receiving_id)
	{
		if(!empty($receipt_receiving_id))
		{
			//RECV #
			$pieces = explode(' ', $receipt_receiving_id);

			if(count($pieces) == 2 && preg_match('/(RECV|KIT)/', $pieces[0]))
			{
				return $this->exists($pieces[1]);
			}
			else
			{
				return $this->get_receiving_by_reference($receipt_receiving_id)->num_rows() > 0;
			}
		}

		return FALSE;
	}

	public function exists($receiving_id)
	{
		$this->db->from('receivings');
		$this->db->where('receiving_id', $receiving_id);

		return ($this->db->get()->num_rows() == 1);
	}

	public function update($receiving_data, $receiving_id)
	{
		$this->db->where('receiving_id', $receiving_id);

		return $this->db->update('receivings', $receiving_data);
	}

	public function save($items, $supplier_id, $employee_id, $comment, $reference, $payment_type, $receiving_id = FALSE, $amount_tendered = 0)
	{
		if(count($items) == 0)
		{
			return -1;
		}
    if($receiving_id != -1){      
      $this->delete($receiving_id, $employee_id, TRUE, FALSE);
      sleep(2);
    }

    if($this->Supplier->exists($supplier_id) == FALSE){
      $supplier_id = 0;
    }
		$receivings_data = array(
			'receiving_time'  => date('Y-m-d H:i:s'),
			'supplier_id'     => $supplier_id,
			'employee_id'     => $employee_id,
			'payment_type'    => $payment_type,
			'comment'         => $comment,
			'reference'       => $reference
		);

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		if($receiving_id == -1)
		{
      $this->db->insert('receivings', $receivings_data);
      $receiving_id = $this->db->insert_id();
		}
		else
		{
			$this->db->where('receiving_id', $receiving_id);
      $this->db->update('receivings', $receivings_data);
		}

		foreach($items as $line=>$item)
		{
			$cur_item_info = $this->Item->get_info($item['item_id']);

			$receivings_items_data = array(
				'receiving_id' => $receiving_id,
				'item_id' => $item['item_id'],
				'line' => $item['line'],
				'description' => $item['description'],
				'serialnumber' => $item['serialnumber'],
				'quantity_purchased' => $item['quantity'],
				'receiving_quantity' => $item['receiving_quantity'],
				'discount' => $item['discount'],
				'discount_type' => $item['discount_type'],
				'item_cost_price' => $cur_item_info->cost_price,
				'item_unit_price' => $item['price'],
				'item_location' => $item['item_location']
			);

			$this->db->insert('receivings_items', $receivings_items_data);

			$items_received = $item['receiving_quantity'] != 0 ? $item['quantity'] * $item['receiving_quantity'] : $item['quantity'];

      if($cur_item_info->stock_type == HAS_STOCK){
        // update cost price, if changed AND is set in config as wanted
        if($cur_item_info->cost_price != $item['price'] && $this->config->item('receiving_calculate_average_price') != FALSE)
        {
          $this->Item->change_cost_price($item['item_id'], $items_received, $item['price'], $cur_item_info->cost_price);
        }
        elseif($item['price'] > $cur_item_info->cost_price){
          $price_update = array(
            'cost_price' => $item['price'],
            'unit_price' => $cur_item_info->unit_price + ($item['price'] - $cur_item_info->cost_price)
          );
          $this->Item->save($price_update, $item['item_id']);
        }

        //Update stock quantity
        $item_quantity = $this->Item_quantity->get_item_quantity($item['item_id'], $item['item_location']);
        $this->Item_quantity->save(array('quantity' => $item_quantity->quantity + $items_received, 'item_id' => $item['item_id'],
                          'location_id' => $item['item_location']), $item['item_id'], $item['item_location']);
  
        $recv_remarks = 'RECV ' . $receiving_id;
        $inv_data = array(
          'trans_date' => date('Y-m-d H:i:s'),
          'trans_items' => $item['item_id'],
          'trans_user' => $employee_id,
          'trans_location' => $item['item_location'],
          'trans_comment' => $recv_remarks,
          'trans_inventory' => $items_received
        );
  
        $this->Inventory->insert($inv_data);
      }

			$this->Attribute->copy_attribute_links($item['item_id'], 'receiving_id', $receiving_id);

			// $supplier = $this->Supplier->get_info($supplier_id);
		}
		if($amount_tendered != null && $payment_type == 'Cash'){
      $supplier_payment = array(
        'supplier_id'     => $supplier_id,
        'receiving_id'    => $receiving_id,
        'amount_tendered' => $amount_tendered,
        'payment_date'    => date('Y-m-d H:i:s')
      );
      $this->db->insert('suppliers_payments', $supplier_payment);
		}

		$this->db->trans_complete();

		if($this->db->trans_status() === FALSE)
		{
			return -1;
		}

		return $receiving_id;
	}

	public function delete_list($receiving_ids, $employee_id, $update_inventory = TRUE)
	{
		$success = TRUE;

		// start a transaction to assure data integrity
		$this->db->trans_start();

		foreach($receiving_ids as $receiving_id)
		{
			$success &= $this->delete($receiving_id, $employee_id, $update_inventory);
		}

		// execute transaction
		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	public function delete($receiving_id, $employee_id, $update_inventory = TRUE, $delete_self = TRUE)
	{
		// start a transaction to assure data integrity
		$this->db->trans_start();

		if($update_inventory)
		{
			// defect, not all item deletions will be undone??
			// get array with all the items involved in the sale to update the inventory tracking
			$items = $this->get_receiving_items($receiving_id)->result_array();
			foreach($items as $item)
			{
        if($item['stock_type'] == HAS_STOCK){
          // create query to update inventory tracking
          $inv_data = array(
            'trans_date' => date('Y-m-d H:i:s'),
            'trans_items' => $item['item_id'],
            'trans_user' => $employee_id,
            'trans_comment' => 'Deleting receiving ' . $receiving_id,
            'trans_location' => $item['item_location'],
            'trans_inventory' => $item['quantity_purchased'] * -1
          );
          // update inventory
          $this->Inventory->insert($inv_data);
  
          // update quantities
          $this->Item_quantity->change_quantity($item['item_id'], $item['item_location'], $item['quantity_purchased'] * -1);
        }
			}
		}

		// delete all items
		$this->db->delete('receivings_items', array('receiving_id' => $receiving_id));
    if($delete_self){
      // delete sale itself
      $this->db->delete('receivings', array('receiving_id' => $receiving_id));
    }

		// execute transaction
		$this->db->trans_complete();
	
		return $this->db->trans_status();
	}

	public function get_receiving_items($receiving_id)
	{
    $this->db->select('ri.*, i.stock_type');
    $this->db->from('receivings_items ri');
    $this->db->join('items i', 'i.item_id = ri.item_id');
		$this->db->where('ri.receiving_id', $receiving_id);

		return $this->db->get();
	}
	
	public function get_supplier($receiving_id)
	{
		$this->db->from('receivings');
		$this->db->where('receiving_id', $receiving_id);

		return $this->Supplier->get_info($this->db->get()->row()->supplier_id);
	}

  public function get_info_payments($receiving_id){
		$price = 'CASE WHEN ri.discount_type = ' . PERCENT . ' THEN ri.item_unit_price * ri.quantity_purchased * (1 - ri.discount / 100) ELSE ri.item_unit_price * ri.quantity_purchased - ri.discount END';
    $total = 'ROUND(SUM(' . $price . '), ' . totals_decimals() . ')';
    $this->db->select('r.*, ' . $total . ' AS total_amount', FALSE);
		$this->db->from('receivings r');
    $this->db->join('receivings_items ri', 'ri.receiving_id = r.receiving_id');
    $this->db->where('r.receiving_id', $receiving_id);
    $receiving = $this->db->get()->row();
    if($receiving){
      $receiving->cash_payment_total = 0;
      $receiving->payments = $this->get_payments($receiving_id);
      foreach($receiving->payments as $p){
        $receiving->cash_payment_total += $p->amount_tendered;
      }
      $receiving->balance = $receiving->total_amount - $receiving->cash_payment_total;
    }
    return $receiving;
  }

  public function get_payments($receiving_id){
    return $this->db->where('receiving_id', $receiving_id)->get('suppliers_payments')->result();
  }

	public function get_payment_options()
	{
		return array(
			$this->lang->line('sales_cash') => $this->lang->line('sales_cash'),
			$this->lang->line('sales_check') => $this->lang->line('sales_check'),
			// $this->lang->line('sales_debit') => $this->lang->line('sales_debit'),
			// $this->lang->line('sales_credit') => $this->lang->line('sales_credit'),
			$this->lang->line('sales_due') => $this->lang->line('sales_due')
		);
	}

	/*
	We create a temp table that allows us to do easy report/receiving queries
	*/
	public function create_temp_table(array $inputs)
	{
		if(empty($inputs['receiving_id']))
		{
			if(empty($this->config->item('date_or_time_format')))
			{
				$where = 'WHERE DATE(receiving_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']);
			}
			else
			{
				$where = 'WHERE receiving_time BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date']));
			}
		}
		else
		{
			$where = 'WHERE receivings_items.receiving_id = ' . $this->db->escape($inputs['receiving_id']);
		}

		$this->db->query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $this->db->dbprefix('receivings_items_temp') .
			' (INDEX(receiving_date), INDEX(receiving_time), INDEX(receiving_id))
			(
				SELECT 
					MAX(DATE(receiving_time)) AS receiving_date,
					MAX(receiving_time) AS receiving_time,
					receivings_items.receiving_id,
					MAX(comment) AS comment,
					MAX(item_location) AS item_location,
					MAX(reference) AS reference,
					MAX(payment_type) AS payment_type,
					MAX(employee_id) AS employee_id, 
					items.item_id,
					MAX(receivings.supplier_id) AS supplier_id,
					MAX(quantity_purchased) AS quantity_purchased,
					MAX(receivings_items.receiving_quantity) AS receiving_quantity,
					MAX(item_cost_price) AS item_cost_price,
					MAX(item_unit_price) AS item_unit_price,
					MAX(discount) AS discount,
					discount_type as discount_type,
					receivings_items.line,
					MAX(serialnumber) AS serialnumber,
					MAX(receivings_items.description) AS description,
					MAX(CASE WHEN receivings_items.discount_type = ' . PERCENT . ' THEN item_unit_price * quantity_purchased * receivings_items.receiving_quantity - item_unit_price * quantity_purchased * receivings_items.receiving_quantity * discount / 100 ELSE item_unit_price * quantity_purchased * receivings_items.receiving_quantity - discount END) AS subtotal,
					MAX(CASE WHEN receivings_items.discount_type = ' . PERCENT . ' THEN item_unit_price * quantity_purchased * receivings_items.receiving_quantity - item_unit_price * quantity_purchased * receivings_items.receiving_quantity * discount / 100 ELSE item_unit_price * quantity_purchased * receivings_items.receiving_quantity - discount END) AS total,
					MAX((CASE WHEN receivings_items.discount_type = ' . PERCENT . ' THEN item_unit_price * quantity_purchased * receivings_items.receiving_quantity - item_unit_price * quantity_purchased * receivings_items.receiving_quantity * discount / 100 ELSE item_unit_price * quantity_purchased * receivings_items.receiving_quantity - discount END) - (item_cost_price * quantity_purchased)) AS profit,
					MAX(item_cost_price * quantity_purchased * receivings_items.receiving_quantity ) AS cost
				FROM ' . $this->db->dbprefix('receivings_items') . ' AS receivings_items
				INNER JOIN ' . $this->db->dbprefix('receivings') . ' AS receivings
					ON receivings_items.receiving_id = receivings.receiving_id
				INNER JOIN ' . $this->db->dbprefix('items') . ' AS items
					ON receivings_items.item_id = items.item_id
				' . "
				$where
				" . '
				GROUP BY receivings_items.receiving_id, items.item_id, receivings_items.line
			)'
		);
	}
  
  /**
	 * Get number of rows for the takings (sales/manage) view
	 */
	public function get_found_rows($search, $filters)
	{
		return $this->search($search, $filters, 0, 0, 'receivings.receiving_time', 'desc', TRUE);
  }
  
  /**
	 * Get the sales data for the takings (sales/manage) view
	 */
	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'receivings.receiving_time', $order = 'desc', $count_only = FALSE)
	{
		// Pick up only non-suspended records
		$where = '';

    if($filters['start_date'] && $filters['end_date'])
    {
      if(empty($this->config->item('date_or_time_format')))
      {
        $where .= 'DATE(receivings.receiving_time) BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']);
      }
      else
      {
        $where .= 'receivings.receiving_time BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date']));
      }
    }

		$decimals = totals_decimals();

		$sale_price = 'CASE WHEN receivings_items.discount_type = ' . PERCENT . ' THEN receivings_items.item_unit_price * receivings_items.quantity_purchased * (1 - receivings_items.discount / 100) ELSE receivings_items.item_unit_price * receivings_items.quantity_purchased - receivings_items.discount END';
		$sale_total = 'ROUND(SUM(' . $sale_price . '), ' . $decimals . ')';

		// get_found_rows case
		if($count_only == TRUE)
		{
			$this->db->select('COUNT(DISTINCT receivings.receiving_id) AS count');
		}
		else
		{
      $payment_tbl = $this->db->dbprefix('suppliers_payments');
			$this->db->select('
          receivings.receiving_id,
					MAX(receivings.receiving_time) AS receiving_time,
					SUM(receivings_items.quantity_purchased) AS items_purchased,
					supplier.company_name,
					' . "
          $sale_total AS amount_due,
          (SELECT SUM(sp.amount_tendered) FROM $payment_tbl sp WHERE sp.receiving_id = receivings.receiving_id) AS total_payment,  
          receivings.payment_type,
          receivings.comment,
          receivings.reference
        ");
		}

		$this->db->from('receivings_items AS receivings_items');
		$this->db->join('receivings AS receivings', 'receivings_items.receiving_id = receivings.receiving_id', 'inner');
		$this->db->join('people AS supplier_p', 'receivings.supplier_id = supplier_p.person_id', 'LEFT');
		$this->db->join('suppliers AS supplier', 'receivings.supplier_id = supplier.person_id', 'LEFT');

		$where ? $this->db->where($where) : FALSE;

		if(!empty($search))
		{
			if($filters['is_valid_receipt'] != FALSE)
			{
				$pieces = explode(' ', $search);
				$this->db->where('receivings.sale_id', $pieces[1]);
			}
			else
			{
				$this->db->group_start();
					// customer last name
					$this->db->like('supplier_p.last_name', $search);
					// customer first name
					$this->db->or_like('supplier_p.first_name', $search);
					// customer first and last name
					$this->db->or_like('CONCAT(supplier_p.first_name, " ", supplier_p.last_name)', $search);
					// customer company name
					$this->db->or_like('supplier.company_name', $search);
				$this->db->group_end();
			}
		}

    if(empty($filters['receiving_id']) == FALSE)
		{
			$this->db->where('receivings.receiving_id', $filters['receiving_id']);
    }
    
		if($filters['location_id'] != 'all')
		{
			// $this->db->where('sales_items.item_location', $filters['location_id']);
		}

		if($filters['only_cash'] != FALSE)
		{
			$this->db->where('receivings.payment_type', 'Cash');
		}

		if($filters['only_due'] != FALSE)
		{
			$this->db->where('receivings.payment_type', 'Due');
		}

		if($filters['only_check'] != FALSE)
		{
			$this->db->where('receivings.payment_type', 'Check');
		}

    if(empty($filters['supplier_id']) == FALSE)
		{
			$this->db->where('receivings.supplier_id', $filters['supplier_id']);
    }
    
		// get_found_rows case
		if($count_only == TRUE)
		{
			return $this->db->get()->row()->count;
		}

		$this->db->group_by('receivings.receiving_id');

		// order by sale time by default
		$this->db->order_by($sort, $order);

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}
}
?>
