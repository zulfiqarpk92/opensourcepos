<?php $this->load->view("partial/header"); ?>

<div id="page_title"><?php echo $title ?></div>

<div id="page_subtitle"><?php echo $subtitle ?></div>
<style>
  .table {
    font-size: 16px;
    border-width: 2px;
    border-color: lightslategrey;
    margin: 10px;
    margin-top: 20px;
  }

  #page_title {
    display: flex;
    justify-content: center;
  }

  #summary {
    display: flex;
    justify-content: center;
    font-size: 16px;
    margin-top: 30px;
  }
</style>
<div id="table_holder">
  <table id="table" class="table table-bordered">
    <thead>
      <tr>
        <th>Name</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($report_data as $rowName => $row){
        if($rowName != 'Inventory_Value'){ ?>
        <tr>
          <td>
            <?php echo str_replace('_', ' ', $rowName) ?>
            <span class="pull-right"><?php echo in_array($rowName, ['Investment', 'Sale_Payments']) ? '+' : '-'; ?></span>
          </td>
          <td><?php echo to_currency($row) ?></td>
        </tr>
      <?php }} ?>
    </tbody>
  </table>
</div>
<div id="report_summary">
  <div class="summary_row"><?php echo 'Cash in Hand: ' . to_currency($report_summary); ?></div>
  <div class="summary_row"><?php echo 'Inventory Value: ' . to_currency($report_data['Inventory_Value']); ?></div>
  <div class="summary_row"><?php echo 'Total Worth: ' . to_currency($report_summary+$report_data['Investment']); ?></div>
</div>
<?php $this->load->view("partial/footer"); ?>