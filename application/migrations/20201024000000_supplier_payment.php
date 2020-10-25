<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_supplier_payment extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating supplier payments changes');
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.2_supplierpaymentchanges.sql');
	}

	public function down()
	{
	}
}
?>