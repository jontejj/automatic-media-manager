<?php

class ImageUtil
{
	
	public static function createThumb( $pathToImage, $pathToThumb, $maxWidth,$maxHeight)
	{
    	list($width, $height, $image_type) = getimagesize($pathToImage);
    	//This checks the mime type of the actual file
	    switch ($image_type)
	    {
	        case 1: 
	        	$src = imagecreatefromgif($pathToImage); 
	        	break;
	        case 2: 
	        	$src = imagecreatefromjpeg($pathToImage);  
	        	break;
	        case 3: 
	        	$src = imagecreatefrompng($pathToImage); 
	        	break;
	        default: 
	        	$src = false;  
	        break;
	    }
		
	    if(!$src)
		{
			Logger::echoText("Failed to create thumnail for: ".$pathToImage);
			return false;
		}

	    $x_ratio = $maxWidth / $width;
	    $y_ratio = $maxHeight / $height;
	
	    if( ($width <= $maxWidth) && ($height <= $maxHeight) )
	    {
	    	$tn_width = $width;
	    	$tn_height = $height;
	    }
	    elseif (($x_ratio * $height) < $maxHeight)
	    {
	    	$tn_height = ceil($x_ratio * $height);
	    	$tn_width = $maxWidth;
	    }
	    else
	    {
	    	$tn_width = ceil($y_ratio * $width);
	    	$tn_height = $maxHeight;
	    }

    	$tmp = imagecreatetruecolor($tn_width,$tn_height);

	    /* Check if this image is PNG or GIF to preserve its transparency */
	    if(($image_type == 1) OR ($image_type==3))
	    {
	        imagealphablending($tmp, false);
	        imagesavealpha($tmp,true);
	        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
	        imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
	    }
	    imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

	    switch ($image_type)
	    {
	        case 1: 
	        	imagegif($tmp, $pathToThumb); 
	        	break;
	        case 2: 
	        	imagejpeg($tmp, $pathToThumb, 80);  
	        	break; // best quality
	        case 3: 
	        	imagepng($tmp, $pathToThumb, 1); 
	        	break; // no compression
	    }
		
		return true;
	}
}

?>