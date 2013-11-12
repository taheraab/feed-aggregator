//validate passwords
function validatePasswords($formElm, $errMsg) {
    // Check if password and confirm password are same
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

// Initialize and show confirm dialog
function showConfirmDialog(title, confirmMsg) {
  var $confirmDialog = $("#confirmDialog");
  $confirmDialog.find(".modal-title").text(title);
  $confirmDialog.find(".confirmMsg").html(confirmMsg);
  $confirmDialog.find(".returnVal").text("no");
  $confirmDialog.modal("show");

}
// Initialize and show add folder dialog
function showAddFolderDialog() {
  var $addFolderDialog = $("#addFolderDialog");
  $addFolderDialog.find(".folderName").val("");
  $addFolderDialog.find(".text-danger").text("");
  $addFolderDialog.find(".returnVal").text("cancel");
  $addFolderDialog.modal("show");

}

