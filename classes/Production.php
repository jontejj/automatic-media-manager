<?php

class Production {
        var $id; 
        var $filenameExludingFileType;
        var $title;
        var $plot;
        var $rating;
        var $votes;
        var $type;
        /* relationed objects */
        var $directors = array(); 
        var $actors = array(); 
        var $writers = array(); 
        var $genres = array(); 
        var $photos = array(); 
        var $fanart = array();
        var $keywords = array();
        var $files = array(); 
        var $imdbPage;
        var $tableWithIMDB;
        var $sortData;
        var $torrentIMDB;
        var $nfoIMDB;
        
        function __construct()
        {
        	$this->tableWithIMDB = '';
        	$this->type = 0;
       		$this->id = "";
	        $this->title = "";
	        $this->plot = "";
	        $this->rating = "";
	        $this->votes = "";
	        $this->filenameExludingFileType = "";
	        $this->sortData = false;
        	$this->torrentIMDB = 0;
        	$this->nfoIMDB = 0;
        }
        function copyValuesFrom($production)
        {	
       		$this->title = $production->title;
       		$this->plot = $production->plot;
       		$this->rating = $production->rating;
       		$this->votes = $production->votes;
       		$this->sortData = $production->sortData;
        }
        
        function toString()
        {
        	return $this->title.' - '.$this->filenameExludingFileType;	
        }
        
        function setSortData($sort)
        {
        	$this->sortData = $sort;
        }
        
        function getDisplayTitle()
        {
        	return $this->title;
        }
        
        function getFile($path,$filename)
        {
        	foreach($this->files as $file)
        	{
        		if($file->path == $path && $file->filename == $filename)
        			return $file;
        	}	
        	return false;
        }
        
        function hasGenre($genre)
        {
        	foreach($this->genres as $g)	
        		if($g == $genre)
        			return true;
        	return false;
        }
        
        function fullHD()
        {
        	foreach($this->files as $file)
        	{
        		if($file->fullHD())
        			return true;
        	}
        	return false;
        }
        
        function regularHD()
        {
        	foreach($this->files as $file)
        	{
        		if($file->regularHD())
        			return true;
        	}
        	return false;
        }
        function filePathForFirstFile()
        {
        	if(count($this->files) > 0)
        		return $this->files[0]->path.$this->files[0]->filename;
        	else
        		return "";
        }
        
        function getBestFile($shouldBeInternetFile)
        {
        	if(count($this->files) > 0)
        	{
	        	$maxWidth = -1;
	        	$bestFile = false;
	        	foreach($this->files as $file)
	        	{
	        		if($file->width > $maxWidth && $shouldBeInternetFile == $file->isInternetFile())
	        		{
	        			$maxWidth = $file->width;
	        			$bestFile = $file;
	        		}
	        	}
	        	return $bestFile;
        	}
        	return false;
        }
        
        function gotFilesWithDifferentPaths()
        {
        	$files = $this->getNonInternetFiles();
        	if(count($files) > 1)
        	{
	        	$path = $files[0]->path;
	        	foreach($files as $file)
	        	{
	        		if($file->path != $path)
	        			return true;
	        	}
        	}
        	return false;
        }
        function getNonInternetFiles()
        {
        	$files = array();
       		foreach($this->files as $file)
	        {
        		if(!$file->isInternetFile())
        			$files [] = $file;
        	}
        	return $files;
        }
        
		function clearIMDBdata()
		{
        	$this->title = "";
        	$this->plot;
        	$this->rating;
        	$this->votes;
        	$this->directors = array(); 
        	$this->actors = array(); 
       		$this->writers = array(); 
        	$this->genres = array(); 
        	$this->photos = array(); 
        	$this->fanart = array();
        	$this->keywords = array();
        	$this->sortData = false;
		}
        /**
         * Returns MULTIPLE_MATCHES if there was several IMDb links in the NFO file
         * @param $pathWithFileExludingFiletype
         */
        function getImdbId($pathWithFileExludingFiletype)
        {
        	$nfoResult = NO_MATCH;
        	//Do not repeat the mistakes you have made!
        	if($this->failedToFindIMDBNumber)
        		return;
        		   
        	if(!$this->hasProperIMDBNumber())
	        {
	        	global $dbh;
	        	//If this is a movie downloaded by rss, it file have a matching file in the database fetched from the .torrent file
	        	$dbh->lookForMatchingProductionForATorrentFile($this);
	        	$path = DirectoryUtil::getPathToNfoFileConnectedToMovieFile($pathWithFileExludingFiletype);
	        	$nfoResult = $this->lookForIMDBInNfoFile($path);
	        }
		        
		    if(!$this->hasProperIMDBNumber())
				MovieMiner::getImdbId($this);
				
	        if(!$this->hasProperIMDBNumber())
	        	$this->failedToFindIMDBNumber = true;
	        	
	        if($this->hasProperIMDBNumber())
	       		$nfoResult = FOUND_ID;
	       		
	        return $nfoResult;
        }
        
        function hasProperIMDBNumber()
        {
        	return MovieMiner::isProperIMDBNumber($this->imdb);
        }
        /**
         * Returns MULTIPLE_MATCHES if there was several IMDb links
         * @param $pathWithFileExludingFiletype
         */
		function lookForIMDBInNfoFile($path)
        {
			if($path !== false)
			{
				$nfoFile = file($path);
				return $this->findIMDB($nfoFile, $path);
			}
			return NO_MATCH;
        }
        /**
         * Returns MULTIPLE_MATCHES if there was several IMDb links
         */
        function findIMDB($object, $path)
        {
        	if(is_array($object))
        	{
	        	$imdbLinkLineNumber = returnarraykey($object,"imdb.com/title/tt");
				if($imdbLinkLineNumber !== false)
				{
					$imdbIdStart = strpos($object[$imdbLinkLineNumber],"imdb.com/title/tt")+17;							
					$imdbId = StringUtil::scanint2(substr($object[$imdbLinkLineNumber],$imdbIdStart));
					if(is_numeric($imdbId))
					{
						while(true)
						{
							//Looks for multiple links
							$imdbLinkLineNumber = returnarraykey($object,"imdb.com/title/tt", $imdbLinkLineNumber+1);
							$imdbIdStart = strpos($object[$imdbLinkLineNumber],"imdb.com/title/tt", $imdbIdStart);
							if($imdbIdStart !== false)
							{
								$imdbIdStart += 17;
								$newImdbId = StringUtil::scanint2(substr($object[$imdbLinkLineNumber],$imdbIdStart));
								if(is_numeric($newImdbId) && $newImdbId != $imdbId)
								{
									Logger::echoText("Found Multiple IMDB links (both {$imdbId} and {$newImdbId}) in NFO at: {$path}<br>");
									return MULTIPLE_MATCHES;
								}
							}	
							else 
								break;
						}

						$this->imdb = $imdbId;
						$this->nfoIMDB = $imdbId;
						Logger::echoText("Found IMDB: {$this->imdb} in NFO at: {$path}<br>");
						return FOUND_ID;
					}
					else
						Logger::echoText("Error while parsing for imdb ID in nfo file at: ".$path."<br>");
				}
        	}
        	else
        	{
             	$imdbIdStart = strpos($object,"imdb.com/title/tt");
             	if($imdbIdStart !== false)
             	{						
					$imdbId = StringUtil::scanint2(substr($object,$imdbIdStart+17));
					if(is_numeric($imdbId))
					{
						while(true)
						{
							//Looks for multiple links
							$imdbIdStart =  strpos($object,"imdb.com/title/tt", $imdbIdStart+17);
							if($imdbIdStart !== false)
							{
								$imdbIdStart += 17;
								$newImdbId = StringUtil::scanint2(substr($object,$imdbIdStart));
								if(is_numeric($newImdbId) && $newImdbId != $imdbId)
								{
									Logger::echoText("Found Multiple IMDB links (both {$imdbId} and {$newImdbId}) in NFO at: {$path}<br>");
									return MULTIPLE_MATCHES;
								}
							}	
							else 
								break;
						}
						$this->imdb = $imdbId;
						$this->nfoIMDB = $imdbId;
						Logger::echoText("Found IMDB: {$this->imdb} in NFO at: {$path}<br>");
						return FOUND_ID;
					}
					else
						Logger::echoText("Error while parsing for imdb ID in nfo file at: ".$path."<br>");
             	}
			}
			return NO_MATCH;
        }
        
        function parseArray($prod)
        {            
            $this->title = $prod['title'];
            if(!is_array($prod['plot']))
                $this->plot = $prod['plot'];
            if(!is_array($prod['rating']))
                $this->rating = $prod['rating'];
            else $this->rating = 0;
            if(!is_array($prod['votes']))  
                $this->votes = str_replace(',','',$prod['votes']);
            else $this->votes = 0;
            if(!is_array($prod['genre']))
                $this->genres = explode(' / ',$prod['genre']);
            if(!is_array($prod['credits']))
                $this->writers = explode(' / ',$prod['credits']); 
            if(!is_array($prod['director']))     
                $this->directors = explode(' / ',$prod['director']);  
            if(!is_array($prod['thumb']))
            {
                $photos = explode('http://',$prod['thumb']);   
                unset($photos[0]); 
                foreach($photos as $photo)
                { 
                    $photo = str_replace('&gt;','',$photo);
                    $photo = str_replace('&lt;','',$photo); 
                    $photo = str_replace('/thumb','',$photo); 
                    $photo = str_replace('/thumbs','',$photo);
                    $photo = str_replace('thumb','',$photo);
                    $photo = str_replace('thumbs','',$photo); 
                    $photo = str_replace('<','',$photo); 
                    $photo = str_replace('>','',$photo); 
                    $this->photos[] = 'http://'.$photo;
                }
                $this->photos = array_unique($this->photos);
            }
            if(isset($prod['fanart']))
            {
                if(isset($prod['fanart']['thumb']))
                {
                    if(is_array($prod['fanart']['thumb']))
                    {
                        foreach($prod['fanart']['thumb'] as $key => $path)
                            if(is_numeric($key))
                                $this->fanart[] = new Fanart($prod['fanart_attr']['url'].$path,$prod['fanart_attr']['url'].$prod['fanart']['thumb'][$key.'_attr']['preview']);      
                    }
                }
            }
            if(isset($prod['actor']))
            {
                if(isset($prod['actor']['name']))
                {
                    $actor = $prod['actor'];
                    if(isset($actor['name']) && isset($actor['role']))
                    {     
                        $this->actors[] = new Acting($actor['name'],$actor['role']);
                        if(!is_array($actor['thumb']))
                        {
                            $photos = explode('http://',$actor['thumb']);   
                            unset($photos[0]); 
                            foreach($photos as $photo) $this->actors[count($this->actors)-1]->photos[] = 'http://'.$photo;
                        }
                    }
                }
                else if(is_array($prod['actor']))
                {
                    foreach($prod['actor'] as $actor)
                    {                          
                        if(isset($actor['name']) && isset($actor['role']))
                        {     
                            $this->actors[] = new Acting($actor['name'],$actor['role']);
                            if(!is_array($actor['thumb']))
                            {
                                $photos = explode('http://',$actor['thumb']);   
                                unset($photos[0]); 
                                foreach($photos as $photo) $this->actors[count($this->actors)-1]->photos[] = 'http://'.$photo;
                            }
                        }
                    }
                }
            }
        }
}
?>
