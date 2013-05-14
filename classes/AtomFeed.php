<?php

class AtomEntry {
	public $id;
	public $title;
	public $updated;
	public $authors;
	public $content;
	public $contentType="text";
	public $summary="";
	public $alternateLink="";

}

class AtomFeed {
	public $id;
	public $title;
	public $subtitle;
	public $selfLink;
	public $updated;
	public $authors;
	public $entries;
	public $alternateLink="";

}
?>
