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
				array('input' => '1.2.3.', 'expected' => false),
				array('input' => '.1.2', 'expected' => false),
				array('input' => '1.2..3', 'expected' => false),
				array('input' => 'v1.0', 'expected' => false),
				array('input' => '1,0', 'expected' => false),
				array('input' => '1.0-beta', 'expected' => false),
				array('input' => ' 1.0', 'expected' => false),
				array('input' => '1.0 ', 'expected' => false),
				array('input' => '1.2.3.4.5', 'expected' => true),
				array('input' => '0.0', 'expected' => true),
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
			'trim_spaces' => array(
				array('input' => 'Hello World', 'expected' => 'Hello World'),
				array('input' => 'Hello  World', 'expected' => 'Hello World'),
				array('input' => '  Hello World', 'expected' => 'Hello World'),
				array('input' => 'Hello World  ', 'expected' => 'Hello World'),
				array('input' => '  Hello   World  ', 'expected' => 'Hello World'),
				array('input' => '', 'expected' => ''),
				array('input' => '   ', 'expected' => ''),
				array('input' => 'A B C', 'expected' => 'A B C'),
				array('input' => 'A  B   C', 'expected' => 'A B C'),
				array('input' => '   a   b   c   ', 'expected' => 'a b c'),
			),
			'plural_handler' => array(
				array('args' => array(1, 'apple'), 'expected' => '1 apple'),
				array('args' => array(2, 'apple'), 'expected' => '2 apples'),
				array('args' => array(0, 'apple'), 'expected' => '0 apples'),
				array('args' => array(-1, 'apple'), 'expected' => '-1 apples'),
				array('args' => array(100, 'test'), 'expected' => '100 tests'),
				array('args' => array(1.5, 'apple'), 'expected' => '1.5 apples'),
				array('args' => array('1', 'apple'), 'expected' => '1 apple'),
				array('args' => array('2', 'apple'), 'expected' => '2 apples'),
				array('args' => array('1,000', 'apple'), 'expected' => '1,000 apples'),
				array('args' => array(null, 'apple'), 'expected' => ' apples'),
				array('args' => array(1.0, 'apple'), 'expected' => '1 apple'),
				array('args' => array('1.0', 'apple'), 'expected' => '1.0 apple'),
				array('args' => array(true, 'apple'), 'expected' => '1 apple'),
				array('args' => array(false, 'apple'), 'expected' => ' apples'),
				array('args' => array('', 'apple'), 'expected' => ' apples'),
				array('args' => array('0', 'apple'), 'expected' => '0 apples'),
			),
			'char_is_of_type' => array(
				// CHAR_LETTER
				array('args' => array('A', pts_strings::CHAR_LETTER), 'expected' => true),
				array('args' => array('z', pts_strings::CHAR_LETTER), 'expected' => true),
				array('args' => array('1', pts_strings::CHAR_LETTER), 'expected' => false),
				array('args' => array('@', pts_strings::CHAR_LETTER), 'expected' => false),

				// CHAR_NUMERIC
				array('args' => array('0', pts_strings::CHAR_NUMERIC), 'expected' => true),
				array('args' => array('9', pts_strings::CHAR_NUMERIC), 'expected' => true),
				array('args' => array('a', pts_strings::CHAR_NUMERIC), 'expected' => false),

				// CHAR_DECIMAL
				array('args' => array('.', pts_strings::CHAR_DECIMAL), 'expected' => true),
				array('args' => array(',', pts_strings::CHAR_DECIMAL), 'expected' => false),

				// CHAR_SPACE
				array('args' => array(' ', pts_strings::CHAR_SPACE), 'expected' => true),
				array('args' => array('a', pts_strings::CHAR_SPACE), 'expected' => false),

				// CHAR_DASH
				array('args' => array('-', pts_strings::CHAR_DASH), 'expected' => true),
				array('args' => array('_', pts_strings::CHAR_DASH), 'expected' => false),

				// CHAR_UNDERSCORE
				array('args' => array('_', pts_strings::CHAR_UNDERSCORE), 'expected' => true),
				array('args' => array('-', pts_strings::CHAR_UNDERSCORE), 'expected' => false),

				// CHAR_COLON
				array('args' => array(':', pts_strings::CHAR_COLON), 'expected' => true),
				array('args' => array(';', pts_strings::CHAR_COLON), 'expected' => false),

				// CHAR_COMMA
				array('args' => array(',', pts_strings::CHAR_COMMA), 'expected' => true),
				array('args' => array('.', pts_strings::CHAR_COMMA), 'expected' => false),

				// CHAR_SLASH
				array('args' => array('/', pts_strings::CHAR_SLASH), 'expected' => true),
				array('args' => array('\\', pts_strings::CHAR_SLASH), 'expected' => true),
				array('args' => array('|', pts_strings::CHAR_SLASH), 'expected' => false),

				// CHAR_AT
				array('args' => array('@', pts_strings::CHAR_AT), 'expected' => true),
				array('args' => array('a', pts_strings::CHAR_AT), 'expected' => false),

				// CHAR_PLUS
				array('args' => array('+', pts_strings::CHAR_PLUS), 'expected' => true),
				array('args' => array('-', pts_strings::CHAR_PLUS), 'expected' => false),

				// CHAR_SEMICOLON
				array('args' => array(';', pts_strings::CHAR_SEMICOLON), 'expected' => true),
				array('args' => array(':', pts_strings::CHAR_SEMICOLON), 'expected' => false),

				// CHAR_EQUAL
				array('args' => array('=', pts_strings::CHAR_EQUAL), 'expected' => true),
				array('args' => array('+', pts_strings::CHAR_EQUAL), 'expected' => false),

				// Combined attributes
				array('args' => array('A', pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC), 'expected' => true),
				array('args' => array('1', pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC), 'expected' => true),
				array('args' => array('.', pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC), 'expected' => false),
				array('args' => array(' ', pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC), 'expected' => false),

				array('args' => array('.', pts_strings::CHAR_DECIMAL | pts_strings::CHAR_COMMA), 'expected' => true),
				array('args' => array(',', pts_strings::CHAR_DECIMAL | pts_strings::CHAR_COMMA), 'expected' => true),
				array('args' => array('a', pts_strings::CHAR_DECIMAL | pts_strings::CHAR_COMMA), 'expected' => false),
			),
		);

		foreach ($test_suites as $method => $test_cases)
		{
			echo "Testing pts_strings::$method ..." . PHP_EOL;

			foreach ($test_cases as $case)
			{
				$expected = $case['expected'];
				$result = null;
				$input_display = null;

				if(isset($case['args']))
				{
					$result = call_user_func_array(array('pts_strings', $method), $case['args']);
					$input_display = implode(', ', array_map(function($v) { return var_export($v, true); }, $case['args']));
				}
				else
				{
					$input = $case['input'];
					$result = pts_strings::$method($input);
					// Mimic original output format which was '$input'
					$input_display = "'" . $input . "'";
				}

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
