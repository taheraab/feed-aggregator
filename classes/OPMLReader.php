<?php

class OPMLReader {
	private $xmlReader = null;

	public function __construct() {
		$this->xmlReader = new XMLReader();
	}

	public function parseFile($filename) {
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

		}else {
			error_log("FeedAggregator::OPMLReader::parseFile: Couldn't open file ".$filename, 0);
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


?>
