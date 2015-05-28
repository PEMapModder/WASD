<?php

/**
 * WASD
 * Copyright (C) 2015 PEMapModder
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace WASD\functions;

class Lang{
	/** @var string[] */
	private $t = [];
	public function __construct($name){
		if(is_file($path = \WASD_RESOURCES_PATH . "lang/$name.ini")){
			$lines = array_filter(array_map("trim", explode("\n", file_get_contents($path))), function($line){
				return is_string($line) and strlen($line) > 0 and substr($line, 0, 1) !== "#";
			});
			foreach($lines as $line){
				if(($pos = strpos($line, "=")) !== false){
					$this->t[rtrim(substr($line, 0, $pos))] = ltrim(substr($line, $pos + 1));
				}else{
					trigger_error("Delimiter \"=\" not found on line \"$line\" of $path, skipping.", E_USER_WARNING);
				}
			}
		}
	}
	public function get($key){
		return isset($this->t[$key]) ? $this->t[$key] : null;
	}
}
