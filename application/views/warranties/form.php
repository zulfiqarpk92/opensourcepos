<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open($controller_name.'/save/'.(!empty($warranty_info->warranty_id) ? $warranty_info->warranty_id : -1), array('id'=>'warranty_form', 'class'=>'form-horizontal')); ?>
	<fieldset>
		<legend><?php echo $this->lang->line('warranties_claim_info'); ?></legend>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_warranty_id'), 'warranty_id', array('class'=>'control-label col-xs-3')); ?>
			<?php echo form_label(!empty($warranty_info->warranty_id) ? $warranty_info->warranty_id : '', 'warranty_info_id', array('class'=>'control-label col-xs-8', 'style'=>'text-align:left')); ?>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_serial_number'), 'serial_number', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<div class="input-group input-group-sm">
					<span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
					<?php echo form_input(array(
							'name'=>'serial_number',
							'id'=>'serial_number',
							'class'=>'form-control input-sm',
							'value'=>$warranty_info->serial_number)
					); ?>
				</div>
				<span id="serial_lookup_message" class="help-block" style="margin-bottom:0;"></span>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_item'), 'item_name', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'item_name',
						'id'=>'item_name',
						'class'=>'form-control input-sm',
						'value'=>$warranty_info->item_name)
				); ?>
				<?php echo form_hidden('item_id', $warranty_info->item_id); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_customer'), 'customer_name', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'customer_name',
						'id'=>'customer_name',
						'class'=>'form-control input-sm',
						'value'=>$warranty_info->customer_name)
				); ?>
				<?php echo form_hidden('customer_id', $warranty_info->customer_id); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_sale_id'), 'sale_id', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'sale_id',
						'id'=>'sale_id',
						'class'=>'form-control input-sm',
						'readonly'=>'readonly',
						'value'=>$warranty_info->sale_id)
				); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_supplier'), 'supplier_name', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'supplier_name',
						'id'=>'supplier_name',
						'class'=>'form-control input-sm',
						'value'=>$warranty_info->supplier_name)
				); ?>
				<?php echo form_hidden('supplier_id', $warranty_info->supplier_id); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_sent_date'), 'sent_date', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<div class="input-group">
					<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
					<?php echo form_input(array(
							'name'=>'sent_date',
							'id'=>'sent_date',
							'class'=>'form-control input-sm datetime',
							'value'=>!empty($warranty_info->sent_date) ? to_datetime(strtotime($warranty_info->sent_date)) : '')
					); ?>
				</div>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_issue_description'), 'issue_description', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'issue_description',
						'id'=>'issue_description',
						'class'=>'form-control input-sm',
						'rows'=>'3',
						'value'=>$warranty_info->issue_description)
				); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_claim_reference'), 'claim_reference', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'claim_reference',
						'id'=>'claim_reference',
						'class'=>'form-control input-sm',
						'value'=>$warranty_info->claim_reference)
				); ?>
			</div>
		</div>
	</fieldset>

	<fieldset>
		<legend><?php echo $this->lang->line('warranties_return_info'); ?></legend>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_status'), 'status', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_dropdown('status', $statuses, $warranty_info->status, array('class'=>'form-control', 'id'=>'status')); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_received_date'), 'received_date', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<div class="input-group">
					<span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
					<?php echo form_input(array(
							'name'=>'received_date',
							'id'=>'received_date',
							'class'=>'form-control input-sm datetime',
							'value'=>(!empty($warranty_info->received_date) && $warranty_info->received_date != '0000-00-00 00:00:00') ? to_datetime(strtotime($warranty_info->received_date)) : '')
					); ?>
				</div>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_additional_cost'), 'additional_cost', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<div class="input-group input-group-sm">
					<?php if (!currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
					<?php echo form_input(array(
							'name'=>'additional_cost',
							'id'=>'additional_cost',
							'class'=>'form-control input-sm',
							'value'=>to_currency_no_money($warranty_info->additional_cost))
					); ?>
					<?php if (currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('warranties_resolution_notes'), 'resolution_notes', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_textarea(array(
						'name'=>'resolution_notes',
						'id'=>'resolution_notes',
						'class'=>'form-control input-sm',
						'rows'=>'3',
						'value'=>$warranty_info->resolution_notes)
				); ?>
			</div>
		</div>
	</fieldset>
	<?php echo form_hidden('employee_id', $warranty_info->employee_id); ?>
<?php echo form_close(); ?>

<script type="text/javascript">
$(document).ready(function()
{
	<?php $this->load->view('partial/datepicker_locale'); ?>

	var fill_item = function(event, ui) {
		event.preventDefault();
		$("input[name='item_id']").val(ui.item.value);
		$("#item_name").val(ui.item.label);
	};

	var fill_customer = function(event, ui) {
		event.preventDefault();
		$("input[name='customer_id']").val(ui.item.value);
		$("#customer_name").val(ui.item.label);
	};

	var fill_supplier = function(event, ui) {
		event.preventDefault();
		$("input[name='supplier_id']").val(ui.item.value);
		$("#supplier_name").val(ui.item.label);
	};

	$('#item_name').autocomplete({
		source: "<?php echo site_url('items/suggest'); ?>",
		minChars: 0,
		delay: 15,
		appendTo: '.modal-content',
		select: fill_item,
		focus: fill_item
	});

	$('#customer_name').autocomplete({
		source: "<?php echo site_url('customers/suggest'); ?>",
		minChars: 0,
		delay: 15,
		appendTo: '.modal-content',
		select: fill_customer,
		focus: fill_customer
	});

	$('#supplier_name').autocomplete({
		source: "<?php echo site_url('suppliers/suggest'); ?>",
		minChars: 0,
		delay: 15,
		appendTo: '.modal-content',
		select: fill_supplier,
		focus: fill_supplier
	});

	$('#item_name').change(function() {
		if(!$(this).val()) {
			$("input[name='item_id']").val('');
		}
	});

	$('#customer_name').change(function() {
		if(!$(this).val()) {
			$("input[name='customer_id']").val('');
		}
	});

	$('#supplier_name').change(function() {
		if(!$(this).val()) {
			$("input[name='supplier_id']").val('');
		}
	});

	var apply_lookup = function(data) {
		var $msg = $('#serial_lookup_message');
		$msg.removeClass('text-success text-warning text-info');

		if(data.open_claim && data.open_claim.warranty_id && !$("input[name='warranty_id']").length) {
			$msg.addClass('text-warning').text("<?php echo $this->lang->line('warranties_lookup_open'); ?>");
		} else if(data.sale) {
			if(!$('#item_name').val()) {
				$("input[name='item_id']").val(data.sale.item_id);
				$('#item_name').val(data.sale.item_name);
			}
			if(!$('#customer_name').val()) {
				$("input[name='customer_id']").val(data.sale.customer_id);
				$('#customer_name').val(data.sale.customer_name);
			}
			if(!$('#sale_id').val()) {
				$('#sale_id').val(data.sale.sale_id);
			}
			if(!$("input[name='supplier_id']").val() && data.sale.supplier_id) {
				$("input[name='supplier_id']").val(data.sale.supplier_id);
				$('#supplier_name').val(data.sale.supplier_name);
			}
			$msg.addClass('text-success').text("<?php echo $this->lang->line('warranties_lookup_found'); ?>");
		} else {
			$msg.addClass('text-info').text("<?php echo $this->lang->line('warranties_lookup_not_found'); ?>");
		}
	};

	var lookup_serial = function() {
		var serial = $.trim($('#serial_number').val());
		if(!serial) {
			return;
		}

		$.getJSON('<?php echo site_url($controller_name . "/lookup_serial"); ?>', {
			serial: serial,
			warranty_id: '<?php echo $warranty_info->warranty_id; ?>'
		}, apply_lookup);
	};

	$('#serial_number').on('keydown', function(e) {
		if(e.which === 13) {
			e.preventDefault();
			e.stopPropagation();
			lookup_serial();
			return false;
		}
	}).on('blur', function() {
		lookup_serial();
	});

	<?php if(!empty($warranty_info->serial_number) && empty($warranty_info->warranty_id)): ?>
		lookup_serial();
	<?php endif; ?>

	$('#status').change(function() {
		if($(this).val() !== 'sent' && !$('#received_date').val()) {
			var picker = $('#received_date').data('DateTimePicker');
			if(picker) {
				picker.date(moment());
			}
		}
	});

	setTimeout(function() {
		$('#serial_number').focus().select();
	}, 200);

	$('#warranty_form').validate($.extend({
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

		ignore: '',

		rules:
		{
			serial_number: 'required',
			supplier_id: 'required',
			sent_date: 'required',
			issue_description: 'required',
			status: 'required'
		},

		messages:
		{
			serial_number: "<?php echo $this->lang->line('warranties_serial_required'); ?>",
			supplier_id: "<?php echo $this->lang->line('warranties_supplier_required'); ?>",
			issue_description: "<?php echo $this->lang->line('warranties_issue_required'); ?>",
			status: "<?php echo $this->lang->line('warranties_status_required'); ?>"
		}
	}, form_support.error));
});
</script>
