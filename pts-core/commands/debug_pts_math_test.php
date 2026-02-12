<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class debug_pts_math_test implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'Unit tests for pts_math::arithmetic_mean';

	public static function run($r)
	{
		$test_cases = array(
			array('values' => array(1, 2, 3), 'expected' => 2),
			array('values' => array(10, 20, 30, 40), 'expected' => 25),
			array('values' => array(5), 'expected' => 5),
			array('values' => array(-1, 1), 'expected' => 0),
			array('values' => array(1.5, 2.5), 'expected' => 2),
			array('values' => array(), 'expected' => 0),
		);

		$passed = 0;
		$failed = 0;

		foreach($test_cases as $case)
		{
			$values = $case['values'];
			$expected = $case['expected'];

			echo "Testing arithmetic_mean([" . implode(', ', $values) . "]) ... ";

			$result = pts_math::arithmetic_mean($values);

			if ($result == $expected)
			{
				echo pts_client::cli_colored_text("PASSED", "green") . PHP_EOL;
				$passed++;
			}
			else
			{
				echo pts_client::cli_colored_text("FAILED", "red") . " (Expected $expected, got " . (is_null($result) ? 'NULL' : $result) . ")" . PHP_EOL;
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
