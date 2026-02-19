<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2025, Phoronix Media
	Copyright (C) 2025, Michael Larabel

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

class debug_pts_strings_test implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'Unit tests for pts_strings';

	public static function run($r)
	{
		$passed = 0;
		$failed = 0;

		$test_suites = array(
			'is_url' => array(
				array('input' => 'http://www.phoronix.com', 'expected' => true),
				array('input' => 'https://phoronix-test-suite.com/', 'expected' => true),
				array('input' => 'ftp://ftp.example.com', 'expected' => true),
				array('input' => 'http://localhost', 'expected' => true),
				array('input' => 'http://127.0.0.1', 'expected' => true),
				array('input' => 'www.phoronix.com', 'expected' => false),
				array('input' => 'http:///', 'expected' => false),
				array('input' => 'mailto:test@example.com', 'expected' => false),
				array('input' => '/path/to/file', 'expected' => false),
				array('input' => 'not a url', 'expected' => false),
			),
			'is_version' => array(
				array('input' => '1.0', 'expected' => true),
				array('input' => '1.2.3', 'expected' => true),
				array('input' => '0.1', 'expected' => true),
				array('input' => '1.0.0', 'expected' => true),
				array('input' => '1.2.3.4', 'expected' => true),
				array('input' => '1', 'expected' => false),
				array('input' => 'abc', 'expected' => false),
				array('input' => '.', 'expected' => false),
				array('input' => '..', 'expected' => false),
				array('input' => '...', 'expected' => false),
				array('input' => '1..2', 'expected' => false),
				array('input' => '.1', 'expected' => false),
				array('input' => '1.', 'expected' => false),
				array('input' => '1. 2', 'expected' => false),
				array('input' => '', 'expected' => false),
			),
			'is_alnum' => array(
				array('input' => 'abc123', 'expected' => true),
				array('input' => 'abc 123', 'expected' => false),
				array('input' => '!!!', 'expected' => false),
			),
			'is_alpha' => array(
				array('input' => 'abc', 'expected' => true),
				array('input' => 'abc1', 'expected' => false),
				array('input' => ' ', 'expected' => false),
			),
			'is_digit' => array(
				array('input' => '123', 'expected' => true),
				array('input' => 'abc', 'expected' => false),
			),
			'is_upper' => array(
				array('input' => 'ABC', 'expected' => true),
				array('input' => 'Abc', 'expected' => false),
				array('input' => 'abc', 'expected' => false),
			),
		);

		foreach ($test_suites as $method => $test_cases)
		{
			echo "Testing pts_strings::$method ..." . PHP_EOL;

			foreach ($test_cases as $case)
			{
				$input = $case['input'];
				$expected = $case['expected'];

				$result = pts_strings::$method($input);

				echo "  '$input' ... ";

				if ($result === $expected)
				{
					echo pts_client::cli_colored_text("PASSED", "green") . PHP_EOL;
					$passed++;
				}
				else
				{
					echo pts_client::cli_colored_text("FAILED", "red") . " (Expected " . var_export($expected, true) . ", got " . var_export($result, true) . ")" . PHP_EOL;
					$failed++;
				}
			}
			echo PHP_EOL;
		}

		echo "Tests passed: $passed" . PHP_EOL;
		echo "Tests failed: $failed" . PHP_EOL;

		if ($failed > 0)
		{
			exit(1);
		}
	}
}

?>
