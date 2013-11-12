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
				content += "<li id='Folder" + rootId + "' class='hidden'></li>";
			}else {
				// Create a navigation entry for each folder 
                content += "<li id='Folder" + folders[i].id + "' class='list-group-item folder' onclick='setActiveFolder(" + folders[i].id +
                ", $(this)); toggleFolderState($(this));' ><span class='glyphicon glyphicon-folder-close'></span>" +
                "&nbsp;&nbsp;<span class='content'>" + folders[i].name + "</span><ul class='list-group hidden'></ul></li>";
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
			var unreadCount = "";
			if (parseInt(myFeeds[i].numUnreadEntries)) {
				allItemsUnreadCount += parseInt(myFeeds[i].numUnreadEntries);
				if (parseInt(myFeeds[i].numUnreadEntries) < 1000) unreadCount= myFeeds[i].numUnreadEntries;
				else unreadCount = "1000+";
			}
			// Check if feed exists
			var $feed = $("#Feed" + myFeeds[i].id);	
			if ($feed.length) {
				feedExists = true;
				if (parseInt(myFeeds[i].numUnreadEntries)) {
					$unreadCountElm = $feed.find("span.badge");
					$unreadCountElm.data("unreadCount", myFeeds[i].numUnreadEntries);
					$unreadCountElm.text(unreadCount);
				}
			} else {
                var matches = myFeeds[i].alternateLink.split("/");
				var hostname = matches[0] + "//" + matches[2];
                newFeed = true;
				var content = "<li id='Feed" + myFeeds[i].id + 
                  "' class='list-group-item feed' onclick = 'setActiveFeed(" + i + ", $(this)); event.stopPropagation();'>" + 
                  "<span class='badge' data-unread-count='" + myFeeds[i].numUnreadEntries + "'>" + unreadCount + "</span><img src='" + 
				hostname + "/favicon.ico' width='15' height='15'></img>&nbsp;&nbsp;<span class='content'>" + myFeeds[i].title + "</span></li>";
				if (myFeeds[i].folder_id == rootId) $feedList.append(content); 
				else {
					$feedList.find("#Folder" + myFeeds[i].folder_id + " > ul").append(content);
				}
			}
		}
		
		
		$allItems = $("#allItems");
		$unreadCountElm = $allItems.find("span.badge");
		$unreadCountElm.data("unreadCount", allItemsUnreadCount);
		//set unread count for All Items link
		if (allItemsUnreadCount) {
			if (allItemsUnreadCount < 1000) $unreadCountElm.text(allItemsUnreadCount);
			else $unreadCountElm.text("1000+");
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
		$entryList.append("<h4> <a target='_blank' href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + "</a></h4><p>" + myFeeds[i].subtitle + "</p>");
	}else {
		pageState.activeFeedId = 0;
		pageState.activeFolderId = rootId;
		window.history.replaceState(pageState, "");
	}
	loadEntries();
}

// Called when a folder item is clicked
function setActiveFolder(id, $elm) {
	if ($activeFolder != null) $activeFolder.toggleClass("highlighted");
	$activeFolder = $elm;
	$activeFolder.toggleClass("highlighted");
	activeFolderId = id;
	pageState.activeFolderId = id;
	window.history.replaceState(pageState, "");
}

// Collapse or Expand folder 
function toggleFolderState($elm) {
    $icon = $elm.find("span.glyphicon");
    $list = $elm.find("ul");
    $icon.toggleClass("glyphicon-folder-close");
    $icon.toggleClass("glyphicon-folder-open");
    $list.toggleClass("hidden");
}

// Load a page of entries from DB for active feed
function loadEntries() {
	var $entryList = $("#entryList");

	var feedId = (activeFeedIndex != -1)? myFeeds[activeFeedIndex].id : 0;	
	//Remove section with id = more from entryList
	$("div.panel[id='more']").remove();
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
			$entryList.append("<div class='panel panel-default' id='last'><div class='panel-body'><p class='text-center'> No more Entries </p></div></div>");
			return;
		}
		for (var i = 0; i < entries.length; i++) { 
			// filter entries before adding them
			var hidden = "";
			if (filter != "all") {
				hidden = "hidden";
				if ((filter == entries[i].type || filter == entries[i].status) || (filter == "unread" && entries[i].status == 'new')) 
					hidden = "";
		
			}
			var updated = new Date(entries[i].updated * 1000); // convert unix timestamp into miliseconds
			var content = "<div class='panel panel-default " + hidden + "' id='Entry" + entries[i].id + 
              "'><div class='panel-body'><small class='pull-right'>" + updated.toLocaleString() + 
              "</small><div class='title'><a target='_blank' href='" + entries[i].alternateLink + "'>" + entries[i].title + "</a></div><div>";
			if (activeFeedIndex == -1) {
				// Show feed title instead of author
				content += "<small> from  <a href='#' onclick=\"$('#Feed" + entries[i].feed_id + "').click(); return false;\">" + 
					entries[i].feedTitle + "</a></small>";
			}else if (entries[i].authors != "") content += "<small>by " + entries[i].authors + "</small>";
			var checked = (entries[i].status == "unread")? "checked" : "";
			var starred = (entries[i].type == "starred") ? "glyphicon-star" : "glyphicon-star-empty";
			content += "</div><br /><div>" + entries[i].content + "</div></div>";
			content += "<div class='panel-heading'><input type='hidden' name='id' value='" + entries[i].id + 
				"' /><a href='#' onclick='setEntryStarred($(this)); return false;'><span class='glyphicon " + starred + 
                "'></span></a><input type='hidden' name='type' value='" + 
				entries[i].type + "' />" + "&nbsp;&nbsp; <label class='checkbox-inline'><input type='checkbox' name='status' value='" + 
                entries[i].status + "' onchange='setEntryStatus($(this));' " + checked + "  />Keep unread</label></div></div>"; 
			
			$entryList.append(content);
		}
		if (entries.length) lastLoadedEntryId = entries[entries.length- 1].id;

		if (entries.length < pageSize) {
			// The last entry has been received
			$entryList.append("<div class='panel panel-default' id='last'><div class='panel-body'><p class='text-center'> No more Entries</p> </div></div>");
		}else {
			$entryList.append("<div class='panel panel-default' id='more'><div class='panel-body'><p class='text-center'> Scroll down to view more entries</p> </div></div>");

		}
		if ($activeEntry == null) {
			// This is the first page
			if (initialPageState == null) $activeEntry = $("#entryList div.panel").first();
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
				$nextEntry = $nextEntry.next("div.panel");
			}while ($nextEntry.length && $nextEntry.hasClass("hidden"));
			if ($nextEntry.length) { 
				if ($nextEntry.attr("id") == "more") {
					// Load new entries if we've reached the bottom of scroll area
					loadEntries();
				} else {
					$activeEntry.toggleClass("highlighted"); 
					// Also change status to read
					var $statusElm = $activeEntry.find(".panel-heading input[name='status']")
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
				$prevEntry = $prevEntry.prev("div.panel");
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
    var $icon = $elm.find("span.glyphicon");
	$icon.toggleClass("glyphicon-star");
    $icon.toggleClass("glyphicon-star-empty");
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
	$elm.parent().parent().parent().addClass("updated");
}

// Increments/Decrements/Sets unread count for active Feed element
function updateUnreadCount(option, value) {
	if (typeof(value) === "undefined") value = 0;
	value = parseInt(value);
	var $spanElm = $activeFeed.find("span.badge");
	var count = parseInt($spanElm.data("unreadCount")); 		
    if (option == "decrement") {
		if (count) {
			count--;
			if (count) 
				$spanElm.text(count);
			else {
				$spanElm.text("");
			}
			$spanElm.data("unreadCount", count);
		}
	}else if (option == "increment") {
		count ++;
		if (count < 1000) $spanElm.text(count);
		else $spanElm.text("1000+");
		$spanElm.data("unreadCount", count);
	}else if (option == "set") {
		if (value) {
			if (value < 1000) $spanElm.text(value);
			else $spanElm.text("1000+");
		}else { 
			$spanElm.text("");
		}
		$spanElm.data("unreadCount", value);
	}
		
}

// Send update entries request to server
function updateEntries() {
	$updatedEntryList = $("#entryList div.updated");
	var entriesToUpdate = new Array();
	$updatedEntryList.each(function() {
		var entry = new EntryObj($(this).find(".panel-heading > input[name='id']").val(), $(this).find(".panel-heading input[name='status']").val(),
						$(this).find(".panel-heading > input[name='type']").val());
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
		$("#entryList div.panel").removeClass("hidden");
	}else {
		$("#entryList div.panel").addClass("hidden");
        if (filter == "starred") $("#entryList input[value='starred']").parent().parent().removeClass("hidden");
		else $("#entryList input[value='" + filter + "']").parent().parent().parent().removeClass("hidden");
		if (filter == "unread") $("#entryList input[value='new']").parent().parent().parent().removeClass("hidden");
		$("#entryList #more").removeClass("hidden");
		$("#entryList #last").removeClass("hidden");
	}

}


// Called when new folder dialog is submitted
function createFolder($dialog) {
    var $errMsg = $("#addFolderDialog .text-danger");
    var name = $("#addFolderDialog .folderName").val(); 
    if (name != "") {
      // Add folder to DB and make it active
      $.getJSON("manage_feeds.php?createFolder&name=" + name, function (id) {
		if (!id) {
			$errMsg.text("Cannot create folder with the given name, please try again");
			
		}else {
	        var content = "<li id='Folder" + id + "' class='list-group-item folder' onclick='setActiveFolder(" + id +
               ", $(this)); toggleFolderState($(this));' ><span class='glyphicon glyphicon-folder-close'></span>" +
               "&nbsp;&nbsp;<span class='content'>" + name + "</span><ul class='list-group hidden'></ul></li>";
			$("#feedList > li.folder").last().after(content);
		    setActiveFolder(id, $("#Folder" + id));
            $("#addFolderDialog").modal("hide");
		}
	  });
    }
}

// Initialize and show confirm dialog
function showAddSubsDialog() {
  var $addSubsDialog = $("#addSubsDialog");
  $addSubsDialog.find(".subsUrl").val("");
  $addSubsDialog.modal("show");

}

