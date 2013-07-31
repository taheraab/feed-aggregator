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
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				if ($this->xmlReader->name == "outline") {
					$xmlUrl = $this->xmlReader->getAttribute("xmlUrl");
					if (!empty($xmlUrl)) $feedUrls[] = $xmlUrl;
				}
			}

		}

		return $feedUrls;
	}




}











?>
