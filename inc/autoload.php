<?php

spl_autoload_register(function($className) 
{
	$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	include_once dirname(__FILE__) . '/' . $className . '.php';
});
