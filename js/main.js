var myFeeds = [];
var $activeEntry = null; // DOM object representing active entry
var $activeFeed = null; // DOM object representing active feed
var $activeFolder = null;
var activeFeedIndex = 0;
var entryPageSize = 20;
var lastLoadedEntryId = 0;
var filter = "all";
var activeFolderId = 0;
var rootId;
var getFeedsTimerId;
var pageState = {
	activeFeedId: 0,
	activeFolderId: 0,
	activeEntryId: 0,
	numEntriesLoaded: 0
};

var initialPageState;

function EntryObj(id, status, type) {
	this.id = id;
	this.status = status;
	this.type = type;
}


// Create the navigation menu
$(document).ready(function(){
	$("#unsubscribe input[type='submit']").prop("disabled", true);
	loadFolders();
	$("#entryList").scroll(setActiveEntry);
	setUpdateTimer();
	setGetFeedsTimer();
	initialPageState = window.history.state;
	console.log(initialPageState);

});

// if the response to an ajax request is not json, then page is redirected due to session timeout
$(document).ajaxComplete(function(event, xhr, settings) {
	if (xhr.getResponseHeader("Content-type") == "text/html") {
		// redirected to login page
        document.open();
        document.write(xhr.responseText);
        document.close();
    }

});


function setUpdateTimer() {
	window.setInterval(updateEntries, 60000); // save updated entries after 60 secs 

}

function setGetFeedsTimer() {
	getFeedsTimerId = window.setInterval(loadFeeds, 30000); //every 30 secs, refresh feed list
}

// Load folders into navigation menu
function loadFolders() {
	$.getJSON("manage_feeds.php?getFolders", function(folders) {
		if (!folders) return;
		var content = "";
		for (var i = 0; i < folders.length; i++) {
			if (folders[i].name == "root" ) {
				rootId = activeFolderId = folders[i].id;
				content += "<li><ul id='Folder" + rootId + "'></ul></li>";
			}else {
				// Create a navigation entry for each folder 
				content += "<li id='Folder" + folders[i].id + "' class='folder' ><div onclick='setActiveFolder(" + folders[i].id + 
				",$(this).parent()); $(this).parent().toggleClass(\"collapsed\"); '>" + 
				"<span></span><img src='resources/folder_icon.png' ><span>" + folders[i].name + "</span></div><ul id='folder" + 
				folders[i].id + "'> </ul></li>";
			}
			
		}	
		$("#feedList").append(content);
		if (initialPageState != null) {
			setActiveFolder(initialPageState.activeFolderId, $("#Folder" + initialPageState.activeFolderId));
		}
		// Now load feeds
		loadFeeds();
	});

}

// Loads feeds from server and populates the navigation list
function loadFeeds() {
	$.getJSON("manage_feeds.php?getFeeds", function(feeds) {
		myFeeds = feeds;
		var i;
		var $feedList = $("#feedList");
		var allItemsUnreadCount = 0;
		var feedExists = false;
		var newFeed = false;
		for (i = 0; i < myFeeds.length; i++) { 
			var titleClass = "";
			var unreadCount = "";
			if (parseInt(myFeeds[i].numUnreadEntries)) {
				allItemsUnreadCount += parseInt(myFeeds[i].numUnreadEntries);
				if (parseInt(myFeeds[i].numUnreadEntries) < 1000) unreadCount= "(" +  myFeeds[i].numUnreadEntries + ")";
				else unreadCount = "(1000+)";
				titleClass = "class = 'unread'";
			}
			// Check if feed exists
			var $feed = $("#Feed" + myFeeds[i].id);	
			if ($feed.length) {
				feedExists = true;
				if (parseInt(myFeeds[i].numUnreadEntries)) {
					$feed.find("span[name='title']").addClass("unread");
					$unreadCountElm = $feed.find("span:last-child");
					$unreadCountElm.data("unreadCount", myFeeds[i].numUnreadEntries);
					$unreadCountElm.text(unreadCount);
				}
			} else {
				newFeed = true;
				var content = "<li id='Feed" + myFeeds[i].id + "' class='feed' onclick = 'setActiveFeed(" + i + ", $(this));'><img src = '" + 
					myFeeds[i].alternateLink + "/favicon.ico'></img> <span name='title'" + titleClass + "  >" + 
					myFeeds[i].title + " </span><span data-unread-count='" + myFeeds[i].numUnreadEntries + "' >" + unreadCount + "</span></li>";
				if (myFeeds[i].folder_id == rootId) $feedList.append(content); 
				else {
					$feedList.find("#folder" + myFeeds[i].folder_id).append(content);
				}
			}
		}
		
		
		$allItems = $("#allItems");
		$unreadCountElm = $allItems.find("span:last-child");
		$unreadCountElm.data("unreadCount", allItemsUnreadCount);
		//set unread count for All Items link
		if (allItemsUnreadCount) {
			$allItems.find("span.first-child").toggleClass("unread");
			if (allItemsUnreadCount < 1000) $unreadCountElm.text("(" + allItemsUnreadCount + ")");
			else $unreadCountElm.text("(1000+)");
		}

		// If no more new feeds were added, clear GetFeedsTimer
		if (!newFeed) window.clearInterval(getFeedsTimerId);
		// Set the first feed as active feed	
		if (!feedExists) { 
			if (initialPageState == null) $("#feedList li.feed").first().click();
			else {
				if (initialPageState.activeFeedId) $("#Feed" + initialPageState.activeFeedId).click();
				else $("#allItems").click();
			}
		}
	});
}

// Called when a feed link in nav is clicked 
function setActiveFeed(i, $elm) {
	if ($activeFeed != null) $activeFeed.toggleClass("highlighted");
	$activeFeed = $elm;
	$activeFeed.toggleClass("highlighted");
	lastLoadedEntryId = 0; //(Non-existant entry id)  Get entries in reverse order from DB
	$activeEntry = null;
	// Before loading new feed entries, send updates for previous feed entries
	updateEntries();
	var $entryList = $("#entryList");
	$entryList.empty();
	activeFeedIndex = i; // index into myFeeds
	if (i != -1) {
		// update page state
		pageState.activeFeedId = myFeeds[i].id;
		window.history.replaceState(pageState, "");
		setActiveFolder(myFeeds[i].folder_id, $("#Folder" + myFeeds[i].folder_id));
		$entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
		$("#unsubscribe input[type='submit']").prop("disabled", false);
	}else {
		// In all items, disable Unsubscribe
		$("#unsubscribe input[type='submit']").prop("disabled", true);
		pageState.activeFeedId = 0;
		pageState.activeFolderId = rootId;
		window.history.replaceState(pageState, "");
	}
	loadEntries();
}

// Called when a folder item is clicked
function setActiveFolder(id, $elm) {
	if ($activeFolder != null) $activeFolder.toggleClass("active");
	$activeFolder = $elm;
	$activeFolder.toggleClass("active");
	activeFolderId = id;
	pageState.activeFolderId = id;
	window.history.replaceState(pageState, "");
}

// Load a page of entries from DB for active feed
function loadEntries() {
	var $entryList = $("#entryList");

	var feedId = (activeFeedIndex != -1)? myFeeds[activeFeedIndex].id : 0;	
	//Remove section with id = more from entryList
	$("section[id='more']").remove();
	if ($activeEntry == null) { //if this is the first page, get unread count
		// Get num of unread entries for this feed
		$.getJSON("manage_feeds.php?getNumUnreadEntries&feedId=" + feedId, function(unreadCount) {
			if (unreadCount !== false) {
				// Set unread entry count for the active feed
				updateUnreadCount("set", unreadCount);
			}
		});
	}
	var pageSize = entryPageSize;
	if (initialPageState != null) pageSize = initialPageState.numEntriesLoaded + 1; //Add one to make sure the 'scroll down for more entries' does not appear
	$.getJSON("manage_feeds.php?getEntries&feedId=" + feedId + "&entryPageSize=" + pageSize + "&lastLoadedEntryId=" + lastLoadedEntryId,
	 function(entries) {
		if (!entries) {
			$entryList.append("<section id='last'> No more Entries </section>");
			return;
		}
		for (var i = 0; i < entries.length; i++) { 
			// filter entries before adding them
			var hidden = "";
			if (filter != "all") {
				hidden = "class = 'hidden'";
				if ((filter == entries[i].type || filter == entries[i].status) || (filter == "unread" && entries[i].status == 'new')) 
					hidden = "";
		
			}
			var content = "<section " + hidden + " id = 'Entry" + entries[i].id + "'><div><div><div class='title'><a target='_blank' href='" +
				 entries[i].alternateLink + "'>" + entries[i].title + "</a></div>";
			if (activeFeedIndex == -1) {
				// Show feed title instead of author
				content += "<div class='author'> from  <a href='#' onclick=\"$('#Feed" + entries[i].feed_id + "').click(); return false;\">" + 
					entries[i].feedTitle + "</a></div>";
			}else if (entries[i].authors != "") content += "<div class='author'>by " + entries[i].authors + "</div>";
			var updated = new Date(entries[i].updated * 1000); // convert unix timestamp into miliseconds
			var checked = (entries[i].status == "unread")? "checked" : "";
			var starred = (entries[i].type == "starred") ? "class='starred'" : "";
			content += "</div><div class='updated'>" + updated.toLocaleString() + "</div></div>";
			content += "<br /><div>" + entries[i].content + "</div>";
			content += "<br /><div class='toolbar'><input type='hidden' name='id' value='" + entries[i].id + 
				"' /><span " + starred + " onclick='setEntryStarred($(this))'></span><input type='hidden' name='type' value='" + 
				entries[i].type + "' />" + "<span> &nbsp;&nbsp; </span><input type='checkbox' name='status' value='" + entries[i].status + 
				"' onchange='setEntryStatus($(this));' " + checked + "  />" + 
				"<label for='status'> Keep unread</label></div></section>";
			$entryList.append(content);
		}
		if (entries.length) lastLoadedEntryId = entries[entries.length- 1].id;

		if (entries.length < pageSize) {
			// The last entry has been received
			$entryList.append("<section id='last'>  No more Entries  </section>");
		}else {
			$entryList.append("<section id='more'> Scroll down to view more entries </section>");

		}
		if ($activeEntry == null) {
			// This is the first page
			if (initialPageState == null) $activeEntry = $("#entryList section:first-of-type");
			else {
				$activeEntry = $("#" + initialPageState.activeEntryId);
				// scroll to active Entry
				var activeEntry = document.getElementById(initialPageState.activeEntryId);
				activeEntry.scrollIntoView(true);
			}
			$activeEntry.addClass("highlighted");
			// Initialize entry stated
			pageState.activeEntryId = $activeEntry.attr("id");
			pageState.numEntriesLoaded = entries.length;
		}else {
			pageState.numEntriesLoaded += entries.length;
		}
		initialPageState = null; // set to to null after it is used for the first time
		window.history.replaceState(pageState, "");
		console.log(pageState);
	});
}

// Highlights the current visible entry in the #entryList viewPort and sets current entryId
// called on scroll event
function setActiveEntry() {
	var $viewport = $(this);
	if ($activeEntry != null) {
		//Check if current entry is still on top of the viewPort
		// If active entry has been scrolled up, replace it with next entry
		if ($activeEntry.position().top < 0) {
			var $nextEntry = $activeEntry;
			do {// loook for a valid next entry
				$nextEntry = $nextEntry.next("section");
			}while ($nextEntry.length && $nextEntry.hasClass("hidden"));
			if ($nextEntry.length) { 
				if ($nextEntry.attr("id") == "more") {
					// Load new entries if we've reached the bottom of scroll area
					loadEntries();
				} else {
					$activeEntry.toggleClass("highlighted"); 
					// Also change status to read
					var $statusElm = $activeEntry.find(".toolbar > input[name='status']")
					if ($statusElm.val() == "new") {
						$statusElm.val("read");
						// decrement unread count for active feed
			    		updateUnreadCount("decrement");
						$activeEntry.addClass("updated");
					}
					$activeEntry = $nextEntry;
					$activeEntry.toggleClass("highlighted");
					pageState.activeEntryId = $activeEntry.attr("id");
					window.history.replaceState(pageState, "");
				}
			}
		}else { 
			var $prevEntry = $activeEntry;
			do {// loook for a valid prev entry
				$prevEntry = $prevEntry.prev("section");
			}while ($prevEntry.length && $prevEntry.hasClass("hidden"));
			// If previous entry's top is visible scroll up to previous entry
			if ($prevEntry.length && $prevEntry.position().top > 0) {
				// if it has been scrolled down, replace with prev entry
				$activeEntry.toggleClass("highlighted");
				$activeEntry = $prevEntry;
				$activeEntry.toggleClass("highlighted");
				pageState.activeEntryId = $activeEntry.attr("id");
				window.history.replaceState(pageState, "");
			}
		}
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

// Increments/Decrements/Sets unread count for active Feed element
function updateUnreadCount(option, value) {
	if (typeof(value) === "undefined") value = 0;
	value = parseInt(value);
	var $spanElm = $activeFeed.find("span:last-child");
	var count = parseInt($spanElm.data("unreadCount")); 		
	if (option == "decrement") {
		if (count) {
			count--;
			if (count) 
				$spanElm.text("(" + count + ")");
			else {
				$spanElm.text("");
				$spanElm.prev().removeClass("unread"); // remove unread style for title
			}
			$spanElm.data("unreadCount", count);
		}
	}else if (option == "increment") {
		if (!count) $spanElm.prev().addClass("unread"); // add unread style for title
		count ++;
		if (count < 1000) $spanElm.text("(" + count + ")");
		else $spanElm.text("(1000+)");
		$spanElm.data("unreadCount", count);
	}else if (option == "set") {
		if (value) {
			if (value < 1000) $spanElm.text("(" + value + ")");
			else $spanElm.text("(1000+)");
		}else { 
			$spanElm.text("");
		}
		if (value && !count) {
			$spanElm.prev().addClass("unread");
		}
		if (!value && count) {
			$spannElm.prev().removeclass("unread");
		}
		$spanElm.data("unreadCount", value);
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


// View filtered by starred, read, unread
function filterView() {
	if (filter == "all") {
		$("#entryList section").removeClass("hidden");
	}else {
		$("#entryList section").addClass("hidden");
		$("#entryList input[value='" + filter + "']").parent().parent().removeClass("hidden");
		if (filter == "unread") $("#entryList input[value='new']").parent().parent().removeClass("hidden");
		$("#entryList section#more").removeClass("hidden");
		$("#entryList section#last").removeClass("hidden");
	}

}


// Called when new folder icon is clicked
function createFolder() {
	// Ask for folder name 
	var name = window.prompt("Enter new folder name", "New Folder");
	if ((name != null) && (name != "") ) {
		// Add folder to DB and make it active
		$.getJSON("manage_feeds.php?createFolder&name=" + name, function (id) {
			if (!id) {
				alert ("Cannot create folder with the given name, please try again");
				
			}else {
				$("#feedList").append("<li class='folder' ><div onclick='setActiveFolder(" + id + ",$(this).parent());'>" + 
				"<span></span><img src='resources/folder_icon.png' ><span>" + name + "</span></div><ul id='folder" + id + "'> </ul></li>");
				$("#feedList > li:last-child > div").click();
			}
		});
	}
}
