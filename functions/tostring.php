<?php
    function tostring($array)
    {
        $string = '';
        foreach ($array as $part) $string .= $part;
        return $string;
    }
?>
