<?php

require_once("FeedData.php");

class Feed
{
	const ATOM = 'atom';
	const RSS = 'rss';

	static function create(FeedData $fd, $type = null)
	{
		if($type === null && ($type = Config::get('default_feed')) == null)
			throw new FeedException('You must specify type');
		
		switch($type)
		{
			case self::ATOM: 
				require_once("AtomFeed.php");
				return new AtomFeed($fd);
			case self::RSS:
				require_once("RSSFeed.php");
				return new RSSFeed($fd);
			default:
				throw new FeedException('Unknown feed type');
		}
	}
	static function getMimeType($type)
	{
		switch($type)
		{
			case self::ATOM: 
				require_once("AtomFeed.php");
				return AtomFeed::mime_type;
			case self::RSS:
				require_once("RSSFeed.php");
				return RSSFeed::mime_type;
			default:
				throw new FeedException('Unknown feed type');
		}

	}
}
