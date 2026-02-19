<?php
/*
    Phoronix Test Suite
    Copyright (C) 2024
*/

class debug_phoromatic_security_test implements pts_option_interface
{
    const doc_section = 'Debugging';
    const doc_description = 'Unit tests for Phoromatic security functions';

    public static function run($r)
    {
        // Load phoromatic_functions.php if not already loaded
        if(!function_exists('phoromatic_web_socket_server_ip'))
        {
            require_once(PTS_CORE_PATH . 'phoromatic/phoromatic_functions.php');
        }

        $tests = array(
            'Basic IP' => array(
                'host' => '1.2.3.4',
                'expected_contains' => '1.2.3.4',
                'port' => 80
            ),
            'Injection Attempt 1' => array(
                'host' => "1.2.3.4'$(reboot)'",
                'expected_contains' => '1.2.3.4reboot',
                'port' => 80
            ),
            'Injection Attempt 2' => array(
                'host' => "1.2.3.4; ls -la",
                'expected_contains' => '1.2.3.4ls-la', // spaces removed
                'port' => 80
            ),
            'Injection Attempt 3' => array(
                'host' => "evil.com/hack",
                'expected_contains' => 'evil.comhack', // slash removed
                'port' => 80
            ),
             'Injection Attempt 4' => array(
                'host' => "evil.com:8080",
                'expected_contains' => 'evil.com', // port stripped before sanitize
                'port' => 8080
            ),
        );

        $passed = 0;
        $failed = 0;

        foreach($tests as $name => $case)
        {
            $_SERVER['HTTP_HOST'] = $case['host'];
            $_SERVER['SERVER_PORT'] = $case['port'];

            $result = phoromatic_web_socket_server_ip();
            // result is IP:PORT

            // Verify result contains only allowed chars + : + PORT
            $allowed_chars = pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_DASH | pts_strings::CHAR_COLON | pts_strings::CHAR_UNDERSCORE;

            // phoromatic_web_socket_server_ip appends :PORT.
            // So result should be SANITIZED_IP:PORT.

            $sanitized_check = pts_strings::keep_in_string($result, $allowed_chars);

            if($sanitized_check == $result)
            {
                // Also check if expected content is there (stripped of bad chars)
                if(strpos($result, $case['expected_contains']) !== false)
                {
                    echo pts_client::cli_colored_text("PASSED", "green") . ": Test '$name' Input '{$case['host']}' -> Output '$result'\n";
                    $passed++;
                }
                else
                {
                    echo pts_client::cli_colored_text("FAILED", "red") . ": Test '$name' Output '$result' does not contain '{$case['expected_contains']}'\n";
                    $failed++;
                }
            }
            else
            {
                echo pts_client::cli_colored_text("FAILED", "red") . ": Test '$name' Output '$result' contains forbidden characters! (Sanitized: '$sanitized_check')\n";
                $failed++;
            }
        }

        echo "\nTests Passed: $passed\nTests Failed: $failed\n";

        if($failed > 0)
        {
            exit(1);
        }
    }
}
?>
