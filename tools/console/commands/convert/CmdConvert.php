<?php
class CmdConvert extends Command{
    
	private $show_body_only = false;
	private $output_file = null;
	private $tidy_only = false;

    public function process()
    {
        Console::initCore();

		$version = array();
		exec('tidy -v 2>&1',$version, $ret);
		$version = implode("\n",$version);
		if($ret != 0) return io::out("You should install tidy to use converter. Exiting.") | 1;

        $ah = ArgsHolder::get();
        $this->show_body_only = $ah->getOption('show-body-only');
        $this->tidy_only = $ah->getOption('tidy_only');
		if (($f = ArgsHolder::get()->getOption('output'))) $this->output_file = trim($f,"/");

		if(($filename = ArgsHolder::get()->shiftCommand()) === false){
            $this->cmdHelp();
            return io::out("Choose file to convert. Exiting.",IO::MESSAGE_FAIL) | 2;
        }

		if(!file_exists($filename))
			$filename = getcwd()."/".trim($filename,"/");
		touch($filename);
		if(!file_exists($filename))
            return io::out("File ".$filename." not found",IO::MESSAGE_FAIL) | 2;

		if(!empty($this->output_file))
		{
			if(dirname($this->output_file) == "")
				$this->output_file = getcwd()."/".$this->output_file;
			if(!is_dir(dirname($this->output_file)))
				return io::out("Output direcotory doesn't exists",IO::MESSAGE_FAIL) | 4;
			touch($this->output_file);
		}

		$output = $ret = null;
		exec("LANG=en_EN.UTF8 tidy -config ".escapeshellarg(dirname(__FILE__)."/tidy.config")." -q ".
			(($this->show_body_only)?" --show-body-only yes ":" ").
			(" --error-file ".escapeshellarg(dirname(__FILE__))."/error.log ").
			escapeshellarg($filename),$output,$ret);

        if($ret == 0) 
            io::done('Tidy-ize done. ');
		elseif($ret == 1)
			io::out('Tidy-ize done, but with warnings. ('.dirname(__FILE__)."/error.log) ",IO::MESSAGE_WARN);
		else 
			return io::out("Tidy-ize failed. ",IO::MESSAGE_FAIL) | 3;


		if($this->tidy_only)
		{
			if(!empty($this->output_file))
			{
				io::out('Writing html to file ');
				$_r = file_put_contents($this->output_file,implode("\n",$output));
				if($_r === false)
                    return io::out("Can't write to file. May be permission denied? ",IO::MESSAGE_FAIL) | 5;
                io::done();
			}
			else 
				echo implode("\n",$output)."\n";
			return 0;
		}
		
		$doc = new DOMDocument('1.0','utf-8');
		$doc->loadHTML(implode("\n",$output));
		$doc->encoding="utf-8";
		
		$subst = array("a"=>"WHyperLink",
			"td"=>"WTableColumn",
			"tr"=>"WTableRow",
			"th"=>"WTableHeader",
			"table"=>"WTable",
			"br"=>"WText:br:1",
			"img"=>"WImage",
			"abbr"=>"WText:abbr:1",
			"acronym"=>"WText:acronym:1",
			"address"=>"WText:address:1",
			"b"=>"WText:b:1",
			"big"=>"WText:big:1",
			"blockquote"=>"WText:blockquote:1",
			"button"=>"WButton:type:button",
			"cite"=>"WText:cite:1",
			"code"=>"WText:code:1",
			"div"=>"WBlock",
			"dfn"=>"WText:dfn:1",
			"em"=>"WText:em:1",
			"fieldset"=>"WFieldSet",
			"form"=>"WForm",
			"h1"=>"WText:h:1",
			"h2"=>"WText:h:2",
			"h3"=>"WText:h:3",
			"h4"=>"WText:h:4",
			"h5"=>"WText:h:5",
			"h6"=>"WText:h:6",
			"hr"=>"WText:hr:1",
			"i"=>"WText:i:1",
			"input"=>"WEdit",
			"ins"=>"WText:ins:1",
			"kbd"=>"WText:kbd:1",
			"li"=>"WListItem",
			"ol"=>"WList:ol:1",
			"option"=>"WSelectOption",
			"p"=>"WText:p:1",
			"pre"=>"WText:pre:1",
			"q"=>"WText:q:1",
			"samp"=>"WText:samp:1",
			"script"=>"WInlineScript",
			"select"=>"WSelect",
			"small"=>"WText:small:1",
			"span"=>"WText",
			"strike"=>"WText:strike:1",
			"strong"=>"WText:strong:1",
			"style"=>"WCSS",
			"sub"=>"WText:sub:1",
			"sup"=>"WText:sup:1",
			"textarea"=>"WTextarea",
			"ul"=>"WList",
			"var"=>"WText:var:1",
			"body"=>"root"
		);

		foreach($subst as $replace_from=>$replace_to)
		{
			@list($replace_to,$new_attr_name, $new_attr_value) = explode(":",$replace_to);
			$nl = $doc->getElementsByTagName($replace_from);
			for($i = 0, $c = $nl->length; $i < $c; $i++)
			{
				$n_dn = $doc->createElement($replace_to);
				$cn = $nl->item(0);
				$cnl = $cn->childNodes;


				if($cn->hasAttributes())
					foreach($cn->attributes as $attrName => $attrNode)
						if(substr((string)$attrNode->value,0,2) != "__" && !empty($attrNode->value))
							$n_dn->setAttribute((string)$attrName,$attrNode->value);
				if(isset($new_attr_value,$new_attr_name) )
					$n_dn->setAttribute($new_attr_name,$new_attr_value);

				for($j = 0; $j < $cnl->length; $j++)
				{
					if($cnl->item($j) instanceof DOMText)
						$n_dn->appendChild($doc->createTextNode($cnl->item($j)->nodeValue));
					else
						$n_dn->appendChild($cnl->item($j)->cloneNode(true));
				}
				$cn->parentNode->replaceChild($n_dn,$cn);
			}
		}
		io::out('Dumping XML...', false);
		if($this->show_body_only)
		{
			$doc2 = new DOMDocument('1.0','utf-8');
			$doc2->encoding = "utf-8";

			$doc2->appendChild($doc2->importNode($doc->getElementsByTagName("root")->item(0),true));
			if(!empty($this->output_file))
				$doc2->save($this->output_file);
			else
				echo $doc2->saveXML();
		}
		else
			if(!empty($this->output_file))
				$doc->save($this->output_file);
			else
				echo $doc->saveXML();
	
		io::done();
    }
}
