# Porting Phoronix Test Suite to Haiku OS

This document outlines the status and implementation details of the Phoronix Test Suite (PTS) port to Haiku OS.

## Status: Initial Implementation Complete

The core framework detection and hardware parsing scaffolding have been implemented.

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
    -   `cpu_model`: CPU brand string.
    -   `cpu_count`: Number of logical CPUs.
    -   `mem_size`: Total physical memory.
-   **`read_listdev()`**: Parses `listdev` output to identify PCI/USB devices. Handles device classes and vendor/device strings.
-   **`read_disk_info()`**: Parses `df -h` output to retrieve filesystem usage and mounting information.

### 3. Component Updates
The following components have been updated to utilize `phodevi_haiku_parser` when running on Haiku:

-   **`phodevi_system.php`**: OS version and vendor detection.
-   **`phodevi_cpu.php`**: CPU model and core count detection via `sysinfo`.
-   **`phodevi_memory.php`**: Memory capacity detection via `sysinfo`.
-   **`phodevi_gpu.php`**: GPU model detection via `listdev` (scanning for 'Display controller' or 'VGA').
-   **`phodevi_disk.php`**: Disk capacity and filesystem reporting via `df`.

## Required Commands on Haiku
The implementation relies on these standard Haiku commands:
-   `sysinfo`
-   `listdev`
-   `uname`
-   `df`
-   `hostname`
-   `php` (CLI)

## Next Steps / To-Do
1.  **Testing**: Verify the code on a live Haiku system to ensure parser regexes match the exact output formats of the command-line tools.
2.  **Refinement**:
    -   Improve detailed CPU feature detection (flags) if possible.
    -   Add sensor monitoring support if Haiku exposes hardware sensors via command line or file system.
    -   Expand `read_listdev` parsing to capture more device details (IDs, revisions) if needed.
