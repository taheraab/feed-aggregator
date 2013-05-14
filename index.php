<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" >
	<title>Feed Reader</title>
	<link rel="stylesheet" href="styles/main.css">
	<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
	<script src="js/main.js"></script>
</head>
<body>	
	<header>
		<h1>Reader</h1>
	</header>
	<nav>
		<button type="button">Subscribe </button><br />
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
	<p> 
<?php 
$extensions = get_loaded_extensions();
foreach($extensions as $extension) {
	echo $extension."<br>";
}

 ?> </p>
</body>
</html>

