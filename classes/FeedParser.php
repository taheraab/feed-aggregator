<?php
include "Feed.php";
include "FeedManager.php";

class FeedParser {

	private $xmlReader = null;
	
	// Parses the Xml feed from given url and returns the ATomFeed object	
	public function parseFeed($feedURL){
		if ($this->xmlReader == null) $this->xmlReader= new XMLReader();
		if ($this->xmlReader->open($feedURL)) {
			while ($this->xmlReader->read())  {
				if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
					switch ($this->xmlReader->name) {
						case "rss":
							$version = $this->xmlReader->getAttribute("version");
							if ($version != "2.0") {
								error_log("FeedAggregator::FeedParser parseFeed: Only RSS 2.0 and Atom formats are supported", 0);
								break;
							}
							break;
						case "channel":
							$feed = $this->parseChannelTag();
							$this->xmlReader->close();
							return $feed;
						case "feed":
							$feed = $this->parseFeedTag();
							$this->xmlReader->close();
							return $feed;
					}
				}
			}
			
		}else {
			error_log("FeedAggregator::FeedParser::parseAtomFeed: Couldn't open feedURL: ".$feedURL,0);
		}
		return false;
	}

	private function parseFeedTag() {
		$feed = new Feed();
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
						$entry = $this->parseEntryTag();
						if($entry) $feed->entries[] = $entry;
						break;
				}			
			
			}
		}	
		return $feed;
	}

	//Function that returns an AtomEntry object
	
	private function parseEntryTag() {
		$entry= new Entry();
		$skipEntry = false;
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->name == "entry") break; // reached end of entry
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				$elmName = $this->xmlReader->name;
				switch ($this->xmlReader->name) {
					case "id":
					case "title":
						if ($elmName == "id") $elmName = "entryId";
						$this->xmlReader->read(); //move to the containing text node
						$entry->$elmName = $this->xmlReader->value;
						break;
					case "updated":
					case "published":
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$entry->$elmName = $date->getTimestamp();
						break;
					case "content":
					case "summary":
						$contentType = $this->xmlReader->getAttribute("type");
						if($contentType != null) {
							 $entry->contentType = $contentType;
							// if contentType is not text, html or xhtml skip this entry (Not supported)
							if (($contentType != "text") && ($contentType != "html") && ($contentType != "xhtml")) $skipEntry = true;
						}
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
		return ($skipEntry)? false : $entry;
	}


	private function parseChannelTag() {
		$feed = new Feed();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				switch ($this->xmlReader->name) {
					case "link": // maps to id and alternateLink 
						$this->xmlReader->read();
						$feed->feedId = $feed->alternateLink = $this->xmlReader->value;
						break;
					case "title": // maps to title
						$this->xmlReader->read();
						$feed->title = $this->xmlReader->value;
						break;
					case "description": //maps to subtitle
						$this->xmlReader->read(); //move to the containing text node
						if ($this->xmlReader->nodeType != XMLReader::TEXT) $this->moveToCdataNode(); // if not text, it must be CDATA
						$feed->subtitle = $this->xmlReader->value;
						break;
					case "lastBuildDate": //maps to updated in both feed and entry
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$lastBuildDate = $feed->updated = $date->getTimestamp();
						break;
					case "managingEditor": //maps to author
						$this->xmlReader->read();
						$feed->authors = $this->xmlReader->value;
						break;
					case "pubDate": // maps to updated if not already set
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$pubDate = $date->getTimestamp();
						break;
					case "item": //maps to entry
						$entry = $this->parseItemTag();
						if(isset($lastBuildDate)) $entry->updated = $lastBuildDate;
						$feed->entries[] = $entry;
						break;
				}			
			
			}
		}
		// Rss items have many optional elements, let's make good guesses for our required updated property 
		if (!$feed->updated) {
			$currentDate = new DateTime();
			//if we're here then entry updated is not set
			foreach($feed->entries as $entry) {
				$i = 1;
				if ($entry->published) $entry->updated = $entry->published;
				else $entry->updated = $currentDate->getTimestamp();
				// If id is empty let's make good guesses for our required id property
				if (empty($entry->id)) {
					// Use link as id, else pubDate , else use an index
					if (!empty($entry->alternateLink)) $entry->entryId = $entry->alternateLink;
					else if($entry->published) $entry->entryId = $entry->published;
					else $entry->id = $i++;
				}	
	
			}
			// if lastBuildDate was not present, then use pubDate, else use first entry's updated value
			if (isset($pubDate)) $feed->updated = $pubDate;
			else $feed->updated = $feed->entries[0]->updated;
			
		}	
		return $feed;
	}

	private function parseItemTag() {
		$entry= new Entry();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->name == "item") break; // reached end of entry
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				switch ($this->xmlReader->name) {
					case "guid": //maps to Id
						$this->xmlReader->read();
						$entry->entryId = $this->xmlReader->value;
						break;
					case "title": //maps to title
						$this->xmlReader->read();
						$entry->title = $this->xmlReader->value;
						break;
					case "pubDate": //maps to published
						$this->xmlReader->read();
						$date = new DateTime($this->xmlReader->value);
						$entry->published = $date->getTimestamp();
						break;
					case "description": //maps to content
						$this->xmlReader->read();
						if ($this->xmlReader->nodeType != XMLReader::TEXT) $this->moveToCdataNode(); // if not text, it must be CDATA
						$entry->content = $this->xmlReader->value;
						break;
					case "link": //maps to alternateLink
						$this->xmlReader->read();
						$entry->alternateLink = $this->xmlReader->value;
						break;
					case "author":
						$this->xmlReader->read();
						$entry->authors = $this->xmlReader->value;
						break;
				}			
			
			}
		}	
	return $entry;
	}

	private function moveToCdataNode() {
		while ($this->xmlReader->nodeType != XMLReader::CDATA) 
			$this->xmlReader->read();
	}	
	
}

$p = new FeedParser();
//var_dump($p->parseFeed("http://tahera-test.blogspot.com/feeds/posts/default"));

//$feed = $p->parseFeed("http://feeds.feedburner.com/tedblog");
$feed = $p->parseFeed("/home/tahera/Documents/sample_rss_2.0.xml");
//$feed = $p->parseFeed("/home/tahera/Documents/sample_feed_content2.xml");
var_dump($feed);
//$feedManager = FeedManager::getInstance();
//$feedManager->createFeed(1, $feed);
?>
