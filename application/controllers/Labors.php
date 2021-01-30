<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Persons.php");

class Labors extends Persons
{
	public function __construct()
	{
		parent::__construct('labors');
	}

	public function index()
	{
		$data['table_headers'] = get_labor_manage_table_headers();

		$this->load->view('people/manage', $data);
	}

	/*
	Gets one row for a Labor manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$person = $this->Labor->get_info($row_id);

		$data_row = get_labor_data_row($person);

		echo json_encode($data_row);
	}

	/*
	Returns labor table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->input->get('search');
		$limit  = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort   = $this->input->get('sort');
		$order  = $this->input->get('order');

		$labors = $this->Labor->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Labor->get_found_rows($search);

		$data_rows = array();
		foreach($labors->result() as $person)
		{
			$data_rows[] = get_labor_data_row($person);
    }
    

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Labor->get_search_suggestions($this->input->get('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Labor->get_search_suggestions($this->input->post('term'), FALSE));

		echo json_encode($suggestions);
	}

	/*
	Loads the labor edit form
	*/
	public function view($labor_id = -1)
	{
		$info = $this->Labor->get_info($labor_id);
    // dump($info);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}
		$data['person_info'] = $info;

		if(empty($info->person_id) || empty($info->created_at) || empty($info->employee_id))
		{
			$data['person_info']->created_at = date('Y-m-d H:i:s');
			$data['person_info']->employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
		}

		$employee_info = $this->Employee->get_info($info->employee_id);
		$data['employee'] = $employee_info->first_name . ' ' . $employee_info->last_name;

    $data['payment_headers'] = [
      'payment_time' => 'Txn Time',
      'credit'       => 'Credit', 
      'debit'        => 'Debit', 
      'reference'    => 'Reference', 
      'comments'     => 'Comments'
    ];
    $data['payments'] = [];
    $credit = 0;
    $debit = 0;
    foreach($this->Labor->get_payments($labor_id) as $payment){
      $data['payments'][] = [
        'id'            => $payment->payment_id,
        'payment_time'  => to_date(strtotime($payment->payment_date)),
        'credit'        => $payment->credit ? to_currency($payment->amount) : '',
        'debit'         => $payment->credit ? '' : to_currency($payment->amount),
        'reference'     => $payment->reference,
        'comments'      => $payment->comments
      ];
      if($payment->credit){
        $credit += $payment->amount;
      }
      else{
        $debit += $payment->amount;
      }
    }
    $data['payments'][] = [
      'id'               => '',
      'payment_time'     => '<b>Total</b>',
      'credit'           => to_currency($credit),
      'debit'            => to_currency($debit), 
      'reference'        => '', 
      'comments'         => ''
    ];
    $data['balance'] = to_currency($credit - $debit);

		$this->load->view('labors/form', $data);
	}

	/*
	Inserts/updates a labor
	*/
	public function save($labor_id = -1)
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

		$date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('date'));

		$labor_data = array(
			'created_at' => $date_formatter->format('Y-m-d H:i:s'),
			'employee_id' => $this->input->post('employee_id'),
		);

		if($this->Labor->save_labor($person_data, $labor_data, $labor_id))
		{
			// New labor
			if($labor_id == -1)
			{
				echo json_encode(array(
          'success' => TRUE,
					'message' => $this->lang->line('labors_successful_adding') . ' ' . $first_name . ' ' . $last_name,
          'id'      => $labor_data['person_id']
        ));
			}
			else // Existing labor
			{
				echo json_encode(array(
          'success' => TRUE,
					'message' => $this->lang->line('labors_successful_updating') . ' ' . $first_name . ' ' . $last_name,
          'id'      => $labor_id
        ));
			}
		}
		else // Failure
		{
			echo json_encode(array(
        'success' => FALSE,
				'message' => $this->lang->line('labors_error_adding_updating') . ' ' . $first_name . ' ' . $last_name,
        'id'      => -1
      ));
		}
	}

	/*
	AJAX call to verify if an email address already exists
	*/
	public function ajax_check_email()
	{
		$exists = $this->Labor->check_email_exists(strtolower($this->input->post('email')), $this->input->post('person_id'));

		echo !$exists ? 'true' : 'false';
	}

	/*
	This deletes labors from the labors table
	*/
	public function delete()
	{
		$labors_to_delete = $this->input->post('ids');
		$labors_info = $this->Labor->get_multiple_info($labors_to_delete);

		$count = 0;

		foreach($labors_info->result() as $info)
		{
			if($this->Labor->delete($info->person_id))
			{
				$count++;
			}
		}

		if($count == count($labors_to_delete))
		{
			echo json_encode(array('success' => TRUE,
				'message' => $this->lang->line('labors_successful_deleted') . ' ' . $count . ' ' . $this->lang->line('labors_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('labors_cannot_be_deleted')));
		}
	}

	/*
	labors import from csv spreadsheet
	*/
	public function csv()
	{
		$name = 'import_labors.csv';
		$data = file_get_contents('../' . $name);
		force_download($name, $data);
	}
	
  public function add_payment($labor_id = -1)
	{
		$info = $this->Labor->get_info($labor_id);
		$data['person_info'] = $info;
    if($this->input->post('add_payment')){
      $amount_tendered = $this->input->post('amount_tendered');
      $payment_id = 0;
      if($amount_tendered){
        $payment = [];
        $payment['amount'] = $amount_tendered;
        $payment['credit'] = $this->input->post('credit') ? '1' : '0';
        $payment['reference'] = $this->input->post('reference');
        $payment['comments'] = $this->input->post('comments');
        $date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('payment_date'));
        $payment['payment_date'] = $date_formatter->format('Y-m-d H:i:s');
        $payment_id = $this->Labor->add_payment($labor_id, $payment);
      }
      if($payment_id){
        echo json_encode(array(
          'success' => TRUE,
          'message' => 'Payment record added for ' . $info->first_name,
          'id'      => $labor_id
        ));
      }
      else{
        echo json_encode(array(
          'success' => FALSE,
          'message' => 'Payment record failed for ' . $info->first_name,
          'id'      => $labor_id
        ));
      }
      return;
    }

		$this->load->view("labors/add_payment", $data);
	}
	
  public function remove_payment($payment_id = 0){
    if($payment_id){
      $deleted_payment = $this->Labor->remove_payment($payment_id);
      $credit = 0;
      $debit = 0;
      $payments = [];
      foreach($this->Labor->get_payments($deleted_payment->labor_id) as $payment){
        $payments[] = [
          'id'            => $payment->payment_id,
          'payment_time'  => to_date(strtotime($payment->payment_date)),
          'credit'        => $payment->credit ? to_currency($payment->amount) : '',
          'debit'         => $payment->credit ? '' : to_currency($payment->amount),
          'reference'     => $payment->reference,
          'comments'      => $payment->comments
        ];
        if($payment->credit){
          $credit += $payment->amount;
        }
        else{
          $debit += $payment->amount;
        }
      }
      $payments[] = [
        'id'               => '',
        'payment_time'     => '<b>Total</b>',
        'credit'           => to_currency($credit),
        'debit'            => to_currency($debit), 
        'reference'        => '', 
        'comments'         => ''
      ];
      echo json_encode(array(
        'success' => TRUE,
        'message' => 'Payment record deleted',
        'payments'=> $payments,
        'balance' => to_currency($credit - $debit)
      ));
      return;
    }
    echo json_encode(array(
      'success' => FALSE,
      'message' => 'Payment record deletion failed',
      'payments'=> []
    ));
  }
}
