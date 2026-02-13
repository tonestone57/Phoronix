<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel
	phodevi_haiku_parser.php: General parsing functions specific to Haiku

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class phodevi_haiku_parser
{
	public static function read_dmidecode($type, $dmidecode_output = null)
	{
		static $dmidecode_cache = array();

		if($dmidecode_output != null)
		{
			$output = $dmidecode_output;
		}
		elseif(isset($dmidecode_cache[$type]))
		{
			$output = $dmidecode_cache[$type];
		}
		else
		{
			if(($dmidecode = pts_client::executable_in_path('dmidecode')) != false)
			{
				$output = shell_exec($dmidecode . ' -t ' . escapeshellarg($type) . ' 2>&1');
				$dmidecode_cache[$type] = $output;
			}
			else
			{
				$output = '';
			}
		}

		return $output;
	}

	public static function read_sysinfo($info, $sysinfo_output = null)
	{
		static $sysinfo_cache = null;

		if($sysinfo_output != null)
		{
			$sysinfo = $sysinfo_output;
		}
		else
		{
			if($sysinfo_cache == null)
			{
				$sysinfo_cache = shell_exec('sysinfo 2>&1');
			}

			$sysinfo = $sysinfo_cache;
		}

		$return = false;

		switch($info)
		{
			case 'cpu_model':
				// CPU #0: "Intel(R) Core(TM) i7-4790K CPU @ 4.00GHz"
				if(preg_match('/CPU #0: "(.*)"/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'cpu_count':
				$return = substr_count($sysinfo, 'CPU #');
				break;
			case 'mem_size':
				// Memory: 16384 MB
				if(preg_match('/Memory:\s+([0-9]+)\s+MB/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'cpu_features':
				if(preg_match('/Features: (?:0x[0-9a-fA-F]+\.\s*)?(.*)/', $sysinfo, $matches))
				{
					$return = strtolower($matches[1]);
				}
				break;
			case 'system_vendor':
				if(preg_match('/Vendor: (.*)/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'system_product':
				if(preg_match('/Product Name: (.*)/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'system_version':
				if(preg_match('/Version: (.*)/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'system_serial':
				if(preg_match('/Serial Number: (.*)/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'kernel_release':
				if(preg_match('/Kernel: Haiku (.*)/', $sysinfo, $matches))
				{
					$kernel_string = trim($matches[1]);
					// Try to strip potential trailing date in parenthesis e.g. (Oct 21 2023)
					// But keep (hrevXXXXX)
					// If it ends with ) and contains a date-like structure
					if(preg_match('/ \([A-Z][a-z]{2} [0-9]{1,2} .*?\)$/', $kernel_string))
					{
						$kernel_string = preg_replace('/ \([A-Z][a-z]{2} [0-9]{1,2} .*?\)$/', '', $kernel_string);
					}
					$return = $kernel_string;
				}
				break;
			case 'kernel_date':
				if(preg_match('/Kernel: .* \((.*)\)/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				break;
			case 'bios_vendor':
				// Match Vendor under Bios Information section
				if(preg_match('/Bios Information.*?Vendor: ([^\n]*)/s', str_replace("\n  ", "\n", $sysinfo), $matches))
				{
					$return = trim($matches[1]);
				}
				break;
			case 'bios_version':
				// Match Version under Bios Information section
				if(preg_match('/Bios Information.*?Version: ([^\n]*)/s', str_replace("\n  ", "\n", $sysinfo), $matches))
				{
					$return = trim($matches[1]);
				}
				break;
			case 'bios_date':
				// Match Release Date under Bios Information section
				if(preg_match('/Bios Information.*?Release Date: ([^\n]*)/s', str_replace("\n  ", "\n", $sysinfo), $matches))
				{
					$return = trim($matches[1]);
				}
				break;
		}

		return $return;
	}

	public static function read_listdev($listdev_output = null)
	{
		static $listdev_cache = null;

		if($listdev_output != null)
		{
			$listdev = $listdev_output;
		}
		else
		{
			if($listdev_cache == null)
			{
				$listdev_cache = shell_exec('listdev 2>&1');
			}
			$listdev = $listdev_cache;
		}

		$devices = array();

		// Basic parsing of listdev output
		// device Network controller [2|0|0]
		//   vendor 8086: Intel Corporation
		//   device 100e: 82540EM Gigabit Ethernet Controller

		$lines = explode("\n", $listdev);
		$current_device = array();

		foreach($lines as $line)
		{
			$trimmed_line = trim($line);
			if(empty($trimmed_line)) continue;

			// The 'device' line for the class starts at the beginning of the line (no indentation)
			// e.g. "device Network controller..."
			// The inner 'device' line for details is indented
			// e.g. "  device 100e:..."

			if(strpos($line, 'device ') === 0)
			{
				if(!empty($current_device))
				{
					$devices[] = $current_device;
				}
				$current_device = array('class' => substr($trimmed_line, 7));
			}
			else if(strpos($trimmed_line, 'vendor ') === 0)
			{
				$vendor_str = substr($trimmed_line, 7);
				if(($c = strpos($vendor_str, ': ')) !== false)
				{
					$current_device['vendor_id'] = substr($vendor_str, 0, $c);
					$current_device['vendor'] = substr($vendor_str, $c + 2);
				}
				else
				{
					$current_device['vendor'] = $vendor_str;
				}
			}
			else if(strpos($trimmed_line, 'device ') === 0) // inner device line
			{
				$device_str = substr($trimmed_line, 7);
				if(($c = strpos($device_str, ': ')) !== false)
				{
					$current_device['device_id'] = substr($device_str, 0, $c);
					$current_device['device'] = substr($device_str, $c + 2);
				}
				else
				{
					$current_device['device'] = $device_str;
				}
			}
		}
		if(!empty($current_device))
		{
			$devices[] = $current_device;
		}

		return $devices;
	}

	public static function read_os_version()
	{
		// 1. Try sysinfo
		$sysinfo = self::read_sysinfo('kernel_release');
		if(strpos($sysinfo, 'hrev') !== false)
		{
			// Format often like: Haiku R1/beta4 (hrev56578)
			return $sysinfo;
		}

		// 2. Try uname
		$uname_v = php_uname('v');
		if(!empty($uname_v))
		{
			return $uname_v;
		}

		return 'Unknown';
	}

	public static function read_memory_usage($sysinfo_mem = null)
	{
		// Parsing sysinfo -mem to get usage
		// 32768 MB total, 25000 MB used (76%)
		$sysinfo_mem = $sysinfo_mem == null ? shell_exec('sysinfo -mem 2>&1') : $sysinfo_mem;

		if(preg_match('/([0-9]+)\s*MB\s*used/i', $sysinfo_mem, $matches))
		{
			return $matches[1];
		}
		return -1;
	}

	public static function read_cpu_usage($top = null)
	{
		// Use top -n 1
		$top = $top == null ? shell_exec('top -n 1 2>&1') : $top;
		// CPU:  total:  8.3%   user:  1.6%   kernel:  6.7%   idle: 91.7%
		if(preg_match('/total:\s*([0-9\.]+)\s*%/', $top, $matches))
		{
			return $matches[1];
		}
		else if(preg_match('/([0-9\.]+)\s*%\s*idle/i', $top, $matches))
		{
			$idle = $matches[1];
			return 100 - $idle;
		}
		// Fallback to standard Linux top format just in case
		else if(preg_match('/([0-9\.]+)\s*id/i', $top, $matches))
		{
			$idle = $matches[1];
			return 100 - $idle;
		}

		return -1;
	}

	public static function read_swap_usage($top = null)
	{
		// Top output: MiB Swap: 3784.0 total, 3756.0 free, 28.0 used.
		$top = $top == null ? shell_exec('top -n 1 2>&1') : $top;

		if(preg_match('/(MiB|KiB|GiB|MB|KB|GB)?\s*Swap:.*?\s+([0-9\.]+)\s+used/i', $top, $matches))
		{
			$unit = strtoupper($matches[1]);
			$val = $matches[2];

			if($unit == 'GIB' || $unit == 'GB') $val *= 1024;
			else if($unit == 'KIB' || $unit == 'KB') $val /= 1024;

			return round($val);
		}
		else if(preg_match('/Swap:.*?\s+([0-9\.]+)\s+([a-zA-Z]+)\s+used/i', $top, $matches))
		{
			$val = $matches[1];
			$unit = strtoupper($matches[2]);

			if($unit == 'GIB' || $unit == 'GB') $val *= 1024;
			else if($unit == 'KIB' || $unit == 'KB') $val /= 1024;

			return round($val);
		}

		return -1;
	}

	public static function read_disk_info($df_output = null, $mount_output = null)
	{
		// Use df -k to ensure numeric output in KiB
		$df_output = $df_output == null ? shell_exec('df -k 2>&1') : $df_output;
		$filesystems = array();

		// Check for Haiku df format
		// Mount           Type      Total    Used    Free
		if(strpos($df_output, 'Mount') !== false && strpos($df_output, 'Type') !== false)
		{
			$mount_output = $mount_output == null ? shell_exec('mount 2>&1') : $mount_output;
			$mount_map = array();
			// Parse mount output to get device
			// /dev/disk/virtual/virtio_block/0/raw on / type bfs (rw)
			foreach(explode("\n", $mount_output) as $mline)
			{
				if(preg_match('#(.*?) on (.*?) type (.*?) \(#', $mline, $matches))
				{
					$mount_map[$matches[2]] = $matches[1];
				}
			}

			$lines = explode("\n", $df_output);
			foreach($lines as $line)
			{
				$line = trim($line);
				if(empty($line) || strpos($line, 'Mount') === 0 || strpos($line, '-') === 0) continue;

				$parts = preg_split('/\s+/', $line);
				if(count($parts) >= 5)
				{
					$mount_point = $parts[0];
					$device = isset($mount_map[$mount_point]) ? $mount_map[$mount_point] : $mount_point;

					// df -k outputs in KiB, convert to GB/MB string
					$filesystems[] = array(
						'filesystem' => $device,
						'size' => self::byte_format($parts[2] * 1024),
						'used' => self::byte_format($parts[3] * 1024),
						'avail' => self::byte_format($parts[4] * 1024),
						'use_percent' => 'N/A',
						'mount' => $mount_point
					);
				}
			}
		}
		else
		{
			// Parse standard df output
			// Filesystem      Size  Used Avail Use% Mounted on
			// /dev/disk/...   ...   ...  ...   ...  /

			$lines = explode("\n", $df_output);
			array_shift($lines); // Remove header

			foreach($lines as $line)
			{
				$parts = preg_split('/\s+/', trim($line));
				if(count($parts) >= 6)
				{
					// df -k output in KiB
					$filesystems[] = array(
						'filesystem' => $parts[0],
						'size' => self::byte_format($parts[1] * 1024),
						'used' => self::byte_format($parts[2] * 1024),
						'avail' => self::byte_format($parts[3] * 1024),
						'use_percent' => $parts[4],
						'mount' => $parts[5]
					);
				}
			}
		}

		return $filesystems;
	}

	public static function read_battery_status()
	{
		$batteries = array();
		if(is_dir('/dev/power/acpi_battery') && ($b = scandir('/dev/power/acpi_battery')))
		{
			foreach($b as $battery)
			{
				if($battery != '.' && $battery != '..')
				{
					// Try to read content? For now just report ID
					// $content = file_get_contents('/dev/power/acpi_battery/' . $battery);
					if(is_file('/dev/power/acpi_battery/' . $battery . '/model'))
					{
						$model = trim(file_get_contents('/dev/power/acpi_battery/' . $battery . '/model'));
						$info = 'ACPI Battery ' . $battery . ' (' . $model . ')';
					}
					else
					{
						$info = 'ACPI Battery ' . $battery;
					}

					if(is_file('/dev/power/acpi_battery/' . $battery . '/capacity'))
					{
						$capacity = trim(file_get_contents('/dev/power/acpi_battery/' . $battery . '/capacity'));
						if(is_numeric($capacity))
						{
							$info .= ' ' . $capacity . '%';
						}
					}
					$batteries[] = $info;
				}
			}
		}
		return $batteries;
	}

	public static function read_thermal_zone()
	{
		// /dev/power/acpi_thermal/0 ...
		// Output format is binary struct? Or text?
		// Assuming standard driver output might be text or simple value if using cat
		// But for now just return availability
		$temp = -1;
		if(is_dir('/dev/power/acpi_thermal') && ($t = scandir('/dev/power/acpi_thermal')))
		{
			foreach($t as $zone)
			{
				if($zone != '.' && $zone != '..')
				{
					// Check for temperature files in the zone directory
					$files = array('temperature', 'temp', 'thermal_zone/temp');
					foreach($files as $f)
					{
						$path = '/dev/power/acpi_thermal/' . $zone . '/' . $f;
						if(is_file($path))
						{
							$content = trim(file_get_contents($path));
							if(!is_numeric($content) && preg_match('/([0-9]+)/', $content, $m))
							{
								$content = $m[1];
							}

							if(is_numeric($content))
							{
								if($content > 10000)
								{
									// Milli-Celsius
									$content = $content / 1000;
								}
								else if($content > 2000)
								{
									// Deci-Kelvin
									$content = ($content / 10) - 273.15;
								}
								$temp = $content;
								break 2;
							}
						}
					}
				}
			}
		}
		return $temp;
	}

	public static function read_uptime($uptime_output = null)
	{
		// Haiku uptime format: "uptime: 1d 2h 30m 10s" or "uptime: 2h 30m 10s"
		$uptime_counter = 0;
		if($uptime_output == null && ($uptime_cmd = pts_client::executable_in_path('uptime')) != false)
		{
			$uptime_output = shell_exec($uptime_cmd . ' 2>&1');
		}

		if($uptime_output)
		{
			if(preg_match('/uptime:\s+(.*)/', $uptime_output, $matches))
			{
				$parts = explode(' ', $matches[1]);
				foreach($parts as $part)
				{
					$val = intval($part);
					if(strpos($part, 'd') !== false)
					{
						$uptime_counter += $val * 86400;
					}
					elseif(strpos($part, 'h') !== false)
					{
						$uptime_counter += $val * 3600;
					}
					elseif(strpos($part, 'm') !== false)
					{
						$uptime_counter += $val * 60;
					}
					elseif(strpos($part, 's') !== false)
					{
						$uptime_counter += $val;
					}
				}
			}
		}
		return $uptime_counter;
	}

	public static function read_smartctl_info($device_path)
	{
		// Returns array with 'model' and 'serial' if found
		$info = array();
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -i ' . $device_path . ' 2>&1');
			if(preg_match('/Device Model:\s+(.*)/', $output, $matches))
			{
				$info['model'] = trim($matches[1]);
			}
			else if(preg_match('/Model Family:\s+(.*)/', $output, $matches))
			{
				$info['model'] = trim($matches[1]);
			}
			else if(preg_match('/Product:\s+(.*)/', $output, $matches))
			{
				$info['model'] = trim($matches[1]);
			}
			else if(preg_match('/Model Number:\s+(.*)/', $output, $matches))
			{
				$info['model'] = trim($matches[1]);
			}

			if(preg_match('/Serial Number:\s+(.*)/', $output, $matches))
			{
				$info['serial'] = trim($matches[1]);
			}
		}
		return $info;
	}

	public static function read_smartctl_temp($device_path)
	{
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -A ' . $device_path . ' 2>&1');
			// 194 Temperature_Celsius     0x0022   100   100   000    Old_age   Always       -       34
			if(preg_match('/Temperature_Celsius.*\s([0-9]+)$/m', $output, $matches))
			{
				return $matches[1];
			}
			// 190 Airflow_Temperature_Cel 0x0022   066   055   045    Old_age   Always       -       34 (Min/Max 25/45)
			if(preg_match('/Airflow_Temperature_Cel.*\s([0-9]+) \(Min/', $output, $matches))
			{
				return $matches[1];
			}
		}
		return -1;
	}

	public static function read_memory_modules($dmidecode_output = null)
	{
		$modules = array();
		$output = self::read_dmidecode('memory', $dmidecode_output);
		$lines = explode("\n", $output);
		$current_module = array();

		foreach($lines as $line)
		{
			$line = trim($line);
			if(strpos($line, 'Memory Device') === 0)
			{
				if(!empty($current_module))
				{
					$modules[] = $current_module;
				}
				$current_module = array();
			}
			else if(strpos($line, ':') !== false)
			{
				list($key, $value) = explode(':', $line, 2);
				$key = trim($key);
				$value = trim($value);

				switch($key)
				{
					case 'Size':
						$current_module['Size'] = $value;
						break;
					case 'Type':
						$current_module['Type'] = $value;
						break;
					case 'Speed':
						$current_module['Speed'] = $value;
						break;
					case 'Manufacturer':
						$current_module['Manufacturer'] = $value;
						break;
					case 'Serial Number':
						$current_module['Serial'] = $value;
						break;
					case 'Part Number':
						$current_module['Part Number'] = $value;
						break;
				}
			}
		}
		if(!empty($current_module))
		{
			$modules[] = $current_module;
		}
		return $modules;
	}

	public static function read_chassis_info($dmidecode_output = null)
	{
		$info = array();
		$output = self::read_dmidecode('chassis', $dmidecode_output);

		if(preg_match('/Manufacturer: (.*)/', $output, $matches))
		{
			$info['Manufacturer'] = trim($matches[1]);
		}
		if(preg_match('/Type: (.*)/', $output, $matches))
		{
			$info['Type'] = trim($matches[1]);
		}
		if(preg_match('/Serial Number: (.*)/', $output, $matches))
		{
			$info['Serial'] = trim($matches[1]);
		}
		if(preg_match('/Asset Tag: (.*)/', $output, $matches))
		{
			$info['Asset Tag'] = trim($matches[1]);
		}

		return $info;
	}

	public static function read_bios_info($dmidecode_output = null)
	{
		$info = array();
		$output = self::read_dmidecode('bios', $dmidecode_output);

		if(preg_match('/Vendor: (.*)/', $output, $matches))
		{
			$info['Vendor'] = trim($matches[1]);
		}
		if(preg_match('/Version: (.*)/', $output, $matches))
		{
			$info['Version'] = trim($matches[1]);
		}
		if(preg_match('/Release Date: (.*)/', $output, $matches))
		{
			$info['Release Date'] = trim($matches[1]);
		}

		return $info;
	}

	public static function read_processor_info($dmidecode_output = null)
	{
		$info = array();
		$output = self::read_dmidecode('processor', $dmidecode_output);

		if(preg_match('/Family: (.*)/', $output, $matches))
		{
			$info['Family'] = trim($matches[1]);
		}
		if(preg_match('/Manufacturer: (.*)/', $output, $matches))
		{
			$info['Manufacturer'] = trim($matches[1]);
		}
		if(preg_match('/Version: (.*)/', $output, $matches))
		{
			$info['Version'] = trim($matches[1]);
		}
		if(preg_match('/Core Count: (.*)/', $output, $matches))
		{
			$info['Core Count'] = trim($matches[1]);
		}
		if(preg_match('/Thread Count: (.*)/', $output, $matches))
		{
			$info['Thread Count'] = trim($matches[1]);
		}

		return $info;
	}

	public static function read_cache_info($dmidecode_output = null)
	{
		$info = array('L1' => null, 'L2' => null, 'L3' => null);
		$output = self::read_dmidecode('cache', $dmidecode_output);
		$lines = explode("\n", $output);
		$current_level = null;

		foreach($lines as $line)
		{
			if(strpos($line, 'Configuration:') !== false)
			{
				if(strpos($line, 'Level 1') !== false) $current_level = 'L1';
				else if(strpos($line, 'Level 2') !== false) $current_level = 'L2';
				else if(strpos($line, 'Level 3') !== false) $current_level = 'L3';
			}
			else if($current_level && strpos($line, 'Installed Size:') !== false)
			{
				$size = trim(substr($line, strpos($line, ':') + 1));
				$info[$current_level] = $size;
				$current_level = null;
			}
		}

		return $info;
	}

	public static function read_battery_details()
	{
		$info = array();
		if(is_dir('/dev/power/acpi_battery') && ($b = scandir('/dev/power/acpi_battery')))
		{
			foreach($b as $battery)
			{
				if($battery != '.' && $battery != '..')
				{
					$path = '/dev/power/acpi_battery/' . $battery;
					if(is_file($path . '/technology'))
					{
						$info['Technology'] = trim(file_get_contents($path . '/technology'));
					}
					if(is_file($path . '/serial_number'))
					{
						$info['Serial'] = trim(file_get_contents($path . '/serial_number'));
					}
					if(is_file($path . '/voltage'))
					{
						$info['Voltage'] = trim(file_get_contents($path . '/voltage'));
					}
					break; // Just get first battery for now
				}
			}
		}
		return $info;
	}

	public static function read_network_status($ifconfig_output = null)
	{
		$info = array('Speed' => null, 'Duplex' => null);
		// Assuming ifconfig format might contain media line for some drivers
		// media: Ethernet autoselect (1000baseT <full-duplex>)
		$ifconfig_output = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;

		if(preg_match('/media: .*? \((.*?)\)/', $ifconfig_output, $matches))
		{
			$media = $matches[1];
			if(strpos($media, '1000base') !== false) $info['Speed'] = '1000Mbps';
			else if(strpos($media, '100base') !== false) $info['Speed'] = '100Mbps';
			else if(strpos($media, '10base') !== false) $info['Speed'] = '10Mbps';
			else if(strpos($media, '10Gbase') !== false) $info['Speed'] = '10000Mbps';

			if(strpos($media, 'full-duplex') !== false) $info['Duplex'] = 'Full';
			else if(strpos($media, 'half-duplex') !== false) $info['Duplex'] = 'Half';
		}
		return $info;
	}

	public static function read_drive_details($device_path)
	{
		$info = array();
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -i ' . escapeshellarg($device_path) . ' 2>&1');
			if(preg_match('/Firmware Version:\s+(.*)/', $output, $matches))
			{
				$info['Firmware'] = trim($matches[1]);
			}
			if(preg_match('/User Capacity:\s+(.*)/', $output, $matches))
			{
				$info['Capacity'] = trim($matches[1]);
			}
		}
		return $info;
	}

	public static function read_process_count($ps_output = null)
	{
		$ps_output = $ps_output == null ? shell_exec('ps 2>&1') : $ps_output;
		// Subtract header
		return max(0, substr_count($ps_output, "\n") - 1);
	}

	public static function read_thread_count($ps_output = null)
	{
		// Haiku ps lists threads (teams/threads)
		$ps_output = $ps_output == null ? shell_exec('ps 2>&1') : $ps_output;
		return max(0, substr_count($ps_output, "\n") - 1);
	}

	public static function read_load_avg()
	{
		// uptime: 1d 2h 30m 10s, load average: 0.00, 0.00, 0.00
		$uptime = shell_exec('uptime 2>&1');
		if(preg_match('/load average:\s+([0-9\.]+),\s+([0-9\.]+),\s+([0-9\.]+)/', $uptime, $matches))
		{
			return $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
		}
		return null;
	}

	public static function read_timezone()
	{
		return trim(shell_exec('date +%Z 2>&1'));
	}

	public static function read_monitor_details()
	{
		$info = array();
		// Try to parse EDID from syslog or get_edid
		$edid_hex = shell_exec('get_edid 2>&1');
		// Placeholder for EDID parsing logic if hex dump is available
		// For now just basic check
		return $info;
	}

	public static function read_screen_refresh_rate()
	{
		// 1920 1080 32 60
		if(pts_client::executable_in_path('screenmode'))
		{
			$mode = trim(shell_exec('screenmode 2>&1'));
			if(preg_match('/[0-9]+ [0-9]+ [0-9]+ ([0-9\.]+)/', $mode, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_screen_color_depth()
	{
		// 1920 1080 32 60
		if(pts_client::executable_in_path('screenmode'))
		{
			$mode = trim(shell_exec('screenmode 2>&1'));
			if(preg_match('/[0-9]+ [0-9]+ ([0-9]+) [0-9\.]+/', $mode, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_memory_max_capacity($dmidecode_output = null)
	{
		$output = self::read_dmidecode('memory', $dmidecode_output);
		if(preg_match('/Maximum Capacity: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_memory_slots_used($dmidecode_output = null)
	{
		$count = 0;
		$modules = self::read_memory_modules($dmidecode_output);
		foreach($modules as $mod)
		{
			if(isset($mod['Size']) && stripos($mod['Size'], 'No Module') === false)
			{
				$count++;
			}
		}
		return $count;
	}

	public static function read_memory_slots_free($dmidecode_output = null)
	{
		$output = self::read_dmidecode('memory', $dmidecode_output);
		if(preg_match('/Number Of Devices: ([0-9]+)/', $output, $matches))
		{
			$total = intval($matches[1]);
			$used = self::read_memory_slots_used($dmidecode_output);
			return max(0, $total - $used);
		}
		return null;
	}

	public static function read_chassis_height($dmidecode_output = null)
	{
		$output = self::read_dmidecode('chassis', $dmidecode_output);
		if(preg_match('/Height: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_chassis_cords($dmidecode_output = null)
	{
		$output = self::read_dmidecode('chassis', $dmidecode_output);
		if(preg_match('/Number Of Power Cords: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_bios_rom_size($dmidecode_output = null)
	{
		$output = self::read_dmidecode('bios', $dmidecode_output);
		if(preg_match('/ROM Size: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_bios_characteristics($dmidecode_output = null)
	{
		$chars = array();
		$output = self::read_dmidecode('bios', $dmidecode_output);
		$lines = explode("\n", $output);
		$in_section = false;
		foreach($lines as $line)
		{
			if(strpos($line, 'Characteristics:') !== false)
			{
				$in_section = true;
			}
			else if($in_section)
			{
				if(strpos($line, "\t\t") === 0)
				{
					$chars[] = trim($line);
				}
				else
				{
					$in_section = false;
				}
			}
		}
		return $chars;
	}

	public static function read_processor_voltage($dmidecode_output = null)
	{
		$output = self::read_dmidecode('processor', $dmidecode_output);
		if(preg_match('/Voltage: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_processor_status($dmidecode_output = null)
	{
		$output = self::read_dmidecode('processor', $dmidecode_output);
		if(preg_match('/Status: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_processor_upgrade($dmidecode_output = null)
	{
		$output = self::read_dmidecode('processor', $dmidecode_output);
		if(preg_match('/Upgrade: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_cache_associativity($dmidecode_output = null)
	{
		$info = array('L1' => null, 'L2' => null, 'L3' => null);
		$output = self::read_dmidecode('cache', $dmidecode_output);
		$lines = explode("\n", $output);
		$current_level = null;

		foreach($lines as $line)
		{
			if(strpos($line, 'Configuration:') !== false)
			{
				if(strpos($line, 'Level 1') !== false) $current_level = 'L1';
				else if(strpos($line, 'Level 2') !== false) $current_level = 'L2';
				else if(strpos($line, 'Level 3') !== false) $current_level = 'L3';
			}
			else if($current_level && strpos($line, 'Associativity:') !== false)
			{
				$assoc = trim(substr($line, strpos($line, ':') + 1));
				$info[$current_level] = $assoc;
				$current_level = null;
			}
		}
		return $info;
	}

	public static function read_port_connectors($dmidecode_output = null)
	{
		$ports = array();
		$output = self::read_dmidecode('connector', $dmidecode_output);
		$lines = explode("\n", $output);
		$current_port = array();

		foreach($lines as $line)
		{
			if(strpos($line, 'Port Connector Information') === 0)
			{
				if(!empty($current_port)) $ports[] = $current_port;
				$current_port = array();
			}
			else if(strpos($line, 'Internal Reference Designator:') !== false)
			{
				$current_port['Internal'] = trim(substr($line, strpos($line, ':') + 1));
			}
			else if(strpos($line, 'External Reference Designator:') !== false)
			{
				$current_port['External'] = trim(substr($line, strpos($line, ':') + 1));
			}
			else if(strpos($line, 'Port Type:') !== false)
			{
				$current_port['Type'] = trim(substr($line, strpos($line, ':') + 1));
			}
		}
		if(!empty($current_port)) $ports[] = $current_port;
		return $ports;
	}

	public static function read_system_boot_status($dmidecode_output = null)
	{
		$output = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/Boot-up State: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_onboard_devices($dmidecode_output = null)
	{
		$devices = array();
		$output = self::read_dmidecode('baseboard', $dmidecode_output);
		$lines = explode("\n", $output);
		foreach($lines as $line)
		{
			if(preg_match('/On Board Device [0-9]+ Information/', $line))
			{
				// Handle older DMI type 10
			}
			else if(preg_match('/Description: (.*)/', $line, $matches))
			{
				$devices[] = trim($matches[1]); // From Type 41
			}
		}
		return $devices;
	}

	public static function read_oem_strings($dmidecode_output = null)
	{
		$strings = array();
		$output = self::read_dmidecode('11', $dmidecode_output); // Type 11
		$lines = explode("\n", $output);
		foreach($lines as $line)
		{
			if(preg_match('/String [0-9]+: (.*)/', $line, $matches))
			{
				$strings[] = trim($matches[1]);
			}
		}
		return $strings;
	}

	public static function read_system_config_options($dmidecode_output = null)
	{
		$options = array();
		$output = self::read_dmidecode('12', $dmidecode_output); // Type 12
		$lines = explode("\n", $output);
		foreach($lines as $line)
		{
			if(preg_match('/Option [0-9]+: (.*)/', $line, $matches))
			{
				$options[] = trim($matches[1]);
			}
		}
		return $options;
	}

	public static function read_gateway()
	{
		if(($route = pts_client::executable_in_path('route')))
		{
			$out = shell_exec($route . ' 2>&1');
			// default         192.168.1.1     UG    0      0        0  ipro1000/0
			if(preg_match('/default\s+([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_dns_servers()
	{
		$dns = array();
		if(is_readable('/etc/resolv.conf'))
		{
			$lines = file('/etc/resolv.conf');
			foreach($lines as $line)
			{
				if(preg_match('/nameserver\s+([0-9\.]+)/', $line, $matches))
				{
					$dns[] = $matches[1];
				}
			}
		}
		return $dns;
	}

	public static function read_ipv6_address($ifconfig_output = null)
	{
		$ifconfig_output = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match('/inet6 addr: ([0-9a-fA-F:]+)/', $ifconfig_output, $matches))
		{
			return $matches[1];
		}
		return null;
	}

	public static function read_open_ports($netstat_output = null)
	{
		$ports = array();
		$netstat_output = $netstat_output == null ? shell_exec('netstat -an 2>&1') : $netstat_output;
		$lines = explode("\n", $netstat_output);
		foreach($lines as $line)
		{
			// tcp        0      0  0.0.0.0:22            0.0.0.0:*             LISTEN
			if(preg_match('/(tcp|udp).*?:([0-9]+)\s/', $line, $matches))
			{
				$ports[] = $matches[2];
			}
		}
		return array_unique($ports);
	}

	public static function read_packages_count()
	{
		if(is_dir('/boot/system/packages'))
		{
			// Basic count of activated packages
			return count(scandir('/boot/system/packages')) - 2;
		}
		return -1;
	}

	public static function read_kext_loaded()
	{
		$kexts = array();
		if(pts_client::executable_in_path('listimage'))
		{
			$out = shell_exec('listimage 2>&1');
			// Lists images loaded by team. Kernel team is usually 1.
			// Ideally filter for kernel add-ons.
			// Just returning raw list count or parsing could be enough.
			$lines = explode("\n", $out);
			foreach($lines as $line)
			{
				if(strpos($line, '/add-ons/kernel') !== false)
				{
					$parts = explode('/', trim($line));
					$kexts[] = end($parts);
				}
			}
		}
		return array_unique($kexts);
	}

	public static function read_kernel_cmdline()
	{
		// /bin/kernel_args is not standardly dumped to text file
		// sysinfo might have it?
		return null;
	}

	public static function read_boot_time()
	{
		$uptime = self::read_uptime();
		if($uptime > 0)
		{
			return time() - $uptime;
		}
		return null;
	}

	public static function read_filesystem_inodes($mount_point = '/')
	{
		// df -i usually works
		$df = shell_exec('df -i ' . escapeshellarg($mount_point) . ' 2>&1');
		// Filesystem      Inodes  IUsed   IFree IUse% Mounted on
		// /dev/disk/...   ...     ...     ...   ...   /
		$lines = explode("\n", $df);
		foreach($lines as $line)
		{
			if(preg_match('/' . preg_quote($mount_point, '/') . '$/', $line))
			{
				$parts = preg_split('/\s+/', trim($line));
				// Assuming standard df -i column layout if supported
				if(count($parts) >= 5 && is_numeric($parts[1]))
				{
					return array('Total' => $parts[1], 'Used' => $parts[2], 'Free' => $parts[3]);
				}
			}
		}
		return null;
	}

	public static function read_audio_details($listdev_output = null)
	{
		$audio = array();
		$devices = self::read_listdev($listdev_output);
		foreach($devices as $dev)
		{
			if(stripos($dev['class'], 'Multimedia audio controller') !== false || stripos($dev['class'], 'Audio device') !== false)
			{
				$audio[] = $dev;
			}
		}
		return $audio;
	}

	public static function read_input_devices_detailed()
	{
		// /dev/input/keyboard/usb/0, /dev/input/mouse/usb/0
		$inputs = array();
		foreach(array('keyboard', 'mouse', 'touchpad') as $type)
		{
			if(is_dir('/dev/input/' . $type))
			{
				$inputs[$type] = array(); // Populate if deeper scan needed
				// For now just existence
				$inputs[$type] = 'Present';
			}
		}
		return $inputs;
	}

	public static function read_printers()
	{
		$printers = array();
		if(is_dir('/dev/printer/usb'))
		{
			$printers = scandir('/dev/printer/usb');
			$printers = array_diff($printers, array('.', '..'));
		}
		return $printers;
	}

	public static function read_pci_bandwidth($listdev_output = null)
	{
		// Not easily parsing bandwidth from listdev output
		return null;
	}

	public static function read_running_services()
	{
		// querying launch_daemon?
		// launch_roster list
		if(pts_client::executable_in_path('launch_roster'))
		{
			$out = shell_exec('launch_roster list 2>&1');
			// Parse logic here
			// Returns raw string for now
			return $out;
		}
		return null;
	}

	public static function read_zombie_processes()
	{
		// ps output usually doesn't clearly mark zombies in Haiku's standard ps
		return -1;
	}

	public static function read_uptime_since()
	{
		return self::read_boot_time();
	}

	public static function read_users_groups()
	{
		// /etc/passwd, /etc/group
		$users = is_readable('/etc/passwd') ? count(file('/etc/passwd')) : -1;
		$groups = is_readable('/etc/group') ? count(file('/etc/group')) : -1;
		return array('Users' => $users, 'Groups' => $groups);
	}

	public static function read_cmake_version()
	{
		if(($bin = pts_client::executable_in_path('cmake')))
		{
			$out = shell_exec($bin . ' --version 2>&1');
			if(preg_match('/cmake version ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_make_version()
	{
		if(($bin = pts_client::executable_in_path('make')))
		{
			$out = shell_exec($bin . ' --version 2>&1');
			if(preg_match('/GNU Make ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_gdb_version()
	{
		if(($bin = pts_client::executable_in_path('gdb')))
		{
			$out = shell_exec($bin . ' --version 2>&1');
			if(preg_match('/GDB\) ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_clang_version()
	{
		if(($bin = pts_client::executable_in_path('clang')))
		{
			$out = shell_exec($bin . ' --version 2>&1');
			if(preg_match('/clang version ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_node_version()
	{
		if(($bin = pts_client::executable_in_path('node')))
		{
			$out = shell_exec($bin . ' -v 2>&1');
			if(preg_match('/v([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_network_mtu($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match('/mtu ([0-9]+)/', $out, $matches))
		{
			return $matches[1];
		}
		return null;
	}

	public static function read_network_link_status($ifconfig_output = null)
	{
		// ifconfig link status logic
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(stripos($out, 'status: active') !== false) return 'Up';
		if(stripos($out, 'status: no carrier') !== false) return 'Down';
		return null;
	}

	public static function read_wifi_ssid($ifconfig_output = null)
	{
		// scan media for ssid if available
		return null;
	}

	public static function read_disk_rotation_speed($device_path)
	{
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -i ' . escapeshellarg($device_path) . ' 2>&1');
			if(preg_match('/Rotation Rate:\s+([0-9]+)/', $output, $matches))
			{
				return $matches[1] . ' RPM';
			}
			if(strpos($output, 'Solid State Device') !== false)
			{
				return 'SSD';
			}
		}
		return null;
	}

	public static function read_disk_sector_size($device_path)
	{
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -i ' . escapeshellarg($device_path) . ' 2>&1');
			if(preg_match('/Sector Size:\s+(.*)/', $output, $matches))
			{
				return trim($matches[1]);
			}
		}
		return null;
	}

	public static function read_disk_model_family($device_path)
	{
		if(pts_client::executable_in_path('smartctl'))
		{
			$output = shell_exec('smartctl -i ' . escapeshellarg($device_path) . ' 2>&1');
			if(preg_match('/Model Family:\s+(.*)/', $output, $matches))
			{
				return trim($matches[1]);
			}
		}
		return null;
	}

	public static function read_audio_driver($listdev_output = null)
	{
		// From listdev
		$devs = self::read_listdev($listdev_output);
		foreach($devs as $dev)
		{
			if(stripos($dev['class'], 'Multimedia') !== false)
			{
				return isset($dev['device']) ? $dev['device'] : null;
			}
		}
		return null;
	}

	public static function read_webcam_devices()
	{
		$cams = array();
		if(is_dir('/dev/video/usb'))
		{
			$cams = scandir('/dev/video/usb');
			$cams = array_diff($cams, array('.', '..'));
		}
		return $cams;
	}

	public static function read_bluetooth_devices()
	{
		// Check for bluetooth stacks or devs
		return array();
	}

	public static function read_usb_version($listdev_output = null)
	{
		$devs = self::read_listdev($listdev_output);
		foreach($devs as $dev)
		{
			if(stripos($dev['class'], 'USB') !== false)
			{
				if(stripos($dev['device'], 'xHCI') !== false) return '3.0';
				if(stripos($dev['device'], 'EHCI') !== false) return '2.0';
				if(strpos($dev['device'], '3.0') !== false) return '3.0';
				if(strpos($dev['device'], '2.0') !== false) return '2.0';
			}
		}
		return null;
	}

	public static function read_virtualization_platform($dmidecode_output = null)
	{
		$out = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/Product Name: (.*)/', $out, $matches))
		{
			$prod = trim($matches[1]);
			if(stripos($prod, 'QEMU') !== false) return 'QEMU';
			if(stripos($prod, 'VirtualBox') !== false) return 'VirtualBox';
			if(stripos($prod, 'VMware') !== false) return 'VMware';
		}
		return null;
	}

	public static function read_haiku_revision($sysinfo_output = null)
	{
		$rel = self::read_sysinfo('kernel_release', $sysinfo_output);
		if(preg_match('/hrev([0-9]+)/', $rel, $matches))
		{
			return $matches[1];
		}
		return null;
	}

	public static function read_system_language()
	{
		if(pts_client::executable_in_path('locale'))
		{
			return trim(shell_exec('locale -a 2>&1'));
		}
		return null;
	}

	public static function read_cpu_stepping($sysinfo_output = null)
	{
		// sysinfo: CPU #0: ...
		// Stepping not explicitly in standard sysinfo string usually
		return null;
	}

	public static function read_cpu_microcode($sysinfo_output = null)
	{
		return null;
	}

	public static function read_kernel_tainted()
	{
		return '0';
	}

	public static function read_process_pid($name)
	{
		if(($pidof = pts_client::executable_in_path('pidof')))
		{
			return trim(shell_exec($pidof . ' ' . escapeshellarg($name) . ' 2>&1'));
		}
		return null;
	}

	public static function read_process_parent_pid($pid)
	{
		// ps on Haiku...
		return null;
	}

	public static function read_process_state($pid)
	{
		return null;
	}

	public static function read_process_memory_usage($pid)
	{
		return null;
	}

	public static function read_process_cpu_usage($pid)
	{
		return null;
	}

	public static function read_battery_time_remaining()
	{
		// /dev/power/acpi_battery/0/battery_status or similar?
		// No standard file for time remaining
		return null;
	}

	public static function read_battery_charge_rate()
	{
		return null;
	}

	public static function read_opencl_device_count()
	{
		if(pts_client::executable_in_path('clinfo'))
		{
			$out = shell_exec('clinfo 2>&1');
			return substr_count($out, 'Device Name');
		}
		return 0;
	}

	public static function read_vulkan_device_count()
	{
		if(pts_client::executable_in_path('vulkaninfo'))
		{
			$out = shell_exec('vulkaninfo 2>&1');
			// Check for GPU entries
			return substr_count($out, 'GPU id');
		}
		return 0;
	}

	public static function read_pci_vendor_id($listdev_output = null)
	{
		// Extract first PCI vendor
		$devs = self::read_listdev($listdev_output);
		foreach($devs as $dev)
		{
			if(isset($dev['vendor_id'])) return $dev['vendor_id'];
		}
		return null;
	}

	public static function read_pci_device_id($listdev_output = null)
	{
		$devs = self::read_listdev($listdev_output);
		foreach($devs as $dev)
		{
			if(isset($dev['device_id'])) return $dev['device_id'];
		}
		return null;
	}

	public static function read_screen_brightness()
	{
		return null;
	}

	public static function read_system_runlevel()
	{
		return 'Multi-User'; // Haiku is graphical multi-threaded
	}

	public static function read_fs_mount_time($mount_point = '/')
	{
		// stat -c %Y ?
		if(file_exists($mount_point))
		{
			return filectime($mount_point);
		}
		return null;
	}

	public static function read_fan_speed($zone = null)
	{
		// /dev/power/acpi_thermal/ ...
		return -1; // Not generally exposed via simple file yet on Haiku
	}

	public static function read_voltage($component = null)
	{
		return -1; // Not generally exposed
	}

	public static function read_power_consumption()
	{
		return -1;
	}

	public static function read_motherboard_info($dmidecode_output = null)
	{
		$info = array();
		$output = self::read_dmidecode('baseboard', $dmidecode_output);

		if(preg_match('/Manufacturer: (.*)/', $output, $matches)) $info['Manufacturer'] = trim($matches[1]);
		if(preg_match('/Product Name: (.*)/', $output, $matches)) $info['Product'] = trim($matches[1]);
		if(preg_match('/Version: (.*)/', $output, $matches)) $info['Version'] = trim($matches[1]);
		if(preg_match('/Serial Number: (.*)/', $output, $matches)) $info['Serial'] = trim($matches[1]);
		if(preg_match('/Asset Tag: (.*)/', $output, $matches)) $info['Asset Tag'] = trim($matches[1]);

		return $info;
	}

	public static function read_kernel_arch()
	{
		return trim(shell_exec('uname -m 2>&1'));
	}

	public static function read_kernel_build_user()
	{
		// Often in uname -v or sysinfo
		$v = php_uname('v');
		if(preg_match('/(.*) @ (.*)/', $v, $matches))
		{
			return trim($matches[1]); // Build user/host
		}
		return null;
	}

	public static function read_kernel_compiler()
	{
		// e.g. GCC 11.2.0
		$sysinfo = self::read_sysinfo('kernel_release');
		// Not typically in release string, check sysinfo output generally?
		// For now return null or try to guess from gcc -v
		return null;
	}

	public static function read_install_date()
	{
		// Haiku doesn't have a definitive install date file standard like some Linux distros
		// Could check creation time of /boot/system
		if(file_exists('/boot/system'))
		{
			return date('Y-m-d', filectime('/boot/system'));
		}
		return null;
	}

	public static function read_hostname()
	{
		return trim(shell_exec('hostname 2>&1'));
	}

	public static function read_pci_express_slots($dmidecode_output = null)
	{
		$slots = array();
		$output = self::read_dmidecode('slot', $dmidecode_output);
		$lines = explode("\n", $output);
		$current_slot = array();

		foreach($lines as $line)
		{
			$line = trim($line);
			if(strpos($line, 'System Slot Information') === 0)
			{
				if(!empty($current_slot)) $slots[] = $current_slot;
				$current_slot = array();
			}
			else if(strpos($line, 'Designation:') === 0) $current_slot['Designation'] = trim(substr($line, 12));
			else if(strpos($line, 'Type:') === 0) $current_slot['Type'] = trim(substr($line, 5));
			else if(strpos($line, 'Current Usage:') === 0) $current_slot['Usage'] = trim(substr($line, 14));
		}
		if(!empty($current_slot)) $slots[] = $current_slot;
		return $slots;
	}

	public static function read_usb_controllers($listdev_output = null)
	{
		$controllers = array();
		$devices = self::read_listdev($listdev_output);
		foreach($devices as $dev)
		{
			if(stripos($dev['class'], 'USB controller') !== false)
			{
				// Clean up class string if it has [a|b|c] suffix
				if(($p = strpos($dev['class'], ' [')) !== false)
				{
					$dev['class'] = substr($dev['class'], 0, $p);
				}
				$controllers[] = $dev;
			}
		}
		return $controllers;
	}

	public static function read_usb_devices($listdev_output = null)
	{
		// USB devices often show up in listdev under USB class or similar
		// This is a rough approximation as listdev focuses on PCI usually
		// `lsusb` is the better tool if available
		if(pts_client::executable_in_path('lsusb'))
		{
			$lsusb = shell_exec('lsusb 2>&1');
			// Parse lsusb
			// Bus 001 Device 001: ID 1d6b:0002 Linux Foundation 2.0 root hub
			$usb_devs = array();
			foreach(explode("\n", $lsusb) as $line)
			{
				if(preg_match('/ID ([0-9a-fA-F:]+) (.*)/', $line, $matches))
				{
					$usb_devs[] = array('ID' => $matches[1], 'Name' => trim($matches[2]));
				}
			}
			return $usb_devs;
		}
		return array();
	}

	public static function read_i2c_devices()
	{
		// `i2cdetect` equivalent?
		return array();
	}

	public static function read_input_devices()
	{
		// Check /dev/input
		$devices = array();
		if(is_dir('/dev/input'))
		{
			foreach(scandir('/dev/input') as $d)
			{
				if($d != '.' && $d != '..') $devices[] = $d;
			}
		}
		return $devices;
	}

	public static function read_screen_resolution()
	{
		if(pts_client::executable_in_path('screenmode'))
		{
			$mode = trim(shell_exec('screenmode 2>&1'));
			// 1920 1080 32 60
			if(preg_match('/([0-9]+) ([0-9]+) [0-9]+ [0-9\.]+/', $mode, $matches))
			{
				return $matches[1] . 'x' . $matches[2];
			}
		}
		return null;
	}

	public static function read_screen_dimensions()
	{
		return self::read_screen_resolution(); // Same for now
	}

	public static function read_gpu_memory()
	{
		// Difficult to get standardly on Haiku without specific driver info
		return null;
	}

	public static function read_opengl_version()
	{
		// glxinfo not native, but maybe available
		if(pts_client::executable_in_path('glxinfo'))
		{
			$out = shell_exec('glxinfo 2>&1');
			if(preg_match('/OpenGL version string: (.*)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_vulkan_version()
	{
		if(pts_client::executable_in_path('vulkaninfo'))
		{
			$out = shell_exec('vulkaninfo 2>&1');
			if(preg_match('/Vulkan Instance Version: (.*)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_mount_points()
	{
		$mounts = array();
		$out = shell_exec('mount 2>&1');
		// /dev/disk/... on / type bfs (rw)
		foreach(explode("\n", $out) as $line)
		{
			if(preg_match('#(.*?) on (.*?) type (.*?) \((.*?)\)#', $line, $matches))
			{
				$mounts[] = array(
					'Device' => $matches[1],
					'MountPoint' => $matches[2],
					'Type' => $matches[3],
					'Options' => $matches[4]
				);
			}
		}
		return $mounts;
	}

	public static function read_fs_type($mount_point)
	{
		$mounts = self::read_mount_points();
		foreach($mounts as $m)
		{
			if($m['MountPoint'] == $mount_point) return $m['Type'];
		}
		return null;
	}

	public static function read_fs_options($mount_point)
	{
		$mounts = self::read_mount_points();
		foreach($mounts as $m)
		{
			if($m['MountPoint'] == $mount_point) return $m['Options'];
		}
		return null;
	}

	public static function read_swap_devices()
	{
		// Can infer from virtual memory settings or file presence, typically managed by system
		// Not explicitly listed like /proc/swaps
		return array();
	}

	public static function read_open_files_count()
	{
		// lsof equivalent?
		return -1;
	}

	public static function read_process_list()
	{
		$list = array();
		$ps = shell_exec('ps 2>&1');
		$lines = explode("\n", $ps);
		array_shift($lines); // header
		foreach($lines as $line)
		{
			// team  thread  name
			// 123   123     init
			$parts = preg_split('/\s+/', trim($line));
			if(count($parts) >= 3)
			{
				// Last part is name, usually
				$list[] = end($parts);
			}
		}
		return array_unique($list);
	}

	public static function read_process_memory($process_name)
	{
		// Not easily extractable from standard `ps` on Haiku without arguments
		return -1;
	}

	public static function read_process_cpu($process_name)
	{
		return -1;
	}

	public static function read_uptime_seconds()
	{
		return self::read_uptime();
	}

	public static function read_users_logged_in()
	{
		return trim(shell_exec('who | wc -l 2>&1'));
	}

	public static function read_gcc_version()
	{
		if(($gcc = pts_client::executable_in_path('gcc')) != false)
		{
			return trim(shell_exec($gcc . ' -dumpversion 2>&1'));
		}
		return null;
	}

	public static function read_python_version()
	{
		if(($py = pts_client::executable_in_path('python')) != false)
		{
			$out = shell_exec($py . ' --version 2>&1');
			// Python 3.9.1
			return trim(str_replace('Python ', '', $out));
		}
		return null;
	}

	public static function read_perl_version()
	{
		if(($perl = pts_client::executable_in_path('perl')) != false)
		{
			$out = shell_exec($perl . ' -v 2>&1');
			if(preg_match('/v([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_ruby_version()
	{
		if(($ruby = pts_client::executable_in_path('ruby')) != false)
		{
			$out = shell_exec($ruby . ' -v 2>&1');
			if(preg_match('/ruby ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_php_version()
	{
		if(($php = pts_client::executable_in_path('php')) != false)
		{
			$out = shell_exec($php . ' -v 2>&1');
			if(preg_match('/PHP ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_java_version()
	{
		if(($java = pts_client::executable_in_path('java')) != false)
		{
			$out = shell_exec($java . ' -version 2>&1');
			if(preg_match('/"([0-9\._]+)"/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_go_version()
	{
		if(($go = pts_client::executable_in_path('go')) != false)
		{
			$out = shell_exec($go . ' version 2>&1');
			if(preg_match('/go([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_rust_version()
	{
		if(($rust = pts_client::executable_in_path('rustc')) != false)
		{
			$out = shell_exec($rust . ' --version 2>&1');
			if(preg_match('/rustc ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_git_version()
	{
		if(($git = pts_client::executable_in_path('git')) != false)
		{
			$out = shell_exec($git . ' --version 2>&1');
			if(preg_match('/git version ([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_fpc_version()
	{
		if(($fpc = pts_client::executable_in_path('fpc')) != false)
		{
			$out = shell_exec($fpc . ' -iV 2>&1');
			if(preg_match('/([0-9\.]+)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_network_packets_rx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/RX packets:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_packets_tx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/TX packets:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_bytes_rx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/RX bytes:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_bytes_tx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/TX bytes:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_errors_rx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/RX errors:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_errors_tx($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/TX errors:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_collisions($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/collisions:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_network_drops($ifconfig_output = null)
	{
		$out = $ifconfig_output == null ? shell_exec('ifconfig -a 2>&1') : $ifconfig_output;
		if(preg_match_all('/dropped:([0-9]+)/', $out, $matches))
		{
			return array_sum($matches[1]);
		}
		return 0;
	}

	public static function read_system_uuid($dmidecode_output = null)
	{
		$output = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/UUID: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_system_wakeup_type($dmidecode_output = null)
	{
		$output = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/Wake-up Type: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_system_sku($dmidecode_output = null)
	{
		$output = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/SKU Number: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_system_family($dmidecode_output = null)
	{
		$output = self::read_dmidecode('system', $dmidecode_output);
		if(preg_match('/Family: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_baseboard_location($dmidecode_output = null)
	{
		$output = self::read_dmidecode('baseboard', $dmidecode_output);
		if(preg_match('/Location In Chassis: (.*)/', $output, $matches))
		{
			return trim($matches[1]);
		}
		return null;
	}

	public static function read_process_highest_cpu($top_output = null)
	{
		// Haiku top format is unique, simple parse highest based on sort if possible
		// Or stub for now as top parsing needs detailed state tracking
		return null;
	}

	public static function read_process_highest_mem($top_output = null)
	{
		return null;
	}

	public static function read_filesystem_total_size()
	{
		$total = 0;
		$df = self::read_disk_info();
		foreach($df as $d)
		{
			if(isset($d['size']))
			{
				$size = $d['size'];
				// Convert back to bytes for summing? Or just return primary disk size?
				// This is tricky without a converter.
				// Actually read_disk_info returns formatted strings.
				// We need raw data.
			}
		}
		return null; // Requires raw data refactor
	}

	public static function read_filesystem_used_size()
	{
		return null;
	}

	public static function read_filesystem_free_size()
	{
		return null;
	}

	public static function read_keyboard_details()
	{
		$devs = self::read_input_devices_detailed();
		return isset($devs['keyboard']) ? $devs['keyboard'] : null;
	}

	public static function read_mouse_details()
	{
		$devs = self::read_input_devices_detailed();
		return isset($devs['mouse']) ? $devs['mouse'] : null;
	}

	public static function read_joystick_details()
	{
		$devs = array();
		if(is_dir('/dev/input/joystick'))
		{
			foreach(scandir('/dev/input/joystick') as $d)
			{
				if($d != '.' && $d != '..') $devs[] = $d;
			}
		}
		return $devs;
	}

	public static function read_cpu_temp_max()
	{
		return self::read_thermal_zone();
	}

	public static function read_cpu_temp_avg()
	{
		return self::read_thermal_zone();
	}

	public static function read_system_temp_avg()
	{
		return self::read_thermal_zone();
	}

	public static function read_gpu_temp_avg()
	{
		return -1;
	}

	public static function read_user_environment_vars()
	{
		return shell_exec('env 2>&1');
	}

	public static function read_system_environment_vars()
	{
		return shell_exec('printenv 2>&1');
	}

	public static function read_screen_resolution_dual()
	{
		return self::read_screen_resolution();
	}

	public static function read_opengl_renderer()
	{
		if(pts_client::executable_in_path('glxinfo'))
		{
			$out = shell_exec('glxinfo 2>&1');
			if(preg_match('/OpenGL renderer string: (.*)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	public static function read_opengl_vendor()
	{
		if(pts_client::executable_in_path('glxinfo'))
		{
			$out = shell_exec('glxinfo 2>&1');
			if(preg_match('/OpenGL vendor string: (.*)/', $out, $matches))
			{
				return $matches[1];
			}
		}
		return null;
	}

	private static function byte_format($bytes)
	{
		if($bytes > 1073741824)
		{
			return round($bytes / 1073741824, 1) . 'GB';
		}
		elseif($bytes > 1048576)
		{
			return round($bytes / 1048576, 1) . 'MB';
		}
		elseif($bytes > 1024)
		{
			return round($bytes / 1024, 1) . 'KB';
		}

		return $bytes;
	}
}

?>
