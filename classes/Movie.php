<?php
define("MOVIE", 1);
class Movie extends Production
{
        var $imdb;  
        var $top250;
        var $year;
        var $runtime;
        var $outline;   
        var $tagline; 
        var $mpaa;
        var $studio;
        var $country;
        var $language;
        
        var $titles = array(); 
        var $imdbPage;
        var $failedToFindIMDBNumber;
        var $badResult;
        function __construct($path = "",$filename = "",$filenameExludingFileType = "",$skipMediaInfo = false)
        {
           parent::__construct();
           $this->type = MOVIE;
           $this->tableWithIMDB = 'movie';
	       $this->imdb = "";  
	       $this->top250 = "";
	       $this->year = "";
	       $this->runtime = "";
	       $this->outline = "";   
	       $this->tagline = ""; 
	       $this->mpaa = "";
	       $this->studio = "";
	       $this->country = "";
	       $this->language = "";
	       $this->failedToFindIMDBNumber = false;
	       $this->miniseries = false;
	       $this->filenameExludingFileType = $filenameExludingFileType;
	       if($filename != "")
	       {
	       		$file = new FFile($path,$filename);
	       		if(!$skipMediaInfo)
	       			$file->getMediaInfo();
	       			
           		$this->files[] = $file;
	       }
        }   
        
        function clearIMDBdata()
        {
        	parent::clearIMDBdata();
 			$this->imdb = 0;  
 	       	$this->top250 = 0;
	        $this->year = 0;
	        $this->runtime = 0;
	        $this->outline = "";   
	        $this->tagline = ""; 
	        $this->mpaa = "";
	        $this->studio = "";
	        $this->country = "";
	        $this->language = "";
	        $this->titles = array(); 
        }
        
        function copyValuesFrom($production)
        {	
        	parent::copyValuesFrom($production);
			$this->imdb		= $production->imdb;  
	       	$this->top250 	= $production->top250;
	       	$this->year 	= $production->year;
	       	$this->runtime 	= $production->runtime;
	       	$this->outline 	= $production->outline;   
	       	$this->tagline 	= $production->tagline; 
	       	$this->mpaa 	= $production->mpaa;
	       	$this->studio 	= $production->studio;
	       	$this->country 	= $production->country;
	       	$this->language = $production->language;
        }
        
        function hasCountryInOtherTitles($countryId)
        {
        	foreach($this->titles as $id => $title)
            {
            	foreach($title->countries as $country)
                	if($country->id == $countryId)
                    	return true;
            }
            return false;
        }
        function prefixOtherTitleForCountryBeforeOriginalTitle($countryId)
        {
            foreach($this->titles as $id => $title)
            {
            	foreach($title->countries as $country)
                	if($country->id == $countryId)
                		return $title->title.' ('.$this->title.')';
            }	
            return $this->title;
        }
   
        function toString()
        {
        	return 'Title: '.$this->title.', Year: '.$this->year.', IMDB: '.$this->imdb.', Path: '.$this->files[0]->path.$this->filenameExludingFileType;	
        }
        
        function getDisplayTitle()
        {
        	$result = $this->title;
			if($this->year && !isset($_GET['year']))
				$result .= ' ('.$this->year.')';
				
			if($this->sortData != "")
				$result .= ' - '.$this->sortData;
				
			return $result;
        }
        
        function getUrlEncodedSearch()
        {
        	$t = $this->getTitle();
        	if(strlen($t) > 0)
        	{
        		//Make the first character lowercase because then IMDB gives us better results, 
        		//but only do it if the provided title don't begin with several big characters
        		if(strtolower($t[1]) == $t[1])
        			$t[0] = strtolower($t[0]);
        	}
        	
        	if($this->year !== false)
        		return urlencode(html_entity_decode($t).' ('.$this->year.')');
        	else
        		return urlencode(html_entity_decode($t));
        		
        	
        }
        
        function getTitle()
        {
        	if($this->title == "")
        	{
	        	$filename = $this->files[0]->filename;
				$filetype = strtolower(substr($filename,strrpos($filename,'.')+1));
	
				//This member variable is stripped from stacking info such as .cd1.avi etc
				
				$otherFiletype = substr($this->filenameExludingFileType,-7);
				if($otherFiletype == 'torrent')
					$title = $filename;
				else
					$title = $this->filenameExludingFileType;
				
				//This reads the title from the path instead because of some strange scene rules
				$pathComponents = explode('/',$this->files[0]->path);
				$depth = count($pathComponents)-2;

				if($filetype == 'rar' && $depth >= 0)
					$title = $pathComponents[$depth];
				//If the parent folder is like CD1 or CD2 or VIDEO_TS go one level up the directory hierachy
				if(stripos($pathComponents[$depth],'CD') !== false)
					if(is_numeric(substr($pathComponents[$depth],2)))
					{
						$depth--;
						$title = $pathComponents[$depth];
					}
						
				if(stripos($filename,'VIDEO_TS') !== false)
					$title = $pathComponents[$depth];
					
				$this->title = stripReleaseInfoFromTitle($title);
				if($this->year == 0)
					$this->year = stripReleaseInfoFromTitle($title,true);
				return $this->title;
        	}
        	else
        		return $this->title;
        }
        
		function getImdbInfo($pathWithFileExludingFiletype = "")
	    {
			$this->getImdbId($pathWithFileExludingFiletype);
	        if($this->hasProperIMDBNumber())
	        {	     
	        	MovieMiner::getBasicInfo($this);
	        	if(!$this->badResult)
	        	{    
					MovieMiner::getActors($this);
					MovieMiner::getPosters($this);
		            MovieMiner::getForeignTitles($this);
		            MovieMiner::getKeywords($this);
	        	}
	        }
		}
	
        function parseArray($movie)
        {         
            parent::parseArray($movie);
            $this->files[] = new FFile();
            $this->files[0]->filename = substr($movie['filenameandpath'],strrpos($movie['filenameandpath'],'\\')+1); 
            if(!is_array($movie['path']))
                $this->files[0]->path = $movie['path'];
            else
                $this->files[0]->path = '';
            $folders = explode('\\',$this->files[0]->path);
            if(!is_array($movie['playcount']))
                $this->files[0]->playcount = $movie['playcount'];
            else $this->files[0]->playcount = 0;
            if($folders[1] == 'skivor')
            {
                $this->files[0]->storagePlace = $folders[2];
                if(isset($folders[3]))
                    $this->files[0]->discNr = $folders[3];
                else $this->files[0]->discNr = 0;
            }
            else
            {
                $this->files[0]->storagePlace = '';
                $this->files[0]->discNr = 0;
                $this->files[0]->getMediaInfo();
            }
            if(!is_array($movie['id'])) 
                $this->imdb = ltrim(substr($movie['id'],2),0);
            else 
                $this->imdb = 0;
            if(!is_array($movie['top250']))
                $this->top250 = $movie['top250'];
            else 
                $this->top250 = 0;
            if(!is_array($movie['year']))
                $this->year = $movie['year'];
            else 
                $this->year = 0;
            if(!is_array($movie['runtime']))
                $this->runtime = substr($movie['runtime'],0,strpos($movie['runtime'],' '));
            else 
                $this->runtime = 0;
            if(!is_array($movie['outline']))
                $this->outline = $movie['outline'];
            else
                $this->outline = '';
            if(!is_array($movie['tagline']))
                $this->tagline = $movie['tagline']; 
            else
                $this->tagline = '';
            if(!is_array($movie['mpaa']))
                $this->mpaa = $movie['mpaa'];  
            else
                $this->mpaa = '';
            if(!is_array($movie['studio']))
                $this->studio = $movie['studio'];
            else
                $this->studio = '';
        }
        
	    function looksLikeAGoodMovie()
	    {    		    	
	    	//720p movies will have to wait 3 weeks and if there have not come any 1080p movies, download the 720p version
	    	if(!$this->fullHD())
	    	{
		        $allowDate = strtotime("+ 3 week",strtotime($this->timeReleased));
		        if($allowDate !== false)
		        {
		        	$now = strtotime(date('Y-m-d H:i:s'));
		        	if($now < $allowDate)
		        		return false;
		        }
	    	}
	    	
	    	//Movies that we already have in our library are considered good
	    	if(count($this->getNonInternetFiles()) > 0)
	    		return true;
	    		
	    	//Blah Blah, movies with only boring genres goes away
			if(count($this->genres) == 1 && ($this->hasGenre("Documentary") || $this->hasGenre("Music") || $this->hasGenre("Sport")))
				return false;
	    
	    	//Block busters ftw
	    	if($this->votes > 10000)
	    		return true; 

	    	//Film noir ftw, old movies with low number of votes is not likely to change, so it is probably a good movie
	    	//Movies with a low number of votes will get scheduled for an update of the vote count and the rating
	    	if(($this->rating < 6.5 || $this->votes < 1000) && $this->year > (date('Y')-15))
	    		return false; 
	    		
	    	//Pretty bad movies but high video quality
	    	if(($this->rating > 6.5 && $this->votes > 2500) && ($this->regularHD() || $this->fullHD()))
	    		return true;
	    		
	    	//Good movie but it may be of bad video quality, lets download those
	    	if(($this->rating > 7.8 && $this->votes > 4000))
	    		return true;
	    		   	
			//My kind of genres
			if($this->fullHD() && ($this->hasGenre("Drama") || $this->hasGenre("Action") || $this->hasGenre("Comedy")))
				return true;
				
			return false;
	    }
}
?>
