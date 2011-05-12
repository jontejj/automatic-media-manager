<?php
class Country {
        var $id;
        var $country;
        function __construct($country,$id = 0)
        {
            $this->id = $id;
            $this->country = $country;  
        }
}
?>
