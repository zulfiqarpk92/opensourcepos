<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * labor class
 */

class Labor extends Person
{
	/*
	Determines if a given person_id is a labor
	*/
	public function exists($person_id)
	{
		$this->db->from('labors');
		$this->db->join('people', 'people.person_id = labors.person_id');
		$this->db->where('labors.person_id', $person_id);

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('labors');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}

	/*
	Returns all the labors
	*/
	public function get_all($rows = 0, $limit_from = 0)
	{
		$this->db->from('labors');
		$this->db->join('people', 'labors.person_id = people.person_id');
		$this->db->where('deleted', 0);
		$this->db->order_by('last_name', 'asc');

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	/*
	Gets information about a particular labor
	*/
	public function get_info($labor_id)
	{
    $this->db->select('people.*, labors.*, 
    SUM(IF(labors_payments.credit = 0, 0, labors_payments.amount)) AS credit, 
    SUM(IF(labors_payments.credit = 1, 0, labors_payments.amount)) AS debit', FALSE);
		$this->db->from('labors');
		$this->db->join('people', 'people.person_id = labors.person_id');
		$this->db->join('labors_payments AS labors_payments', 'labors.person_id = labors_payments.labor_id', 'LEFT');
		$this->db->where('labors.person_id', $labor_id);
		$this->db->group_by('labors.person_id');
		$query = $this->db->get();

		if($query->num_rows() == 1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $labor_id is NOT a labor
			$person_obj = parent::get_info(-1);

			//Get all the fields from labor table
			//append those fields to base parent object, we we have a complete empty object
			foreach($this->db->list_fields('labors') as $field)
			{
				$person_obj->$field = '';
			}
      $person_obj->credit = 0;
      $person_obj->debit = 0;

			return $person_obj;
		}
	}

	/*
	Gets information about multiple labors
	*/
	public function get_multiple_info($labor_ids)
	{
		$this->db->from('labors');
		$this->db->join('people', 'people.person_id = labors.person_id');
		$this->db->where_in('labors.person_id', $labor_ids);
		$this->db->order_by('last_name', 'asc');

		return $this->db->get();
	}

	/*
	Checks if labor email exists
	*/
	public function check_email_exists($email, $labor_id = '')
	{
		// if the email is empty return like it is not existing
		if(empty($email))
		{
			return FALSE;
		}

		$this->db->from('labors');
		$this->db->join('people', 'people.person_id = labors.person_id');
		$this->db->where('people.email', $email);
		$this->db->where('labors.deleted', 0);

		if(!empty($labor_id))
		{
			$this->db->where('labors.person_id !=', $labor_id);
		}

		return ($this->db->get()->num_rows() == 1);
	}

	/*
	Inserts or updates a labor
	*/
	public function save_labor(&$person_data, &$labor_data, $labor_id = FALSE)
	{
		$success = FALSE;

		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		if(parent::save($person_data, $labor_id))
		{
			if(!$labor_id || !$this->exists($labor_id))
			{
				$labor_data['person_id'] = $person_data['person_id'];
				$success = $this->db->insert('labors', $labor_data);
			}
			else
			{
				$this->db->where('person_id', $labor_id);
				$success = $this->db->update('labors', $labor_data);
			}
		}

		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	/*
	Deletes one labor
	*/
	public function delete($labor_id)
	{
		$result = TRUE;

		// if privacy enforcement is selected scramble labor data
		if($this->config->item('enforce_privacy'))
		{
			$this->db->where('person_id', $labor_id);

			$result &= $this->db->update('people', array(
					'first_name'	=> $labor_id,
					'last_name'		=> $labor_id,
					'phone_number'	=> '',
					'email'			=> '',
					'gender'		=> NULL,
					'address_1'		=> '',
					'address_2'		=> '',
					'city'			=> '',
					'state'			=> '',
					'zip'			=> '',
					'country'		=> '',
					'comments'		=> ''
				));

			$this->db->where('person_id', $labor_id);

			$result &= $this->db->update('labors', array('deleted' => 1));
		}
		else
		{
			$this->db->where('person_id', $labor_id);

			$result &= $this->db->update('labors', array('deleted' => 1));
		}

		return $result;
	}

	/*
	Deletes a list of labors
	*/
	public function delete_list($labor_ids)
	{
		$this->db->where_in('person_id', $labor_ids);

		return $this->db->update('labors', array('deleted' => 1));
 	}

 	/*
	Get search suggestions to find labors
	*/
	public function get_search_suggestions($search, $unique = TRUE, $limit = 25)
	{
		$suggestions = array();

		$this->db->from('labors');
		$this->db->join('people', 'labors.person_id = people.person_id');
		$this->db->group_start();
			$this->db->like('first_name', $search);
			$this->db->or_like('last_name', $search);
			$this->db->or_like('CONCAT(first_name, " ", last_name)', $search);
			if($unique)
			{
				$this->db->or_like('email', $search);
				$this->db->or_like('phone_number', $search);
			}
		$this->db->group_end();
		$this->db->where('deleted', 0);
		$this->db->order_by('first_name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->person_id, 'label' => $row->first_name . ' ' . $row->last_name . (!empty($row->phone_number) ? ' [' . $row->phone_number . ']' : ''));
		}

		if(!$unique)
		{
			$this->db->from('labors');
			$this->db->join('people', 'labors.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('email', $search);
			$this->db->order_by('email', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->email);
			}

			$this->db->from('labors');
			$this->db->join('people', 'labors.person_id = people.person_id');
			$this->db->where('deleted', 0);
			$this->db->like('phone_number', $search);
			$this->db->order_by('phone_number', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$suggestions[] = array('value' => $row->person_id, 'label' => $row->phone_number);
			}
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

 	/*
	Gets rows
	*/
	public function get_found_rows($search)
	{
		return $this->search($search, 0, 0, 'last_name', 'asc', TRUE);
	}

	/*
	Performs a search on labors
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'last_name', $order = 'asc', $count_only = FALSE)
	{
    $this->db->select('people.*, labors.*, 
    SUM(IF(labors_payments.credit = 0, 0, labors_payments.amount)) AS credit, 
    SUM(IF(labors_payments.credit = 1, 0, labors_payments.amount)) AS debit', FALSE);
		$this->db->from('labors');
		$this->db->join('people', 'labors.person_id = people.person_id');
		$this->db->join('labors_payments AS labors_payments', 'labors.person_id = labors_payments.labor_id', 'LEFT');
		$this->db->group_start();
			$this->db->like('first_name', $search);
			$this->db->or_like('last_name', $search);
			$this->db->or_like('email', $search);
			$this->db->or_like('phone_number', $search);
			$this->db->or_like('CONCAT(first_name, " ", last_name)', $search);
		$this->db->group_end();
		$this->db->where('deleted', 0);

		// get_found_rows case
		if($count_only == TRUE)
		{
			return $this->db->get()->num_rows();
		}

		$this->db->group_by('labors.person_id');
		$this->db->order_by($sort, $order);

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}
  
  public function add_payment($labor_id, $payment)
  {
    if(empty($labor_id) OR empty($payment['amount'])){
      return FALSE;
    }
    $payment['labor_id']  = $labor_id;
    $this->db->insert('labors_payments', $payment);
    return $this->db->insert_id();
  }
  
  public function get_payments($labor_id)
  {
		$this->db->where('labor_id', $labor_id);
		$this->db->from('labors_payments');
    $this->db->order_by('payment_date');
		return $this->db->get()->result();
	}
  
  public function remove_payment($payment_id)
  {
    $payment = $this->db->where('payment_id', $payment_id)->get('labors_payments')->row();
    if($payment){
      $this->db->where('payment_id', $payment_id);
      $this->db->from('labors_payments');
      $this->db->delete();
    }
    return $payment;
	}
}
