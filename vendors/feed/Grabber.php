<?php
require_once("FeedData.php");
require_once("RSSFeed.php");
require_once("AtomFeed.php");
Autoload::addVendorDir("gCurl");
class Grabber
{
    
    public static function importUrl($url)
    {

        $is_html = false;
        $socket=Config::get('feed_grabber_transport');
        if($socket=='gCurl')
        {
            try{
               $curl = new gCurl($url);
               $r=$curl->exec();
               $h=$r->getHeaderValues('Content-Type');
               if(!(strpos($h,"text/html")===false))
                   $is_html = true;

            }catch (gksException $e){
                $message = $e->getLogMessage();
                echo $e->getHtmlMessage();
             }
        } 
        else
        {
            $f=fopen($url,'r');
            $r=stream_get_contents($f);
            $s=stream_get_meta_data($f);
            foreach($s['wrapper_data'] as $w)
                if(preg_match('/^Content-Type:\s*(\S+);/',$w,$match) && !empty($match) && $match[1] == "text/html")
                    $is_html = true;
        }

        if($is_html)
        {
            $pattern = '~(<?link[^>]+)/?>~i';
            $result = @preg_match_all($pattern, $r, $matches);
            if($result===false) return;
            if(isset($matches[1])&& count($matches[1]>0))
                foreach($matches[1] as $lnk)
                {
                    $xml = @simplexml_load_string(rtrim($lnk, ' /') . ' />');
                    if ($xml === false) {
                        continue;
                    }
                    $attributes = $xml->attributes();
                    if (!isset($attributes['rel']) || !@preg_match('~^(?:alternate|service\.feed)~i', $attributes['rel'])) {
                        continue;
                    }
                    if (!isset($attributes['type']) ||
                            !@preg_match('~^application/(?:atom|rss|rdf)\+xml~', $attributes['type'])) {
                        continue;
                    }
                    if (!isset($attributes['href'])) {
                        continue;
                    }
                    //$l=$attributes['href'];
                    //$data = @simplexml_load_file($l);
                    $data = file_get_contents($attributes['href']);
                    return self::importString($data);
                 }
        }
        else
            return self::importString($r);
    }

    public static function importString($string)
    {
        $xml = simplexml_load_string($string);
        if ($xml === false) return;
        if(isset($xml->channel))
            {
                // RSS
                return RSSFeed::import($xml->channel[0]);
            }
            if(isset($xml->entry))
            {
                // Atom
                return  AtomFeed::import($xml);
            }
    }
}

?>
