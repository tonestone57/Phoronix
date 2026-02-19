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

class debug_pts_validation_test implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'Unit tests for pts_validation';

	public static function run($r)
	{
		$passed = 0;
		$failed = 0;

		$test_suites = array(
			'string_to_sanitized_test_profile_base' => array(
				array('input' => 'Test Profile', 'expected' => 'test-profile'),
				array('input' => 'Test  Profile', 'expected' => 'test--profile'),
				array('input' => 'Foo123', 'expected' => 'foo123'),
				array('input' => 'Invalid!@#Chars', 'expected' => 'invalidchars'),
				array('input' => 'A-B', 'expected' => 'a-b'),
				array('input' => '   ', 'expected' => '---'),
				array('input' => '', 'expected' => ''),
				array('input' => 'a b c', 'expected' => 'a-b-c'),
				array('input' => 'CamelCase', 'expected' => 'camelcase'),
				array('input' => 'Mixed 123-Text', 'expected' => 'mixed-123-text'),
				array('input' => 'Hello_World', 'expected' => 'helloworld'),
				array('input' => 'Foo.Bar', 'expected' => 'foobar'),
			),
		);

		foreach ($test_suites as $method => $test_cases)
		{
			echo "Testing pts_validation::$method ..." . PHP_EOL;

			foreach ($test_cases as $case)
			{
				$expected = $case['expected'];
				$result = null;
				$input_display = null;

				$input = $case['input'];
				$result = pts_validation::$method($input);
				$input_display = "'" . $input . "'";

				echo "  $input_display ... ";

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
