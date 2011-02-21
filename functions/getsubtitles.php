<?php
  //Perform search with CURL and post variables
  function getSubtitles($path)
  {
  	  $dir = array();
      if(is_dir($path."/"))
        $path .= "/";
      if(is_dir($path))
      {
        $dir = scandir($path);
        foreach($dir as $filename)
        {
            if(is_file($path.$filename))
            {
                $fileext = substr($filename,strrpos($filename,'.')+1);
                if($fileext != 'srt')
                {
                    if(isset($_GET['type']))
                    	$type = $_GET['type'];
                    else
                    	$type = '';
                	$result = FileRetrieve::postAndRetrieve('www.undertexter.se?p=so&add=arkiv',array('str' => substr($filename,0,-4), 'typ' => $type));

                    //Extract the search results table
                    $searchresultstart = strpos($result,'(Max 100 träffar per sökning)'); 
                    $searchresult = substr($result,$searchresultstart);
                    $searchresultend = strpos($searchresult,'</table>');
                    $searchresult = substr($searchresult,0,$searchresultend); 
                                  
                    $resultsubs = explode('http://www.undertexter.se/?p=subark&id=',$searchresult);
                    for($i = 1;$i<count($resultsubs);$i++)
                    {
                        $id = substr($resultsubs[$i],0,strpos($resultsubs[$i],'"'));
                        $filename = substr($resultsubs[$i],strpos($resultsubs[$i],'"3"><br>')+8); //Remove text before filename
                        $filename = substr($filename,0,strpos($filename,'</td>'));  //Remove text after filename 
                        $file = file("http://www.undertexter.se/text.php?id=".$id);
                        
                        $filename = $path.'/'.$filename.'.'.$id.'.rar';
                        $filehandle = fopen($filename, 'w') or die("can't open file");
                        fwrite($filehandle, tostring($file));
                        fclose($filehandle);
                        //Unpack
                        //exec('unrar e "'.$filename.'" subtitles/test',$aOut);
                    }
                    //close connection
                    curl_close($ch);
                }
            }
        }
      }
      else
      {
        echo $path. " does not exist.<br>";
      }
  }
?>
