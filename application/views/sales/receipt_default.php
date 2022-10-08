<?php
// calculating ending balance by the total sale minus the total payment of customer
$ending_balance = $customer_total - $customer_total_payments ;
// if there is any due amount then we will later add that to ending balance too
$balance_after_payments = $ending_balance;
?>
<div id="receipt_wrapper" style="font-size:<?php echo $this->config->item('receipt_font_size'); ?>px">
    <div id="receipt_header">
        <?php
        if ($this->config->item('company_logo') != '') {
            ?>
            <div id="company_name">
                <img id="image" src="<?php echo base_url('uploads/' . $this->config->item('company_logo')); ?>"
                     alt="company_logo"/>
            </div>
            <?php
        }
        ?>

        <?php
        if ($this->config->item('receipt_show_company_name')) {
            ?>
            <div id="company_name"><?php echo $this->config->item('company'); ?></div>
            <?php
        }
        ?>

        <div id="company_address"><?php echo nl2br($this->config->item('address')); ?></div>
        <div id="company_phone"><?php echo $this->config->item('phone'); ?></div>
        <div id="sale_receipt"><?php echo $this->lang->line('sales_receipt'); ?></div>
        <div id="sale_time"><?php echo $transaction_time ?></div>
    </div>

    <div id="receipt_general_info">
        <?php
        if (isset($customer)) {
            ?>
            <div id="customer"><?php echo $this->lang->line('customers_customer') . ": " . $customer; ?></div>
            <?php
        }
        ?>

        <div id="sale_id"><?php echo $this->lang->line('sales_id') . ": " . $sale_id; ?></div>

        <?php
        if (!empty($invoice_number)) {
            ?>
            <div id="invoice_number"><?php echo $this->lang->line('sales_invoice_number') . ": " . $invoice_number; ?></div>
            <?php
        }
        ?>

        <div id="employee"><?php echo $this->lang->line('employees_employee') . ": " . $employee; ?></div>
    </div>

    <table id="receipt_items">
        <tr>
            <th style="width:40%;"><?php echo $this->lang->line('sales_description_abbrv'); ?></th>
            <th style="width:20%;"><?php echo $this->lang->line('sales_price'); ?></th>
            <th style="width:20%;"><?php echo $this->lang->line('sales_quantity'); ?></th>
            <th style="width:20%;" class="total-value"><?php echo $this->lang->line('sales_total'); ?></th>
            <?php
            if ($this->config->item('receipt_show_tax_ind')) {
                ?>
                <th style="width:20%;"></th>
                <?php
            }
            ?>
        </tr>
        <?php
        foreach ($cart as $line => $item) {
            if ($item['print_option'] == PRINT_YES) {
                ?>
                <tr>
                    <td><?php echo ucfirst($item['name'] . ' ' . $item['attribute_values']); ?></td>
                    <td><?php echo to_currency($item['price']); ?></td>
                    <td><?php echo to_quantity_decimals($item['quantity']); ?></td>
                    <td class="total-value"><?php echo to_currency($item[($this->config->item('receipt_show_total_discount') ? 'total' : 'discounted_total')]); ?></td>
                    <?php
                    if ($this->config->item('receipt_show_tax_ind')) {
                        ?>
                        <td><?php echo $item['taxed_flag'] ?></td>
                        <?php
                    }
                    ?>
                </tr>
                <tr>
                    <?php
                    if ($this->config->item('receipt_show_description')) {
                        ?>
                        <td colspan="2"><?php echo $item['description']; ?></td>
                        <?php
                    }

                    if ($this->config->item('receipt_show_serialnumber')) {
                        ?>
                        <td><?php echo $item['serialnumber']; ?></td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
                if ($item['discount'] > 0) {
                    ?>
                    <tr>
                        <?php
                        if ($item['discount_type'] == FIXED) {
                            ?>
                            <td colspan="3"
                                class="discount"><?php echo to_currency($item['discount']) . " " . $this->lang->line("sales_discount") ?></td>
                            <?php
                        } elseif ($item['discount_type'] == PERCENT) {
                            ?>
                            <td colspan="3"
                                class="discount"><?php echo number_format($item['discount'], 0) . " " . $this->lang->line("sales_discount_included") ?></td>
                            <?php
                        }
                        ?>
                        <td class="total-value"><?php echo to_currency($item['discounted_total']); ?></td>
                    </tr>
                    <?php
                }
            }
        }
        ?>

        <?php
        if ($this->config->item('receipt_show_total_discount') && $discount > 0) {
            ?>
            <tr>
                <td colspan="3"
                    style='text-align:right;border-top:2px solid #000000;'><?php echo $this->lang->line('sales_sub_total'); ?></td>
                <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($prediscount_subtotal); ?></td>
            </tr>
            <tr>
                <td colspan="3" class="total-value"><?php echo $this->lang->line('sales_customer_discount'); ?>:</td>
                <td class="total-value"><?php echo to_currency($discount * -1); ?></td>
            </tr>
            <?php
        }
        ?>

        <?php
        if ($this->config->item('receipt_show_taxes')) {
            ?>
            <tr>
                <td colspan="3"
                    style='text-align:right;border-top:2px solid #000000;'><?php echo $this->lang->line('sales_sub_total'); ?></td>
                <td style='text-align:right;border-top:2px solid #000000;'><?php echo to_currency($subtotal); ?></td>
            </tr>
            <?php
            foreach ($taxes as $tax_group_index => $tax) {
                ?>
                <tr>
                    <td colspan="3"
                        class="total-value"><?php echo (float)$tax['tax_rate'] . '% ' . $tax['tax_group']; ?>:
                    </td>
                    <td class="total-value"><?php echo to_currency_tax($tax['sale_tax_amount']); ?></td>
                </tr>
                <?php
            }
            ?>
            <?php
        }
        ?>

        <tr>
        </tr>

        <?php $border = (!$this->config->item('receipt_show_taxes') && !($this->config->item('receipt_show_total_discount') && $discount > 0)); ?>

        <tr>
            <td colspan="3"
                style="text-align:right;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo $this->lang->line('sales_total'); ?></td>
            <td style="text-align:right;<?php echo $border ? 'border-top: 2px solid black;' : ''; ?>"><?php echo to_currency($total); ?></td>
        </tr>

        <tr>
            <td colspan="4">&nbsp;</td>
        </tr>


        <?php
        $only_sale_check = FALSE;
        $show_giftcard_remainder = FALSE;
        foreach ($payments as $payment_id => $payment) {
            $only_sale_check |= $payment['payment_type'] == $this->lang->line('sales_check');
            $splitpayment = explode(':', $payment['payment_type']);
            $show_giftcard_remainder |= $splitpayment[0] == $this->lang->line('sales_giftcard');

            if ($payment['payment_type'] == $this->lang->line('sales_due')) {
                // if it's old receipt then remove the due payment, else add it ot closing balance
                if (!empty($is_old_receipt)) {
                    $ending_balance -= $payment['payment_amount'];
                }else{
                    $balance_after_payments = $ending_balance + $payment['payment_amount'];
                }
            }
            ?>
            <tr>
                <td colspan="3" style="text-align:right;"><?php echo $splitpayment[0]; ?> </td>
                <td class="total-value"><?php echo to_currency(($payment['payment_amount'] - $payment['cash_refund']) * -1); ?></td>
            </tr>
            <?php
        }
        ?>
        <tr>
            <td colspan="3" style="text-align:right;">Ending Balance</td>
            <td style="text-align:right;">+<?php echo to_currency($ending_balance); ?></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:right;">Cash Adjustments</td>
            <td style="text-align:right;"><?php echo to_currency($cash_refunds * -1); ?></td>
        </tr>

        <tr>
            <td colspan="4">&nbsp;</td>
        </tr>

        <?php
        if (isset($cur_giftcard_value) && $show_giftcard_remainder) {
            ?>
            <tr>
                <td colspan="3"
                    style="text-align:right;"><?php echo $this->lang->line('sales_giftcard_balance'); ?></td>
                <td class="total-value"><?php echo to_currency($cur_giftcard_value); ?></td>
            </tr>
            <?php
        }
        ?>
        <tr>
            <td colspan="3"
                style="text-align:right;"> <?php echo $this->lang->line($amount_change >= 0 ? ($only_sale_check ? 'sales_check_balance' : 'sales_change_due') : 'sales_amount_due'); ?> </td>
            <td class="total-value"><?php echo to_currency($amount_change); ?></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right">Closing Balance</td>
            <td  style="text-align: right"><?= to_currency($balance_after_payments) ?></td>
        </tr>
    </table>

    <div id="sale_return_policy">
        <?php echo nl2br($this->config->item('return_policy')); ?>
    </div>

    <div id="barcode">
        <img src='data:image/png;base64,<?php echo $barcode; ?>'/><br>
        <?php echo $sale_id; ?>
    </div>
</div>
