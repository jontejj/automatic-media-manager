<?php
    function returnarraykey($array,$search,$start = 0){
    	if($start == -1)
    		return false;
        if($array && is_array($array))
        {
            for($i = $start, $k = count($array); $i < $k; $i++)
            {
                    if(strpos($array[$i],$search) !== false) 
                        return $i;
            }
        }
        return false;
    }
?>