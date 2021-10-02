<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open($controller_name . '/add_payment/' . $person_info->person_id, array('id' => 'payment_form', 'class' => 'form-horizontal')); ?>
	<fieldset id="supplier_basic_info">
    
    <input type="hidden" name="add_payment" value="1" />

    <div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('common_date'), 'payment_date', array('class' => 'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
					'name'  => 'payment_date',
					'id'    => 'payment_date',
					'class' => 'datetime form-control input-sm',
          'value' => to_datetime(time())
        )); ?>
			</div>
    </div>

    <div class="form-group form-group-sm">
			<?php echo form_label('Amount', 'amount_tendered', array('class' => 'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
					'name'  => 'amount_tendered',
					'id'    => 'amount_tendered',
					'class' => 'form-control input-sm',
          'value' => '0'
        )); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">	
			<?php echo form_label('Reference', 'reference', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
					'name'  => 'reference',
					'id'    => 'reference',
					'class' => 'form-control input-sm',
          'value' => ''
        )); ?>
			</div>
		</div>
    <div>
      <table class="table table-bordered">
        <tr>
          <th>Sale ID</th>
          <th>Date</th>
          <th>Sale Total</th>
          <th>Payment</th>
          <th>Balance</th>
        </tr>
        <?php 
        $outstanding_sales = 0;
        foreach($sales as $sale){ 
          $outstanding_sales += $sale->balance; ?>
        <tr>
          <td><?php echo $sale->sale_id; ?></td>
          <td><?php echo to_date(strtotime($sale->sale_time)); ?></td>
          <td><?php echo to_currency($sale->sale_total); ?></td>
          <td><?php echo to_currency($sale->payment_total); ?></td>
          <td><?php echo to_currency($sale->balance); ?></td>
        </tr>
        <?php } ?>
        <tr>
          <td colspan="4" style="text-align: right"><strong>Total Outstanding:</strong></td>
          <td><strong><?php echo to_currency($outstanding_sales); ?></strong></td>
        </tr>
      </table>
    </div>

	</fieldset>
	
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
var outstanding_sales = <?php echo $outstanding_sales; ?>;
$(document).ready(function()
{
	<?php $this->load->view('partial/datepicker_locale'); ?>
	$('#payment_form').validate($.extend({
		submitHandler: function(form) {
			$(form).ajaxSubmit({
				success: function(response)
				{
					dialog_support.hide();
					table_support.handle_submit("<?php echo site_url($controller_name); ?>", response);
				},
				dataType: 'json'
			});
		},

		errorLabelContainer: '#error_message_box',
 
		rules:
		{
			amount_tendered: {
        required: true,
        max: outstanding_sales
      },
			reference: 'required',
   	},

		messages: 
		{
			amount_tendered: {
        required: "Amount field is required",
        max: 'Enter amount less than or equal to total outstanding: ' + outstanding_sales
      },
		}
	}, form_support.error));
});
</script>
