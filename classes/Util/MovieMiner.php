<?php
define("TITLE_SEARCH", 'Title Search');
define("TITLE_IMDB_MAIN_PAGE", 'Title IMDB Main page');
define("IMDB_PLOT_RETRIEVE", 'IMDB plot Retrieve');
define("IMDB_KEYWORDS", 'IMDB Keyword');
define("GOOGLE_SEARCH", 'Google Search');
define("POSTER_LINK", 'Poster Link');
define("IMDB_AKA", 'Also known as file');
define("IMDB_ACTOR_PAGE", 'IMDB Actors Fullcredits');
define("ALL_EPISODES_INFO_IMDB", 'All episodes info from IMDB');
define("TITLE_IMDB_POSTER_PAGE", 'Title IMDB Poster page');


class MovieMiner
{
	//Searches Imdb and considers the first result as a match
	public static function getImdbId($movie,$onlyAcceptDirectHit = false)
	{
		if(!$movie->hasProperIMDBNumber())
		{
			$lines = FileRetrieve::file("http://www.imdb.com/find?s=tt&q={$movie->getUrlEncodedSearch()}",TITLE_SEARCH); //Hämtar info från imdb
			Logger::echoText("Fetching imdb id for ".$movie->getDisplayTitle().PHP_EOL);
			
			if(returnarraykey($lines,'<b>No Matches.</b>') === false)
			{				
				$searchPage = returnarraykey($lines," Search</title>");
				//We got redirected, it was probably a really good hit
				if($searchPage === false)
				{
					//If only one hit, and it is approximated, ignore it and log the search as failed.
					$hit = true;
					if(returnarraykey($lines,'Titles (Approx Matches)') !== false)
					{
						if(returnarraykey($lines,'Popular Titles') === false)
						{
							if(returnarraykey($lines,'Titles (Exact Matches)') === false)
							//We probably got a very inaccurate result, ignore it
								$hit = false;
						}
					}
					if($hit)
					{
						$imdbnrline = returnarraykey($lines,"/title/tt");
						$imdbnr = explode("/title/tt",$lines[$imdbnrline],2);
						//We got redirected to the movie's page
						if(count($imdbnr) > 1)
						{
							$titleline = returnarraykey($lines,"<title>");
							if($titleline !== false)
							{
								$dom = str_get_html(implode('',$lines));
								MovieMiner::getTitleAndYearFromDom($movie,$dom);
								$title = html_entity_decode($movie->title);
								$properResult = true;
								if(is_a($movie,"Movie"))
								{
									//If we have added a movie and the result starts with a " it is a show, if it is a mini-series we accept it as a movie
									$isMiniSeries = (returnarraykey($lines,'tv-extra">TV mini-series') !== false);
									if($title[0] == '"' && !$isMiniSeries)
										$properResult = false;
								}
								else if(is_a($movie,"Tvshow") || is_a($movie,"Episode"))
								{
									//If we have added a tvshow or a episode and the result do not start with a " it is a movie
									if($title[0] != '"')
										$properResult = false;
								}
								if($properResult)
								{
									$movie->imdb = strtok($imdbnr[1],"/");
									$movie->imdbPage = $lines;
								}
							}
						}
					}				
				}
				else if($onlyAcceptDirectHit === false)
				{
					//Some times the partial results is presented first, usually they are better those times
					$approxFirst = true;
					$partial = "<p><b>Titles (Partial Matches)</b>";
					$approx = "<p><b>Titles (Approx Matches)</b>";
					$partialLine = returnarraykey($lines,$partial);
					$approxLine = returnarraykey($lines,$approx);
					if($partialLine < $approxLine)
						$approxFirst = false;
					else if($partialLine == $approxLine)
					{
						$partialStart = strpos($lines[$partialLine],$partial);
						$approxStart = strpos($lines[$approxLine],$approx);
						if($partialStart !== false && $partialStart < $approxStart)
							$approxFirst = false;
					}
					
					$resultSections = array("Popular Titles", "Titles (Exact Matches)");
						
					if($approxFirst)
					{
						$resultSections[] = "Titles (Approx Matches)";
						$resultSections[] = "Titles (Partial Matches)";
					}
					else
					{
						$resultSections[] = "Titles (Partial Matches)";
						$resultSections[] = "Titles (Approx Matches)";
					}
					
					foreach($resultSections as $index => $section)
					{
						//HTML for the different sections
						$s = "<p><b>".$section."</b>";
						$resultLine = returnarraykey($lines,$s);
						if($resultLine !== false)
						{
							//Result line string
							$sectionStart = strpos($lines[$resultLine],$s);
							$sectionEnd = strpos($lines[$resultLine],"</table>",$sectionStart);
							$r = substr($lines[$resultLine],$sectionStart,$sectionEnd-$sectionStart);
							$resultArray = explode('<tr>',$r);
							if(count($resultArray) > 1)
							{
								unset($resultArray[0]);
								MovieMiner::setIMDBNumberFromBestResult($movie,$resultArray);
								//We found a good match
								if($movie->hasProperIMDBNumber())
									break;
							}
						}
					}
				}
			}
		}
		if(!$movie->hasProperIMDBNumber() && $onlyAcceptDirectHit === false)
		{
			//TODO: search google and check the hits for a IMDB movie
			Logger::logNoIMDBNumberFoundForProduction($movie);
		}
	}
	
	private static function setIMDBNumberFromBestResult($movie,$titleinfo)
	{
		$suggestion = false;
		foreach($titleinfo as $t)
		{
			$image = 0;
			$nrOfImages = substr_count($t,'src="http://');
			//There may be a expand/collapse thingy with two images in it
			if($nrOfImages == 1 || $nrOfImages == 3)
				$image = 1;
			
			$id = explode('<a href="',$t,2);
			if(count($id) > 0)
			{
				$titlename = explode("</a>",$id[1],3);
				$year = StringUtil::scanint($titlename[1+$image],false); 
				$isMiniSeries = (strpos($titlename[1+$image],'mini-series') !== false);
				//The filename may have told us if the result's year is correct
				if(!is_a($movie,"Movie") || $movie->year === false || $movie->year == $year)
				{
					
					if($suggestion === false)// || $image == 1) //A movie with a thumb could be prioritized
					{
						$properSuggestion = true;
						$title = substr($titlename[$image],strrpos($titlename[$image],';">')+3);
						$title = html_entity_decode($title);
						if(is_a($movie,"Movie"))
						{
							//If we have added a movie and the result starts with a " it is a show
							if($title[0] == '"' && !$isMiniSeries)
								$properSuggestion = false;
						}
						else if(is_a($movie,"Tvshow") || is_a($movie,"Episode"))
						{
							//If we have added a tvshow or a episode and the result do not start with a " it is a movie
							if($title[0] != '"')
								$properSuggestion = false;
						}
						
						if(strpos($titlename[1+$image],'/II)') !== false)
							$properSuggestion = false;
						else if(strpos($titlename[1+$image],'/III)') !== false)
							$properSuggestion = false;
						else if(strpos($titlename[1+$image],') (VG)') !== false)
							$properSuggestion = false;
							
						if($properSuggestion)
							$suggestion = StringUtil::scanint($id[1],false); //Suggested IMDB
					}
						
					//if($image == 1)
					//	break;
				}
			}
		}
		if($suggestion !== false)
			$movie->imdb = $suggestion;
	}
	
	public static function getTitleAndYearFromDom($movie,$dom)
	{
		if(is_a($movie,'Movie'))
		{
			$yearElement = $dom->find('a[href^="/year/"]',0);
			if($yearElement)
			{
				$movie->year = trim($yearElement->plaintext);
			}
			else
			{
				$yearElement = $dom->find('time[itemprop="datePublished"]',0);
				if($yearElement)
				{
					$movie->year = trim($yearElement->plaintext);
				}
			}
		}	
		if(!is_a($movie,'Episode'))
			$movie->title = strip_tags(trim($dom->find('h1[itemprop="name"]',0)->nodetext, "\n"));
	}
	
	public static function getEpisodesFor($tvshow)
	{
		//FileRetrieve::copy("http://www.thetvdb.com/api/".$cfg['TVDB_API_KEY']."/mirrors.xml",'mirrors.xml');
		
		$episodeInfo = FileRetrieve::file("http://www.imdb.com/title/tt{$tvshow->imdb}/episodes",ALL_EPISODES_INFO_IMDB,0,true); 
		$tvshow->tempTvshow = new Tvshow();
		$dom = str_get_html($episodeInfo);
		$seasonSections = $dom->find('div[class^="season-filter-all"]');
		foreach($seasonSections as $seasonSection)
		{
			$episodes = $seasonSection->find('div[class^="filter-all"]');
			foreach($episodes as $e)
			{
				$episode = new Episode();
				$info = $e->find('h3',0);
				if($info != null)
				{
					$text = $info->plaintext;
					$episode->season = StringUtil::scanint($text,false,0);
					$episode->episode = StringUtil::scanint($text,false,1);
					
					$link = $info->find('a',0);
					if($link != null)
					{
						$href = $link->href;
						$episode->imdb = StringUtil::scanint($href);
						$episode->title = $link->plaintext;
					}
					
					$plotTextElement = $e->innertext;
					$plotStart = strpos($plotTextElement,'<br>')+6;
					$plotEnd = strpos($plotTextElement,'</td>',$plotStart);
					
					$episode->plot = substr($plotTextElement,$plotStart,$plotEnd-$plotStart);
					
					$airDateElement = $e->find('strong',0);
					if($airDateElement != null)
					{
						$date = $airDateElement->plaintext;
						if (($timestamp = strtotime($date)) === false)
							Logger::parseError($episode,"Failed to convert date from episode list");
						else
						    $episode->airdate = date('Y-m-d', $timestamp);
					}
					$tvshow->tempTvshow->episodes[] = $episode;
				}
			}
		}
		$dom->clear();
	}
	
	public static function getBasicInfo($movie, $basic = false)
	{   
		Logger::echoText("Fetching imdb info for ".$movie->getDisplayTitle().PHP_EOL);
		//If there only was a title when we searced, the page for that movie will be cached
		if(isset($movie->imdbPage))
		{
			if(returnarraykey($movie->imdbPage,'<title>IMDb Title Search') === false)
				$lines = $movie->imdbPage;

			unset($movie->imdbPage);
		}
		//Läser in filmens huvudsida från imdb
		if(!isset($lines))
			$lines = FileRetrieve::file("http://www.imdb.com/title/tt{$movie->imdb}/",TITLE_IMDB_MAIN_PAGE); //Hämtar info från imdb
			
		$dom = str_get_html(implode('',$lines));
		
		MovieMiner::getTitleAndYearFromDom($movie,$dom);
		
		//Return if the result is a tvshow and we are looking for a movie
		//If we have added a movie and the result starts with a " it is a show
		if(is_a($movie, "RssMovie"))
		{
			$title = html_entity_decode($movie->title);
			$isMiniSeries = (returnarraykey($lines,'tv-extra">TV mini-series') !== false);
			if($title[0] == '"')// && !$isMiniSeries)
			{
				$movie->badResult = true;
				$dom->clear();
				return;
			}
			else 
				$movie->badResult = false;
		}
		$articles = $dom->find('div.article');
		//Movie details
		foreach($articles as $article)
		{
			$infoHeadlines = $article->find('h4');
			foreach($infoHeadlines as $infoHeadline)
			{
				if($infoHeadline != null)
				{
					$parent = $infoHeadline->parent;
					
					if($infoHeadline->plaintext == 'Country:')
						$movie->country = $infoHeadline->next_sibling()->plaintext;
					else if($infoHeadline->plaintext == 'Language:')
						$movie->language = $infoHeadline->next_sibling()->plaintext;
					else if($infoHeadline->plaintext == 'Runtime:')
					{
						$runtime = trim($parent->nodetext);
						//Handles different runtimes in different countries
						//Example: http://www.imdb.com/title/tt0393597/
						//http://www.imdb.com/title/tt0167261/
						$start = strpos($runtime,':');
						if($start !== false)
							$start += 1;
						$movie->runtime = substr($runtime,$start,strpos($runtime,' min',$start)-$start).' min';					
					}
					else if(strpos($infoHeadline->plaintext,'MPAA') !== false)
						$movie->mpaa = trim($parent->nodetext);
					else if($infoHeadline->plaintext == 'Production Co:')
					{
						$companyElements = $parent->find('a[href^="/company/"]');
						if(count($companyElements) > 0)
						{
							//TODO: handle multiple studios, for instance check 21 grams
							//foreach($companyElements as $companyElement)
							//	$movie->genres[] = $genreElement->plaintext;
							$movie->studio = $companyElements[0]->plaintext;	
						}
					}
					else if($infoHeadline->plaintext == 'Taglines:')
						$movie->tagline = trim($parent->nodetext);
					else if(strpos($infoHeadline->plaintext,'Genre') === 0)
					{
						$genreElements = $parent->find('a[href^="/genre/"]');
						foreach($genreElements as $genreElement)
							$movie->genres[] = $genreElement->plaintext;
					}
					else if(strpos(trim($infoHeadline->plaintext),'Director') === 0)
					{
						//If there are several directors
						$directorElements = $parent->find('a[href^="/name/nm"]');
						foreach($directorElements as $director)
						{
							$director = new Person($director->plaintext,StringUtil::scanint($director->href,false,0));
							if(!$basic)
								MovieMiner::getPersonInfo($director);
							$movie->directors[] = $director;
						}
					}
					else if(strpos(trim($infoHeadline->plaintext),'Writer') === 0)
					{
						//If there are several writers
						$writerElements = $parent->find('a[href^="/name/nm"]');
						foreach($writerElements as $writer)
						{
							$writer = new Person($writer->plaintext,StringUtil::scanint($writer->href,false,0));
							if(!$basic)
								MovieMiner::getPersonInfo($writer);
							$movie->writers[] = $writer;
						}
					}
					else if(strpos($infoHeadline->plaintext,'Creator') === 0)
					{
						//If there are several writers/creators
						$writerElements = $parent->find('a[href^="/name/nm"]');
						foreach($writerElements as $writer)
						{
							$writer = new Person($writer->plaintext,StringUtil::scanint($writer->href,false,0));
							if(!$basic)
								MovieMiner::getPersonInfo($writer);
							$movie->writers[] = $writer;
						}
					}
				}
			}
			//Plot/Storyline
			$posterHeadlines = $article->find('h2');
			foreach($posterHeadlines as $infoHeadline)
			{
				if($infoHeadline->plaintext == 'Storyline')
				{
					$plot = trim($infoHeadline->next_sibling()->nodetext);
					$plotEnd = StringUtil::backwardStrpos($plot, "\n");
					if($plotEnd !== false)
						$movie->plot = trim(substr($plot,0,$plotEnd));
					else
						$movie->plot = trim($plot);
					//We do not want unfinished sentences
					if(substr($movie->plot,-3,3) == '...')
					{
						$plotLines = FileRetrieve::file("http://www.imdb.com/title/tt{$movie->imdb}/plotsummary",IMDB_PLOT_RETRIEVE);
						$plots = array();
						$plotDom = str_get_html(implode('',$plotLines));
						$plotElements = $plotDom->find('p.plotpar');
						if(is_array($plotElements))
						{
							foreach($plotElements as $plotElement)
							{
								$plot = $plotElement->innertext;
								//Removes the trailing written by text
								$plots [] = strip_tags(substr($plot,0,strpos($plot,'<i>')));
							}
							//Lengthy plot descriptions are dull!
							//Select the shortest one
							$plotWithLeastCharacters = 0;
							$selectedPlotLength = strlen($plots[0]);
							for($i = 1;$i< count($plots);$i++)
							{
								$length = strlen($plots[$i]);
								if($length < $selectedPlotLength)
								{
									$plotWithLeastCharacters = $i;
									$selectedPlotLength = $length;
								}
							}
							$movie->plot = $plots[$plotWithLeastCharacters];
						}
						$plotDom->clear();
					}
				}
			}
		}
		//Rating
		$ratingElement = $dom->find('span[itemprop="ratingCount"]', 0);
		if($ratingElement)
		{
			$movie->rating = $ratingElement->plaintext;
		}
		else 
			$movie->rating = 0;
			
		$votesElement = $dom->find('span[itemprop="ratingCount"]', 0);
		if($votesElement)
		{
			$movie->votes = str_replace(',','',$votesElement->plaintext);
		}
		else 
			$movie->votes = 0;
			
		//Top 250
		$top250Element = $dom->find('a[href^="http://www.imdb.com/chart/top"]',0);
		if($top250Element != null)
		{
			$placementText = $top250Element->plaintext;
			$kind = substr($placementText,0,strpos($placementText,' '));
			$number = StringUtil::scanint($placementText,false,1);
			if(!is_numeric($number))
				Logger::parseError($movie,'Top 250/Bottom 100 found, but placement non numeric');
			else if($kind == 'Top')
				$movie->top250 = $number;
			else if($kind == 'Bottom')
				$movie->top250 = -$number;	
		}
		
		//IMDB poster
		$posterElement = $dom->find('td[id=img_primary]',0);
		if($posterElement != null)
		{	
			$link = $posterElement->first_child();
			if($link != null && isset($link->href))
			{
				$posterLines = FileRetrieve::file("http://www.imdb.com{$link->href}",TITLE_IMDB_POSTER_PAGE); //Fetches the media page containing the big image url
				$posterDom = str_get_html(implode('',$posterLines));
				$imageNode = $posterDom->find('img[id=primary-img]',0);
				if($imageNode != null)
				{
					$localDest = 'images/posters/'.$movie->imdb.'_p.jpg';
					if(!file_exists($localDest))
					{
						if(FileRetrieve::copy($imageNode->src,$localDest))
						{
							$movie->photos[] = $localDest;
							ThumbnailProvider::fillThumbFolders($localDest, ThumbnailProvider::$POSTER);
						}
					}
					else
						$movie->photos[] = $localDest;
				}
				else 
					Logger::parseError($movie, "Poster page doesn't have a primary image");
					
				$posterDom->clear();
			}
			else 
				Logger::echoText("No IMDB poster found: ".$movie->title."<br>");
		}
		else 
			Logger::parseError($movie, "No table element for the IMDB primary poster found on the main page");
		
		$dom->clear();
	}

	public static function getKeywords($movie)
	{	    
		Logger::echoText("Getting keywords for: ".$movie->getDisplayTitle().PHP_EOL);
		$keywordFile = FileRetrieve::file('http://www.imdb.com/title/tt'.$movie->imdb.'/keywords',IMDB_KEYWORDS,0,true);
		if($keywordFile !== false)
		{
			$dom = str_get_html($keywordFile);
			$keywordElements = $dom->find("b[class='keyword']");
			if($keywordElements != null)
			{
				foreach($keywordElements as $keywordElement)
					$movie->keywords[] = trim($keywordElement->plaintext,"\n");
			}
			$dom->clear();
		}
	}

	public static function getForeignTitles($movie)
	{
	    Logger::echoText("Getting aka's for: ".$movie->getDisplayTitle().PHP_EOL);
		$titleFile = FileRetrieve::file('http://www.imdb.com/title/tt'.$movie->imdb.'/releaseinfo',IMDB_AKA,0,true);
		if($titleFile !== false)
		{
			$akastart = strpos($titleFile,'Also Known As (AKA)');
			if($akastart !== false)
			{
				$akaend = strpos($titleFile,'</table>',$akastart);
				$akasstring = substr($titleFile,$akastart,$akaend-$akastart);
				$othertitles = explode('<td>',$akasstring);
				unset($othertitles[0]);
				for($i = 1;$i<count($othertitles)+1;$i+=2) // 1 = title,2 = country
					$movie->titles[] = new OriginalTitle(substr($othertitles[$i],0,strpos($othertitles[$i],'<')),explode(' / ',substr($othertitles[$i+1],0,strpos($othertitles[$i+1],'<'))));
			}
		}
	}

	//Complete info with name,imdbid,role and a poster
	public static function getActors($movie)
	{
		Logger::echoText("Getting actors for: ".$movie->getDisplayTitle().PHP_EOL);
		$actorFile = FileRetrieve::file("http://www.imdb.com/title/tt{$movie->imdb}/fullcredits#cast",IMDB_ACTOR_PAGE,0,true);
		$dom = str_get_html($actorFile);
		$actorElements = $dom->find('table.cast tr');
		foreach($actorElements as $actorElement)
		{
			$actor = new Acting();
			$nameElement = $actorElement->find('td.nm',0);
			if($nameElement != null)
			{
				$actor->name = $nameElement->plaintext;
				$linkElement = $nameElement->find('a',0);
				if($linkElement != null)
				{
					$test = substr($linkElement->outertext,17);
					$actor->id = StringUtil::scanint2($test);
					
					$roleElement = $actorElement->find('td.char',0);
					if($roleElement != null)
					{
						$actor->role = $roleElement->plaintext;
						MovieMiner::getPersonInfo($actor);
						$movie->actors[] = $actor;
					}
				}
			}
		}
		$dom->clear();
	}
	//Retrieves photo,bio,gender
	public static function getPersonInfo($person)
	{
		global $dbh;
		if($dbh->getPerson($person->id) === false)
		{			
			if(is_numeric($person->id))
			{	
				//BIO stuff
				$actorbio = FileRetrieve::file("http://www.imdb.com/name/nm".$person->id.'/bio');
				$bioline = returnarraykey($actorbio,'<h5>Mini Biography</h5>');
				if($bioline !== false)
					$person->bio = strip_tags($actorbio[$bioline+1]);
	
				$dobline = returnarraykey($actorbio,'<h5>Date of Birth</h5>');
				if($dobline !== false)
				{
					$dobline++;
					$date = substr($actorbio[$dobline],strpos($actorbio[$dobline],'/date/')+6);
					$month = substr($date,0,2);
					$day = substr($date,3,2);
					$year = substr($actorbio[$dobline],strpos($actorbio[$dobline],'birth_year=')+11,4);
					$person->dob = $year.'-'.$month.'-'.$day;
					$person->birthplace = substr($actorbio[$dobline],strrpos($actorbio[$dobline],'>',-4)+1,-5);
				}
					
				//Gender
				$gendersearch = FileRetrieve::file('http://www.imdb.com/search/name?gender=female&name='.$person->getUrlEncodedName());
				//If the person is in the search results, it is a female
				if(returnarraykey($gendersearch,$person->id.'/">'))
					$person->gender = "1";
				else
					$person->gender = "0";
					
				//Avatar
				MovieMiner::getPersonPhoto($person);
			}
			else
				Logger::parseError($person,'Non numeric id of person');
		}
	}
	public static function getPersonPhotoFromTMDB($person)
	{
		$localActorPath ='images/persons/'.$person->gender.'/'.$person->id.'.jpg';
		if($person->name != "" && count($person->photos) == 0)
		{
			global $dbh;
			if($dbh->getPerson($person->id) === false)
			{
				if(file_exists($localActorPath))
					$person->photos[] = $localActorPath;
				else
				{
					global $cfg;
					$tmdbJSONResponse = FileRetrieve::file("http://api.themoviedb.org/2.1/Person.search/en/json/".$cfg['TMDB_API_KEY']."/".$person->getUrlEncodedName());
					if(is_array($tmdbJSONResponse))
						$tmdbJSONResponse = implode('',$tmdbJSONResponse);
					$tmdbJSONResponseArray = json_decode($tmdbJSONResponse);
					if(is_array($tmdbJSONResponseArray))
					{
						foreach($tmdbJSONResponseArray as $p)
						{
							if(is_object($p) && html_entity_decode($p->{"name"}) == html_entity_decode($person->name))
							{
								$actorJSONResponse = FileRetrieve::file("http://api.themoviedb.org/2.1/Person.getInfo/en/json/".$cfg['TMDB_API_KEY']."/".$p->{"id"},0,true);
								if(is_array($actorJSONResponse))
									$actorJSONResponse = implode('',$actorJSONResponse);
								$parsedResult = json_decode($actorJSONResponse);
								if(count($parsedResult) > 0)
								{
									$actorInfo = $parsedResult[0];
									$person->bio = $actorInfo->{"biography"};
									$person->dob = $actorInfo->{"birthday"};
									$person->birthplace = $actorInfo->{"birthplace"};
									$images = $actorInfo->{"profile"};
									if(is_array($images))
									{
										foreach($images as $i)
										{
											$image = $i->{"image"};
											if($image->{"size"} == 'original')
											{
												if($person->id == "")
													$localActorPath ='images/persons/'.$person->gender.'/'.$actorInfo->{"id"}.'_tmdb.jpg';
												if(FileRetrieve::copy($image->{"url"}, $localActorPath))
												{
													$person->photos[] = $localActorPath;
													ThumbnailProvider::fillThumbFolders($localActorPath, ThumbnailProvider::$PERSON);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	public static function getPersonPhoto($person)
	{
		MovieMiner::getPersonPhotoFromTMDB($person);
		MovieMiner::getPersonPhotoFromIMDB($person);
	}

	public static function getPersonPhotoFromIMDB($person)
	{
		if(count($person->photos) == 0 && isset($person->id) && is_numeric($person->id))
		{
			$localActorPath ='images/persons/'.$person->gender.'/'.$person->id.'.jpg';
			if(!file_exists($localActorPath))
			{
				global $dbh;
				if($dbh->getPerson($person->id) === false)
				{
					$actorpage = FileRetrieve::file("http://www.imdb.com/name/nm".$person->id.'/');

					$headshotline = returnarraykey($actorpage,'/rg/action-box-name/headshot');
					if($headshotline !== false)
					{
						$bigimagelink = substr($actorpage[$headshotline],25);
						$bigimagelink = substr($bigimagelink,0,strpos($bigimagelink,'"'));
						if($bigimagelink != "")
						{
							$bigimagepage = FileRetrieve::file("http://www.imdb.com".$bigimagelink);
							$bigimageurlline = returnarraykey($bigimagepage,'<img id="primary-img"');
							if($bigimageurlline !== false)
							{
								$srcline =  returnarraykey($bigimagepage,'src="',$bigimageurlline-1);
								if($srcline !== false)
								{
									$bigimageurl = $bigimagepage[$srcline];
									$bigimageurl = substr($bigimageurl,strpos($bigimageurl,'src="')+5);
									$bigimageurl = substr($bigimageurl,0,strpos($bigimageurl,'"'));

									if(FileRetrieve::copy($bigimageurl, $localActorPath))
									{
										$person->photos[] = $localActorPath;
										ThumbnailProvider::fillThumbFolders($localActorPath, ThumbnailProvider::$PERSON);
									}
								}
							}
						}
					}
				}
			}
			else
			$person->photos[] = $localActorPath;
		}
	}

	//First we try to get posters from TMDB and then from impawards.com
	public static function getPosters($movie)
	{
		global $cfg;
		Logger::echoText("Getting posters for: ".$movie->getDisplayTitle().PHP_EOL);
		
		$paddedIMDBid = str_pad($movie->imdb,7,'0',STR_PAD_LEFT);
		$tmdbJSONResponse = FileRetrieve::file("http://api.themoviedb.org/2.1/Movie.getImages/en/json/".$cfg['TMDB_API_KEY']."/tt".$paddedIMDBid,0,true);
		if(is_array($tmdbJSONResponse))
			$tmdbJSONResponse = implode('',$tmdbJSONResponse);
		$parsedResponse = json_decode($tmdbJSONResponse);
		$firstResult = $parsedResponse[0];
		if(is_object($firstResult))
		{
			$tmdbFanart = $firstResult->{"backdrops"};
			foreach($tmdbFanart as $backdrop)
			{
				$image = $backdrop->{"image"};
				if($image->{"size"} == 'original')
				{
					$fanart = new Fanart('images/fanart/'.$movie->imdb.'_'.$image->{"id"}.'.jpg');
					$fanart->id = $image->{"id"};
					if(!file_exists($fanart->path))
					{
						if(FileRetrieve::copy($image->{"url"},$fanart->path))
						{
							$movie->fanart[] = $fanart;
							ThumbnailProvider::fillThumbFolders($fanart->path, ThumbnailProvider::$FANART);
						}
					}
					else
						$movie->fanart[] = $fanart;
				}
				else if($image->{"size"} == 'thumb')
				{
					foreach($movie->fanart as $fanart)
					{
						if($fanart->id == $image->{"id"})
						{
							$fanart->preview = 'images/fanart/'.$movie->imdb.'_'.$image->{"id"}.'_t.jpg';
							if(!file_exists($fanart->preview))
							{
								if(!FileRetrieve::copy($image->{"url"},$fanart->preview))
									$fanart->preview = "";
							}
						}
					}
				}
			}
			$tmdbPosters = $firstResult->{"posters"};
			$counter = 1;
			foreach($tmdbPosters as $poster)
			{
				$image = $poster->{"image"};
				if($image->{"size"} == 'original')
				{
					$localDest = 'images/posters/'.$movie->imdb.'_'.$counter.'.jpg';
					if(!file_exists($localDest))
					{
						if(FileRetrieve::copy($image->{"url"},$localDest))
						{
							$counter++;
							$movie->photos[] = $localDest;
							ThumbnailProvider::fillThumbFolders($localDest, ThumbnailProvider::$POSTER);
						}
					}
					else
						$movie->photos[] = $localDest;
				}
			}
		}
		if(count($movie->photos) == 0)
		{
			$googleSearchUrl = "http://www.google.se/search?hl=sv&q=site%3A+www.impawards.com+".urlencode($movie->title)."&btnG=S%C3%B6k&meta=";
			$postersearch = FileRetrieve::file($googleSearchUrl,GOOGLE_SEARCH);
			$resultlinenr = returnarraykey($postersearch,'<li class=g>');
			$info = $postersearch[$resultlinenr];
			$results = explode('<li class=g>',$info);
			$found = false;
			for($i = 1;$i<count($results) && !$found;$i++)
			{
				$link = substr($results[$i],23);
				$link = substr($link,0,strpos($link,'"'));
				if($link != "http://www.impawards.com/")
				{
					if(strpos($link,"http://www.impawards.com/") !== false)
					{
						$posterhtml = FileRetrieve::file($link,POSTER_LINK);
						$linklinenr = returnarraykey($posterhtml,'<div id="left_half">');
						if($linklinenr !== false)
						{
							$line = $posterhtml[$linklinenr+1];
							if(strpos($line,'<img src="') !== false)
							{
								$line = substr($line,strpos($line,'<img src="')+10);
								$line = substr($line,0,strpos($line,'"'));
									
								$imagepath = 'images/posters/'.$movie->imdb.'.jpg';
									
								//Firt we try with a big picture
								//If tvshow /tv/ instead of /{year}/ before $line
								if(!is_file($imagepath))
								{
									$bigImageUrl = 'http://www.impawards.com/'.$movie->year.'/'.substr($line,0,-4).'_xlg'.substr($line,-4);
									if(FileRetrieve::copy($bigImageUrl,$imagepath))
									{
										$found = true;
										if(returnarraykey(file($imagepath),'No Movie Posters on This Page') !== false)
										{
											$found = false;
											Logger::echoText("No big poster for: ".$movie->title."<br>");
											unlink($imagepath);
											//There was no big picture
											$smallImageUrl = 'http://www.impawards.com/'.$line;
											if(FileRetrieve::copy($smallImageUrl,$imagepath))
											{
												if(returnarraykey(file($imagepath),'No Movie Posters on This Page') === false)
													$found = true;
												else
													unlink($imagepath);
											}
										}
									}
									if($found)
									{
										$movie->photos[] = $imagepath;
										ThumbnailProvider::fillThumbFolders($imagepath, ThumbnailProvider::$POSTER);
									}
								}
								else
									$movie->photos[] = $imagepath;
							}
						}
					}
				}
			}
		}
	}
	
	public static function isProperIMDBNumber($imdb)
	{
		return (isset($imdb) && is_numeric($imdb) && $imdb > 0);
	}
}