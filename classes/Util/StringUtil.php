<?php
class StringUtil
{
	//Throws away non-numeric characters
	//Offset: find the nth number in the string
	public static function scanint($string, $concat = true, $offset = 0) 
	{
	    $length = strlen($string);   
	    $int = '';
	    $i = 0;
	    for ($concat_flag = true; $i < $length; $i++) 
	    {
	        if (is_numeric($string[$i]) && $concat_flag) 
	        {
	            $int .= $string[$i];
	        } 
	        elseif(!$concat && $concat_flag && strlen($int) > 0) 
	            break; 
	    }
	    if($int == '')
	    	return false;
	    else if($offset == 0)
	   		return (int)$int;
	   	else 
	   	{
	   		$offset--;
	   		return StringUtil::scanint(substr($string,$i),$concat,$offset);
	   	}
	}
	
	//Scans for a integer (strictly
    public static function scanint2($string) 
    {
    	$length = strlen($string);   
    	$int = '';
    	for ($i = 0; $i < $length; $i++)
    	{
    		if(is_numeric($string[$i]))
    			$int .= $string[$i];
    		else 
    			break;
    	}
    	if($int == '')
    		return false;
    	else
    		return $int;
    } 
	public static function backwardStrpos($haystack, $needle, $offset = 0)
	{
	    $length = strlen($haystack);
	    $offset = ($offset > 0)?($length - $offset):abs($offset);
	    $pos = strpos(strrev($haystack), strrev($needle), $offset);
	    return ($pos === false)?false:( $length - $pos - strlen($needle) );
	}
	
	public static function escapeSingleQuotes($string)
	{
		return str_replace("'", "\'", $string);
	}
}