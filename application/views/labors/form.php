<style>
@media (min-width: 768px)
{
	.modal-dlg .modal-dialog
	{
		width: 750px !important;
	}
}
</style>
<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open($controller_name . '/save/' . $person_info->person_id, array('id'=>'customer_form', 'class'=>'form-horizontal')); ?>
  
  <ul class="nav nav-tabs nav-justified" data-tabs="tabs">
		<li class="active" role="presentation">
			<a data-toggle="tab" href="#customer_basic_info"><?php echo $this->lang->line("customers_basic_information"); ?></a>
		</li>
		<?php if($person_info->person_id > 0){ ?>
			<li role="presentation">
				<a data-toggle="tab" href="#payments">Transactions</a>
			</li>
		<?php	} ?>
	</ul>

	<div class="tab-content">
		<div class="tab-pane fade in active" id="customer_basic_info">
			<fieldset>
				<?php $this->load->view("people/form_basic_info"); ?>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('customers_date'), 'date', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<div class="input-group">
							<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
							<?php echo form_input(array(
									'name'=>'date',
									'id'=>'datetime',
									'class'=>'form-control input-sm',
									'value'=>to_datetime(strtotime($person_info->created_at)),
									'readonly'=>'true')
									); ?>
						</div>
					</div>
				</div>

				<div class="form-group form-group-sm">
					<?php echo form_label($this->lang->line('customers_employee'), 'employee', array('class'=>'control-label col-xs-3')); ?>
					<div class='col-xs-8'>
						<?php echo form_input(array(
								'name'=>'employee',
								'id'=>'employee',
								'class'=>'form-control input-sm',
								'value'=>$employee,
								'readonly'=>'true')
								); ?>
					</div>
				</div>

				<?php echo form_hidden('employee_id', $person_info->employee_id); ?>
			</fieldset>
		</div>

    <div class="tab-pane" id="payments">
      <table class="table table-bordered table-striped table-condensed">
        <thead>
          <tr>
            <?php foreach($payment_headers as $header){ ?>
            <th><?php echo $header; ?></th>
            <?php } ?>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($payments as $payment){ ?>
          <tr>
            <?php foreach(array_keys($payment_headers) as $prop){ ?>
            <td><?php echo $payment[$prop]; ?></td>
            <?php } ?>
            <td>
              <?php if($payment['id']){ ?>
              <a href="javascript:void(0)" onclick="removePayment(<?php echo $payment['id']; ?>)">
                <i class="glyphicon glyphicon-trash text-danger"></i>
              </a>
              <?php } ?>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
      <h3>Balance: <span id="balance"><?php echo $balance; ?></span></h3>
    </div>

  </div>
<?php echo form_close(); ?>

<script type="text/javascript">
function removePayment(payment_id){
  if(confirm('You are about to delete transaction. Continue?')){
    $.get('<?php echo site_url($controller_name . '/remove_payment/'); ?>' + payment_id, function(response){
      var pbody = '';
      for(var x in response.payments){
        p = response.payments[x];
        pbody += '<tr>';
        <?php foreach(array_keys($payment_headers) as $prop){ ?>
        var key = '<?php echo $prop; ?>';
        pbody += '<td>'+(p[key] ? p[key] : '')+'</td>';
        <?php } ?>
        if(p['id']){
          pbody += '<td><a href="javascript:void(0)" onclick="removePayment('+p['id']+')"><i class="glyphicon glyphicon-trash text-danger"></i></a></td>';
        } else {
          pbody += '<td></td>';
        }
        pbody += '</tr>';
      }
      $('#payments tbody').html(pbody);
      $('#balance').html(response.balance);
    }, "json");
  }
  return;
}
//validation and submit handling
$(document).ready(function()
{
	$('#customer_form').validate($.extend({
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
			first_name: 'required',
			last_name: 'required',
			email:
			{
				remote:
				{
					url: "<?php echo site_url($controller_name . '/ajax_check_email') ?>",
					type: 'POST',
					data: {
						'person_id': "<?php echo $person_info->person_id; ?>"
						// email is posted by default
					}
				}
			}
		},

		messages:
		{
			first_name: "<?php echo $this->lang->line('common_first_name_required'); ?>",
			last_name: "<?php echo $this->lang->line('common_last_name_required'); ?>",
			email: "<?php echo $this->lang->line('customers_email_duplicate'); ?>"
		}
	}, form_support.error));
});
</script>
