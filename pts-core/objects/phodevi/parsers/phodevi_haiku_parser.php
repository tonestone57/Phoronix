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
		$sysinfo = $sysinfo_output == null ? shell_exec('sysinfo 2>&1') : $sysinfo_output;
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
}

?>
