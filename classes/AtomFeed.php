<?php

class AtomEntry {
	public $id; // Id from DB
	public $entryId; // Id from XML
	public $title;
	public $updated;
	public $authors;
	public $content;
	public $contentType="text";
	public $alternateLink="";

}

class AtomFeed {
	public $id; // Id from DB
	public $feedId; //Id from XML
	public $title;
	public $subtitle;
	public $selfLink;
	public $updated;
	public $authors;
	public $entries;
	public $alternateLink="";

}
?>
