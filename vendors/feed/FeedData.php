<?php

/*
 * Based on ZendFramework's feed ZendFeed
 * Zend Technologies USA Inc. (http://www.zend.com)
 * http://framework.zend.com/license/new-bsd New BSD License
*/


require_once("FeedItunesHeaders.php");
require_once("FeedEntry.php");

class FeedException extends Exception {}

abstract class AbstractFeedData 
{
	protected $required_data = array ();

	function __get($var)
	{
		if(!property_exists($this,$var))
			throw new FeedException("Property $var doesn't exists");
		return $this->$var;
	}
    function __set($var, $val)
    {
		if(!property_exists($this,$var))
			throw new FeedException("Property $var doesn't exists");
        $this->$var = $val;
    }
	function __isset($var)
	{
		if(!property_exists($this,$var))
			throw new FeedException("Property $var doesn't exists");
		
		return isset($this->$var);
	}
	function checkRequired()
	{
		foreach($this->required_data as $v)
			if(!isset($this->$v)) return false;
		return true;
	}
	function isReady()
	{
		return (bool)$this->checkRequired();
	}
}
class FeedData extends AbstractFeedData
{
	protected 
			$title,       // 'title of the feed', 
			$link,       // 'canonical url to the feed, required
			$last_update,  // 'timestamp of the update date',  optional
			$published,   // 'timestamp of the publication date', optional
			$charset = "utf8",     // 'charset',  required
			$description, // 'short description of the feed', optional
			$author,      // 'author/publisher of the feed', optional
			$email,       // 'email of the author', optional
			$webmaster,   // 'email address for person responsible for technical issues'  optional, ignored if atom is used
			$copyright,   // 'copyright notice', optional
			$image,       // 'url to image', optional
			$generator,   // 'generator',  optional
			$language,    // 'language the feed is written in',  optional
			$ttl,         // 'how long in minutes a feed can be cached before refreshing',  optional, ignored if atom is used
			$rating,      // 'The PICS rating for the channel.,  optional, ignored if atom is used
			$cloud = array(), //     a cloud to be notified of updates // optional, ignored if atom is used
			//    'domain'            => 'domain of the cloud, e.g. rpc.sys.com' // required
			//    'port'              => 'port to connect to' // optional, default to 80
			//    'path'              => 'path of the cloud, e.g. /RPC2 //required
			//    'registerProcedure' => 'procedure to call, e.g. myCloud.rssPleaseNotify' // required
			//    'protocol'          => 'protocol to use, e.g. soap or xml-rpc' // required

			$textInput = array(), // a text input box that can be displayed with the feed // optional, ignored if atom is used
			 //   'title'       => 'the label of the Submit button in the text input area' // required,
			 //   'description' => 'explains the text input area' // required
			 //   'name'        => 'the name of the text object in the text input area' // required
			 //   'link'        => 'the URL of the CGI script that processes text input requests' // required
    
			$skipHours   = array(), // Hint telling aggregators which hours they can skip // optional, ignored if atom is used
									//   up to 24 rows whose value is a number between 0 and 23
			 //  'hour in 24 format', // e.g 13 (1pm)

			$skipDays   = array() // Hint telling aggregators which days they can skip // optional, ignored if atom is used
     							  // up to 7 rows whose value is a Monday, Tuesday, Wednesday, Thursday, Friday, Saturday or Sunday
			//   'a day to skip', // e.g Monday
		;
	protected $itunes = null;
	protected $entries = array();


	function __construct($title = null, $link = null, $charset = null)
	{
		if(isset($title))
			$this->setTitle($title);
		if(isset($link))
			$this->setLink($link);
		if(isset($charset))
			$this->setCharset($charset);
		$this->setLastUpdate(time());
		$this->setGenerator('Cassea Framework Feed Genearator');

		$this->required_data = array (
			'title',       // 'title of the feed', 
			'link' ,       // 'canonical url to the feed', required
			'charset'     // 'charset',  required
		);
	}
	function setTitle($title)
	{
		$this->title = (string)$title;
		return $this;
	}
	function setLink($link)
	{
		if(!preg_match(POSTChecker::$url_regexp,$link))
			throw new FeedException('Wrong url format');
		$this->link = $link;
		return $this;
	}
	function setCharset($charset)
	{ 
		$this->charset = (string)$charset; 
		return $this; 
	}
	function setLastUpdate($lastUpdate)
	{
		if(!is_numeric($lastUpdate))
			throw new FeedException('Wrong time format. Only int allowed');
		$this->last_update = $lastUpdate;
		return $this;
	}
	function setPublishedDate($published)
	{
		if(!is_numeric($published))
			throw new FeedException('Wrong time format. Only int allowed');
		$this->published = $published;
		return $this;
	}
	function setDescription($description)
	{
		$this->description = (string)$description;
		return $this;
	}
	function setAuthor($author)
    {
        $this->offsetSet('author', $author);
        return $this;
    }
	function setEmail($email)
	{
		if(!preg_match(POSTChecker::$email_regexp,$email))
			throw new FeedException('Wrong mail address format');

		$this->email = $email;
		return $this;
	}
    function setCopyright($copyright)
	{
		$this->copyright = (string)$copyright;
        return $this;
    }
    function setImage($image_url)
    {
		$this->image = (string)$image_url;
        return $this;
    }
	function setGenerator($generator)
	{
		$this->generator = (string)$generator;
        return $this;
    }
    function setLanguage($language)
	{
		$this->language = (string)$language;
        return $this;
    }
    function setWebmaster($webmaster_email)
    {
		if(!preg_match(POSTChecker::$email_regexp,$webmaster_email))
			throw new FeedException('Wrong mail address format');
		$this->webmaster = (string)$webmaster_email;
        return $this;
    }
    function setTtl($ttl)
    {
		if(!is_numeric($ttl))
			throw new FeedException('Wrong time format. Only int allowed');
		$this->ttl = (int)$ttl;
        return $this;
    }
	function setRating($rating)
	{
		$this->rating = (string)$rating;
        return $this;
    }

	function setCloud($uri, $procedure, $protocol)
    {
		if(!isset($uri) || !isset($procedure) || !isset($protocol))
			throw new FeedException("All components are required to be set");

		if(($uri_a = parse_url($uri)) === false)
			throw new FeedException('Wrong uri format');

		$this->cloud = array(
			'domain' => (string)$uri_a['host'],
			'port' => isset($uri_a['port'])?$uri_a['port']:80,
			'path' => (string)$uri_a['path'],
			'registerProcedure'=>(string)$procedure,
			'protocol'=>(string)$protocol
			);
        return $this;
    }
    function setTextInput($title, $description, $name, $link)
    {
		if(!isset($title) || !isset($description) ||
			!isset($name) || !isset($link))
			throw new FeedException('All components are required to be set');
		$this->textInput = array(
			'title' => $title,
			'description' => $description,
			'name' => $name,
			'link' => $link);
        return $this;
    }
   function addSkipHours($hour)
   {
	   if (count($this->skipHours) > 23)
		   throw new FeedException("you can not have more than 24 rows in the skipHours property");

		if ($hour < 0 || $hour > 23)
		   throw new FeedException("$hour has te be between 0 and 23");

	   $this->skipHours[] =  $hour;
	   return $this;
    }
    function addSkipDays($day)
    {
		if (count($this->skipDays) > 6)
			throw new FeedException("you can not have more than 7 days in the skipDays property");

		if (!in_array(strtolower($day),
			array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'))) 
				throw new FeedException("$day is not a valid day");

		$this->skipDays[] = $day;
		return $this;
    }

	function setITunes(FeedItunesHeaders $itunes)
	{
		$this->itunes = $itunes;
		return $this;
	}

	function addEntry(FeedEntry $entry)
	{
		$this->entries[] = $entry;
		return $this;
	}

	function isReady()
	{
		return count($this->entries) && $this->checkRequired();
	}
	function hasEntries()
	{
		return (bool)count($this->entries);
	}
}
