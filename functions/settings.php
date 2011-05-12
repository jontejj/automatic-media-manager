<?php

function configureSettings()
{
	global $dbh,$cfg;
	error_reporting(E_ERROR);
	set_time_limit(0);/* For data processing tasks that takes a lot of time */
	date_default_timezone_set('Europe/Stockholm');

	Logger::enableEcho();

	if($_SERVER['REMOTE_ADDR'] == '127.0.0.1')
	{
		$_SERVER['SERVER_ADDR'] = "127.0.0.1";
		error_reporting(E_ALL);  
		if(function_exists("apache_setenv") && function_exists("ini_set"))
		{
			//ini_set('default_socket_timeout', 15);
			ini_set('memory_limit', "1024M");
			
			/* Disables flushing for a local computer and results in echo's and print's being sent to the browser directly */
			/*apache_setenv('no-gzip', 1);
			ini_set('zlib.output_compression', 0);
			ini_set('implicit_flush', 1);
			for ($i = 0; $i < ob_get_level(); $i++) 
				ob_end_flush(); 
			ob_implicit_flush(1);
			*/
			
		}
	}
	$_ENV['pathToXbmc2web'] = $_SERVER['SERVER_ADDR'].substr($_SERVER['SCRIPT_NAME'],0,-9);
	if(substr($_ENV['pathToXbmc2web'],0,7) != "http://")
		$_ENV['pathToXbmc2web'] = "http://".$_ENV['pathToXbmc2web'];
}

?>