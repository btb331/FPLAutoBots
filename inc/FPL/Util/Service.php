<?php 
/**
 *	Handles making the API calls to FPL servers with the correct secure headers
 */

namespace FPL\Util;

use FPL\Util\ResponseParser as ResponseParser;
use HTTP\Request as Request;

class Service 
{
	private $_default_headers = [
		'Origin: https://fantasy.premierleague.com'
	];

	private $_logged_in = false ;
	private $_pl_profile = '' ;
	private $_csrftoken = '' ;
	private $_sessionid = '' ;


	// immediately log the user in and retreive the log credentials.
	function __construct($login, $password, $csrf) 
	{
		$this->login($login, $password, $csrf);
	}

	// POSTs to the given url with provided data and adds the correct headers
	public function post($url, $data, $headers = [])
	{
		return ResponseParser::format( Request::call("POST", $url, $data, $this->create_headers(array_merge(['Content-Type: application/json; charset=UTF-8'], $headers))) );
	}

	// GETs from the given url
	public function get($url)
	{
		return ResponseParser::format( Request::call("GET", $url, false, $this->create_headers()) );
	}

	// GETs from the given url
	public static function unsigned_get($url)
	{
		return ResponseParser::format( Request::call("GET", $url) );
	}

	// get logged in
	public function is_logged_in ()
	{
		return $this->_logged_in;
	}

	// Logs the user in a retreives all the security data for subsequent calls
	private function login($login, $password, $csrf)
	{
		$url = "https://users.premierleague.com/accounts/login/";

		$data = [
			'csrfmiddlewaretoken' => $csrf,
			'login' => $login,
			'password' => $password,
			'app' => 'plfpl-web',
			'redirect_uri' => 'https://fantasy.premierleague.com/a/login'
		];

		$result = Request::call("POST", $url, $data, self::default_header([
			'Content-Type: application/x-www-form-urlencoded'
		]));

		$parsed_result = ResponseParser::parse_header(ResponseParser::split_header_body($result)['headers']);

		// if there is a success response
		if(preg_match('/success/', $parsed_result['Location']) && array_key_exists('Set-Cookie', $parsed_result)) 
		{
			// Grabs the data for the cookie string
			$this->extract_data_from_cookie_string($parsed_result['Set-Cookie']);

			// must send the retreived data to this url to receieve a sessionid which can be used by other secured
			$success_url = 'https://fantasy.premierleague.com/a/login?state=success';

			$success_result = Request::call("GET", $success_url, false, $this->create_headers());

			$parsed_success_result = ResponseParser::parse_header(ResponseParser::split_header_body($success_result)['headers']);
			// sets the latest session id
			$this->extract_data_from_cookie_string($parsed_success_result['Set-Cookie']);

			$this->_logged_in = true;
		}
	}


	

	// Parse the set cookie response string to extract important data and set the variables
	private function extract_data_from_cookie_string($string)
	{
		foreach ($string as $value) 
		{
			$cookies = explode(';', $value);
			foreach($cookies as $cookie) 
			{
				$cookie = str_replace([' ', '"'], '', $cookie);
				$info = explode('=', $cookie, 2);

				if($info[0] == 'pl_profile' && isset($info[1]))
				{
					$this->_pl_profile = $info[1];
				}
				if($info[0] == 'csrftoken' && isset($info[1])) 
				{	
					$this->_csrftoken = $info[1];
				}
				if($info[0] == 'sessionid' && isset($info[1]))
				{
					$this->_sessionid = $info[1];
				}
			}
		}
	}

	// combine headers
	private function create_headers($headers = [])
	{
		return $this->default_header(array_merge($this->get_cookie_header(), $headers));
	}

	// Get the cookie header to be sent with secured requests
	private function get_cookie_header() 
	{
		return array(
			'Cookie: csrftoken=' . $this->_csrftoken . '; pl_profile="' . $this->_pl_profile . '"; sessionid="' . $this->_sessionid .'"',
			'X-CSRFToken: ' . $this->_csrftoken
		);
	}

	// merge default header with any optional ones
	private function default_header($headers = [])
	{
		return array_merge($this->_default_headers, $headers);
	}
}

?>
