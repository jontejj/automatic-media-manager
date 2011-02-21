<?php
    function toarray($var)
    {
        if(isset($var[0]))
        {
            if(!is_array($var[0]))
            {
                $new[0] = $var;
                return $new;
            }  
            else return $var; 
        }
        else 
        {
            $new[0] = $var;
            return $new;
        }
    }
?>
