<?php


abstract class AbstractFeed
{

	protected 
		$dom = null
		;
	function __construct(AbstractFeedData $fd)
	{
		if(!$fd->isReady())
			throw new Exception('Feed data has no entries');

		$this->dom = new DOMDocument('1.0', $fd->charset);
		$this->mapHeaders($fd);
		$this->mapEntries($fd);
	}
	abstract public    function asXML();
	abstract protected function mapHeaders(AbstractFeedData $fd);
	abstract protected function mapEntries(AbstractFeedData $fd);
	abstract public    function send();
	

}
