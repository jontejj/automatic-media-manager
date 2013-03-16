<?php
$reservedwords = array(
				'720p','720P','1080P','1080i','480p','x264','Bluray','Blu-ray','BluRay',',bluray','HD','BD','HDTV','X360','PS3',
				'Extended','Directors.Cut','Directors Cut','DirCut','DC','UNCUT','UNRATED','Unrated','LIMITED','LiMiTED','Limited','THEATRICAL',
				'REPACK','PROPER','RERIP','REAL','DUBBED','SUBBED','INTERNAL','iNTERNAL','HDre','REMASTERED','Special','Edition',
				'BDr','BDRE','eng','swe','German','ENG','SWE','MULTi','MULTiSUBS','multisub',
				'DTS','DTSMA',
				'SiDE A','SiDE B','Side B','SIDE B','DVD-r','DVD-R','DVD5','DL','NTSC','PAL',
				'XViD','XviD','Xvid', 'TELESYNC','BRRip','R5','DVDRip','BDRiP','STV');
$seperators = array('.','-',',','_','[',']',' ','(',')');
$seperatorsAsString = ' .-,_[]()';
function stripReleaseInfoFromTitle($title, $getYear = false)
{
	global $reservedwords,$acceptableformats,$seperators,$seperatorsAsString;

	//If there is a date in the beginning strip it from the rest
	$y = substr($title,0,4);
	$m = substr($title,5,2);
	$d = substr($title,8,2);
	if(is_numeric($y) && $y > 1800 && is_numeric($m) && $m > 0 && $m < 13 && is_numeric($d) && $d > 0 && $d < 32)
		$movie = substr($title,11);
	else
		$movie = $title;


    $filetype = strtolower(substr($title,strrpos($title,'.')+1));
    if(!in_array($filetype,$acceptableformats))
    	$filetype = "";
    else
    	$filetype = '.'.$filetype;


    //More readable without dots and underscores
    //Replacing dots followed by any other character than a space
    $dotsreplaced = "";
    $year = 0;
    $lastDot = 0;
    $yearStart = 0;
    $potentialYearStart = 0;
    $lookingForYear = false;
    for($i = 0;$i<strlen($movie);$i++)
    {
    	$ignoreChar = false;
    	if($movie[$i] == '.')
    	{
    		if($i+1 < strlen($movie) && ($movie[$i+1] == '.' || $movie[$i+1] == ' '))
    			$dotsreplaced .= '.';
    		else
    		{
	    		//If it is a dot and there is not a coming scene word, keep the word
	    		$sceneWord = substr($movie,$i+1,strpos($movie,'.',$i+1)-$i-1);
	    		if($sceneWord !== false && strlen($sceneWord) > 1)
	    		{
		    		foreach($reservedwords as $index => $word)
		    		{
		    	 		if(stripos($sceneWord,$word))
		    	 			$ignoreChar = true;
		    		}
	    		}
	    		//Check for a year
				if(!$ignoreChar)
	    		{
	    			$potentialYear = StringUtil::scanint(substr($movie,$lastDot+1,$i-($lastDot+1)),false,0);
	    			if($potentialYear > 1800 && $lastDot != 0)
	    			{
	    				$year = $potentialYear;
	    				$yearStart = $lastDot;
	    				$dotsreplaced .= ' ';
	    			}
	    			else
	    			{
	    				//Clever dot replacement
	    				if(strlen($sceneWord) <= 1)
	    				{
	    					if(substr_count($title, ' ') == 0)
	    						$dotsreplaced .= ' ';
	    					else
	    						$dotsreplaced .= '.';
	    				}
	    				else
	    				{
		    				$nextDot = strpos($movie,'.',$i+1);
		    				$nextSpace = strpos($movie,' ',$i+1);
		    				if($nextSpace == false)
		    					$dotsreplaced .= ' ';
		    				else if($nextSpace < $nextDot || $nextDot === false)
		    					$dotsreplaced .= '.';
	    				}
	    			}
	    			$lastDot = $i;
	    		}
				else
					$dotsreplaced .= ' ';
    		}
    	}
    	else if($movie[$i] == '(' || ($movie[$i] == ' ' && !$lookingForYear))
    	{
    		$potentialYearStart = $i;
    		$dotsreplaced .= $movie[$i];
    		$lookingForYear = true;
    	}
    		//Checks for a year anywhere in the filename
    	else if($movie[$i] == ')' || ($movie[$i] == ' ' && $lookingForYear))
    	{
    		if(strlen($movie) > $potentialYearStart+5)
    		{
    			$potentialYear = StringUtil::scanint(substr($movie,$potentialYearStart+1,$i-($potentialYearStart+1)),false,0);
    			if(is_numeric($potentialYear) && $potentialYear > 1800)
    			{
    				$year = $potentialYear;
    				$yearStart = $potentialYearStart;
    			}
    		}
    		$dotsreplaced .= $movie[$i];
    		if($movie[$i] == ' ')
    		{
    			$potentialYearStart = $i;
    			$lookingForYear = true;
    		}
    		else
    			$lookingForYear = false;
    	}
    	else
    		$dotsreplaced .= $movie[$i];
    }
    //If the filename ends with a year
    if($lookingForYear && strlen($movie) > $potentialYearStart+5)
    {
    	$potentialYear = StringUtil::scanint(substr($movie,$potentialYearStart+1,$i-($potentialYearStart+1)),false,0);
    	if(is_numeric($potentialYear) && $potentialYear > 1800)
    	{
    		$year = $potentialYear;
    		$yearStart = $potentialYearStart;
    	}
    }

    $movie  = str_replace('_',' ',$dotsreplaced);

	$places = array();
    foreach($reservedwords as $index => $word)
    {
    	$places[$index] = stripos($movie,$word);
    	if($places[$index] !== false)
    	{
    		//makes sure there are seperators sorrounding the reserved word
    		if($places[$index]-1 > 0) //The reserved word needs to have something before it
    		{
    			if(!in_array(substr($movie,$places[$index]-1,1),$seperators))
    				$places[$index] = false;

		    	if(strlen($movie) > $places[$index]+strlen($word)+1)
		    	{
			    	if(!in_array(substr($movie,$places[$index]+strlen($word),1),$seperators))
			    		$places[$index] = false;
		    	}
    		}
    		else
    			$places[$index] = false;
    	}
    }
    $lowest = -1;
    foreach($places as $index => $place)
    {
    	if(($lowest == -1 || $place < $lowest) && $place !== false)
    		$lowest = $place;
    }

    if($year > 1800)
    {
    	$offset = $yearStart;

    	if($lowest != -1)
    		$offset = min($lowest,$yearStart);

    	$movie = substr($movie,0,$offset);
    	//Replaces shitty characters in the end of the string
    	//if(substr_count($movie,'.') > 1)
    	//	$movie = rtrim($movie,$seperatorsAsString);
    }
    else
    {
	    //Check for a year in the end of the filename
	    $year = trim(substr($movie,-6),$seperatorsAsString);
	    if(is_numeric($year) && $year > 1800)
	    {
	    	$movie = substr($movie,0,-5);
	    	$movie = rtrim($movie,$seperatorsAsString);
	    }
	    else if($lowest != -1)
	    {
    		//Strips away the scene stuff
    		$movie = substr($movie,0,$lowest);

	    	$releaseinfo = substr($title,$lowest);
	    	$parts = explode('.',$releaseinfo);
	    	$year = 0;
	    	foreach($parts as $part)
	    	{
	    		if(is_numeric($part) && $part > 1800)
	    		{
	    			$year = $part;
	    			break;
	    		}
	    	}
	    }
    }
	if($getYear)
	{
		if(!is_numeric($year) || $year < 1800)
			return false;
		else
			return $year;
	}
	else
	{
		//Log to keep track of how well the script performs
		Logger::titleParsed($title, trim($movie,' '), $year);
	}
    return trim($movie,' ');
}
function hasYearInTitle($title)
{
	return stripReleaseInfoFromTitle($title,true);
}

function renameSceneFiles($dir)
{
    $movies = scandir($dir);
	//Clean result from parent and current folder
	$currentDirIndex = array_search('.',$movies);
	$parentDirIndex = array_search('..',$movies);
	if($currentDirIndex !== FALSE)
		unset($movies[$currentDirIndex]);
	if($parentDirIndex !== FALSE)
		unset($movies[$parentDirIndex]);

    foreach($movies as $movie)
    {
    	if(is_file($dir.$movie))
    	{
			$old = $movie;
	    	if(hasYearInTitle($movie))
	    	{
	    		$movie = stripReleaseInfoFromTitle($movie);
	    		if(!is_dir($dir.'renamed/'))
	    			mkdir($dir.'renamed/',0777);

	    		if(!is_file($dir.'renamed/'.$movie))
	    			rename($dir.$old,$dir.'renamed/'.$movie);
	    		else
	    			unlink($dir.$old);
	    	}
	    	else
	    	    echo $movie."<br>";
	    }
    }
}
?>
