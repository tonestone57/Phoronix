<?php

// Mock dependencies
if(!defined('PTS_IS_CLIENT'))
{
    define('PTS_IS_CLIENT', true);
}

class pts_client {
    public static function executable_in_path($cmd) {
        // Return the command name itself so shell_exec tries to run it (and fails or succeeds)
        // or return false to test fallback paths?
        // Let's return the command to test regex safety against Linux output if it runs,
        // or empty output if it doesn't.
        return $cmd;
    }
}

class phodevi {
    public static function is_windows() { return false; }
    public static function is_haiku() { return true; }
}

// Load the parser
require_once('pts-core/objects/phodevi/parsers/phodevi_haiku_parser.php');

// Helpers needed by parser
if(!function_exists('pts_client::executable_in_path')) {
    // Already defined in class above? PHP might complain if I redefine functions.
    // The class def above handles static method.
}

echo "Starting Haiku Parser Audit...\n";

$class = new ReflectionClass('phodevi_haiku_parser');
$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

$success_count = 0;
$fail_count = 0;

foreach($methods as $method) {
    $name = $method->getName();
    echo "Testing $name... ";

    // Skip methods that require arguments if they don't have defaults?
    // Most phodevi parsers allow null args or have defaults.
    $params = $method->getParameters();
    $args = array();
    $skip = false;

    foreach($params as $param) {
        if(!$param->isOptional()) {
            // Provide a dummy argument
            if($name == 'read_process_pid') $args[] = 'test_process';
            else if(strpos($name, 'pid') !== false) $args[] = 1234;
            else if(strpos($name, 'device_path') !== false) $args[] = '/dev/disk/test';
            else if(strpos($name, 'mount_point') !== false) $args[] = '/';
            else if(strpos($name, 'process_name') !== false) $args[] = 'bash';
            else $args[] = null;
        }
    }

    try {
        $result = $method->invokeArgs(null, $args);
        $type = gettype($result);
        $val = var_export($result, true);
        if(strlen($val) > 100) $val = substr($val, 0, 100) . '...';
        echo "OK (Type: $type, Value: $val)\n";
        $success_count++;
    } catch(Exception $e) {
        echo "FAIL (Exception: " . $e->getMessage() . ")\n";
        $fail_count++;
    } catch(Error $e) {
        echo "FAIL (Error: " . $e->getMessage() . ")\n";
        $fail_count++;
    }
}

echo "\nAudit Complete.\n";
echo "Success: $success_count\n";
echo "Failures: $fail_count\n";

?>
