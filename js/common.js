//validate passwords
function validatePasswords($formElm) {
    // Check if password and confirm password are same
    var $errMsg = $("div.errMsg");
    var password = $formElm.find("input[name='password']").val();
	if (password.length < 8 ) {
        $errMsg.text("Password must be atleast 8 characters long");
        event.preventDefault();
        return;
    }
    if (password != $formElm.find("input[name='confirmPassword']").val()) {
        $errMsg.text("Confirm password not equal to password, try again");
        event.preventDefault();
    }
    
}   

