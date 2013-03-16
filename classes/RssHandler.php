<?php
define("DOWNLOAD", 1);
define("DISALLOWED", 2);
define("EXISTED", 3);
define("HAS_FAILED_BEFORE", 4);
define("REDOWNLOAD_TORRENT_FILE",5);

class RssHandler
{
	private $ignoredReleases = array('BoxSet','Quadrilogy','Quadrology','Trilogy','Duology','Hexalogy','Collection','Nuked','Movie pack','PACK','PS3','X360',
										'FRENCH', 'GERMAN','German','WEBSCR', 'KORSUB');
	private $seperators = array('.','-',',','_','[',']',' ','(',')');
	/**
	 * TvTorrents Rss filter
	 */
    public function rss($feed,$update)
    {
    	Logger::disableEcho();
        error_reporting(E_ERROR);
        $rss = fetch_rss($feed);
        header("Content-type: text/xml");
        echo "<?xml version='1.0' encoding='UTF-8'?>
        <rss version='2.0'>
        <channel>
        <title>New shows from TvTorrents</title>
        <link>".htmlentities($feed)."</link>
        <description>Torrents</description>
        <language>en-us</language>";
        foreach($rss->items as $item)
        {
            global $dbh;

            $showname = substr($item['summary'],10,strpos($item['summary'],';')-10);
            $str = substr($item['summary'],strpos($item['summary'],'; Season:')+10);
            $season = substr($str,0,strpos($str,';'));
            $episodestr = substr($str,11+strlen($season));
            $episode = substr($episodestr,0,strpos($episodestr,';'));
            $hd = strpos($str,'.mkv');
            $d = new DateTime($item['pubdate']);
            $date = $d->format('Y-m-d'); //date_parse($item['pubdate']);   //'date_timestamp'   ?
            $link = $item['link'];   //200 chars
            $filename = substr($item['summary'],strpos($item['summary'],'Filename:')+9,-1);
            $rssitem = new RssItem($link, $season, $episode, $hd, $date, $showname, $filename);
            if('all' != strtolower($rssitem->episode))   //Vi vill inte ha stora packar med alla avsnitt från en säsong
            {
                if(!$rssitem->isFetchedWithHd()) //Är objektet inte hämtat i hd kvalitet?
                {
                    if($rssitem->hd())
                    {
                        $rssitem->removeSDversions();
                        $rssitem->insert();
                        //Lägg ut objekt i RSS fil
                        $rssitem->put();
                    }
                    else
                        $rssitem->insert();
                }
                else if($rssitem->hd())
                {
                    $rssitem->removeSDversions();
                    //Lägg ut objekt i RSS fil
                    $rssitem->put();
                }
            }
        }
        //Hämta alla objekt som är äldre än 7 dagar gamla och är i SD
        $rssitems = $dbh->getMax7DaysOldSDversions();
        foreach($rssitems as $rssitem)
        {
            //Lägg ut objekt i RSS fil
            $rssitem->put();
        }
        echo "</channel></rss>";
        Logger::enableEcho();
        die();
    }

    public function mineTL($user, $pass, $update = false)
    {
    	global $dbh;
    	$this->printRssHeader();
    	if($update)
    	{
	    	$alreadyLoggedIn = FileRetrieve::validRememberMeCookie("cookies/torrentleech.txt");
	    	if(!$alreadyLoggedIn)
				$this->torrentLeechlogin($user, $pass);

	    	$timeToQuit = false;
	    	$currentPage = 1;
	    	while($currentPage < 30 && !$timeToQuit)
	    	{
				$moviesListing = FileRetrieve::getPageByCurl("http://www.torrentleech.org/torrents/browse/index/categories/13/page/".$currentPage,"cookies/torrentleech.txt");
				$dom = str_get_html($moviesListing);
		    	if($this->torrentLeechLoginRequired($dom))
		    	{
		    		//If something bad happens during our scan, we may be thrown out
		    		$this->torrentLeechlogin($user, $pass);
		    		$moviesListing = FileRetrieve::getPageByCurl("http://www.torrentleech.org/torrents/browse/index/categories/13/page/".$currentPage,"cookies/torrentleech.txt");
					$dom = str_get_html($moviesListing);
		    	}
		    	//$dom = file_get_dom("html_layouts/torrentleech.browse.html");

				$torrentRows = $dom->find("table[id=torrenttable] tbody tr");


				foreach($torrentRows as $torrentRow)
				{
					if(isset($torrentRow->id))
					{
						$torrentId = $torrentRow->id;

						//Release date on TL
						$nameElement = $torrentRow->find("td.name", 0);
						$date = substr(trim($nameElement->nodetext),13);

						$titleNode = $torrentRow->find('a[href^="/torrent/"]',0);
						$title = $titleNode->plaintext;

						$pathToStoreTorrentAt = "torrents/TL_web_".$torrentId.".torrent";

						$quickDownloadLinkElement = $torrentRow->find("td.quickdownload a",0);
						if($quickDownloadLinkElement != null)
						{
							$downloadURL = "http://www.torrentleech.org".str_replace(" ", "%20", $quickDownloadLinkElement->href);
							$action = $this->actionForRssMovie($pathToStoreTorrentAt, $title);
							switch($action)
							{
								case DOWNLOAD:
									$rssMovie = new RssMovie($pathToStoreTorrentAt, $title, $date);
									$rssMovie->filenameExludingFileType = $title;
									if(!$rssMovie->getInfoFromTL($torrentId,$this))
									{
										//Multiple IMDB links was found in NFO, this indicates a Trilogy or something, lets ignore those
										$dbh->logFailedRssMovieLink($rssMovie->link);
    									Logger::logIgnoredRSSMovie($rssMovie, "Multiple IMDB links found in NFO");
    									break;
									}

									if(!$rssMovie->tooBig)
									{
										//Downloads the torrent
				    					if(!file_exists($rssMovie->link))
				    					{
				    						$torrentFile = FileRetrieve::getPageByCurl($downloadURL, "cookies/torrentleech.txt");
				    						if($torrentFile != "")
				    						{
				    							Logger::setFileToString($torrentFile,$rssMovie->link);
				    						}
				    						else
				    							//Something went wrong with the download, lets skip this torrent
				    							break;
				    					}
				    					$this->handleNewRssMovie($rssMovie);
									}
									else
									{
										$dbh->logFailedRssMovieLink($pathToStoreTorrentAt);
										Logger::logNotAllowedRSSMovie($rssMovie->toString().": Too big");
									}

			    					break;
								case REDOWNLOAD_TORRENT_FILE:
									unlink($pathToStoreTorrentAt);
		    						$torrentFile = FileRetrieve::getPageByCurl($downloadURL, "cookies/torrentleech.txt");
		    						if($torrentFile != "")
		    							Logger::setFileToString($torrentFile,$pathToStoreTorrentAt);

		    						if($this->actionForRssMovie($pathToStoreTorrentAt, $title) == REDOWNLOAD_TORRENT_FILE)
		    						{
		    							Logger::echoText("Failed to download torrent file: ".$downloadURL);
		    							//die();
		    						}
		    						break;
								case EXISTED:
									$timeToQuit = true;
									break;
							}
						}
					}
					if($timeToQuit)
						break;
				}
				$dom->clear();
				$currentPage++;
	    	}
    	}

		$this->printRssMoviesFromDatabase();
		$this->printRssFooter();
    }

    public function torrentLeechLoginRequired($dom)
    {
    	$loginForm = $dom->find('form[action="/user/account/login/"]');
    	return $loginForm != null;
    }

    private function torrentLeechlogin($user, $pass)
    {
    	//Creates a session and retrieves the PHP_SESSID
    	FileRetrieve::getPageByCurl("http://www.torrentleech.org/","cookies/torrentleech.txt");

    	$postData = array('username' => $user, 'password' => $pass, 'login' => 'submit', 'remember_me' => 'on');
    	//Logins and saves the cookies for future use
    	FileRetrieve::postAndRetrieve("http://www.torrentleech.org/user/account/login/",$postData,"cookies/torrentleech.txt");
    }

    public function rssTPB($feed, $update)
    {
		$this->printRssHeader();
        if($update)
        {
	        $rss = fetch_rss($feed);
	        //TODO: save to a seperate rss file to be able to rescan failed items
	        if($rss !== false)
	        {
	        	$timeToQuit = false;
		        foreach($rss->items as $item)
		        {
		        	$action = $this->actionForRssMovie($item['link'],$item['title']);
		        	switch($action)
					{
						case DOWNLOAD:
		        			//New movie
		            		$rssMovie = new RssMovie($item['link'],$item['title']);
		            		//First try to get the IMDB from the description at the comment page (i.e the NFO on the web)
		            		$rssMovie->getImdbIdFromTPB($item);
		            		$this->handleNewRssMovie($rssMovie);
	    					break;
						case EXISTED:
							$timeToQuit = true;
							break;
					}
					if($timeToQuit)
						break;
		        }
	        }
        }
		$this->printRssMoviesFromDatabase();
		$this->printRssFooter();
    }

    private function actionForRssMovie($link,$title)
    {
    	//TODO: remove this check
    	if(is_file($link))
    	{
    		$file = file($link);
    		if($file !== false && returnarraykey($file, "<h1>An error occurred</h1>") !== false)
    			return REDOWNLOAD_TORRENT_FILE;
    	}

    	global $dbh;
    	$rssMovie = $dbh->getRssMovie($link);
        if($rssMovie === false)
        {
        	$rssMovie = $dbh->getFailedRssMovieLink($link);
        	if($rssMovie === false)
        	{
        		$allowed = true;
        		//Ignore collections etc.
        	    foreach($this->ignoredReleases as $word)
			    {
			    	$index = stripos($title,$word);
			    	if($index !== false)
			    	{
			    		//makes sure there are seperators sorrounding the reserved word
			    		if($index-1 > 0) //The reserved word needs to have something before it
			    		{
			    			if(!in_array(substr($title,$index-1,1),$this->seperators))
			    				$index = false;
			    		}
			    		else
			    			$index = false;
			    	}
			    	if($index !== false)
			    	{
			    		$allowed = false;
			    		break;
			    	}
			    }
			    //Removes possible trailers from the results
			    $year = stripReleaseInfoFromTitle($title,true);
			    if($year !== false)
			    {
			    	$yearPos = strpos($title, $year);
			    	if(strpos($title, "Trailer",$yearPos) !== false)
			    		$allowed = false;
			    }

        		if($allowed && !tvShowInfoFromFullFilePath($link.$title,0))
        			return DOWNLOAD;
        		else
        		{
        			Logger::logNotAllowedRSSMovie($title);
        			return DISALLOWED;
        		}
        	}
        	else
        		return HAS_FAILED_BEFORE;
        }
        else
        	return EXISTED;
    }

    private function printRssHeader()
    {
    	Logger::disableEcho();
    	error_reporting(E_ERROR);

        header("Content-type: text/xml");
        echo "<?xml version='1.0' encoding='UTF-8'?>".PHP_EOL."
        <rss version='2.0'>".PHP_EOL."
        <channel>".PHP_EOL."
        <title>HD movies</title>".PHP_EOL."
        <ttl>1800</ttl>
        <link>".htmlentities($_ENV['pathToXbmc2web'].'?page=mineTL&update=1&r=1')."</link>".PHP_EOL."
        <description>Torrents</description>".PHP_EOL."
        <language>en-us</language>".PHP_EOL;
    }

    //Also exits the script
    private function printRssFooter()
    {
        echo "</channel>".PHP_EOL."</rss>".PHP_EOL;
    }

    private function printRssMoviesFromDatabase()
    {
    	global $dbh;
    	$regular = $dbh->getRegularHDRSSmoviesNotDownloaded();
    	$fullHd = $dbh->getlatestFullHDMovies();
        $rssmovies = array_merge($regular,$fullHd);
        foreach($rssmovies as $rssMovie)
        {
        	if(is_a($rssMovie,"RssMovie"))
        	{
        		if(!$rssMovie->looksLikeAGoodMovie())
        		{
	        		//Update the votes count for old movies that may be interesting when people have voted
	        		$updateDate = strtotime("+ ".($rssMovie->nrOfChecks+1).' week',strtotime($rssMovie->lastCheck));
	        		if($updateDate !== false)
	        		{
	        			$now = strtotime(date('Y-m-d H:i:s'));
	        			if($now > $updateDate && !$rssMovie->looksLikeAGoodMovie())
	        			{
	        				MovieMiner::getBasicInfo($rssMovie,true);
	        				$rssMovie->nrOfChecks++;
	        				$rssMovie->lastCheck = date('Y-m-d H:i:s');
	        				$dbh->addProduction($rssMovie);
	        				$dbh->addRSSMovie($rssMovie);
	        			}
	        		}
        		}
        		if($rssMovie->looksLikeAGoodMovie())
        		{
		            $rssMovie->put();
		            //Makes sure that the RSS datarow is updated
		            $dbh->markRssMovieAsDownloaded($rssMovie);
        		}
        	}
        }
    }

    public function handleNewRssMovie($rssMovie)
    {
    	global $dbh;

    	//Makes sure the Movie gets a nice looking title without searching the interwebs for the title
    	$rssMovie->getTitle();

    	if(!$rssMovie->hasProperIMDBNumber()) //Fall back to an IMDB search
    	{
    		if($rssMovie->getImdbId($rssMovie->filenameExludingFileType) == MULTIPLE_MATCHES)
    		{
    			$dbh->logFailedRssMovieLink($rssMovie->link);
    			Logger::logIgnoredRSSMovie($rssMovie, "Multiple IMDB links found in NFO");
    			return;
    		}
    	}

    	if($rssMovie->hasProperIMDBNumber())
    	{
    		$movie = $dbh->getProductionByIMDB($rssMovie->imdb);
    		if($movie === false)
    		{
    			//Speeds up the filtering of bad movies (low rating)
    			//MovieMiner::getBasicInfo($rssMovie,true);
    			//Fetch some more info about the movie, now that we know that it is of high quality

    			//if(!$rssMovie->haveBasicIMDBinfo)
    			$rssMovie->getImdbInfo("");

    			if($rssMovie->badResult)
    				Logger::logNotAllowedRSSMovie($rssMovie->getDisplayTitle());
    			else
    			{
    				//A new movie, we are allowed to demand good stuff right?
    				if($rssMovie->looksLikeAGoodMovie())
    				{
    					if($rssMovie->fullHD())
    					{
    						//if($rssMovie->haveBasicIMDBinfo)
    						//	$rssMovie->getImdbInfo("");
    						//Maybe the new info from IMDB says that it isn't good anymore?
    						//if($rssMovie->looksLikeAGoodMovie())
    						$rssMovie->downloaded = true;
    					}
    				}
    				if($rssMovie->downloaded == false)
    					$rssMovie->type = IGNORED_RSSMOVIE;

    				$dbh->addProduction($rssMovie);
	    			$rssMovie->addTorrentFilesToDb();
    			}
    		}
    		else
    		{
    			$bestQuality = 0;
    			foreach($movie->files as $file)
    			{
    				if($file->width > $bestQuality)
    					$bestQuality = $file->width;
    			}
    			$newWidth = $rssMovie->files[0]->width;
    			$rssMovie->copyValuesFrom($movie);
    			//We add a little to the current best quality because a movie that is less than 80px wider isn't that interesting
    			if($newWidth > $bestQuality+80 && (!is_a($movie, "RssMovie") || $movie->looksLikeAGoodMovie() || $rssMovie->looksLikeAGoodMovie()))
    			{
    				//We already had the movie, but this new one is of better quality
    				$rssMovie->downloaded = true;
    				$dbh->removeRegularHDVersionOfRssMovie($movie);
    				$rssMovie->id = $movie->id;
    				$dbh->addProduction($rssMovie);
    				$dbh->addRSSMovie($rssMovie);
    				$rssMovie->addTorrentFilesToDb();
    			}
    			else if($dbh->getProductionIdForRssLink($rssMovie->link) === false)
    			{
    				$dbh->addLinkToRSSMovie($rssMovie->link,$movie);
    				Logger::logIgnoredRSSMovie($rssMovie, "Already had a movie with sufficient quality");
    				$rssMovie->addTorrentFilesToDb();
    			}
    		}
    	}
    	else
    		$dbh->logFailedRssMovieLink($rssMovie->link);

    }
}
?>
