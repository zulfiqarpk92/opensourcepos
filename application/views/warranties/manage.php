<?php $this->load->view("partial/header"); ?>

<script type="text/javascript">
$(document).ready(function()
{
	$('#filters').on('hidden.bs.select', function(e) {
		table_support.refresh();
	});

	$('#supplier_id').on('changed.bs.select', function(e) {
		table_support.refresh();
	});

	<?php $this->load->view('partial/daterangepicker'); ?>

	// Show all claims by default so open (sent) items are not hidden by the month filter
	start_date = "<?php echo date('Y-m-d', mktime(0, 0, 0, 1, 1, 2010)); ?>";
	end_date = "<?php echo date('Y-m-d'); ?>";
	var warranty_range = $('#daterangepicker').data('daterangepicker');
	if(warranty_range) {
		warranty_range.setStartDate("<?php echo date($this->config->item('dateformat'), mktime(0, 0, 0, 1, 1, 2010)); ?>");
		warranty_range.setEndDate("<?php echo date($this->config->item('dateformat'), mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1); ?>");
	}

	$("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
		table_support.refresh();
	});

	<?php $this->load->view('partial/bootstrap_tables_locale'); ?>

	table_support.init({
		resource: '<?php echo site_url($controller_name);?>',
		headers: <?php echo $table_headers; ?>,
		pageSize: <?php echo $this->config->item('lines_per_page'); ?>,
		uniqueId: 'warranty_id',
		queryParams: function() {
			return $.extend(arguments[0], {
				start_date: start_date,
				end_date: end_date,
				filters: $("#filters").val() || [""],
				supplier_id: $("#supplier_id").val() || ""
			});
		}
	});

	var open_warranty_form = function(id, serial) {
		var href = '<?php echo site_url($controller_name); ?>/view/' + id;
		if(serial) {
			href += '?serial=' + encodeURIComponent(serial);
		}
		var title = (id > 0)
			? "<?php echo $this->lang->line('warranties_update'); ?>"
			: "<?php echo $this->lang->line('warranties_new'); ?>";
		$('#warranty_form_launcher').attr('data-href', href).attr('title', title).click();
	};

	$('#serial_scan').on('keydown', function(e) {
		if(e.which === 13) {
			e.preventDefault();
			var serial = $.trim($(this).val());
			if(!serial) {
				return;
			}

			$.getJSON('<?php echo site_url($controller_name . "/lookup_serial"); ?>', {serial: serial}, function(data) {
				if(data.open_claim && data.open_claim.warranty_id) {
					open_warranty_form(data.open_claim.warranty_id, serial);
				} else {
					open_warranty_form(-1, serial);
				}
				$('#serial_scan').val('');
			});
		}
	});
});
</script>

<div id="title_bar" class="btn-toolbar">
	<div class="pull-left" style="max-width: 280px; width: 100%;">
		<div class="input-group input-group-sm">
			<span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
			<?php echo form_input(array(
				'name' => 'serial_scan',
				'id' => 'serial_scan',
				'class' => 'form-control input-sm',
				'placeholder' => $this->lang->line('warranties_scan_placeholder')
			)); ?>
		</div>
	</div>
	<button class='btn btn-info btn-sm pull-right modal-dlg modal-dlg-wide' data-btn-submit='<?php echo $this->lang->line('common_submit') ?>' data-href='<?php echo site_url($controller_name."/view"); ?>'
			title='<?php echo $this->lang->line($controller_name.'_new'); ?>'>
		<span class="glyphicon glyphicon-tags">&nbsp</span><?php echo $this->lang->line($controller_name . '_new'); ?>
	</button>
	<button id="warranty_form_launcher" class="modal-dlg modal-dlg-wide" data-btn-submit='<?php echo $this->lang->line('common_submit') ?>' data-href='<?php echo site_url($controller_name."/view"); ?>' title='<?php echo $this->lang->line($controller_name.'_new'); ?>' style="display:none;"></button>
</div>

<div id="toolbar">
	<div class="pull-left form-inline" role="toolbar">
		<button id="delete" class="btn btn-default btn-sm print_hide">
			<span class="glyphicon glyphicon-trash">&nbsp</span><?php echo $this->lang->line("common_delete");?>
		</button>

		<?php echo form_input(array('name'=>'daterangepicker', 'class'=>'form-control input-sm', 'id'=>'daterangepicker')); ?>
		<?php echo form_multiselect('filters[]', $filters, '', array('id'=>'filters', 'data-none-selected-text'=>$this->lang->line('common_none_selected_text'), 'class'=>'selectpicker show-menu-arrow', 'data-selected-text-format'=>'count > 1', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
		<?php echo form_dropdown('supplier_id', $suppliers, '', array('id'=>'supplier_id', 'class'=>'selectpicker show-menu-arrow', 'data-live-search'=>'true', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
	</div>
</div>

<div id="table_holder">
	<table id="table"></table>
</div>

<?php $this->load->view("partial/footer"); ?>
