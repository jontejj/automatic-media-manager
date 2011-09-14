<?php
set_time_limit(0);
require_once 'includes.php';

$dbh = new DatabaseHandler();

$movie = $dbh->getProduction(5275);


MovieMiner::getBasicInfo($movie);
echo $movie->toString();
die();


$objects = $dbh->listProductions("production.id", "DESC", 1, IGNORED_RSSMOVIE); //"production.rating", "=", 0, false);

foreach($objects as $movie)
{
	$m = $dbh->getProduction($movie->id);
	if($m->looksLikeAGoodMovie())
	{
		$m->type = RSSMOVIE;
		$dbh->setTypeForProduction($m);
	}
}

$objects = $dbh->listProductions("production.id", "DESC", 1, RSSMOVIE); //"production.rating", "=", 0, false);

foreach($objects as $movie)
{
	$m = $dbh->getProduction($movie->id);
	if(!$m->looksLikeAGoodMovie())
	{
		$m->type = IGNORED_RSSMOVIE;
		$dbh->setTypeForProduction($m);
	}
}
/*
$gui = new Gui();

$gui->renderHead();

$gui->renderListOfProductions($objects, "");

$gui->renderTail();

echo $gui->out;*/

?>
