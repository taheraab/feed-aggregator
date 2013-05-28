var myFeeds=[];
var myEntries = []; //entries corresponding to a selected feed
// Create the navigation menu
$(document).ready(function(){
	loadFeeds();
	createCList($("#subsList"));
});

//functions to create a collapsible list
function createCList($list) {
	$list.find("ul").each(function(){
		$(this).addClass("noListStyle");
		$(this).parent().prepend("<span onclick=\"toggleState(this);\" ></span> ");
		$(this).parent().addClass("collapsed");
	});
	
}

function toggleState(elm){
	console.log("Entered click event handler" + elm.toString());
	if($(elm).parent().hasClass("collapsed")) {
		$(elm).parent().removeClass("collapsed");
		$(elm).parent().addClass("expanded");
	}else {
		$(elm).parent().removeClass("expanded");
		$(elm).parent().addClass("collapsed");
	}
}

// Loads feeds from server and populates the navigation list
function loadFeeds() {
	$.getJSON("manage_feeds.php?getFeeds", function(feeds) {
		myFeeds = feeds;
		var i;
		var feedList = $("#feedList");
		for (i = 0; i < myFeeds.length; i++) {
			feedList.append("<li><img src = '" + myFeeds[i].alternateLink + "/favicon.ico' width='20px' height='20px'></img>" +
			" <a href = 'javascript:loadEntries(" + i + ");'>" + myFeeds[i].title + "</a></li>");
		}
	});

}

// Load entries for a given feed (index into myFeeds)
function loadEntries(i) {
	var entryList = $("#entryList");
	entryList.empty();
	entryList.append("<h3> <a href='" + myFeeds[i].alternateLink + "'>" + myFeeds[i].title + " >></a></h3><p>" + myFeeds[i].subtitle + "</p><hr>");
	$.getJSON("manage_feeds.php?getEntries&feedId=" + myFeeds[i].id, function(entries) {
		myEntries = entries;
		for (var i = 0; i < myEntries.length; i++) {
			entryList.append("<section><h4><a href='" + myEntries[i].alternateLink + "'>" + myEntries[i].title + "</a></h4><p>By " +
				myEntries[i].authors + "</p><p>" + myEntries[i].updated + "</p><br /><p>" + myEntries[i].content + "</p></section>");
		}

	});
}


