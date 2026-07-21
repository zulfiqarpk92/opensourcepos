<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Ledger_supplier extends Report
{
	public function getDataColumns()
	{
		return array();
	}

	public function getData(array $inputs)
	{
		return $this->getReceivingRows($inputs['supplier_id'], $inputs['start_date'], $inputs['end_date']);
	}

	public function getSummaryData(array $inputs)
	{
		return array();
	}

	/**
	 * Per-receiving total, same discount semantics as Specific_supplier::getReceivingData.
	 * Alias expected: ri = receivings_items
	 */
	private function receiving_total_select()
	{
		$percent = PERCENT;
		$decimals = totals_decimals();
		$total_amount = 'ri.item_unit_price * ri.quantity_purchased * ri.receiving_quantity';

		return "ROUND(SUM(CASE WHEN ri.discount_type = $percent THEN $total_amount * (1 - ri.discount / 100) ELSE $total_amount - ri.discount END), $decimals)";
	}

	/**
	 * Net activity before the start date: receivings minus all supplier payments.
	 * The caller adds the supplier's init_balance.
	 */
	public function getOpeningBalance($supplier_id, $start_date)
	{
		$row = $this->db->query(
			'SELECT SUM(t.receiving_total) AS receivings_total FROM ('
			. ' SELECT ' . $this->receiving_total_select() . ' AS receiving_total'
			. ' FROM ' . $this->db->dbprefix('receivings_items') . ' ri'
			. ' JOIN ' . $this->db->dbprefix('receivings') . ' r ON r.receiving_id = ri.receiving_id'
			. ' WHERE r.supplier_id = ' . $this->db->escape($supplier_id)
			. ' AND DATE(r.receiving_time) < ' . $this->db->escape($start_date)
			. ' GROUP BY r.receiving_id'
			. ') t'
		)->row();
		$receivings_total = ($row && $row->receivings_total) ? $row->receivings_total : 0;

		$row = $this->db->query(
			'SELECT SUM(sp.amount_tendered) AS payments_total'
			. ' FROM ' . $this->db->dbprefix('suppliers_payments') . ' sp'
			. ' WHERE sp.supplier_id = ' . $this->db->escape($supplier_id)
			. ' AND DATE(sp.payment_date) < ' . $this->db->escape($start_date)
		)->row();
		$payments_total = ($row && $row->payments_total) ? $row->payments_total : 0;

		return $receivings_total - $payments_total;
	}

	/**
	 * Receivings in the range with their item lines.
	 */
	public function getReceivingRows($supplier_id, $start_date, $end_date)
	{
		$percent = PERCENT;
		$decimals = totals_decimals();
		$total_amount = 'ri.item_unit_price * ri.quantity_purchased * ri.receiving_quantity';
		$line_total = "ROUND(CASE WHEN ri.discount_type = $percent THEN $total_amount * (1 - ri.discount / 100) ELSE $total_amount - ri.discount END, $decimals)";

		$this->db->select('r.receiving_id, r.receiving_time, r.reference, r.comment');
		$this->db->from('receivings r');
		$this->db->where('r.supplier_id', $supplier_id);
		$this->db->where('DATE(r.receiving_time) >=', $start_date);
		$this->db->where('DATE(r.receiving_time) <=', $end_date);
		$this->db->order_by('r.receiving_time');
		$summary = $this->db->get()->result_array();

		$details = array();
		foreach($summary as $key => $row)
		{
			$this->db->select("i.name, ri.item_unit_price, (ri.quantity_purchased * ri.receiving_quantity) AS quantity, $line_total AS total", FALSE);
			$this->db->from('receivings_items ri');
			$this->db->join('items i', 'i.item_id = ri.item_id');
			$this->db->where('ri.receiving_id', $row['receiving_id']);
			$this->db->order_by('ri.line');
			$items = $this->db->get()->result_array();

			$total = 0;
			foreach($items as $item)
			{
				$total += $item['total'];
			}
			$summary[$key]['total'] = $total;
			$details[$row['receiving_id']] = $items;
		}

		return array('summary' => $summary, 'details' => $details);
	}

	/**
	 * Every supplier payment dated within the range (receiving-linked and on-account).
	 */
	public function getPaymentRows($supplier_id, $start_date, $end_date)
	{
		$this->db->select('sp.supplier_payment_id, sp.receiving_id, sp.amount_tendered, sp.payment_date, sp.reference, sp.comments');
		$this->db->from('suppliers_payments sp');
		$this->db->where('sp.supplier_id', $supplier_id);
		$this->db->where('DATE(sp.payment_date) >=', $start_date);
		$this->db->where('DATE(sp.payment_date) <=', $end_date);
		$this->db->order_by('sp.payment_date');

		return $this->db->get()->result_array();
	}

	/**
	 * Per-receiving totals up to the report end date, oldest first. Used for FIFO aging.
	 */
	public function getDebitsForAging($supplier_id, $end_date)
	{
		return $this->db->query(
			'SELECT MAX(DATE(r.receiving_time)) AS tdate, ' . $this->receiving_total_select() . ' AS amount'
			. ' FROM ' . $this->db->dbprefix('receivings_items') . ' ri'
			. ' JOIN ' . $this->db->dbprefix('receivings') . ' r ON r.receiving_id = ri.receiving_id'
			. ' WHERE r.supplier_id = ' . $this->db->escape($supplier_id)
			. ' AND DATE(r.receiving_time) <= ' . $this->db->escape($end_date)
			. ' GROUP BY r.receiving_id'
			. ' ORDER BY MAX(r.receiving_time)'
		)->result_array();
	}

	/**
	 * All supplier payments up to the report end date.
	 */
	public function getCreditsTotal($supplier_id, $end_date)
	{
		$row = $this->db->query(
			'SELECT SUM(sp.amount_tendered) AS payments_total'
			. ' FROM ' . $this->db->dbprefix('suppliers_payments') . ' sp'
			. ' WHERE sp.supplier_id = ' . $this->db->escape($supplier_id)
			. ' AND DATE(sp.payment_date) <= ' . $this->db->escape($end_date)
		)->row();

		return ($row && $row->payments_total) ? floatval($row->payments_total) : 0.0;
	}
}
?>
