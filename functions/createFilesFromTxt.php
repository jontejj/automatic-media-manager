<?php
function createfilesFromTxt($file)
    {
    	$movieformats = array("mkv","avi","ts","wmv","iso","img");
		$movies = file($file);
	    foreach($movies as $moviefile)
	    {
	    	/*
	    	if($moviefile[0] == 'G')
	    		$path = 'HD Film 1/';
	    	else if($moviefile[0] == 'K')
	    		$path = 'HD Film 2/';
	    	else if($moviefile[0] == 'M')
	    		$path = 'HD Film 3/';
	    	else if(substr($moviefile,0,13) == 'F:/HD Film 4/')
	    		$path = 'HD Film 4/';
	    	else if(substr($moviefile,0,7) == 'F:/avi/')
	    		$path = 'Avi/';
	    	else if(substr($moviefile,0,7) == 'F:/dvd/')
	    		$path = 'Dvd/';
	    	else if(substr($moviefile,0,6) == 'L:/HD/')
	    		$path = 'Utan text/HD/';
	    	else if(substr($moviefile,0,19) == 'F:/Videor/avi/utan/')
	    		$path = 'Utan text/Avi/';
	    	else if(substr($moviefile,0,19) == 'F:/Videor/avi/engel')
	    		$path = 'Utan text/Engelsk/';
	    	else 
	    		$path = 'Utan text/';*/
	    	//$path_dirs = explode("/",$moviefile);
	    	//$showname = $path_dirs[1];
	    	//$ep = substr($path_dirs[2],0,-1);
	    	//echo $ep."<br>";
	    	//if(!is_dir("showfiles/".$showname))
		    //	mkdir("showfiles/".$showname,0777);
    		$path = "F:/getimdb/".substr($moviefile,0,-2);
	        $filehandle = fopen($path, 'w+') or die("can't create file");
	        fwrite($filehandle, $moviefile);
	        fclose($filehandle);
	    }
    }
?>
