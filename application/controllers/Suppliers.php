<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Persons.php");

class Suppliers extends Persons
{
	public function __construct()
	{
		parent::__construct('suppliers');
	}

	public function index()
	{
		$data['table_headers'] = $this->xss_clean(get_suppliers_manage_table_headers());

		$this->load->view('people/manage', $data);
	}

	/*
	Gets one row for a supplier manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
    $supplier = $this->Supplier->get_info($row_id);
    $total_purchases = $this->Supplier->get_total_purchases($supplier->person_id);
    $total_payments = $this->Supplier->get_total_payment($supplier->person_id);
    $supplier->total_purchases = $supplier->init_balance + $total_purchases;
    $supplier->total_payments = $total_payments;
    $supplier->total_due = $supplier->total_purchases - $total_payments;
		$data_row = $this->xss_clean(get_supplier_data_row($supplier));

		echo json_encode($data_row);
	}
	
	/*
	Returns Supplier table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->input->get('search');
		$limit  = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort   = $this->input->get('sort');
		$order  = $this->input->get('order');

		$suppliers = $this->Supplier->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Supplier->get_found_rows($search);

		$data_rows = array();
		foreach($suppliers->result() as $supplier)
		{	
			$row = $this->xss_clean(get_supplier_data_row($supplier));
			$row['category'] = $this->Supplier->get_category_name($row['category']);
			$data_rows[] = $row;
		}
		
		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}
	
	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Supplier->get_search_suggestions($this->input->get('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Supplier->get_search_suggestions($this->input->post('term'), FALSE));

		echo json_encode($suggestions);
	}
	
	/*
	Loads the supplier edit form
	*/
	public function view($supplier_id = -1)
	{
		$info = $this->Supplier->get_info($supplier_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['person_info'] = $info;
		$data['categories'] = $this->Supplier->get_categories();

		if(empty($info->person_id))
		{
      $data['person_info']->gender = '1';
		}

    $filters = array(
      'sale_type'         => 'all',
      'location_id'       => 'all',
      'start_date'        => '',
      'end_date'          => '',
      'only_cash'         => FALSE,
      'only_due'          => FALSE,
      'only_check'        => FALSE,
      'only_invoices'     => FALSE,
      'is_valid_receipt'  => FALSE,
      'supplier_id'       => $supplier_id,
    );
    $receivings = $this->Receiving->search('', $filters, 0, 0, 'receivings.receiving_time', 'asc');
    $total_rows = $this->Receiving->get_found_rows('', $filters);

    $data_rows = array();
		foreach($receivings->result() as $receiving)
		{
			$data_rows[] = $this->xss_clean(get_receiving_data_row($this, $receiving));
		}

		if($total_rows > 0)
		{
			$data_rows[] = $this->xss_clean(get_receiving_data_last_row($this, $receivings));
		}
    $data['purchases'] = $data_rows;
    $data['purchase_headers'] = [];
    foreach(json_decode(get_receivings_manage_table_headers($this), TRUE) as $header)
    {
      if(in_array($header['field'], ['receiving_time', 'quantity', 'total_amount', 'balance']))
      {
        $data['purchase_headers'][] = $header;
      }
    }
    $data['payment_headers'] = [
      'payment_time'     => 'Payment Time',
      'receiving_id'     => 'Recv #', 
      'amount_tendered'  => 'Amount', 
      'reference'        => 'Reference', 
      'comments'         => 'Comments'
    ];
    $data['payments'] = [];
    $payments_total = 0;
    foreach($this->Supplier->get_payments($supplier_id) as $payment){
      $data['payments'][] = [
        'id'               => $payment->supplier_payment_id,
        'payment_time'     => to_datetime(strtotime($payment->payment_date)),
        'receiving_id'     => $payment->receiving_id,
        'amount_tendered'  => to_currency($payment->amount_tendered), 
        'reference'        => $payment->reference, 
        'comments'         => $payment->comments
      ];
      $payments_total += $payment->amount_tendered;
    }
    $data['payments'][] = [
      'id'               => '',
      'payment_time'     => '<b>Total</b>',
      'receiving_id'     => '',
      'amount_tendered'  => to_currency($payments_total), 
      'reference'        => '', 
      'comments'         => ''
    ];

		$this->load->view("suppliers/form", $data);
	}
	
  public function add_payment($supplier_id = -1)
	{
		$info = $this->Supplier->get_info($supplier_id);
		$data['person_info'] = $info;
    if($this->input->post('add_payment')){
      $amount_tendered = $this->input->post('amount_tendered');
      $payment_id = 0;
      if($amount_tendered){
        $supplier_payment = [];
        $supplier_payment['amount_tendered'] = $amount_tendered;
        $supplier_payment['reference'] = $this->input->post('reference');
        $supplier_payment['comments'] = $this->input->post('comments');
        $date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('payment_date'));
        $supplier_payment['payment_date'] = $date_formatter->format('Y-m-d H:i:s');
        $payment_id = $this->Supplier->add_payment($supplier_id, $supplier_payment);
      }
      if($payment_id){
        echo json_encode(array(
          'success' => TRUE,
          'message' => 'Payment record added for ' . $info->company_name,
          'id'      => $supplier_id
        ));
      }
      else{
        echo json_encode(array(
          'success' => FALSE,
          'message' => 'Payment record failed for ' . $info->company_name,
          'id'      => $supplier_id
        ));
      }
      return;
    }

		$this->load->view("suppliers/add_payment", $data);
	}
	
  public function removepayment($payment_id = 0){
    if($payment_id){
      $deleted_payment = $this->Supplier->remove_payment($payment_id);
      $payments_total = 0;
      $payments = [];
      foreach($this->Supplier->get_payments($deleted_payment->supplier_id) as $payment){
        $payments[] = [
          'id'               => $payment->supplier_payment_id,
          'payment_time'     => to_datetime(strtotime($payment->payment_date)), 
          'amount_tendered'  => to_currency($payment->amount_tendered), 
          'reference'        => $payment->reference, 
          'comments'         => $payment->comments
        ];
        $payments_total += $payment->amount_tendered;
      }
      $payments[] = [
        'id'               => '',
        'payment_time'     => '<b>Total</b>', 
        'amount_tendered'  => to_currency($payments_total), 
        'reference'        => '', 
        'comments'         => ''
      ];
      echo json_encode(array(
        'success' => TRUE,
        'message' => 'Payment record deleted',
        'payments'=> $payments
      ));
      return;
    }
    echo json_encode(array(
      'success' => FALSE,
      'message' => 'Payment record deletion failed',
      'payments'=> []
    ));
  }
	
	/*
	Inserts/updates a supplier
	*/
	public function save($supplier_id = -1)
	{
		$first_name = $this->xss_clean($this->input->post('first_name'));
		$last_name = $this->xss_clean($this->input->post('last_name'));
		$email = $this->xss_clean(strtolower($this->input->post('email')));

		// format first and last name properly
		$first_name = $this->nameize($first_name);
		$last_name = $this->nameize($last_name);

		$person_data = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'gender' => $this->input->post('gender'),
			'email' => $email,
			'phone_number' => $this->input->post('phone_number'),
			'address_1' => $this->input->post('address_1'),
			'address_2' => $this->input->post('address_2'),
			'city' => $this->input->post('city'),
			'state' => $this->input->post('state'),
			'zip' => $this->input->post('zip'),
			'country' => $this->input->post('country'),
			'comments' => $this->input->post('comments')
		);

		$supplier_data = array(
			'company_name' => $this->input->post('company_name'),
			'agency_name' => $this->input->post('agency_name') ?: '',
			'category' => $this->input->post('category'),
			'account_number' => $this->input->post('account_number') == '' ? NULL : $this->input->post('account_number'),
			'init_balance' => $this->input->post('init_balance') ?: 0,
			'tax_id' => $this->input->post('tax_id')
		);

		if($this->Supplier->save_supplier($person_data, $supplier_data, $supplier_id))
		{
			$supplier_data = $this->xss_clean($supplier_data);

			//New supplier
			if($supplier_id == -1)
			{
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('suppliers_successful_adding') . ' ' . $supplier_data['company_name'],
								'id' => $supplier_data['person_id']));
			}
			else //Existing supplier
			{
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('suppliers_successful_updating') . ' ' . $supplier_data['company_name'],
								'id' => $supplier_id));
			}
		}
		else//failure
		{
			$supplier_data = $this->xss_clean($supplier_data);

			echo json_encode(array('success' => FALSE,
							'message' => $this->lang->line('suppliers_error_adding_updating') . ' ' . 	$supplier_data['company_name'],
							'id' => -1));
		}
	}
	
	/*
	This deletes suppliers from the suppliers table
	*/
	public function delete()
	{
		$suppliers_to_delete = $this->xss_clean($this->input->post('ids'));

		if($this->Supplier->delete_list($suppliers_to_delete))
		{
			echo json_encode(array('success' => TRUE,'message' => $this->lang->line('suppliers_successful_deleted').' '.
							count($suppliers_to_delete).' '.$this->lang->line('suppliers_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE,'message' => $this->lang->line('suppliers_cannot_be_deleted')));
		}
	}
	
}
?>
