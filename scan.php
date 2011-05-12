<?php
session_start();
require_once("includes.php");

$dbh = new DatabaseHandler();
configureSettings();

//Scans the NFO files for wrong IMDB links
//scanVideoFolders(true,true);
//removeProductionsWithoutFiles();
scanVideoFolders(true);
//scanVideoFolders(false);
//$dbh->removeLowResRssMovies();
//$rssHandler = new RssHandler();
//$rssHandler->mineTL($cfg['torrentleech_user'], $cfg['torrentleech_pass'], true);
//scanShowFolders(true);
?>

