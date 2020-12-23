<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Secure_Controller.php");

class Receivings extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('receivings');

		$this->load->library('receiving_lib');
		$this->load->library('token_lib');
		$this->load->library('barcode_lib');
	}

	public function index()
	{
		$this->session->set_userdata('allow_temp_items', 1);
    $receiving_id = $this->receiving_lib->get_receiving_id();
    if($receiving_id > 0){
      $this->receiving_lib->remove_receiving_id();
    }
		$this->_reload();
	}

	public function manage()
	{
		$person_id = $this->session->userdata('person_id');

		if(FALSE && !$this->Employee->has_grant('reports_receivings', $person_id))
		{
			redirect('no_access/receivings/reports_receivings');
		}
		else
		{
			$data['table_headers'] = get_receivings_manage_table_headers($this);

      $data['filters'] = array(
        'only_cash'   => $this->lang->line('sales_cash_filter'),
        'only_due'    => $this->lang->line('sales_due_filter'),
        'only_check'  => $this->lang->line('sales_check_filter')
      );

			$this->load->view('receivings/manage', $data);
		}
	}

	public function get_row($row_id)
	{
    $filters = array(
      'sale_type'         => 'all',
      'location_id'       => 'all',
      'start_date'        => '',
      'end_date'          => '',
      'only_cash'         => FALSE,
      'only_due'          => FALSE,
      'only_check'        => FALSE,
      'only_invoices'     => $this->config->item('invoice_enable') && $this->input->get('only_invoices'),
      'is_valid_receipt'  => '',
      'receiving_id'      => $row_id
    );
		$receiving_info = $this->Receiving->search('', $filters)->row();
		$data_row = $this->xss_clean(get_receiving_data_row($this, $receiving_info));

		echo json_encode($data_row);
	}

	public function search()
	{
		$search = $this->input->get('search');
		$limit = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort = $this->input->get('sort');
		$order = $this->input->get('order');

    $filters = array(
      'sale_type'         => 'all',
      'location_id'       => 'all',
      'start_date'        => $this->input->get('start_date'),
      'end_date'          => $this->input->get('end_date'),
      'only_cash'         => FALSE,
      'only_due'          => FALSE,
      'only_check'        => FALSE,
      'only_invoices'     => $this->config->item('invoice_enable') && $this->input->get('only_invoices'),
      'is_valid_receipt'  => $this->Receiving->is_valid_receipt($search)
    );

		// check if any filter is set in the multiselect dropdown
		$filledup = array_fill_keys($this->input->get('filters'), TRUE);
		$filters = array_merge($filters, $filledup);

		$receivings = $this->Receiving->search($search, $filters, $limit, $offset, $sort, $order);
    $total_rows = $this->Receiving->get_found_rows($search, $filters);
		// $payments = $this->Sale->get_payments_summary($search, $filters);
		// $payment_summary = $this->xss_clean(get_sales_manage_payments_summary($payments, $receivings));

		$data_rows = array();
		foreach($receivings->result() as $receiving)
		{
			$data_rows[] = $this->xss_clean(get_receiving_data_row($this, $receiving));
		}

		if($total_rows > 0)
		{
			$data_rows[] = $this->xss_clean(get_receiving_data_last_row($this, $receivings));
		}

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows, 'payment_summary' => ''));
	}

	public function item_search()
	{
		$suggestions = $this->Item->get_search_suggestions($this->input->get('term'), array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE);
		$suggestions = array_merge($suggestions, $this->Item_kit->get_search_suggestions($this->input->get('term')));

		$suggestions = $this->xss_clean($suggestions);

		echo json_encode($suggestions);
	}

	public function stock_item_search()
	{
		$suggestions = $this->Item->get_stock_search_suggestions($this->input->get('term'), array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE);
		$suggestions = array_merge($suggestions, $this->Item_kit->get_search_suggestions($this->input->get('term')));

		$suggestions = $this->xss_clean($suggestions);

		echo json_encode($suggestions);
	}

	public function select_supplier()
	{
		$supplier_id = $this->input->post('supplier');
		if($this->Supplier->exists($supplier_id))
		{
			$this->receiving_lib->set_supplier($supplier_id);
		}

		$this->_reload();
	}

	public function change_mode()
	{
		$stock_destination = $this->input->post('stock_destination');
		$stock_source = $this->input->post('stock_source');

		if((!$stock_source || $stock_source == $this->receiving_lib->get_stock_source()) &&
			(!$stock_destination || $stock_destination == $this->receiving_lib->get_stock_destination()))
		{
			$this->receiving_lib->clear_reference();
			$mode = $this->input->post('mode');
			$this->receiving_lib->set_mode($mode);
		}
		elseif($this->Stock_location->is_allowed_location($stock_source, 'receivings'))
		{
			$this->receiving_lib->set_stock_source($stock_source);
			$this->receiving_lib->set_stock_destination($stock_destination);
		}

		$this->_reload();
	}
	
	public function set_comment()
	{
		$this->receiving_lib->set_comment($this->input->post('comment'));
	}

	public function set_print_after_sale()
	{
		$this->receiving_lib->set_print_after_sale($this->input->post('recv_print_after_sale'));
	}
	
	public function set_reference()
	{
		$this->receiving_lib->set_reference($this->input->post('recv_reference'));
	}
	
	public function add()
	{
		$data = array();

		$mode = $this->receiving_lib->get_mode();
		$item_id_or_number_or_item_kit_or_receipt = $this->input->post('item');
		$this->token_lib->parse_barcode($quantity, $price, $item_id_or_number_or_item_kit_or_receipt);
		$quantity = ($mode == 'receive' || $mode == 'requisition') ? $quantity : -$quantity;
		$item_location = $this->receiving_lib->get_stock_source();
		$discount = $this->config->item('default_receivings_discount');
		$discount_type = $this->config->item('default_receivings_discount_type');

		if($mode == 'return' && $this->Receiving->is_valid_receipt($item_id_or_number_or_item_kit_or_receipt))
		{
			$this->receiving_lib->return_entire_receiving($item_id_or_number_or_item_kit_or_receipt);
		}
		elseif($this->Item_kit->is_valid_item_kit($item_id_or_number_or_item_kit_or_receipt))
		{
			$this->receiving_lib->add_item_kit($item_id_or_number_or_item_kit_or_receipt, $item_location, $discount, $discount_type);
		}
		elseif(!$this->receiving_lib->add_item($item_id_or_number_or_item_kit_or_receipt, $quantity, $item_location, $discount,  $discount_type))
		{
			$data['error'] = $this->lang->line('receivings_unable_to_add_item');
		}

		$this->_reload($data);
	}

	public function edit_item($item_id)
	{
		$data = array();

		$this->form_validation->set_rules('price', 'lang:items_price', 'required|callback_numeric');
		$this->form_validation->set_rules('quantity', 'lang:items_quantity', 'required|callback_numeric');
		$this->form_validation->set_rules('discount', 'lang:items_discount', 'required|callback_numeric');

		$description = $this->input->post('description');
		$serialnumber = $this->input->post('serialnumber');
		$price = parse_decimals($this->input->post('price'));
		$quantity = parse_quantity($this->input->post('quantity'));
		$discount = parse_decimals($this->input->post('discount'));
		$discount_type = $this->input->post('discount_type');
		$item_location = $this->input->post('location');
		$receiving_quantity = $this->input->post('receiving_quantity');

		if($this->form_validation->run() != FALSE)
		{
			$this->receiving_lib->edit_item($item_id, $description, $serialnumber, $quantity, $discount, $discount_type, $price, $receiving_quantity);
		}
		else
		{
			$data['error']=$this->lang->line('receivings_error_editing_item');
		}

		$this->_reload($data);
	}
	
	public function edit($receiving_id)
	{
		$data = array();

		$data['suppliers'] = array('' => 'No Supplier');
		foreach($this->Supplier->get_all()->result() as $supplier)
		{
			$data['suppliers'][$supplier->person_id] = $this->xss_clean($supplier->first_name . ' ' . $supplier->last_name);
		}
	
		$data['employees'] = array();
		foreach($this->Employee->get_all()->result() as $employee)
		{
			$data['employees'][$employee->person_id] = $this->xss_clean($employee->first_name . ' '. $employee->last_name);
		}
	
		$receiving_info = $this->xss_clean($this->Receiving->get_info($receiving_id)->row_array());
		$data['selected_supplier_name'] = !empty($receiving_info['supplier_id']) ? $receiving_info['company_name'] : '';
		$data['selected_supplier_id'] = $receiving_info['supplier_id'];
		$data['receiving_info'] = $receiving_info;
	
		$this->load->view('receivings/form', $data);
	}

	public function delete_item($item_number)
	{
		$this->receiving_lib->delete_item($item_number);

		$this->_reload();
	}
	
	public function delete($receiving_id = -1, $update_inventory = TRUE) 
	{
		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$receiving_ids = $receiving_id == -1 ? $this->input->post('ids') : array($receiving_id);
    if(empty($receiving_ids)){
      echo json_encode(array('success' => FALSE, 'message' => 'No receiving ids provided'));
      exit();
    }
	
		if($this->Receiving->delete_list($receiving_ids, $employee_id, $update_inventory))
		{
			echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('receivings_successfully_deleted') . ' ' .
							count($receiving_ids) . ' ' . $this->lang->line('receivings_one_or_multiple'), 'ids' => $receiving_ids));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('receivings_cannot_be_deleted')));
		}
	}

	public function remove_supplier()
	{
		$this->receiving_lib->clear_reference();
		$this->receiving_lib->remove_supplier();

		$this->_reload();
	}

	public function complete()
	{
		$data = array();
    
    $receiving_id = $this->receiving_lib->get_receiving_id();
		$data['cart'] = $this->receiving_lib->get_cart();
		$data['total'] = $this->receiving_lib->get_total();
		$data['transaction_time'] = to_datetime(time());
		$data['mode'] = $this->receiving_lib->get_mode();
		$data['comment'] = $this->receiving_lib->get_comment();
		$data['reference'] = $this->receiving_lib->get_reference();
		$data['payment_type'] = $this->input->post('payment_type');
		$data['show_stock_locations'] = $this->Stock_location->show_locations('receivings');
		$data['stock_location'] = $this->receiving_lib->get_stock_source();
    $data['amount_tendered'] = 0;
		if($this->input->post('amount_tendered') != NULL)
		{
			$data['amount_tendered'] = $this->input->post('amount_tendered');
			$data['amount_change'] = to_currency($data['amount_tendered'] - $data['total']);
		}
		
		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
		$employee_info = $this->Employee->get_info($employee_id);
		$data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;

		$supplier_info = '';
		$supplier_id = $this->receiving_lib->get_supplier();
		if($supplier_id != -1)
		{
			$supplier_info = $this->Supplier->get_info($supplier_id);
			$data['supplier'] = $supplier_info->company_name;
			$data['first_name'] = $supplier_info->first_name;
			$data['last_name'] = $supplier_info->last_name;
			$data['supplier_email'] = $supplier_info->email;
			$data['supplier_address'] = $supplier_info->address_1;
			if(!empty($supplier_info->zip) or !empty($supplier_info->city))
			{
				$data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;				
			}
			else
			{
				$data['supplier_location'] = '';
			}
		}
		
		//SAVE receiving to database
		$data['receiving_id'] = 'RECV ' . $this->Receiving->save($data['cart'], $supplier_id, $employee_id, $data['comment'], $data['reference'], $data['payment_type'], $receiving_id, $data['amount_tendered']);

		$data = $this->xss_clean($data);

		if($data['receiving_id'] == 'RECV -1')
		{
			$data['error_message'] = $this->lang->line('receivings_transaction_failed');
		}
		else
		{
			$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['receiving_id']);				
		}

		$data['print_after_sale'] = $this->receiving_lib->is_print_after_sale();

		$this->load->view("receivings/receipt",$data);

		$this->receiving_lib->clear_all();
	}

	public function requisition_complete()
	{
		if($this->receiving_lib->get_stock_source() != $this->receiving_lib->get_stock_destination()) 
		{
			foreach($this->receiving_lib->get_cart() as $item)
			{
				$this->receiving_lib->delete_item($item['line']);
				$this->receiving_lib->add_item($item['item_id'], $item['quantity'], $this->receiving_lib->get_stock_destination(), $item['discount_type']);
				$this->receiving_lib->add_item($item['item_id'], -$item['quantity'], $this->receiving_lib->get_stock_source(), $item['discount_type']);
			}
			
			$this->complete();
		}
		else 
		{
			$data['error'] = $this->lang->line('receivings_error_requisition');

			$this->_reload($data);	
		}
	}
	
	public function receipt($receiving_id)
	{
		$receiving_info = $this->Receiving->get_info($receiving_id)->row_array();
		$this->receiving_lib->copy_entire_receiving($receiving_id);
		$data['cart'] = $this->receiving_lib->get_cart();
		$data['total'] = $this->receiving_lib->get_total();
		$data['mode'] = $this->receiving_lib->get_mode();
		$data['transaction_time'] = to_datetime(strtotime($receiving_info['receiving_time']));
		$data['show_stock_locations'] = $this->Stock_location->show_locations('receivings');
		$data['payment_type'] = $receiving_info['payment_type'];
		$data['reference'] = $this->receiving_lib->get_reference();
		$data['receiving_id'] = 'RECV ' . $receiving_id;
		$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($data['receiving_id']);
		$employee_info = $this->Employee->get_info($receiving_info['employee_id']);
		$data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;

		$supplier_id = $this->receiving_lib->get_supplier();
		if($supplier_id != -1)
		{
			$supplier_info = $this->Supplier->get_info($supplier_id);
			$data['supplier'] = $supplier_info->company_name;
			$data['first_name'] = $supplier_info->first_name;
			$data['last_name'] = $supplier_info->last_name;
			$data['supplier_email'] = $supplier_info->email;
			$data['supplier_address'] = $supplier_info->address_1;
			if(!empty($supplier_info->zip) or !empty($supplier_info->city))
			{
				$data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;				
			}
			else
			{
				$data['supplier_location'] = '';
			}
		}

		$data['print_after_sale'] = FALSE;

		$data = $this->xss_clean($data);
		
		$this->load->view("receivings/receipt", $data);

		$this->receiving_lib->clear_all();
	}

  public function editdetail($receiving_id){
		$this->receiving_lib->copy_entire_receiving($receiving_id);
    $this->_reload();
  }

	private function _reload($data = array())
	{
    if(stripos($this->input->server('HTTP_REFERER'), 'index2') !== FALSE){
      redirect('/receivings/index2');
    }
		$data['cart'] = $this->receiving_lib->get_cart();
		$data['modes'] = array('receive' => $this->lang->line('receivings_receiving'), 'return' => $this->lang->line('receivings_return'));
		$data['mode'] = $this->receiving_lib->get_mode();
		$data['stock_locations'] = $this->Stock_location->get_allowed_locations('receivings');
		$data['show_stock_locations'] = count($data['stock_locations']) > 1;
		if($data['show_stock_locations']) 
		{
			$data['modes']['requisition'] = $this->lang->line('receivings_requisition');
			$data['stock_source'] = $this->receiving_lib->get_stock_source();
			$data['stock_destination'] = $this->receiving_lib->get_stock_destination();
		}

		$data['total'] = $this->receiving_lib->get_total();
		$data['items_module_allowed'] = $this->Employee->has_grant('items', $this->Employee->get_logged_in_employee_info()->person_id);
		$data['comment'] = $this->receiving_lib->get_comment();
		$data['reference'] = $this->receiving_lib->get_reference();
		$data['payment_options'] = $this->Receiving->get_payment_options();

		$supplier_id = $this->receiving_lib->get_supplier();
		$supplier_info = '';
		if($supplier_id != -1)
		{
			$supplier_info = $this->Supplier->get_info($supplier_id);
			$data['supplier'] = $supplier_info->company_name;
			$data['first_name'] = $supplier_info->first_name;
			$data['last_name'] = $supplier_info->last_name;
			$data['supplier_email'] = $supplier_info->email;
			$data['supplier_address'] = $supplier_info->address_1;
			if(!empty($supplier_info->zip) or !empty($supplier_info->city))
			{
				$data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;				
			}
			else
			{
				$data['supplier_location'] = '';
			}
		}
		
		$data['print_after_sale'] = $this->receiving_lib->is_print_after_sale();

		$data = $this->xss_clean($data);

		$this->load->view("receivings/receiving", $data);
	}
	
  public function index2()
  {
		$data['cart'] = $this->receiving_lib->get_cart();
		$data['modes'] = array('receive' => $this->lang->line('receivings_receiving'), 'return' => $this->lang->line('receivings_return'));
		$data['mode'] = $this->receiving_lib->get_mode();
		$data['stock_locations'] = $this->Stock_location->get_allowed_locations('receivings');
		$data['show_stock_locations'] = count($data['stock_locations']) > 1;
		if($data['show_stock_locations']) 
		{
			$data['modes']['requisition'] = $this->lang->line('receivings_requisition');
			$data['stock_source'] = $this->receiving_lib->get_stock_source();
			$data['stock_destination'] = $this->receiving_lib->get_stock_destination();
		}

		$data['total'] = $this->receiving_lib->get_total();
		$data['items_module_allowed'] = $this->Employee->has_grant('items', $this->Employee->get_logged_in_employee_info()->person_id);
		$data['comment'] = $this->receiving_lib->get_comment();
		$data['reference'] = $this->receiving_lib->get_reference();
		$data['payment_options'] = $this->Receiving->get_payment_options();

		$supplier_id = $this->receiving_lib->get_supplier();
		$supplier_info = '';
		if($supplier_id != -1)
		{
			$supplier_info = $this->Supplier->get_info($supplier_id);
			$data['supplier'] = $supplier_info->company_name;
			$data['first_name'] = $supplier_info->first_name;
			$data['last_name'] = $supplier_info->last_name;
			$data['supplier_email'] = $supplier_info->email;
			$data['supplier_address'] = $supplier_info->address_1;
			if(!empty($supplier_info->zip) or !empty($supplier_info->city))
			{
				$data['supplier_location'] = $supplier_info->zip . ' ' . $supplier_info->city;				
			}
			else
			{
				$data['supplier_location'] = '';
			}
		}
		
		$data['print_after_sale'] = $this->receiving_lib->is_print_after_sale();

    $data = $this->xss_clean($data);
    
    $this->load->view('receivings/receiving_new', $data);
  }

  public function update_cart($item_id)
	{
		$data = array();

		$this->form_validation->set_rules('price', 'lang:items_price', 'required|callback_numeric');
		$this->form_validation->set_rules('quantity', 'lang:items_quantity', 'required|callback_numeric');
		$this->form_validation->set_rules('discount', 'lang:items_discount', 'required|callback_numeric');

		$description = $this->input->post('description');
		$serialnumber = $this->input->post('serialnumber');
		$price = parse_decimals($this->input->post('price'));
		$quantity = parse_quantity($this->input->post('quantity'));
		$discount = parse_decimals($this->input->post('discount'));
		$discount_type = $this->input->post('discount_type');
		$item_location = $this->input->post('location');
		$receiving_quantity = $this->input->post('receiving_quantity');

		if($this->form_validation->run() != FALSE)
		{
			$this->receiving_lib->edit_item($item_id, $description, $serialnumber, $quantity, $discount, $discount_type, $price, $receiving_quantity);
		}
		else
		{
			$data['error']=$this->lang->line('receivings_error_editing_item');
		}

		$this->_reload($data);
	}

  public function get_cart()
  {
    // $items = $this->receiving_lib->get_cart();
    $data['currency_symbol'] = $this->config->item('currency_symbol');
    $data['cart_items'] = [];
    foreach(array_reverse($this->receiving_lib->get_cart()) as $line=>$item){
      $item['price'] = (double) $item['price'];
      $item['in_stock'] = to_quantity_decimals($item['in_stock']);
      $item['quantity'] = to_quantity_decimals($item['quantity']);
      $item['receiving_quantity'] = to_quantity_decimals($item['receiving_quantity']);
      $item['total'] = (double) $item['total'];
      $data['cart_items'][] = $item;
    }
    $data['supplier'] = null;
		$supplier_id = $this->receiving_lib->get_supplier();
		if($supplier_id != -1)
		{
      $supplier_info = $this->Supplier->get_info($supplier_id);
      $data['supplier'] = [];
			$data['supplier']['company_name'] = $supplier_info->company_name;
			$data['supplier']['first_name'] = $supplier_info->first_name;
			$data['supplier']['last_name'] = $supplier_info->last_name;
			$data['supplier']['email'] = $supplier_info->email;
			$data['supplier']['address'] = $supplier_info->address_1;
      $data['supplier']['location'] = '';
			if(!empty($supplier_info->zip) or !empty($supplier_info->city))
			{
				$data['supplier']['location'] = $supplier_info->zip . ' ' . $supplier_info->city;				
			}
    }
    echo json_encode($data);
  }

	public function save($receiving_id = -1)
	{
		$newdate = $this->input->post('date');
		
		$date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $newdate);

		$receiving_data = array(
			'receiving_time' => $date_formatter->format('Y-m-d H:i:s'),
			'supplier_id' => $this->input->post('supplier_id') ? $this->input->post('supplier_id') : NULL,
			'employee_id' => $this->input->post('employee_id'),
			'comment' => $this->input->post('comment'),
			'reference' => $this->input->post('reference') != '' ? $this->input->post('reference') : NULL
		);
	
		if($this->Receiving->update($receiving_data, $receiving_id))
		{
			echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('receivings_successfully_updated'), 'id' => $receiving_id));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('receivings_unsuccessfully_updated'), 'id' => $receiving_id));
		}
	}

	public function cancel_receiving()
	{
		$this->receiving_lib->clear_all();

		$this->_reload();
	}
  
  public function addpayment($receiving_id)
	{
    $receiving = $this->Receiving->get_info_payments($receiving_id);
		$data['receiving'] = $receiving;
    if($this->input->post('add_payment')){
      $payment_amount = $this->input->post('payment_amount');
      $payment_id = 0;
      if($payment_amount){
        if($payment_amount > $receiving->balance){
          echo json_encode(array(
            'success' => FALSE,
            'message' => 'Payment amount must be less or equal to ' . $receiving->balance,
            'id'      => $receiving_id
          ));
          return;
        }
        $receiving_payment = [];
        $receiving_payment['receiving_id'] = $receiving_id;
        $receiving_payment['amount_tendered'] = $payment_amount;
        $receiving_payment['reference'] = $this->input->post('reference');
        $date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('payment_date'));
        $receiving_payment['payment_date'] = $date_formatter->format('Y-m-d H:i:s');
        $payment_id = $this->Supplier->add_payment($receiving->supplier_id, $receiving_payment);
      }
      if($payment_id){
        if($receiving->payment_type == 'Due'){
          if(($receiving->balance - $payment_amount) <= 0){
            $this->Receiving->update(['payment_type' => 'Cash'], $receiving_id);
          }
        }
        echo json_encode(array(
          'success' => TRUE,
          'message' => 'Payment record added for RECV # ' . $receiving_id,
          'id'      => $receiving_id
        ));
      }
      else{
        echo json_encode(array(
          'success' => FALSE,
          'message' => 'Payment record failed for RECV # ' . $receiving_id,
          'id'      => $receiving_id
        ));
      }
      return;
    }
		$this->load->view("receivings/add_payment", $data);
	}
}
?>
