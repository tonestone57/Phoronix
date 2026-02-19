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
		$test_functions = array(
			'arithmetic_mean' => array(
				array('values' => array(1, 2, 3), 'expected' => 2),
				array('values' => array(10, 20, 30, 40), 'expected' => 25),
				array('values' => array(5), 'expected' => 5),
				array('values' => array(-1, 1), 'expected' => 0),
				array('values' => array(1.5, 2.5), 'expected' => 2),
				array('values' => array(), 'expected' => 0),
				array('values' => array(PHP_INT_MAX, PHP_INT_MAX), 'expected' => PHP_INT_MAX),
				array('values' => array(PHP_INT_MAX, PHP_INT_MIN), 'expected' => -0.5),
				array('values' => array(0.5, 0.25), 'expected' => 0.375),
				array('values' => array("10", "20"), 'expected' => 15),
				array('values' => array('a' => 10, 'b' => 20), 'expected' => 15),
				array('values' => array(true, false, true), 'expected' => 2/3),
				array('values' => array(null, null), 'expected' => 0),
			),
			'geometric_mean' => array(
				array('values' => array(1, 2, 4), 'expected' => 2),
				array('values' => array(3, 9, 27), 'expected' => 9),
				array('values' => array(5), 'expected' => 5),
				array('values' => array(2, 2, 2, 2, 2, 2, 2, 2, 2), 'expected' => 2),
				array('values' => array(), 'expected' => 0),
				array('values' => array(1.1, 2.2, 3.3), 'expected' => 1.9988326521154),
				array('values' => array(0, 10, 100), 'expected' => 0),
				array('values' => array_fill(0, 2000, 2), 'expected' => 2),
				array('values' => array_fill(0, 2000, 0.5), 'expected' => 0.5),
			),
		);

		$passed = 0;
		$failed = 0;

		foreach($test_functions as $func => $test_cases)
		{
			foreach($test_cases as $case)
			{
				$values = $case['values'];
				$expected = $case['expected'];

				$values_str = implode(', ', array_slice($values, 0, 10));
				if(count($values) > 10)
				{
					$values_str .= ', ... (' . count($values) . ' items)';
				}
				echo "Testing $func([" . $values_str . "]) ... ";

				$result = pts_math::$func($values);

				$is_pass = false;

				if(abs($result - $expected) < 0.0001)
				{
					$is_pass = true;
				}

				if($is_pass)
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
		}

		echo PHP_EOL . "Tests passed: $passed" . PHP_EOL;
		echo "Tests failed: $failed" . PHP_EOL;

		if ($failed > 0)
		{
			exit(1);
		}
	}

	private static function is_approx_equal($a, $b, $epsilon = 0.00001)
	{
		if(is_numeric($a) && is_numeric($b))
		{
			return abs($a - $b) < $epsilon;
		}
		return $a == $b;
	}
}

?>
