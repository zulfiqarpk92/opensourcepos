<?php $this->load->view("partial/header"); ?>

<div id="page_title"><?php echo $title ?></div>

<div id="page_subtitle"><?php echo $subtitle ?></div>

<table class="table table-bordered detailed-report">
  <thead>
    <th>Recv ID</th>
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

    <?php if($report_total['previous_balance']){ ?>
    <tr class="bill-total">
      <td colspan="9"></td>
      <td class="text-bold"><?php echo $report_total['previous_balance']; ?></td>
      <td>Previous</td>
    </tr>
    
    <tr><td colspan="11"></td></tr>
    <?php } ?>

    <?php foreach($report_data as $sale_data){ ?>
    
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
      <?php if($sale_data['is_payment']){ ?>
      <tr class="bill-total">
        <td></td>
        <td><?php echo $sale_data['sale_date']; ?></td>
        <td colspan="3" class="text-bold">
          <?php echo $sale_data['reference']; ?>
          <span class="pull-right">Payment # <?php echo str_replace('P-', '', $sale_data['id']); ?></span>
        </td>
        <td></td>
        <td></td>
        <td></td>
        <td class="text-bold"><?php echo $sale_data['cash_payment']; ?></td>
        <td class="text-bold"><?php echo $sale_data['due_payment']; ?></td>
        <td><?php echo $sale_data['comment']; ?></td>
      </tr>
      <?php } else{ ?>
      <tr class="bill-total">
        <td></td>
        <td></td>
        <td colspan="3" class="text-bold">
          <?php echo $sale_data['reference']; ?>
          <span class="pull-right">Recv # <?php echo $sale_data['id']; ?> Total:</span>
        </td>
        <td class="text-bold"><?php echo $sale_data['quantity']; ?></td>
        <td class="text-bold"><?php echo $sale_data['total']; ?></td>
        <td></td>
        <td class="text-bold"><?php echo $sale_data['cash_payment']; ?></td>
        <td class="text-bold"><?php echo $sale_data['due_payment']; ?></td>
        <td><?php echo $sale_data['comment']; ?></td>
      </tr>
      <?php } ?>

    <?php } ?>
    
    <tr class="grand-total"><td colspan="11"></td></tr>
    
    <tr class="grand-total">
      <td colspan="5"></td>
      <td class="text-bold"><?php echo $report_total['quantity']; ?></td>
      <td class="text-bold"><?php echo $report_total['sales_total']; ?></td>
      <td></td>
      <td class="text-bold"><?php echo $report_total['cash_payment']; ?></td>
      <td class="text-bold"><?php echo $report_total['due_payment']; ?></td>
      <td></td>
    </tr>
  </tbody>
</table>

<?php $this->load->view("partial/footer"); ?>
