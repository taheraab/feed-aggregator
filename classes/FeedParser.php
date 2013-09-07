<?php

include_once "Feed.php";
include_once (dirname(__FILE__)."/../includes/htmlpurifier-4.5.0-lite/library/HTMLPurifier.auto.php");

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
							$feed->selfLink = $feedURL;
							$this->xmlReader->close();
							return ($feed) ? $this->sanitizeFeed($feed) : false;
						case "feed":
							$feed = $this->parseFeedTag();
							if (empty($feed->selfLink)) $feed->selfLink = $feedURL;
							$this->xmlReader->close();
							return ($feed) ? $this->sanitizeFeed($feed) : false;
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
						$feed->$elmName = $this->getElementText();
						break;
					case "updated":
						$date = new DateTime($this->getElementText());
						if ($date) $feed->updated = $date->getTimestamp();
						break;
					case "link":
						if ($this->xmlReader->getAttribute("rel") == "self") $feed->selfLink = $this->xmlReader->getAttribute("href");
						if ($this->xmlReader->getAttribute("rel") == "alternate") $feed->alternateLink = $this->xmlReader->getAttribute("href");
						break;
					case "author":
						do {
							$this->xmlReader->read();
							if (($this->xmlReader->nodeType == XMLReader::ELEMENT) && ($this->xmlReader->name == "name")) {
								$value = $this->getElementText();
								$feed->authors = empty($feed->authors) ? $value : $feed->authors.", ".$value;
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
		if ($feed->feedId && $feed->updated) return $feed;	// Return only valid feed
		else return false;
	}

	//Function that returns an AtomEntry object
	
	private function parseEntryTag() {
		$entry= new Entry();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT && $this->xmlReader->name == "entry") break; // reached end of entry
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				$elmName = $this->xmlReader->name;
				switch ($this->xmlReader->name) {
					case "id":
					case "title":
						if ($elmName == "id") $elmName = "entryId";
						$entry->$elmName = $this->getElementText();
						break;
					case "updated":
					case "published":
						$date = new DateTime($this->getElementText());
						if ($date) $entry->$elmName = $date->getTimestamp();
						break;
					case "content":
					case "summary":
						$contentType = $this->xmlReader->getAttribute("type");
						if($contentType != null) {
							 $entry->contentType = $contentType;
							// if contentType is not text, html or xhtml skip this entry (Not supported)
							if (($contentType != "text") && ($contentType != "html") && ($contentType != "xhtml")) $skipEntry = true;
						}
						$entry->content = $this->getElementText();
						break;
					case "link":
						if ($this->xmlReader->getAttribute("rel") == "alternate") $entry->alternateLink = $this->xmlReader->getAttribute("href");
						break;
					case "author":
						do {
							$this->xmlReader->read();
							if (($this->xmlReader->nodeType == XMLReader::ELEMENT) && ($this->xmlReader->name == "name")) {
								$value = $this->getElementText();
								$entry->authors = empty($entry->authors) ? $value : $entry->authors.", ".$value;
							}
						}while ($this->xmlReader->name != "author");
						break;
				}			
			
			}
		}	
		// Return only valid entry;
		if ($entry->entryId && $entry->updated) return $entry;
		else return false;
	}

	private function parseChannelTag() {
		$feed = new Feed();
		while ($this->xmlReader->read()) {
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
				switch ($this->xmlReader->name) {
					case "link": // maps to id and alternateLink 
						$feed->feedId = $feed->alternateLink = $this->getElementText();
						break;
					case "title": // maps to title
						$feed->title = $this->getElementText();
						break;
					case "description": //maps to subtitle
						$feed->subtitle = $this->getElementText();
						break;
					case "lastBuildDate": //maps to updated in both feed and entry
						$date = new DateTime($this->getElementText());
						if ($date) $lastBuildDate = $feed->updated = $date->getTimestamp();
						break;
					case "managingEditor": //maps to author
						$feed->authors = $this->getElementText();
						break;
					case "pubDate": // maps to updated if not already set
						$date = new DateTime($this->getElementText());
						if ($date) $pubDate = $date->getTimestamp();
						break;
					case "item": //maps to entry
						$entry = $this->parseItemTag();   
						$feed->entries[] = $entry;
						break;
				}			
			
			}
		}
		// Rss items have many optional elements, let's make good guesses for our required updated property 
			$currentDate = new DateTime();
			//if we're here then entry updated is not set
		foreach($feed->entries as $entry) {
			$i = 1;
			if (!$entry->updated) { // if pubDate is absent , then use lastBuildDate else currentdate
				if (isset($lastBuildDate)) $entry->updated = $lastBuildDate;
				else $entry->updated = $currentDate->getTimestamp();
			}
			// If id is empty let's make good guesses for our required id property
			if (empty($entry->entryId)) {
				// Use link as id, else pubDate , else use an index
				if (!empty($entry->alternateLink)) $entry->entryId = $entry->alternateLink;
				else if($entry->published) $entry->entryId = $entry->published;
				else $entry->entryId = $i++;
			}	
		}
	
		if(!$feed->updated) {
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
						$entry->entryId = $this->getElementText();
						break;
					case "title": //maps to title
						$entry->title = $this->getElementText();
						break;
					case "pubDate": //maps to published
						$date = new DateTime($this->getElementText());
						if ($date) $entry->updated= $entry->published = $date->getTimestamp();
						break;
					case "description": //maps to content
						$entry->content = $this->getElementText();
						break;
					case "link": //maps to alternateLink
						$entry->alternateLink = $this->getElementText();
						break;
					case "author":
						$entry->authors = $this->getElementText();
						break;
				}			
			
			}
		}	
	return $entry;
	}


	// Gets the text contents of a leaf element (an element without child elements)
	private function getElementText() {
		$value = "";
		$name = $this->xmlReader->name;
		if (!$this->xmlReader->isEmptyElement) { 
			$this->xmlReader->read();
			if ($this->xmlReader->nodeType == XMLReader::TEXT) {
				$value = $this->xmlReader->value;
			}else {
				//Check if there's a CDATA
				while($this->xmlReader->nodeType != XMLReader::CDATA) {
					if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT && $this->xmlReader->name == $name) break; 
					if (!$this->xmlReader->read()) break;
				}
				if ($this->xmlReader->nodeType == XMLReader::CDATA) 
					$value = $this->xmlReader->value;

			}
		}
		return $value;
	}

	// Sanitize feed for safe database insertion and html display
	private function sanitizeFeed(Feed $feed) {
      // Configure html purifier
        $config = HTMLPurifier_Config::createDefault();
    //  $config->set("HTML.DocType", "HTML 4.01 Transitional"); not sure if I want this yet
        $purifier = new HTMLPurifier($config);

		$feed->title = filter_var($feed->title, FILTER_SANITIZE_STRING);
		$feed->subtitle = $purifier->purify($feed->subtitle);
		$feed->selfLink = filter_var($feed->selfLink, FILTER_SANITIZE_URL);
		$feed->alternateLink = filter_var($feed->alternateLink, FILTER_SANITIZE_URL);
		$feed->authors = filter_var($feed->authors, FILTER_SANITIZE_STRING);
		
		foreach($feed->entries as $entry) {
			$entry->title = filter_var($entry->title, FILTER_SANITIZE_STRING);
			$entry->authors= filter_var($entry->authors, FILTER_SANITIZE_STRING);
			$entry->alternateLink = filter_var($entry->alternateLink, FILTER_SANITIZE_URL);
			$entry->content = $purifier->purify($entry->content);
		}
		
		return $feed;

	}	
	
}
/*
//include_once "FeedManager.php";

$p = new FeedParser();
var_dump($p->getFeedUrls("http://blog.linuxmint.com/"));
//$feed = $p->parseFeed("http://feeds.feedburner.com/youthcurryblogspotcom");
//$feed = $p->parseFeed("/home/tahera/Documents/sample_rss_2.0.xml");
//$feed = $p->parseFeed("/home/tahera/Documents/sample_feed_content2.xml");
var_dump($feed);
//$feedManager = FeedManager::getInstance();
//echo $feedManager->createFeed(1, $feed);
//var_dump($feedManager->getFeeds(1));
//var_dump($feedManager->getEntries(1, 1));
*/
?>
