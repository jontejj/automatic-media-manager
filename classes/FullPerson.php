<?php 
class FullPerson extends Person
{
        var $personPhoto = array();
        
        var $acting = array();
        var $directing = array();
        var $writing = array();
        
        var $credited = array();
        
        function __construct($name = "",$id = 0)
        {
        	parent::__construct($name,$id);
        }
        
        function getActingRoleForProduction($productionId)
        {
        	foreach($this->acting as $index => $actingObject)
        	{
        		if(is_numeric($actingObject->production))
        			if($actingObject->production == $productionId)
        				return $actingObject->role;	
        		else if(is_a($actingObject->production,"Production"))
        			if($actingObject->production->id == $productionId)
        				return $actingObject->role;	
        	}
        }
}

?>