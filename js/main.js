var myFeeds=[];
// Create the navigation menu
$(document).ready(function(){
	createCList($("#subsList"));
	loadFeeds();
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
		for (feed in myFeeds) {

		}

	});

}
