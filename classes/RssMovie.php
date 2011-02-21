<?php
define("RSSMOVIE", 3);
define("IGNORED_RSSMOVIE", 4);
class RssMovie extends Movie
{
	var $rssMovieId;
	var $link;
	var $timeReleased;
	var $releaseName;
	var $downloaded;
	var $nrOfChecks;
	var $lastCheck;
	var $haveBasicIMDBinfo;
	var $tooBig;
	var $manuallyAdded;
	
	function __construct($link = "",$releaseName = "", $timeReleased = "",$id = 0)
	{
		$this->tableWithIMDB = 'movie';
		$paths = explode('/',$link);
		if(count($paths) > 0)
			$realReleaseFileName = $paths[count($paths)-1];
		else
			$realReleaseFileName = $releaseName;
			
		$this->rssMovieId = $id;
		$this->manuallyAdded = false;
		$this->link = $link;
		$this->releaseName = $releaseName;
		if($timeReleased == "")
			$this->timeReleased = date('Y-m-d H:i:s');
		else
			$this->timeReleased = $timeReleased;
		$this->downloaded = false;
		
		parent::__construct('internet',$releaseName,$realReleaseFileName,false);
		$this->type = RSSMOVIE;
		$this->lastCheck = date('Y-m-d H:i:s');
		$this->nrOfChecks = 0;
		$this->haveBasicIMDBinfo = false;
		$this->tooBig = false;
	}
	
	function getImdbIdFromTPB($item)
	{
		if(isset($item['comments']))
		{
			if(strpos($item['comments'],'http://') !== false)
			{
			    $detailPage = FileRetrieve::file($item['comments'],"Rss comment page",0,true);
			    if($detailPage !== false)
			    {
			    	$dom = str_get_html($detailPage);
					$nfoNode = $dom->find('div[id=details] div.nfo',0);
					if($nfoNode != null)
					{
						$nfo = $nfoNode->innertext;
						$imdbOffset = strpos($nfo,"imdb.com/title/tt");
						if($imdbOffset !== false)
						{
							$imdbId = StringUtil::scanint(substr($nfo,$imdbOffset),false,0);
							if(is_numeric($imdbId))
								$this->imdb = $imdbId;
						}
					}
					if(!$this->hasProperIMDBNumber())
					{
						$imdbNode = $dom->find('div[id=details] a[href^="http://www.imdb.com/title"]',0);
						if($imdbNode != null)
						{
							$link = $imdbNode->href;
							$imdbId = StringUtil::scanint($link,false,0);
							if(is_numeric($imdbId))
								$this->imdb = $imdbId;
						}
					}
					$dom->clear();
			    }
			}
		}
	}
	
	//Fetches some basic IMDB info from the torrent details page at torrenleech
	//Returns false if multiple imdb links was found in the NFO
	function getInfoFromTL($torrentId, $rssHandler)
	{
		$torrentPage = FileRetrieve::getPageByCurl("http://www.torrentleech.org/torrent/".$torrentId,"cookies/torrentleech.txt");
		$torrentDetails = str_get_html($torrentPage);
		
		if($rssHandler->torrentLeechLoginRequired($torrentDetails))
		{
			$torrentPage = FileRetrieve::getPageByCurl("http://www.torrentleech.org/torrent/".$torrentId,"cookies/torrentleech.txt");
			$torrentDetails = str_get_html($torrentPage);
		}
		
		$nfoLink = "http://www.torrentleech.org/torrents/torrent/nfotext?torrentID=".$torrentId;
		$nfoPage = FileRetrieve::getPageByCurl($nfoLink, "cookies/torrentleech.txt");
		if($this->findIMDB($nfoPage, $nfoLink) == MULTIPLE_MATCHES)
			return false;
		
		$nfoDetails = str_get_html($nfoPage);
		$nfoElement = $nfoDetails->find('pre.nfo',0);
		if($nfoElement != null && strlen($nfoElement->plaintext) > 10)
			Logger::setFileToString(html_entity_decode($nfoElement->plaintext), "torrents/TL_web_".$torrentId.".nfo");
		
		//$torrentDetails = file_get_dom("html_layouts/torrentleech.torrentdetails.html");	
		
		$torrentTable = $torrentDetails->find('table[id=torrentTable] tbody',0);
		if($torrentTable != null)
		{
			$torrentTableRows = $torrentTable->find('td.label');
			foreach($torrentTableRows as $row)
			{
				if($row->plaintext == "Size")
				{
					$sizeNode = $row->next_sibling();
					if($sizeNode != null)
					{
						$sizeText = $sizeNode->plaintext;
						if(strpos($sizeText, "GB") !== false)
						{
							$sizeInGigaBytes = StringUtil::scanint($sizeText,false,0);
							if($sizeInGigaBytes >= 20)
								$this->tooBig = true;
						}
					}
				}
			}
		}
		
		$gotIMDBtable = false;
		
		$headers = $torrentDetails->find('h3');
		foreach($headers as $header)
		{
			if($header->plaintext == "IMDB Info")
				$gotIMDBtable = true;
		}
		
		$imdbTable = $torrentDetails->find('table[id=torrentTable] tbody',1);
		if($gotIMDBtable && $imdbTable != null)
		{
			$rows = $imdbTable->find('td.label');
			foreach($rows as $row)
			{
				if($row->plaintext == "Release Date")
				{
					$releaseDateNode = $row->next_sibling();
					if($releaseDateNode != null)
						$this->year = substr($releaseDateNode->plaintext,0,4);
				}
				else if($row->plaintext == "Title")
				{
					$titleNode = $row->next_sibling();
					if($titleNode != null)
					{
						$this->title = "";
						$this->filenameExludingFileType = $titleNode->plaintext;
					}
				}
			}
			
			$genreRow = $imdbTable->first_child();
			$failedToParseGenre = true;
			if($genreRow != null)
			{
				$genreRow = $genreRow->children(3);
				if($genreRow != null)
				{
					$genresText = "";
					if($genreRow->last_child() != null)
						$genresText = $genreRow->last_child()->plaintext;
						
					if($genresText != "")
					{
						$this->genres = explode(",", $genresText);
						for($i = 0, $size = count($this->genres); $i < $size; $i++)
							$this->genres[$i] = trim($this->genres[$i]); //Removes whitespace left over from the explode
						$failedToParseGenre = false;
					}
				}
			}
			if($failedToParseGenre)
				Logger::parseError($this, "Failed parse Torrentleech IMDB information");
				
			$imdbLink = $imdbTable->find('a[href^="http://www.imdb.com/title/tt"]',0);
			if($imdbLink != null)
			{
				$this->imdb = StringUtil::scanint($imdbLink->href, false, 0);
				
				if($this->year > 0 && $this->hasProperIMDBNumber())
					$this->haveBasicIMDBinfo = true;
			}
			
			$ratingRow = $torrentDetails->find('div.rating',0);
			
			if($ratingRow != null)
			{
				$ratingDetails = explode("(", $ratingRow->plaintext);
				if(count($ratingDetails) == 2)
				{
					$this->rating = trim($ratingDetails[0]);
					$this->votes = StringUtil::scanint($ratingDetails[1],false,0);
				}
			}
			$plotRow = $imdbTable->first_child()->children(6);
			if($plotRow != null)
				$this->plot = $plotRow->last_child()->plaintext;
		}
		
		$torrentDetails->clear();
		return true;
	}
	
    function put()
    {    	
   		echo "	<item>".PHP_EOL."
        			<title>".$this->getRssTitleHTML()."</title>".PHP_EOL."   
        			<link>".utf8_encode(htmlentities($this->downloadLink()))."</link>".PHP_EOL."
        			<description>".$this->getRssDescriptionHTML()."</description>".PHP_EOL."
    				<pubDate>".$this->timeReleased."</pubDate>".PHP_EOL;
        echo "</item>".PHP_EOL;
   		
        $this->downloaded = true;
   		Logger::logPublishedRSSMovie($this);
   }
   
   function getRssDescriptionHTML()
   {
   		return utf8_encode(trim(strip_tags($this->releaseName)));
   }
	
   function getRssTitleHTML()
   {   		
    	$bestRssFile = $this->getBestFile(true);
    	$bestLibraryMovie = $this->getBestFile(false);
    	
    	if(strlen($this->title) > 0)
    		$title = trim(strip_tags($this->getDisplayTitle()));
    	else
    		$title = $this->releaseName;

   		if($this->rating > 0)
			$title .= " Rating: {$this->rating}";
			
		if($bestRssFile->fullHD())
			$title .= " 1080p";
		else if($bestRssFile->regularHD())
			$title .= " 720p";
			
   		if($bestLibraryMovie !== false)		
        	$title .= " Replaces: ".htmlspecialchars($bestLibraryMovie->fullPath());
        	
        $title .= "TorrentID: ".StringUtil::scanint($this->link,false, 0);
        	
        return utf8_encode($title);
   }
   
   function downloadLink()
   {
   		if(substr($this->link,0,4) != "http")
   			return $_ENV['pathToXbmc2web'].$this->link;
   		else
   			return $this->link;
   }
   
   function downloadTorrent()
   {
    	$localpath = "torrents/".basename($this->link);
    	if(!file_exists($localpath))
    		FileRetrieve::copy($this->link, $localpath);
    	return $localpath;
   }
   
   function addTorrentFilesToDb()
   {
   		global $dbh;
   		
   		if($this->haveBasicIMDBinfo)
   			$pathToTorrent = $this->link;
   		else 
   			$pathToTorrent = $this->downloadTorrent();
   			
   		$torrent = new Torrent($pathToTorrent);
		$files = $torrent->content();
		foreach($files as $filename => $size)
		{
			$realpath = str_replace("\\", "/", $filename);
			$dbh->addFileInTorrent($this, $realpath);
		}
   }
   
   function looksLikeAGoodMovie()
   {
   		if($this->manuallyAdded)
   			return true;
   		else 
   			return parent::looksLikeAGoodMovie();
   }
   
   function toString()
   {
   		return $this->getDisplayTitle().', IMDB: '.$this->imdb.', Path: '.$this->files[0]->path.$this->filenameExludingFileType.' Link: '.$this->link.' Date: '.$this->timeReleased;	
   }
   
   /*
    * This function estimates which of two rss releases that is better based on PROPER,DTS, Bluray properties
    */
   function compareReleaseProperties($rssMovie)
   {
   	
   }
}