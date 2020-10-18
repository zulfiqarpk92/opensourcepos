<?php $this->load->view("partial/header"); ?>

<script src="https://unpkg.com/vue@next"></script>

<div id="receiving_app">
  <div id="register_wrapper">
    
    <!-- Top register controls -->

    <?php echo form_open($controller_name."/add", array('id'=>'add_item_form', 'class'=>'form-horizontal panel panel-default')); ?>
      <div class="panel-body form-group">
        <ul>
          <li class="pull-left first_li">
            <label for="item" class='control-label'>
              <?php if($mode=='receive' or $mode=='requisition'){?>
                <?php echo $this->lang->line('receivings_find_or_scan_item'); ?>
              <?php } else { ?>
                <?php echo $this->lang->line('receivings_find_or_scan_item_or_receipt'); ?>
              <?php } ?>			
            </label>
          </li>
          <li class="pull-left">
            <?php echo form_input(array('name'=>'item', 'id'=>'item', 'class'=>'form-control input-sm', 'size'=>'50', 'tabindex'=>'1')); ?>
          </li>
          <li class="pull-right">
            <button id='new_item_button' class='btn btn-info btn-sm pull-right modal-dlg'
              data-btn-submit='<?php echo $this->lang->line('common_submit') ?>'
              data-btn-new='<?php echo $this->lang->line('common_new') ?>'
              data-href='<?php echo site_url("items/view"); ?>'
              title='<?php echo $this->lang->line('sales_new_item'); ?>'>
              <span class="glyphicon glyphicon-tag">&nbsp</span><?php echo $this->lang->line('sales_new_item'); ?>
            </button>
          </li>
        </ul>
      </div>
    <?php echo form_close(); ?>

    <!-- Receiving Items List -->

    <table class="sales_table_100" id="register">
      <thead>
        <tr>
          <th style="width:5%;"><?php echo $this->lang->line('common_delete'); ?></th>
          <th style="width:10%;"><?php echo $this->lang->line('sales_item_number'); ?></th>
          <th><?php echo $this->lang->line('receivings_item_name'); ?></th>
          <th style="width:10%;"><?php echo $this->lang->line('receivings_cost'); ?></th>
          <th style="width:8%;"><?php echo $this->lang->line('receivings_quantity'); ?></th>
          <th style="width:10%;"><?php echo $this->lang->line('receivings_ship_pack'); ?></th>
          <th style="width:14%;"><?php echo $this->lang->line('receivings_discount'); ?></th>
          <th style="width:10%;"><?php echo $this->lang->line('receivings_total'); ?></th>
        </tr>
      </thead>

		  <tbody id="cart_contents">
        <tr v-for="item in cart_items">
          <td><?php echo anchor($controller_name."/delete_item", '<span class="glyphicon glyphicon-trash"></span>');?></td>
          <td>{{item.item_number}}</td>
          <td style="align:center;">
            {{item.name}} {{item.attributes}} <br>
            [{{item.in_stock}} in {{item.stock_name}}]
          </td>
          <td>
            <input class="form-control input-sm" type="text" name="price" v-model="item.price" @keyup="recalculateCart">
          </td>
          <td>
            <input class="form-control input-sm" type="text" name="quantity" v-model="item.quantity" @keyup="recalculateCart">
          </td>
          <td>
            <select class="form-control input-sm" name="receiving_quantity" v-model="item.receiving_quantity">
              <option v-for="(rqc_val, rqc_key) in item.receiving_quantity_choices" :value="rqc_key">{{rqc_val}}</option>
            </select>
          </td>
          <td>
            <div class="input-group"> 
              <input class="form-control input-sm" type="text" name="discount" v-model="item.discount">
              <span class="input-group-btn">
                <input type="checkbox" name="discount_toggle" id="discount_toggle" value="1" 
                       data-toggle="toggle" 
                       data-size="small" 
                       data-onstyle="success" 
                       data-on="<?php echo '<b>'.$this->config->item('currency_symbol').'</b>'; ?>" 
                       data-off="<b>%</b>" :data-line="item.line" :checked="item.discount_type != <?php echo PERCENT; ?>">
              </span>
            </div> 
          </td>
		      <td v-if="item.discount_type == <?php echo PERCENT; ?>">{{currency_symbol}}{{item.total.toFixed(2)}}</td> 
		      <td v-if="item.discount_type != <?php echo PERCENT; ?>">{{currency_symbol}}{{item.total.toFixed(2)}}</td>
        </tr>
		</tbody>
	</table>
    
  </div>

  <div id="overall_sale" class="panel panel-default">
    <div class="panel-body">
      <table class="sales_table_100" id="sale_totals">
        <tr>
          <th style="width: 55%;"><?php echo $this->lang->line('sales_total'); ?></th>
          <th style="width: 45%; text-align: right;">{{currency_symbol}}{{cart_d.total.toFixed(2)}}</th>
        </tr>
      </table>
    </div>
  </div>

  <div>Counter: {{ counter }}</div>
  <button v-on:click="getItems">Load</button>
  <pre>{{cart_items}}</pre>
  <pre>{{cart_d}}</pre>
</div>

<div id="counter">
</div>

<script>
$(document).ready(function()
{
	$("#item").autocomplete(
	{
		source: '<?php echo site_url($controller_name."/stock_item_search"); ?>',
		minChars:0,
		delay:10,
		autoFocus: false,
		select:	function (a, ui) {
			$(this).val(ui.item.value);
			$("#add_item_form").submit();
			return false;
		}
  });
});
const Counter = {
  data() {
    return {
      counter: 5,
      currency_symbol: 'USD',
      cart_items: [],
      cart_total: 0
    }
  },
  computed: {
    cart_d(){
      let cart = {};
      cart.total = 0;
      this.cart_items.forEach(function(item){
        console.log(parseFloat(item.total));
        cart.total += parseFloat(item.total);
      });

      return cart;
    }
  },
  methods: {
    async getItems(){
      let response = await fetch('/receivings/get_cart');
      let data = await response.json();
      console.log(data);
      this.cart_items = data.cart_items;
      this.currency_symbol = data.currency_symbol;
      setTimeout(function(){$('[name="discount_toggle"]').bootstrapToggle();}, 300);
    },
    recalculateCart(){
      self = this;
      self.cart_total = 0;
      self.cart_items.forEach(function(item){
        if(item.discount_type == <?php echo PERCENT; ?>){
          item.total = ((item.price * item.quantity * item.receiving_quantity) - (item.price * item.quantity * item.receiving_quantity * item.discount / 100));
        }
        else{
          item.total = ((item.price * item.quantity * item.receiving_quantity) - item.discount);
        }
        console.log(item.total);
      });
    }
  },
  mounted(){
    this.getItems();
  }
}
Vue.createApp(Counter).mount('#receiving_app');
</script>
<?php $this->load->view("partial/footer"); ?>