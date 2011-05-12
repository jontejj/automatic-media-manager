<?php
class FileRetrieve
{
	
	//Uses FileRetrieve's improved file and the logger to write to the destination path
	//Returns true on success
	public static function copy($from,$to)
	{
		$string = FileRetrieve::file($from,'Copying file',0,true);
		if($string !== false)
		{
			return Logger::setFileToString($string,$to);
		}
		else
		{
			Logger::unreachableFile('Could not copy from: '.$from.' to: '.$to);
			return false;
		}
	}
	
	public static function file($path,$fetchCode = '',$nrOfTries = 0,$toString = false)
    {
    	$result = false;
    	if($path == '')
    	{
    		Logger::unreachableFile('Code: '.$fetchCode);
    		return $result;
    	}
        else if($toString)
        	$result = file_get_contents($path);
        else
        	$result = file($path);
        	
        //PHP file fetches several pages if the location header is defined, so lets check the last statuscode
        $statusCode = 0;
        $location = "";
        if(isset($http_response_header) && is_array($http_response_header))
        {
        	foreach($http_response_header as $header)
        	{
        		if(substr($header,0,5) == 'HTTP/')
        		{
					$firstSpace = strpos($header,' ');
	        		$statusCode = substr($header,$firstSpace+1,3);	
        		}
        	    else if(substr($header,0,10) == 'Location: ')
        		{
	        		$location = substr($header,10);	
        		}
        	}
        }
        
        //OK
         if($statusCode == 200)
         {
         	Logger::fileFetched($path);
        	return $result;
         }
        else if($nrOfTries == 5)
        {       		
        	Logger::echoText("The link: ".$path." timed out ".$nrOfTries." times. Sleeping for 20 seconds.".PHP_EOL);
        	sleep(20);
        	return FileRetrieve::file($path,$fetchCode,6,$toString);
        }
        else if($nrOfTries > 5)
        {
        	Logger::echoText("The link: ".$path." timed out ".$nrOfTries." times. returning empty string.".PHP_EOL);
        	Logger::unreachableFile($path.' code: '.$fetchCode);
        	return $result;
        }
        		//Temporary moved
        else if($statusCode == 302)
        {
       		Logger::httpRequestFailed("Temporary moved: (".$path.") to (".$location."), result: ".$result);
       		$nrOfTries++;
       		//Try the new location
       		return FileRetrieve::file($location,$fetchCode,$nrOfTries,$toString);
        }
        		//Not found
        else if($statusCode == 404)
        {
        	Logger::unreachableFile($path.' code: '.$fetchCode);
        	return $result;
        }
        		//unavailable		  //timeout	            //Bad Gateway		  //No response		  //Internal Server Error
        else if($statusCode == 408 || $statusCode == 503 || $statusCode == 502 || $statusCode == 0 || $statusCode == 500 || $result === false)
        {
        	//lets retry 5 times
        	if($result === false)
        		Logger::echoText("Connection for path: ".$path." did not receive any answer. Trying again in 5 sec.".PHP_EOL);
        	else
        		Logger::echoText("The link: ".$path." was unavailable. Trying again in 5 sec.".PHP_EOL);
        	sleep(5);
        	$nrOfTries++;
        	return FileRetrieve::file($path,$fetchCode,$nrOfTries,$toString);
        }
        //Unhandled statuscode
        else
        {
        	$errorString = $path.', Statuscode: '.$statusCode.', Result: '.$result;
        	Logger::unreachableFile($errorString);
        }
        return $result;
     }
     
     public static function postAndRetrieve($url,$postArray,$cookieFile)
     {
     	global $cfg;
     	$absoluteCookiePath = realpath(getcwd()."/".$cookieFile);
     	
     	$postData = "";
     	foreach($postArray as $key=>$value) 
     		$postData .= urlencode($key).'='.urlencode($value).'&';	
     	rtrim($postData,'&');

     	$ch = curl_init();
     	
     	//set the url, number of POST vars, POST data, and wait for an answer
     	curl_setopt($ch, CURLOPT_URL,				$url);
     	curl_setopt($ch, CURLOPT_POST,				count($postArray));
     	curl_setopt($ch, CURLOPT_POSTFIELDS,		$postData);
		curl_setopt($ch, CURLOPT_USERAGENT, 		$cfg['userAgent']);
		curl_setopt($ch, CURLOPT_COOKIEFILE, 		$absoluteCookiePath);		//Read cookies from this file
		curl_setopt($ch, CURLOPT_COOKIEJAR, 		$absoluteCookiePath);		//Write cookies to this file
     	curl_setopt($ch, CURLOPT_RETURNTRANSFER,	true);
     	
     	$result = curl_exec($ch);
     	
     	//Makes sure that we save the cookies to file
     	curl_close($ch);
     	Logger::fileFetched($url);
     	
     	return $result;
     }
     
     public static function getPageByCurl($url,$cookieFile)
     {
     	global $cfg;
     	
     	$absoluteCookiePath = realpath(getcwd()."/".$cookieFile);
     	
     	$ch = curl_init($url);
     	
		curl_setopt($ch, CURLOPT_USERAGENT, 		$cfg['userAgent']);
		curl_setopt($ch, CURLOPT_COOKIEFILE, 		$absoluteCookiePath); 		//Read cookies from this file
		curl_setopt($ch, CURLOPT_COOKIEJAR, 		$absoluteCookiePath); 		//Write cookies to this file
     	curl_setopt($ch, CURLOPT_RETURNTRANSFER,	true);
     	
     	$result = curl_exec($ch);
     	
     	curl_close($ch);
     	Logger::fileFetched($url);
     	
		return $result;
     }
     
     public static function validRememberMeCookie($cookieFile)
     {
     	$cookies = file($cookieFile);
     	foreach($cookies as $cookie)
     	{
     		if(strpos($cookie, "tlpass=") !== false && strlen($cookie) > 20)
     			return true;
     	}
    	return false;
     }
}