$(document).ready(function() {
    var queryString = window.location.search.substring(1);
    if (queryString == "register") {
		toggleActiveForm();
  	}
   
}); 




function toggleActiveForm() {
        $("#registerForm").toggleClass("hidden");
		$("#loginForm").toggleClass("hidden");
}

//validate register form input
function validateRegisterInput($formElm) {
	// Check if password and confirm password are same
	var $errMsg = $("div.errMsg");
	var password = $formElm.find("input[name='password']").val();
	if (password.length < 6 ) {
		$errMsg.text("Password must be atleast 6 characters long");
		event.preventDefault();
		return;
	}
	if (password != $formElm.find("input[name='confirmPassword']").val()) {
		$errMsg.text("Confirm password not equal to password, try again");
		event.preventDefault();
	}
	
}

