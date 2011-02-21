<?php
session_start();
require_once("includes.php");

$dbh = new DatabaseHandler();
configureSettings();

$rssHandler = new RssHandler();
$rssHandler->mineTL($cfg['torrentleech_user'], $cfg['torrentleech_pass'], true);
?>

