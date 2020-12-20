<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Cash_in_hand extends Report
{
  public function getTotalPayment()
  {
    $data = [];
    $this->db->select_sum('amount');
    $this->db->from('expenses');
    $this->db->where('amount < 0');
    
    $data['investment'] = new stdClass();
    $data['investment']->label = 'Investments';
    $data['investment']->value = ($this->db->get()->row('amount') ?: 0) * -1;

    $this->db->select('sp.payment_type, SUM(payment_amount-cash_refund) AS payment_amount');
    $this->db->from('sales_payments sp');
    $this->db->join('sales s', 's.sale_id = sp.sale_id');
    $this->db->where('s.sale_status', COMPLETED);
    $this->db->group_by('sp.payment_type');
    $sale_payments = $this->db->get()->result();

    $data['payment'] = new stdClass();
    $data['payment']->label = 'Cash or Equivalent';
    $data['payment']->value = 0;

    $data['receivable'] = new stdClass();
    $data['receivable']->label = 'Account Receivable';
    $data['receivable']->value = 0;

    foreach($sale_payments as $sp){
      if($sp->payment_type == 'Due'){
        $data['receivable']->value += $sp->payment_amount;
      }
      else{
        $data['payment']->value += $sp->payment_amount;
      }
    }

    //fetch total inventory value
    $this->db->select('SUM(i.cost_price * iq.quantity) AS inventory_value');
    $this->db->from('items i');
    $this->db->join('item_quantities iq', 'i.item_id = iq.item_id');
    $this->db->where('i.deleted', '0');

    $data['inventory'] = new stdClass();
    $data['inventory']->label = 'Inventory';
    $data['inventory']->value = $this->db->get()->row('inventory_value') ?: 0;

    //fetch total amount paid to suppliers
    $this->db->select_sum('amount_tendered');
    $this->db->from('suppliers_payments');

    $data['supplier_payment'] = new stdClass();
    $data['supplier_payment']->label = 'Supplier Payment';
    $data['supplier_payment']->value = $this->db->get()->row('amount_tendered') ?: 0;

		$this->db->select('SUM(ri.quantity_purchased * ri.receiving_quantity * ri.item_unit_price) AS total_purchases');
    $this->db->from('receivings r');
    $this->db->join('receivings_items ri', 'ri.receiving_id = r.receiving_id');

    $data['payable'] = new stdClass();
    $data['payable']->label = 'Account Payable';
    $data['payable']->value = ($this->db->get()->row('total_purchases') ?: 0) - $data['supplier_payment']->value;

    //fetch total expense
    $this->db->select_sum('amount');
    $this->db->from('expenses');
    $this->db->where('amount > 0');

    $data['expense'] = new stdClass();
    $data['expense']->label = 'Expenses';
    $data['expense']->value = $this->db->get()->row('amount') ?: 0;

    $data['cash_in_hand'] = new stdClass();
    $data['cash_in_hand']->label = 'Cash in Hand';
    $data['cash_in_hand']->value = 0;
    $data['cash_in_hand']->value += $data['investment']->value;
    $data['cash_in_hand']->value += $data['payment']->value;
    $data['cash_in_hand']->value -= $data['supplier_payment']->value;
    $data['cash_in_hand']->value -= $data['expense']->value;

    $data['net_total'] = new stdClass();
    $data['net_total']->label = 'Net Worth';
    $data['net_total']->value = 0;
    $data['net_total']->value += $data['inventory']->value;
    $data['net_total']->value += $data['payment']->value;
    $data['net_total']->value += $data['receivable']->value;
    $data['net_total']->value -= $data['supplier_payment']->value;
    $data['net_total']->value -= $data['payable']->value;
    $data['net_total']->value -= $data['expense']->value;

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
  }
}
