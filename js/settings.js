var $activeTab = null;
var $activeSection;

$(document).ready(function() {
	setActiveTab($("nav li:first-child"), $("article section:first-child").attr("id"));

}); 

function setActiveTab($tab, sectionId) {
	console.log($tab);
	$section = $("#" + sectionId);
	console.log($section);
	if ($activeTab != null) {
		$activeTab.toggleClass("active");
		$activeSection.toggleClass("hidden");
	}
	$activeTab = $tab;
	$activeSection = $section;
	$activeTab.toggleClass("active");
	$activeSection.toggleClass("hidden");
	

}
