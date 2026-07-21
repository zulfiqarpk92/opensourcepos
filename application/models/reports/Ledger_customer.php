<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Ledger_customer extends Report
{
	public function create(array $inputs)
	{
		//Create our temp tables to work with the data in our report
		$this->Sale->create_temp_table($inputs);
	}

	public function getDataColumns()
	{
		return array();
	}

	public function getData(array $inputs)
	{
		return $this->getSaleRows($inputs);
	}

	public function getSummaryData(array $inputs)
	{
		return array();
	}

	/**
	 * Per-sale total with the same discount/tax semantics as Sale::create_temp_table,
	 * usable outside the temp table (full-history queries). Alias expected: si = sales_items, s = sales
	 */
	private function sale_total_select()
	{
		$decimals = totals_decimals();
		$sale_price = 'CASE WHEN si.discount_type = ' . PERCENT . ' THEN si.item_unit_price * si.quantity_purchased * (1 - si.discount / 100) ELSE si.item_unit_price * si.quantity_purchased - si.discount END';

		if($this->config->item('tax_included'))
		{
			return 'ROUND(SUM(' . $sale_price . '), ' . $decimals . ')';
		}

		// tax-exclusive configuration: add the sales taxes (tax_type = 1)
		return 'ROUND(SUM(' . $sale_price . '), ' . $decimals . ')'
			. ' + IFNULL((SELECT SUM(sit.item_tax_amount) FROM ' . $this->db->dbprefix('sales_items_taxes') . ' sit'
			. ' WHERE sit.sale_id = s.sale_id AND sit.tax_type = 1), 0)';
	}

	/**
	 * Net activity before the start date: completed sale totals minus real payments.
	 * The caller adds the customer's init_balance.
	 */
	public function getOpeningBalance($customer_id, $start_date)
	{
		$row = $this->db->query(
			'SELECT SUM(t.sale_total) AS sales_total FROM ('
			. ' SELECT ' . $this->sale_total_select() . ' AS sale_total'
			. ' FROM ' . $this->db->dbprefix('sales_items') . ' si'
			. ' JOIN ' . $this->db->dbprefix('sales') . ' s ON s.sale_id = si.sale_id'
			. ' WHERE s.customer_id = ' . $this->db->escape($customer_id)
			. ' AND s.sale_status = ' . COMPLETED
			. ' AND DATE(s.sale_time) < ' . $this->db->escape($start_date)
			. ' GROUP BY s.sale_id'
			. ') t'
		)->row();
		$sales_total = ($row && $row->sales_total) ? $row->sales_total : 0;

		$row = $this->db->query(
			'SELECT SUM(sp.payment_amount - sp.cash_refund) AS payments_total'
			. ' FROM ' . $this->db->dbprefix('sales_payments') . ' sp'
			. ' JOIN ' . $this->db->dbprefix('sales') . ' s ON s.sale_id = sp.sale_id'
			. ' WHERE s.customer_id = ' . $this->db->escape($customer_id)
			. ' AND s.sale_status = ' . COMPLETED
			. ' AND sp.payment_type NOT LIKE "Due%"'
			. ' AND DATE(sp.payment_time) < ' . $this->db->escape($start_date)
		)->row();
		$payments_total = ($row && $row->payments_total) ? $row->payments_total : 0;

		return $sales_total - $payments_total;
	}

	/**
	 * Completed sales in the range with their item lines (from sales_items_temp).
	 */
	public function getSaleRows(array $inputs)
	{
		$this->db->select('sale_id,
			MAX(sale_time) AS sale_time,
			MAX(invoice_number) AS invoice_number,
			MAX(sale_type) AS sale_type,
			MAX(comment) AS comment,
			SUM(quantity_purchased) AS items_purchased,
			SUM(total) AS total');
		$this->db->from('sales_items_temp');
		$this->db->where('customer_id', $inputs['customer_id']);
		$this->db->where('sale_status', COMPLETED);
		$this->db->group_by('sale_id');
		$this->db->order_by('MAX(sale_time)');
		$summary = $this->db->get()->result_array();

		$details = array();
		foreach($summary as $row)
		{
			$this->db->select('name, item_unit_price, quantity_purchased, total');
			$this->db->from('sales_items_temp');
			$this->db->where('sale_id', $row['sale_id']);
			$details[$row['sale_id']] = $this->db->get()->result_array();
		}

		return array('summary' => $summary, 'details' => $details);
	}

	/**
	 * Individual real payments (excluding the "Due" marker) dated within the range.
	 */
	public function getPaymentRows($customer_id, $start_date, $end_date)
	{
		$this->db->select('sp.payment_id, sp.sale_id, sp.payment_type, sp.payment_amount, sp.cash_refund, sp.payment_time, sp.reference_code, s.invoice_number');
		$this->db->from('sales_payments sp');
		$this->db->join('sales s', 's.sale_id = sp.sale_id');
		$this->db->where('s.customer_id', $customer_id);
		$this->db->where('s.sale_status', COMPLETED);
		$this->db->not_like('sp.payment_type', 'Due', 'after');
		$this->db->where('DATE(sp.payment_time) >=', $start_date);
		$this->db->where('DATE(sp.payment_time) <=', $end_date);
		$this->db->order_by('sp.payment_time');

		return $this->db->get()->result_array();
	}

	/**
	 * Per-sale totals over the whole history up to the report end date, oldest first.
	 * Used for FIFO aging.
	 */
	public function getDebitsForAging($customer_id, $end_date)
	{
		return $this->db->query(
			'SELECT MAX(DATE(s.sale_time)) AS tdate, ' . $this->sale_total_select() . ' AS amount'
			. ' FROM ' . $this->db->dbprefix('sales_items') . ' si'
			. ' JOIN ' . $this->db->dbprefix('sales') . ' s ON s.sale_id = si.sale_id'
			. ' WHERE s.customer_id = ' . $this->db->escape($customer_id)
			. ' AND s.sale_status = ' . COMPLETED
			. ' AND DATE(s.sale_time) <= ' . $this->db->escape($end_date)
			. ' GROUP BY s.sale_id'
			. ' ORDER BY MAX(s.sale_time)'
		)->result_array();
	}

	/**
	 * All real payments (net of refunds) up to the report end date.
	 */
	public function getCreditsTotal($customer_id, $end_date)
	{
		$row = $this->db->query(
			'SELECT SUM(sp.payment_amount - sp.cash_refund) AS payments_total'
			. ' FROM ' . $this->db->dbprefix('sales_payments') . ' sp'
			. ' JOIN ' . $this->db->dbprefix('sales') . ' s ON s.sale_id = sp.sale_id'
			. ' WHERE s.customer_id = ' . $this->db->escape($customer_id)
			. ' AND s.sale_status = ' . COMPLETED
			. ' AND sp.payment_type NOT LIKE "Due%"'
			. ' AND DATE(sp.payment_time) <= ' . $this->db->escape($end_date)
		)->row();

		return ($row && $row->payments_total) ? floatval($row->payments_total) : 0.0;
	}
}
?>
