<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Summary_report.php");

class Summary_daily extends Summary_report
{
	protected function _get_data_columns()
	{
		return array(
			array('date'      => $this->lang->line('reports_date'), 'sortable' => FALSE),
			array('sales'     => $this->lang->line('reports_revenue'), 'sorter' => 'number_sorter'),
			array('expenses'  => $this->lang->line('reports_expenses'), 'sorter' => 'number_sorter'),
      array('profit'    => $this->lang->line('reports_profit'), 'sorter' => 'number_sorter')
    );
	}

	protected function _select(array $inputs)
	{
		parent::_select($inputs);

		$this->db->select('DATE(sales.sale_time) AS sale_date');
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

		if(empty($this->config->item('date_or_time_format')))
		{
			$this->db->where('DATE(expenses.date) BETWEEN ' . $this->db->escape($inputs['start_date']) . ' AND ' . $this->db->escape($inputs['end_date']));
		}
		else
		{
			$this->db->where('expenses.date BETWEEN ' . $this->db->escape(rawurldecode($inputs['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($inputs['end_date'])));
		}

		$this->db->where('expenses.deleted', 0);

		$this->db->group_by('expenses.date');
		$this->db->order_by('expenses.date');

		return $this->db->get()->result_array();
	}
}
?>
