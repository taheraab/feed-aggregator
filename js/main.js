var myFeeds = [];
var activeFeedId = 0;
var activeEntryId = 0;
var $activeEntry = null; // DOM object representing active entry
var $activeFeed = null; // DOM object representing active feed
var $activeFolder = null;
var activeFeedIndex = 0;
var entryPageSize = 20;
var lastLoadedEntryId = 0;
var filter = "all";
var activeFolderId = 0;
var rootId;

function EntryObj(id, status, type) {
	this.id = id;
	this.status = status;
	this.type = type;
}


// Create the navigation menu
$(document).ready(function(){
	loadFolders();
	$("#entryList").scroll(setActiveEntry);
	setUpdateTimer();
});

function setUpdateTimer() {
	window.setTimeout(updateEntries(), 60000); // save updated entries after 60 secs 

}

// Load folders into navigation menu
function loadFolders() {
	$.getJSON("manage_feeds.php?getFolders", function(folders) {
		if (!folders) return;
		var content = "";
		for (var i = 0; i < folders.length; i++) {
			if (folders[i].name == "root" ) {
				rootId = folders[i].id;
				content += "<li><ul id='root" + rootId + "'></ul></li>";
			}else {
				// Create a navigation entry for each folder 
				content += "<li class='folder' ><div onclick='setActiveFolder(" + folders[i].id + ",$(this).parent());'>" + 
				"<span></span><img src='resources/folder_icon.png' ><span>" + folders[i].name + "</span></div><ul id='folder" + 
				folders[i].id + "'> </ul></li>";
			}
			
		}	
		$("#feedList").append(content);
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
		for (i = 0; i < myFeeds.length; i++) { 
			var titleClass = "";
			var unreadCount = "";
			if (parseInt(myFeeds[i].numUnreadEntries)) {
				allItemsUnreadCount += parseInt(myFeeds[i].numUnreadEntries);
				unreadCount= "(" +  myFeeds[i].numUnreadEntries + ")";
				titleClass = "class = 'unread'";
			}
		
			var content = "<li class='feed' onclick = 'setActiveFeed(" + i + ", $(this));'><img src = '" + 
				myFeeds[i].alternateLink + "/favicon.ico'></img> <span id='feed" + myFeeds[i].id + "' " + titleClass + "  >" + 
				myFeeds[i].title + " </span><span>" + unreadCount + "</span></li>";
			if (myFeeds[i].folder_id == rootId) $feedList.append(content); 
			else {
				$feedList.find("#folder" + myFeeds[i].folder_id).append(content);
			}
		}
		//set unread count for All Items link
		if (allItemsUnreadCount) {
			$allItems = $("#allItems");
			$allItems.find("span.first-child").toggleClass("unread");
			$allItems.find("span:last-child").text("(" + allItemsUnreadCount + ")");
		}

		// Set the first feed as active feed	
		$("#feedList li.feed").first().click();
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
		activeFolderId = myFeeds[i].folder_id;
		$entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
		$("#unsubscribe input[type='submit']").prop("disabled", false);
	}else {
		// In all items, disable Unsubscribe
		$("#unsubscribe input[type='submit']").prop("disabled", true);
	}
	loadEntries();
}

// Called when a folder item is clicked
function setActiveFolder(id, $elm) {
	if ($activeFolder != null) $activeFolder.toggleClass("active");
	$activeFolder = $elm;
	$activeFolder.toggleClass("active");
	activeFolderId = id;
	$elm.toggleClass("collapsed");
}

// Load a page of entries from DB for active feed
function loadEntries() {
	var $entryList = $("#entryList");

	var feedId = (activeFeedIndex != -1)? myFeeds[activeFeedIndex].id : 0;	
	//Remove section with id = more from entryList
	$("section[id='more']").remove();
	$.getJSON("manage_feeds.php?getEntries&feedId=" + feedId + "&entryPageSize=" + entryPageSize + "&lastLoadedEntryId=" + lastLoadedEntryId,
	 function(entries) {
		if (!entries) {
			$entryList.append("<section id='last'> No more Entries </section>");
			return;
		}
		var unreadCount = 0;
		for (var i = 0; i < entries.length; i++) { 
			// filter entries before adding them
			var hidden = "";
			if (filter != "all") {
				hidden = "class = 'hidden'";
				if ((filter == entries[i].type || filter == entries[i].status) || (filter == "unread" && entries[i].status == 'new')) 
					hidden = "";
		
			}
			var content = "<section " + hidden + " id = 'Entry" + entries[i].id + "'><div><div><div class='title'><a href='" +
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
			if (entries[i].status == "unread" || entries[i].status == "new") ++unreadCount;
		}
		lastLoadedEntryId = entries[entries.length- 1].id;
		if (entries.length < entryPageSize) {
			// The last entry has been received
			$entryList.append("<section id='last'>  No more Entries  </section>");
		}else {
			$entryList.append("<section id='more'> Scroll down to view more entries </section>");

		}
		if ($activeEntry == null) {
			// This is the first page
			$activeEntry = $("#entryList section:first-of-type");
			$activeEntry.addClass("highlighted");
			// Set unread entry count for the active feed
			updateUnreadCount("set", unreadCount);
		}else {
			// Increment unread count
			updateUnreadCount("increment", unreadCount);
		}

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
			    		updateUnreadCount("decrement", 1);
						$activeEntry.addClass("updated");
					}
					$activeEntry = $nextEntry;
					$activeEntry.toggleClass("highlighted");
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
		updateUnreadCount("increment", 1);
	}else {
		$elm.val("read");
		updateUnreadCount("decrement", 1);
	}
	$elm.parent().parent().addClass("updated");
}

// Increments/Decrements/Sets unread count for active Feed element
function updateUnreadCount(option, step) {
	var $spanElm = $activeFeed.find("span:last-child");
	var countString = $spanElm.text(); 		
	if (countString) {
		var count = /\((\d+)\)/.exec(countString);
		var c = parseInt(count[1]);
		if(c) {
			if (option == "decrement") {
				c -= step;
				if (c) 
					$spanElm.text("(" + c + ")");
				else {
					$spanElm.text("");
					$spanElm.prev().toggleClass("unread"); // remove unread style for the title
				}
			}else if (option == "increment") {
				c += step;
				$spanElm.text("(" + c + ")");
			}else if (option == "set") {
				if (step) $spanElm.text("(" + step + ")");
			}
		}
	}else if (option == "increment" || option == "set")  {
		if (step) {
			$spanElm.text("(" + step + ")");
			$spanElm.prev().toggleClass("unread");
		}	
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

//set Folder Id to active folder Id
function setFolderId($form) {
	var folderId;
	if (activeFolderId)	folderId = activeFolderId;
	else folderId = rootId;
	$form.find("input[name='folderId']").val(folderId);
	return true;
}

// Called when new folder icon is clicked
function createFolder() {
	// Ask for folder name 
	var name = window.prompt("Enter new folder name", "New Folder");
	if ((name != null) && (name != "") ) {
		// Add folder to DB and make it active
		$.getJSON("manage_feeds.php?createFolder&name=" + name, function (id) {
			console.log(id);
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
