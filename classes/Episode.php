<?php
define("EPISODE", 2);
class Episode extends Production{
        var $idTvshow;
        var $season;
        var $episode;
        var $mpaa;
        var $aired;
        var $runtime;
        var $tvshow;
        var $imdb;
        var $files = array(); 
        
        function __construct($season = 0,$episode = 0, $tvshowname = "", $path = "",$filename = "",$filenameExludingFileType = "")
        {
           parent::__construct();
           $this->type = EPISODE;
           $this->tableWithIMDB = 'episode';
	       $this->imdb = "";  
	       $this->runtime = "";
	       $this->mpaa = "";
	       $this->aired = "";
	       $this->failedToFindIMDBNumber = false;
	       $this->season = $season;
	       $this->episode = $episode;
	       $this->tvshow = $tvshowname;
	       $this->filenameExludingFileType = $filenameExludingFileType;
	       if($filename != "")
	       {
	       		$file = new FFile($path,$filename);
	       		$file->getMediaInfo();
	       		$this->files[] = $file;
	       }	
        }   
        
        function clearIMDBdata()
        {
        	parent::clearIMDBdata();
	        $this->mpaa = "";
	        $this->aired = "";
	        $this->runtime = 0;
	        $this->imdb = 0;
        }
        
        function copyValuesFrom($episode)
        {
        	parent::copyValuesFrom($episode);
        	$this->mpaa = $episode->mpaa;
        	$this->aired = $episode->aired;
        	$this->runtime = $episode->runtime;
			$this->imdb = $episode->imdb;
        }
        function toString()
        {
        	$tvshow = $this->tvshow;
        	if(is_a($tvshow,"TvShow"))
        		$tvshow = $tvshow->title;
        		
        	return $tvshow.' - S'.$this->season.'E'.$this->episode.' - '.$this->title;	
        }
        function getDisplayTitle()
        {
        	$tvshow = $this->tvshow;
        	if(is_a($tvshow,"TvShow"))
        		$tvshow = $tvshow->title;
        		
			return $tvshow.' - '.$this->title.' - Season '.$this->season.' Episode '.$this->episode;
        }
        
		function getImdbInfo()
	    {
			$this->getImdbId("");
	        if($this->hasProperIMDBNumber())
	        {	    
	        	MovieMiner::getBasicInfo($this);     
	        	/*
	        	 *  TODO: fetch more info about the episode
	        	 *  MovieMiner::getPosters($this);
	            */
	        	MovieMiner::getActors($this);
	            MovieMiner::getKeywords($this);
	        }
		}
        
        function parseArray($episode)
        {
            parent::parseArray($episode);
            //File info
            $this->files[] = new FFile(); 
            $this->files[0]->filename = substr($episode['filenameandpath'],strrpos($episode['filenameandpath'],'\\')+1); 
            if(!is_array($episode['path']))
                $this->files[0]->path = $episode['path'];
            else
                $this->files[0]->path = '';
            $this->files[0]->discNr = 0; 
            $this->files[0]->storagePlace = '';
            if(!is_array($episode['playcount']))
                $this->files[0]->playcount = $episode['playcount'];
            else
                $this->files[0]->playcount = 0; 
            $this->files[0]->getMediaInfo();    
            if(!is_array($episode['season']))
                $this->season = $episode['season'];
            else 
                $this->season = 0;
            if(!is_array($episode['episode']))
                $this->episode = $episode['episode'];
            else
                $this->episode = 0; 
            if(!is_array($episode['runtime']))
                $this->runtime = substr($episode['runtime'],0,strpos($episode['runtime'],' '));
            else $this->runtime = 0;
            if(!is_array($episode['mpaa']))
                $this->mpaa = $episode['mpaa'];
            else 
                $this->mpaa = ''; 
            if(!is_array($episode['aired']))   
                $this->aired = $episode['aired'];
            else
                $this->aired = '0000-00-00';
        }
}
?>
