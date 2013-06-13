var myFeeds = [];
var myEntries = []; //entries corresponding to a selected feed
var activeFeedId = 0;
var activeEntryId = 0;
var $activeEntry = null; // DOM object representing active entry
var $activeFeed = null; // DOM object representing active feed

function EntryObj(id, status, type) {
	this.id = id;
	this.status = status;
	this.type = type;
}


// Create the navigation menu
$(document).ready(function(){
	loadFeeds();
	// create navigation list
	$("#subsList").find("ul").parent().prepend("<span onclick=\"$(this).parent().toggleClass('collapsed');\" ></span> ");
	setUpdateTimer();
});

function setUpdateTimer() {
	window.setTimeout(updateEntries(), 60000); // save updated entries after 60 secs 

}

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
			" <a " + titleClass + "href = '#' onclick = 'setActiveFeed(" + i + ", $(this).parent());' >" + 
			myFeeds[i].title + " </a><span>" + unreadCount + "</span></li>");
		}
		setActiveFeed(0, $("#feedList > li:first-child"));
	});
}
// Called when a feed link in nav is clicked 
function setActiveFeed(i, $elm) {
	$activeFeed = $elm;
	loadEntries(i);
	return false; // prevent default link action
}


// Load entries for a given feed (index into myFeeds)
function loadEntries(i) {
	// Before loading new feed entries, send updates for previous feed entries
	updateEntries();
	var $entryList = $("#entryList");
	$entryList.empty();
	$entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
	$.getJSON("manage_feeds.php?getEntries&feedId=" + myFeeds[i].id, function(entries) {
		myEntries = entries;
		var unreadCount = 0;
		for (var i = 0; i < myEntries.length; i++) {
			var content = "<section><div><div><div class='title'><a href='" + myEntries[i].alternateLink + "'>" + myEntries[i].title + "</a></div>";
			if (myEntries[i].authors != "") content += "<div class='author'>by " + myEntries[i].authors + "</div>";
			var updated = new Date(myEntries[i].updated * 1000); // convert unix timestamp into miliseconds
			var checked = (myEntries[i].status == "unread")? "checked" : "";
			var starred = (myEntries[i].type == "starred") ? "class='starred'" : "";
			content += "</div><div class='updated'>" + updated.toLocaleString() + "</div></div>";
			content += "<br /><div>" + myEntries[i].content + "</div>";
			content += "<br /><div class='toolbar'><input type='hidden' name='id' value='" + myEntries[i].id + 
				"' /><span " + starred + " onclick='setEntryStarred($(this))'></span><input type='hidden' name='type' value='" + 
				myEntries[i].type + "' />" + "<span> &nbsp;&nbsp; </span><input type='checkbox' name='status' value='" + myEntries[i].status + 
				"' onchange='setEntryStatus($(this));' " + checked + "  />" + 
				"<label for='status'> Keep unread</label></div></section>";
			$entryList.append(content);
			if (myEntries[i].status == "unread" && myEntries[i].status == "new") ++unreadCount;
		}
		$entryList.append("<section id='last'>  No more Entries  </section>");
		$activeEntry = $("#entryList section:first-of-type");
		$activeEntry.addClass("highlighted");
		//Add scroll event handler
		$entryList.scroll(setActiveEntry);
		// Set unread entry count for the active feed
		if (unreadCount) {
			var $spanElm = $activeFeed.find("span");
			$spanElm.text("(" + unreadCount + ")");
			$spanElm.prev().addClass("unread"); // style the related link 
		}
	});
}

// Highlights the current visible entry in the #entryList viewPort and sets current entryId
function setActiveEntry() {
	var $viewport = $(this);
	//Check if current entry is still on top of the viewPort
	var activeEntryTop = $activeEntry.position().top;
	var activeEntryBottom = activeEntryTop + $activeEntry.outerHeight(true);
	var viewportBottom = $viewport.innerHeight();
	// If active entry has been scrolled up, replace it with next entry
	if (activeEntryTop < 0 && $activeEntry.attr("id") != "last") {
		$activeEntry.toggleClass("highlighted"); 
		// Also change status to read
		var $statusElm = $activeEntry.find(".toolbar > input[name='status']")
		if ($statusElm.val() == "new") {
			$statusElm.val("read");
			// decrement unread count for active feed
	    	updateUnreadCount("decrement");
			$activeEntry.addClass("updated");
		}
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
	if ($inputElm.val() == "unstarred") $inputElm.val("starred"); //  toggle value attr for the related input element
		else $inputElm.val("unstarred");
	$elm.parent().parent().addClass("updated");
}

// Updates entry status when Keep unread checkbox state changes
function setEntryStatus($elm) {
	if ($elm.prop("checked")) {
		$elm.val("unread"); 
		// Increment unread count for active feed
		updateUnreadCount("increment");
	}else {
		$elm.val("read");
		updateUnreadCount("decrement");
	}
	$elm.parent().parent().addClass("updated");
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
function updateEntries() {
	$updatedEntryList = $("#entryList section.updated");
	var entriesToUpdate = new Array();
	$updatedEntryList.each(function() {
		var entry = new EntryObj($(this).find(".toolbar > input[name='id']").val(), $(this).find(".toolbar > input[name='status']").val(),
						$(this).find(".toolbar > input[name='type']").val());
		entriesToUpdate.push(entry);
		$(this).removeClass("updated");
	});
	if (entriesToUpdate.length) {
		var data = JSON.stringify(entriesToUpdate);
		console.log(data);
		if (data) {
			$.ajax({
				type: "POST",
				contentType : "application/json; charset=UTF-8",
				url: "manage_feeds.php?updateEntries",
				data: data
			});
		}
	}
}


