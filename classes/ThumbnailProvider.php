<?php

class ThumbnailProvider
{
	public static $POSTER 	= 1;
	public static $FANART 	= 2;
	public static $PERSON 	= 3;
	public static $CLAPPER 	= 4;
	
	public static function getCreatedPathForImage($src, $type, $maxWidth, $maxHeight)
	{
		$thumbPath = ThumbnailProvider::getThumbPath($src, $type, $maxWidth, $maxHeight);

		if(!is_file($thumbPath))
		{
			//Create the thumbnail if it doesn't exist yet
			if(is_file($src))
			{
				$createdThumb = ImageUtil::createThumb($src, $thumbPath, $maxWidth, $maxHeight);
				if($createdThumb)
					return $thumbPath;				
			}
			return false;
		}
		
		return $thumbPath;
	}	
	
	public static function getThumbPath($src, $type, $maxWidth, $maxHeight)
	{
		return ThumbnailProvider::getThumbFolder($type, $maxWidth, $maxHeight).basename($src);
	}
	
	public static function prepareThumbsFolder($type,$width,$height)
	{
		$thumbsFolder = ThumbnailProvider::getThumbFolder($type, $width, $height);
		
		if(!is_dir($thumbsFolder))
		{
			mkdir($thumbsFolder);
			return true;
		}
		else
			return false;
	}
	
	public static function getThumbFolder($type,$width,$height)
	{
		switch ($type)
		{
			case ThumbnailProvider::$POSTER:
				return "images/posters/thumbs/".$width."_".$height."/";
				break;
			case ThumbnailProvider::$FANART:
				return "images/fanart/thumbs/".$width."_".$height."/";
				break;
			case ThumbnailProvider::$PERSON:
				return "images/persons/thumbs/".$width."_".$height."/";
				break;
			case ThumbnailProvider::$CLAPPER:
				return "images/clapper/".$width."_".$height."/";
				break;
			default:
				return false;
		}
	}
	
	private static function listThumbFolders($type)
	{		
		switch ($type)
		{
			case ThumbnailProvider::$POSTER:
				return DirectoryUtil::scandir("images/posters/thumbs/");
				break;
			case ThumbnailProvider::$FANART:
				return DirectoryUtil::scandir("images/fanart/thumbs/");
				break;
			case ThumbnailProvider::$PERSON:
				return DirectoryUtil::scandir("images/persons/thumbs/");
				break;
			default:
				return false;
		}
	}
	
	public static function fillThumbFolders($newImageResource, $type)
	{
		$folders = ThumbnailProvider::listThumbFolders($type);
		if($folders !== false)
		{
			foreach($folders as $folder)
			{
				$dimensions = explode("_", $folder);
				if(count($dimensions) == 2)
				{
					$width = $dimensions[0];
					$height = $dimensions[1];
					if($width > 0 && $height > 0)
					{
						$thumbPath = ThumbnailProvider::getThumbPath($newImageResource, $type, $width, $height);
						ImageUtil::createThumb($newImageResource, $thumbPath, $width, $height);
					}
				}
			}
		}
	}
}
?>