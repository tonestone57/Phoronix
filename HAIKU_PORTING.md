# Porting Phoronix Test Suite to Haiku OS

This document outlines the status and implementation details of the Phoronix Test Suite (PTS) port to Haiku OS.

## Status: Hardware Detection Implemented

The core framework detection and hardware parsing have been significantly expanded to cover CPU, GPU, Network, Motherboard, and more.

## Implementation Details

### 1. Core Modifications
-   **`pts-core/objects/pts_types.php`**: Added 'Haiku' to supported operating systems.
-   **`pts-core/objects/phodevi/phodevi.php`**:
    -   Added 'haiku' to operating systems array.
    -   Implemented `is_haiku()` detection method.
    -   Updated `initial_setup()` to set the haiku flag when `php_uname('s')` returns "Haiku".

### 2. New Parser: `phodevi_haiku_parser`
-   Located at: `pts-core/objects/phodevi/parsers/phodevi_haiku_parser.php`
-   **`read_sysinfo($info)`**: Parses `sysinfo` output for:
    -   `cpu_model`, `cpu_count`: CPU details.
    -   `cpu_features`: CPU instruction set extensions.
    -   `mem_size`: Total physical memory.
    -   `system_vendor`, `system_product`, `system_version`, `system_serial`: Motherboard/System DMI info.
    -   `bios_vendor`, `bios_version`, `bios_date`: BIOS details.
-   **`read_listdev()`**: Parses `listdev` output. Returns an array of devices with:
    -   `class`: Device class string.
    -   `vendor_id`: PCI Vendor ID (e.g., '8086').
    -   `device_id`: PCI Device ID.
    -   `vendor`: Vendor name string.
    -   `device`: Device description string.
-   **`read_disk_info()`**: Parses `df -h` output for filesystem usage.

### 3. Component Updates
The following components have been updated to utilize `phodevi_haiku_parser` when running on Haiku:

-   **`phodevi_system.php`**:
    -   OS version and vendor detection.
    -   Architecture detection (mapping 'BePC' to 'i686').
-   **`phodevi_cpu.php`**:
    -   CPU model and core count detection via `sysinfo`.
    -   CPU feature flags extraction.
-   **`phodevi_memory.php`**: Memory capacity detection via `sysinfo`.
-   **`phodevi_gpu.php`**:
    -   GPU model detection via `listdev` (scanning for 'Display controller' or 'VGA').
    -   GPU PCI Device ID extraction.
-   **`phodevi_disk.php`**: Disk capacity and filesystem reporting via `df`.
-   **`phodevi_network.php`**: Network interface detection via `listdev`.
-   **`phodevi_chipset.php`**: Chipset/Bridge detection via `listdev`.
-   **`phodevi_audio.php`**: Audio controller detection via `listdev`.
-   **`phodevi_monitor.php`**: Monitor detection via `get_edid`.
-   **`phodevi_motherboard.php`**: Motherboard and BIOS information via `sysinfo`.

## Required Commands on Haiku
The implementation relies on these standard Haiku commands:
-   `sysinfo`
-   `listdev`
-   `uname`
-   `df`
-   `get_edid`
-   `hostname`
-   `php` (CLI)

## Next Steps / To-Do
1.  **Run-Time Testing**: Verify the code on a live Haiku system to ensure all regexes match the real-world output formats.
2.  **Sensor Support**: Hardware sensor monitoring is currently not implemented due to lack of a standardized CLI tool.
3.  **Compiler Detection**: Verify `phodevi_system::sw_compiler` correctly identifies Haiku's GCC toolchain.
