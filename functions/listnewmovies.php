<?php
function listnewmovies()
{
    $file = file("bluray.html");
    $html = "";
    foreach($file as $linenum => $line)
    {
        $html .= $line;    
    }
    $movies = explode("<li><b> <a href=\"http://bluray.highdefdigest.com",$html);
    unset($movies[0]);
    $newmovies = array();
    class NewMovie
    {
        var $title,$year;
        function __construct($title,$year = 0)
        {
            $this->title = $title;
            $this->year = $year;
        }
    }
    foreach($movies as $movie)
    {
        $name = substr($movie,strpos($movie,'>')+1);
        if(strpos($name,'</a>') > 0)
        {
            $name = substr($name,0,strpos($name,'</a>'));     
        }
        if(strpos($name,'(') > 0)
        {
            if(is_numeric(substr($name,strpos($name,'(')+1,4)))
                $year = substr($name,strpos($name,'(')+1,4);
            $name = substr($name,0,strpos($name,'(')); 
        }
        else $year = 0;
        $name = str_replace(": Director's Cut","",$name);
        $name = str_replace(" - Director's Cut","",$name);
        $name = str_replace(": The Director's Cut","",$name);
        $name = str_replace(" - The Director's Cut","",$name);
        $name = str_replace(" Director's Cut","",$name);
        $newmovies[] = new NewMovie(trim($name),$year);
    }
    //Get movies from db
    global $dbh;
    global $gui;
    $movies = $dbh->movieProductions();
    $nrOfHdMovies = 0;
    if(is_array($movies) && is_array($newmovies))
    {
        /*foreach($newmovies as $newmovie)
        {
            $found = false;
            foreach($movies as $movie)
            {
                $match = false;
                foreach($movie->titles as $title)
                    if($newmovie->title == $title->title)
                        $match = true;
                if($newmovie->title == $movie->title)
                    $match = true;
                if($match)
                {
                    if($movie->year == $newmovie->year or $newmovie->year == 0)
                        foreach($movie->files as $file)
                            if($file->path == "K:\\HD Film 2\\" || $file->path =="G:\\")
                                $found = true;  
                }
            }
            if($found)
            {
                $gui->out .= $newmovie->title.' ('.$newmovie->year.")<br>\n";
                //$nrOfHdMovies++;
            }
        }   */
        foreach($movies as $movie)
        {
            if(count($movie->files) > 1)
                $gui->out .= $movie->title.' ('.$movie->year.")<br>\n";  
        }
        //echo $nrOfHdMovies."<br>\n";
    }
}
?>