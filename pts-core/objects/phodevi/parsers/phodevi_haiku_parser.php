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
				if(preg_match('/Features: (.*)/', $sysinfo, $matches))
				{
					$return = $matches[1];
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
				if(preg_match('/Kernel: Haiku (.*) \(/', $sysinfo, $matches))
				{
					$return = $matches[1];
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
		// Hardware sensor monitoring is not yet supported on Haiku as there isn't a standardized command-line interface for it yet.

		$listdev = $listdev_output == null ? shell_exec('listdev 2>&1') : $listdev_output;
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

	public static function read_memory_usage()
	{
		// Parsing sysinfo -mem to get usage
		// 32768 MB total, 25000 MB used (76%)
		$sysinfo_mem = shell_exec('sysinfo -mem 2>&1');
		if(preg_match('/([0-9]+) MB used/', $sysinfo_mem, $matches))
		{
			return $matches[1];
		}
		return -1;
	}

	public static function read_cpu_usage()
	{
		// Use top -n 1
		// Try parsing Haiku top output, which might differ from Linux
		// " 2.8% cpu" or similar?
		// Actually typical Haiku top output shows:
		// Load average: ...
		// ...
		// 91.7% idle
		$top = shell_exec('top -n 1 2>&1');
		if(preg_match('/([0-9\.]+)% idle/', $top, $matches))
		{
			$idle = $matches[1];
			return 100 - $idle;
		}
		// Fallback to standard Linux top format just in case
		else if(preg_match('/([0-9\.]+) id/', $top, $matches))
		{
			$idle = $matches[1];
			return 100 - $idle;
		}

		return -1;
	}

	public static function read_swap_usage()
	{
		// Try to find swap usage
		// Currently vm_stat or sysinfo don't cleanly expose used swap in MB
		// But let's check vm_stat output format again or try top
		// Top output: MiB Swap: 3784.0 total, 3756.0 free, 28.0 used.
		$top = shell_exec('top -n 1 2>&1');
		if(preg_match('/Swap:\s+([0-9\.]+)\s+total,\s+([0-9\.]+)\s+free,\s+([0-9\.]+)\s+used/', $top, $matches))
		{
			return $matches[3]; // Used
		}
		// Haiku might report it differently in some versions or depending on top implementation
		// Try sysinfo again if we can find swap there (unlikely based on previous checks but good to note)

		return -1;
	}

	public static function read_disk_info($df_output = null)
	{
		// Use df
		$df_output = $df_output == null ? shell_exec('df -h 2>&1') : $df_output;
		$filesystems = array();

		// Parse df output
		// Filesystem      Size  Used Avail Use% Mounted on
		// /dev/disk/...   ...   ...  ...   ...  /

		$lines = explode("\n", $df_output);
		array_shift($lines); // Remove header

		foreach($lines as $line)
		{
			$parts = preg_split('/\s+/', trim($line));
			if(count($parts) >= 6)
			{
				$filesystems[] = array(
					'filesystem' => $parts[0],
					'size' => $parts[1],
					'used' => $parts[2],
					'avail' => $parts[3],
					'use_percent' => $parts[4],
					'mount' => $parts[5]
				);
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
						$batteries[] = 'ACPI Battery ' . $battery . ' (' . $model . ')';
					}
					else
					{
						$batteries[] = 'ACPI Battery ' . $battery;
					}
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

	public static function read_uptime()
	{
		// Haiku uptime format: "uptime: 1d 2h 30m 10s" or "uptime: 2h 30m 10s"
		$uptime_counter = 0;
		if(($uptime_cmd = pts_client::executable_in_path('uptime')) != false)
		{
			$uptime_output = shell_exec($uptime_cmd . ' 2>&1');
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
}

?>
