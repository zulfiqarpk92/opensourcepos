<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Report.php");

class Cash_in_hand extends Report{
    public function getTotalPayment(){
        //fetch total amount paid to suppliers
        $this->db->select('SUM(amount_tendered) AS totalPayment');
        $this->db->from('ospos_supplier_payment');
        $totalPayment = $this->db->get()->result_array();
        $data['Supplier_Payment'] = $totalPayment[0]['totalPayment']?:0;
    
        //fetch total inventory value
        $this->db->select('SUM(ospos_items.cost_price * itemQuantity.quantity ) as totalInventoryValue');
        $this->db->join('ospos_item_quantities itemQuantity','ospos_items.item_id = itemQuantity.item_id');
        $this->db->from('ospos_items');
        $inventory_value = $this->db->get()->result_array();
        $data['Inventory_Value'] = $inventory_value[0]['totalInventoryValue']?:0;

        //fetch total expense
        $this->db->select('SUM(amount) AS totalExpense');
        $this->db->from('ospos_expenses');
        $expense_value = $this->db->get()->result_array();
        $data['Expense_Value'] = $expense_value[0]['totalExpense']?:0;


        $this->db->select('SUM(payment_amount) AS totalSale');
        $this->db->where('payment_type =','Cash');
        $this->db->or_where('payment_type =','Debit Card');
        $this->db->or_where('payment_type =','Credit Card');
        $this->db->from('ospos_sales_payments');
        $sale_payments = $this->db->get()->result_array();
        $data['Sale_Payments'] = $sale_payments[0]['totalSale']?:0;
   
        return $data;
    }
    public function getDataColumns(){

    }
    public function getData(array $input){

    }
    public function getSummaryData(array $input){
        return $input['Sale_Payments']+$input['Inventory_Value']-$input['Expense_Value']-$input['Supplier_Payment'];
    }
    
}
?>