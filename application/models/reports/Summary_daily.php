<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once("Summary_report.php");

class Summary_daily extends Summary_report
{
  public $summary_for = 'store';

  protected function _get_data_columns()
  {
    if($this->summary_for == 'store'){
      return array(
        array('date'      => $this->lang->line('reports_date'), 'sortable' => FALSE),
        array('sales'     => $this->lang->line('reports_revenue'), 'sorter' => 'number_sorter'),
        array('expenses'  => $this->lang->line('reports_expenses'), 'sorter' => 'number_sorter'),
        array('spayments' => $this->lang->line('reports_spayments'), 'sorter' => 'number_sorter'),
        array('profit'    => $this->lang->line('reports_profit'), 'sorter' => 'number_sorter')
      );
    }
    else{
      return array(
        array('date'      => $this->lang->line('reports_date'), 'sortable' => FALSE),
        array('sales'     => $this->lang->line('reports_revenue'), 'sorter' => 'number_sorter'),
        array('expenses'  => $this->lang->line('reports_expenses'), 'sorter' => 'number_sorter'),
        array('profit'    => $this->lang->line('reports_profit'), 'sorter' => 'number_sorter')
      );
    }
  }

  protected function _select(array $inputs)
  {
    parent::_select($inputs);

    $this->db->select('DATE(sales.sale_time) AS sale_date');
  }

  protected function _where(array $inputs){
    parent::_where($inputs);
    
		$this->db->join('items AS items', 'sales_items.item_id = items.item_id');
    if($inputs['report_for'] == 'store'){
      $this->db->where_not_in('items.category', [$this->config->item('lab_category'), $this->config->item('xray_category')]);
    }
    elseif($inputs['report_for'] == 'lab'){
      $this->db->where('items.category', $this->config->item('lab_category'));
    }
    elseif($inputs['report_for'] == 'xray'){
      $this->db->where('items.category', $this->config->item('xray_category'));
    }
  }

  protected function _group_order()
  {
    $this->db->group_by('sale_date');
    $this->db->order_by('sale_date');
  }

  public function getExpenses(array $inputs)
  {
    $this->db->select('
    DATE(expenses.date) AS expense_date, 
    COUNT(expenses.expense_id) AS count, 
    SUM(IF(expenses.is_monthly = 0, expenses.amount, 0)) AS total_amount,
    SUM(IF(expenses.is_monthly = 1, expenses.amount, 0)) AS monthly_exp
    ');
    $this->db->from('expenses AS expenses');
    $this->db->join('expense_categories AS ec', 'ec.expense_category_id = expenses.expense_category_id');

    if (empty($this->config->item('date_or_time_format'))) {
      $this->db->where('DATE(expenses.date) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
    } else {
      $this->db->where('expenses.date BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date'])));
    }

    if($inputs['report_for'] == 'store'){
      $this->db->where_not_in('ec.category_name', [$this->config->item('lab_category'), $this->config->item('xray_category')]);
    }
    elseif($inputs['report_for'] == 'lab'){
      $this->db->where('ec.category_name', $this->config->item('lab_category'));
    }
    elseif($inputs['report_for'] == 'xray'){
      $this->db->where('ec.category_name', $this->config->item('xray_category'));
    }
    $this->db->where('expenses.deleted', 0);

    $this->db->group_by('expenses.date');
    $this->db->order_by('expenses.date');

    return $this->db->get()->result_array();
  }

  public function get_supplier_payments(array $inputs)
  {
    $this->db->select('
    DATE(sp.payment_date) AS payment_date,
    SUM(sp.amount_tendered) AS total_amount
    ');
    $this->db->from('suppliers_payments AS sp');

    if (empty($this->config->item('date_or_time_format'))) {
      $this->db->where('DATE(sp.payment_date) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
    } else {
      $this->db->where('sp.payment_date BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date'])));
    }

    $this->db->group_by('sp.payment_date');
    $this->db->order_by('sp.payment_date');

    return $this->db->get()->result_array();
  }
}
