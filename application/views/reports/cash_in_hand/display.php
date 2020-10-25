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
      <?php foreach ($report_data as $rowName => $row) { ?>
        <tr>
          <td><?php echo str_replace('_', ' ', $rowName) ?></td>
          <td><?php echo to_currency($row) ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<div id="summary"><?php echo 'Cash in Hand: ' . to_currency($report_summary) ?></div>
<?php $this->load->view("partial/footer"); ?>