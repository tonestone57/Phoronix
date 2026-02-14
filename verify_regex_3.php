<?php

function test_regex($name, $ps_output) {
    $regex = '/^\s*([0-9]+)\s+[0-9]+\s+(?:.*\/)?' . preg_quote($name, '/') . '(?:\s|$)/m';

    echo "Regex: $regex\n";
    echo "Testing Name: '$name'\n";

    if(preg_match_all($regex, $ps_output, $matches)) {
        echo "MATCHED PIDs: " . implode(', ', $matches[1]) . "\n";
    } else {
        echo "NO MATCH\n";
    }
}

$mock_ps = "300 301 webserver";
test_regex('web', $mock_ps);
