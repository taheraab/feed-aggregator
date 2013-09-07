var $activeTab = null;
var $activeSection;
var myFolders = null;
var rootId;

$(document).ready(function() {
	var queryString = window.location.search.substring(1);
	if (queryString != "") {
		setActiveTab($("nav li[name='" + queryString + "']"), queryString);
	}else if (window.history.state) {
        setActiveTab($("nav li[name='" + window.history.state + "']"), window.history.state);
	}else setActiveTab($("nav li:first-child"), $("article section:first-child").attr("id"));
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

// Implement tabbed menu
function setActiveTab($tab, sectionId) {
	if (sectionId == "subscriptions" || sectionId == "folders") {
		loadFolders();
	}
	$section = $("#" + sectionId);
	if ($activeTab != null) {
		$activeTab.toggleClass("active");
		$activeSection.toggleClass("hidden");
	}
	$activeTab = $tab;
	$activeSection = $section;
	$activeTab.toggleClass("active");
	$activeSection.toggleClass("hidden");
    window.history.replaceState(sectionId, "");
}

// Load folders from server and set up Folders tab content 
function loadFolders() {
	$.getJSON("manage_feeds.php?getFolders", function(folders) {
		if (!folders) return;
		myFolders = folders;
		$folderList = $("#folderList");
		$folderList.empty();
		$actions = $("#actions");
		$actions.empty();
		var folderListContent = "";
		var actionsContent = "<option name='add' selected >Add To Folder...</option>";
		for (var i = 0; i < folders.length; i++) {
			if (folders[i].name != "root") {
				folderListContent += "<div id='Folder" + folders[i].id + "'> <input type='checkbox' form='foldersForm' name='folderIds[]' value='" + 
					folders[i].id + "' ></input><span class='folderName rename'>" + folders[i].name + 
					" </span><div class='folderName rename hidden'><input type='text' onchange=\"renameFolder(" + folders[i].id + ", this.value, " + 
					"$(this).parent().parent().find('span.folderName').text());\" value='" + folders[i].name + "' ></input></div><div>" + 
					"<button class='rename' type='button' onclick=\"$(this).parent().parent().find('.rename').toggleClass('hidden');\">Rename</button>" + 
					"<button class='rename hidden' type='button' onclick=\"$(this).parent().parent().find('.rename').toggleClass('hidden');\">" + 
					"Cancel</button></div><img onclick=\"deleteFolder(" + folders[i].id + ", $(this).parent().find('span.folderName').text());\"" + 
					" src='resources/delete_icon.png' class='delete'></img><span class='errMsg'></span></div>";
			}else rootId = folders[i].id;
			actionsContent += "<option name='" + folders[i].id + "' value='" + folders[i].id + "'>" + folders[i].name + "</option>";
		}
		actionsContent += "<option name='new' >New Folder...</option>";
		$folderList.append(folderListContent);
		$actions.append(actionsContent);

		loadFeeds();
	});

}


// Load feeds from server and set up subscriptions tab content
function loadFeeds() {
    $.getJSON("manage_feeds.php?getFeedsForSettings", function(feeds) {
        if (!feeds)  return;
      	$feedList = $("#feedList");
		$feedList.empty();
		var content = "";
		var currentFolderName = "";
		for (var i = 0; i < feeds.length; ++i) {
			content += "<div id='Feed" + feeds[i].id + "'> <input form='subscriptionsForm' type='checkbox' name='feedIds[]' value='" + feeds[i].id + 
				"'></input> <div class='feedName'><span>" + feeds[i].title + "</span><br /><span>(" + feeds[i].selfLink + ")</span>" + 
				"</div> <div><button type='button' onclick = \"unsubscribeFeed(" + feeds[i].id + ", '" + feeds[i].title + 
				"');\" >Unsubscribe</button></div>";
			if (feeds[i].folder_id == rootId) currentFolderName = "<span class='currentFolderName'>root</span>";
			else currentFolderName = "<span class='currentFolderName'>" + $("#Folder" + feeds[i].folder_id).find("span.folderName").text() + "</span>"; 
			content += "<div><select onchange=\"$selOption = $(this).find('option:selected'); changeFolder($selOption.attr('name'), " + feeds[i].id + 
				", $selOption.text());\"><option name='change' selected > Change Folder...</option>";
			var hidden;
			for (var j = 0; j < myFolders.length; j++) {
				if (feeds[i].folder_id == myFolders[j].id) hidden = "class = 'hidden' "; // hide current folder from list 
				else hidden = "";
				content += "<option " + hidden + "name='" + myFolders[j].id + "'>" + myFolders[j].name + "</option>"

			}
			content += "<option name='new'>New Folder...</option></select></div>" + currentFolderName + "<span class='errMsg'></span></div>";
			
		}
		
		$feedList.append(content);
	});

}


// Delete feed from DB and DOM
function unsubscribeFeed(id, title) {
	var $feed = $("#Feed" + id);
	$feed.find(".errMsg").text("");
	if (window.confirm("Are you sure you want to unsubscribe from " + title + "?")) {
		$.getJSON("manage_feeds.php?unsubscribeFeed&feedId=" + id, function (result) {
			if (!result) {
				$("#Feed" + id).find(".errMsg").text("Unsubscribe failed, please try again");
			}else {
				$("#Feed" + id).remove();
			}	
		});

	} 

}

//Unsubscribe from more than one feed, called before form is submitted
function unsubscribeFeeds() {
	var $selectedFeeds = $("#feedList input:checked").parent();
	var $errMsg = $("#subscriptions > .aggrMenu > span.errMsg");
	$errMsg.text("");
	if (!$selectedFeeds.length) {
		$errMsg.text("No feeds are selected, please try again");
		return;
	}
	// Get titles 
	var message = "Are you sure you want to subscribe from the following feeds?\n";
	$selectedFeeds.each(function(i) {
		message += $(this).find(".feedName > span:first-child").text() + "\n";
	});
	if (window.confirm(message)) {
		$form = $("#subscriptionsForm");
		$form.attr("action", $form.attr("action") + "?unsubscribeFeeds");
		$form.submit();
	}


}

// Delete folder from DB and DOM
function deleteFolder(id, name) {
	var $folder = $("#Folder" + id);
	$folder.find(".errMsg").text("");
	if (window.confirm("Are you sure you want to delete " + name + "?")) {
		$.getJSON("manage_feeds.php?deleteFolder&folderId=" + id, function (result) {
			if (!result) {
				$folder.find(".errMsg").text("Couldn't delete " + name + ", please try again");
			}else {
				$("#Folder" + id).remove();
			}	
		});

	} 

}


// Delete multiple folders
function deleteFolders() {
	var $selectedFolders = $("#folderList input:checked").parent();
	var $errMsg = $("#folders > .aggrMenu > span.errMsg");
	$errMsg.text("");
	if (!$selectedFolders.length) {
		$errMsg.text("No folders are selected, please try again");
		return;
	}
	// Get folder names 
	var message = "Are you sure you want to delete the following folders?\n";
	$selectedFolders.each(function(i) {
		message += $(this).find(".folderName").text() + "\n";
	});
	if (window.confirm(message)) {
		$form = $("#foldersForm");
		console.log($form.length);
		$form.attr("action", $form.attr("action") + "?deleteFolders");
		$form.submit();
	}

}


// Called when folder name changes
function renameFolder(id, newName, oldName) {
	var $folder = $("#Folder" + id);
	$folder.find(".errMsg").text("");
	$.getJSON("manage_feeds.php?renameFolder&folderId=" + id + "&newName=" + newName, function (result) {
		if (!result) {
			$folder.find(".errMsg").text("Couldn't rename folder, please try again");
			$folder.find("div.folderName > input").val(oldName);
		}else {
			$folder.find("span.folderName").text(newName);
			$folder.find("div.folderName > input").val(newName);
		}
		$folder.find(".rename").toggleClass("hidden");
	});

}

// Called when user changes folder for a feed
function changeFolder(newId, feedId, newName) {
	var $feed = $("#Feed" + feedId);
	$feed.find(".errMsg").text("");
	if (newId == "change") return;
	if (newId == "new") {
		var newName = window.prompt("Enter a folder name", "New Folder");
		if (newName != null && newName != "") {
			$.getJSON("manage_feeds.php?changeFolder&feedId=" + feedId + "&newName=" + newName, changeFolderCallback);	
		}else {
			$feed.find("option[name='change']").prop("selected", true);
		}
	}else { 
		$.getJSON("manage_feeds.php?changeFolder&feedId=" + feedId + "&newId=" + newId, changeFolderCallback);
	}


	function changeFolderCallback(result) {
		if (!result) {
			$feed.find(".errMsg").text("Couldn't change folder, please try again");
		}else {
			$feed.find("span.currentFolderName").text(newName);
			// Change select list to hide current Folder option 
			$feed.find("option.hidden").removeClass("hidden");
			if (newId == "new") {
				$feed.find("option:selected").before("<option class='hidden' name='" + result + "'>" + newName + "</option>");
			}else $feed.find("option:selected").addClass("hidden");
			$feed.find("option[name='change']").prop("selected",true);
		}
	}	
	
}

//Move multiple feeds to a selected folder
function moveFeedsToFolder() {
	var $selectedFeeds = $("#feedList input:checked").parent();
	var $errMsg = $("#subscriptions > div.aggrMenu > span.errMsg");
	$errMsg.text("");
	var $actions = $("#actions");
	if (!$selectedFeeds.length) {
		$errMsg.text("No feeds are selected, please try again");
		console.log($actions.find("option:selected").length);
		$actions.find("option[name='add']").prop("selected", true);
		return;
	}
	var $selOption = $actions.find("option:selected");
	if ($selOption.attr("name") == "new") {
		var newName = window.prompt("Enter a folder name", "New Folder");
		if (newName != null && newName != "") {
			$selOption.val(newName);
		}else {
			$actions.find("option[name='add']").prop("selected", true);
			return;
		}

	}
	$form = $("#subscriptionsForm");
	$form.attr("action", $form.attr("action") + "?moveFeedsToFolder");
	$form.submit();


}

