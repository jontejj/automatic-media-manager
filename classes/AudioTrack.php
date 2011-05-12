<?php
	class AudioTrack
	{
	    var $language;   //English
        var $format;     //DTS
        var $formatinfo; //Digital Theater Systems
        var $bitrate;    //Kbps
        var $channels;   //nr of channels
        var $title;		 //Main Audio, Director Danny Leiner, Actors John Cho & Kal Penn
		function __construct($language = "",$format = "",$formatinfo = "",$bitrate = 0,$channels = 0,$title = "")
		{
		    $this->language = $language;
	        $this->format = $format;
	        $this->formatinfo = $formatinfo;
	        $this->bitrate = $bitrate;
	        $this->channels = $channels;
	        $this->title = $title;	
		}
	}
?>