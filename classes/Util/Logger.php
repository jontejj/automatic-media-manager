<?php
class Logger
{
	private static $unreachableFilesLogFileName = "logs/unreachable_files.log";
	private static $fetchedFilesLogFileName = "logs/fetched_files.log";
	private static $productionsLogFileName = "logs/productions.log";
	private static $rssMoviesLogFileName = "logs/rss_movies.log";
	private static $rssMoviesIgnoredLogFileName = "logs/rss_movies_ignored.log";
	private static $rssMoviesNotAllowedLogFileName = "logs/rss_movies_notallowed.log";
	private static $failedToAddProductionsLogFileName = "logs/failedToAdd.log";
	private static $httpRequestFailedLogFileName = "logs/httpRequestFailed.log";
	private static $nfoFilesWithBadLinks = "logs/nfo_files_with_bad_links.log";
	private static $titleParser = "logs/titleParser.log";
	private static $echoFile = "logs/echoFile.log";
	private static $parseErrors = "logs/parseErrors.log";
	private static $missMatchBetweenIMDBlookupAndNFOFile = "logs/missMatchBetweenIMDBlookupAndNFOFile.log";
	private static $echoAvailable = true;
	
	public static function disableEcho()
	{
		Logger::$echoAvailable = false;
	}
	
	public static function enableEcho()
	{
		Logger::$echoAvailable = true;
	}
	public static function echoEnabled()
	{
		return Logger::$echoAvailable;
	}
	public static function echoText($text)
	{
		if(Logger::$echoAvailable)
			echo $text;
		else
			Logger::appendStringToFile($text,Logger::$echoFile);
	}
	
	public static function nfoFileWithBadLink($request)
	{
		Logger::appendStringToFile($request,Logger::$nfoFilesWithBadLinks);
	}
	
	public static function titleParsed($from, $title,$year)
	{
		Logger::appendStringToFile($from." Translated to: ".$title." (".$year.")",Logger::$titleParser);
	}
	
	public static function httpRequestFailed($request)
	{
		Logger::appendStringToFile($request,Logger::$httpRequestFailedLogFileName);
	}
	
	public static function parseError($production,$errorCode)
	{
		Logger::appendStringToFile($production->toString().' - Error with: '.$errorCode, Logger::$parseErrors);
	}
	
	public static function unreachableFile($filepath)
	{
		Logger::appendStringToFile($filepath,Logger::$unreachableFilesLogFileName);
	}
	
	public static function missMatchBetweenIMDBlookupAndNFOFile($imdbProduction, $nfoProduction, $path)
	{
		Logger::appendStringToFile("File at path: ".$path.", 
		IMDB search got: ".$imdbProduction->getDisplayTitle()." (IMDB: ".$imdbProduction->imdb.", ID: ".$imdbProduction->id.") 
		NFO file got: ".$nfoProduction->getDisplayTitle()." (IMDB: ".$nfoProduction->imdb.", ID: ".$nfoProduction->id.")"
		,Logger::$missMatchBetweenIMDBlookupAndNFOFile);
	}
	
	public static function fileFetched($filepath)
	{
		Logger::echoText('Retrieved: '.$filepath.PHP_EOL);
		Logger::appendStringToFile($filepath,Logger::$fetchedFilesLogFileName);
	}
	
	public static function logProduction($production)
	{
		if(is_a($production,'Production'))
			Logger::appendStringToFile($production->toString(),Logger::$productionsLogFileName);
	}
	public static function logNoIMDBNumberFoundForProduction($production)
	{
		if(is_a($production,'Production'))
			Logger::appendStringToFile($production->toString(),Logger::$failedToAddProductionsLogFileName);
	}
	
	public static function logPublishedRSSMovie($rssMovie)
	{
		if(is_a($rssMovie,'RssMovie'))
			Logger::appendStringToFile($rssMovie->toString(),Logger::$rssMoviesLogFileName);
	}
	
	public static function logIgnoredRSSMovie($rssMovie)
	{
		if(is_a($rssMovie,'RssMovie'))
			Logger::appendStringToFile($rssMovie->toString(),Logger::$rssMoviesIgnoredLogFileName);
	}
	
	public static function logNotAllowedRSSMovie($string)
	{
		Logger::appendStringToFile($string,Logger::$rssMoviesNotAllowedLogFileName);
	}
	
	private static function appendStringToFile($string, $path)
	{
		//Opens the file for writing at the end of it, if not found, attempt to create it
		if($handle = fopen($path,'a'))
		{	
			fwrite($handle,$string.PHP_EOL);
			fclose($handle);
		}	
	}
	
	public static function setFileToString($string, $path)
	{
		if(is_array($string))
			$string = implode("", $string);
			
		$success = false;
		//Opens the file for writing at the beginning of it, if not found, attempt to create it
		if($handle = fopen($path,'w'))
		{	
			if(fwrite($handle,$string) !== false)
				$success = true;	
			fclose($handle);
		}	
		return $success;
	}
}