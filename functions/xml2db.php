<?php
  function xml2db($file)
  {
        global $dbh;  
        //Import from a xml file
        if (!($fp = fopen($file, "r"))) 
            die("could not open XML input");
        $data = fread($fp, filesize($file));
        fclose($fp);
        $array = xml2array($data);
        $productions = array();
        $i = 0;
        $t = microtime(true);
        //Parse the array and add to Production objects
        if(isset($array['videodb']['movie']))
        {
            $array['videodb']['movie'] = toarray($array['videodb']['movie']); 
            foreach($array['videodb']['movie'] as $movie)
            {                               
                $i++; 
                $time_start = microtime(true);
                $productions[$i] = new Movie(); 
                $productions[$i]->parseArray($movie);
                
                $dbh->addProduction($productions[$i]);
                
                $timeend = microtime(true) - $time_start; 
                echo "saved ($i): ".$productions[$i]->title." ($timeend ms)<br>";
            }
        }  
        if(isset($array['videodb']['tvshow']))
        {
            $array['videodb']['tvshow'] = toarray($array['videodb']['tvshow']);  
            foreach($array['videodb']['tvshow'] as $tvshow)
            {
                $i++;  
                $time_start = microtime(true);
                $productions[$i] = new Tvshow(); 
                $productions[$i]->parseArray($tvshow);
                $dbh->addProduction($productions[$i]);
                $timeend = microtime(true) - $time_start; 
                echo "saved ($i): ".$productions[$i]->title." ($timeend ms)<br>";
            }
        }
        $t = microtime(true) - $t;
        echo "imported ($i) items from the file '".$file."' in $t ms<br>"; 
  }
?>
