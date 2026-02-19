<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class gpu_fanspeed extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'fan-speed';
	const SENSOR_UNIT = 'Percent';

	private $fan_to_monitor = null;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
		{
			$this->fan_to_monitor = $parameter;
		}
		else
		{
			$fans = self::get_supported_devices();
			if($fans != null)
			{
				$this->fan_to_monitor = $fans[0];
			}
		}
	}

	public static function get_supported_devices()
	{
		$supported = null;

		if(phodevi::is_nvidia_graphics())
		{
			$i = 0;
			// Iterate fans until we find one that doesn't exist
			while(true)
			{
				$check_fan = '[fan:' . $i . ']/GPUCurrentFanSpeed';
				if(phodevi_parser::read_nvidia_extension($check_fan) === false)
				{
					break;
				}

				if($supported == null) $supported = array();
				$supported[] = 'fan:' . $i;
				$i++;

				if($i > 16) break; // Safety break
			}
		}

		return $supported;
	}

	public function read_sensor()
	{
		// Report graphics processor fan speed as a percent
		$fan_speed = -1;

		if(phodevi::is_haiku())
		{
			$fan_speed = -1;
		}
		else if(phodevi::is_nvidia_graphics())
		{
			// NVIDIA fan speed reading support in NVIDIA 190.xx and newer
			// nvidia-settings --describe GPUFanTarget 

			$fan_query = '[fan:0]/GPUCurrentFanSpeed';

			if($this->fan_to_monitor != null && strpos($this->fan_to_monitor, 'fan:') === 0)
			{
				$fan_query = '[' . $this->fan_to_monitor . ']/GPUCurrentFanSpeed';
			}

			$fan_speed = phodevi_parser::read_nvidia_extension($fan_query);
		}
		else if($fan1_input = phodevi_linux_parser::read_sysfs_node('/sys/class/drm/card0/device/hwmon/hwmon*/fan1_input', 'POSITIVE_NUMERIC'))
		{
			// AMDGPU path
			$fan_speed = round($fan1_input / phodevi_linux_parser::read_sysfs_node('/sys/class/drm/card0/device/hwmon/hwmon*/fan1_max', 'POSITIVE_NUMERIC') * 100, 2);
		}

		return $fan_speed;
	}

}

?>
