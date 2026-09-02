<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_warranties extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating warranties module');
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.2_warranties.sql');
	}

	public function down()
	{
	}
}
