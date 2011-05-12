<?php
session_start();
require_once("includes.php");

$dbh = new DatabaseHandler();
configureSettings();
removeProductionsWithoutFiles();

?>
