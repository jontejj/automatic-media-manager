<?php
//Test commit
$movieformats = array("mkv","avi","ts","wmv","divx","m2ts","mpg","mp4", "mov");
$acceptableformats = array("iso", "rar","nrg","img");

foreach($movieformats as $format)
	$acceptableformats [] = $format;

$dvdFileNames = array('VIDEO_TS.VOB','VIDEO_TS.vob','VIDEO_TS.ifo','VIDEO_TS.IFO');

$seperators = array(' ','.','-',',','_','[',']');
$stack = array();
function removeEmptyFolders($folder)
{
	$dir = DirectoryUtil::scandir($folder);

	if(count($dir) == 0)
	{
		echo "removing".$folder."<br>";
		rmdir($folder);
	}
	else
	{
		foreach($dir as $filerecord)
		{
			if(is_dir($folder.$filerecord))
				removeEmptyFolders($folder.$filerecord.'/');
		}
	}
}
function calcFileSize($size,$folder)
{
	$dir = DirectoryUtil::scandir($folder);

	foreach($dir as $filerecord)
	{
		if(is_dir($folder.$filerecord))
			$size = calcFileSize($size,$folder.$filerecord.'/');
		else
		{
			if(strpos($folder,'/kvar/DVD') !== false || strpos($folder,'/kvar/Orginal') !== false)
				$size +=4.2;
			else
				$size +=0.7;
		}
	}
	return $size;
}

function removeProductionsWithoutFiles()
{
	global $dbh;
	$files = $dbh->getFilePathsAndFiles();
	$html = "Removed productions for files:<br><table>";
	foreach($files as $file => $prodId)
	{
		if(substr($file,0,8) != 'internet')
		{
			if(!isVideoTsFolder($file) && !is_file($file))
			{
				$path = substr($file,0,strrpos($file,'/')+1);
				$filename = substr($file,strlen($path));
				$dbh->deleteProduction($path,$filename);
				$html .= '<tr><td>'.$path.$filename.'</td></tr>';
			}
		}
	}
	return $html."</table>";
}

function updateFileInfoForFilesInDB()
{
	global $dbh,$movieformats;
	$filesInDB = $dbh->getFilesFromDB();
	foreach($filesInDB as $file)
	{
		//$filetype = strtolower(substr($file->filename,strrpos($file->filename,'.')+1));
		$file->getMediaInfo();
		$dbh->addFile($file);
	}
}

function setInternetMoviesAsInternetMovies()
{
	global $dbh;
	$movies = $dbh->movieProductions();
	foreach($movies as $movie)
	{
		if(count($movie->getNonInternetFiles()) == 0 && $movie->rssMovieId > 0)
		{
			if($movie->looksLikeAGoodMovie())
				$movie->type = RSSMOVIE;
			else
				$movie->type = IGNORED_RSSMOVIE;
	 		$dbh->setTypeForProduction($movie);
		}
	}
}

function scanVideoFolders($onlyNewFiles = true,$checkIMDBLinksInNFOFiles = false)
{
	$html = "";
	$start = time();
	global $dbh,$stack,$cfg;

	$stack = array();
	Logger::echoText("Started on: ".date('Y-m-d H:i:s').PHP_EOL);
	Logger::echoText("Scanning movie folders".PHP_EOL);
	foreach($cfg['moviefolders'] as $folder)
	{
		stackAndRenameMovies($folder);
	}
	Logger::echoText("Scan Complete. Found: ".count($stack)." different files (stacked items ignored)".PHP_EOL);
	Logger::echoText("Scraping for movie info".PHP_EOL);
	$filesInDB = $dbh->getFilePathsAndFiles();
	$html .= '<table>';
	$resume = false;
	$addedMap = array();
	foreach($stack as $stackitem)
	{
		foreach($stackitem->files as $filename)
		{
		 	//Tests how the filename cleaner works
			/*$production = new Movie($stackitem->path, $filename, $stackitem->name, true);
			$fullpath = $stackitem->path.$filename;
			$pathWithFileExludingFiletype = substr($fullpath,0,strrpos($fullpath,'.'));
			$production->getTitle();
			//$production->lookForIMDBInNfoFile($pathWithFileExludingFiletype);

			$html .= '<tr><td>'.$stackitem->path.$filename.'<br><div class="red">'.$production->getDisplayTitle().'</div></tr>';
			*/

			if(!$onlyNewFiles || ($onlyNewFiles && !isset($filesInDB[$stackitem->path.$filename])))
			{
				if(isset($addedMap[$stackitem->path.$stackitem->name]))
				{
					//If we have added the production already during this run, perhaps this is cd 2 or something
					$file = new FFile($stackitem->path, $filename);
					$file->getMediaInfo();
					$file->productionId = $addedMap[$stackitem->path.$stackitem->name];
					$dbh->addFile($file);
				}
				else
				{
					$pathWithFileExludingFiletype = substr($stackitem->path.$filename,0,strrpos($stackitem->path.$filename,'.'));
					if($checkIMDBLinksInNFOFiles)
					{
						$test = new Movie($stackitem->path, $filename, $stackitem->name,true);
						$nfoLocation = DirectoryUtil::getPathToNfoFileConnectedToMovieFile($pathWithFileExludingFiletype);
	        			$found = $test->lookForIMDBInNfoFile($nfoLocation);
	        			if($test->hasProperIMDBNumber() && $found == FOUND_ID)
	        			{
	        				$nfoImdb = $test->imdb;
	        				$test->imdb = "";
							MovieMiner::getImdbId($test,true);
							//The direct hit from imdb does not match the imdb from the NFO file
							if($test->hasProperIMDBNumber() && $test->imdb != $nfoImdb)
							{
								Logger::nfoFileWithBadLink($nfoLocation);
								$html .= '<tr><td>'.$pathWithFileExludingFiletype.'</td></tr>';
							}
	        			}
	        			break;
					}

					if(isset($filesInDB[$stackitem->path.$filename]))
					{
						//An update for an old movie
						$productionFromDatabase = $dbh->getProduction($filesInDB[$stackitem->path.$filename],true);
					}
					else
					{
						$production = new Movie($stackitem->path, $filename, $stackitem->name);
						$production->getImdbId($pathWithFileExludingFiletype);
						//Is there already a movie with the same imdb but another path?
						$productionFromDatabase = $dbh->getProductionByIMDB($production->imdb);

						//If the Title parser made IMDB return a wrong match, then the nfo file could tell us the correct one
						if(MovieMiner::isProperIMDBNumber($production->nfoIMDB)
						&& MovieMiner::isProperIMDBNumber($production->torrentIMDB)
						&& $production->torrentIMDB != $production->nfoIMDB)
						{
							$productionByTorrent = $dbh->getProductionByIMDB($production->torrentIMDB);
							Logger::missMatchBetweenIMDBlookupAndNFOFile($production, $productionByTorrent, $stackitem->path.$filename);
						}
					}

					if($productionFromDatabase !== false)
					{
						Logger::echoText("The movie for: ".$filename." was already in the database.".PHP_EOL);
						if(!$onlyNewFiles)
						{
							$productionFromDatabase->getImdbInfo($pathWithFileExludingFiletype);
							$dbh->addProduction($productionFromDatabase);
						}
						else
							Logger::echoText("But the file: {$filename} was new.".PHP_EOL);

						$file = new FFile($stackitem->path, $filename);
						$file->getMediaInfo();
						$file->productionId = $productionFromDatabase->id;
						$dbh->addFile($file);
						$dbh->markRssMovieAsLibraryMovie($productionFromDatabase);
						$addedMap[$stackitem->path.$stackitem->name] = $productionFromDatabase->id;
					}
					else if($production->hasProperIMDBNumber())
					{
						Logger::echoText("New file: ".$filename.PHP_EOL);
						//New file, get more info and add it
						$production->getImdbInfo($pathWithFileExludingFiletype);
						$dbh->addProduction($production);
						$addedMap[$stackitem->path.$stackitem->name] = $production->id;
					}
				}
			}
		}
	}
	$html .= '</table>';
    $end = time();
    Logger::echoText("Scan took ".($end-$start)." seconds".PHP_EOL);
    Logger::echoText("Finished: ".date('Y-m-d H:i:s').PHP_EOL);
    return $html;
}

function stackAndRenameMovies($folder)
{
	global $stack;
	if(is_readable($folder))
	{
		if(is_dir($folder))
		{
			//Makes sure the folder path ends with a slash
			if(strrpos($folder,'/') != strlen($folder)-1)
				$folder .= '/';

			$dir = DirectoryUtil::scandir($folder);
			//If it was a successful scan folder
			if($dir !== false)
			{
				foreach($dir as $filerecord)
				{
					if((isVideoTsFolder($folder.$filerecord) || is_file($folder.$filerecord)) && !tvShowInfoFromFullFilePath($folder.$filerecord))
						stackFile($filerecord,$folder);
					else
					{
						if(is_dir($folder.$filerecord.'/'))
							stackAndRenameMovies($folder.$filerecord.'/');
					}
				}
			}
		}
		else if(is_file($folder) && !tvShowInfoFromFullFilePath($folder))
		{
			$folderPath = substr($folder,0,strrpos($folder,'/')+1);
			$filename = substr($folder,strrpos($folder,'/')+1);
			stackFile($filename,$folderPath);
		}
	}
}

function stackFile($filerecord,$folder)
{
	//We ignore sample videos
	if(!isSampleVideoFile($filerecord) && !parentFolderIndicateNotMovie($folder))
	{
		if(is_file($folder.$filerecord))
			$withoutfiletype = substr($filerecord,0,strrpos($filerecord,'.'));
		else
			$withoutfiletype = $filerecord;

		//Movie file or folder?
		$filetype = strtolower(substr($filerecord,strrpos($filerecord,'.')+1));
		global $acceptableformats,$stack;
		if(in_array($filetype,$acceptableformats) || is_dir($folder.$filerecord) || isVideoTsFolder($folder.$filerecord))
		{
			$stackIndex = count($stack);

			//If the filerecord contains a dot or space,
			//extract last part,
			//remove cd/dvd etc,
			//check if the remainder is numeric
			$char = ' ';
			if(strrpos($withoutfiletype,'.') !== false)
				$char = '.';
			//Test for: movie(2008).cd1.avi or movie(2008) cd1.avi
			$fileending = strtolower(substr($withoutfiletype,strrpos($withoutfiletype,$char)+1));
			$fileending = str_replace(array('cd','dvd','part','episode','e',' '),'',$fileending);
			if(is_numeric($fileending)) //Stackable
				$name = substr($withoutfiletype,0,strrpos($withoutfiletype,$char));

			//Default non-stackable files gets set
			if(!isset($name))
				$name = $withoutfiletype;

			//Check stack for the name
			foreach($stack as $index => $stackItem)
			{
				if($stackItem->name == $name && $stackItem->path == $folder)
				{
					$stackIndex = $index;
					break;
				}
			}

			//If there isn't a stackitem at stackindex create one
			if(!isset($stack[$stackIndex]))
				$stack[$stackIndex] = new StackableFile($name,$folder,array($filerecord));
			else
				$stack[$stackIndex]->files[] = $filerecord;
		}
	}
}

function parentFolderIndicateNotMovie($folder)
{
	$lastFolderStartIndex = strrpos($folder,'/',-2);
	if($lastFolderStartIndex !== false)
	{
		if(stripos(substr($folder,$lastFolderStartIndex),'sample/') !== false)
			return true;
		else if(stripos(substr($folder,$lastFolderStartIndex),'subs/') !== false)
			return true;
	}
	return false;
}

function isSampleVideoFile($filename)
{
	global $seperators;
    $sampleIndex = stripos($filename,'sample');
    if($sampleIndex !== false)
    {
    	//makes sure there are seperators sorrounding the sample word
    	if($sampleIndex-1 > 0) //The reserved word needs to have something before it
    	{
    		if(!in_array(substr($filename,$sampleIndex-1,1),$seperators))
    			return false;

    		//The seperator to the right
	    	if(strlen($filename) > $sampleIndex+6)
	    	{
		    	if(!in_array(substr($filename,$sampleIndex+6,1),$seperators))
		    		return false;
	    	}
    	}
    	return true;
    }
    return false;
}

function isVideoTsFolder($folder)
{
	global $dvdFileNames;
	foreach($dvdFileNames as $dvd)
	{
		if(is_file($folder.'/'.$dvd))
			return '/'.$dvd;
	}
	return false;
}
?>