<?php
class PersonPhoto {
        var $id;
        var $idPerson;
        var $path;
        function __construct($path = "",$idPerson = 0, $idPhoto = 0)
        {
           $this->idPerson = $idPerson;
           $this->id = $idPhoto;
           $this->path = $path;
        }
}
?>
