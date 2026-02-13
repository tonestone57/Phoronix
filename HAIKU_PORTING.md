# Porting Phoronix Test Suite to Haiku OS

This document outlines the status and implementation details of the Phoronix Test Suite (PTS) port to Haiku OS.

## Status: Advanced Hardware Detection & Dependencies Implemented

Hardware detection has been significantly enhanced, including GPU resolution/modes via EDID, network interface details, and better sensor parsing. External dependencies have been expanded.

## Implementation Details

### 1. Core Modifications
-   **`pts-core/objects/pts_types.php`**: Added 'Haiku' to supported operating systems.
-   **`pts-core/objects/phodevi/phodevi.php`**:
    -   Added 'haiku' to operating systems array.
    -   Implemented `is_haiku()` detection method.
    -   Updated `initial_setup()` to set the haiku flag when `php_uname('s')` returns "Haiku".

### 2. Hardware Detection (`phodevi_haiku_parser`)
-   **System**: Kernel release, build date, architecture (BePC mapped to i686), OS version, Filesystem type (via `mount`).
-   **CPU**: Model, core count, feature flags (parsed from `sysinfo`), Usage (improved `top` parsing).
-   **Memory**: Total physical memory (fallback to `sysinfo`), Swap usage (via `top` with unit support).
-   **Motherboard/BIOS**: Vendor, product, version, serial, BIOS date/vendor.
-   **PCI/USB**: Enumeration via `listdev`.
-   **GPU**: Model detection via `listdev`, PCI device ID, Resolution and Available Modes (via `get_edid` or `/var/log/syslog` parsing).
-   **Disk**: Capacity, filesystem, model name refinement (e.g. virtio_block), Block size (via `stat`), `smartctl` Model Family fallback.
-   **Network**: Interface detection via `listdev`, Active interface via `route`, MAC address (improved `ifconfig` parsing).
-   **Audio**: Device string extraction via `listdev`.
-   **Battery**: Presence check via `/dev/power/acpi_battery`, Capacity percentage parsing, Discharge state detection.
-   **Monitor**: EDID extraction via `get_edid` or `/var/log/syslog` for model, count, and modes.
-   **Sensors**: Thermal zones (text parsing support added).

### 3. External Dependencies
-   **Installer Script**: `pts-core/external-test-dependencies/scripts/install-haiku-packages.sh` added to use `pkgman install -y`.
-   **Package Mappings**: `pts-core/external-test-dependencies/xml/haiku-packages.xml` extended.
    -   Build Tools: `gcc`, `make`, `autoconf`, `automake`, `libtool`, `pkg-config`, `yasm`, `nasm`, `ninja`, `meson`, `llvm`, `clang`.
    -   VCS: `git`, `mercurial`, `subversion`.
    -   Libraries: `libsdl`, `libsdl2`, `zlib_devel`, `openssl_devel`, `boost_devel`, `ncurses_devel`, `libxml2_devel`, `freetype_devel`, `fontconfig_devel`, `libpng16_devel`, `libjpeg_turbo_devel`, `gtk+_devel`, `imlib2_devel`, `portaudio_devel`, `glew_devel`, `freeimage_devel`, `eigen_devel`, `libaio_devel`, `openal_devel`, `libvorbis_devel`, `qt5_devel`, `libevent_devel`, `popt_devel`, `fftw_devel`, `openblas_devel`, `lapack_devel`, `openmpi_devel`, `bzip2_devel`, `gmp_devel`, `tinyxml_devel`, `attr_devel`, `opencv_devel`, `expat_devel`, `hdf5_devel`, `libconfig_devel`, `libuuid_devel`, `gflags_devel`, `benchmark_devel`, `snappy_devel`, `opencl_headers`.
    -   Languages: `openjdk17`, `gfortran`, `tcl`, `rust`, `go`, `nodejs`.
    -   Utils: `dmidecode`, `mesa_demos`, `vulkan_tools`, `bc`, `p7zip`, `smartmontools`, `scons`, `redis`.

## Required Commands on Haiku
The implementation relies on these standard Haiku commands:
-   `sysinfo`
-   `listdev`
-   `uname`
-   `df`
-   `mount`
-   `top`
-   `route`
-   `ifconfig`
-   `get_edid`
-   `stat` (GNU or BSD syntax supported)
-   `pkgman`
-   `php` (CLI)

## Next Steps / To-Do
1.  **Run-Time Testing**: Verify the code on a live Haiku system to ensure all regexes match the real-world output formats.
2.  **Extended Sensor Support**: Investigate if more sensors can be exposed via `listdev` or other system commands.

## Completed Verification
-   **Compiler Detection**: `phodevi_system::sw_compiler` relies on standard `gcc` detection which is supported on Haiku.
-   **Sensor Support**: Thermal zones (with dK/mC detection) and battery status are supported via `/dev/power/`.
