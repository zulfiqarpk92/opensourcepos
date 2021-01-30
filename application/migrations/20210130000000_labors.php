<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_labors extends CI_Migration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function up()
	{
		error_log('Migrating init balance changes');
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.2_labors.sql');
	}

	public function down()
	{
	}
}