<?php
session_start();
require_once("includes.php");

$dbh = new DatabaseHandler();
$gui = new Gui();

//echo stripReleaseInfoFromTitle("Waiting... (2005)",true);
//die();

configureSettings();

//For calls from the commandline
if (defined('STDIN'))
{
	//Scans the NFO files for wrong IMDB links
	//scanVideoFolders(true,true);
	//removeProductionsWithoutFiles();
	scanVideoFolders(true);
	//scanVideoFolders(false);
	//$dbh->removeLowResRssMovies();
	//$rssHandler = new RssHandler();
	//$rssHandler->mineTL($cfg['torrentleech_user'], $cfg['torrentleech_pass'], true);
	//scanShowFolders(true);
}
else
	$gui->render();

if(Logger::echoEnabled())
	Logger::echoText($gui->out);
?>

