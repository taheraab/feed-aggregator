<?php
include_once("includes/util.php");

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" >
	<title>Feed Reader</title>
	<link rel="stylesheet" href="styles/main.css">
	<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
	<script src="js/main.js"></script>
	<script src="js/misc.js"></script>
</head>
<body>	
	<header>
		<h1>Feed Reader</h1>
		<div>
<?php
	echo "<span> Welcome ".$_SESSION["currentUsername"]."</span>";
	echo "<button type=\"button\" onclick = \"gotoPage('login.php?logout=1')\"> Logout </button>";
?>		
	</header>
		</div>
	<div id="content">
			<article>
			<section>
				<p> Feed1 title </p>
				<p> Feed1 summary </p>
			</section>
			<section>
				<p> Feed2 title</p>
				<p> Feed2 summary</p>
			</section>
		</article>
		<nav>
			<button type="button">Subscribe </button><br /><br />
			<a href="#"> Home </a>
			<ul id="subsList">
				<li><a href="#"> All Items</a> </li>
				<li> <a href="#">Subscriptions</a> 
					<ul>
						<li><a href="#">Thinkers</a>
							<ul><li><a href="#">Ted blog</a></li></ul>
						</li>
						<li><a href="#">Aayis Recipes</a></li>
					</ul>
				</li>
			</ul>
		</nav>
		<aside>
			<p> Aside </p>
		</aside>
	</div>
	<footer>
		<p> Footer </p>
	</footer>
</body>
</html>

