<?php

class Entry {
	public $id; // Id from DB
	public $entryId=""; // Id from XML
	public $title="";
	public $updated=0;
	public $published=0;
	public $authors="";
	public $content="";
	public $contentType="text";
	public $alternateLink="";

}

class Feed {
	public $id; // Id from DB
	public $feedId=""; //Id from XML
	public $title="";
	public $subtitle="";
	public $selfLink="";
	public $updated=0;
	public $authors="";
	public $entries=array();
	public $alternateLink="";

}
?>
