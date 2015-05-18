Requirements:

php extensions:
CURL
GD2
mysqli

Usage instructions:

Install apache, mysql and php.
Load the sql file "design/database.sql" into your database (Use phpmyadmin or something)
Configure "config_sample.php"
Use a web browser to administrate the system.

If you want to use the watch button from your browser you need to follow [this guide](http://php.net/manual/en/book.exec.php)

TODO list
* Rewrite to gwt
* Faster DB lookups
* Nicer GUI
* Piratebay parser
* Support for TV episodes in the GUI
* Ability to save .nfo's (containing a imdb link) next to the movie files in order to keep other systems in sync such as XBMC
* Remove files with CD1 and CD2 from the duplicate detector
* Automate the duplicate detector and remove the file with the lowest quality automatically
* Much faster search (As it is now it searches so long that the mysql server goes away (:
* Subtitle downloading
* Ability to choose whether or not to display the original title
* Ability to prioritize movies with DTS over movies without it etc.
* Add subscription for good actors for the rss filter from the GUI.
* Fetch ratings from several sources
