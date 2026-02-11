# Porting Phoronix Test Suite to Haiku OS

This document outlines the requirements and changes needed to port the Phoronix Test Suite (PTS) to Haiku OS.

## Overview

The Phoronix Test Suite is primarily written in PHP and relies on shell commands for system interaction. To support Haiku, we need to:
1.  Enable OS detection in the core framework.
2.  Implement a parser for Haiku-specific system commands (`sysinfo`, `listdev`, etc.).
3.  Update hardware detection components to use the Haiku parser.

## Core Modifications

### 1. `pts-core/objects/pts_types.php`
-   **Add Haiku to supported operating systems:**
    -   Update `operating_systems()` to include `array('Haiku')`.
    -   Update `known_operating_systems()` to include `'Haiku'`.

### 2. `pts-core/objects/phodevi/phodevi.php`
-   **OS Detection Logic:**
    -   Add `'haiku' => false` to the `private static $operating_systems` array.
    -   Implement `public static function is_haiku()` to return the detection status.
    -   Update `initial_setup()` to detect Haiku using `php_uname('s')` (which returns "Haiku").

## New Parser: `phodevi_haiku_parser`

A new class `phodevi_haiku_parser` should be created in `pts-core/objects/phodevi/parsers/phodevi_haiku_parser.php`. This class will handle parsing of Haiku-specific commands.

### Suggested Methods:
-   `read_sysinfo()`: Parses the output of the `sysinfo` command to retrieve CPU and memory information.
-   `read_listdev()`: Parses the output of `listdev` to identify PCI/USB devices (GPU, Network, Audio, etc.).
-   `read_disk_info()`: Uses `df` or Haiku-specific API to get disk usage and filesystem info.

## Component Updates

The following components in `pts-core/objects/phodevi/components/` need updates to use `phodevi_haiku_parser` when running on Haiku:

### `phodevi_system.php`
-   **OS Name:** Use `php_uname('s')` or `uname` command.
-   **Kernel Version:** Use `uname -v` or `uname -r`.
-   **Hostname:** Use `hostname` command.

### `phodevi_cpu.php`
-   **Model:** Parse from `sysinfo -cpu` or `/proc/cpuinfo` if available (Haiku doesn't have standard `/proc` like Linux, so `sysinfo` is key).
-   **Core Count:** Parse from `sysinfo -cpu`.
-   **Frequency:** Parse from `sysinfo -cpu`.

### `phodevi_memory.php`
-   **Capacity:** Parse from `sysinfo -mem`.
-   **Details:** Parse from `sysinfo` or `listdev` if memory controller info is available.

### `phodevi_gpu.php`
-   **Model:** Parse from `listdev` (look for Display controller).
-   **Driver:** Check for loaded drivers via `listdev` or `listimage`.

### `phodevi_disk.php`
-   **Filesystem:** Use `df` or `mount` command output.
-   **Capacity:** Use `df` output.

## Required Commands on Haiku
The following commands are expected to be available on a standard Haiku installation:
-   `sysinfo`
-   `listdev`
-   `uname`
-   `df`
-   `mount`
-   `hostname`
-   `php` (CLI)

## Next Steps
1.  Implement the changes outlined above.
2.  Test on a running Haiku system.
3.  Refine parsing logic based on actual command output on Haiku.
