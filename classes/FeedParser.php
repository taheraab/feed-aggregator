<?php
include "AtomFeed.php";
include "FeedManager.php";

class FeedParser {

	private $xmlReader = null;
	
	// Parses the Xml feed from given url and returns the ATomFeed object	
	public function parseFeed($feedURL){
		if ($this->xmlReader == null) $this->xmlReader= new XMLReader();
		if ($this->xmlReader->open($feedURL)) {
			while ($this->xmlReader->read())  {
				if ($this->xmlReader->name == "feed") {
					$feed = $this->parseFeedTag();
					$this->xmlReader->close();
					return $feed;
				}
			}
		}else {
			error_log("FeedAggregator::FeedParser::parseAtomFeed: Couldn't open feedURL: ".$feedURL,0);
		}
		return false;
	}

	private function parseFeedTag() {
		$feed = new AtomFeed();
		$feed->entries = array();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				switch ($this->xmlReader->name) {
					case "id":
					case "title":
					case "subtitle":
						$elmName = $this->xmlReader->name;
						if ($elmName == "id") $elmName = "feedId";
						$this->xmlReader->read(); //move to the containing text node
						$feed->$elmName = $this->xmlReader->value;
						break;
					case "updated":
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$feed->updated = $date->getTimestamp();
						break;
					case "link":
						if ($this->xmlReader->getAttribute("rel") == "self") $feed->selfLink = $this->xmlReader->getAttribute("href");
						if ($this->xmlReader->getAttribute("rel") == "alternate") $feed->alternateLink = $this->xmlReader->getAttribute("href");
						break;
					case "author":
						do {
							$this->xmlReader->read();
							if (($this->xmlReader->nodeType == XMLReader::ELEMENT) && ($this->xmlReader->name == "name")) {
								$this->xmlReader->read();
								$feed->authors = empty($feed->authors) ? $this->xmlReader->value : $feed->authors.", ".$this->xmlReader->value;
							}
						}while ($this->xmlReader->name != "author");
						break;
					case "entry":
						$feed->entries[] =$this->parseEntryTag();
						break;
				}			
			
			}
		}	
		return $feed;
	}

	//Function that returns an AtomEntry object
	
	private function parseEntryTag() {
		$entry= new AtomEntry();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->name == "entry") break; // reached end of entry
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				switch ($this->xmlReader->name) {
					case "id":
					case "title":
						$elmName = $this->xmlReader->name;
						if ($elmName == "id") $elmName = "entryId";
						$this->xmlReader->read(); //move to the containing text node
						$entry->$elmName = $this->xmlReader->value;
						break;
					case "updated":
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$entry->updated = $date->getTimestamp();
						break;
					case "content":
					case "summary":
						$contentType = $this->xmlReader->getAttribute("type");
						if($contentType != null) $entry->contentType = $contentType;
						$this->xmlReader->read(); //move to the containing text node
						if ($this->xmlReader->hasValue) $entry->content = $this->xmlReader->value;
						break;
					case "link":
						if ($this->xmlReader->getAttribute("rel") != "alternate") $entry->alternateLink = $this->xmlReader->getAttribute("href");
						break;
					case "author":
						do {
							$this->xmlReader->read();
							if (($this->xmlReader->nodeType == XMLReader::ELEMENT) && ($this->xmlReader->name == "name")) {
								$this->xmlReader->read();
								$entry->authors = empty($entry->authors) ? $this->xmlReader->value : $entry->authors.", ".$this->xmlReader->value;
							}
						}while ($this->xmlReader->name != "author");
						break;
				}			
			
			}
		}	
		return $entry;
	}

	
}

$p = new FeedParser();
//var_dump($p->parseFeed("http://tahera-test.blogspot.com/feeds/posts/default"));
$feed = $p->parseFeed("/home/tahera/Documents/sample_feed.xml");
//$feed = $p->parseFeed("/home/tahera/Documents/sample_feed_content2.xml");
//var_dump($feed);
$feedManager = FeedManager::getInstance();
$feedManager->createFeed(2, $feed);
?>
