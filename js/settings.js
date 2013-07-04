var $activeTab = null;
var $activeSection;
var myFolders = null;

$(document).ready(function() {
	setActiveTab($("nav li:first-child"), $("article section:first-child").attr("id"));

}); 

function setActiveTab($tab, sectionId) {
	if (sectionId == "subscriptions") {
		loadFolders();
		loadFeeds();
	}else if (sectionId == "folders") {
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
}

function loadFolders() {
	$.getJSON("manage_feeds.php?getFolders", function(folders) {
		if ($folders) return;
		myFolders = folders;
		$folderList = $("#folderList");
		$folderList.empty();
		$actions = $("#actions");
		$actions.empty();
		var folderListContent = "";
		var actionsContent = "<option selected>Add To Folder</option>";
		for (var i = 0; i < folders.length; i++) {
			folderListContent += "<div id='Folder" + folders[i].id + "'> <span>" + folders[i].name + " </span>&nbsp; " +
				"<button type='button'>Rename</button>&nbsp;<img src='resources/delete_icon.png' width='20px' height='20px'/></div>"
			actionsContent += "<option name='" + folders[i].id + "'>" + folders[i].name + "</option>";
		}
		actionsContent += "<option name='new'>New Folder</option>";
		$folderList.append(folderListContent);
		$actions.append(actionsContent);
	});

}
function loadFeeds() {
    $.getJSON("manage_feeds.php?getFeedsForSettings", function(feeds) {
        if (!feeds)  return;
      	$feedList = $("#feedList");
		$feedList.empty();
		var content = "";
		for (var i = 0; i < feeds.length; ++i) {
			content += "<div id='Feed" + feeds[i].id + "'> <input type='checkbox'></input> <div><span>" + feeds[i].title + 
				"</span><br /><span>(" + feeds[i].selfLink + ")</span></div> <button type='button'>Unsubscribe</button>";
			if (feeds[i].folder_id) {
				content += "<span>" + feeds[i].folder_id + "</span> &nbsp;"
				content += "<select><option selected > Change Folder...</option>";
			}else content += "<select><option selected > Add to Folder...</option>";
			for (var j = 0; j < myFolders.length; j++) {
				content += "<option name='" + folders[i].id + "'>" + folders[i].name + "</option>"

			}
			content += "<option name='new'>New Folder</option></select>";
			
		}
		
		$feedList.append(content);
	});

}
