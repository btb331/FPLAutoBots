<?php 

namespace Util;

/**
 *	Handles interactions with external files
 */
class File
{

	public static function write_json_to($filename, $data)
	{
		self::write_to($filename, json_encode($data));
	}

	public static function read_json_from($filename)
	{
		return json_decode(self::read_from($filename), true);
	}


	// write data to filename
	public static function write_to($filename, $data)
	{
		$text = fopen("/home2/btb331/public_html/fpl/fpl-autobot/" . $filename, "w") or die("Unable to open file!");
		fwrite($text, $data);
		fclose($text);
	}

	// return data from file with filename
	public static function read_from($filename)
	{
		$txt_data = fopen("/home2/btb331/public_html/fpl/fpl-autobot/" . $filename, "r") or die("Unable to open file!");
		$data = fread($txt_data, filesize("/home2/btb331/public_html/fpl/fpl-autobot/" . $filename));
		fclose($txt_data);
		return $data;
	}

	// get last accessed file date
	public static function get_modify_date($filename)
	{
		if(file_exists("/home2/btb331/public_html/fpl/fpl-autobot/" .  $filename))
		{
			return filemtime("/home2/btb331/public_html/fpl/fpl-autobot/" . $filename);
		}

		return false;
	}
}
