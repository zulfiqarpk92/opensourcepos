<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Cash_in_hand extends Report
{
  public function getTotalPayment()
  {
    $this->db->select_sum('amount');
    $this->db->from('expenses');
    $this->db->where('amount < 0');
    $data['Investment'] = ($this->db->get()->row('amount') ?: 0) * -1;

    $this->db->select('SUM(payment_amount-cash_refund) AS payment_amount');
    $this->db->from('sales_payments sp');
    $this->db->join('sales s', 's.sale_id = sp.sale_id');
    $this->db->where('s.sale_status', COMPLETED);
    $this->db->where_in('sp.payment_type', ['Cash', 'Debit Card', 'Credit Card']);
    $data['Sale_Payments'] = $this->db->get()->row('payment_amount') ?: 0;

    //fetch total inventory value
    $this->db->select('SUM(i.cost_price * iq.quantity) AS totalInventoryValue');
    $this->db->from('items i');
    $this->db->join('item_quantities iq', 'i.item_id = iq.item_id');
    $data['Inventory_Value'] = $this->db->get()->row('totalInventoryValue') ?: 0;

    //fetch total amount paid to suppliers
    $this->db->select_sum('amount_tendered');
    $this->db->from('suppliers_payments');
    $data['Supplier_Payment'] = $this->db->get()->row('amount_tendered') ?: 0;

    //fetch total expense
    $this->db->select_sum('amount');
    $this->db->from('expenses');
    $this->db->where('amount > 0');
    $data['Expense_Value'] = $this->db->get()->row('amount') ?: 0;

    return $data;
  }

  public function getDataColumns()
  {
  }

  public function getData(array $input)
  {
  }

  public function getSummaryData(array $input)
  {
    return $input['Investment'] + $input['Sale_Payments'] - $input['Expense_Value'] - $input['Supplier_Payment'];
  }
}
