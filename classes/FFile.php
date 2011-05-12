<?php
$subtitlesformats = array("srt","sup","sub","SRT","SUP","SUB");
 class FFile
 {
  		var $fileId;
  		var $productionId;
        var $playcount;
        var $filename;
        var $path;
        var $timeAdded;
        
        //File specific from Media info
        var $format;               //Matroska
        var $filesize;                  //GB
        var $duration;                  //mins
        var $videobitrate;              //Mbps
        var $width;                     //1920
        var $height;                    //1040
        var $ar;                        //16/9
        var $writinglibrary;
        var $hassubtitle;
        var $audiotracks = array();

        function __construct($path = "",$filename = "")
        {
        	$this->fileId = -1;
        	$this->productionId = -1;
        	$this->hassubtitle = false;
	        $this->playcount = 0;
	        $this->format = "";            
	        $this->filesize = 0;              
	        $this->duration = 0;            
	        $this->videobitrate = 0.0;      
	        $this->width = 0;              
	        $this->height = 0;                
	        $this->ar = "";     
	        $this->writinglibrary = "";
			$this->timeAdded = date('Y-m-d H:i:s');
				
  			$this->path = $path;
  			$this->filename = $filename;
        }
        
        function hasProperties()
        {
        	
        }
        
        function fullPath()
        {
        	return $this->path.$this->filename;
        }
        
        function fullHD()
        {
        	if($this->width >= 1920)
        		return true;
        	else 
        		return false;		
        }
        function regularHD()
        {
        	if($this->width >= 1280 && $this->width < 1920)
        		return true;
        	else
        		return false;
        }
        
        function isInternetFile()
        {
        	return (substr($this->path,0,8) == 'internet');
        }
        
        function getMediaInfo()
        {
        	global $movieformats,$acceptableformats,$subtitlesformats, $cfg;
        	$filetype = strtolower(substr($this->filename,strrpos($this->filename,'.')+1));
        	
        	if($this->isInternetFile() || $filetype == 'rar')
        	{
        		//Some info is given by the release name, 
        		if(stripos($this->path.$this->filename,'1080p') !== false)
        		{
        			$this->width = 1920;
        			$this->height = 1080;
        		}
        	    else if(stripos($this->path.$this->filename,'720p') !== false)
        		{
        			$this->width = 1280;
        			$this->height = 540;
        		}
        	    else if(stripos($this->path.$this->filename,'810p') !== false)
        		{
        			$this->width = 1440;
        			$this->height = 600;
        		}
        		//No more info about a internet file can be fetched
        		if($this->isInternetFile())
        			return;	
        	}
        	
			$this->hassubtitle = false;
			$pathWithFileExludingFiletype = substr($this->path.$this->filename,0,strrpos($this->path.$this->filename,'.'));
			foreach($subtitlesformats as $format)
				if(is_file($pathWithFileExludingFiletype.'.'.$format))
				{
					$this->hassubtitle = true;
					break;
				}
        	
        	$filesize = filesize($this->path.$this->filename);
        	if ( $filesize < 0 )
        		$filesize = -$filesize;
        	if( 
        		isVideoTsFolder($this->path.$this->filename)
        		||
        		(in_array($filetype,$acceptableformats) && is_readable($this->path.$this->filename) && is_file($this->path.$this->filename) && $filesize > 1000))
        	{
        		$fileToLookAt = $this->path.$this->filename;
        		
        		$dvdfile = isVideoTsFolder($this->path.$this->filename);
        		if($dvdfile !== false)
	            	$fileToLookAt .= $dvdfile;
	            	
	            exec(realpath(getcwd()."/functions/mediainfo/mediainfo.exe").' "'.$fileToLookAt.'"',$output); 
	            $info = array();
	            foreach($output as $line)
	            {
	                //Remove description of each line
	                $t = explode(" : ",$line);
	                if(count($t) > 1)
	                    $info[] = $t[1];
	                else 
	                    $info[] = $t[0];
	            }
	            //Has subtitle?
	            if($this->hassubtitle === false)
	            {
	            	if(returnarraykey($output,'Text') !== false)
	            		$this->hassubtitle = true;	
	            }
	            //Duration
	            $durationline = returnarraykey($output,'Duration');
	            if($durationline !== false)
	            {
	            	$durationline = $info[$durationline];
		            if(strpos($durationline,'h') !== false)
		                $hours = substr($durationline,0,strpos($durationline,'h'));
		            else
		                $hours = 0;
		            if(strpos($durationline,'mn') !== false)
		                $mins = substr($durationline,strpos($durationline,'h')+2,2);
		            else
		                $mins = 0;
		            $this->duration = $hours*60+$mins;
	            }
	            //Format
	            $formatline = returnarraykey($output,'Format');
	            if($formatline !== false)
	            	$this->format = $info[$formatline];
	            else 
	            	$this->format = "";
	            
	            //Filesize
	            $filesizeline = returnarraykey($output,'File size');
	            if($filesizeline !== false)
	            {
	            	$filesizeline = $info[$filesizeline];
		            $this->filesize = substr($filesizeline,0,strpos($filesizeline,' '));
		            if(strpos($filesizeline,'MiB'))
		                $this->filesize /= 1024;
		            $this->filesize = round($this->filesize,2);
	            }
	            
        		//Video bitrate
	            $videoOffset = returnarraykey($output,'Video');
	            if($videoOffset !== false)
	            {
		            $videobitrateline = returnarraykey($output,'Bit rate',$videoOffset);
		            if($videobitrateline !== false)
		            {
		            	$line = $info[$videobitrateline];
			            if(strpos($line,'Kbps') !== false)
			            {
			                $line = str_replace(array("Kbps"," "),'',$line);
			                $this->videobitrate = $line/1024;    
			            }
			            else
			                $this->videobitrate = substr($line,0,strpos($line,' '));
			            $this->videobitrate = round($this->videobitrate,2);           
		            }
	            }
	            
	            //Video width
	            $widthline = returnarraykey($output,'Width');
	            if($widthline !== false)
	            	$this->width = str_replace(array("pixels"," "),'',$info[$widthline]);
	            	
	            //Video height
	            $heightline = returnarraykey($output,'Height');
	            if($heightline !== false)
	            	$this->height = str_replace(array("pixels"," "),'',$info[$heightline]);
	
	            //Aspect Ratio
	            $arline = returnarraykey($output,'Display aspect ratio');
	            if($arline !== false)
	            	$this->ar = $info[$arline];
	            	
	            //Writing library
	            $wlline = returnarraykey($output,'Writing library');
	            if($wlline !== false)
	            	$this->writinglibrary = $info[$wlline];
	
	            //Audio tracks
	            $multipleaudiotracks = returnarraykey($output,'Audio #');
	            $multiple = false;
	            if($multipleaudiotracks !== false) //Multiple
	            {
	            	$nr = 1;
	            	$audiotrackline = returnarraykey($output,'Audio #'.$nr);
	            	$multiple = true;
	            }
	            else	//Single track
	            	$audiotrackline = returnarraykey($output,'Audio');
	           	while($audiotrackline !== false)
	           	{
		            //title
		            $lline = returnarraykey($output,'Title',$audiotrackline);
		            if($lline !== false)
		            	$title = $info[$lline];
		            else 
		            	$title = "";
		            	
		            //language
		            $lline = returnarraykey($output,'Language',$audiotrackline);
		            if($lline !== false)
		            	$language = $info[$lline];
		            else 
		            	$language = "";
	
		            //format
		            $formatline = returnarraykey($output,'Format',$audiotrackline);
		            if($formatline !== false)
		            	$format = $info[$formatline];
		            else
		            	$format = "";
		            
		            //formatinfo
		            $formatiline = returnarraykey($output,'Format/Info',$audiotrackline);
		            if($formatiline !== false)
		            	$formatinfo = $info[$formatiline];
		            else
		            	$formatinfo = "";
	
		            //bitrate
		            $bitrateline = returnarraykey($output,'bps',$audiotrackline);
		            if($bitrateline !== false)
		            {
		            	$bitrateline = $info[$bitrateline];
		            	$bitrate = str_replace(' ','',substr($bitrateline,0,strpos($bitrateline,'K')));
		            }
		            else
		            	$bitrate = "";
		            
	           		//channels
		            $channelsline = returnarraykey($output,'Channel(s)',$audiotrackline);
		            if($channelsline !== false)
		            {
		            	$channelsline = $info[$channelsline];
		            	$channels = substr($channelsline,0,strpos($channelsline,' '));
		            }
		            else
		            	$channels = "";
		            $this->audiotracks[] = new AudioTrack($language,$format,$formatinfo,$bitrate,$channels,$title);	
		            if($multiple)
		            	$audiotrackline = returnarraykey($output,'Audio #'.(++$nr));
		            else
		            	$audiotrackline = false;
	           	}
        	}
        	//We can not check a internet file for media info or a rar file (at the moment)
        	else if(in_array($filetype,$acceptableformats) || $this->isInternetFile())
        	{
        	    //Some info is given by the release name
        		if(stripos($this->filename,'1080p') !== false)
        		{
        			$this->width = 1920;
        			$this->height = 1080;
        		}
        	    else if(stripos($this->filename,'720p') !== false)
        		{
        			$this->width = 1280;
        			$this->height = 720;
        		}
        		
        	    if(stripos($this->filename,'DTS') !== false)
        		{
        			$this->audiotracks[] = new AudioTrack("english","DTS","Digital Theatre System",1500,6,"Assumed audio track by filename");
        		}
        	}
        }
  }
?>
