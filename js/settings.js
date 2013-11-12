var $activeTab = null;
var $activeSection;
var myFolders = null;
var rootId;

$(document).ready(function() {
    $("#settingsNav a").on("show.bs.tab", function(e) {
	  var type = $(this).attr("href").substr(1);
      if (type == "subscriptions" || type == "folders") {
		loadFolders();
	  }
      window.history.replaceState(type, "");
    });
   
   var queryString = window.location.search.substr(1);
	if (queryString != "") {
		$("#settingsNav a[href='#" + queryString + "']").tab("show");
	}else if (window.history.state) {
        $("#settingsNav a[href='#" + window.history.state + "']").tab("show");
	}
    else $("#settingsNav a").first().tab("show");
       
    
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
				folderListContent += "<div class='row folder' id='Folder" + folders[i].id + "'><div class='col-md-3'>" + 
                  "<label class='checkbox-inline rename longText'><input type='checkbox' name='folderIds[]' form='foldersForm' value='" + 
					folders[i].id + "' /><span class='folderName'>" + folders[i].name + 
                    "</span></label><input type='text' class='form-control input-sm rename hidden' " + 
                    "onchange=\"renameFolder(" + folders[i].id + ", this.value, " + 
					"$(this).parent().find('.folderName').text());\" value='" + folders[i].name + "' ></input></div><div class='col-md-1'>" + 
					"<button class='rename btn btn-default btn-sm' type='button' " + 
                    "onclick=\"$(this).parent().parent().find('.rename').toggleClass('hidden');\">Rename</button>" + 
					"<button class='btn btn-default btn-sm rename hidden' type='button' " + 
                    "onclick=\"$(this).parent().parent().find('.rename').toggleClass('hidden');\">" + 
					"Cancel</button></div><div class='col-md-1'><a href='#' class='btn' onclick=\"deleteFolder(" + folders[i].id + 
                    ", $(this).parent().parent().find('.folderName').text());\"><span class='glyphicon glyphicon-trash'></span></a></div>" + 
					"<div class='col-md-7'><p class='form-control-static text-danger'></p></div></div>";
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
			content += "<div class='row feed' id='Feed" + feeds[i].id + "'><div class='col-md-4'><label class='checkbox-inline longText'>" + 
              "<input form='subscriptionsForm' type='checkbox' name='feedIds[]' value='" + feeds[i].id + 
			  "' /><span class='feedName'> " + feeds[i].title + "</span><br />(" + feeds[i].selfLink + ")" + 
			  "</label></div> <div class='col-md-2'><button type='button' class='btn btn-default btn-sm' onclick = 'unsubscribeFeed(" + 
              feeds[i].id + ", \"" + feeds[i].title + "\");' >Unsubscribe</button></div>";
			if (feeds[i].folder_id == rootId) currentFolderName = "root";
			else currentFolderName = $("#Folder" + feeds[i].folder_id).find("span.folderName").text(); 
			content += "<div class='col-md-2'><select class='form-control input-sm' onchange=\"$selOption = $(this).find('option:selected');" +
              " changeFolder($selOption.attr('name'), " + feeds[i].id + ", $selOption.text());\"><option name='change' selected > Change Folder...</option>";
			var hidden;
			for (var j = 0; j < myFolders.length; j++) {
				if (feeds[i].folder_id == myFolders[j].id) hidden = "class = 'hidden' "; // hide current folder from list 
				else hidden = "";
				content += "<option " + hidden + "name='" + myFolders[j].id + "'>" + myFolders[j].name + "</option>"

			}
			content += "<option name='new'>New Folder...</option></select></div><div class='col-md-2'>" + 
              "<p class='currentFolderName form-control-static longText'>" + 
              currentFolderName + "</p></div><div class='col-md-2'><p class='form-control-static text-danger'></p></div></div>";
			
		}
		
		$feedList.append(content);
	});

}


// Delete feed from DB and DOM
function unsubscribeFeed(id, title) {
	var $feed = $("#Feed" + id);
	var $errMsg = $feed.find(".text-danger");
    $errMsg.text("");
    $confirmDialog = $("#confirmDialog");
    showConfirmDialog("Unsubscribe", "Are you sure you want to unsubscribe from <strong><em>" + title + "</strong></em>?");
    $confirmDialog.on("hidden.bs.modal", function() {
      if ($confirmDialog.find(".returnVal").text() == "yes") {
		$.getJSON("manage_feeds.php?unsubscribeFeed&feedId=" + id, function (result) {
			if (!result) {
				$errMsg.text("Unsubscribe failed, please try again");
			}else {
				$feed.remove();
			}	
		});
      }
    });
   
}


//Unsubscribe from more than one feed, called before form is submitted
function unsubscribeFeeds() {
	var $selectedFeeds = $("#feedList input:checked").parent().parent().parent();
	var $errMsg = $("#subscriptions  .panel-heading  .text-danger");
	$errMsg.text("");
	if (!$selectedFeeds.length) {
		$errMsg.text("No feeds are selected, please try again");
		return;
	}
	// Get titles 
	var message = "<p><strong>Are you sure you want to unsubscribe from the following feeds?</strong></p>";
	$selectedFeeds.each(function(i) {
		message += $(this).find(".feedName").text() + "<br />";
	});
    $confirmDialog = $("#confirmDialog");
    showConfirmDialog("Unsubscribe", message);
    $confirmDialog.on("hidden.bs.modal", function() {
      if ($confirmDialog.find(".returnVal").text() == "yes") {
		$form = $("#subscriptionsForm");
		$form.attr("action", "manage_feeds.php?unsubscribeFeeds");
		$form.submit();
	  }
    });

}

// Delete folder from DB and DOM
function deleteFolder(id, name) {
	var $folder = $("#Folder" + id);
	var $errMsg = $folder.find(".text-danger");
    $errMsg.text("");
	$confirmDialog = $("#confirmDialog");
    showConfirmDialog("Delete Folder", "Are you sure you want to delete <strong><em>" + name + "</strong></em>?");
    $confirmDialog.on("hidden.bs.modal", function() {
      if ($confirmDialog.find(".returnVal").text() == "yes") {
		$.getJSON("manage_feeds.php?deleteFolder&folderId=" + id, function (result) {
			if (!result) {
				$errMsg.text("Couldn't delete " + name + ", please try again");
			}else {
				$folder.remove();
			}	
		});
      }
	});

}


// Delete multiple folders
function deleteFolders() {
	var $selectedFolders = $("#folderList input:checked").parent().parent().parent();
	var $errMsg = $("#folders .panel-heading text-danger");
	$errMsg.text("");
	if (!$selectedFolders.length) {
		$errMsg.text("No folders are selected, please try again");
		return;
	}
	// Get folder names 
	var message = "<p><strong>Are you sure you want to delete the following folders?</strong></p>";
	$selectedFolders.each(function(i) {
		message += $(this).find(".folderName").text() + "<br />";
	});
	$confirmDialog = $("#confirmDialog");
    showConfirmDialog("Delete Folders", message);
    $confirmDialog.on("hidden.bs.modal", function() {
      if ($confirmDialog.find(".returnVal").text() == "yes") {
		$form = $("#foldersForm");
		$form.attr("action", "manage_feeds.php?deleteFolders");
		$form.submit();
	  }
    });

}


// Called when folder name changes
function renameFolder(id, newName, oldName) {
	var $folder = $("#Folder" + id);
	var $errMsg = $folder.find(".text-danger");
    $errMsg.text("");
	$.getJSON("manage_feeds.php?renameFolder&folderId=" + id + "&newName=" + newName, function (result) {
		if (!result) {
			$errMsg.text("Couldn't rename folder, please try again");
			$folder.find("input.rename").val(oldName);
		}else {
			$folder.find(".folderName").text(newName);
			$folder.find("input.rename").val(newName);
		}
		$folder.find(".rename").toggleClass("hidden");
	});

}
// Called when Add button is selected in the Add Folder Dialog 
function createFolder() {
    $addFolderDialog = $("#addFolderDialog");
    $addFolderDialog.find(".returnVal").text("add");
    $addFolderDialog.modal("hide");
    
}

// Called when user changes folder for a feed
function changeFolder(newId, feedId, newName) {
	var $feed = $("#Feed" + feedId);
	$feed.find(".text-danger").text("");
	if (newId == "change") return;
	if (newId == "new") {
        var $addFolderDialog = $("#addFolderDialog");
        showAddFolderDialog();
        $addFolderDialog.on("hidden.bs.modal", function() {
          var returnVal = $addFolderDialog.find(".returnVal").text();
		  newName = $addFolderDialog.find(".folderName").val();
		  if (returnVal == "add" && newName != "") {
		   	$.getJSON("manage_feeds.php?changeFolder&feedId=" + feedId + "&newName=" + newName, changeFolderCallback);	
		  }else {
			$feed.find("option[name='change']").prop("selected", true);
		  }
        });  
	}else { 
		$.getJSON("manage_feeds.php?changeFolder&feedId=" + feedId + "&newId=" + newId, changeFolderCallback);
	}


	function changeFolderCallback(id) {
		if (!id) {
			$feed.find(".text-danger").text("Couldn't change folder, please try again");
			$feed.find("option[name='change']").prop("selected",true);
		}else {
			$feed.find(".currentFolderName").text(newName);
			// Change select list to hide current Folder option 
			$feed.find("option.hidden").removeClass("hidden");
			if (newId == "new") {
                // Add new folder to all folder dropdowns
                var newOption = "<option name='" + id + "'>" + newName + "</option>";
				$("#feedList").find("option[name='new']").before(newOption);
                $actions.find("option[name='new']").before(newOption);
                $feed.find("option[name=" + id + "]").addClass("hidden");
			}else $feed.find("option:selected").addClass("hidden");
			$feed.find("option[name='change']").prop("selected",true);
		}
	}	
	
}

//Move multiple feeds to a selected folder
function moveFeedsToFolder() {
	var $selectedFeeds = $("#feedList input:checked").parent().parent().parent();
	var $errMsg = $("#subscriptions .panel-heading .text-danger");
	var $form = $("#subscriptionsForm");
	$form.attr("action", "manage_feeds.php?moveFeedsToFolder");
	$errMsg.text("");
	var $actions = $("#actions");
	if (!$selectedFeeds.length) {
		$errMsg.text("No feeds are selected, please try again");
		$actions.find("option[name='add']").prop("selected", true);
		return;
	}
	var $selOption = $actions.find("option:selected");
	if ($selOption.attr("name") == "new") {
	    var $addFolderDialog = $("#addFolderDialog");
        showAddFolderDialog();
        $addFolderDialog.on("hidden.bs.modal", function() {
          var returnVal = $addFolderDialog.find(".returnVal").text();
		  var newName = $addFolderDialog.find(".folderName").val();
		  if (returnVal == "add" && newName != "") {
			$selOption.val(newName);
        	$form.submit();
 	      }else {
			$actions.find("option[name='add']").prop("selected", true);
			return;
		  }
        });

	}else {
	    $form.submit();
    }

}

