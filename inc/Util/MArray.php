<?php

namespace Util;

/**
 *  Some custom multidimensional array filter functions
 */
class MArray
{
	
	/**
	 *	custom filter function for multidimensional array
	 */
	public static function filter_by($array, $key, $operator, $val)
	{
		$filtered_array = array();
		foreach ($array as $id => $elem) 
		{
			if($elem[$key] === null) continue;
			switch($operator)
			{
				case '<':
					if($elem[$key] < $val) $filtered_array[$id] = $elem;
					break;
				case '<=':
					if($elem[$key] <= $val) $filtered_array[$id] = $elem;
					break;
				case '>':
					if($elem[$key] > $val) $filtered_array[$id] = $elem;
					break;
				case '>=':
					if($elem[$key] >= $val) $filtered_array[$id] = $elem;
					break;
				case '=':
				case '==':
					if($elem[$key] == $val) $filtered_array[$id] = $elem;
					break;
				case '!=':
					if($elem[$key] != $val) $filtered_array[$id] = $elem;
					break;
			}
		}
		return $filtered_array;
	}

	/**
	 *  A sorting function for multidimensional array
	 */ 
	public static function order_by($array, $key, $asc = true)
	{
		$asc_val = $asc ? SORT_ASC : SORT_DESC;

		$keys = array_keys($array);
		array_multisort(array_column($array, $key), $asc_val, $array, $keys);

		return array_combine($keys, $array);
	}

	/**
	 *	Count how many elements where key =  value
	 */
	public static function count_by($array, $key, $value)
	{
		$count = 0;
		foreach ($array as $id => $elem) 
		{
			if($elem[$key] == $value) $count++;
		}
		return $count;
	}


}