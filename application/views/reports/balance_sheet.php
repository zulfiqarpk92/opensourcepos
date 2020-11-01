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

  .table .heading{
    text-align: center;
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
        <th class="heading" colspan="2">Inflow</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo $report_data['investment']->label; ?></td>
        <td><?php echo to_currency($report_data['investment']->value); ?></td>
      </tr>
      <tr>
        <td><?php echo $report_data['payment']->label; ?></td>
        <td><?php echo to_currency($report_data['payment']->value); ?></td>
      </tr>
      <tr>
        <td><?php echo $report_data['receivable']->label; ?></td>
        <td><?php echo to_currency($report_data['receivable']->value); ?></td>
      </tr>
    </tbody>
  </table>
  <table id="table" class="table table-bordered">
    <thead>
      <tr>
        <th class="heading" colspan="2">OutFlow</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo $report_data['supplier_payment']->label; ?></td>
        <td><?php echo to_currency($report_data['supplier_payment']->value); ?></td>
      </tr>
      <tr>
        <td><?php echo $report_data['payable']->label; ?></td>
        <td><?php echo to_currency($report_data['payable']->value); ?></td>
      </tr>
      <tr>
        <td><?php echo $report_data['expense']->label; ?></td>
        <td><?php echo to_currency($report_data['expense']->value); ?></td>
      </tr>
    </tbody>
  </table>
  <table id="table" class="table table-bordered">
    <thead>
      <tr>
        <th class="heading" colspan="2">Net Worth</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo $report_data['cash_in_hand']->label; ?></td>
        <td><?php echo to_currency($report_data['cash_in_hand']->value); ?></td>
      </tr>
      <tr>
        <td><?php echo $report_data['inventory']->label; ?></td>
        <td><?php echo to_currency($report_data['inventory']->value); ?></td>
      </tr>
      <tr class="<?php echo $report_data['net_total']->value > 0 ? 'text-success' : 'text-danger'; ?>">
        <td><?php echo $report_data['net_total']->label; ?></td>
        <td><?php echo to_currency($report_data['net_total']->value); ?></td>
      </tr>
    </tbody>
  </table>
</div>
<?php $this->load->view("partial/footer"); ?>