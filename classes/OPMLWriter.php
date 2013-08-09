<?php
include_once "FeedManager.php";
include_once "FolderManager.php";

class OPMLWriter {
	private $xmlWriter = null;

	public function __construct() {
		$this->xmlWriter = new XMLWriter();
	}
	
	// Exports feeds to an OPML file with given name
	// Returns false on failure
	public function exportFeedsToFile($userId, $filename) {
		if (!$this->xmlWriter->openURI($filename)) {
			error_log("FeedAggregator::OPMLWriter::exportFeedsToFile:: Cannot open file ".$filename, 0);
			return false;
		}
		$this->xmlWriter->setIndent(true);
		$this->writeBegining();
		$folderManager = new FolderManager();
		$feedManager = new FeedManager();
		if ($folders = $folderManager->getFolders($userId)) {
			foreach($folders as $folder) {
				if($feeds = $feedManager->getFeedsFromFolder($folder->id)) {
					if ($folder->name != "root") {
						// Insert an outline element for folder
						$this->xmlWriter->startElement("outline");
						$this->xmlWriter->writeAttribute("text", $folder->name);	
						$this->xmlWriter->writeAttribute("title", $folder->name);
					}
					foreach($feeds as $feed) {
						// insert and outline element for feed		
						$this->xmlWriter->startElement("outline");
						$this->xmlWriter->writeAttribute("text", $feed->title);	
						$this->xmlWriter->writeAttribute("title", $feed->title);
						$this->xmlWriter->writeAttribute("xmlUrl", $feed->selfLink);	
						$this->xmlWriter->writeAttribute("htmlUrl", $feed->alternateLink);
						$this->xmlWriter->endElement();	
					}
					if ($folder->name != "root") $this->xmlWriter->endElement();
				
				}else if(is_bool($feeds)){
					error_log("FeedAggregator::OPMLWriter::exportFeedsToFile:: getFeedsFromFolder failed ", 0);
					return false;
				}	
			}

		}else if (is_bool($folders)){
			error_log("FeedAggregator::OPMLWriter::exportFeedsToFile:: getFolders failed", 0);
			return false;
		}
		$this->writeEnd();
		$this->xmlWriter->flush();
		return true;
	}

	// Write the tags preceding feeds
	private function writeBegining() {
		$this->xmlWriter->startDocument("1.0", "UTF-8");
		$this->xmlWriter->startElement ("opml");
		$this->xmlWriter->writeAttribute("version", "1.0");

		$this->xmlWriter->startElement("head");
		$this->xmlWriter->writeElement("title", "Subscriptions from Feed Reader");
		$this->xmlWriter->endElement();

		$this->xmlWriter->startElement("body");
	}

	private function writeEnd() {
		$this->xmlWriter->endElement(); // End body
		$this->xmlWriter->endElement(); // End opml
		$this->xmlWriter->endDocument();
	}


}

/*
$opmlWriter = new OPMLWriter();
$opmlWriter->exportFeedsToFile(6, "files/temp.xml");
*/
?>
