<?php
/*
 * Based on ZendFramework's feed ZendFeed
 * Zend Technologies USA Inc. (http://www.zend.com)
 * http://framework.zend.com/license/new-bsd New BSD License
*/



class FeedItunesHeaders extends AbstractFeedData
{
	protected 
	$author, //Artist column  optional, default to the main author value
	$owner = array(), //Owner of the podcast optional 
		//name => name of the owner  optional, default to main author value
		// email => email of the owner  optional, default to main email value
	$image,  //album/podcast art  optional, default to the main image value
	$subtitle, //short description  optional, default to the main description value
	$summary,  //longer description  optional, default to the main description value
	$block,  //Prevent an episode from appearing (yes|no)  optional
	$category     = array(),  //Category column and in iTunes Music Store Browse required
							  // up to 3 rows
		// main => main category,  required
		// sub category  optional
	$explicit,  //parental advisory graphic (yes|no|clean)  optional
	$keywords,  //a comma separated list of 12 keywords maximum  optional
	$new_feed_url  //used to inform iTunes of new feed URL location  optional
	;

    public function addCategory(array $category = array())
    {
		if(!count($category))
            throw new FeedException("You have to set at least one itunes category");
		
		if(count($this->category) > 2)
            throw new FeedException("You have to set at most three itunes categories");

		if (empty($category['main']))
			throw new FeedException("You have to set the main category");

		$this->category[] = $category;
        return $this;
    }

    function setAuthor($author)
    {
		$this->author = (string)$author;
        return $this;
    }

    function setOwner($name = '', $email = '')
    {
		if (!empty($email) 
			&& !preg_match(POSTChecker::$email_regexp,$email))
                throw new FeedException("You have to set a valid email address into the itunes owner's email property");

		$this->owner = array('name'=>$name,'email'=>$email);
        return $this;
    }

    function setImage($image)
	{
		$this->image = (string)$image;
        return $this;
    }

    public function setSubtitle($subtitle)
    {
		$this->subtitle = (string)$subtitle;
        return $this;
    }

    public function setSummary($summary)
    {
		$this->summary = (string)$summary;
        return $this;
    }
    public function setBlock($block)
    {
        $block = strtolower($block);
        if (!in_array($block, array('yes', 'no')))
            throw new FeedException("You have to set yes or no to the itunes block property");
        $this->block = $block;
        return $this;
    }
    public function setExplicit($explicit)
    {
        $explicit = strtolower($explicit);
        if (!in_array($explicit, array('yes', 'no', 'clean')))
            throw new FeedException("you have to set yes, no or clean to the itunes explicit property");
        $this->explicit = $explicit;
        return $this;
    }
    public function setKeywords($keywords)
    {
		$this->keywords = (string) $keywords;
        return $this;
    }

    public function setNewFeedUrl($url)
    {
		if(!preg_match(POSTChecker::$url_regexp,$url))
			throw new FeedException('Wrong url format');
		$this->new_feed_url = $url;
        return $this;
    }
}

