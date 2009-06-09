<?php

require_once("AbstractFeed.php");

class AtomFeed extends AbstractFeed
{
	const mime_type = 'application/atom+xml';

	protected function mapHeaders(AbstractFeedData $fd)
	{
        $feed = $this->dom->createElement('feed');
        $feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');

        $id = $this->dom->createElement('id', $fd->link);
        $feed->appendChild($id);

        $title = $this->dom->createElement('title');
        $title->appendChild($this->dom->createCDATASection($fd->title));
        $feed->appendChild($title);

		if (isset($fd->author)) 
		{
            $author = $this->dom->createElement('author');
            $name = $this->dom->createElement('name', $fd->author);
            $author->appendChild($name);
			if (isset($fd->email)) 
			{
                $email = $this->dom->createElement('email', $fd->email);
                $author->appendChild($email);
            }
            $feed->appendChild($author);
        }

        $updated = isset($fd->last_update) ? $fd->last_update : time();
        $updated = $this->dom->createElement('updated', date(DATE_ATOM, $updated));
        $feed->appendChild($updated);

		if (isset($fd->published)) 
		{
            $published = $this->dom->createElement('published', date(DATE_ATOM, $fd->published));
            $feed->appendChild($published);
        }

        $link = $this->dom->createElement('link');
        $link->setAttribute('rel', 'self');
        $link->setAttribute('href', $fd->link);
		if (isset($fd->language)) 
            $link->setAttribute('hreflang', $fd->language);
        $feed->appendChild($link);

		if (isset($fd->description)) 
		{
            $subtitle = $this->dom->createElement('subtitle');
            $subtitle->appendChild($this->dom->createCDATASection($fd->description));
            $feed->appendChild($subtitle);
        }

		if (isset($fd->copyright)) 
		{
            $copyright = $this->dom->createElement('rights', $fd->copyright);
            $feed->appendChild($copyright);
        }

        if (isset($fd->image)) {
            $image = $this->dom->createElement('logo', $fd->image);
            $feed->appendChild($image);
        }

        $generator = !empty($fd->generator) ? $fd->generator : 'Zend_Feed';
        $generator = $this->dom->createElement('generator', $generator);
		$feed->appendChild($generator);

		$this->dom->appendChild($feed);

	}
	protected function mapEntries(AbstractFeedData $fd)
	{
		foreach ($fd->entries as $dataentry) 
		{
            $entry = $this->dom->createElement('entry');

            $id = $this->dom->createElement('id', isset($dataentry->guid) ? $dataentry->guid : $dataentry->link);
            $entry->appendChild($id);

			if(!empty($dataentry->author))
			{
				$author = $this->dom->createElement('author');
				$name = $this->dom->createElement('name', $dataentry->author);
				$author->appendChild($name);
				$entry->appendChild($author);

			}
            $title = $this->dom->createElement('title');
            $title->appendChild($this->dom->createCDATASection($dataentry->title));
            $entry->appendChild($title);

            $updated = isset($dataentry->last_update) ? $dataentry->last_update : time();
            $updated = $this->dom->createElement('updated', date(DATE_ATOM, $updated));
            $entry->appendChild($updated);

            $link = $this->dom->createElement('link');
            $link->setAttribute('rel', 'alternate');
            $link->setAttribute('href', $dataentry->link);
            $entry->appendChild($link);

            $summary = $this->dom->createElement('summary');
            $summary->appendChild($this->dom->createCDATASection($dataentry->description));
            $entry->appendChild($summary);

			if (isset($dataentry->content)) 
			{
                $content = $this->dom->createElement('content');
                $content->setAttribute('type', 'html');
                $content->appendChild($this->dom->createCDATASection($dataentry->content));
                $entry->appendChild($content);
            }

			if (!empty($dataentry->category)) 
			{
				foreach ($dataentry->category as $category) 
				{
                    $node = $this->dom->createElement('category');
                    $node->setAttribute('term', $category['term']);
					if (isset($category['scheme'])) 
                        $node->setAttribute('scheme', $category['scheme']);
                    $entry->appendChild($node);
                }
            }

			if (!empty($dataentry->source)) 
			{
                $source = $this->dom->createElement('source');
                $title = $this->dom->createElement('title', $dataentry->source['title']);
                $source->appendChild($title);
                $link = $this->dom->createElement('link', $dataentry->source['title']);
                $link->setAttribute('rel', 'alternate');
                $link->setAttribute('href', $dataentry->source['url']);
                $source->appendChild($link);
            }

			if (!empty($dataentry->enclosure)) 
				foreach ($dataentry->enclosure as $enclosure) 
				{
                    $node = $this->dom->createElement('link');
                    $node->setAttribute('rel', 'enclosure');
                    $node->setAttribute('href', $enclosure['url']);
                    if (isset($enclosure['type']))
                        $node->setAttribute('type', $enclosure['type']);
                    if (isset($enclosure['length']))
                        $node->setAttribute('length', $enclosure['length']);
                    $entry->appendChild($node);
                }

			if (isset($dataentry->comments)) 
			{
                $comments = $this->dom->createElementNS('http://wellformedweb.org/CommentAPI/',
                                                             'wfw:comment',
                                                             $dataentry->comments);
                $entry->appendChild($comments);
            }
			if (isset($dataentry->commentRss)) 
			{
                $comments = $this->dom->createElementNS('http://wellformedweb.org/CommentAPI/',
                                                             'wfw:commentRss',
                                                             $dataentry->commentRss);
                $entry->appendChild($comments);
            }
            $this->dom->documentElement->appendChild($entry);
		}
	}
    public function asXml()
    {
        // Return a complete document including XML prologue.
        $doc = new DOMDocument($this->dom->version,
                               $this->dom->actualEncoding);
        $doc->appendChild($doc->importNode($this->dom->documentElement, true));
        $doc->formatOutput = true;

        return $doc->saveXML();
    }
	function send()
	{
        if (headers_sent())
            throw new FeedException('Cannot send ATOM because headers have already been sent.');

        header('Content-Type: '.self::mime_type.'; charset=' . $this->dom->actualEncoding);
        echo $this->asXML();
	}
}
