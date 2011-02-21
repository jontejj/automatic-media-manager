<?php
	require_once("constants.php");
	require_once("config_sample.php");
	require_once("config.php");
/* Classes */
	require_once("classes/ThumbnailProvider.php");
    require_once("classes/RssItem.php");
    require_once("classes/ListObject.php");
    require_once("classes/Studio.php");
    require_once("classes/Production.php");  
    require_once("classes/Fanart.php"); 
    require_once("classes/Person.php");
    require_once("classes/FullPerson.php");
    require_once("classes/PersonPhoto.php");
    require_once("classes/Movie.php"); 
    require_once("classes/RssMovie.php");  
    require_once("classes/Tvshow.php");
    require_once("classes/Episode.php");   
    require_once("classes/Acting.php"); 
    require_once("classes/Country.php");
    require_once("classes/OriginalTitle.php");
    require_once("classes/AudioTrack.php"); 
    require_once("classes/FFile.php"); 
    require_once("classes/Keyword.php");
    
    require_once("classes/DatabaseHandler.php"); 
    require_once("classes/Gui.php");   
    require_once("classes/StackableFile.php"); 
    
    require_once("classes/RssHandler.php"); 
    
/* Util Classes */
	require_once("classes/Util/ImageUtil.php");
    require_once("classes/Util/Thread.php");
    require_once("classes/Util/FileRetreive.php");
    require_once("classes/Util/Logger.php");
    require_once("classes/Util/MovieMiner.php");
    require_once("classes/Util/DirectoryUtil.php");
    require_once("classes/Util/StringUtil.php");
    require_once("classes/Util/ProductionFactory.php");
    require_once("classes/Util/DatabaseStatementParameter.php");
    require_once("classes/Util/DBField.php");
    require_once("classes/Util/Torrent.php");
    
/* functions */   
    require_once("functions/toarray.php"); 
    require_once("functions/tostring.php"); 
    require_once("functions/xml2array.php");
    require_once("functions/xml2db.php"); 
    require_once("functions/html2xml.php");
    require_once("functions/listnewmovies.php");
    require_once("functions/magpierss-0.72/rss_fetch.inc");
    require_once("functions/htmlparser/simple_html_dom.php");
    require_once("functions/getsubtitles.php"); 
    require_once("functions/returnarraykey.php");
    require_once("functions/scanvideofolders.php"); 
    require_once("functions/scanshowfolders.php"); 
    require_once("functions/renameSceneFiles.php"); 
    require_once("functions/createFilesFromTxt.php");
	require_once("functions/settings.php");
?>