<?php
class DirectoryUtil
{
	//parent and current folder descriptors, trash etc.
	private static $ignoredFolders = array('.','..','$RECYCLE.BIN','RECYCLER','System Volume Information','Recycled');
	
	//Takes a path like N:\moviecollection\moviefolder\subfolder\moviefilewithoutfilename
	//And looks for a nfo file at the following locations in this order,
	//1. N:\moviecollection\moviefolder\subfolder\moviefilewithoutfilename.nfo
	//2. N:\moviecollection\moviefolder\moviefilewithoutfilename.nfo
	
	//It also looks for all nfo files in
	//3. N:\moviecollection\moviefolder\subfolder\
	//4. N:\moviecollection\moviefolder\
	
	//If only one .nfo file is found when looking for .nfo files (and it's filename doesn't match another movie's), take it, 
	//if many .nfo files ignore them and return an empty string

	public static function getPathToNfoFileConnectedToMovieFile($filepath)
	{
		if($filepath == "")
			return false;
			
		if(file_exists($filepath.'.nfo'))
		{
			return $filepath.'.nfo';
		}
		$pathComponents = explode('/',$filepath);
		$depth = count($pathComponents)-1;
		$filename = $pathComponents[$depth];
		unset($pathComponents[$depth]);
		if($depth-1 >= 0)
		{
			$folder = $pathComponents[$depth-1];
			unset($pathComponents[$depth-1]);
		}
		
		$path = implode('/',$pathComponents);
		
		if($path != "")
		{
			if(file_exists($path.'/'.$filename.'.nfo'))
			{
				return $path.'/'.$filename.'.nfo';
			}
			$p = $path.'/';
		}
		else
			$p = "";
			
		if(isset($folder))
		{
			$files = DirectoryUtil::scandir($p.$folder);
			if($files !== false)
			{
				$nfoFiles = array();
				global $movieformats;
				foreach($files as $file)
				{
					if(strlen($file) > 3)
					{
						if(stripos($file,'.nfo',strlen($file)-4) !== false)
						{
							//When the NFO file is found, does it have another movie connected to it?
							$validNFOFile = true;
							foreach($movieformats as $movieformat)
							{
								$f = substr($file,0,-4);
								if(is_file($p.$folder."/".$f.".".$movieformat))
								{
									$validNFOFile = false;
									break;
								}
							}
							if($validNFOFile)
								$nfoFiles [] = $file;	
						}
					}
				}
				if(count($nfoFiles) == 1)
				{
					return $p.$folder.'/'.$nfoFiles[0];	
				}
			}
		}
		if($p != "")
		{
			$files = DirectoryUtil::scandir($p);
			if($files !== false)
			{
				global $movieformats;
				$nfoFiles = array();
				foreach($files as $file)
				{
					if(strlen($file) > 3 && stripos($file,'.nfo',strlen($file)-4) !== false)
					{
						//When the NFO file is found, does it have another movie connected to it?
						$validNFOFile = true;
						foreach($movieformats as $movieformat)
						{
							$f = substr($file,0,-4);
							if(is_file($p.$f.".".$movieformat))
							{
								$validNFOFile = false;
								break;
							}
						}
						if($validNFOFile)
							$nfoFiles [] = $file;
					}
				}
				if(count($nfoFiles) == 1)
				{
					return $p.$nfoFiles[0];	
				}
			}
		}
		return false;
	}
	
	
	//Extends the regular scandir by removing parent and current folder descriptors, trash etc.
	public static function scandir($path)
	{
		$dir = scandir($path);
		if($dir !== false)
		{
			//Clean result from unwanted folders
			foreach(DirectoryUtil::$ignoredFolders as $foldername)
			{
				$folderIndex = array_search($foldername,$dir);
				if($folderIndex !== FALSE)
					unset($dir[$folderIndex]);
			}
		}
		return $dir;
	}
	
	public static function getFileStack($arrayOfPaths, $arrayOfFilenames)
	{
		$stackSet = array();
		foreach($arrayOfPaths as $index => $path)
		{
			$fullpath = $path.$arrayOfFilenames[$index];
			
			$lastDot = strrpos($arrayOfFilenames[$index], ".");
			if($lastDot !== false)
				$withoutFiletype = substr($arrayOfFilenames[$index], 0, $lastDot);
			else 
				$withoutFiletype = $arrayOfFilenames[$index];
			
			$stackIndex = count($stackSet);
			//If the filerecord contains a dot or space, 
			//extract last part, 
			//remove cd/dvd etc,
			//check if the remainder is numeric
			$char = ' ';
			if(strrpos($withoutFiletype,'.') !== false)
				$char = '.';
			//Test for: movie(2008).cd1.avi or movie(2008) cd1.avi
			$fileending = strtolower(substr($withoutFiletype,strrpos($withoutFiletype,$char)+1));
			$fileending = str_replace(array('cd','dvd','part','episode','e','ep',' ','disk'),'',$fileending);
			if(is_numeric($fileending)) //Stackable
				$name = substr($withoutFiletype,0,strrpos($withoutFiletype,$char));
			
			//Default non-stackable files gets set
			if(!isset($name))
				$name = $withoutFiletype;
			
			//Check stack for the name
			foreach($stackSet as $index => $stackItem)
			{
				if($stackItem->name == $name && $stackItem->path == $path)
				{
					$stackIndex = $index;
					break;
				}
			}
			
			//If there isn't a stackitem at stackindex create one
			if(!isset($stackSet[$stackIndex]))
				$stackSet[$stackIndex] = new StackableFile($name,$path,array($arrayOfFilenames[$index]));
			else
				$stackSet[$stackIndex]->files[] = $arrayOfFilenames[$index];
			unset($name);
		}
		return $stackSet;
	}
}