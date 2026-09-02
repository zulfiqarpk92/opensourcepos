<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Warranty class
 */

class Warranty extends CI_Model
{
	const STATUS_SENT = 'sent';
	const STATUS_FULFILLED = 'fulfilled';
	const STATUS_REJECTED = 'rejected';
	const STATUS_PARTIAL = 'partial';

	public function get_statuses()
	{
		return array(
			self::STATUS_SENT => $this->lang->line('warranties_status_sent'),
			self::STATUS_FULFILLED => $this->lang->line('warranties_status_fulfilled'),
			self::STATUS_REJECTED => $this->lang->line('warranties_status_rejected'),
			self::STATUS_PARTIAL => $this->lang->line('warranties_status_partial')
		);
	}

	public function exists($warranty_id)
	{
		$this->db->from('warranties');
		$this->db->where('warranty_id', $warranty_id);

		return ($this->db->get()->num_rows() == 1);
	}

	public function get_found_rows($search, $filters)
	{
		return $this->search($search, $filters, 0, 0, 'warranty_id', 'desc', TRUE);
	}

	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'warranty_id', $order = 'desc', $count_only = FALSE)
	{
		if($count_only == TRUE)
		{
			$this->db->select('COUNT(DISTINCT warranties.warranty_id) as count');
		}
		else
		{
			$this->db->select('
				warranties.warranty_id,
				warranties.serial_number,
				warranties.item_id,
				warranties.item_name,
				warranties.customer_id,
				warranties.sale_id,
				warranties.supplier_id,
				warranties.sent_date,
				warranties.received_date,
				warranties.status,
				warranties.issue_description,
				warranties.claim_reference,
				warranties.resolution_notes,
				warranties.additional_cost,
				warranties.employee_id,
				suppliers.company_name AS supplier_name,
				CONCAT(customers.first_name, " ", customers.last_name) AS customer_name,
				CONCAT(employees.first_name, " ", employees.last_name) AS created_by
			');
		}

		$this->db->from('warranties AS warranties');
		$this->db->join('suppliers AS suppliers', 'suppliers.person_id = warranties.supplier_id', 'LEFT');
		$this->db->join('people AS customers', 'customers.person_id = warranties.customer_id', 'LEFT');
		$this->db->join('people AS employees', 'employees.person_id = warranties.employee_id', 'LEFT');

		if($search != '')
		{
			$this->db->group_start();
				$this->db->like('warranties.serial_number', $search);
				$this->db->or_like('warranties.item_name', $search);
				$this->db->or_like('warranties.claim_reference', $search);
				$this->db->or_like('warranties.issue_description', $search);
				$this->db->or_like('warranties.status', $search);
				$this->db->or_like('suppliers.company_name', $search);
				$this->db->or_like('customers.first_name', $search);
				$this->db->or_like('customers.last_name', $search);
				$this->db->or_like('CONCAT(customers.first_name, " ", customers.last_name)', $search);
			$this->db->group_end();
		}

		$this->db->where('warranties.deleted', isset($filters['is_deleted']) ? $filters['is_deleted'] : 0);

		if(!empty($filters['start_date']) && !empty($filters['end_date']))
		{
			if(empty($this->config->item('date_or_time_format')))
			{
				$this->db->where('DATE_FORMAT(warranties.sent_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
			}
			else
			{
				$this->db->where('warranties.sent_date BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date'])));
			}
		}

		if(!empty($filters['statuses']) && is_array($filters['statuses']))
		{
			$this->db->where_in('warranties.status', $filters['statuses']);
		}

		if(!empty($filters['supplier_id']))
		{
			$this->db->where('warranties.supplier_id', $filters['supplier_id']);
		}

		if($count_only == TRUE)
		{
			return $this->db->get()->row()->count;
		}

		$sort_columns = array(
			'warranty_id' => 'warranties.warranty_id',
			'serial_number' => 'warranties.serial_number',
			'item_name' => 'warranties.item_name',
			'customer_name' => 'customer_name',
			'supplier_name' => 'suppliers.company_name',
			'sent_date' => 'warranties.sent_date',
			'received_date' => 'warranties.received_date',
			'status' => 'warranties.status',
			'additional_cost' => 'warranties.additional_cost',
			'issue_description' => 'warranties.issue_description',
			'created_by' => 'employees.first_name'
		);

		if(isset($sort_columns[$sort]))
		{
			$this->db->order_by($sort_columns[$sort], $order);
		}
		else
		{
			$this->db->order_by('warranties.warranty_id', 'desc');
		}

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	public function get_info($warranty_id)
	{
		$this->db->select('
			warranties.*,
			suppliers.company_name AS supplier_name,
			CONCAT(customers.first_name, " ", customers.last_name) AS customer_name,
			CONCAT(employees.first_name, " ", employees.last_name) AS created_by
		');
		$this->db->from('warranties AS warranties');
		$this->db->join('suppliers AS suppliers', 'suppliers.person_id = warranties.supplier_id', 'LEFT');
		$this->db->join('people AS customers', 'customers.person_id = warranties.customer_id', 'LEFT');
		$this->db->join('people AS employees', 'employees.person_id = warranties.employee_id', 'LEFT');
		$this->db->where('warranties.warranty_id', $warranty_id);

		$query = $this->db->get();
		if($query->num_rows() == 1)
		{
			return $query->row();
		}

		$warranty_obj = new stdClass();
		foreach($this->db->list_fields('warranties') as $field)
		{
			$warranty_obj->$field = '';
		}
		$warranty_obj->supplier_name = '';
		$warranty_obj->customer_name = '';
		$warranty_obj->created_by = '';
		$warranty_obj->status = self::STATUS_SENT;
		$warranty_obj->additional_cost = 0;

		return $warranty_obj;
	}

	public function get_open_by_serial($serial_number, $exclude_id = FALSE)
	{
		$this->db->from('warranties');
		$this->db->where('serial_number', $serial_number);
		$this->db->where('status', self::STATUS_SENT);
		$this->db->where('deleted', 0);
		if($exclude_id)
		{
			$this->db->where('warranty_id !=', $exclude_id);
		}
		$this->db->order_by('warranty_id', 'desc');
		$this->db->limit(1);

		$query = $this->db->get();
		if($query->num_rows() == 1)
		{
			return $query->row();
		}

		return NULL;
	}

	public function get_latest_by_serial($serial_number)
	{
		$this->db->from('warranties');
		$this->db->where('serial_number', $serial_number);
		$this->db->where('deleted', 0);
		$this->db->order_by('warranty_id', 'desc');
		$this->db->limit(1);

		$query = $this->db->get();
		if($query->num_rows() == 1)
		{
			return $query->row();
		}

		return NULL;
	}

	public function lookup_sale_by_serial($serial_number)
	{
		$this->db->select('
			sales_items.sale_id,
			sales_items.item_id,
			sales_items.serialnumber,
			sales.sale_time,
			sales.customer_id,
			items.name AS item_name,
			items.supplier_id AS item_supplier_id,
			item_suppliers.company_name AS item_supplier_name,
			CONCAT(customer.first_name, " ", customer.last_name) AS customer_name
		');
		$this->db->from('sales_items');
		$this->db->join('sales', 'sales.sale_id = sales_items.sale_id');
		$this->db->join('items', 'items.item_id = sales_items.item_id');
		$this->db->join('people AS customer', 'customer.person_id = sales.customer_id', 'LEFT');
		$this->db->join('suppliers AS item_suppliers', 'item_suppliers.person_id = items.supplier_id', 'LEFT');
		$this->db->where('sales_items.serialnumber', $serial_number);
		$this->db->where('sales_items.serialnumber !=', '');
		$this->db->order_by('sales.sale_time', 'desc');
		$this->db->limit(1);

		$query = $this->db->get();
		if($query->num_rows() == 1)
		{
			return $query->row();
		}

		return NULL;
	}

	public function get_suppliers()
	{
		$this->db->select('suppliers.person_id, company_name');
		$this->db->from('suppliers');
		$this->db->join('people', 'suppliers.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->order_by('company_name', 'asc');

		return $this->db->get();
	}

	public function save(&$warranty_data, $warranty_id = FALSE)
	{
		if(!$warranty_id || !$this->exists($warranty_id))
		{
			if($this->db->insert('warranties', $warranty_data))
			{
				$warranty_data['warranty_id'] = $this->db->insert_id();

				return TRUE;
			}

			return FALSE;
		}

		$this->db->where('warranty_id', $warranty_id);

		return $this->db->update('warranties', $warranty_data);
	}

	public function delete_list($warranty_ids)
	{
		$success = FALSE;

		$this->db->trans_start();
			$this->db->where_in('warranty_id', $warranty_ids);
			$success = $this->db->update('warranties', array('deleted' => 1));
		$this->db->trans_complete();

		return $success;
	}
}
?>
