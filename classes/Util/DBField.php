<?php
class DBField
{	
	public static $INTEGER 	= 'i';
	public static $DOUBLE 	= 'd';
	public static $STRING 	= 's';
	public static $BLOB		= 'b';
	
	//Returns the type of a database field
	public static function getType($field)
	{
		switch(strtolower($field))
		{
			case 'file.path':
				return DBField::$STRING;
			case 'file.filename':
				return DBField::$STRING;
			case 'file.format':
				return DBField::$STRING;
			case 'file.writinglibrary':
				return DBField::$STRING;
				
			case 'person.name':
				return DBField::$STRING;
			case 'person.bio':
				return DBField::$STRING;
			case 'person.birthplace':
				return DBField::$STRING;
				
			case 'movie.tagline':
				return DBField::$STRING;
			case 'movie.mpaa':
				return DBField::$STRING;
				
			case 'production.plot':
				return DBField::$STRING;
			case 'production.title':
				return DBField::$STRING;
		}
		return DBField::$INTEGER;
	}
}
?>