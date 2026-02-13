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
				$output = shell_exec($dmidecode . ' -t ' . $type . ' 2>&1');
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
				if(preg_match('/Kernel: Haiku (.*?) \(/', $sysinfo, $matches))
				{
					$return = $matches[1];
				}
				else if(preg_match('/Kernel: Haiku (.*)/', $sysinfo, $matches))
				{
					$return = trim($matches[1]);
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
