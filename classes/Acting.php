<?php
class Acting extends Person{
        var $role;
        var $production;
        function __construct($name = "",$role = "",$id = 0)
        {
        	parent::__construct($name,$id);
        	
            if(!is_array($role))
                $this->role = $role;
        }
}
?>
