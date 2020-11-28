<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_wholesale extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating wholesale changes');
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.2_wholesale.sql');
	}

	public function down()
	{
	}
}
?>