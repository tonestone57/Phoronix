<?php

// Mock dependencies
if(!defined('PTS_IS_CLIENT'))
{
    define('PTS_IS_CLIENT', true);
}

// Mock phodevi class minimal methods if not loaded
if(!class_exists('phodevi')) {
    class phodevi {
        public static function is_haiku() { return true; }
        public static function is_windows() { return false; }
        public static function is_linux() { return false; }
        public static function is_bsd() { return false; }
        public static function is_macos() { return false; }
        public static function is_solaris() { return false; }
        public static function is_nvidia_graphics() { return false; }
        public static function read_property($type, $prop) { return null; }
        public static $vfs = null;
    }
}

// Mock pts_client
if(!class_exists('pts_client')) {
    class pts_client {
        public static function executable_in_path($cmd) { return $cmd; }
        public static function is_process_running($proc) { return false; }
    }
}

if(!class_exists('pts_math')) {
    class pts_math {
        public static function set_precision($v, $p) { return round($v, $p); }
    }
}

// We cannot mock shell_exec, so we skip tests that strictly depend on it returning mocked data
// unless we can refactor code. For now, we just test that classes load and methods exist.

require_once('pts-core/objects/phodevi/parsers/phodevi_haiku_parser.php');
require_once('pts-core/objects/phodevi/phodevi_device_interface.php');
require_once('pts-core/objects/phodevi/components/phodevi_motherboard.php');
require_once('pts-core/objects/phodevi/components/phodevi_cpu.php');
require_once('pts-core/objects/phodevi/components/phodevi_system.php');
require_once('pts-core/objects/phodevi/components/phodevi_disk.php');
require_once('pts-core/objects/phodevi/components/phodevi_network.php');
require_once('pts-core/objects/phodevi/components/phodevi_gpu.php');
require_once('pts-core/objects/phodevi/phodevi_sensor.php');
require_once('pts-core/objects/phodevi/sensors/memory_usage.php');
require_once('pts-core/objects/phodevi/sensors/cpu_freq.php');

function test_haiku_tasks() {
    echo "Testing Haiku Tasks (Syntax/Load Check)...\n";

    // 4. Mount Options
    // This will likely fail or return null because shell_exec returns empty/real system output
    // $mounts = phodevi_disk::proc_mount_options('/');

    // 5. Scheduler
    $sched = phodevi_disk::hdd_scheduler();
    assert($sched === null, 'Scheduler should be null');
    echo "Scheduler [OK]\n";

    // 10. Desktop
    $desktop = phodevi_system::sw_desktop_environment();
    // Expected to be 'Haiku' if is_haiku() is true
    assert($desktop == 'Haiku', 'Desktop failed: ' . $desktop);
    echo "Desktop [OK]\n";

    // 11. Display Server
    $display = phodevi_system::sw_display_server();
    assert($display == 'Haiku app_server', 'Display Server failed: ' . $display);
    echo "Display Server [OK]\n";

    // 15. CPU Freq Fallback check
    $cpu_freq_sensor = new cpu_freq(0, null);
    // read_sensor() calls phodevi::read_property which returns null in our mock, so it might fail calculation or return 0
    // We just check if it runs without error
    $freq = $cpu_freq_sensor->read_sensor();
    echo "CPU Freq Sensor instantiated [OK]\n";

    echo "Basic Haiku tasks verification passed!\n";
}

test_haiku_tasks();

?>
