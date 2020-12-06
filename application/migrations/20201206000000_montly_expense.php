<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_montly_expense extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating wholesale changes');
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.2_montly_expense.sql');
	}

	public function down()
	{
	}
}
?>