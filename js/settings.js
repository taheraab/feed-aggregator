var $activeTab = null;
var $activeSection;
var myFolders = null;

$(document).ready(function() {
	setActiveTab($("nav li:first-child"), $("article section:first-child").attr("id"));
	loadFolders();
}); 

function setActiveTab($tab, sectionId) {
	if (sectionId == "subscriptions") {
		//loadFolders();
		//loadFeeds();
	}else if (sectionId == "folders") {
		//loadFolders();

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
}

function loadFolders() {
	$.getJSON("manage_feeds.php?getFolders", function(folders) {
		if (!folders) return;
		myFolders = folders;
		$folderList = $("#folderList");
		$folderList.empty();
		$actions = $("#actions");
		$actions.empty();
		var folderListContent = "";
		var actionsContent = "<option selected>Add To Folder...</option>";
		for (var i = 0; i < folders.length; i++) {
			folderListContent += "<div id='Folder" + folders[i].id + "'> <input type='checkbox' /><span id='folderName'>" + folders[i].name + " </span> " +
				"<div><button type='button'>Rename</button></div><img src='resources/delete_icon.png' class='delete'/></div>"
			actionsContent += "<option name='" + folders[i].id + "'>" + folders[i].name + "</option>";
		}
		actionsContent += "<option name='new'>New Folder...</option>";
		$folderList.append(folderListContent);
		$actions.append(actionsContent);

		loadFeeds();
	});

}
function loadFeeds() {
    $.getJSON("manage_feeds.php?getFeedsForSettings", function(feeds) {
        if (!feeds)  return;
      	$feedList = $("#feedList");
		$feedList.empty();
		var content = "";
		var currentFolderName = "";
		for (var i = 0; i < feeds.length; ++i) {
			content += "<div id='Feed" + feeds[i].id + "'> <input type='checkbox'></input> <div id='feedName'><span>" + feeds[i].title + "</span>";
			if (feeds[i].selfLink.length) 
				content += "<br /><span>(" + feeds[i].selfLink + ")</span>";
			content += "</div> <div><button type='button'>Unsubscribe</button></div>";
			if (feeds[i].folder_id) {
				currentFolderName = "<span>" + $("#Folder" + feeds[i].folder_id).find("#folderName").text() + "</span>"; 
				content += "<div><select><option selected > Change Folder...</option>";
			}else content += "<div><select><option selected > Add to Folder...</option>";
			for (var j = 0; j < myFolders.length; j++) {
				content += "<option name='" + myFolders[j].id + "'>" + myFolders[j].name + "</option>"

			}
			content += "<option name='new'>New Folder...</option></select></div>" + currentFolderName + "</div>";
			
		}
		
		$feedList.append(content);
	});

}
