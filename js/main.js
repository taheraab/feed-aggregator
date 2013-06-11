var myFeeds = [];
var myEntries = []; //entries corresponding to a selected feed
var activeFeedId = 0;
var activeEntryId = 0;
var $activeEntry = null; // DOM object representing active entry
var $activeFeed = null; // DOM object representing active feed

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
			var titleClass = "";
			var unreadCount = "";
			if (myFeeds[i].numUnreadEntries) {
				unreadCount= "(" +  myFeeds[i].numUnreadEntries + ")";
				titleClass = "class = 'unread'";
			}
			$feedList.append("<li><img src = '" + myFeeds[i].alternateLink + "/favicon.ico' width='20px' height='20px'></img>" +
			" <a " + titleClass + "href = '#' onclick = 'setActiveFeed(" + i + ", $(this));' >" + 
			myFeeds[i].title + " </a><span>" + unreadCount + "</span></li>");
		}
	});

}
// Called when a feed link in nav is clicked 
function setActiveFeed(i, $elm) {
	$activeFeed = $elm.parent();
	loadUnreadEntryCount(i);
	loadEntries(i);
	return false; // prevent default link action
}

// Load unread entry count for activeFeed from DB
function loadUnreadEntryCount(i) {
	$.getJSON("manage_feeds.php?getUnreadEntryCount&feedId=" + myFeeds[i].id, function(count) {
		if (count) {
			var $spanElm = $activeFeed.find("span");
			$spanElm.text("(" + count + ")");
			$spanElm.prev().addClass("unread"); // style the related link 
		}
	});

}

// Load entries for a given feed (index into myFeeds)
function loadEntries(i) {
	// Before loading new feed entries, send updates for previous feed entries
	$("#entriesForm").submit();
	var $entryList = $("#entryList > form");
	$entryList.empty();
	$entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
	$.getJSON("manage_feeds.php?getEntries&feedId=" + myFeeds[i].id, function(entries) {
		myEntries = entries;
		for (var i = 0; i < myEntries.length; i++) {
			var content = "<section><div><div><div class='title'><a href='" + myEntries[i].alternateLink + "'>" + myEntries[i].title + "</a></div>";
			if (myEntries[i].authors != "") content += "<div class='author'>by " + myEntries[i].authors + "</div></div>";
			var updated = new Date(myEntries[i].updated * 1000); // convert unix timestamp into miliseconds
			var checked = (myEntries[i].status == "unread")? "checked" : "";
			content += "<div class='updated'>" + updated.toLocaleString() + "</div></div>";
			content += "<br /><div>" + myEntries[i].content + "</div>";
			content += "<br /><div class='toolbar'><input type='hidden' name='id' value='" + myEntries[i].id + 
				"' /><span onclick='setEntryStarred($(this))'></span><input type='hidden' name='starred' value='false' />" + 
				"<span> &nbsp;&nbsp; </span><input type='checkbox' name='status' value='" + 
				myEntries[i].status + "' onchange='setEntryStatus($(this));' " + checked + "  />" + 
				"<label for='status'> Keep unread</label></div></section>";
			$entryList.append(content);
		}
		$activeEntry = $("#entryList section:first-of-type");
		$activeEntry.addClass("highlighted");
		//Add scroll event handler
		$entryList.parent().scroll(setActiveEntry);
	});
	return false;
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
		// Also change status to read
		$activeEntry.find(".toolbar > input[name='status']").attr("value", "read");
		// decrement unread count for active feed
	    updateUnreadCount("decrement");
		$activeEntry = $activeEntry.next();
		$activeEntry.toggleClass("highlighted");
	}else if (activeEntryBottom > viewportBottom) { 
		// if it has been scrolled down, replace with prev entry
		$activeEntry.toggleClass("highlighted");
		$activeEntry = $activeEntry.prev();
		$activeEntry.toggleClass("highlighted");
	}
	
}

// Sets the starred value for the entry when star is clicked.
function setEntryStarred($elm) {
	$elm.toggleClass("starred");
	$inputElm = $elm.next();
	if ($inputElm.attr("value") == "false") $inputElm.attr("value", "true"); //  toggle value attr for the related input element
		else $inputElm.attr("value", "false");
}

// Updates entry status when Keep unread checkbox state changes
function setEntryStatus($elm) {
	if ($elm.prop("checked")) {
		$elm.value = "unread"; 
		// Increment unread count for active feed
		updateUnreadCount("increment");
	}else {
		$elm.value = "read";
		updateUnreadCount("decrement");
	}

}

// Increments/Decrements unread count for active Feed element
function updateUnreadCount(option) {
	var $spanElm = $activeFeed.find("span");
	var countString = $spanElm.text(); 		
	if (countString) {
		var count = /\((\d+)\)/.exec(countString);
		if(count[1]) {
			if (option == 'decrement') 
				if (--count[1]) 
					$spanElm.text("(" + count[1] + ")");
				else {
					$spanElm.text("");
					$spanElm.prev().toggleClass("unread"); // remove unread style for the title
				}
			else if (option == 'increment') 
				$spanElm.text("(" + ++count[1] + ")");
		}
	}else if (option == "increment")  {
		$spanElm.text("(1)");
		$spanElm.prev().toggleClass("unread");	
	}
		
}

// Send update entries request to server
function updateEntries(evt, $elm) {
	//var data = $elm.serialize();
	//if (data) $.post("manage_feeds.php?updateEntries", data);
	evt.preventDefault();
}
