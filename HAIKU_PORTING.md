# Porting Phoronix Test Suite to Haiku OS

This document outlines the status and implementation details of the Phoronix Test Suite (PTS) port to Haiku OS.

## Status: External Dependencies Implemented

Hardware detection is complete, and support for installing external test dependencies via `pkgman` has been added.

## Implementation Details

### 1. Core Modifications
-   **`pts-core/objects/pts_types.php`**: Added 'Haiku' to supported operating systems.
-   **`pts-core/objects/phodevi/phodevi.php`**:
    -   Added 'haiku' to operating systems array.
    -   Implemented `is_haiku()` detection method.
    -   Updated `initial_setup()` to set the haiku flag when `php_uname('s')` returns "Haiku".

### 2. Hardware Detection (`phodevi_haiku_parser`)
-   **System**: Kernel release, build date, architecture (BePC mapped to i686), OS version.
-   **CPU**: Model, core count, feature flags (parsed from `sysinfo`).
-   **Memory**: Total physical memory.
-   **Motherboard/BIOS**: Vendor, product, version, serial, BIOS date/vendor.
-   **GPU**: Model detection via `listdev`, PCI device ID extraction.
-   **Disk**: Capacity, filesystem, model name refinement (e.g. virtio_block).
-   **Network**: Interface detection via `listdev`.
-   **Battery**: Presence check via `/dev/power/acpi_battery`.
-   **Monitor**: EDID extraction via `get_edid`.

### 3. External Dependencies
-   **Installer Script**: `pts-core/external-test-dependencies/scripts/install-haiku-packages.sh` added to use `pkgman install -y`.
-   **Package Mappings**: `pts-core/external-test-dependencies/xml/haiku-packages.xml` added with mappings for:
    -   `build-utilities` -> `gcc make`
    -   `cmake`, `git`, `python`, `perl`, `pcre`, `curl`
    -   `sdl-development` -> `libsdl`
    -   `sdl2-development` -> `libsdl2`
    -   `zlib-development` -> `zlib_devel`
    -   `openssl-development` -> `openssl_devel`

## Required Commands on Haiku
The implementation relies on these standard Haiku commands:
-   `sysinfo`
-   `listdev`
-   `uname`
-   `df`
-   `get_edid`
-   `pkgman`
-   `php` (CLI)

## Next Steps / To-Do
1.  **Run-Time Testing**: Verify the code on a live Haiku system to ensure all regexes match the real-world output formats.
2.  **Sensor Support**: Hardware sensor monitoring is currently not implemented due to lack of a standardized CLI tool.
3.  **Compiler Detection**: Verify `phodevi_system::sw_compiler` correctly identifies Haiku's GCC toolchain.
