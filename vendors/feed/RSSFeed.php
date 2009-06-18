<?php


require_once("AbstractFeed.php");

class RSSFeed extends AbstractFeed
{
	const mime_type='application/rss+xml';
	function __construct(FeedData $fd)
	{
		parent::__construct($fd);
	}
	protected function mapHeaders(AbstractFeedData $fd)
	{
		
        $channel = $this->dom->createElement('channel');

        $title = $this->dom->createElement('title');
        $title->appendChild($this->dom->createCDATASection($fd->title));
        $channel->appendChild($title);

        $link = $this->dom->createElement('link', $fd->link);
        $channel->appendChild($link);

        $desc = isset($fd->description) ? $fd->description : '';
        $description = $this->dom->createElement('description');
        $description->appendChild($this->dom->createCDATASection($desc));
        $channel->appendChild($description);

        $pubdate = isset($fd->last_update) ? $fd->last_update : time();
        $pubdate = $this->dom->createElement('pubDate', gmdate('r', $pubdate));
        $channel->appendChild($pubdate);

		if (isset($fd->published)) 
		{
            $lastBuildDate = $this->dom->createElement('lastBuildDate', gmdate('r', $fd->published));
            $channel->appendChild($lastBuildDate);
        }

        $editor = '';
        if (!empty($fd->email))
            $editor .= $fd->email;
        if (!empty($fd->author)) 
            $editor .= ' (' . $fd->author . ')';
        if (!empty($editor)) {
            $author = $this->dom->createElement('managingEditor', ltrim($editor));
            $channel->appendChild($author);
        }
        if (isset($fd->webmaster))
            $channel->appendChild($this->dom->createElement('webMaster', $fd->webmaster));

		if (!empty($fd->copyright)) 
		{
            $copyright = $this->dom->createElement('copyright', $fd->copyright);
            $channel->appendChild($copyright);
        }

/*		if (isset($fd->category)) 
		{
            $category = $this->dom->createElement('category', $fd->category);
            $channel->appendChild($category);
        }
 */
		if (!empty($fd->image)) 
		{
            $image = $this->dom->createElement('image');
            $url = $this->dom->createElement('url', $fd->image);
            $image->appendChild($url);
            $imagetitle = $this->dom->createElement('title');
            $imagetitle->appendChild($this->dom->createCDATASection($fd->title));
            $image->appendChild($imagetitle);
            $imagelink = $this->dom->createElement('link', $fd->link);
            $image->appendChild($imagelink);

            $channel->appendChild($image);
        }

        $generator = !empty($fd->generator) ? $fd->generator : 'Cassea Feed Generator';
        $generator = $this->dom->createElement('generator', $generator);
        $channel->appendChild($generator);

		if (!empty($fd->language)) 
		{
            $language = $this->dom->createElement('language', $fd->language);
            $channel->appendChild($language);
        }

        $doc = $this->dom->createElement('docs', 'http://blogs.law.harvard.edu/tech/rss');
        $channel->appendChild($doc);

		if (!empty($fd->cloud)) 
		{
            $cloud = $this->dom->createElement('cloud');
            $cloud->setAttribute('domain', $fd->cloud['domain']);
            $cloud->setAttribute('port', $fd->cloud['port']);
            $cloud->setAttribute('path', $fd->cloud['path']);
            $cloud->setAttribute('registerProcedure', $fd->cloud['procedure']);
            $cloud->setAttribute('protocol', $fd->cloud['protocol']);
            $channel->appendChild($cloud);
        }

		if (isset($fd->ttl)) 
		{
            $ttl = $this->dom->createElement('ttl', $fd->ttl);
            $channel->appendChild($ttl);
        }

		if (isset($fd->rating)) 
		{
            $rating = $this->dom->createElement('rating', $fd->rating);
            $channel->appendChild($rating);
        }

		if (!empty($fd->textInput)) 
		{
            $textinput = $this->dom->createElement('textInput');
            $textinput->appendChild($this->dom->createElement('title', $fd->textInput['title']));
            $textinput->appendChild($this->dom->createElement('description', $fd->textInput['description']));
            $textinput->appendChild($this->dom->createElement('name', $fd->textInput['name']));
            $textinput->appendChild($this->dom->createElement('link', $fd->textInput['link']));
            $channel->appendChild($textinput);
        }

		if (!empty($fd->skipHours)) 
		{
            $skipHours = $this->dom->createElement('skipHours');
            foreach ($fd->skipHours as $hour)
                $skipHours->appendChild($this->dom->createElement('hour', $hour));
            $channel->appendChild($skipHours);
        }

		if (!empty($fd->skipDays)) 
		{
            $skipDays = $this->dom->createElement('skipDays');
            foreach ($fd->skipDays as $day)
                $skipDays->appendChild($this->dom->createElement('day', $day));
            $channel->appendChild($skipDays);
        }

        if (isset($fd->itunes))
            $this->buildITunes($channel, $fd);
        
		$this->dom->appendChild($channel);
	}
	protected function buildITunes(DomElement $root, AbstractFeedData $fd)
	{
        /* author node */
        $author = '';
        if (isset($fd->itunes->author))
            $author = $fd->itunes->author;
        elseif (isset($fd->author)) 
            $author = $fd->author;
        
		if (!empty($author)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:author', $author);
            $root->appendChild($node);
        }

        /* owner node */
        $author = '';
        $email = '';
		if (isset($fd->itunes->owner)) 
		{
            if (isset($fd->itunes->owner['name']))
                $author = $fd->itunes->owner['name'];
            if (isset($fd->itunes->owner['email']))
                $email = $fd->itunes->owner['email'];
        }
		if (empty($author) && isset($fd->author)) 
            $author = $fd->author;
        if (empty($email) && isset($fd->email)) 
            $email = $fd->email;

		if (!empty($author) || !empty($email)) 
		{
            $owner = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:owner');
			if (!empty($author)) 
			{
                $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:name', $author);
                $owner->appendChild($node);
            }
			if (!empty($email)) 
			{
                $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:email', $email);
                $owner->appendChild($node);
            }
            $root->appendChild($owner);
        }
        $image = '';
        if (isset($fd->itunes->image)) 
            $image = $fd->itunes->image;
        elseif (isset($fd->image))
            $image = $fd->image;

		if (!empty($image)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:image');
            $node->setAttribute('href', $image);
            $root->appendChild($node);
        }
        $subtitle = '';
        if (isset($fd->itunes->subtitle))
            $subtitle = $fd->itunes->subtitle;
        elseif (isset($fd->description))
            $subtitle = $fd->description;

		if (!empty($subtitle)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:subtitle', $subtitle);
            $root->appendChild($node);
        }
        $summary = '';
        if (isset($fd->itunes->summary))
            $summary = $fd->itunes->summary;
        elseif (isset($fd->description))
            $summary = $fd->description;

        if (!empty($summary)) {
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:summary', $summary);
            $root->appendChild($node);
        }
		if (isset($fd->itunes->block)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:block', $fd->itunes->block);
            $root->appendChild($node);
        }
		if (isset($fd->itunes->explicit)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:explicit', $fd->itunes->explicit);
            $root->appendChild($node);
        }
		if (isset($fd->itunes->keywords)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:keywords', $fd->itunes->keywords);
            $root->appendChild($node);
        }
		if (isset($fd->itunes->new_feed_url)) 
		{
            $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:new-feed-url', $fd->itunes->new_feed_url);
            $root->appendChild($node);
        }
		if (isset($fd->itunes->category)) 
	
			foreach ($fd->itunes->category as $i => $category) 
			{
                $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:category');
                $node->setAttribute('text', $category['main']);
                $root->appendChild($node);
                $add_end_category = false;
				if (!empty($category['sub'])) 
				{
                    $add_end_category = true;
                    $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:category');
                    $node->setAttribute('text', $category['sub']);
                    $root->appendChild($node);
                }
				if ($i > 0 || $add_end_category) 
				{
                    $node = $this->_element->createElementNS('http://www.itunes.com/DTDs/Podcast-1.0.dtd', 'itunes:category');
                    $root->appendChild($node);
                }
            }
	}
	protected function mapEntries(AbstractFeedData $fd)
	{
			
		foreach ($fd->entries as $dataentry) 
		{
			$description = $dataentry->description;

			if(empty($description))
				$dataentry->setDescription($dataentry->content)
				->setContent(null);

			$guid = $dataentry->guid;
			if(empty($guid))
				$dataentry->setGUID($dataentry->link);

            $item = $this->dom->createElement('item');

			if(!empty($dataentry->author))
			{
				$author = $this->dom->createElement('author', $dataentry->author);
				$item->appendChild($author);
			}

            $title = $this->dom->createElement('title');
            $title->appendChild($this->dom->createCDATASection($dataentry->title));
            $item->appendChild($title);

            $link = $this->dom->createElement('link', $dataentry->link);
            $item->appendChild($link);

			if (isset($dataentry->guid)) 
			{
                $guid = $this->dom->createElement('guid', $dataentry->guid);
                $item->appendChild($guid);
            }

            $description = $this->dom->createElement('description');
            $description->appendChild($this->dom->createCDATASection($dataentry->description));
            $item->appendChild($description);

			if (!empty($dataentry->content)) 
			{
                $content = $this->dom->createElement('content:encoded');
                $content->appendChild($this->dom->createCDATASection($dataentry->content));
                $item->appendChild($content);
            }

            $pubdate = isset($dataentry->last_update) ? $dataentry->last_update : time();
            $pubdate = $this->dom->createElement('pubDate', gmdate('r', $pubdate));
            $item->appendChild($pubdate);

			if (isset($dataentry->category)) 
			{
				foreach ($dataentry->category as $category) 
				{
                    $node = $this->dom->createElement('category', $category['term']);
                    if (isset($category['scheme'])) 
                        $node->setAttribute('domain', $category['scheme']);
                    $item->appendChild($node);
                }
            }

			if (!empty($dataentry->source)) 
			{
                $source = $this->dom->createElement('source', $dataentry->source['title']);
                $source->setAttribute('url', $dataentry->source['url']);
                $item->appendChild($source);
            }

			if (isset($dataentry->comments)) 
			{
                $comments = $this->dom->createElement('comments', $dataentry->comments);
                $item->appendChild($comments);
            }
			if (isset($dataentry->commentRss)) 
			{
                $comments = $this->dom->createElementNS('http://wellformedweb.org/CommentAPI/',
                                                             'wfw:commentRss',
                                                             $dataentry->commentRss);
                $item->appendChild($comments);
            }


			if (!empty($dataentry->enclosure)) 
			{
				foreach ($dataentry->enclosure as $enclosure) 
				{
                    $node = $this->dom->createElement('enclosure');
                    $node->setAttribute('url', $enclosure['url']);
                    if (isset($enclosure['type'])) 
                        $node->setAttribute('type', $enclosure['type']);
                    if (isset($enclosure['length'])) 
                        $node->setAttribute('length', $enclosure['length']);
                    $item->appendChild($node);
                }
            }
            $this->dom->getElementsByTagName('channel')->item(0)->appendChild($item);
        }
	}
	function asXML()
	{
        // Return a complete document including XML prologue.
        $doc = new DOMDocument($this->dom->version,
                               $this->dom->actualEncoding);
        $root = $doc->createElement('rss');

        // Use rss version 2.0
        $root->setAttribute('version', '2.0');

        // Content namespace
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $root->appendChild($doc->importNode($this->dom->documentElement, true));

        // Append root node
        $doc->appendChild($root);

        // Format output
        $doc->formatOutput = true;

        return $doc->saveXML();
	}
    function send()
    {
        if (headers_sent())
            throw new FeedException('Cannot send RSS because headers have already been sent.');

        header('Content-Type: '.self::mime_type.'; charset=' . $this->dom->actualEncoding);

        echo $this->asXML();
    }

	static function import(SimpleXMLElement $el)
	{
		$fd = new RSSFeedData();
		if(isset($el->image) && isset($el->image->url))
			$fd->image = (string)$el->image->url;
		if(isset($el->cloud))
			$fd->cloud = array('domain'=>(string)$el->cloud['domain'],
				'port' => $el->cloud['port'],
				'path' => $el->cloud['path'],
				'procedure' => $el->cloud['registerProcedure'],
				'protocol'=> $el->cloud['protocol']);

		if(isset($el->textInput))
			$fd->setTextInput($el->textInput->title, $el->textInput->description, 
				$el->textInput->name, $el->textInput->link);
		
		if(isset($el->skipHours))
			foreach($el->skipHours->children() as $v)
				if($v->getName() == "hour")
					$fd->addSkipHours((string)$v);

		if(isset($el->skipDays))
			foreach($el->skipDays->children() as $v)
				if($v->getName() == "day")
					$df->addSkipDays((string)$v);

		foreach($el->children() as $v)
			if($v->getName() == 'item')
			{
				if(isset($v->description))
					$_content = (string)$v->description;
				else
				{
					$_c = $v->children('content',true);
					$_content = (string)$_c->encoded;
					unset($_c);
				}
				$fe = new FeedEntry((string)$v->title, (string)$v->link, $_content);
				unset($_content);
				foreach($v->children() as $e)
					if($e->getName() == "pubDate")
						$fe->last_update = (string)$e;
					elseif($e->getName() == "category")
						$fe->addCategory((string)$e, $e['domain']);
					elseif($e->getName() == "source")
						$fe->setSource((string)$e,$e['url']);
					elseif($e->getName() == "enclosure")
						$fe->addEnclosure($e['url'],$e['type'],$e['length']);
					else
						$fe->{$e->getName()} = (string)$e;
				if(!$fe->isReady())
					throw new FeedException('Not all required fields are filled-in');
				$fd->addEntry($fe);
				unset($fe);
			}
			elseif(in_array($v->getName(),array('image','cloud','textInput','skipHours','skipDays'))) continue;
			else
				$fd->{(string)$v->getName()} = (string)$v;
		if(!$fd->isReady()) 
			throw new Exception('Not all required feilds of FeedData are filled-in');
		return $fd;
	}

}

class RSSFeedData extends FeedData
{
	private $map = array(
		'pubDate' => 'last_update',
		'lastBuildDate'=>'published',
		'managingEditor' => 'author',
		'webMaster'=>'webmaster',
		'docs'=>"",
		'item'=>""
		);
	function __set($var, $val)
	{
		if(isset($this->map[$var]) && $this->map[$var] === "") return;
		if(isset($this->map[$var]) && $this->map[$var] !== "")
			$this->{$this->map[$var]} = $val;
		else parent::__set($var,$val);
	}
}
