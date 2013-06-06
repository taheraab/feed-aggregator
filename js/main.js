var myFeeds = [];
var myEntries = []; //entries corresponding to a selected feed
var activeFeedId = 0;
var activeEntryId = 0;
var $activeEntry = null;

// Create the navigation menu
$(document).ready(function(){
	loadFeeds();
	// create navigation list
	$("#subsList").find("ul").parent().prepend("<span onclick=\"$(this).parent().toggleClass('collapsed');\" ></span> ");
});


// Loads feeds from server and populates the navigation list
function loadFeeds() {
	$.getJSON("manage_feeds.php?getFeeds", function(feeds) {
		myFeeds = feeds;
		var i;
		var $feedList = $("#feedList");
		for (i = 0; i < myFeeds.length; i++) {
			$feedList.append("<li><img src = '" + myFeeds[i].alternateLink + "/favicon.ico' width='20px' height='20px'></img>" +
			" <a href = 'javascript:loadEntries(" + i + ");'>" + myFeeds[i].title + "</a></li>");
		}
	});

}

// Load entries for a given feed (index into myFeeds)
function loadEntries(i) {
	activeFeedId = myFeeds[i].id;
	var $entryList = $("#entryList");
	$entryList.empty();
	$entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
	$.getJSON("manage_feeds.php?getEntries&feedId=" + myFeeds[i].id, function(entries) {
		myEntries = entries;
		for (var i = 0; i < myEntries.length; i++) {
			var content = "<section><div><div><div class='title'><a href='" + myEntries[i].alternateLink + "'>" + myEntries[i].title + "</a></div>";
			if (myEntries[i].authors != "") content += "<div class='author'>by " + myEntries[i].authors + "</div></div>";
			var updated = new Date(myEntries[i].updated * 1000); // convert unix timestamp into miliseconds
			content += "<div class='updated'>" + updated.toLocaleString() + "</div></div>";
			content += "<br /><div>" + myEntries[i].content + "</div></section>";
			$entryList.append(content);
		}
		$activeEntry = $("#entryList > section:first");
		$activeEntry.addClass("highlighted");
		//Add scroll event handler
		$entryList.scroll(setActiveEntry);
	});
}

// Highlights the current visible entry in the #entryList viewPort and sets current entryId
function setActiveEntry() {
	var $viewport = $(this);
	//Check if current entry is still on top of the viewPort
	var activeEntryTop = $activeEntry.position().top;
	var activeEntryBottom = activeEntryTop + $activeEntry.outerHeight(true);
	var viewportBottom = $viewport.scrollTop() + $viewport.innerHeight();
	// If active entry has been scrolled up, replace it with next entry
	if (activeEntryTop < 0) {
		$activeEntry.toggleClass("highlighted");
		$activeEntry = $activeEntry.next();
		$activeEntry.toggleClass("highlighted");
	}else if (activeEntryBottom > viewportBottom) { // if it has been scrolled down
		$activeEntry.toggleClass("highlighted");
		$activeEntry = $activeEntry.prev();
		$activeEntry.toggleClass("highlighted");
	}
	//Check which entry is on the top of the viewPort
	//var $entries = $("#entryList > section");
	
}




