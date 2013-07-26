	<header>
        <h2>Feed Reader</h2>
        <div id="welcome">
<?php
    echo "<span> Welcome ".$_SESSION["currentUsername"]."</span>";
?>
            <button onclick = "gotoPage('login.php?logout');"> Logout </button>
        </div>

    </header>
