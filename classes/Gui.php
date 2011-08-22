<?php
define("SMALLICONS", 1);
define("ORDINARY_LIST", 2);
define("FANART",3);
define("FLEXICONS", 4);
define("MEDIA_LIST", 5);
define("NR_OF_VIEWTYPES",5);


define("NR_OF_SORTINGFIELDS",9);
class Gui
{
	var $out;
	var $listView;
	var $order;
	var $orderby;
	
	var $headerSeperator = ' > ';
	
	var $page;
	var $sub;
	
	var $title;
	var $production;
	
	var $person;
	var $role;
	
	var $season;
	var $episode;

	var $studio;
	var $genre;
	var $year;
	var $country;
	var $keyword;
	
	var $search;

	var $delete;
	
	var $screenWidth;
	var $screenHeight;
	
	var $nrOfColumns;
	var $nrOfRows;
	
	var $update;
	
	var $productionPageName;
	
	var $changeIMDB;
	
	var $manuallyAdd;
	
	var $imdb;
	
	function __construct()
	{
		if(isset($_GET['page']))
			$this->page = $_GET['page'];
		else 
			$this->page = 'main';
			
		if(isset($_GET['update']) && $_GET['update'] == 1)
			$this->update = true;
		else 
			$this->update = false;
			
		if(isset($_GET['sub']))
			$this->sub = $_GET['sub'];
		else 
			$this->sub = '';
			
		if(isset($_GET['title']))
			$this->title = $_GET['title'];
		else 
			$this->title = 0;

		if(isset($_GET['person']))
			$this->person = $_GET['person'];
		else 
			$this->person = 0;
			
		if(isset($_GET['season']))
			$this->season = $_GET['season'];
		else 
			$this->season = 0;
			
		if(isset($_GET['episode']))
			$this->episode = $_GET['episode'];
		else 
			$this->episode = 0;
			
		if(isset($_GET['studio']))
			$this->studio = $_GET['studio'];
		else 
			$this->studio = 0;
			
		if(isset($_GET['genre']))
			$this->genre = $_GET['genre'];
		else 
			$this->genre = 0;
			
		if(isset($_GET['year']))
			$this->year = $_GET['year'];
		else 
			$this->year = 0;
			
		if(isset($_GET['role']))
			$this->role = $_GET['role'];
		else 
			$this->role = 0;
			
		if(isset($_GET['country']))
			$this->country = $_GET['country'];
		else 
			$this->country = 0;
			
		if(isset($_GET['search']))
			$this->search = $_GET['search'];
		else 
			$this->search = 0;
			
		if(isset($_GET['keyword']))
			$this->keyword = $_GET['keyword'];
		else 
			$this->keyword = 0;
			
		if(isset($_GET['delete']))
			$this->delete = $_GET['delete'];
		else 
			$this->delete = 0;
			
		if(isset($_GET['changeIMDB']))
			$this->changeIMDB = $_GET['changeIMDB'];
		else 
			$this->changeIMDB = 0;
			
		if(isset($_GET['manuallyAdd']) && $_GET['manuallyAdd'] == 1)
			$this->manuallyAdd = true;
		else 
			$this->manuallyAdd = false;	
			
		if(isset($_GET['imdb']))
			$this->imdb = $_GET['imdb'];
		else 
			$this->imdb = 0;
			
		if(isset($_GET['screenWidth']))
			$_SESSION['xbmc2web.screenWidth'] = $_GET['screenWidth'];
			
		if(isset($_SESSION['xbmc2web.screenWidth']))
			$this->screenWidth = $_SESSION['xbmc2web.screenWidth'];	
		else
			$this->screenWidth = 1024;
			
		if(isset($_GET['screenHeight']))
			$_SESSION['xbmc2web.screenHeight'] = $_GET['screenHeight'];
			
		if(isset($_SESSION['xbmc2web.screenHeight']))
			$this->screenHeight = $_SESSION['xbmc2web.screenHeight'];	
		else
			$this->screenHeight = 600;
			
		if(isset($_GET['nrOfColumns']) && is_numeric($_GET['nrOfColumns']))
			$_SESSION['xbmc2web.nrOfColumns'] = $_GET['nrOfColumns'];
			
		if(isset($_SESSION['xbmc2web.nrOfColumns']))
			$this->nrOfColumns = $_SESSION['xbmc2web.nrOfColumns'];	
		else
			$this->nrOfColumns = 6;	
			
		if(isset($_GET['nrOfRows']) && is_numeric($_GET['nrOfRows']))
			$_SESSION['xbmc2web.nrOfRows'] = $_GET['nrOfRows'];
			
		if(isset($_SESSION['xbmc2web.nrOfRows']))
			$this->nrOfRows = $_SESSION['xbmc2web.nrOfRows'];	
		else
			$this->nrOfRows = 2;	
			
	
		if(isset($_GET['listView']))
			$_SESSION['xbmc2web.listView'] = $_GET['listView'];
			
		if(isset($_SESSION['xbmc2web.listView']))
			$this->listView = $_SESSION['xbmc2web.listView'];	
		else
			$this->listView = SMALLICONS;
			
		if(isset($_GET['orderby']))
			$_SESSION['xbmc2web.orderby'] = $_GET['orderby'];
			
		if(isset($_SESSION['xbmc2web.orderby']))
			$this->orderby = $_SESSION['xbmc2web.orderby'];	
		else
			$this->orderby = 1;
			
		if(isset($_GET['order']))
			$_SESSION['xbmc2web.order'] = $_GET['order'];
		if(isset($_SESSION['xbmc2web.order']))
			$this->order = $_SESSION['xbmc2web.order'];	
		else
			$this->order = 'ASC';
		
		if($this->title != 0 || $this->imdb != 0)
		{
			global $dbh;
			if($this->title != 0)
				$this->production = $dbh->getProduction($this->title);
			else 
			{
				$this->production = $dbh->getProductionByIMDB($this->imdb, false);
				$this->title = $this->production->id;
			}
				
			if($this->production === false)
				die('Movie was not found');
			
			if(is_a($this->production,'Movie'))
			{
				$this->productionPageName = "movies";
			}
			else if(is_a($this->production,'Tvshow') || is_a($this->production,'Episode'))
			{
				$this->productionPageName = "shows";
			}
		}
	}
	function renderHead()
	{
		global $dbh;
		$this->out .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                            <html xmlns="http://www.w3.org/1999/xhtml">
                                    <head>
                                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                                            
                                            <link rel="stylesheet" type="text/css" href="common.css">
                                            <link rel="alternate" type="application/rss+xml" title="Movies" href="?page=mineTL&r=1"/>';
		$title = 'Movie System';
		if($this->title != 0)
		{
			$title = $this->production->getDisplayTitle();
			$this->out .= '					<link rel="stylesheet" type="text/css" href="title.css">';
		}
		else
		{
			if($this->page == 'movies') 
				$title .= ' - Movies';
				
			$this->out .= '					<link rel="stylesheet" type="text/css" href="listView.css">';
		}
        $this->out .= '						<script type="text/javascript" src="main.js"></script>
        									<title>'.$title.'</title>
                                    </head>
                                    <body onload="load();"';
        
         if($this->listView == FLEXICONS)
         	$this->out .= 'onresize="resize();"';
        
		$this->out .= '><div id="header"><a href="?page=main">Main</a>';
		if($this->page != 'main')
		{
			if($this->page != 'main' && $this->page != 'media')
				$this->out .= $this->headerSeperator.'<a href="?page=media">Media</a>';
			$this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'">'.ucfirst($this->page).'</a>';
		}
		if($this->sub) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'">'.ucfirst($this->sub).'</a>';
		if($this->search) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&search='.$this->search.'">'.ucfirst($this->search).'</a>';
		
		if($this->keyword) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&keyword='.$this->keyword.'">'.ucfirst($dbh->getKeyword($this->keyword)).'</a>';
		if($this->genre) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&genre='.$this->genre.'">'.ucfirst($dbh->getGenre($this->genre)).'</a>';
		if($this->year) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&year='.$this->year.'">'.$this->year.'</a>';
		
		if($this->person) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&person='.$this->person.'">'.ucfirst($dbh->getPerson($this->person)).'</a>';

		if($this->studio) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&studio='.$this->studio.'">'.ucfirst($dbh->getStudio($this->studio)).'</a>';
		if($this->role) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&role='.$this->role.'">'.$this->role.'</a>';
		if($this->country) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&country='.$this->country.'">'.ucfirst($dbh->getcountry($this->country)).'</a>';
		if($this->country && $this->production)
		{
			$title = $dbh->getCountryTitle($this->country,$this->production->id);
			$this->out .= $this->headerSeperator.ucfirst($title[0]).' ('.ucfirst($title[1]).')';
		}
		else if($this->production) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&title='.$this->title.'">'.ucfirst($dbh->getTitle($this->production->id)).'</a>';
		if($this->season) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&title='.$this->title.'&season='.$this->season.'">Season '.$this->season.'</a>';
		if($this->episode) $this->out .= $this->headerSeperator.'<a href="?page='.$this->page.'&sub='.$this->sub.'&title='.$this->title.'&season='.$this->season.'&episode='.$this->episode.'">Episode '.$this->episode.'</a>';
		$this->out .= '';
		$this->out .= '</div>';
	}
	function renderTail()
	{
		$this->out .= '</body></html>';
	}
	function renderBody()
	{
		global $dbh,$cfg;

		if($this->changeIMDB > 0 && $this->title > 0)
		{
			Logger::disableEcho();
			$dbh->clearIMDBdata($this->production);
			$this->production->clearIMDBData();
			$productionWithNewIMDB = $dbh->getProductionByIMDB($this->changeIMDB, false);
			if($productionWithNewIMDB === false)
			{
				$dbh->setIMDBForProduction($this->production, $this->changeIMDB);
				$this->production->imdb = $this->changeIMDB;
				$this->production->getImdbInfo();
				$dbh->addProduction($this->production);
				$this->production = $dbh->getProduction($this->title);
			}
			else if(is_a($this->production, "RssMovie"))
			{
				$dbh->deleteProductionById($this->production->id);
				$this->production->imdb = $this->changeIMDB;
				$rssHandler = new RssHandler();
				$rssHandler->handleNewRssMovie($this->production);
				$this->production = $dbh->getProductionByIMDB($this->changeIMDB);
				$this->title = $this->production->id;
			}
			Logger::enableEcho();
		}
		if($this->manuallyAdd)
		{
			$dbh->manuallyAddRssMovie($this->production);
			$this->production = $dbh->getProduction($this->title);
		}
		
		if($this->title > 0)
			$this->renderTitle($this->title);
			
		if($this->delete > 0)
		{
			$dbh->deleteProductionById($this->delete);
			$this->out .= 'Removed Production<br>';
		}
			
		else if($this->page == 'scanForNewFiles')
			$this->out .= scanVideoFolders(true);
		else if($this->page == 'completeUpdate')
			$this->out .= scanVideoFolders(false);
		else if($this->page == 'scanForNewShowFiles')
			$this->out .= scanShowFolders(true);
		else if($this->page == 'completeUpdateShows')
			$this->out .= scanShowFolders(false);
		else if($this->page == 'cleanup')
			$this->out .= removeProductionsWithoutFiles();
		else if($this->page == 'scan')
			xml2db("videodb.xml");
		else if($this->page == 'newmovies')
			listnewmovies();
		else if($this->page == 'rss')
		{
			$rssHandler = new RssHandler();
			$rssHandler->rss($cfg['tvtorrents_rss_link'],$this->update);
		}
		else if($this->page == 'mineTL')
		{
			$rssHandler = new RssHandler();
			$rssHandler->mineTL($cfg['torrentleech_user'], $cfg['torrentleech_pass'],$this->update);
		}
		else if($this->page == 'rssTPB')  
		{
			$rssHandler = new RssHandler();
			//TODO implement this
			$rssHandler->rssTPB("http://rss.thepiratebay.org/207",$this->update);
		}
		else if($this->page == 'subs')
		{
			getSubtitles($_GET['path']);
		}
		else if($this->page == 'run')
		{
			$files = $this->production->getNonInternetFiles();
			if(count($files) > 0)
			{
				//Watch movie on local computer
				//TODO: fix this for other operating systems as well
				$file = str_replace("/", "\\", $files[0]->fullPath());
				pclose(popen("$file",'r'));
			}
		}
	}
	function renderTitle($productionId)
	{
		if($this->production)
		{
			if(is_a($this->production,'Movie'))
			{
				$this->out .= '<div id="title">';
				
				$this->renderTitleFanartInBackground();
				$this->renderTitlePictureDisplayer();
				$this->renderTitleBackgroundTable();
				
				$this->out .= '</div>';
			}
			else if(is_a($this->production,'Tvshow'))
			{
				global $dbh;
				if($this->season > 0)
				{
					if($this->episode > 0)
					{
						/* List episode info */
					}
					else
					/*List episodes with 0sx0e Title Format with info*/
					$this->menuWithoutParenthesis($dbh->episodesOfSeason($this->title,$this->season),'?page=shows&sub=title&title='.$this->title.'&season='.$this->season.'&episode','?page=shows&sub=title&title='.$this->title);
				}
				else
				{
					/* list seasons(number of episodes in season) */
					$this->menu($dbh->seasonOfShow($this->title),'?page=shows&sub=title&title='.$this->title.'&season','?page=shows&sub=title');
				}
			}
		}
		else
			$this->out .= 'Movie was not found';
	}
	function renderTitlePictureDisplayer()
	{
		$this->out .= '
	    <div id="pictureDisplayer" class="transparent" onclick=\'hidePictureDisplayer();\'>
        	<img id="pictureDisplayerImage" src="images/1x1.placeholder.png">
        </div>';
	}
	function renderTitleFanartInBackground()
	{
		if(is_array($this->production->fanart) && count($this->production->fanart) > 0)
		{
			$this->out .= "<div id='showCornerTableButton'><a href='javascript:displayCornerTable();'>".$this->production->getDisplayTitle().'</a></div>';
			    
			$this->out .= '<script type="text/javascript">
					var fanartArray = new Array();';
			foreach($this->production->fanart as $index => $photo)
				$this->out .= 'fanartArray['.$index.'] = \''.$photo->path.'\';';
			
			$this->out .= "</script>";
		}  
	}
	function renderTitleBackgroundTable()
	{
		$this->out .= '
		<table id="cornerTable" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td align="right"><img src="images/corners/topleft.png" class="cornerimage"></td>
				<td class="cornertopunder"></td>
				<td align="left">';
		if(count($this->production->fanart) > 0) 
			$this->out .= '<img src="images/corners/topright.png" onclick=\'hideCornerTable();\' class="cornerimage">';

		$this->out .= '</td>
			</tr>
			<tr>
				<td align="right" class="cornerside"></td>
				<td class="transparent">';
				
			$this->out .= '
					<table id="titleContainer">
						<tr>
							<td id="titlePosterColumn" valign="top">';
								$this->renderTitlePosterColumn();
					$this->out .= '
							</td>
							<td id="titleMainInfoColumn" valign="top">';
								$this->renderTitleMainInfoColumn();
								$this->renderTitleCastTable();
					$this->out .= '
							</td>
							<td id="titleIconColumn" valign="top">';
								$this->renderTitleIconColumn();
					$this->out .= '
							</td>
						</tr>
					</table>';
			
			$this->out .= '
				</td>
				<td align="left" class="cornerside"></td>
			</tr>
			<tr>
				<td valign="top" align="right"><img src="images/corners/bottomleft.png" class="cornerimage"></td>
				<td class="cornertopunder"></td>
				<td valign="top" align="left"><img src="images/corners/bottomright.png" class="cornerimage"></td>
			</tr>
		</table>';
	}
	function renderTitlePosterColumn()
	{
		//Poster slideshow
		$this->out .= '<script type="text/javascript">
				var posterArray = new Array();
				var posterLinkArray = new Array();';
		$index = 0;
		$maxPosterWidth = round($this->screenWidth/4,0,PHP_ROUND_HALF_DOWN);
		ThumbnailProvider::prepareThumbsFolder(ThumbnailProvider::$POSTER, $maxPosterWidth, $this->screenHeight);	
		foreach($this->production->photos as $photonum => $photo)
		{
			$thumbSrc = ThumbnailProvider::getCreatedPathForImage($photo, ThumbnailProvider::$POSTER, $maxPosterWidth, $this->screenHeight);
			
			$this->out .= 'posterArray['.$index.'] = \''.$thumbSrc.'\';';
			$this->out .= 'posterLinkArray['.$index++.'] = \''.$photo.'\';';
		}
		
		$this->out .= "</script>";
		//First poster is displayed
		$this->out .= '
		<table id="titlePosterTable">
			<tr valign="top" width="'.$maxPosterWidth.'">
            	<td align="center" colspan="25">';//Main Poster
		if(count($this->production->photos))
		{
			$thumbSrc = ThumbnailProvider::getCreatedPathForImage($this->production->photos[0], ThumbnailProvider::$POSTER, $maxPosterWidth, $this->screenHeight);
			$this->out .= '
					<a id="coverhref" href="'.$this->production->photos[0].'">
						<img id="cover" src="'.$thumbSrc.'">
					</a><br>';
		}
		$this->out .= '
                 </td>
             </tr>';
		//Only show LAN computers the location of the file
		//if($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || strpos($_SERVER['REMOTE_ADDR'],'192.168') !== false)
			$this->renderTitleFileInfoRow();
		//else 
		//{
		//	$this->renderTitleKeywordRow();
		//}
			
		//$this->renderTitleKeywordRow();
        $this->out .= '
        </table>';
	}
	function renderTitleMainInfoColumn()
	{
		//Title and Year
		$this->out .= "<h1 id='titlenameheader'>{$this->production->title}";
		
		if(is_numeric($this->production->year))
			$this->out .= " (<a href='?page=".$this->productionPageName."&sub=year&year={$this->production->year}'>{$this->production->year}</a>)";
		
		$this->out .= "</h1>";
		//Genres
		foreach($this->production->genres as $genrenum => $genre)
		{
			$this->out .= '<a href="?page='.$this->productionPageName.'&sub=genre&genre='.$genre[0].'">'.$genre[1].'</a>';
			if($genrenum+1 != count($this->production->genres))
				$this->out .= ' / ';
		}
		
		$this->out .= "<br>";
		//IMDB Rating
		$i = 0.9;
		while($i < $this->production->rating)
		{
			$this->out .= '<img src="images/star.png">';
			$i++;
		}
		if($this->production->rating > $i-0.5)
		{
			$this->out .= '<img src="images/halfstar.png">';
			$i++;
		}
		while($i++ < 10)
		$this->out .= '<img src="images/greystar.png">';
		$this->out .= ' '.round($this->production->rating,1).'/10 ('.$this->production->votes.' votes)<br>';
			
		if($this->production->top250 > 0)
			$this->out .= '<a href="?page=top250">Top 250</a>: #'.$this->production->top250.'<br>';
		
		$this->out .= "<table class='basicInfo'>";

		$this->renderTitleDirectorRow();
		
		if($this->production->tagline != "")
		{
			$this->out .= '<tr>
							<td><h3>Tagline</h3></td>
                            <td>'.$this->production->tagline.'</td>
                           </tr>';
		}
		if($this->production->outline != "")
		{
			$this->out .= '<tr>
                            <td><h3>Outline</h3></td>
                            <td>'.$this->production->outline.'</td>
                           </tr>';
		}
		if($this->production->plot != "")
		{
			$this->out .= '<tr valign="top">
                           	<td><h3>Plot</h3></td>
                            <td>'.$this->production->plot.'</td>
                           </tr>';
		}
		if(is_numeric($this->production->runtime) && $this->production->runtime > 0)
		{
			$this->out .= '<tr valign="top">
			               	<td><h3>Runtime</h3></td>
			                <td>'.$this->production->runtime.' min</td>
			               </tr>';
		}
		if($this->production->mpaa != "")
		{
			$this->out .= '<tr valign="top">
			               	<td><h3>Mpaa</h3></td>
			                <td>'.$this->production->mpaa.'</td>
			               </tr>';
		}
		$this->renderTitleWriterRow();
		
		if(count($this->production->studio) == 2 && $this->production->studio[1] != "")
		{
        	$this->out .= '<tr valign="top">
                           	<td><h3>Studio</h3></td>
                            <td><a href="?page=movies&sub=studios&studio='.$this->production->studio[0].'">'.$this->production->studio[1].'</a></td>
                           </tr>';
		}
		$this->out .= '</table>';
	}
	function renderTitleWriterRow()
	{
		if(count($this->production->writers) > 0)
		{
			if(count($this->production->writers) > 1)
				$writerHeadline = 'Writers';
			else 
				$writerHeadline = 'Writer';
				
			$this->out .= "<tr>
								<td>
									<h3>{$writerHeadline}</h3>
								</td>
								<td>
									<table>";
			
			foreach($this->production->writers as $writer)
			{
						$this->out .= '<tr valign="middle">
											<td>
												<a href="?page=persons&person='.$writer->id.'">'.$writer->name.'</a>
											</td>
											<td>';
						
						if(count($writer->photos) > 0 && is_file($writer->photos[0]->path))
							$this->out .= '	<img src="'.$writer->photos[0]->path.'" class="personphoto" onclick="displayImage(\''.$writer->photos[0]->path.'\');">';
						
						$this->out .= '		</td>
										</tr>';
			}
			
			$this->out .= '			</table>
								</td>
							</tr>';
		}
	}
	
	function renderTitleDirectorRow()
	{
		if(count($this->production->directors) > 0)
		{
			if(count($this->production->directors) > 1)
				$directorHeadline = 'Directors';
			else 
				$directorHeadline = 'Director';
				
			$this->out .= "<tr>
								<td>
									<h3>{$directorHeadline}</h3>
								</td>
								<td>
									<table>";
			
			foreach($this->production->directors as $director)
			{
						$this->out .= '<tr valign="middle">
											<td>
												<a href="?page=persons&person='.$director->id.'">'.$director->name.'</a>
											</td>
											<td>';
						
						if(count($director->photos) > 0 && is_file($director->photos[0]->path))
							$this->out .= '	<img src="'.$director->photos[0]->path.'" class="personphoto" onclick="displayImage(\''.$director->photos[0]->path.'\');">';
						
						$this->out .= '		</td>
										</tr>';
			}
			
			$this->out .= '			</table>
								</td>
							</tr>';
		}
	}
	function renderTitleCastTable()
	{
		if(count($this->production->actors) == 0)
			return;
			
        $this->out .= '
        <table class="basicInfo">
        	<tr><td><h3>Cast</h3></td></tr>
            <tr>
            	<td>
                	<table>';
        $column = 1;
		foreach($this->production->actors as $actor)
		{
			$role = $actor->getActingRoleForProduction($this->production->id);
			if(count($actor->photos) > 0 && is_file($actor->photos[0]->path))
				$this->out .= '	<tr valign="middle" class="actorRow">
									<td><img src="'.$actor->photos[0]->path.'" class="castThumb" onclick="displayImage(\''.$actor->photos[0]->path.'\');"></td>
                        			<td><a href="?page=persons&person='.$actor->id.'">'.$actor->name.'</a></td>
                        			<td class="role"><a href="?page='.$this->page.'&sub=roles&role='.$role.'">'.$role.'</a></td>
                        		</tr>';
			else
				$this->out .= '<tr valign="middle" class="actorRowWithoutImage">
									<td></td>
									<td><a href="?page=persons&person='.$actor->id.'">'.$actor->name.'</a></td>
                        			<td class="role"><a href="?page='.$this->page.'&sub=roles&role='.$role.'">'.$role.'</a></td>
                        		</tr>';

		}
		$this->out .= '</table>
                 </td>
             </tr>
        </table>';
	}
	
	function renderTitleAlsoKnownAs()
	{
		$this->out .= '
		<table width="600px">
        	<tr>
            	<td valign="top"><h2>Also Known As (AKA)</h2></td>
            </tr>
           	<tr>
             	<td>
                	<table>'; 
			foreach($this->production->titles as $title)
			{
				$this->out .= '
						<tr>
							<td>'.$title->title.'</td>
							<td>';
				foreach($title->countries as $countrynum => $country)
				{
					$this->out .= '<a href="?page='.$this->page.'&sub=countrytitles&country='.$country->id.'">'.$country->country.'</a>';
					if($countrynum < count($title->countries)-1) 
						$this->out .= ', ';
				}
				$this->out .= '
							</td>
						</tr>';
			}
		$this->out .= '
                   </table>
                </td>
             </tr>
        </table>';
	}
	
	function renderTitleFanartImages()
	{
		$this->out .= '
		<table>    
        	<tr valign="top">
            	<td width="600px">';
		foreach($this->production->fanart as $photo)
			$this->out .= '
					<a href="'.$photo->path.'"><img src="'.$photo->preview.'" width="200px" height="160px"></a>';
		$this->out .= '
                </td>
            </tr>
        </table>';
	}
	function renderTitleKeywordRow()
	{
		if(count($this->production->keywords) > 0)
		{
			$this->out .= '
				<tr width="100px">
	            	<td valign="top">Keywords</td>
	                <td>';
				foreach($this->production->keywords as $keyword)
				{
					$this->out .= '<a href="?page='.$this->page.'&sub=keyword&keyword='.$keyword->id.'">'.$keyword->word.'</a>,';
				}
			$this->out .= '
	                </td>
	            </tr>';	
		}
	}
	function renderTitleIconColumn()
	{
     	$this->out .= '
     		<a href="http://www.imdb.com/title/tt'.$this->production->imdb.'"><img class="iconThumb" src="images/imdb.png"></a><br>
     		<a href="http://thepiratebay.org/search/'.$this->production->title.'"><img class="iconThumb" src="images/tpb.png"></a><br>
     		<a href="http://www.mrskin.com/search/search?term='.$this->production->title.'"><img class="iconThumb" src="images/mrskin.png"></a><br>
     		<a href="?page=run&title='.$this->production->id.'"><img class="iconThumb" src="images/clapper.png" title="Watch"></a><br>';
     	
     	if($this->production->type == IGNORED_RSSMOVIE)
     		$this->out .= '<a href="?page='.$this->page.'&sub='.$this->sub.'&title='.$this->title.'&manuallyAdd=1" title="Add movie to RSS feed"><img class="iconThumb" src="images/download.png"></a><br>';
     	else if(is_a($this->production,"RssMovie"))
     		$this->out .= '<a href="?page=mineTL&r=1" title="Check RSS feed"><img class="iconThumb" src="images/rss.png"></a><br>';
	}
	
	function renderOrderingChooser()
	{	
		$nextOrder = ($this->order == "DESC" ? "ASC" : "DESC");
		$orderDescription = ($this->order == "DESC" ? "Descending" : "Ascending");
		$this->out .= '<div id="orderingChooser">Sorting by: <a href="'.$this->produceNextLink('orderby', ($this->orderby % NR_OF_SORTINGFIELDS)+1).'">'.$this->orderbyName($this->orderby).'</a> - <a href="'.$this->produceNextLink('order', $nextOrder).'">'.$orderDescription.'</a></div>';	
	}
	
	function renderListViewTypeChooser()
	{
		$this->out .= '<div id="listViewChooser">Viewing as: <a href="'.$this->produceNextLink('listView', ($this->listView % NR_OF_VIEWTYPES)+1).'">'.$this->listViewName($this->listView).'</a></div>';	
	}
	function renderMenu()
	{
		//Do not render a menu if we are displaying a title
		if($this->title > 0)
			return;
		
		global $dbh;
		
		$this->renderOrderingChooser();
		$this->renderListViewTypeChooser();
		//Main menu
		if($this->page == 'main')
		{
			$menu = array(		'scan' => 'Scan XBMC file',
                				'scanForNewFiles' => 'Scan video folders for new files', 
                				'completeUpdate' => 'Update existing movies and add new files',
               					'scanForNewShowFiles' => 'Scan show folders for new files', 
                				'completeUpdateShow' => 'Update existing shows and add new files',
								'cleanup' => 'Clean up library from missing files',
								'failed' => 'List movies that may be wrong',
								'duplicates' => 'List movies with both HD and SD versions',
								'updateFileInfo' => 'Update File Info for files in DB',
                				'newmovies' => 'List new releases not in library',
                				'media' => 'Media', 
                				'subs' => 'Get subtitles', 
                				'rss' => 'View Tv show RSS',
								'rssTL' => 'View Movie RSS',
								'rssTL&update=1' => 'Update Movie RSS',
								'purgeRssMovies' => 'Purge all RSS Movies');
			$this->menuWithoutParenthesis($menu,'?page');
		}
		else if($this->page == 'media')
		{
			$menu = array('movies' => 'Movies','shows' => 'TV','persons' => 'Persons','search' => 'Search','listRssMovies' => 'RSS Movies','listIgnoredRssMovies' => 'Ignored RSS Movies');
			$this->menuWithoutParenthesis($menu,'?page');
		}
		else if($this->page == 'duplicates')
		{
			$this->renderListOfProductions($dbh->getDuplicates($this->orderby,$this->order,$this->listView),'?page=movies&sub=title&title');
		}
		else if($this->page == 'quality')
		{
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,array('file.height','file.height'),array('>','<'),array(500,700)),'?page=movies&sub=title&title');
		}
		else if($this->page == 'file')
		{
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,array('file.filename'),array('LIKE'),array($_GET['filename'])),'?page=movies&sub=title&title');
		}
		else if($this->page == 'top250')
		{
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,array('movie.top250'),array('>'),array(0),'movie.top250'),'?page=movies&sub=title&title');
		}
		else if($this->page == 'top250low')
		{
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,array('movie.top250','file.width'),array('>','<'),array(0,1200),'movie.top250'),'?page=movies&sub=title&title');
		}
		else if($this->page == 'bottom100')
		{
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,array('movie.top250'),array('<'),array(0)),'?page=movies&sub=title&title');
		}
		else if($this->page == 'failed')
		{
			/* List movies with fields not quite correctly looking */
			$lowRuntime = $dbh->listProductions('production.title','ASC',$this->listView,MOVIE,'movie.runtime','<',60);
			$noRating = $dbh->listProductions('production.title','ASC',$this->listView,MOVIE,'production.rating','=',0);
			$productions = array_merge($lowRuntime,$noRating);
			$this->renderListOfProductions($productions,'?page=movies&sub=title&title');
		}
		else if($this->page == 'updateFileInfo')
		{
			updateFileInfoForFilesInDB();
		}
		else if($this->page == 'purgeRssMovies')
		{
			$dbh->deleteRSSMovies();
		}
		else if($this->page == 'movies')
		{
			if($this->sub == '')
			{
				$menu = array('title' => 'Title','year' => 'Year','genre' => 'Genre','keyword' => 'Keywords','actors' => 'Actors','directors' => 'Directors', 'studios' => 'Studios', 'countrytitles' => 'Country Titles','search' => 'Search');
				$this->menuWithoutParenthesis($menu,'?page=movies&sub');
			}
			else if($this->sub == 'search')
			{
				if($this->search)
				{
					$titles = $dbh->movieSearch($this->search);
					$this->out .= '<table class="menu">';
					$this->out .= '<tr><td><a class="menubutton" href="?page=movies&sub=search">..</a></td></tr>';
					foreach($titles as $i => $title) /* key = i,0 - id, 1 - title */
					$this->out .= '     <tr><td><a class="menubutton" href="?page=movies&sub=search&search='.$this->search.'&title='.$title[0].'">'.$title[1].'('.$title[2].')</a></td></tr>';
					$this->out .= '</table>';
				}
				else
				{
					$this->out .= '<form action="?" method="GET">
                                        <input type="hidden" name="page" value="movies">
                                        <input type="hidden" name="sub" value="search">
                                        <input type="text" name="search">
                                        <input type="submit" value="Über search">
                                        </form>';
				}
			}
			else if($this->sub == 'title')
			{
				$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView),'?page=movies&sub=title&title');
			}

			else if($this->sub == 'year')
			{
				/* List years(count) */
				if($this->year > 0)
				{
					$productionsWithYear = $dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,'movie.year', '=', $this->year);
					$this->renderListOfProductions($productionsWithYear,'?page=movies&sub=year&year='.$this->year.'&title');
				}
				else
				{
					$yearObjects = $dbh->listObjects('movie.year','ASC','movie.year', 'movie.year','movie','movie.year',MOVIE);
					$this->renderListOfObjects($yearObjects,'?page=movies&sub=year&year');
				}
			}
			else if($this->sub == 'genre')
			{
				if($this->genre > 0)
				{
					$productionsWithGenre = $dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,'productiongenre.idGenre','=',$this->genre);
					$this->renderListOfProductions($productionsWithGenre,'?page=movies&sub=genre&genre='.$this->genre.'&title');
				}
				else
				{
					$genres = $dbh->listObjects('genre.genre','ASC','genre.id', 'genre.genre','productiongenre','productiongenre.idGenre',MOVIE);
					$this->renderListOfObjects($genres,'?page=movies&sub=genre&genre');
				}
			}
			else if($this->sub == 'keyword')
			{
				if($this->keyword > 0)
				{
					$productionsWithKeyword = $dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,'keywordproduction.idKeyword','=',$this->keyword);
					$this->renderListOfProductions($productionsWithKeyword,'?page=movies&sub=keyword&keyword='.$this->keyword.'&title');
				}
				else
				{
					$keywords = $dbh->listObjects('keyword.keyword','ASC','keyword.id','keyword.keyword','keywordproduction','keywordproduction.idKeyword',MOVIE);
					$this->renderListOfObjects($keywords,'?page=movies&sub=keyword&keyword');
				}
			}
			else if($this->sub == 'countrytitles')
			{
				if($this->country > 0)
				{
					$productionsWithCountry = $dbh->listProductionsWithCountryInOtherTitles($this->orderby,$this->order,$this->listView,$this->country);
					$this->renderListOfProductions($productionsWithCountry,'?page=movies&sub=countrytitles&country='.$this->country.'&title');
				}
				else
				{
					$countries = $dbh->listObjects('country.country','ASC','country.id','country.country','countrytitles','countrytitles.idCountry',MOVIE);
					$this->renderListOfObjects($countries,'?page=movies&sub=countrytitles&country');					
				}
			}
			else if($this->sub == 'studios')
			{
				if($this->studio > 0)
				{
					$productionsFromStudio = $dbh->listProductions($this->orderby,$this->order,$this->listView,MOVIE,'movie.idStudio','=',$this->studio);
					$this->renderListOfProductions($productionsFromStudio,'?page=movies&sub=studios&studio='.$this->studio.'&title');
				}
				else
				{
					/* list the movies that the studio has */
					$studios = $dbh->listObjects('studio.studio','ASC','studio.id','studio.studio','movie','movie.idStudio',MOVIE);
					$this->renderListOfObjects($studios,'?page=movies&sub=studios&studio');
				}
			}
			else if($this->sub == 'roles')
			{
				if($this->role != "")
				{
					$items = $dbh->moviesWithRole($this->role);
					$this->out .= '<table class="menu">';
					$this->out .= '<tr><td><a class="menubutton" href="?page=movies&sub=roles">..</a></td></tr>';
					foreach($items as $id => $item) /* key = id,0 - title, 1 - pid, 2 = pname */
					$this->out .= '     <tr><td><a class="menubutton" href="?page=movies&sub=roles&role='.$this->role.'&title='.$item[3].'">'.$item[0].'</a> played by <a href="?page=movies&sub=actors&actor='.$item[1].'">'.$item[2].'</a></td></tr>';
					$this->out .= '</table>';
				}
				else
					$this->menuWithoutParenthesis($dbh->moviesByRole(),'?page=movies&sub=roles&role');
			}
		}
		else if($this->page == 'shows')
		{
			if($this->sub == '')
			{
				$menu = array('title' => 'Title','genre' => 'Genre','actors' => 'Actors','tvsearch' => 'Search');
				$this->menuWithoutParenthesis($menu,'?page=shows&sub');
			}
			else if($this->sub == 'title')
			{
				/* List shows by title, with poster at rollover */
				$this->menu($dbh->showsByTitle(),'?page=shows&sub=title&title');
			}
			else if($this->sub == 'genre')
			{
				if($this->genre != 0)
					$this->menuWithoutParenthesis($dbh->showsInGenre($this->genre),'?page=shows&sub=genre&genre='.$this->genre.'&title');
				else
				/* List genres(count) */
					$this->menu($dbh->genresInShows(),'?page=shows&sub=genre&genre');
			}
		}
		if($this->page == 'persons')
		{
			if($this->person == '')
			/*list actors , at rollover show image*/
				$this->menu($dbh->actors(),'?page=persons&person','?');
			else
			{
				$person = $dbh->getPersonInfo($this->person, false);
				if(count($person->photos) > 0)
				{
					$this->out .= "<img src='".$person->photos[0]->path."' style='float: left;'/>";
				}
				//BIO stuff
				$this->out .= "<div>
								<h1>".$person->name."</h1>
								<br>Born: ".$person->dob." in ".$person->birthplace."
								<br>Gender: ".(($person->gender == 0) ? "Male" : "Female")."
								<br>Bio: ".$person->bio."
								<br>IMDB: <a href='http://www.imdb.com/name/nm".$person->id."'>Link</a>
								</div><br style='clear: both;'/>";
				
				if(count($person->acting) > 0)
				{
					$this->out .= "<p>Acting in:</p>";
					$this->renderListOfProductions($person->acting, '?page=persons&person='.$this->person.'&title');
				}
				if(count($person->directing) > 0)
				{
					$this->out .= "<br class='clear'/><p>Director of:</p>";
					$this->renderListOfProductions($person->directing, '?page=persons&person='.$this->person.'&title');
				}
				if(count($person->writing) > 0)
				{
					$this->out .= "<br class='clear'/><p>Writer of:</p>";
					$this->renderListOfProductions($person->writing, '?page=persons&person='.$this->person.'&title');
				}
			}
		}
		else if($this->page == 'listRssMovies')
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,RSSMOVIE),'?page=listRssMovies&title');
		else if($this->page == 'listIgnoredRssMovies')
			$this->renderListOfProductions($dbh->listProductions($this->orderby,$this->order,$this->listView,IGNORED_RSSMOVIE),'?page=listIgnoredRssMovies&title');
	}
	
	function renderListOfProductions($productions,$link)
	{
		$this->out .= "<div id='listCount'>".count($productions)." objects</div>";
		switch($this->listView)
		{
			case FLEXICONS:
				$counter = 0;
				foreach ($productions as $prod)
				{
					if(is_a($prod, "Acting"))
						$prod = $prod->production;
						
					$this->out .= "<div class='listViewFlexIconContainer'>";
					if(count($prod->photos) > 0)
						$this->out .= "<img src='".$prod->photos[0]."' class='flexIconPoster'>";
						
					$this->out .= "<br><a href='".$link."=".$prod->id."'>".$prod->getDisplayTitle()."</a>";
					$this->out .= "</div>"; 
					if($counter == 5)
					{
						$this->out .= "<br class='clear'>";
						$counter = 0;
					}
					else
						$counter++;		
				}
				break;
			case SMALLICONS:
				$maxWidth = round(($this->screenWidth - ($this->nrOfColumns * 71)) / $this->nrOfColumns,0,PHP_ROUND_HALF_DOWN);
				$maxHeight = round(($this->screenHeight - ($this->nrOfRows * 130)) / $this->nrOfRows,0,PHP_ROUND_HALF_DOWN);
				$createdThumbsFolder = ThumbnailProvider::prepareThumbsFolder(ThumbnailProvider::$POSTER, $maxWidth, $maxHeight);
				$createdClapperFolder = ThumbnailProvider::prepareThumbsFolder(ThumbnailProvider::$CLAPPER, $maxWidth, $maxHeight);
				$counter = 1;
				$this->out .= "<table class='listViewSmallIconTable'><tr>".PHP_EOL;
				foreach ($productions as $prod)
				{
					if(is_a($prod, "Acting"))
						$prod = $prod->production;
						
					$this->out .= "<td id='Cell".$prod->id."' class='listViewSmallIconPoster' height='".$maxHeight."' style='max-width: ".$maxWidth."px;' valign='top' align='center' 
					onmouseover='highlightCell(\"Cell".$prod->id."\");'
					onmouseout='removeHighlightForCell(\"Cell".$prod->id."\");'>".PHP_EOL.
										"<a href='".$link."=".$prod->id."'>";
					
					$thumbSrc = false;
					
					if(count($prod->photos) > 0)
						$thumbSrc = ThumbnailProvider::getCreatedPathForImage($prod->photos[0],ThumbnailProvider::$POSTER,$maxWidth, $maxHeight);
					if($thumbSrc !== false)
						$this->out .= "<img src='".$thumbSrc."' class='smallIconPoster'>".PHP_EOL;
					else
						$this->out .= "<img src='".ThumbnailProvider::getCreatedPathForImage("images/clapper.png",ThumbnailProvider::$CLAPPER,$maxWidth, $maxHeight)."' class='smallIconPoster'>".PHP_EOL;
					
						
					$this->out .= "<br>".$prod->getDisplayTitle();
					$this->out .= "</a>".PHP_EOL."</td>".PHP_EOL; 
					if($counter == $this->nrOfColumns)
					{
						$this->out .= "</tr><tr>".PHP_EOL;
						$counter = 1;
					}
					else
						$counter++;		
				}
				$this->out .= "</tr></table>".PHP_EOL;
				break;
			case ORDINARY_LIST:
				$this->out .= '<table class="menu">';
				foreach($productions as $prod)
				{
					if(is_a($prod, "Acting"))
						$prod = $prod->production;
						
					$this->out .= '<tr>
										<td>
											<a class="menubutton" href="'.$link.'='.$prod->id.'">
												'.$prod->getDisplayTitle().'
											</a>
										</td>
									</tr>';
				}
				$this->out .= '</table>';	
				break;
				//TODO: take more $this->listView types into account
		}
	}
	
	function renderListOfObjects($objects,$link)
	{
		$this->out .= "<div id='listCount'>".count($objects)." objects</div>";
		//TODO: take $this->listView into account
		$this->out .= '<table class="menu">';
		foreach($objects as $object)
		{
			$this->out .= '<tr>
								<td>
									<a class="menubutton" href="'.$link.'='.$object->id.'">
										'.$object->getDisplayName().'
									</a>
								</td>
							</tr>';
		}
		$this->out .= '</table>';	
	}
	
	function renderListOfPersons($persons,$link)
	{
		//TODO: take $this->listView into account
		$this->out .= '<table class="menu">';
		foreach($persons as $person)
		{
			$this->out .= '<tr>
								<td>
									<a class="menubutton" href="'.$link.'='.$person->id.'">
										'.$person->getDisplayName().'
									</a>
								</td>
							</tr>';
		}
		$this->out .= '</table>';	
	}
	
	function menu($items,$link)
	{
		$this->out .= '<table class="menu">';
		foreach($items as $id => $item) /* key = id,0 - title, 1 - (info) */
		{
			$this->out .= '     <tr><td><a class="menubutton" href="'.$link.'='.$id.'">'.$item[0];
			if($item[1] != '') /* If the () should be empty */
			$this->out .= ' ('.$item[1].')';
			$this->out .= '</a></td></tr>';
		}
		$this->out .= '</table>';
	}
	function menuWithNumber($items,$link)
	{
		$this->out .= '<table class="menu">';
		foreach($items as $id => $item) /* key = id,0 - title, 1 - (info) */
		{
			$this->out .= '<tr><td><a class="menubutton" href="'.$link.'='.$id.'">'.$item[0].'('.$item[1].')</a></td><td align="right">'.$item[2].'</td></tr>';
		}
		$this->out .= '</table>';
	}
	function menuWithoutParenthesis($items,$link)
	{
		$this->out .= '<table class="menu">';
		foreach($items as $id => $item) /* key = id,0 - title, 1 - (info) */
		{
			$this->out .= '     <tr><td><a class="menubutton" href="'.$link.'='.$id.'">'.$item.'</a></td></tr>';
		}
		$this->out .= '</table>';
	}
	function renderTitleFileInfoRow()
	{
		$maxPosterColumnWidth = round($this->screenWidth*0.3,0,PHP_ROUND_HALF_DOWN);
		
		if(is_a($this->production,"RssMovie"))
		{
			$this->out .= '<tr><td colspan="6">Time Released: '.$this->production->timeReleased.'</td></tr>';
			if(strpos($this->production->link,"TL_web") !== false)
			{
				$torrentId = StringUtil::scanint($this->production->link,false,0);
				$this->out .= '<tr><td colspan="6"><a href="http://www.torrentleech.org/torrent/'.$torrentId.'">Torrentleech link</a></td></tr>';
				if(is_file("torrents/TL_web_".$torrentId.".nfo"))
					$this->out .= '<tr><td colspan="6"><a href="torrents/TL_web_'.$torrentId.'.nfo">View NFO</a></td></tr>';
			}
			
		}
		
		foreach($this->production->files as $file)
		{
				$this->out .= '<tr valign="middle" style="max-width: '.$maxPosterColumnWidth.'px">
                                  <td valign="top"><img src="images/hdd.png"></td>
                                  <td colspan="6">'.$file->path.$file->filename.'<br>
                                  	  Subtitle: '.($file->hassubtitle ? 'Yes' : ' No').'<br>
                                      Playcount: '.$file->playcount.'<br>
                                      Format: '.$file->format.'<br>
                                      Filesize: '.$file->filesize.' GiB<br>
                                      Duration: '.$file->duration.' mins<br>
                                      Video bitrate: '.$file->videobitrate.' Mbps<br>
                                      Width: '.$file->width.' pixels<br>
                                      Height: '.$file->height.' pixels<br>
                                      Aspect ratio: '.$file->ar.'<br>
                                      Writing library: '.$file->writinglibrary.'<br>
                                      Time added: '.$file->timeAdded.'<br>';
				$i = 1;
				foreach($file->audiotracks as $audiotrack)
				{
					$this->out .= 'Audio track #'.($i++).': <br>';
					if($audiotrack->title)
					$this->out .= 'Title: '.$audiotrack->title.'<br>';
					$this->out .= 'Language: '.$audiotrack->language.'<br>
	                               Audio format: '.$audiotrack->format.' ('.$audiotrack->formatinfo.')<br>
	                               Audio bitrate: '.$audiotrack->bitrate.' Kbps<br>
	                               Channels: '.$audiotrack->channels.'<br>';
				}
				$this->out .= '		</td>
                               <td></td>
                             </tr>';
		}
	}
	
	public function render()
	{
		$this->renderHead();	
		//Makes sure that we have the client's resolution
		if(!isset($_GET['r']) && !isset($_SESSION['xbmc2web.resolutionFetched']))
		{
			$_SESSION['xbmc2web.resolutionFetched'] = true;
			$url = $_SERVER['REQUEST_URI'];
			if(strpos($url, "?") === false) 
				$url .= "?";
			else
				$url .= "&";
			$this->out .= '<script language="JavaScript">
			<!-- 
			document.location="'.$url.'r=1&screenWidth="+screen.width+"&screenHeight="+screen.height;
			//-->
			</script>';
		}
		else
		{
			$this->renderMenu();
			$this->renderBody();
		}
		$this->renderTail();
	}
	
	private function listViewName($listView)
	{
		switch($listView)
		{
			case SMALLICONS:
				return "Grid";
			case FLEXICONS:
				return "Stretchable Icons";
			case ORDINARY_LIST:
				return "List";
			case MEDIA_LIST:
				return "Media List";
			case FANART:
				return "Fanart";
		}
		return "";
	}
	
	private function orderbyName($orderByNumber)
	{
		switch($orderByNumber)
		{				
			case 1:
				return "Name";
			case 2:
				return "Rating";
			case 3:
				return "Year";
			case 4:
				return "Votes";
			case 5:
				return "ID";
			case 6:
				return "Runtime";
			case 7:
				return "Top 250";
			case 8:
				return "Time added";
			case 9:
				return "Quality";
		}
		return "";	
	}
	
	private function produceNextLink($variableName,$newValue)
	{
		if(isset($_GET[$variableName]))
			$oldvalue = $_GET[$variableName];
		
		$_GET[$variableName] = $newValue;
		
		$link = $_SERVER['SCRIPT_NAME'].'?';
		foreach($_GET as $name => $value)
			$link .= htmlentities($name).'='.htmlentities($value).'&';
			
		if(isset($oldvalue))
			$_GET[$variableName] = $oldvalue;
		else 
			unset($_GET[$variableName]);
		
		return $link;
	}
}
?>
