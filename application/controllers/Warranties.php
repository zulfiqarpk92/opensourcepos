<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Secure_Controller.php");

class Warranties extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('warranties');
	}

	public function index()
	{
		$data['table_headers'] = $this->xss_clean(get_warranties_manage_table_headers());

		$data['filters'] = $this->Warranty->get_statuses();

		$suppliers = array('' => $this->lang->line('warranties_all_suppliers'));
		foreach($this->Warranty->get_suppliers()->result() as $supplier)
		{
			$suppliers[$supplier->person_id] = $supplier->company_name;
		}
		$data['suppliers'] = $suppliers;

		$this->load->view('warranties/manage', $data);
	}

	public function search()
	{
		$search  = $this->input->get('search');
		$limit   = $this->input->get('limit');
		$offset  = $this->input->get('offset');
		$sort    = $this->input->get('sort');
		$order   = $this->input->get('order');

		$filters = array(
			'start_date' => $this->input->get('start_date'),
			'end_date' => $this->input->get('end_date'),
			'supplier_id' => $this->input->get('supplier_id'),
			'is_deleted' => 0,
			'statuses' => array()
		);

		$status_filters = $this->input->get('filters');
		if(is_array($status_filters))
		{
			$status_filters = array_filter($status_filters);
			if(!empty($status_filters))
			{
				$filters['statuses'] = $status_filters;
			}
		}

		$warranties = $this->Warranty->search($search, $filters, $limit, $offset, $sort, $order);
		$total_rows = $this->Warranty->get_found_rows($search, $filters);

		$data_rows = array();
		foreach($warranties->result() as $warranty)
		{
			$data_rows[] = get_warranty_data_row($warranty);
		}

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	public function suggest_search()
	{
		$suggestions = array();
		$term = $this->input->post('term');
		if($term != '')
		{
			$filters = array('start_date' => '', 'end_date' => '', 'supplier_id' => '', 'is_deleted' => 0, 'statuses' => array());
			$results = $this->Warranty->search($term, $filters, 25, 0, 'warranty_id', 'desc');
			foreach($results->result() as $row)
			{
				$suggestions[] = $this->xss_clean($row->serial_number);
			}
		}

		echo json_encode($suggestions);
	}

	public function get_row($row_id)
	{
		$warranty_info = $this->Warranty->get_info($row_id);
		$data_row = get_warranty_data_row($warranty_info);

		echo json_encode($data_row);
	}

	public function view($warranty_id = -1)
	{
		$info = $this->Warranty->get_info($warranty_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}

		if(empty($info->warranty_id))
		{
			$info->sent_date = date('Y-m-d H:i:s');
			$info->employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
			$info->status = Warranty::STATUS_SENT;
			$serial = $this->input->get('serial');
			if(!empty($serial))
			{
				$serial = trim($serial);
				$info->serial_number = $this->xss_clean($serial);
				$this->_apply_serial_lookup($info, $serial);
			}
		}

		$data['warranty_info'] = $info;
		$data['statuses'] = $this->Warranty->get_statuses();

		$this->load->view('warranties/form', $data);
	}

	public function lookup_serial()
	{
		$serial = $this->input->get_post('serial');
		$serial = !empty($serial) ? trim($serial) : '';
		$exclude_id = $this->input->get_post('warranty_id');

		$response = array(
			'success' => FALSE,
			'serial' => $serial,
			'sale' => NULL,
			'open_claim' => NULL,
			'latest_claim' => NULL
		);

		if($serial == '')
		{
			echo json_encode($response);
			return;
		}

		$response['success'] = TRUE;

		$sale = $this->Warranty->lookup_sale_by_serial($serial);
		if($sale)
		{
			$response['sale'] = $this->xss_clean(array(
				'sale_id' => $sale->sale_id,
				'sale_time' => to_datetime(strtotime($sale->sale_time)),
				'item_id' => $sale->item_id,
				'item_name' => $sale->item_name,
				'customer_id' => $sale->customer_id,
				'customer_name' => trim($sale->customer_name),
				'supplier_id' => $sale->item_supplier_id,
				'supplier_name' => $sale->item_supplier_name
			));
		}

		$open = $this->Warranty->get_open_by_serial($serial, $exclude_id);
		if($open)
		{
			$response['open_claim'] = $this->xss_clean(array(
				'warranty_id' => $open->warranty_id,
				'status' => $open->status,
				'sent_date' => $open->sent_date
			));
		}

		$latest = $this->Warranty->get_latest_by_serial($serial);
		if($latest)
		{
			$response['latest_claim'] = $this->xss_clean(array(
				'warranty_id' => $latest->warranty_id,
				'status' => $latest->status
			));
		}

		echo json_encode($response);
	}

	public function save($warranty_id = -1)
	{
		if($warranty_id === '' || $warranty_id === NULL)
		{
			$warranty_id = -1;
		}

		$serial = $this->input->post('serial_number');
		$serial = !empty($serial) ? trim($serial) : '';
		$status = $this->input->post('status');
		$allowed_statuses = array_keys($this->Warranty->get_statuses());
		if(!in_array($status, $allowed_statuses))
		{
			$status = Warranty::STATUS_SENT;
		}

		$sent_date = $this->_parse_datetime($this->input->post('sent_date'));
		if($sent_date == NULL)
		{
			$sent_date = date('Y-m-d H:i:s');
		}

		$received_date = $this->_parse_datetime($this->input->post('received_date'));
		$logged_in_id = $this->Employee->get_logged_in_employee_info()->person_id;

		if($status != Warranty::STATUS_SENT && $received_date == NULL)
		{
			$received_date = date('Y-m-d H:i:s');
		}

		if($status == Warranty::STATUS_SENT)
		{
			$received_date = NULL;
		}

		$additional_cost = parse_decimals($this->input->post('additional_cost'));
		if($additional_cost === FALSE || $additional_cost === '')
		{
			$additional_cost = 0;
		}

		$open = $this->Warranty->get_open_by_serial($serial, $warranty_id > 0 ? $warranty_id : FALSE);
		if($open && $status == Warranty::STATUS_SENT)
		{
			echo json_encode(array(
				'success' => FALSE,
				'message' => $this->lang->line('warranties_error_open_exists') . ' #' . $open->warranty_id,
				'id' => -1
			));
			return;
		}

		$warranty_data = array(
			'serial_number' => $serial,
			'item_id' => $this->_nullable_id($this->input->post('item_id')),
			'item_name' => $this->input->post('item_name'),
			'customer_id' => $this->_nullable_id($this->input->post('customer_id')),
			'sale_id' => $this->_nullable_id($this->input->post('sale_id')),
			'supplier_id' => $this->_nullable_id($this->input->post('supplier_id')),
			'sent_date' => $sent_date,
			'received_date' => $received_date,
			'status' => $status,
			'issue_description' => $this->input->post('issue_description'),
			'claim_reference' => $this->input->post('claim_reference'),
			'resolution_notes' => $this->input->post('resolution_notes'),
			'additional_cost' => $additional_cost,
			'employee_id' => $this->input->post('employee_id') ? $this->input->post('employee_id') : $logged_in_id
		);

		if($status != Warranty::STATUS_SENT)
		{
			$warranty_data['received_employee_id'] = $logged_in_id;
		}

		if($this->Warranty->save($warranty_data, $warranty_id))
		{
			$id = ($warranty_id == -1) ? $warranty_data['warranty_id'] : $warranty_id;
			$message = ($warranty_id == -1)
				? $this->lang->line('warranties_successful_adding')
				: $this->lang->line('warranties_successful_updating');

			echo json_encode(array('success' => TRUE, 'message' => $message . ' ' . $serial, 'id' => $id));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('warranties_error_adding_updating'), 'id' => -1));
		}
	}

	public function delete()
	{
		$ids = $this->input->post('ids');

		if($this->Warranty->delete_list($ids))
		{
			echo json_encode(array(
				'success' => TRUE,
				'message' => $this->lang->line('warranties_successful_deleted') . ' ' . count($ids) . ' ' . $this->lang->line('warranties_one_or_multiple'),
				'ids' => $ids
			));
		}
		else
		{
			echo json_encode(array(
				'success' => FALSE,
				'message' => $this->lang->line('warranties_cannot_be_deleted'),
				'ids' => $ids
			));
		}
	}

	private function _apply_serial_lookup(&$info, $serial)
	{
		$sale = $this->Warranty->lookup_sale_by_serial($serial);
		if($sale)
		{
			$info->item_id = $sale->item_id;
			$info->item_name = $sale->item_name;
			$info->customer_id = $sale->customer_id;
			$info->customer_name = trim($sale->customer_name);
			$info->sale_id = $sale->sale_id;
			if(empty($info->supplier_id))
			{
				$info->supplier_id = $sale->item_supplier_id;
				$info->supplier_name = $sale->item_supplier_name;
			}
		}
	}

	private function _parse_datetime($value)
	{
		if(empty($value))
		{
			return NULL;
		}

		$date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $value);
		if($date_formatter)
		{
			return $date_formatter->format('Y-m-d H:i:s');
		}

		$date_formatter = date_create_from_format($this->config->item('dateformat'), $value);
		if($date_formatter)
		{
			return $date_formatter->format('Y-m-d H:i:s');
		}

		$timestamp = strtotime($value);
		if($timestamp)
		{
			return date('Y-m-d H:i:s', $timestamp);
		}

		return NULL;
	}

	private function _nullable_id($value)
	{
		if($value === NULL || $value === '' || $value === FALSE)
		{
			return NULL;
		}

		return $value;
	}
}
?>
