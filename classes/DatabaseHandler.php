<?php
class DatabaseHandler
{
	var $db_prfx = "";
	var $mysqli;
	 
	function __construct()
	{
		global $cfg;
		$this->mysqli = new mysqli($cfg['db_host'],$cfg['db_user'],$cfg['db_pass'], $cfg['db_name']);
		if (mysqli_connect_errno())
		{
			printf("Connection to DB failed: %s\n", mysqli_connect_error());
			exit();
		}
	}
	function __destruct()
	{
	}
	function mysql_real_escape_array($array)
	{
		if(is_array($array))
		{
			$new = array();
			foreach($array as $key => $var)
			{
				if(is_array($var))
				$new[$key] = $this->mysql_real_escape_array($var);
				else
				$new[$key] = $this->mysqli->real_escape_string($var);
			}
			return $new;
		}
		else return $this->mysqli->real_escape_string($array);
	}
	function getGenre($id)
	{
		$stmt = $this->mysqli->prepare("SELECT genre FROM genre WHERE id = ? LIMIT 1");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result($genre);
		$stmt->fetch();
		$stmt->close();
		return $genre;
	}
	function isFetchedWithHd($showname,$season,$episode)
	{
		$stmt = $this->mysqli->prepare("SELECT * FROM rssitem WHERE showname = ? AND season = ? AND episode = ? AND hd > 0 LIMIT 1");
		$stmt->bind_param('sii', $showname,$season, $episode);
		$stmt->execute();
		$stmt->bind_result($hd);
		$stmt->store_result();
		$stmt->fetch();
		$num = $stmt->num_rows();
		$stmt->close();
		return ($num == 1);
	}
	function insertRssItem($link, $hd, $season, $episode, $date, $showname, $filename)
	{
		$stmt = $this->mysqli->prepare("INSERT INTO rssitem (link,hd,season,episode,date,showname,filename) VALUES (?,?,?,?,?,?,?)");
		$stmt->bind_param('siiisss', $link, $hd, $season, $episode, $date, $showname, $filename);
		$stmt->execute();
		//print_r($stmt->result_metadata());
		$stmt->close();
	}

	function getRegularHDRSSmoviesNotDownloaded()
	{
		$rssmovies = array(); // AND timeReleased >= date_sub( current_date( ) , INTERVAL 2 WEEK)  AND timeReleased <= date_add( current_date( ) , INTERVAL 3 WEEK)
		$stmt = $this->mysqli->prepare("SELECT link,releaseName,timeReleased,idProduction,lastCheck,nrOfChecks FROM rssmovie INNER JOIN production ON production.id = rssmovie.idProduction WHERE production.type != 1 AND fullHD = 0 AND manuallyAdded = 0");
		$stmt->execute();
		$stmt->bind_result($link, $releaseName, $timeReleased, $idProduction,$lastCheck,$nrOfChecks);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$rssMovie = $this->getProduction($idProduction,true,true);
			$rssMovie->link = $link;
			$rssMovie->releaseName = $releaseName;
			$rssMovie->timeReleased = $timeReleased;
			$rssMovie->lastCheck = $lastCheck;
			$rssMovie->nrOfChecks = $nrOfChecks;
			$rssmovies[] = $rssMovie;
		}
		$stmt->close();
		return $rssmovies;
	}
	function deleteRSSMovies()
	{
		$stmt = $this->mysqli->prepare("DELETE FROM production WHERE type = 3 OR type = 4");
		$stmt->execute();	
		
		$stmt = $this->mysqli->prepare("DELETE FROM rssmovielinks WHERE 1");
		$stmt->execute();
	}
	
	function getFailedRssMovieLink($link)
	{
		$stmt = $this->mysqli->prepare("SELECT lastCheck FROM failedrssmovielinks WHERE link = ? LIMIT 1");
		$stmt->bind_param('s', $link);
		$stmt->execute();
		$stmt->bind_result($lastCheck);
		$stmt->store_result();
		while($stmt->fetch())
		{
			return $lastCheck;
		}
		$stmt->close();
		return false;	
	}
	
	function logFailedRssMovieLink($link)
	{
		$date = date('Y-m-d H:i:s');
		$stmt = $this->mysqli->prepare("INSERT INTO failedrssmovielinks (link,lastCheck) VALUES (?,?)");
		$stmt->bind_param('ss', $link, $date);
		$stmt->execute();
		$stmt->close();
	}
	
	function getlatestFullHDMovies()
	{
		$rssmovies = array();
		$stmt = $this->mysqli->prepare("SELECT rssmovie.link,rssmovie.releaseName,rssmovie.timeReleased,rssmovie.idProduction,rssmovie.manuallyAdded FROM rssmovie INNER JOIN production ON production.id = rssmovie.idProduction WHERE production.type != 1 AND ((rssmovie.fullHD = 1 AND rssmovie.downloaded = 1 AND rssmovie.timeReleased >= date_sub( current_date( ) , INTERVAL 150 WEEK)) OR rssmovie.manuallyAdded = 1)");
		$stmt->execute();
		$stmt->bind_result($link, $releaseName, $timeReleased, $idProduction,$manuallyAdded);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$rssMovie = $this->getProduction($idProduction,true,true);
			$rssMovie->link = $link;
			$rssMovie->releaseName = $releaseName;
			$rssMovie->timeReleased = $timeReleased;
			$rssMovie->manuallyAdded = $manuallyAdded;
			$rssmovies[] = $rssMovie;
		}
		$stmt->close();
		return $rssmovies;
	}
	
	function manuallyAddRssMovie($rssMovie)
	{
		if($rssMovie->type == IGNORED_RSSMOVIE)
		{
			$stmt = $this->mysqli->prepare("UPDATE rssmovie SET manuallyAdded = 1, downloaded = 1 WHERE idProduction = ?");
			$stmt->bind_param('i', $rssMovie->id);
			$stmt->execute();
			$stmt->close();	
			
			$stmt = $this->mysqli->prepare("UPDATE production SET type = ".RSSMOVIE." WHERE id = ?");
			$stmt->bind_param('i', $rssMovie->id);
			$stmt->execute();
			$stmt->close();
		}
	}

	function markRssMovieAsDownloaded($rssMovie)
	{
		$stmt = $this->mysqli->prepare("UPDATE rssmovie SET downloaded = 1 WHERE link = ?");
		$stmt->bind_param('s', $rssMovie->link);
		$stmt->execute();
		$stmt->close();
		
		$stmt = $this->mysqli->prepare("UPDATE production SET type = ".RSSMOVIE." WHERE type = ".IGNORED_RSSMOVIE." AND id IN(SELECT idProduction FROM rssmovie WHERE link = ?)");
		$stmt->bind_param('s', $rssMovie->link);
		$stmt->execute();
		$stmt->close();
	}
	
	//Happens when the rss movie has been downloaded and scanned into the library
	function markRssMovieAsLibraryMovie($rssMovie)
	{
		$stmt = $this->mysqli->prepare("UPDATE production SET type = ".MOVIE." WHERE (type = ".RSSMOVIE." OR type = ".IGNORED_RSSMOVIE.") AND id = ?");
		$stmt->bind_param('i', $rssMovie->id);
		$stmt->execute();
		$stmt->close();
	}
	
	//Happens when the rss movie has been downloaded and scanned into the library
	function setTypeForProduction($prod)
	{
		$stmt = $this->mysqli->prepare("UPDATE production SET type = ? WHERE id = ?");
		$stmt->bind_param('ii', $prod->type, $prod->id);
		$stmt->execute();
		$stmt->close();
	}

	function removeRegularHDVersionOfRssMovie($rssMovie)
	{
		$stmt = $this->mysqli->prepare("DELETE FROM rssmovie WHERE fullHD = 0 AND idProduction = ?");
		$stmt->bind_param('i', $rssMovie->id);
		$stmt->execute();
		$stmt->close();
	}

	function getRssMovie($link)
	{
		$rssMovie = false;
		$stmt = $this->mysqli->prepare("SELECT releaseName,timeReleased,idProduction FROM rssmovie WHERE link = ? LIMIT 1");
		$stmt->bind_param('s', $link);
		$stmt->execute();
		$stmt->bind_result($releaseName, $timeReleased, $idProduction);
		$stmt->store_result();
		$stmt->fetch();
		if($stmt->num_rows != 0)
		{
			$rssMovie = $this->getProduction($idProduction,false);
			if($rssMovie !== false)
			{
				$rssMovie->link = $link;
				$rssMovie->releaseName = $releaseName;
				$rssMovie->timeReleased = $timeReleased;
			}
		}
		$stmt->close();
		
		if($rssMovie === false)
		{
			$id = $this->getProductionIdForRssLink($link);
			if($id !== false)
			{
				$rssMovie = $this->getProduction($id,false);
				if($rssMovie !== false)
					$rssMovie->link = $link;
			}
		}
		return $rssMovie;
	}
	
	function getProductionIdForRssLink($link)
	{
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM rssmovielinks WHERE link = ? LIMIT 1");
		$stmt->bind_param('s', $link);
		$stmt->execute();
		$stmt->bind_result($idProduction);
		$stmt->store_result();
		$stmt->fetch();
		if($stmt->num_rows != 0)
			return $idProduction;
			
		return false;
	}
	
	function addLinkToRSSMovie($link,$movie)
	{
		$stmt = $this->mysqli->prepare("INSERT INTO rssmovielinks (idProduction, link) values (?,?)");
		$stmt->bind_param('is',$movie->id,$link);
		$stmt->execute();
		$stmt->close();
	}
	
	function addFileInTorrent($movie,$filepath)
	{
		$stmt = $this->mysqli->prepare("INSERT INTO filesintorrents (idProduction, path) values (?,?)");
		$stmt->bind_param('is',$movie->id,$filepath);
		$stmt->execute();
		$stmt->close();
	}
	function lookForMatchingProductionForATorrentFile($movie)
	{
		$pathToLookFor = $movie->filePathForFirstFile();
		$stmt = $this->mysqli->prepare("SELECT filesintorrents.path,movie.imdb FROM filesintorrents INNER JOIN movie ON movie.idProduction = filesintorrents.idProduction");
		//$path = '%'.($movie->filePathForFirstFile());
		//$stmt->bind_param("s", $path);
		$stmt->execute();
		$stmt->bind_result($path,$imdb);
		$stmt->store_result();
		while($stmt->fetch())
		{
			if(strpos($pathToLookFor,$path) !== false)
			{
				$movie->imdb = $imdb;
				$movie->torrentIMDB = $imdb;
				break;
			}
		}
		$stmt->close();		
	}

	function removeSDversionsOfRssItem($showname,$season,$episode)
	{
		$stmt = $this->mysqli->prepare("DELETE FROM rssitem WHERE season = ? AND episode = ? AND showname = ? AND hd = 0");
		$stmt->bind_param('iis', $season, $episode, $showname);
		$stmt->execute();
		$stmt->close();
	}
	function getMax7DaysOldSDversions()
	{
		$rssitems = array();
		$stmt = $this->mysqli->prepare("SELECT link,hd,season,episode,date,showname,filename FROM rssitem WHERE hd = 0 AND date <= date_sub( current_date( ) , INTERVAL 1 WEEK)  AND date >= date_sub( current_date( ) , INTERVAL 2 WEEK)");
		$stmt->execute();
		$stmt->bind_result($link, $hd, $season, $episode, $date, $showname,$filename);
		$stmt->store_result();
		while($stmt->fetch())
		$rssitems[] = new RssItem($link, $hd, $season, $episode, $date, $showname,$filename);
		$stmt->close();
		return $rssitems;
	}
	function getStudio($id)
	{
		$stmt = $this->mysqli->prepare("SELECT studio FROM studio WHERE id = ? LIMIT 1");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result($studio);
		$stmt->fetch();
		$stmt->close();
		return $studio;
	}
	function getPerson($id)
	{
		if(isset($id) && is_numeric($id))
		{
			$stmt = $this->mysqli->prepare("SELECT name FROM person WHERE id = ? LIMIT 1");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->bind_result($name);
			$stmt->store_result();
			$stmt->fetch();
			if($stmt->num_rows == 0)
			$name = false;
			$stmt->close();
			return $name;
		}
		else
		return false;
	}

	function getPersonInfo($id,$basic = true)  /* Get all info about an actor */
	{
		$fullPerson = new FullPerson();
		$fullPerson->id = $id;
		$stmt = $this->mysqli->prepare("SELECT person.name,person.bio,person.dob,person.birthplace,person.gender FROM person WHERE person.id = ?") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$id);
		$stmt->execute();
		$stmt->bind_result($fullPerson->name, $fullPerson->bio, $fullPerson->dob, $fullPerson->birthplace, $fullPerson->gender);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0)
		$fullPerson = false;
		else
		{
			//Photos for the person
			$stmt = $this->mysqli->prepare("SELECT id,path FROM personphoto WHERE idPerson = ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$id);
			$stmt->execute();
			$stmt->bind_result($photoId, $path);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$fullPerson->photos[] = new PersonPhoto($path,$id,$photoId);
			}
			$stmt->close();
			 
			//Acting roles for the person
			$stmt = $this->mysqli->prepare("SELECT acting.idProduction,acting.role FROM acting WHERE acting.idPerson = ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$id);
			$stmt->execute();
			$stmt->bind_result($productionId, $role);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$acting = new Acting($fullPerson->name,$role);

				if(!$basic)
				{
					$acting->production = $this->getProduction($productionId,true);
					$acting->production->sortData = $role;
				}
				else
					$acting->production = $productionId;
				 
				$fullPerson->acting[] = $acting;
			}
			$stmt->close();
			 
			//Director positions
			$stmt = $this->mysqli->prepare("SELECT directing.idProduction FROM directing WHERE directing.idPerson = ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$id);
			$stmt->execute();
			$stmt->bind_result($productionId);
			$stmt->store_result();
			while($stmt->fetch())
			{
				if($basic)
					$fullPerson->directing[] = $id;
				else
				{
					$production = $this->getProduction($productionId,true);
					$production->sortData = $role;
					$fullPerson->directing[] = $production;
				}
			}
			$stmt->close();
			 
			//Writer positions
			$stmt = $this->mysqli->prepare("SELECT writing.idProduction FROM writing WHERE writing.idPerson = ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$id);
			$stmt->execute();
			$stmt->bind_result($productionId);
			$stmt->store_result();
			while($stmt->fetch())
			{
				if($basic)
					$fullPerson->writing[] = $id;
				else 
				{
					$production = $this->getProduction($productionId,true);
					$production->sortData = $role;
					$fullPerson->writing[] = $production;
				}
			}
			$stmt->close();
		}
		return $fullPerson;
	}

	function getTitle($id)
	{
		$stmt = $this->mysqli->prepare("SELECT title FROM production WHERE id = ? LIMIT 1");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result($title);
		$stmt->fetch();
		$stmt->close();
		return $title;
	}
	function actingInShows($actorId) /* Get shows an actor stars in */
	{
		$titles = array();
		//Get tvshows were actor is listed in the main cast
		$stmt = $this->mysqli->prepare("
            SELECT 
                production.id,
                production.title,
                acting.role,
                (SELECT count(episode.idProduction) FROM acting AS a JOIN episode ON a.idProduction = episode.idProduction WHERE episode.idTvshow = production.id AND acting.idPerson = ? GROUP BY episode.idTvshow)
            FROM acting
            JOIN production ON production.id = acting.idProduction
            WHERE 
                production.type = 0 
                AND 
                acting.idPerson = ?
            GROUP BY production.id 
            ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('ii', $actorId,$actorId);
		$stmt->execute();
		$stmt->bind_result($idTvshow,$title,$role,$nr);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$titles[$idTvshow][0] = $title;
			$titles[$idTvshow][1] = $role;
			$titles[$idTvshow][2] = $nr;
		}
		$stmt->close();
		//Get tvshows were actor is listed in a episode
		$stmt = $this->mysqli->prepare("
            SELECT 
                production.id,
                production.title,
                acting.role,
                count(episode.idProduction)
            FROM acting
            JOIN episode ON episode.idProduction = acting.idProduction 
            WHERE acting.idPerson = ?
            GROUP BY episode.idTvshow 
            ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));//            JOIN production ON production.id = episode.idTvshow
		$stmt->bind_param('i', $actorId);
		$stmt->execute();
		$stmt->bind_result($idTvshow,$title,$role,$nr);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$titles[$idTvshow][0] = $title;
			$titles[$idTvshow][1] = $role;
			$titles[$idTvshow][2] = $nr;
		}
		$stmt->close();
		return $titles;
	}
	function genresInShows()  /* Get shows genres */
	{
		$genres = array();
		$stmt = $this->mysqli->prepare("SELECT productiongenre.idGenre,genre.genre,count(productiongenre.idGenre) FROM productiongenre JOIN genre ON genre.id = productiongenre.idGenre WHERE EXISTS(SELECT idProduction FROM tvshow WHERE idProduction = productiongenre.idProduction) GROUP BY productiongenre.idGenre ORDER BY genre.genre ASC") or die(mysqli_error($this->mysqli));
		$stmt->execute();
		$stmt->bind_result($id, $genre, $count);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$genres[$id][0] = $genre;
			$genres[$id][1] = $count;
		}
		$stmt->close();
		return $genres;
	}
	function moviesWithRole($role)  /* Get titles that has a specific role in the cast list */
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT production.id,production.title,person.id,person.name FROM production JOIN acting ON production.id = acting.idProduction JOIN person ON acting.idPerson = person.id WHERE acting.role = ? && type = 1 ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('s',$role);
		$stmt->execute();
		$stmt->bind_result($id, $title,$pid,$pname);
		$stmt->store_result();
		$i = 0;
		while($stmt->fetch())
		{
			$titles[$i][0] = $title;
			$titles[$i][1] = $pid;
			$titles[$i][2] = $pname;
			$titles[$i][3] = $id;
			$i++;
		}
		$stmt->close();
		return $titles;
	}
	function getKeyword($id)
	{
		$r = '';
		$stmt = $this->mysqli->prepare("SELECT keyword FROM keyword WHERE keyword.id = ?") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$id);
		$stmt->execute();
		$stmt->bind_result($word);
		$stmt->store_result();
		while($stmt->fetch())
		$r = $word;
		$stmt->close();
		return $r;
	}
	function showsInGenre($genreId)  /* Get shows that has a specific genre */
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN productiongenre ON production.id = productiongenre.idProduction WHERE production.type = 0 AND productiongenre.idGenre = ? ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$genreId);
		$stmt->execute();
		$stmt->bind_result($id, $title);
		$stmt->store_result();
		while($stmt->fetch())
		$titles[$id] = $title;
		$stmt->close();
		return $titles;
	}
	private function orderbyTable($orderByNumber)
	{
		switch($orderByNumber)
		{
			case 1:
				return "production.title";
			case 2:
				return "production.rating";
			case 3:
				return "movie.year";
			case 4:
				return "production.votes";
			case 5:
				return "production.id";
			case 6:
				return "movie.runtime";
			case 7:
				return "movie.top250";
			case 8:
				return "file.timeAdded";
			case 9:
				return "file.width";
		}
		return "production.title";	
	}
	/* Example usage:
	 * listProductions('production.title','ASC','smallicons');
	 * listProductions('production.title','ASC','fanart',MOVIE,'productiongenre.idGenre','=', $genreId);
	 * listProductions('production.title','ASC','fanartPreview',MOVIE,'productiongenre.idGenre','=',$genreId);
	 * listProductions('production.title','ASC','list',MOVIE,array('keywordproduction.idKeyword','movie.year'),array('=','='),array($keywordId,$year));
	 * listProductions('episode.season,episode.episode','ASC','list',EPISODE,'episode.idTvshow','=',$tvShow);
	 */
	function listProductions(	$orderby,$order,
	$listView,$type = MOVIE,
	$withCriteria = false,$andOperator = false,$andValue = false, $fetchSortData = false)
	{
		if(is_numeric($orderby))
			$orderby = $this->orderbyTable($orderby);
		$titles = array();

		//The only sorting directions available
		if($order != 'ASC' && $order != 'DESC')
			return $titles;

		$tables = array('production');
		$parameters = new DatabaseStatementBindParameter();
		$result = new DatabaseStatementBindResult();
			
		$joinClause 	= '';
			
		$whereClause 	= ' production.type = ?';
		$parameters->addParameter('production.type', DBField::$INTEGER, $type);

		$fields = 'production.id,production.title';
		$result->addParameter('resultId');
		$result->addParameter('resultTitle');
		
		if($fetchSortData !== false)
		{
			$fields .= ','.$fetchSortData;
			$result->addParameter('sortField');
		}

		if($type == MOVIE || $type == RSSMOVIE || $type == IGNORED_RSSMOVIE)
		{
			$fields .= ',movie.year';
			$result->addParameter('resultYear');
			$tables [] = 'movie';
			$joinClause .= ' INNER JOIN movie ON production.id = movie.idProduction';
		}
		else if($type == TVSHOW)
		{
			$tables [] = 'tvshow';
			$joinClause .= ' INNER JOIN tvshow ON production.id = tvshow.idProduction';
		}
		else if($type == EPISODE)
		{
			$tables [] = 'episode';
			$fields .= ',episode.season,episode.episode';
			$result->addParameter('resultSeason');
			$result->addParameter('resultEpisode');
			$joinClause .= ' INNER JOIN episode ON production.id = episode.idProduction';
		}
		
		if($type == RSSMOVIE || $type == IGNORED_RSSMOVIE)
		{
			$tables [] = 'rssmovie';
			$joinClause .= ' INNER JOIN rssmovie ON production.id = rssmovie.idProduction';
		}
		 
		if($listView == SMALLICONS || $listView == FLEXICONS)
		{
			$tables [] = 'photo';
			$joinClause .= ' LEFT JOIN photo ON production.id = photo.idProduction';
			$fields .= ',photo.path';
			$result->addParameter('resultPhoto');
		}
		else if($listView == FANART)
		{
			$tables [] = 'fanart';
			$joinClause .= ' LEFT JOIN fanart ON production.id = fanart.idProduction';
			$fields .= ',fanart.path';
			$result->addParameter('resultFanart');
		}
		else if($listView == 'fanartPreview')
		{
			$tables [] = 'fanart';
			$tables [] = 'previewfanart';
			$joinClause .= ' LEFT JOIN fanart ON production.id = fanart.idProduction RIGHT JOIN previewfanart ON fanart.id = previewfanart.idFanart';
			$fields .= ',previewfanart.path';
			$result->addParameter('resultFanart');
		}

		//Support for a number of fields with a specified value
		if(is_array($withCriteria))
		{
			foreach($withCriteria as $index => $criteria)
			{
				//make sure the where clause can search the table for the critera's value
				$criteriaTable = substr($criteria,0,strpos($criteria,'.'));
				if(!in_array($criteriaTable,$tables))
				{
					$joinClause .= ' INNER JOIN '.$criteriaTable.' ON '.$criteriaTable.'.idProduction = production.id';
					$tables [] = $criteriaTable;
				}
				 
				$whereClause .= ' AND '.$criteria;
				if($andOperator[$index] == 'LIKE')
				{
					$whereClause .= " LIKE ?";
					$parameters->addParameter($index, DBField::getType($criteria), '%'.$andValue[$index].'%');
				}
				else
				{
					$whereClause .= ' '.$andOperator[$index].' ?';
					$parameters->addParameter($index, DBField::getType($criteria), $andValue[$index]);
				}
			}
		}
		else if($andValue !== false)
		{
			//make sure the where clause can search the table for the critera's value
			$criteriaTable = substr($withCriteria,0,strpos($withCriteria,'.'));
			if(!in_array($criteriaTable,$tables))
			{
				$joinClause .= ' INNER JOIN '.$criteriaTable.' ON '.$criteriaTable.'.idProduction = production.id';
				$tables [] = $criteriaTable;
			}
			
			$whereClause .= ' AND '.$withCriteria;
			if($andOperator == 'LIKE')
			{
				$whereClause .= " LIKE '%?%'";
				$parameters->addParameter($withCriteria, DBField::getType($withCriteria), '%'.$andValue.'%');
			}
			else
			{
				$whereClause .= ' '.$andOperator.' ?';	
				$parameters->addParameter($withCriteria, DBField::getType($withCriteria), $andValue);
			}
		}
		$orderByTable = substr($orderby,0,strpos($orderby,'.'));
		if(!in_array($orderByTable,$tables))
		{
			$joinClause .= ' INNER JOIN '.$orderByTable.' ON '.$orderByTable.'.idProduction = production.id';
			$tables [] = $orderByTable;
		}
		$productions = array();
		//TODO: how to remove group by without speed penalties ie  GROUP BY production.id 
		$query = "SELECT ".$fields." FROM production".$joinClause." WHERE".$whereClause." ORDER BY ".$orderby." ".$order;
		$stmt = $this->mysqli->prepare($query) or die(mysqli_error($this->mysqli));
		$parameters->bindParams($stmt);
		$stmt->execute();
		$result->bindResult($stmt);
		$stmt->store_result();

		while($stmt->fetch())
		{
			$prodId = $result->get('resultId');
			if(!isset($productions[$prodId]))
			{				
				$prod = ProductionFactory::constructProduction($type);
				if($type == MOVIE || $type == RSSMOVIE || $type == IGNORED_RSSMOVIE)
					$prod->year = $result->get('resultYear');
				else if($type == EPISODE)
				{
					$prod->season = $result->get('resultSeason');
					$prod->episode = $result->get('resultEpisode');
				}
				//Shared variables
				$prod->id = $prodId;
				$prod->title = $result->get('resultTitle');
				 
				if("" != $result->get('resultPhoto'))
					$prod->photos[] = $result->get('resultPhoto');
					
				if("" != $result->get('resultFanart'))
					$prod->fanart[] = $result->get('resultFanart');
					
				if($fetchSortData !== false)
					$prod->setSortData($result->get('sortField'));
	
				$titles[] = $prod;
				$productions[$prodId] = true;
			}
		}
		$stmt->close();
		return $titles;
	}
	function listProductionsWithCountryInOtherTitles($orderby,$order, $listView,$country)
	{
		$productions = $this->listProductions($orderby,$order,$listView,MOVIE);
		$withCountry = array();
		foreach($productions as $prod)
		{
			$this->getOtherTitlesForProduction($prod);
			if($prod->hasCountryInOtherTitles($country))
			{
				$prod->title = $prod->prefixOtherTitleForCountryBeforeOriginalTitle($country);
				$withCountry [] = $prod;
			}
		}
		return $withCountry;
	}

	/* Example usage:
	 * //Year
	 * listObjects(	'movie.year','DESC',
	 * 				'movie.year', 'movie.year',
	 * 				'movie','movie.year',MOVIE);
	 * //Keyword
	 * listObjects(	'keyword.keyword','ASC',
	 * 				'keyword.id','keyword.keyword',
	 * 				'keywordproduction','keywordproduction.idKeyword',MOVIE);
	 * //Studio
	 *
	 * //Genre
	 * listObjects(	'genre.genre','DESC',
	 * 				'genre.id', 'genre.genre',
	 * 				'productiongenre','productiongenre.idGenre',MOVIE);
	 */
	function listObjects(	$orderby, $order,
	$columnIdentiferForObject, $columnToSelect,
	$tableToCountObjectsIn, $linkColumnToObjectTable,$type = MOVIE)
	{
		if(is_numeric($orderby))
			$orderby = $this->orderbyTable($orderby);
		$listObjects = array();

		//The only sorting directions available
		if($order != 'ASC' && $order != 'DESC')
		return $listObjects;
		 
		//$resultId = 0;

		$selectFromTable = substr($columnToSelect,0,strpos($columnToSelect,'.'));
		$selectColumn = substr($columnIdentiferForObject,strpos($columnIdentiferForObject,'.')+1);
		$linkColumn = substr($linkColumnToObjectTable,strpos($linkColumnToObjectTable,'.')+1);

		$result = new DatabaseStatementBindResult();
			
		$joinClause 	= '';
		$whereClause 	= ' 1';

		$fields = $columnIdentiferForObject.',';
		$result->addParameter('resultId');

		$fields .= $columnToSelect.',';
		$result->addParameter('resultDisplayName');
			
		$fields .= '(SELECT COUNT(countTable.'.$linkColumn.') FROM '.$tableToCountObjectsIn.' AS countTable WHERE countTable.'.$linkColumn.' = '.$columnIdentiferForObject.')';
		$result->addParameter('resultCount');
			
		/*if($type == MOVIE)
		 $joinClause .= ' INNER JOIN movie ON production.id = movie.idProduction';
		 else if($type == TVSHOW)
		 $joinClause .= ' INNER JOIN tvshow ON production.id = tvshow.idProduction';
		 else if($type == EPISODE)
		 $joinClause .= ' INNER JOIN episode ON production.id = episode.idProduction';
		 else if($type == RSSMOVIE)
		 $joinClause .= ' INNER JOIN rssmovie ON production.id = rssmovie.idProduction';
		 */
		$query = "SELECT ".$fields." FROM ".$selectFromTable.$joinClause." WHERE".$whereClause." GROUP BY ".$columnToSelect." ORDER BY ".$orderby." ".$order;
		$stmt = $this->mysqli->prepare($query) or die(mysqli_error($this->mysqli));
		$stmt->execute();
		$result->bindResult($stmt);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$listObjects[] = new ListObject($result->get('resultId'),$result->get('resultDisplayName'),$result->get('resultCount'));
		}
		$stmt->close();
		return $listObjects;
	}

	function moviesByRole($type = 1)  /* Get all roles */
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT role FROM acting JOIN production ON acting.idProduction = production.id WHERE production.type = ? ORDER BY acting.role ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$type);
		$stmt->execute();
		$stmt->bind_result($role);
		$stmt->store_result();
		while($stmt->fetch())
		$titles[$role] = $role;
		$stmt->close();
		return $titles;
	}
	function getcountry($cid) /* Get country name */
	{
		$stmt = $this->mysqli->prepare("SELECT country FROM country WHERE id = ?") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$cid);
		$stmt->execute();
		$stmt->bind_result($country);
		$stmt->store_result();
		$stmt->fetch();
		$stmt->close();
		return $country;
	}
	function getCountryTitle($country,$title)
	{
		$tit = array();
		$stmt = $this->mysqli->prepare("SELECT originaltitles.title,production.title FROM originaltitles JOIN countrytitles ON countrytitles.idOriginaltitle = originaltitles.id JOIN production ON originaltitles.idProduction = production.id WHERE countrytitles.idCountry = ? AND production.id = ?");
		$stmt->bind_param('ii',$country,$title);
		$stmt->execute();
		$stmt->bind_result($c,$t);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$tit[0] = $c;
			$tit[1] = $t;
		}
		$stmt->close();
		return $tit;
	}
	function movieProductions($type = 1)  /* Get all titles and in object form*/
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT id FROM production WHERE type = ? ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$type);
		$stmt->execute();
		$stmt->bind_result($id);
		$stmt->store_result();
		while($stmt->fetch())
			$titles[$id] = $this->getProduction($id,true,true);
		$stmt->close();
		return $titles;
	}
	function seasonOfShow($show)
	{
		$seasons = array();
		$stmt = $this->mysqli->prepare("SELECT e.season,(SELECT count(s.idProduction) FROM episode AS s WHERE s.idTvshow = {$show} && s.season = e.season) FROM episode AS e WHERE e.idTvshow = ? ORDER BY e.season ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('i',$show);
		$stmt->execute();
		$stmt->bind_result($season, $nrOfEpisodes);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$seasons[$season][0] = 'Season '.$season;
			$seasons[$season][1] = $nrOfEpisodes;
		}
		$stmt->close();
		return $seasons;
	}
	function episodesOfSeason($tvshow,$season)
	{
		$episodes = array();
		$stmt = $this->mysqli->prepare("SELECT episode.episode,production.title FROM episode JOIN production ON production.id = episode.idProduction WHERE episode.idTvshow = ? && episode.season = ? ORDER BY episode.episode ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('ii',$tvshow,$season);
		$stmt->execute();
		$stmt->bind_result($episode, $title);
		$stmt->store_result();
		while($stmt->fetch())
		$episodes[$episode] = $episode.' '.$title;
		$stmt->close();
		return $episodes;
	}
	function showsByTitle()  /* Get all titles */
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT production.id,production.title,(SELECT count(idProduction) FROM episode WHERE idTvshow = production.id) FROM production WHERE type = 0 ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->execute();
		$stmt->bind_result($id, $title,$nrOfEpisodes);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$titles[$id][0] = $title;
			$titles[$id][1] = $nrOfEpisodes;
		}
		$stmt->close();
		return $titles;
	}
	//Specify -1 to get actors in any kind of production
	function actorsInProductions($type = 1)  /* Get actors that has starred in a production by a type */
	{
		$actors = array();
		if($type == 0 || $type == 2)
		$stmt = $this->mysqli->prepare("SELECT idPerson,person.name FROM acting JOIN person ON person.id = acting.idPerson JOIN production ON production.id = acting.idProduction WHERE production.type = 0 OR production.type = 2 GROUP BY acting.idPerson ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
		else if($type == -1)
		$stmt = $this->mysqli->prepare("SELECT idPerson,person.name FROM acting JOIN person ON person.id = acting.idPerson JOIN production ON production.id = acting.idProduction WHERE GROUP BY acting.idPerson ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
		else
		{
			$stmt = $this->mysqli->prepare("SELECT idPerson,person.name FROM acting JOIN person ON person.id = acting.idPerson JOIN production ON production.id = acting.idProduction WHERE production.type = ? GROUP BY acting.idPerson ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$type);
		}
		$stmt->execute();
		$stmt->bind_result($id, $name);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$actors[$id] = $name;
		}
		$stmt->close();
		return $actors;
	}

	/* Get productions an actor stars in */
	function actingInProductions($actorId,$type = 1)
	{
		$titles = array();
		$stmt = $this->mysqli->prepare("SELECT production.id,production.title,acting.role FROM acting JOIN production ON production.id = acting.idProduction WHERE acting.idPerson = ? AND production.type = ? GROUP BY acting.idPerson ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('ii', $actorId,$type);
		$stmt->execute();
		$stmt->bind_result($id,$title,$role);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$titles[$id][0] = $title;
			$titles[$id][1] = $role;
		}
		$stmt->close();
		return $titles;
	}
	function directingProductions($type = 1)  /* Get all directors */
	{
		$directors = array();
		//TVshow or episode
		if($type == 0 || $type == 2)
		$stmt = $this->mysqli->prepare("SELECT person.id,person.name,count(directing.idProduction) FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction WHERE production.type = 0 OR production.type = 2 GROUP BY person.id ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
		//Type of production don't matter
		else if($type == -1)
		$stmt = $this->mysqli->prepare("SELECT person.id,person.name,count(directing.idProduction) FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction GROUP BY person.id ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
		else
		{
			$stmt = $this->mysqli->prepare("SELECT person.id,person.name,count(directing.idProduction) FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction WHERE production.type = ? GROUP BY person.id ORDER BY person.name ASC") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$type);
		}
		$stmt->bind_param('i',$type);
		$stmt->execute();
		$stmt->bind_result($id, $name,$count);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$directors[$id][0] = $name;
			$directors[$id][1] = $count;
		}
		$stmt->close();
		return $directors;
	}
	function productionsDirectedBy($directorId,$type = 1)  /* Get all movies by a director */
	{
		$titles = array();
		//TVshow or episode
		if($type == 0 || $type == 2)
		{
			$stmt = $this->mysqli->prepare("SELECT production.id FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction WHERE directing.idPerson = ? AND production.type = 0 OR production.type = 2 GROUP BY production.id ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$directorId);
		}
		//Type of production don't matter
		else if($type == -1)
		{
			$stmt = $this->mysqli->prepare("SELECT production.id FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction WHERE directing.idPerson = ? GROUP BY production.id ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('i',$directorId);
		}
		else
		{
			$stmt = $this->mysqli->prepare("SELECT production.id FROM directing JOIN person ON directing.idPerson = person.id JOIN production ON production.id = directing.idProduction WHERE directing.idPerson = ? AND production.type = ? GROUP BY production.id ORDER BY production.title ASC") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('ii',$directorId,$type);
		}
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		while($stmt->fetch())
		$titles[$id] = $this->getProduction($productionId,true);
		$stmt->close();
		return $titles;
	}
	function movieSearch($string)
	{
		$oldErrorLevel = error_reporting();
		error_reporting(E_ERROR);
		$start = microtime();
		//divide string by space or dot foreach word
		//search all places and have AND operator between word matches
		$titles = array();
		$spacedivided = explode(' ',$string);
		$words = array();
		foreach($spacedivided as $word)
		{
			$dotdivided = explode('.',$word);
			foreach($dotdivided as $word2)
			{
				if($word2 != '')
				$words[] = $word2;
			}
		}
		$matches = array(array());
		$i = 0;
		$points = array();
		$trace = array();
		//Exact match title
		$stmt = $this->mysqli->prepare("SELECT id,title FROM production WHERE type = 1 AND title LIKE ?") or die(mysqli_error($this->mysqli));
		$stmt->bind_param('s',$string);
		$stmt->execute();
		$stmt->bind_result($id,$title);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$trace[$id]['Exact title match'] += 1000;
			if(isset($points[$id]))
				$points[$id] += 1000;
			else
				$points[$id] = 1000;
		}
		$stmt->close();

		foreach($words as $word)
		{
			//Primary title
			$w = '%'.$word.'%';
			$stmt = $this->mysqli->prepare("SELECT id,title FROM production WHERE type = 1 AND title LIKE ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('s',$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['Title']+=100;
				if(isset($points[$id]))
					$points[$id] += 100;
				else
					$points[$id] = 100;
			}
			$stmt->close();
			//Other titles
			$stmt = $this->mysqli->prepare("SELECT production.id,production.title
                FROM production 
                JOIN originaltitles ON production.id = originaltitles.idProduction 
                WHERE type = 1 AND originaltitles.title LIKE ?") 
			or die(mysqli_error($this->mysqli));
			$stmt->bind_param('s',$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['Other Title']+=50;
				if(isset($points[$id]))
					$points[$id] += 50;
				else
					$points[$id] = 50;
			}
			$stmt->close();
			//Keywords
			$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN keywordproduction ON production.id = keywordproduction.idProduction JOIN keyword ON keywordproduction.idKeyword = keyword.id WHERE type = 1 AND keyword.keyword LIKE ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('s',$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['Keyword']+=1;
				if(isset($points[$id]))
					$points[$id] += 1;
				else
					$points[$id] = 1;
			}
			$stmt->close();
			//persons directing a movie
			$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN directing ON production.id = directing.idProduction JOIN person ON person.id = directing.idPerson WHERE production.type = 1 AND person.name LIKE ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('s',$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['Director']+=5;
				if(isset($points[$id]))
					$points[$id] += 5;
				else
					$points[$id] = 5;
			}
			$stmt->close();
			//persons acting in a movie or a role in the movie
			$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN acting ON production.id = acting.idProduction JOIN person ON person.id = acting.idPerson WHERE production.type = 1 AND (person.name LIKE ? OR acting.role LIKE ?)") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('ss',$w,$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['Acting']+=10;
				if(isset($points[$id]))
				$points[$id] += 10;
				else
				$points[$id] = 10;
			}
			$stmt->close();
			//File details
			$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN file ON production.id = file.idProduction JOIN audiotrack ON audiotrack.idFile = file.id WHERE audiotrack.format LIKE ? OR file.format LIKE ?") or die(mysqli_error($this->mysqli));
			$stmt->bind_param('ss',$w,$w);
			$stmt->execute();
			$stmt->bind_result($id,$title);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$matches[$i][$id] = $title;
				$trace[$id]['File Details']+=1;
				if(isset($points[$id]))
					$points[$id] += 1;
				else
					$points[$id] = 1;
			}
			$stmt->close();
			//Year
			if(is_numeric($word))
			{
				$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN movie ON production.id = movie.idProduction WHERE movie.year = ?") or die(mysqli_error($this->mysqli));
				$stmt->bind_param('i',$word);
				$stmt->execute();
				$stmt->bind_result($id,$title);
				$stmt->store_result();
				while($stmt->fetch())
				{
					$matches[$i][$id] = $title;
					$trace[$id]['Year']+=20;
					if(isset($points[$id]))
						$points[$id] += 20;
					else
						$points[$id] = 20;
				}
				$stmt->close();
			}
			//Plot
			if(is_numeric($word))
			{
				$stmt = $this->mysqli->prepare("SELECT production.id,production.title FROM production JOIN movie ON production.id = movie.idProduction WHERE movie.plot LIKE ?") or die(mysqli_error($this->mysqli));
				$stmt->bind_param('s',$w);
				$stmt->execute();
				$stmt->bind_result($id,$title);
				$stmt->store_result();
				while($stmt->fetch())
				{
					$matches[$i][$id] = $title;
					$trace[$id]['Plot']+=0.5;
					if(isset($points[$id]))
						$points[$id] += 0.5;
					else
						$points[$id] = 0.5;
				}
				$stmt->close();
			}
			$i++;
		}
		//Utför AND operation mellan orden så att alla ord matchar något
		//Varje ord har sitt egna resultat set
		//Gå igenom alla de andra setten och kolla så att de också har objektet
		$titles = $matches[0];
		for($i = 1;$i< count($words);$i++)
		{
			foreach($titles as $id => $title)
				if(!isset($matches[$i][$id]))
				{
					unset($titles[$id]);
					unset($points[$id]);
					unset($trace[$id]);
				}
		}
		//Order by points array
		$prioritezedmovies = array();
		while(count($points) > 0)
		{
			$highestscore = 0;
			$movie = 0;
			foreach($points as $id => $score)
			{
				if($score > $highestscore)
				{
					$movie = $id;
					$highestscore = $score;
				}
			}
			if($movie != 0)
			{
				if(isset($titles[$movie]))
				$prioritezedmovies[] = array($movie,$titles[$movie],$points[$movie]);
				unset($points[$movie]);
			}
		}
		/*foreach($trace as $id => $match)
		 {
		 if(isset($titles[$id]))
		 {
		 echo $titles[$id].':<br>';
		 foreach($match as $m2 => $m)
		 echo $m2.': '.$m.'<br>';
		 }
		 }   */
		$end = microtime()-$start;
		echo $end;
		error_reporting($oldErrorLevel);
		return $prioritezedmovies;
	}
	function getProductionByPathAndFilename($path,$filename)
	{
		//basic info
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM file WHERE path = ? && filename = ?");
		$stmt->bind_param('ss',$path,$filename);
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0)
		return false;
		else
		return $this->getProduction($productionId);
	}
	function hasFileWithPathAndFilename($path,$filename)
	{
		//basic info
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM file WHERE path = ? && filename = ?");
		$stmt->bind_param('ss',$path,$filename);
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0)
		return false;
		else
		return true;
	}

	function getFilePathsAndFiles()
	{
		$container = array();
		$stmt = $this->mysqli->prepare("SELECT path,filename,idProduction FROM file");
		$stmt->execute();
		$stmt->bind_result($path,$filename,$idProduction);
		$stmt->store_result();

		while($stmt->fetch())
			$container [$path.$filename] = $idProduction;
		 
		$stmt->close();
		return $container;
	}
	function getFilesFromDB()
	{
		$container = array();
		$stmt = $this->mysqli->prepare("SELECT id, idProduction, path, filename FROM file");
		$stmt->execute();
		$stmt->bind_result($fileId, $productionId, $path, $filename);
		$stmt->store_result();

		while($stmt->fetch())
		{
			$file = new FFile($path,$filename);
			$file->fileId = $fileId;
			$file->productionId = $productionId;
			$container [] = $file;
		}
		 
		$stmt->close();
		return $container;
	}

	function getProductionByIMDB($imdbId,$basic = true)
	{
		//Is it a movie?
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM movie WHERE imdb = ?");
		$stmt->bind_param('i',$imdbId);
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		
		if($numrows != 0)
			return $this->getProduction($productionId,$basic);
			
		 
		//tvshow?
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM tvshow WHERE imdb = ?");
		$stmt->bind_param('i',$imdbId);
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		
		if($numrows != 0)
			return $this->getProduction($productionId,$basic);
		 
		//episode?
		$stmt = $this->mysqli->prepare("SELECT idProduction FROM episode WHERE imdb = ?");
		$stmt->bind_param('i',$imdbId);
		$stmt->execute();
		$stmt->bind_result($productionId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		
		if($numrows != 0)
			return $this->getProduction($productionId,$basic);
		
		return false;
	}

	function getProduction($productionId, $basic = false, $forceToRSS = false)
	{
		//basic info
		$stmt = $this->mysqli->prepare("SELECT type,title,plot,rating,votes FROM production WHERE id = ?");
		$stmt->bind_param('i',$productionId);
		$stmt->execute();
		$stmt->bind_result($prodtype,$prodtitle,$prodplot,$prodrating,$prodvotes);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0) return false;
		else
		{
			$prod = ProductionFactory::constructProduction($prodtype,$forceToRSS);
			//Set shared variables
			$prod->id = $productionId;
			$prod->title = $prodtitle;
			$prod->plot = $prodplot;
			$prod->rating = $prodrating;
			$prod->votes = $prodvotes;

			if(is_a($prod,"Movie"))
			{
				if(is_a($prod,"RssMovie"))
				{
					$stmt = $this->mysqli->prepare("SELECT link,releaseName,timeReleased,lastCheck,nrOfChecks,id,manuallyAdded FROM rssmovie WHERE idProduction = ? LIMIT 1");
					$stmt->bind_param('i', $prod->id);
					$stmt->execute();
					$stmt->bind_result($link, $releaseName, $timeReleased,$lastCheck,$nrOfChecks,$rssMovieId,$manuallyAdded);
					$stmt->store_result();
					while($stmt->fetch())
					{
						$prod->link = $link;
						$prod->releaseName = $releaseName;
						$prod->timeReleased = $timeReleased;
						$prod->lastCheck = $lastCheck;
						$prod->nrOfChecks = $nrOfChecks;
						$prod->rssMovieId = $rssMovieId;
						$prod->manuallyAdded = $manuallyAdded;
					}
					$stmt->close();
				}

				$stmt = $this->mysqli->prepare("SELECT (SELECT studio FROM studio WHERE id = idStudio),imdb,top250,year,outline,tagline,mpaa,runtime,idStudio FROM movie WHERE idProduction = ?");
				$stmt->bind_param('i',$prod->id);
				$stmt->execute();
				$stmt->bind_result($prod->studio[1],$prod->imdb,$prod->top250,$prod->year,$prod->outline,$prod->tagline,$prod->mpaa,$prod->runtime,$prod->studio[0]);
				$stmt->store_result();
				$stmt->fetch();
				$stmt->close();
				$this->getOtherTitlesForProduction($prod);
			}
			else if(is_a($prod,"Tvshow"))
			{
				$stmt = $this->mysqli->prepare("SELECT premiered,imdb FROM tvshow WHERE idProduction = ?");
				$stmt->bind_param('i',$prod->id);
				$stmt->execute();
				$stmt->bind_result($prod->premiered,$prod->imdb);
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
				$stmt->close();

				//Get all episodes for the tvshow
				$stmt = $this->mysqli->prepare("SELECT idProduction FROM episode WHERE idTvshow = ?");
				$stmt->bind_param('i',$prod->id);
				$stmt->execute();
				$stmt->bind_result($episodeId);
				$stmt->store_result();
				while($stmt->fetch())
				{
					if($basic)
					$prod->episodes[] = $episodeId;
					else
					$prod->episodes[] = $this->getProduction($episodeId,true);
				}
				$stmt->close();

			}
			else if(is_a($prod,"Episode"))
			{
				$stmt = $this->mysqli->prepare("SELECT idTvshow,season,episode,mpaa,aired,runtime,imdb FROM episode WHERE idProduction = ?");
				$stmt->bind_param('i',$prod->id);
				$stmt->execute();
				$stmt->bind_result($prod->idTvshow,$prod->season,$prod->episode,$prod->mpaa,$prod->aired,$prod->runtime,$prod->imdb);
				$stmt->store_result();
				$stmt->fetch();
				$stmt->close();
				$prod->tvshow = $this->getProduction($prod->idTvshow,true);
			}
			/* relationed objects */
			//Files
			$stmt = $this->mysqli->prepare("SELECT id,playcount,path,filename,filesize,duration,format,width,height,ar,writinglibrary,videobitrate,hassubtitle,timeAdded FROM file WHERE idProduction = ?");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$file = new FFile();
			$stmt->bind_result($file->fileId,$file->playcount,$file->path,$file->filename,$file->filesize,$file->duration,$file->format,$file->width,$file->height,$file->ar,$file->writinglibrary,$file->videobitrate,$file->hassubtitle,$file->timeAdded);
			$stmt->store_result();
			while($stmt->fetch())
			{
				$f = new FFile();
				$f->playcount = $file->playcount;
				$f->path = $file->path;
				$f->filename = $file->filename;
				$f->format = $file->format;               //Matroska
				$f->filesize = $file->filesize;                  //GB
				$f->duration = $file->duration;                  //mins
				$f->videobitrate = $file->videobitrate;              //Mbps
				$f->width = $file->width;                     //1920
				$f->height = $file->height;                    //1040
				$f->ar = $file->ar;                        //16/9
				$f->writinglibrary = $file->writinglibrary;
				$f->hassubtitle = $file->hassubtitle;
				$f->timeAdded = $file->timeAdded;
				//Audio tracks
				$stmt2 = $this->mysqli->prepare("SELECT format,formatinfo,channels,bitrate,title,language FROM audiotrack WHERE idFile = ?");
				$stmt2->bind_param('i',$file->fileId);
				$stmt2->execute();
				$audiotrack = new AudioTrack();
				$stmt2->bind_result($audiotrack->format,$audiotrack->formatinfo,$audiotrack->channels,$audiotrack->bitrate,$audiotrack->title,$audiotrack->language);
				$stmt2->store_result();
				while($stmt2->fetch())
				{
					$a = new AudioTrack();
					$a->format = $audiotrack->format;
					$a->formatinfo = $audiotrack->formatinfo;
					$a->channels = $audiotrack->channels;
					$a->bitrate = $audiotrack->bitrate;
					$a->title = $audiotrack->title;
					$a->language = $audiotrack->language;
					$f->audiotracks[] = $a;
				}
				$prod->files[] = $f;
				$stmt2->close();
			}
			$stmt->close();

			//Genres
			$stmt = $this->mysqli->prepare("SELECT genre.id,genre.genre FROM genre JOIN productiongenre ON productiongenre.idGenre = genre.id WHERE productiongenre.idProduction = ?");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($id,$genre);
			$stmt->store_result();
			$i = 0;
			while($stmt->fetch())
			{
				$prod->genres[$i][0] = $id;
				$prod->genres[$i][1] = $genre;
				$i++;
			}
			$stmt->close();

			//Keywords
			$stmt = $this->mysqli->prepare("SELECT keyword.keyword,keyword.id FROM keyword JOIN keywordproduction ON keywordproduction.idKeyword = keyword.id WHERE keywordproduction.idProduction = ? ORDER BY keyword.keyword ASC");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($word,$id);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->keywords[] = new Keyword($word,$id);
			$stmt->close();

			//Photos
			$stmt = $this->mysqli->prepare("SELECT path FROM photo WHERE idProduction = ?");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($photo);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->photos[] = $photo;
			$stmt->close();
			//Fanart
			$stmt = $this->mysqli->prepare("SELECT fanart.path,previewfanart.path FROM fanart LEFT JOIN previewfanart ON fanart.id = previewfanart.idFanart WHERE fanart.idProduction = ?");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($path,$preview);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->fanart[] = new Fanart($path,$preview);
			$stmt->close();

			//Avoids infinite loop
			if($basic)
				return $prod;
			 
			//directors
			$stmt = $this->mysqli->prepare("SELECT person.id FROM directing JOIN person ON person.id = directing.idPerson LEFT JOIN personphoto ON person.id = personphoto.idPerson WHERE directing.idProduction = ? GROUP BY person.id");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($id);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->directors[] = $this->getPersonInfo($id,true);
			$stmt->close();
			//actors
			$stmt = $this->mysqli->prepare("SELECT person.id FROM acting JOIN person ON person.id = acting.idPerson LEFT JOIN personphoto ON person.id = personphoto.idPerson WHERE acting.idProduction = ? GROUP BY person.id,person.name,acting.role ORDER BY acting.id ASC");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($id);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->actors[] = $this->getPersonInfo($id,true);

			$stmt->close();
			//writers
			$stmt = $this->mysqli->prepare("SELECT person.id FROM writing JOIN person ON person.id = writing.idPerson LEFT JOIN personphoto ON person.id = personphoto.idPerson WHERE writing.idProduction = ? GROUP BY person.id");
			$stmt->bind_param('i',$prod->id);
			$stmt->execute();
			$stmt->bind_result($id);
			$stmt->store_result();
			while($stmt->fetch())
			$prod->writers[] = $this->getPersonInfo($id,true);

			$stmt->close();
			return $prod;
		}
		 
	}
	function getOtherTitlesForProduction($production)
	{
		$stmt = $this->mysqli->prepare("SELECT originaltitles.title,country.id,country.country FROM originaltitles JOIN countrytitles ON countrytitles.idOriginaltitle = originaltitles.id JOIN country ON country.id = countrytitles.idCountry WHERE originaltitles.idProduction = ? GROUP BY country.id");
		$stmt->bind_param('i',$production->id);
		$stmt->execute();
		$stmt->bind_result($title,$cid,$country);
		$stmt->store_result();
		$i = 0;
		$titles = array();
		while($stmt->fetch())
		{
			$titles[$i][0] = $title;
			$titles[$i][1] = $cid;
			$titles[$i][2] = $country;
			$i++;
		}
		foreach($titles as $tid => $title)
		{
			$f = false;
			foreach($production->titles as $id => $t)
			{
				if($t->title == $title[0])
				{
					$production->titles[$id]->countries[] = new Country($title[2],$title[1]);
					$f = true;
				}
			}
			if(!$f)
			$production->titles[] = new OriginalTitle($title[0],array(new Country($title[2],$title[1])));
		}
		$stmt->close();
	}
	function deleteProductionById($productionId)
	{
		if(is_numeric($productionId))
		{
			$stmt = $this->mysqli->prepare("DELETE FROM production WHERE id = ?");
			$stmt->bind_param('i', $productionId);
			$stmt->execute();
			$stmt->close();	
		}
	}
	function deleteProduction($path,$filename)
	{
		$files = array();
		//First get the id of the production connected to the file
		$stmt = $this->mysqli->prepare("SELECT path,filename,idProduction FROM file WHERE file.idProduction = (SELECT idProduction FROM file WHERE file.path = ? AND file.filename = ? LIMIT 1)");
		$stmt->bind_param('ss',$path,$filename);
		$stmt->execute();
		$stmt->bind_result($p,$f,$id);
		$stmt->store_result();
		while($stmt->fetch())
		{
			$files [] = $p.$f;
		}
		$stmt->close();

		//Remove the file and wait for the other files to be deemed as removed and thereby removing the production
		if(count($files) > 1)
		{
			$stmt = $this->mysqli->prepare("DELETE FROM file WHERE path = ? AND filename = ?");
			$stmt->bind_param('ss', $path,$filename);
			$stmt->execute();
			$stmt->close();
		}
		//If there aren't any other files, remove the production and the file
		else
		{
			$stmt = $this->mysqli->prepare("DELETE FROM production WHERE id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		}
	}
	
	function removeLowResRssMovies()
	{
		$files = array();
		//First get the id of the production connected to the file
		$stmt = $this->mysqli->prepare("SELECT idProduction, COUNT(file.idProduction),production.title FROM  `file` INNER JOIN production ON production.id = file.idProduction WHERE  `width` = 0 AND production.type = 4 GROUP BY file.idProduction");
		$stmt->execute();
		$stmt->bind_result($id,$count,$title);
		$stmt->store_result();
		while($stmt->fetch())
		{
			if($count == 1)
			{
				echo "Removing ".$title.PHP_EOL;
				$this->deleteProductionById($id);
			}
		}
		$stmt->close();
	}

	//Get productions with files at different paths
	function getDuplicates($orderby,$order,$listView)
	{				
		$all = $this->listProductions($orderby,$order,$listView);
		$duplicates = array();
		foreach($all as $production)
		{
			$p = $this->getProduction($production->id,true);
			$filenames = array();
			$paths = array();
			foreach($p->getNonInternetFiles() as $file)
			{
				$filenames[] = $file->filename;
				$paths[] = $file->path;
			}
			$stackSet = DirectoryUtil::getFileStack($paths,$filenames);
			if(count($stackSet) > 1)
				$duplicates[] = $production;
		}
		
		return $duplicates;
	}
	
	function addRSSMovie($production)
	{
		if(is_a($production,"RssMovie"))
		{
			$alreadyInDb = $this->getRssMovie($production->link);
			if($alreadyInDb === false)
			{
				$stmt = $this->mysqli->prepare("INSERT INTO rssmovie (link,releaseName,timeReleased,idProduction,downloaded,fullHD,lastCheck,nrOfChecks) VALUES (?,?,?,?,?,?,?,?)");
				$fullHD = $production->fullHD();
				$stmt->bind_param('sssiiisi', $production->link, $production->title,$production->timeReleased,$production->id,$production->downloaded,$fullHD,$production->lastCheck,$production->nrOfChecks);
				$stmt->execute();
				$production->rssMovieId = $stmt->insert_id;
				$stmt->close();
			}
			else
			{
				$stmt = $this->mysqli->prepare("UPDATE rssmovie SET releaseName = ?,timeReleased = ?,downloaded = ?,fullHD = ?,lastCheck = ?,nrOfChecks = ? WHERE link = ?");
				$fullHD = $production->fullHD();
				$stmt->bind_param('ssiisis', $production->title,$production->timeReleased,$production->downloaded,$fullHD,$production->lastCheck,$production->nrOfChecks, $production->link);
				$stmt->execute();
				$stmt->close();
			}
		}
		
	}
	
	function addProduction($production)
	{
		//We must have an imdb id
		if(!$production->hasProperIMDBNumber())
			return;
		 
		//Check if the production already is in the db
		$stmt = $this->mysqli->prepare("SELECT idProduction,production.type FROM ".$production->tableWithIMDB." INNER JOIN production ON production.id = ".$production->tableWithIMDB.".idProduction WHERE imdb = ?");
		$stmt->bind_param('i',$production->imdb);
		$stmt->execute();
		$stmt->bind_result($production->id,$type);
		$stmt->store_result();
		$stmt->fetch();
		$productionFound = ($stmt->num_rows > 0);
		$stmt->close();

		if(!$productionFound)
		{
			Logger::echoText("Adding ".$production->getDisplayTitle()." to DB.".PHP_EOL);
			//Insert Production
			$stmt = $this->mysqli->prepare("INSERT INTO production (type,title,plot,rating,votes) values (?,?,?,?,?)");
			$stmt->bind_param('issdi',$production->type,$production->title, $production->plot, $production->rating, $production->votes);
			$stmt->execute();
			$production->id = $stmt->insert_id;
			$stmt->close();
			Logger::logProduction($production);
		}
		else
		{
			//Update Production
			$stmt = $this->mysqli->prepare("UPDATE production SET title = ?,plot = ?,rating = ?,votes = ?, type = ? WHERE id = ?");
			$stmt->bind_param('ssdiii',$production->title, $production->plot, $production->rating, $production->votes,$production->type,$production->id);
			$stmt->execute();
			$stmt->close();
		}

		if(is_a($production,"Movie"))
		{
			// Insert/Get Studio
			if(is_string($production->studio))
			{
				$stmt = $this->mysqli->prepare("SELECT id FROM studio WHERE studio = ?");
				$stmt->bind_param('s',$production->studio);
				$stmt->execute();
				$stmt->bind_result($studio);
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0) /* The studio was not found. Insert it and get the ID */
				{
					$stmt = $this->mysqli->prepare("INSERT INTO studio (studio) values (?)");
					$stmt->bind_param('s',$production->studio);
					$stmt->execute();
					$studio = $stmt->insert_id;
					$stmt->close();
				}
			}
			if(!$productionFound) /* The movie was not found. */
			{
				//Insert Movie
				$stmt = $this->mysqli->prepare("INSERT INTO movie (idProduction,idStudio,imdb,top250,year,outline,tagline,mpaa,runtime) values (?,?,?,?,?,?,?,?,?)");
				$stmt->bind_param('iiiiisssi',$production->id,$studio,$production->imdb,$production->top250,$production->year,$production->outline,$production->tagline,$production->mpaa,$production->runtime);
				$stmt->execute();
				$stmt->close();
				if(is_a($production,"RssMovie"))
					$this->addRSSMovie($production);
			}
			else
			{
				//Update Movie
				$stmt = $this->mysqli->prepare("UPDATE movie SET imdb = ?,top250 = ?,year = ?,outline = ?,tagline = ?,mpaa = ?,runtime = ? WHERE idProduction = ?");
				$stmt->bind_param('iiisssii',$production->imdb,$production->top250,$production->year,$production->outline,$production->tagline,$production->mpaa,$production->runtime,$production->id);
				$stmt->execute();
				$stmt->close();
			}
			//Insert Other titles
			foreach($production->titles as $title)
			{
				// Insert/Get Other title
				$stmt = $this->mysqli->prepare("SELECT id FROM originaltitles WHERE title = ? AND idProduction = ?");
				$stmt->bind_param('is',$production->id,$title->title);
				$stmt->execute();
				$stmt->bind_result($id);
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0) /* The other title was not found. Insert it and get the ID */
				{
					$stmt = $this->mysqli->prepare("INSERT INTO originaltitles (idProduction,title) values (?,?)");
					$stmt->bind_param('is',$production->id,$title->title);
					$stmt->execute();
					$id = $stmt->insert_id;
					$stmt->close();
				}
				foreach($title->countries as $country)
				{
					// Insert/Get Country
					if(is_string($country))
					{
						$stmt = $this->mysqli->prepare("SELECT id FROM country WHERE country = ?");
						$stmt->bind_param('s',$country);
						$stmt->execute();
						$stmt->bind_result($cid);
						$stmt->store_result();
						$stmt->fetch();
						$numrows = $stmt->num_rows;
						$stmt->close();
						if($numrows == 0) /* The country was not found. Insert it and get the ID */
						{
							$stmt = $this->mysqli->prepare("INSERT INTO country (country) values (?)");
							$stmt->bind_param('s',$country);
							$stmt->execute();
							$cid = $stmt->insert_id;
							$stmt->close();
						}
						$stmt = $this->mysqli->prepare("INSERT INTO countrytitles (idOriginaltitle,idCountry) values (?,?)");
						$stmt->bind_param('ii', $id, $cid);
						$stmt->execute();
						$stmt->close();
					}
				}
			}
			//Keywords
			foreach($production->keywords as $keyword)
			{
				if(is_string($keyword))
				{
					// Insert/Get keyword id
					$stmt = $this->mysqli->prepare("SELECT id FROM keyword WHERE keyword.keyword = ?");
					$stmt->bind_param('s',$keyword);
					$stmt->execute();
					$stmt->bind_result($id);
					$stmt->store_result();
					$stmt->fetch();
					$numrows = $stmt->num_rows;
					$stmt->close();
					if($numrows == 0) /* The keyword was not found. Insert it and get the ID */
					{
						$stmt = $this->mysqli->prepare("INSERT INTO keyword (id,keyword) values (NULL,?)");
						$stmt->bind_param('s',$keyword);
						$stmt->execute();
						$id = $stmt->insert_id;
						$stmt->close();
					}
					$stmt = $this->mysqli->prepare("INSERT INTO keywordproduction (idKeyword,idProduction) values (?,?)");
					$stmt->bind_param('ii', $id, $production->id);
					$stmt->execute();
					$stmt->close();
				}
			}
			 
		}
		if(is_a($production,"Tvshow"))
		{
			if(!$productionFound) /* The tvshow was not found. Insert it and get the ID */
			{
				//Insert Tvshow
				$stmt = $this->mysqli->prepare("INSERT INTO tvshow (idProduction,premiered,imdb) values (?,?,?)");
				$stmt->bind_param('isi',$production->id,$production->premiered,$production->imdb);
				$stmt->execute();
				$stmt->close();
			}
			foreach($production->episodes as $episode)
			{
				$episode->idTvshow = $production->id;
				$this->addProduction($episode);
			}
		}
		else if(is_a($production,"Episode"))
		{
			if(!$productionFound) /* The episode was not found. Insert it and get the ID */
			{
				//Insert Episode
				$stmt = $this->mysqli->prepare("INSERT INTO episode (idProduction,idTvshow,season,episode,mpaa,aired,runtime,imdb) values (?,?,?,?,?,?,?,?)") or die(mysqli_error($this->mysqli));
				$stmt->bind_param('iiiissii',$production->id,$production->idTvshow, $production->season,$production->episode,$production->mpaa,$production->aired,$production->runtime, $production->imdb) or die(mysqli_error($this->mysqli));
				$stmt->execute();
				$stmt->close();
			}
			else
			{
				//Update Episode
				$stmt = $this->mysqli->prepare("UPDATE episode SET mpaa = ?,aired = ?,runtime = ? WHERE idProduction = ?");
				$stmt->bind_param('ssii',$production->mpaa,$production->aired,$production->runtime,$production->id);
				$stmt->execute();
				$stmt->close();
			}
		}
		foreach($production->files as $file)
		{
			$file->productionId = $production->id;
			$this->addFile($file);

		}
		//Genres
		foreach($production->genres as $genre)
		{
			if(is_string($genre))
			{
				// Insert/Get Genre
				$stmt = $this->mysqli->prepare("SELECT id FROM genre WHERE genre = ?");
				$stmt->bind_param('s',$genre);
				$stmt->execute();
				$stmt->bind_result($gid);
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0) /* The genre was not found. Insert it and get the ID */
				{
					$stmt = $this->mysqli->prepare("INSERT INTO genre (genre) values (?)");
					$stmt->bind_param('s',$genre);
					$stmt->execute();
					$gid = $stmt->insert_id;
					$stmt->close();
				}
				//Add connection to production
				$stmt = $this->mysqli->prepare("INSERT INTO productiongenre (idProduction,idGenre) values (?,?)");
				$stmt->bind_param('ii',$production->id,$gid);
				$stmt->execute();
				$stmt->close();
			}
		}
		//Photos
		foreach($production->photos as $photo)
		{
			// Insert/Get Photo
			$stmt = $this->mysqli->prepare("SELECT id FROM photo WHERE path = ? && idProduction = ?");
			$stmt->bind_param('si',$photo,$production->id);
			$stmt->execute();
			$stmt->store_result();
			$numrows = $stmt->num_rows;
			$stmt->close();
			if($numrows == 0) /* The photo was not found. Insert it. */
			{
				$stmt = $this->mysqli->prepare("INSERT INTO photo (path,idProduction) values (?,?)");
				$stmt->bind_param('si',$photo,$production->id);
				$stmt->execute();
				$stmt->close();
			}
		}
		//Fanart
		foreach($production->fanart as $fanart)
		{
			// Insert/Get Fanart
			$stmt = $this->mysqli->prepare("SELECT id FROM fanart WHERE path = ? && idProduction = ?");
			$stmt->bind_param('si',$fanart->path,$production->id);
			$stmt->execute();
			$stmt->bind_result($fid);
			$stmt->store_result();
			$stmt->fetch();
			$numrows = $stmt->num_rows;
			$stmt->close();
			if($numrows == 0) /* The fanart was not found. Insert it. */
			{
				$stmt = $this->mysqli->prepare("INSERT INTO fanart (path,idProduction) values (?,?)");
				$stmt->bind_param('si',$fanart->path,$production->id);
				$stmt->execute();
				$fid = $stmt->insert_id;
				$stmt->close();
			}
			if(strlen($fanart->preview) > 2)
			{
				// Insert/Get Fanart preview
				$stmt = $this->mysqli->prepare("SELECT idFanart FROM previewfanart WHERE idFanart = ?");
				$stmt->bind_param('i',$fid);
				$stmt->execute();
				$stmt->bind_result($fid);
				$stmt->store_result();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0) /* The fanart preview was not found. Insert it. */
				{
					$stmt = $this->mysqli->prepare("INSERT INTO previewfanart (idFanart,path) values (?,?)");
					$stmt->bind_param('is',$fid,$fanart->preview);
					$stmt->execute();
					$stmt->close();
				}
			}
		}
		//Actors
		foreach($production->actors as $actor)
			$this->addPerson($actor,0,$production);
		//Directors
		foreach($production->directors as $director)
			$this->addPerson($director,1,$production);
		//Writers
		foreach($production->writers as $writer)
			$this->addPerson($writer,2,$production);
	}
	
	function setIMDBForProduction($production,$imdb)
	{
		if($production->id > 0 && $imdb > 0)
		{
			//Update Episode
			$stmt = $this->mysqli->prepare("UPDATE ".$production->tableWithIMDB." SET imdb = ? WHERE idProduction = ?");
			$stmt->bind_param('ii',$imdb,$production->id);
			$stmt->execute();
			$stmt->close();	
		}
	}
	function clearIMDBdata($production)
	{
		$tables = array("acting","originaltitles","photo","fanart","keywordproduction","productiongenre","writing","directing");
		foreach($tables as $table)
		{
			$stmt = $this->mysqli->prepare("DELETE FROM {$table} WHERE idProduction = ?");
			$stmt->bind_param('i', $production->id);
			$stmt->execute();
			$stmt->close();	
		}
	}

	function addFile($file)
	{
		//Check if the file already is in the db
		$stmt = $this->mysqli->prepare("SELECT id FROM file WHERE idProduction = ? AND path = ? AND filename = ?");
		$stmt->bind_param('iss',$file->productionId,$file->path,$file->filename);
		$stmt->execute();
		$stmt->bind_result($file->fileId);
		$stmt->store_result();
		$stmt->fetch();
		$numrows = $stmt->num_rows;
		$stmt->close();
		if($numrows == 0)
		{
			//Insert into file
			$stmt = $this->mysqli->prepare("INSERT INTO file (idProduction,playcount,path,filename,filesize,duration,format,width,height,ar,writinglibrary,videobitrate,hassubtitle,timeAdded) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			$stmt->bind_param('iissdisiissdis',
					$file->productionId,$file->playcount,$file->path,$file->filename,
					$file->filesize,$file->duration,$file->format,$file->width,
					$file->height, $file->ar,$file->writinglibrary,$file->videobitrate,$file->hassubtitle,$file->timeAdded);

			$stmt->execute();
			$file->fileId = $stmt->insert_id;
			$stmt->close();
			foreach($file->audiotracks as $audiotrack)
			{
				//Is there already an identic audiotrack?
				$stmt = $this->mysqli->prepare("SELECT id FROM audiotrack WHERE idFile = ? AND format = ? AND formatinfo = ? AND channels = ? AND bitrate = ? AND title = ? AND language = ? LIMIT 1");
				$stmt->bind_param('issiiss',$file->fileId,$audiotrack->format,$audiotrack->formatinfo,$audiotrack->channels,$audiotrack->bitrate,$audiotrack->title,$audiotrack->language);
				$stmt->execute();
				$stmt->bind_result($audiotrackId);
				$stmt->store_result();
				$stmt->fetch();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0)
				{
					//Insert into audiotrack
					$stmt = $this->mysqli->prepare("INSERT INTO audiotrack (idFile,format,formatinfo,channels,bitrate,title,language) VALUES (?,?,?,?,?,?,?)");
					$stmt->bind_param('issiiss',$file->fileId,$audiotrack->format,$audiotrack->formatinfo,$audiotrack->channels,$audiotrack->bitrate,$audiotrack->title,$audiotrack->language);
					$stmt->execute();
					$stmt->close();
				}
			}
		}
		else
		{
			//Update the playcount etc
			$stmt = $this->mysqli->prepare("UPDATE file SET playcount = ?, filesize = ?,duration = ?,format = ?,width = ?,height = ?,ar = ?,writinglibrary = ?,videobitrate = ?,hassubtitle = ? WHERE id = ?");
			$stmt->bind_param('idisiissdii',$file->playcount,$file->filesize,$file->duration,$file->format,$file->width,$file->height,$file->ar,$file->writinglibrary,$file->videobitrate, $file->hassubtitle, $file->fileId);
			$stmt->execute();
			$stmt->close();
		}
	}
	
	function addPerson($person,$jobType = 0,$production = false)
	{
		if(is_a($person,"Person"))
		{
			$stmt = $this->mysqli->prepare("INSERT INTO person (id,name,bio,dob,birthplace,gender) values (?,?,?,?,?,?)");
			$stmt->bind_param('issssi', $person->id, $person->name, $person->bio, $person->dob,$person->birthplace,$person->gender);
			$stmt->execute();
			$stmt->close();
			if($production !== false)
			{
				if(is_a($person,"Acting"))
				{
					//Add connection to production
					$stmt = $this->mysqli->prepare("SELECT id FROM acting WHERE role = ? AND idProduction = ? AND idPerson = ?");
					$stmt->bind_param('sii',$person->role,$production->id,$person->id);
					$stmt->execute();
					$stmt->store_result();
					$stmt->fetch();
					$numrows = $stmt->num_rows;
					$stmt->close();
					if($numrows == 0) /* The acting was not found. Insert it. */
					{
						$stmt = $this->mysqli->prepare("INSERT INTO acting (role,idProduction,idPerson) values (?,?,?)");
						$stmt->bind_param('sii',$person->role,$production->id,$person->id);
						$stmt->execute();
						$stmt->close();
					}
				}
				//Directing
				else if($jobType == 1)
				{
					//Add connection to production
					$stmt = $this->mysqli->prepare("INSERT INTO directing (idProduction,idPerson) values (?,?)");
					$stmt->bind_param('ii',$production->id,$person->id);
					$stmt->execute();
					$stmt->close();
				}
				//Writing
				else if($jobType == 2)
				{
					//Add connection to production
					$stmt = $this->mysqli->prepare("INSERT INTO writing (idProduction,idPerson) values (?,?)");
					$stmt->bind_param('ii',$production->id,$person->id);
					$stmt->execute();
					$stmt->close();
				}
			}
			//Add Actor photos
			foreach($person->photos as $personPhoto)
			{
				$path = $personPhoto;
				if(is_a($personPhoto,"PersonPhoto"))
				$path = $personPhoto->path;

				$stmt = $this->mysqli->prepare("SELECT idPerson FROM personphoto WHERE path = ?");
				$stmt->bind_param('s',$path);
				$stmt->execute();
				$stmt->store_result();
				$numrows = $stmt->num_rows;
				$stmt->close();
				if($numrows == 0) /* The thumb was not found. Insert it. */
				{
					$stmt = $this->mysqli->prepare("INSERT INTO personphoto (idPerson,path) values (?,?)");
					$stmt->bind_param('is',$person->id,$path);
					$stmt->execute();
					$stmt->close();
				}
			}
		}
	}
}
?>
