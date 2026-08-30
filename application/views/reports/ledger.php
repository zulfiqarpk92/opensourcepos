<?php $this->load->view("partial/header"); ?>

<style>
#ledger-report { max-width: 1100px; margin: 0 auto; }
#ledger-report .company-header { text-align: center; margin-bottom: 10px; }
#ledger-report .company-header h2 { margin: 4px 0; }
#ledger-report .statement-title { text-align: center; font-size: 18px; font-weight: bold; margin: 12px 0 2px; }
#ledger-report .statement-range { text-align: center; margin-bottom: 12px; }
#ledger-report table.ledger { width: 100%; border-collapse: collapse; margin-top: 8px; }
#ledger-report table.ledger th, #ledger-report table.ledger td { border: 1px solid #444; padding: 3px 6px; font-size: 12px; }
#ledger-report table.ledger th { background: #eee; text-align: center; }
#ledger-report .num { text-align: right; white-space: nowrap; }
#ledger-report tr.credit-row td { background: #5cd65c; }
#ledger-report tr.opening-row td { font-weight: bold; }
#ledger-report .closing-balance { text-align: right; font-size: 16px; font-weight: bold; color: #c00; margin: 10px 0; }
#ledger-report .ledger-note { text-align: center; font-style: italic; font-size: 11px; margin: 8px 0; }
#ledger-report table.aging th { background: #eee; }
#ledger-report .filter-bar { margin: 10px 0; }
@media print {
	.filter-bar, #print_button { display: none; }
	#ledger-report table.ledger th, #ledger-report table.ledger td { font-size: 10px; }
	/* the theme's print reset forces color:#000/background:transparent on * —
	   re-assert our colors and force background printing */
	#ledger-report, #ledger-report * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
	#ledger-report table.ledger th { background: #eee !important; }
	#ledger-report tr.credit-row td { background: #5cd65c !important; }
	#ledger-report .closing-balance { color: #c00 !important; }
}
</style>

<div id="ledger-report">

	<div class="filter-bar form-inline print_hide">
		<input type="text" id="daterangepicker" class="form-control input-sm" style="display:inline-block; width:auto;">
		<button class="btn btn-primary btn-sm" id="ledger_refresh">Refresh</button>
		<button class="btn btn-default btn-sm" id="print_button" onclick="window.print();"><span class="glyphicon glyphicon-print"></span> Print</button>
	</div>

	<div class="company-header">
		<h2><?php echo $this->config->item('company'); ?></h2>
		<?php if($this->config->item('tax_id') != ''): ?>
			<div><strong>VAT:</strong> <?php echo $this->config->item('tax_id'); ?></div>
		<?php endif; ?>
		<div><?php echo nl2br($this->config->item('address')); ?></div>
		<div>Phone: <?php echo $this->config->item('phone'); ?><?php if($this->config->item('email')): ?> | Email: <?php echo $this->config->item('email'); ?><?php endif; ?></div>
	</div>

	<div class="statement-title"><?php echo $title; ?></div>
	<div class="statement-range">From: <?php echo $start_date; ?> To: <?php echo $end_date; ?></div>

	<div class="person-block">
		<strong><?php echo $person_name; ?><?php if(!empty($person_company)): ?> | <?php echo $person_company; ?><?php endif; ?></strong><br>
		<?php if(!empty($person_phone) || !empty($person_email)): ?>
			Phone: <?php echo $person_phone; ?><?php if(!empty($person_email)): ?> | Email: <?php echo $person_email; ?><?php endif; ?><br>
		<?php endif; ?>
		<?php if(!empty($person_address)): ?>
			<?php echo $person_address; ?><br>
		<?php endif; ?>
		<strong>Opening Balance:</strong> <?php echo to_currency($opening_balance); ?>
	</div>

	<table class="ledger">
		<thead>
			<tr>
				<th>Date</th>
				<th>Voucher</th>
				<th>Product</th>
				<th>Qty</th>
				<th>Unit Price</th>
				<th>Total Price</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Balance</th>
			</tr>
		</thead>
		<tbody>
			<tr class="opening-row">
				<td><?php echo $start_date; ?></td>
				<td></td>
				<td>Opening Balance</td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td class="num"><?php echo to_currency($opening_balance); ?></td>
			</tr>

			<?php foreach($entries as $entry): ?>
				<?php
				$row_class = ($entry['kind'] == 'payment' || $entry['kind'] == 'return') ? 'credit-row' : '';
				$item_count = count($entry['items']);
				?>
				<?php if($item_count > 0): ?>
					<?php foreach($entry['items'] as $index => $item): ?>
						<tr class="<?php echo $row_class; ?>">
							<?php if($index == 0): ?>
								<td rowspan="<?php echo $item_count; ?>"><?php echo $entry['date']; ?></td>
								<td rowspan="<?php echo $item_count; ?>"><?php echo $entry['voucher']; ?></td>
							<?php endif; ?>
							<td><?php echo $item['name']; ?></td>
							<td class="num"><?php echo to_quantity_decimals($item['quantity']); ?></td>
							<td class="num"><?php echo to_currency($item['price']); ?></td>
							<td class="num"><?php echo to_currency($item['total']); ?></td>
							<?php if($index == 0): ?>
								<td rowspan="<?php echo $item_count; ?>" class="num"><?php echo $entry['debit'] != 0 ? to_currency($entry['debit']) : ''; ?></td>
								<td rowspan="<?php echo $item_count; ?>" class="num"><?php echo $entry['credit'] != 0 ? to_currency($entry['credit']) : ''; ?></td>
								<td rowspan="<?php echo $item_count; ?>" class="num"><?php echo to_currency($entry['balance']); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr class="<?php echo $row_class; ?>">
						<td><?php echo $entry['date']; ?></td>
						<td><?php echo $entry['voucher']; ?></td>
						<td><?php echo $entry['description']; ?></td>
						<td></td>
						<td></td>
						<td></td>
						<td class="num"><?php echo $entry['debit'] != 0 ? to_currency($entry['debit']) : ''; ?></td>
						<td class="num"><?php echo $entry['credit'] != 0 ? to_currency($entry['credit']) : ''; ?></td>
						<td class="num"><?php echo to_currency($entry['balance']); ?></td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="closing-balance">Closing Balance: <?php echo to_currency($closing_balance); ?></div>

	<div class="ledger-note">Note: Opening Balance is the outstanding amount as of the day before the selected start date.</div>

	<table class="ledger aging">
		<thead>
			<tr>
				<th>CURRENT</th>
				<th>1-30 DAYS PAST DUE</th>
				<th>31-60 DAYS PAST DUE</th>
				<th>60-90 DAYS PAST DUE</th>
				<th>OVER 90 DAYS PAST DUE</th>
				<th>Amount Due</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="num"><?php echo to_currency($aging['current']); ?></td>
				<td class="num"><?php echo to_currency($aging['b1_30']); ?></td>
				<td class="num"><?php echo to_currency($aging['b31_60']); ?></td>
				<td class="num"><?php echo to_currency($aging['b60_90']); ?></td>
				<td class="num"><?php echo to_currency($aging['over_90']); ?></td>
				<td class="num"><strong><?php echo to_currency($aging['amount_due']); ?></strong></td>
			</tr>
		</tbody>
	</table>

</div>

<script type="text/javascript">
$(document).ready(function()
{
	// browsers use the page title as the default save-to-PDF filename
	document.title = <?php echo json_encode(trim($person_name) . ' - ' . $start_date . ' to ' . $end_date); ?>;

	<?php $this->load->view('partial/daterangepicker'); ?>

	// show the range this statement was rendered with, not the picker's default
	start_date = "<?php echo $start_date; ?>";
	end_date = "<?php echo $end_date; ?>";
	var picker = $('#daterangepicker').data('daterangepicker');
	picker.setStartDate(moment(start_date));
	picker.setEndDate(moment(end_date));

	$('#ledger_refresh').click(function()
	{
		window.location = "<?php echo site_url('reports/' . $ledger_type . '_ledger'); ?>/" + start_date.substr(0, 10) + "/" + end_date.substr(0, 10) + "/<?php echo $person_id; ?>";
	});
});
</script>

<?php $this->load->view("partial/footer"); ?>
