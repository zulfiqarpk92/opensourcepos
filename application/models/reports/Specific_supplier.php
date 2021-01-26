<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Specific_supplier extends Report
{
	public function create(array $inputs)
	{
		//Create our temp tables to work with the data in our report
		$this->Sale->create_temp_table($inputs);
	}

	public function getDataColumns()
	{
		return array(
			array('id' => $this->lang->line('reports_sale_id')),
			array('type_code' => $this->lang->line('reports_code_type')),
			array('sale_date' => $this->lang->line('reports_date'), 'sortable' => FALSE),
			array('name' => $this->lang->line('reports_name')),
			array('category' => $this->lang->line('reports_category')),
			array('item_number' => $this->lang->line('reports_item_number')),
			array('quantity' => $this->lang->line('reports_quantity')),
			array('subtotal' => $this->lang->line('reports_subtotal'), 'sorter' => 'number_sorter'),
			array('tax' => $this->lang->line('reports_tax'), 'sorter' => 'number_sorter'),
			array('total' => $this->lang->line('reports_total'), 'sorter' => 'number_sorter'),
			array('cost' => $this->lang->line('reports_cost'), 'sorter' => 'number_sorter'),
			array('profit' => $this->lang->line('reports_profit'), 'sorter' => 'number_sorter'),
			array('discount' => $this->lang->line('reports_discount'))
		);
	}

  public function getOutstanding(array $inputs){
    $percent = PERCENT;
    $decimals = totals_decimals();
    $total_amount = 'ri.item_unit_price * ri.quantity_purchased * ri.receiving_quantity';
    $receiving_total = "ROUND(SUM(CASE WHEN ri.discount_type = $percent THEN $total_amount * (1 - ri.discount / 100) ELSE $total_amount - ri.discount END), $decimals)";

    $this->db->select("$receiving_total AS total_due, SUM(sp.amount_tendered) AS total_payment");
    $this->db->from('receivings r');
    $this->db->join('receivings_items ri', 'r.receiving_id = ri.receiving_id');
    $this->db->join('suppliers_payments sp', 'r.receiving_id = sp.receiving_id AND r.supplier_id = sp.supplier_id', 'LEFT');

    $this->db->where('r.supplier_id', $inputs['supplier_id']);
    
    $where = '';
    if(empty($this->config->item('date_or_time_format')))
    {
      $where = 'DATE(receiving_time) < ' . $this->db->escape($inputs['start_date']);
    }
    else
    {
      $where = 'receiving_time < ' . $this->db->escape(rawurldecode($inputs['start_date']));
    }
    $this->db->where($where);

		$this->db->group_by('r.supplier_id');

    $data = $this->db->get()->row();

    $outstanding = $data ? $data->total_due - $data->total_payment : 0;

    $this->db->select("SUM(sp.amount_tendered) AS total_payment");
    $this->db->from('suppliers_payments AS sp');
    $this->db->where('sp.supplier_id', $inputs['supplier_id']);
    $this->db->where('sp.receiving_id', '0');
    
    $where = '';
    if(empty($this->config->item('date_or_time_format')))
    {
      $where = 'DATE(payment_date) < ' . $this->db->escape($inputs['start_date']);
    }
    else
    {
      $where = 'payment_date < ' . $this->db->escape(rawurldecode($inputs['start_date']));
    }
    $this->db->where($where);

    $this->db->group_by('sp.supplier_id');

    $data = $this->db->get()->row();

    $outstanding -= ($data && $data->total_payment) ? $data->total_payment : 0;

		return $outstanding;
  }

  public function getReceivingData(array $inputs){
    $this->db->select("
    '0' AS sid, 
    r.receiving_id,
    r.receiving_time,
    r.supplier_id,
    r.payment_type,
    r.comment,
    r.reference,
    SUM(sp.amount_tendered) AS total_payment");
    $this->db->from('receivings AS r');
    $this->db->join('suppliers_payments sp', 'r.receiving_id = sp.receiving_id AND r.supplier_id = sp.supplier_id', 'LEFT');
    $this->db->where('r.supplier_id', $inputs['supplier_id']);
    
    $where = '';
    if(empty($this->config->item('date_or_time_format')))
    {
      $where = 'DATE(receiving_time) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']);
    }
    else
    {
      $where = 'receiving_time BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date']));
    }
    $this->db->where($where);

    $this->db->group_by('r.receiving_id');

    $query1 = $this->db->get_compiled_select();
    
    $this->db->select("
    sp.supplier_payment_id AS sid,
    sp.receiving_id,
    sp.payment_date AS receiving_time,
    sp.supplier_id,
    'Cash' AS payment_type,
    sp.comments,
    sp.reference,
    sp.amount_tendered AS total_payment");
    $this->db->from('suppliers_payments AS sp');
    $this->db->where('sp.supplier_id', $inputs['supplier_id']);
    $this->db->where('sp.receiving_id', '0');
    
    $where = '';
    if(empty($this->config->item('date_or_time_format')))
    {
      $where = 'DATE(payment_date) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']);
    }
    else
    {
      $where = 'payment_date BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date']));
    }
    $this->db->where($where);

    $this->db->group_by('sp.supplier_payment_id');

    $query2 = $this->db->get_compiled_select();

    $receiving_data = $this->db->query("($query1 UNION $query2) ORDER BY receiving_time")->result();
    
    $receiving_records = [];
    
    $receiving_ids = [];
    foreach($receiving_data as $row){
      if($row->receiving_id == 0){
        $row->receiving_id = 'P-' . $row->sid;
      }
      else{
        $receiving_ids[] = $row->receiving_id;
      }
      $row->items = [];
      $row->total_quantity = 0;
      $row->total_amount = 0;
      $receiving_records[$row->receiving_id] = $row;
    }

    if(count($receiving_ids) > 0){
      $percent = PERCENT;
      $decimals = totals_decimals();
      $total_amount = 'ri.item_unit_price * ri.quantity_purchased * ri.receiving_quantity';
      $line_total = "ROUND(SUM(CASE WHEN ri.discount_type = $percent THEN $total_amount * (1 - ri.discount / 100) ELSE $total_amount - ri.discount END), $decimals)";
  
      $this->db->select("ri.*, i.name, i.category, i.item_number, i.description, $line_total AS line_total");
      $this->db->from('receivings_items AS ri');
      $this->db->join('items AS i', 'i.item_id = ri.item_id');
      $this->db->where_in('ri.receiving_id', $receiving_ids);
      $this->db->group_by('ri.receiving_id, ri.line');
    
      foreach($this->db->get()->result() as $row){
        if(isset($receiving_records[$row->receiving_id])){
          $receiving_records[$row->receiving_id]->items[] = $row;
          $receiving_records[$row->receiving_id]->total_quantity += $row->quantity_purchased * $row->receiving_quantity;
          $receiving_records[$row->receiving_id]->total_amount += $row->line_total;
        }
      }
    }
    return $receiving_records;
  }

	public function getData(array $inputs)
	{
		$this->db->select('sale_id,
			MAX(CASE
			WHEN sale_type = ' . SALE_TYPE_POS . ' && sale_status = ' . COMPLETED . ' THEN \'' . $this->lang->line('reports_code_pos') . '\'
			WHEN sale_type = ' . SALE_TYPE_INVOICE . ' && sale_status = ' . COMPLETED . ' THEN \'' . $this->lang->line('reports_code_invoice') . '\'
			WHEN sale_type = ' . SALE_TYPE_WORK_ORDER . ' && sale_status = ' . SUSPENDED . ' THEN \'' . $this->lang->line('reports_code_work_order') . '\'
			WHEN sale_type = ' . SALE_TYPE_QUOTE . ' && sale_status = ' . SUSPENDED . ' THEN \'' . $this->lang->line('reports_code_quote') . '\'
			WHEN sale_type = ' . SALE_TYPE_RETURN . ' && sale_status = ' . COMPLETED . ' THEN \'' . $this->lang->line('reports_code_return') . '\'
			WHEN sale_status = ' . CANCELED . ' THEN \'' . $this->lang->line('reports_code_canceled') . '\'
			ELSE \'\'
			END) AS type_code,
			MAX(sale_status) as sale_status,
			MAX(sale_date) AS sale_date,
			MAX(name) AS name,
			MAX(category) AS category,
			MAX(item_number) AS item_number,
			SUM(quantity_purchased) AS items_purchased,
			SUM(subtotal) AS subtotal,
			SUM(tax) AS tax,
			SUM(total) AS total,
			SUM(cost) AS cost,
			SUM(profit) AS profit,
			MAX(discount_type) AS discount_type,
			MAX(discount) AS discount');
		$this->db->from('sales_items_temp');

		$this->db->where('supplier_id', $inputs['supplier_id']);

		if($inputs['sale_type'] == 'complete')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->group_start();
			$this->db->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->or_where('sale_type', SALE_TYPE_RETURN);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'sales')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->group_start();
			$this->db->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'quotes')
		{
			$this->db->where('sale_status', SUSPENDED);
			$this->db->where('sale_type', SALE_TYPE_QUOTE);
		}
		elseif($inputs['sale_type'] == 'work_orders')
		{
			$this->db->where('sale_status', SUSPENDED);
			$this->db->where('sale_type', SALE_TYPE_WORK_ORDER);
		}
		elseif($inputs['sale_type'] == 'canceled')
		{
			$this->db->where('sale_status', CANCELED);
		}
		elseif($inputs['sale_type'] == 'returns')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->where('sale_type', SALE_TYPE_RETURN);
		}

		$this->db->group_by('sale_id');
		$this->db->order_by('MAX(sale_date)');

		return $this->db->get()->result_array();
	}

	public function getSummaryData(array $inputs)
	{
		$this->db->select('SUM(subtotal) AS subtotal, SUM(tax) AS tax, SUM(total) AS total, SUM(cost) AS cost, SUM(profit) AS profit');
		$this->db->from('sales_items_temp');

		$this->db->where('supplier_id', $inputs['supplier_id']);

		if($inputs['sale_type'] == 'complete')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->group_start();
			$this->db->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->or_where('sale_type', SALE_TYPE_RETURN);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'sales')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->group_start();
			$this->db->where('sale_type', SALE_TYPE_POS);
			$this->db->or_where('sale_type', SALE_TYPE_INVOICE);
			$this->db->group_end();
		}
		elseif($inputs['sale_type'] == 'quotes')
		{
			$this->db->where('sale_status', SUSPENDED);
			$this->db->where('sale_type', SALE_TYPE_QUOTE);
		}
		elseif($inputs['sale_type'] == 'work_orders')
		{
			$this->db->where('sale_status', SUSPENDED);
			$this->db->where('sale_type', SALE_TYPE_WORK_ORDER);
		}
		elseif($inputs['sale_type'] == 'canceled')
		{
			$this->db->where('sale_status', CANCELED);
		}
		elseif($inputs['sale_type'] == 'returns')
		{
			$this->db->where('sale_status', COMPLETED);
			$this->db->where('sale_type', SALE_TYPE_RETURN);
		}

		return $this->db->get()->row_array();
	}
}
?>
