  <nav class="navbar navbar-default navbar-static-top" role="navigation">
  <!-- Brand and toggle get grouped for better mobile display -->
  <div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="index.php" data-toggle="tooltip" title="Home" >Feed Reader</a>
  </div>
<?php
	if (isset($_SESSION["currentUserId"])) {
?>   
 
  <!-- Collect the nav links, forms, and other content for toggling -->
  <div class="collapse navbar-collapse navbar-ex1-collapse">
    <ul class="nav navbar-nav navbar-right">
      <li><p class="navbar-text"> <?php echo "Signed in as ".$_SESSION["currentUsername"].
        " &nbsp;(<a href='manage_user.php?logout'>Signout</a>)&nbsp; &nbsp;" ?></p></li>
      <li><a href="settings.php" data-toggle="tooltip" title="Settings">
          <span class="glyphicon glyphicon-cog"></span>
        </a>
      </li>
    </ul>
  </div><!-- /.navbar-collapse -->
 <?php }
?>
 </nav>
