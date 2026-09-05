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

class debug_pts_test_result_buffer_test implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'Unit tests for pts_test_result_buffer';

	public static function run($r)
	{
		$passed = 0;
		$failed = 0;

		$run_test = function($test_name, $callable) use (&$passed, &$failed) {
			echo "Testing $test_name ... ";
			try {
				if($callable())
				{
					echo pts_client::cli_colored_text("PASSED", "green") . PHP_EOL;
					$passed++;
				}
				else
				{
					echo pts_client::cli_colored_text("FAILED", "red") . PHP_EOL;
					$failed++;
				}
			} catch(Exception $e) {
				echo pts_client::cli_colored_text("FAILED (" . $e->getMessage() . ")", "red") . PHP_EOL;
				$failed++;
			}
		};

		$run_test('add_test_result and O(1) lookup', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('Run A', 100);
			$buffer->add_test_result('Run B', 200);

			if($buffer->get_count() !== 2) return false;
			if($buffer->get_result_from_identifier('Run A') != 100) return false;
			if($buffer->get_result_from_identifier('Run B') != 200) return false;
			if($buffer->get_result_from_identifier('Run C') !== false) return false;

			return true;
		});

		$run_test('remove and index rebuild', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('Run 1', 10);
			$buffer->add_test_result('Run 2', 20);
			$buffer->add_test_result('Run 3', 30);

			$buffer->remove('Run 2');

			if($buffer->get_count() !== 2) return false;
			if($buffer->get_result_from_identifier('Run 2') !== false) return false;
			if($buffer->get_result_from_identifier('Run 1') != 10) return false;
			if($buffer->get_result_from_identifier('Run 3') != 30) return false;

			// Verify 0-indexed contiguous array
			$items = $buffer->get_buffer_items();
			if(array_keys($items) !== array(0, 1)) return false;
			if($items[0]->get_result_identifier() !== 'Run 1') return false;
			if($items[1]->get_result_identifier() !== 'Run 3') return false;

			return true;
		});

		$run_test('rename and index update', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('Old Name', 50);
			$buffer->add_test_result('Keep Name', 75);

			$buffer->rename('Old Name', 'New Name');

			if($buffer->get_result_from_identifier('Old Name') !== false) return false;
			if($buffer->get_result_from_identifier('New Name') != 50) return false;
			if($buffer->get_result_from_identifier('Keep Name') != 75) return false;

			return true;
		});

		$run_test('reorder and index update', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('A', 1);
			$buffer->add_test_result('B', 2);
			$buffer->add_test_result('C', 3);

			$buffer->reorder(array('C', 'A', 'B'));

			$identifiers = $buffer->get_identifiers();
			if($identifiers !== array('C', 'A', 'B')) return false;

			if($buffer->get_result_from_identifier('A') != 1) return false;
			if($buffer->get_result_from_identifier('B') != 2) return false;
			if($buffer->get_result_from_identifier('C') != 3) return false;

			return true;
		});

		$run_test('sort_buffer_values and index update', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('High', '300');
			$buffer->add_test_result('Low', '100');
			$buffer->add_test_result('Mid', '200');

			$buffer->sort_buffer_values(true);

			$identifiers = $buffer->get_identifiers();
			if($identifiers !== array('Low', 'Mid', 'High')) return false;

			if($buffer->get_result_from_identifier('Low') != '100') return false;
			if($buffer->get_result_from_identifier('Mid') != '200') return false;
			if($buffer->get_result_from_identifier('High') != '300') return false;

			return true;
		});

		$run_test('clear_outlier_results and min/max update', function() {
			$buffer = new pts_test_result_buffer();
			$buffer->add_test_result('Low', 5);
			$buffer->add_test_result('Normal 1', 100);
			$buffer->add_test_result('Normal 2', 200);

			if($buffer->get_min_value(true) !== 'Low') return false;

			$buffer->clear_outlier_results(50);

			if($buffer->get_count() !== 2) return false;
			if($buffer->get_result_from_identifier('Low') !== false) return false;
			if($buffer->get_min_value(true) !== 'Normal 1') return false;

			return true;
		});

		echo PHP_EOL;
		echo "Tests passed: $passed" . PHP_EOL;
		echo "Tests failed: $failed" . PHP_EOL;

		if ($failed > 0)
		{
			exit(1);
		}
	}
}

?>
