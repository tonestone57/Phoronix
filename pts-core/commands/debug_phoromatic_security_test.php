<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2022, Phoronix Media
	Copyright (C) 2010 - 2022, Michael Larabel

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

class debug_phoromatic_security_test implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'Unit tests for Phoromatic security sanitization';

	public static function run($r)
	{
		require_once(PTS_CORE_PATH . 'phoromatic/phoromatic_functions.php');

		$test_inputs = array(
			'127.0.0.1' => '127.0.0.1:80',
			// 'localhost' => 'localhost:80', // Function resolves localhost to LAN IP
			'example.com' => 'example.com:80',
			'192.168.1.1:8080' => '192.168.1.1:80', // Port stripped then re-appended from SERVER_PORT
			'malicious.com; rm -rf /' => 'malicious.comrm-rf:80',
			'foo$(reboot)bar' => 'foorebootbar:80',
			'1.2.3.4|cat /etc/passwd' => '1.2.3.4catetcpasswd:80',
			'foo`touch /tmp/hacked`bar' => 'footouchtmphackedbar:80',
			'invalid chars <>!@#$%^&*()' => 'invalidchars:80',
			'[::1]' => ':80', // IPv6 brackets not allowed
		);

		$passed = 0;
		$failed = 0;

		$_SERVER['SERVER_PORT'] = 80;

		foreach($test_inputs as $input => $expected)
		{
			$_SERVER['HTTP_HOST'] = $input;
			$result = phoromatic_web_socket_server_ip();

			echo "Testing HTTP_HOST='$input' -> '$result' (Expected: '$expected') ... ";

			if($result === $expected)
			{
				echo pts_client::cli_colored_text("PASSED", "green") . PHP_EOL;
				$passed++;
			}
			else
			{
				echo pts_client::cli_colored_text("FAILED", "red") . PHP_EOL;
				$failed++;
			}
		}

		// Additional check for keep_in_string specifically for shell metachars
		$shell_metachars = array(';', '|', '&', '$', '`', '>', '<', '(', ')', '\\', '"', "'", "\n", "\r");
		foreach($shell_metachars as $char)
		{
			$input = "foo" . $char . "bar";
			$sanitized = pts_strings::keep_in_string($input, pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_DASH | pts_strings::CHAR_COLON | pts_strings::CHAR_UNDERSCORE);

			echo "Testing keep_in_string('$input') -> '$sanitized' ... ";

			if($sanitized === "foobar")
			{
				echo pts_client::cli_colored_text("PASSED", "green") . PHP_EOL;
				$passed++;
			}
			else
			{
				echo pts_client::cli_colored_text("FAILED", "red") . " (Expected 'foobar')" . PHP_EOL;
				$failed++;
			}
		}

		echo PHP_EOL . "Tests passed: $passed" . PHP_EOL;
		echo "Tests failed: $failed" . PHP_EOL;

		if ($failed > 0)
		{
			exit(1);
		}
	}
}

?>
