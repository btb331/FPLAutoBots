<?php

namespace HTTP;
/**
 *  For making API calls
 */
class Request
{
    /**
     *  $method: 'POST', 'GET', 'PUT' or 'DELETE'
     *  $url: (string) where to call to
     *  $data: (array or string) to be sent with POST or PUT requests
     *  $custom_headers: (array) headers sent with request
     */ 
    public static function call($method, $url, $data = false, $custom_headers = [])
    {
        $curl = curl_init();

        $data_string = $data ? (is_array($data) ? http_build_query($data) : $data) : false;

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, $data_string);
        }

        $headers = $method == 'GET' ? array() : array(
            'Content-Length: ' . strlen($data_string),
        );

        $sent_headers = array_merge($headers, $custom_headers);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CAINFO, "/home2/btb331/public_html/fpl/fpl-autobot/" . 'test.pem');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $sent_headers);
    
        $result = curl_exec($curl);
    
            
        curl_close($curl);
        
        return $result;
    }
}