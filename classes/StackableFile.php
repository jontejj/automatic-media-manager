<?php
	class StackableFile
	{
		var $name;
		var $path;
		var $files = array();
		function __construct($name = "",$path = "",$files = array())
		{
			$this->path = $path;
			$this->name = $name;
			$this->files = $files;
		}
	}
?>