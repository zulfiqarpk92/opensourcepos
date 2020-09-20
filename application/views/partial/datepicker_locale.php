<?php $this->lang->load('calendar'); $this->lang->load('date'); ?>

var pickerconfig = function(config) {
    return $.extend({
      format: "<?php echo dateformat_momentjs($this->config->item('dateformat')) . ' ' . dateformat_momentjs($this->config->item('timeformat'));?>",
      locale: "<?php echo current_language_code(); ?>"
    }, <?php echo isset($config) ? $config : '{}' ?>);
};

$('.datetime').datetimepicker(pickerconfig());
