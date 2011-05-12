<?php
function scanShowFolders($onlyNewFiles = true)
{
	$html = "";
	/*
	$size = calcFileSize(0,"F:/kvar/");
	echo $size." GB's needed<br>";
	//removeEmptyFolders("F:/kvar/");
	die();
	*/
	$start = time();
	global $dbh,$acceptableformats,$stack;
	$showfolders = array("G:/","H:/","M:/TV shows/");
	//Test cases
	//$showfolders = array("C:/wamp/www/xbmc2webNew/testcases");

	$subtitlesformats = array("srt","sup","sub");
	$stack = array();
	Logger::echoText("Started on: ".date('Y-m-d H:i:s').PHP_EOL);
	Logger::echoText("Scanning show folders".PHP_EOL);
	foreach($showfolders as $folder)
	{
		stackAndRenameShows($folder);
	}
	Logger::echoText("Scan Complete. Found: ".count($stack)." different files (stacked items ignored)".PHP_EOL);
	Logger::echoText("Scraping for show info".PHP_EOL);
	$filesInDB = $dbh->getFilePathsAndFiles();
	
	$html .= '<table>';
	$tvshows = array();
	foreach($stack as $stackitem)
	{
		foreach($stackitem->files as $filename)
		{
			$fullpath = $stackitem->path.$filename;
			$filetype = strtolower(substr($filename,strrpos($filename,'.')+1));
			if(in_array($filetype,$acceptableformats) || isVideoTsFolder($fullpath))
			{
				if($onlyNewFiles || !isset($filesInDB[$stackitem->path.$filename]))
				{
					$season = tvShowInfoFromFullFilePath($fullpath,1);	
					$episode = tvShowInfoFromFullFilePath($fullpath,2);		
					$tvshow = tvShowInfoFromFullFilePath($fullpath,3);	
					$found = false;
					foreach($tvshows as $show)
					{
						if($show->title == $tvshow)
						{
							$show->episodes[] = new Episode($season,$episode,$tvshow,$stackitem->path, $filename, $stackitem->name);
							$found = true;
							break;
						}
					}
					if(!$found)
					{
						$show = new Tvshow();
						$show->title = $tvshow;
						$show->episodes[] = new Episode($season,$episode,$tvshow,$stackitem->path, $filename, $stackitem->name);
						$tvshows[] = $show;
					}
				}
			}
		}
	}
	foreach($tvshows as $tvshow)
	{
		$tvshow->getImdbId("");
		if($tvshow->hasProperIMDBNumber())
			$productionFromDatabase = $dbh->getProductionByIMDB($tvshow->imdb);
		else
			$productionFromDatabase = false;
			
		if($productionFromDatabase === false)
		{
			Logger::echoText("New show: ".$tvshow->getDisplayTitle().PHP_EOL);
			//New file, get more info and add it
			$tvshow->getImdbInfo();
			$tvshow->getInfoForEpisodes();
			$dbh->addProduction($tvshow);
		}
		else
		{
			Logger::echoText("The tvshow for: ".$filename." was already in database. But a episode was new.".PHP_EOL);
			MovieMiner::getEpisodesFor($tvshow);
			$tvshow->getInfoForEpisodes();
			$tvshow->episodes = array_merge($productionFromDatabase->episodes, $tvshow->episodes);
			$dbh->addProduction($tvshow);
		}
	}
	$html .= '</table>';
    $end = time();
    Logger::echoText("Scan took ".($end-$start)." seconds".PHP_EOL);
    Logger::echoText("Finished: ".date('Y-m-d H:i:s').PHP_EOL);
    return $html;
}
function stackAndRenameShows($folder)
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
					if((isVideoTsFolder($folder.$filerecord) || is_file($folder.$filerecord)) && tvShowInfoFromFullFilePath($folder.$filerecord))
						stackFile($filerecord,$folder);
					else
					{
						if(is_dir($folder.$filerecord.'/'))
							stackAndRenameShows($folder.$filerecord.'/');
					}
				}	
			}
		}
		else if(is_file($folder) && tvShowInfoFromFullFilePath($folder))
		{
			$folderPath = substr($folder,0,strrpos($folder,'/')+1);
			$filename = substr($folder,strrpos($folder,'/')+1);
			stackFile($filename,$folderPath);
		}
	}
}

/**
 * Specify 
 * type 0: to get a boolean value if the fullpath indicates that the file is an episode
 * type 1: get Season
 * type 2: get EpisodeNumber
 * type 3: get TvShow name
**/
function tvShowInfoFromFullFilePath($fullpath, $type = 0)
{
	//Filename could either be in the format 
	//Tvshow.S01E22.Random shit.avi
	$pathComponents = explode('/',$fullpath);
	$filename = $pathComponents[count($pathComponents)-1];
	$length = strlen($fullpath);
	$offset = 0;
	while($offset !== false && $offset < $length)
	{
		$offset = stripos($fullpath,'.s',$offset);
		if($offset !== false)
		{
			$tvshowStart = strrpos($fullpath,'/',$offset - strlen($fullpath));
			$tvshowName = substr($fullpath,$tvshowStart+1,$offset-$tvshowStart-1);
			$offset += 2;
			$season = StringUtil::scanint2(substr($fullpath,$offset));
				
			if($season !== false)
			{
				$offset += strlen($season);
				if(strtolower($fullpath[$offset]) == 'e' || strtolower($fullpath[$offset]) == 'd')
				{
					$offset++;
					$episode = StringUtil::scanint2(substr($fullpath,$offset));
					if($episode !== false)
					{
						if($type == 0)
							return true;
						else if($type == 1)
							return $season;
						else if($type == 2)
							return $episode;
						else if($type == 3)
							return $tvshowName;
					}	
				}	
			}
		}
	}	
	//or 1x22 - Title of Episode.avi
	$season  = StringUtil::scanint2($filename);
	if($season !== false)
	{
		$offset = strlen($season);
		if($filename[$offset] == 'x')
		{
			$episode = StringUtil::scanint2(substr($filename,$offset+1));
			if($episode !== false)
			{
				if($type == 0)
					return true;
				else if($type == 1)
					return $season;
				else if($type == 2)
					return $episode;
				else if($type == 3)
					//The parent folder is the TVshow's name
					return $pathComponents[count($pathComponents)-2];
			}
		}
	}
	
	$seasonPos = stripos($filename, "season");
	if($seasonPos !== false)
	{
		$season = StringUtil::scanint2(substr($filename,$seasonPos+7));
		if($season > 0)
		{
			$tvshowStart = strrpos($fullpath,'season',$seasonPos - strlen($fullpath));
			$tvshowName = substr($fullpath,$tvshowStart+1,$seasonPos-$tvshowStart-1);
			
			$episode = false;
			$isEpisodeStr = (strtolower(substr($filename,($seasonPos+2+strlen($season)),7)) == 'episode');
			if($isEpisodeStr)
				$episode = StringUtil::scanint(substr($filename,$seasonPos),false,1);
				
			if($type == 0)
				return true;
			else if($type == 1)
				return $season;
			else if($type == 2)
				return $episode;
			else if($type == 3)
				return $tvshowName;
		}
	}
	
	if($type == 0)
	{
		$offset = strpos($filename,'S');
		while($offset !== false)
		{
			if(StringUtil::scanint2(substr($filename, $offset+1)) !== false)
				return true;
			$offset = strpos($filename,'S',$offset+1);
		}
	}
	
	return false;
}