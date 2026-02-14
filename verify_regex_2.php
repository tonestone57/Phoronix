<?php

function test_regex($name, $ps_output) {
    // Modified regex: allow space after name
    $regex = '/^\s*([0-9]+)\s+[0-9]+\s+(?:.*\/)?' . preg_quote($name, '/') . '(?:\s|$)/m';

    echo "Regex: $regex\n";
    echo "Testing Name: '$name'\n";

    if(preg_match_all($regex, $ps_output, $matches)) {
        echo "MATCHED PIDs: " . implode(', ', $matches[1]) . "\n";
    } else {
        echo "NO MATCH\n";
    }
    echo "----------------------------------------\n";
}

$mock_ps = "
100 101 init
200 201 /bin/web
300 301 webserver
400 401 /boot/system/apps/WebPositive
500 501 myweb
600 601 /bin/sh -c 'web'
700 701 web
800 801 web -p 80
";

echo "Mock PS Output:\n$mock_ps\n";

test_regex('web', $mock_ps);
test_regex('sh', $mock_ps);
