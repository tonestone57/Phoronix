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
	public static function read_sysinfo($arg)
	{
		// TODO: Implement parsing of sysinfo command
		// sysinfo -cpu
		// sysinfo -mem
		// etc.
		return null;
	}

	public static function read_listdev()
	{
		// TODO: Implement parsing of listdev command for PCI/USB devices
		return null;
	}

	public static function read_disk_info()
	{
		// TODO: Implement parsing of disk info (df, mount, or other commands)
		return null;
	}
}

?>
