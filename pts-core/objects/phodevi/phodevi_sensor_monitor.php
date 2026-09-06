<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2026, Phoronix Media
	Copyright (C) 2008 - 2026, Michael Larabel

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

class phodevi_sensor_monitor
{
	private $sensors_to_monitor;
	private $sensor_storage_dir;

	public function __construct($to_monitor, $recover_dir = false)
	{
		if($recover_dir != false && is_dir($recover_dir) && is_array($to_monitor) && isset($to_monitor[0]) && !($to_monitor[0] instanceof phodevi_sensor))
		{
			$this->sensors_to_monitor = $to_monitor;
			$this->sensor_storage_dir = $recover_dir;
			if(substr($this->sensor_storage_dir, -1) != '/')
			{
				$this->sensor_storage_dir .= '/';
			}
		}
		else
		{
			$this->sensor_storage_dir = is_string($recover_dir) && is_dir($recover_dir) ? $recover_dir : pts_client::create_temporary_directory('sensors');
			if(substr($this->sensor_storage_dir, -1) != '/')
			{
				$this->sensor_storage_dir .= '/';
			}

			$monitor_all = is_array($to_monitor) && in_array('all', $to_monitor);
			$this->sensors_to_monitor = array();

			if(is_array($to_monitor))
			{
				foreach($to_monitor as $sensor)
				{
					if($sensor instanceof phodevi_sensor)
					{
						array_push($this->sensors_to_monitor, $sensor);
						$id = phodevi::sensor_object_identifier($sensor);
						if(!is_file($this->sensor_storage_dir . $id))
						{
							file_put_contents($this->sensor_storage_dir . $id, null);
						}
					}
				}
			}

			if(empty($this->sensors_to_monitor))
			{
				foreach(phodevi::query_sensors() as $sensor)
				{
					if($monitor_all || (is_array($to_monitor) && (in_array(phodevi::sensor_identifier($sensor), $to_monitor) || in_array('all.' . $sensor[0], $to_monitor))))
					{
						array_push($this->sensors_to_monitor, $sensor);
						$id = phodevi::sensor_identifier($sensor);
						if(!is_file($this->sensor_storage_dir . $id))
						{
							file_put_contents($this->sensor_storage_dir . $id, null);
						}
					}
				}
			}
		}
	}
	public function details()
	{
		return array($this->sensors_to_monitor, $this->sensor_storage_dir);
	}
	public function sensors_logging($match = null)
	{
		if($match == null || $match == 'all')
		{
			return $this->sensors_to_monitor;
		}
		else
		{
			$share = array();
			$match = explode(',', $match);
			foreach($this->sensors_to_monitor as $sensor)
			{
				$id = $sensor instanceof phodevi_sensor ? phodevi::sensor_object_identifier($sensor) : phodevi::sensor_identifier($sensor);
				$type = $sensor instanceof phodevi_sensor ? $sensor->get_type() : $sensor[0];
				if(in_array($id, $match) || in_array('all.' . $type, $match))
				{
					array_push($share, $sensor);
				}
			}

			return $share;
		}
	}
	public function sensor_logging_start($interval = 1)
	{
		if(is_file($this->sensor_storage_dir . 'STOP'))
		{
			unlink($this->sensor_storage_dir . 'STOP');
		}
		$this->sensor_logging_update();
		pts_client::timed_function(array($this, 'sensor_logging_update'), array(), $interval, array($this, 'sensor_logging_continue'), array());
	}
	public function sensor_logging_stop()
	{
		file_put_contents($this->sensor_storage_dir . 'STOP', 'STOP');
	}
	public function cleanup()
	{
		pts_file_io::delete($this->sensor_storage_dir, null, true);
	}
	public function sensor_logging_continue()
	{
		return !is_file($this->sensor_storage_dir . 'STOP');
	}
	public function sensor_logging_update()
	{
		if(!$this->sensor_logging_continue())
		{
			return false;
		}

		foreach($this->sensors_to_monitor as $sensor)
		{
			$sensor_value = phodevi::read_sensor($sensor);
			$id = $sensor instanceof phodevi_sensor ? phodevi::sensor_object_identifier($sensor) : phodevi::sensor_identifier($sensor);

			if($sensor_value != -1 && is_file($this->sensor_storage_dir . $id))
			{
				file_put_contents($this->sensor_storage_dir . $id, $sensor_value . PHP_EOL, FILE_APPEND);
			}
		}
	}
	public function read_sensor_data($sensor, $offset = 0)
	{
		if($sensor instanceof phodevi_sensor)
		{
			$id = phodevi::sensor_object_identifier($sensor);
		}
		else if(is_array($sensor))
		{
			$id = phodevi::sensor_identifier($sensor);
		}
		else
		{
			$id = $sensor;
		}

		$log_file = $this->sensor_storage_dir . $id;
		if(!is_file($log_file))
		{
			return array();
		}

		$log_f = file_get_contents($log_file);
		$lines = explode(PHP_EOL, $log_f);

		if($offset != 0)
		{
			$lines = array_slice($lines, $offset);
		}

		foreach($lines as $i => $line)
		{
			if(!is_numeric($line) || $line < 0)
			{
				unset($lines[$i]);
			}
		}

		return array_values($lines);
	}
	public function read_sensor_results($sensor, $offset = 0)
	{
		$results = $this->read_sensor_data($sensor, $offset);

		if(empty($results))
		{
			return false;
		}

		if($sensor instanceof phodevi_sensor)
		{
			$id = phodevi::sensor_object_identifier($sensor);
			$name = phodevi::sensor_object_name($sensor);
			$unit = phodevi::read_sensor_object_unit($sensor);
		}
		else if(is_array($sensor))
		{
			$id = phodevi::sensor_identifier($sensor);
			$name = phodevi::sensor_name($sensor);
			$unit = phodevi::read_sensor_unit($sensor);
		}
		else
		{
			$id = $sensor;
			$name = $sensor;
			$unit = '';
		}

		return array('id' => $id, 'name' => $name, 'results' => $results, 'unit' => $unit);
	}
}

?>