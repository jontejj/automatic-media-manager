<?php
define("TVSHOW", 0);
class Tvshow extends Production{
        var $premiered;
        var $episodes = array(); 
        var $tempTvshow;
       	var $failedToFindIMDBNumber;
        function __construct()
        {
        	$this->tableWithIMDB = 'tvshow';
        	$this->type = TVSHOW;
        	$this->failedToFindIMDBNumber = false;
        }
        
        function clearIMDBdata()
        {
        	parent::clearIMDBdata();
        	$this->premiered = "";
        	foreach($this->episodes as $episode)
        		$episode->clearIMDBdata();
        }
        
        function getDisplayTitle()
        {       		
			return $this->title;
        }
        
        function getUrlEncodedSearch()
        {
        	return urlencode($this->getDisplayTitle());
        }
        
        function removeEpisodesWithoutFiles()
        {
        	for($i = 0;$i<count($this->episodes); $i++)
        	{
        		if(count($this->episodes[$i]->files) == 0)
        			unset($this->episodes[$i]);
        	}
        }
        
        function getInfoForEpisodes()
        {
        	foreach($this->episodes as $episode)
        	{
        		$fetchedEpisode = $this->tempTvshow->getEpisode($episode->season,$episode->episode);
        		if($fetchedEpisode !== false)
        		{
        			$episode->copyValuesFrom($fetchedEpisode);
        			$episode->getImdbInfo();
        		}
        		else
        			Logger::logNoIMDBNumberFoundForProduction($episode);
        	}	
        }
        
		function getImdbInfo()
	    {
			$this->getImdbId("");
	        if($this->hasProperIMDBNumber())
	        {	     
	            //Getting episode info to speed up the process of fetching episode info for individual episodes later
	            MovieMiner::getEpisodesFor($this);
	        	MovieMiner::getBasicInfo($this);     
	        	/*
				MovieMiner::getPosters($this);
	            
	            */
	        	MovieMiner::getActors($this);
	            MovieMiner::getForeignTitles($this);
	            MovieMiner::getKeywords($this);
	        }
		}
        
        function getEpisode($season,$episode)
        {
        	foreach($this->episodes as $e)
        	{
        		if($e->episode == $episode && $e->season == $season)
        			return $e;
        	}
        	return false;
        }
        
        function getFile($path, $filename)
        {
        	foreach($this->episodes as $episode)
        	{
        		$file = $episode->getFile($path, $filename);
        		if($file !== false)
        			return $file;
        	}
        	return false;
        }
        
        function parseArray($tvshow)
        {
            parent::parseArray($tvshow);
            if(!is_array($tvshow['premiered']))
                $this->premiered = $tvshow['premiered'];
            else 
                $this->premiered = '0000-00-00'; 
            if(isset($tvshow['episodedetails']))
            {
                $tvshow['episodedetails'] = toarray($tvshow['episodedetails']);  
                foreach($tvshow['episodedetails'] as $episode)
                {
                    $this->episodes[] = new Episode();
                    $this->episodes[count($this->episodes)-1]->parseArray($episode);
                }
            }
        }

}
?>
