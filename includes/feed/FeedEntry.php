<?php

/*
 * Based on ZendFramework's feed ZendFeed
 * Zend Technologies USA Inc. (http://www.zend.com)
 * http://framework.zend.com/license/new-bsd New BSD License
*/

class FeedEntry extends AbstractFeedData
{
	protected
		$author,       // indicates the author of the entry or feed
		$title,        // title of the feed entry, required
		$link,         // url to a feed entry, required
		$description,  // short version of a feed entry,  only text, no html, required
		$guid,         // id of the article, if not given link value will used, optional
		$content,      // long version,  can contain html, optional
		$lastUpdate,   // timestamp of the publication date,  optional
		$comments,     // comments page of the feed entry,  optional
		$commentRss,   // the feed url of the associated comments,  optional
		$source       = array(),  //original source of the feed entry  optional
					// title => title of the original source  required,
					// url => url of the original source  required
		$category     = array(),  //list of the attached categories  optional
					// array(
					// term => first category label  required,
					// scheme => url that identifies a categorization scheme  optional
					// )
		$enclosure    = array() // list of the enclosures of the feed entry  optional
					// array(
					// url => url of the linked enclosure  required
					// type => mime type of the enclosure  optional
					// length => length of the linked content in octets  optional
						
		;

	function __construct($title, $link, $content)
	{
		if(!isset($title) || !isset($link) || !isset($content))
			throw new FeedException('Some of required feelds are empty');
		
		$this->title = (string)$title;
		$this->link = (string)$link;
		$this->content = (string)$content;
        $this->setLastUpdate(time());

		$this->required_data = array (
			'title',		// 'title of the feed', 
			'link',			// 'canonical url to the feed', required
			'content'		// content of the feed. May be ''
		);
	}
	function setTitle($title)
	{
		$this->title = (string)$title;
		return $this;
	}
	function setLink($link)
	{
		$this->link = (string)$link;
		return $this;
	}
	function setDescription($description)
	{
		$this->description = (string)$description;
		return $this;
	}
	function setAuthor($author)
	{
		$this->author = (string)$author;
		return $this;
	}
	function setGUID($guid)
	{
		$this->guid = (string)$guid; 
		return $this;
	}
	function setContent($content)
	{
		$this->content = (string)$content;
		return $this;
	}
	function setLastUpdate($lastUpdate)
	{
		if(!is_numeric($lastUpdate))
			throw new FeedException('Wrong time format. Only int allowed');
		$this->last_update = (int)$lastUpdate;
		return $this;
	}
	function setCommentsUrl($comments)
	{
		if(!preg_match(POSTChecker::$url_regexp,$comments))
			throw new FeedException('Wrong url address');
		$this->comments = (string)$comments;
		return $this;
	}
	function setCommentsRssUrl($commentRss)
	{
		if(!preg_match(POSTChecker::$url_regexp,$commentsRss))
			throw new FeedException('Wrong url address');
		$this->commentsRss = (string)$commentsRss;
		return $this;
	}
	function setSource($title, $url)
	{
		if(!isset($title) || !isset($url) ||
			!preg_match(POSTChecker::$url_regexp,$url))
			throw new FeedException('Wrong url format');

		$this->source = array('title'=>$title, 'url'=>$url);

		return $this;
	}

	function addCategory($term, $scheme)
	{
		if (!isset($term)) 
			throw new FeedException("You have to define the term of the category");

		$this->category[] = array('term'=>$term, 'scheme'=>$scheme);
		return $this;
	}

	function addEnclosure($url, $type = '', $length = '')
	{
		if(!preg_match(POSTChecker::$url_regexp,$url))
			throw new FeedException("Wrong url format");

		$this->enclosure[] = array('url' => $url,
							 'type' => $type,
							 'length' => $length);
		return $this;
	}


}
