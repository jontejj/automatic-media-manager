<?php
  class RssItem
  {
        var $showname, $season, $episode, $hd, $date, $link, $filename;
        function __construct($link, $season, $episode, $hd, $date, $showname,$filename)
        {
            $this->showname = $showname;
            $this->season = $season;
            $this->episode = $episode;
            $this->hd = $hd;
            $this->date = $date;
            $this->link = $link;
            $this->filename = $filename;
        }
        function removeSDversions()
        {
            global $dbh;
            $dbh->removeSDversionsOfRssItem($this->showname,$this->season,$this->episode);
        }
        function insert()
        {
            global $dbh;                      
            $dbh->insertRssItem($this->link, $this->hd, $this->season, $this->episode, $this->date, $this->showname,$this->filename);
        }
        function hd()
        {
            return $this->hd;
        }
        function isFetchedWithHd()
        {
            global $dbh;
            return $dbh->isFetchedWithHd($this->showname,$this->season,$this->episode);
        }
        function put()
        {
            echo "<item>";
            if($this->hd())
                echo "<title>$this->showname $this->season x $this->episode HD</title>";
            else 
                echo "<title>$this->showname $this->season x $this->episode</title>";   
            echo "<link>".htmlentities($this->link)."</link>
            <description>$this->showname $this->season x $this->episode $this->date $this->filename</description>
            </item>";
        }
  }
?>
