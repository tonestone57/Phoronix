<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016 - 2021, Phoronix Media
	Copyright (C) 2016 - 2021, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class php_conf implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'This option will print information that is useful to developers when debugging problems with the Phoronix Test Suite and/or test profiles and test suites.';

	public static function run($r)
	{
		$table = array();
		$table[] = array('PHP:', PTS_PHP_VERSION);
		$table[] = array('PHP VERSION ID: ', PHP_VERSION_ID);
		$table[] = array('PHP BINARY: ', getenv('PHP_BIN'));
		echo PHP_EOL . pts_user_io::display_text_table($table, null, 0) . PHP_EOL;
		echo PHP_EOL;
		echo 'MAIN CAPABILITY CHECK: ' . PHP_EOL;
		pts_client::program_requirement_checks(false, true);

		$functions_to_check = pts_client::php_functions_to_check();
		$table = array();
		foreach($functions_to_check as $func)
		{
			$table[] = array($func, (pts_client::function_check($func) ? pts_client::cli_just_bold('PRESENT') : 'MISSING'));
		}
		echo 'OPTIONAL FUNCTION CHECKS: ';
		echo PHP_EOL . pts_user_io::display_text_table($table, null, 0) . PHP_EOL;
	}
}

?>
