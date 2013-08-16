	<header>
        <div><img src= "resources/header.png" /></div>
<?php
	if (isset($_SESSION["currentUserId"])) {
?>   
     <div id="welcome">
   <?php  echo "<span> Welcome ".$_SESSION["currentUsername"]."</span>"; ?>

            <button onclick = "window.location.assign('manage_user.php?logout');"> Logout </button>
        </div>

<?php }
?>
    </header>
