<?php
class ProductionFactory
{
	public static function constructProduction($typeToConstruct,$forceToRSS = false)
	{
		$prod = NULL;
		switch($typeToConstruct)
		{
			case TVSHOW:
				$prod = new Tvshow();
				break;
			case MOVIE:
				if($forceToRSS)
				{
					if($typeToConstruct == IGNORED_RSSMOVIE)
					{
						$prod = new RssMovie();
						$prod->type = IGNORED_RSSMOVIE;
					}
					else
						$prod = new RssMovie();
				}
				else
					$prod = new Movie();
				break;
			case EPISODE:
				$prod = new Episode();
				break;
			case IGNORED_RSSMOVIE:
				$prod = new RssMovie();
				$prod->type = IGNORED_RSSMOVIE;
				break;
			case RSSMOVIE:
				$prod = new RssMovie();
				break;
		}
		return $prod;
	}

}