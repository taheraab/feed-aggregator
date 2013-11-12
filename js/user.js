var $activeSection = null;

$(document).ready(function() {
	$activeSection = $('#login');
    var queryString = window.location.search.substring(1);
    if (queryString != "") {
		activateSection($('#' + queryString));
  	}
   
}); 




function activateSection($elm) {
	if ($activeSection != null) $activeSection.addClass("hidden");
	$elm.removeClass("hidden");
	$activeSection = $elm;
}

