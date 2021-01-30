<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open($controller_name . '/save/' . $person_info->person_id, array('id'=>'supplier_form', 'class'=>'form-horizontal')); ?>
  
  <ul class="nav nav-tabs nav-justified" data-tabs="tabs">
		<li class="active" role="presentation">
			<a data-toggle="tab" href="#supplier_basic_info"><?php echo $this->lang->line("customers_basic_information"); ?></a>
		</li>
    <?php if($person_info->person_id > 0){ ?>
    <li role="presentation">
      <a data-toggle="tab" href="#supplier_purchases">Purchases</a>
    </li>
    <li role="presentation">
      <a data-toggle="tab" href="#supplier_payments">Payments</a>
    </li>
    <?php } ?>
  </ul>

  <div class="tab-content">
		<div class="tab-pane fade in active" id="supplier_basic_info">
      <fieldset>
        <div class="form-group form-group-sm">
          <?php echo form_label($this->lang->line('suppliers_company_name'), 'company_name', array('class'=>'required control-label col-xs-3')); ?>
          <div class='col-xs-8'>
            <?php echo form_input(array(
              'name'=>'company_name',
              'id'=>'company_name_input',
              'class'=>'form-control input-sm',
              'value'=>$person_info->company_name)
              );?>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <?php echo form_label($this->lang->line('suppliers_category'), 'category', array('class'=>'required control-label col-xs-3')); ?>
          <div class='col-xs-6'>
            <?php echo form_dropdown('category', $categories, $person_info->category, array('class'=>'form-control', 'id'=>'category'));?>
          </div>
        </div>

        <!-- <div class="form-group form-group-sm">	
          <?php echo form_label($this->lang->line('suppliers_agency_name'), 'agency_name', array('class'=>'control-label col-xs-3')); ?>
          <div class='col-xs-8'>
            <?php echo form_input(array(
              'name'=>'agency_name',
              'id'=>'agency_name_input',
              'class'=>'form-control input-sm',
              'value'=>$person_info->agency_name)
              );?>
          </div>
        </div> -->

        <?php $this->load->view("people/form_basic_info"); ?>

        <div class="form-group form-group-sm">	
          <?php echo form_label($this->lang->line('suppliers_account_number'), 'account_number', array('class'=>'control-label col-xs-3')); ?>
          <div class='col-xs-8'>
            <?php echo form_input(array(
              'name'=>'account_number',
              'id'=>'account_number',
              'class'=>'form-control input-sm',
              'value'=>$person_info->account_number)
              );?>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <?php echo form_label($this->lang->line('common_init_balance'), 'init_balance', array('class' => 'control-label col-xs-3')); ?>
          <div class='col-xs-4'>
            <?php echo form_input(array(
                'name'=>'init_balance',
                'id'=>'init_balance',
                'class'=>'form-control input-sm',
                'value'=>$person_info->init_balance
              )); ?>
          </div>
        </div>

        <div class="form-group form-group-sm">
          <?php echo form_label($this->lang->line('suppliers_tax_id'), 'tax_id', array('class'=>'control-label col-xs-3')); ?>
          <div class='col-xs-8'>
            <?php echo form_input(array(
                'name'=>'tax_id',
                'id'=>'tax_id',
                'class'=>'form-control input-sm',
                'value'=>$person_info->tax_id)
            );?>
          </div>
        </div>
      </fieldset>
    </div>

    <div class="tab-pane" id="supplier_purchases">
      <table class="table table-bordered table-striped table-condensed">
        <thead>
          <tr>
            <?php foreach($purchase_headers as $header){ ?>
            <th><?php echo $header['title']; ?></th>
            <?php } ?>
          </tr>
        </thead>
        <?php foreach($purchases as $purchase){ ?>
        <tr>
          <?php foreach($purchase_headers as $header){ ?>
          <td><?php echo $purchase[$header['field']]; ?></td>
          <?php } ?>
        </tr>
        <?php } ?>
      </table>
    </div>

    <div class="tab-pane" id="supplier_payments">
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
    </div>

  </div>
	
<?php echo form_close(); ?>

<script type="text/javascript">
function removePayment(payment_id){
  if(confirm('You are about to delete supplier payment. Continue?')){
    $.get('<?php echo site_url($controller_name . '/removepayment/'); ?>' + payment_id, function(response){
      var pbody = '';
      console.log(response);
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
        console.log(pbody);
      }
      $('#supplier_payments tbody').html(pbody);
    }, "json");
  }
  return;
}
//validation and submit handling
$(document).ready(function()
{
	$('#supplier_form').validate($.extend({
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
			company_name: 'required',
			first_name: 'required',
			last_name: 'required',
			email: 'email'
   		},

		messages: 
		{
			company_name: "<?php echo $this->lang->line('suppliers_company_name_required'); ?>",
			first_name: "<?php echo $this->lang->line('common_first_name_required'); ?>",
			last_name: "<?php echo $this->lang->line('common_last_name_required'); ?>",
			email: "<?php echo $this->lang->line('common_email_invalid_format'); ?>"
		}
	}, form_support.error));
});
</script>
