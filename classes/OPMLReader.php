<?php

class OPMLReader {
	private $xmlReader = null;

	public function parseFile($filename) {
		if ($this->xmlReader == null) $this->xmlReader = new XMLReader();
		if ($this->xmlReader->open($filename)) {
			// Look for opml tag
			$this->xmlReader->read();
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				if ($this->xmlReader->name == "opml") {
					$feedUrls =  $this->parseOPML();
					$this->xmlReader->close();
					return $feedUrls;
				}
			} 

		}
		return false;
	}

	private function parseOPML() {
		$feedUrls = array();
		$depth = 0;
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				if ($this->xmlReader->name == "outline") {
					if (!$depth) $depth = $this->xmlReader->depth; // store depth of first outline element
					if ($this->xmlReader->depth > $depth) {
						// this is a child element 
						$xmlUrl = $this->xmlReader->getAttribute("xmlUrl");
						if (!empty($xmlUrl)) {
							$feedUrls[$folderName][] = $xmlUrl;
						}
					}else {
						// could be a parent or leaf node
						$xmlUrl = $this->xmlReader->getAttribute("xmlUrl");
						if (!empty($xmlUrl)) {
							// is a leaf node
							$feedUrls["root"][] = $xmlUrl;
						}else {
							// is a parent
							$folderName = $this->xmlReader->getAttribute("text");
							if (empty($foldername)) $foldername = "root";
						}
					}
				}
			}

		}

		return $feedUrls;
	}

}
/*
$parser = new OPMLReader();
var_dump($parser->parseFile("/home/tahera/subscriptions.xml"));

*/









?>
