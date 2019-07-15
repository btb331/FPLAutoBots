<?php

namespace FPL\Util;

class ResponseParser
{
	// Returns a response in a consistent readable format
	public static function format($response)
	{
		if(!is_array($response))
		{
			$response = self::split_header_body($response);
		}

		$header = self::parse_header($response['headers']);
		// code and message come from the first line of header
		list(,$c,$m) = explode(' ', $header[0], 3);

		return array(
			'code' => $c, 
			'message' => $m, 
			'data' => $response['body']
		);
	}


	// Parses a the headers of a response
	public static function parse_header($response)
	{
		$lines = explode("\r\n", $response);
		$lines = array_filter($lines);

		$headers = [];

		foreach($lines as $line) 
		{
			$split_lines = explode(':', $line, 2);
			if(count($split_lines) != 2) 
			{
				array_push($headers, $split_lines[0]);
			}
			else
			{
				list($k, $v) = $split_lines;
			
				if(array_key_exists($k, $headers))
				{
					if(is_array($headers[$k]))
					{
						array_push($headers[$k], $v);
					} 
					else 
					{
						$headers[$k] = array($headers[$k], $v);
					}
				}
				else 
				{
					$headers[$k] = $v;
				}
			}
		}

		return $headers;
	}

	// Splits into header and body of the response
	public static function split_header_body($response)
	{
		$split_result = preg_split("/\R\R/", $response, 2);

        // Some annoying response headers give back a "100 Continue" message before the main header
        if(strpos($split_result[0], 'Continue') !== false)
        {
            $split_result = preg_split("/\R\R/", $split_result[1], 2);
        }

        return array(
            "headers" => $split_result[0], 
            "body"=> $split_result[1]
        );
	}
}