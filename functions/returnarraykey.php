<?php
    function returnarraykey($array,$search,$start = 0){
    	if($start == -1)
    		return false;
        if($array && is_array($array))
        {
            if($start != 0)
            {
                foreach($array as $linenum => $line) 
                    if(strpos($line,$search) !== false && $linenum > $start) 
                        return $linenum;    
            }
            else 
            {
	            foreach($array as $linenum => $line) 
	            	if(strpos($line,$search) !== false) 
	            		return $linenum;
            }
        }
        return false;
    }
?>