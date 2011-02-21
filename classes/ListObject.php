<?php

class ListObject
{
	var $id;
	var $displayName;
	var $count;
    function __construct($id, $displayName, $count)
    {
        $this->id = $id;
        $this->displayName = $displayName;
        $this->count = $count;
    }
    
    function getDisplayName()
    {
    	return $this->displayName.' ('.$this->count.')';
    }
}

?>