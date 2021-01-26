<?php $this->load->view("partial/header"); ?>

<div id="page_title"><?php echo $title ?></div>

<div id="page_subtitle"><?php echo $subtitle ?></div>

<table class="table table-bordered detailed-report">
  <thead>
    <th>Sale ID</th>
    <th>Date</th>
    <th>Item</th>
    <th>Category</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Total</th>
    <th>Discount</th>
    <th>Payment</th>
    <th>Balance</th>
    <th>Comments</th>
  </thead>
  <tbody>

    <?php if($sales_total['previous_balance']){ ?>
    <tr class="bill-total">
      <td colspan="9"></td>
      <td class="text-bold"><?php echo $sales_total['previous_balance']; ?></td>
      <td>Previous</td>
    </tr>
    
    <tr><td colspan="11"></td></tr>
    <?php } ?>

    <?php foreach($sales_data as $sale_data){ ?>
    
      <?php foreach($sale_data['items'] as $sale_item){ ?>
        <tr>
          <td><?php echo $sale_data['id']; ?></td>
          <td><?php echo $sale_data['sale_date']; ?></td>
          <td><?php echo $sale_item['name']; ?></td>
          <td><?php echo $sale_item['category']; ?></td>
          <td><?php echo $sale_item['price']; ?></td>
          <td><?php echo $sale_item['quantity']; ?></td>
          <td><?php echo $sale_item['total']; ?></td>
          <td><?php echo $sale_item['discount']; ?></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      <?php } ?>
      <tr class="bill-total">
        <td colspan="5" class="text-bold text-right">Sale # <?php echo $sale_data['id']; ?> Total:</td>
        <td class="text-bold"><?php echo $sale_data['quantity']; ?></td>
        <td class="text-bold"><?php echo $sale_data['total']; ?></td>
        <td></td>
        <td class="text-bold"><?php echo $sale_data['cash_payment']; ?></td>
        <td class="text-bold"><?php echo $sale_data['due_payment']; ?></td>
        <td><?php echo $sale_data['comment']; ?></td>
      </tr>

    <?php } ?>
    
    <tr class="grand-total"><td colspan="11"></td></tr>
    
    <tr class="grand-total">
      <td colspan="5"></td>
      <td class="text-bold"><?php echo $sales_total['quantity']; ?></td>
      <td class="text-bold"><?php echo $sales_total['sales_total']; ?></td>
      <td></td>
      <td class="text-bold"><?php echo $sales_total['cash_payment']; ?></td>
      <td class="text-bold"><?php echo $sales_total['due_payment']; ?></td>
      <td></td>
    </tr>
  </tbody>
</table>

<?php $this->load->view("partial/footer"); ?>
