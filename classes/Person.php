<?php
class Person {
        var $id;
        var $name;
        var $bio;
        var $dob;
        var $birthplace;
        var $gender;
        var $photos = array();
        function __construct($name = "",$id = 0)
        {
            $this->name = $name;
            $this->id = $id;
            
            $this->gender = "0";
			$this->dob = "0000-00-00";
			$this->birthplace = "Unknown";
			$this->bio = "";
        }
        function getUrlEncodedName()
        {
        	return urlencode(html_entity_decode($this->name));
        }
        
        function getDisplayName()
        {
        	return $this->name;
        }
}
?>
