<?php

//The folders where you keep your movies
$cfg['moviefolders'] = array("H:/Movie folder","E:/Another movie folder");

//Local Database access
$cfg['db_host'] = "localhost";
$cfg['db_user'] = "root";
$cfg['db_pass'] = "";
$cfg['db_name'] = "movies";

//Goto ?page=mineTL&update=1 to see a rss feed with good movies in it 
//(according to looksLikeAGoodMovie() in classes/Movie.php, feel free to tweak this to your liking)
//Your credentials to torrenleech.org
$cfg['torrentleech_user'] = "user";
$cfg['torrentleech_pass'] = "pass";

//use ?page=rss to access the filtered rss feed that this system provides, I.e it keeps track of HD/SD versions of your episodes and keeps the
//amount of duplicate downloads at a minimum
//This link should point to your own tvtorrents rss link
$cfg['tvtorrents_rss_link'] = "http://www.tvtorrents.com/RssServlet?digest=fdsfsdf&hash=sfsdfsdf&fav=true";

//No need to configure these
$cfg['TMDB_API_KEY'] = "d0282f52ce4d93564dfea3d0b1b1e40a";
$cfg['TVDB_API_KEY'] = "DFE16311A64B8749";
$cfg['userAgent'] = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
?>