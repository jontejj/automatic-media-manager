<?php
class Fanart {
        var $path;
        var $preview;
        var $id;
        function __construct($path,$preview = '')
        {
            $this->path = $path;
            $this->preview = $preview;
            $this->id = "";
        }
}
?>
