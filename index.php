<?php
include_once "includes/util.php";
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";
include_once "classes/FolderManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
}

if (isset($_SESSION["subsErrMsg"])) {
	$subsErrMsg = $_SESSION["subsErrMsg"];
	unset($_SESSION["subsErrMsg"]);
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Feed Reader</title>
	<meta charset="utf-8" >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="//code.jquery.com/jquery.js"></script>
    <!-- Bootstrap -->
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href='http://fonts.googleapis.com/css?family=Vast+Shadow' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="styles/main.css">
     <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
	<script src="js/common.js"></script>
    <script src="js/main.js"></script>
</head>
<body>	
    <div class="container">
	<?php require_once "includes/header.php"; ?>
  <div class="row">
    <div id ="subsListPanelContainer" class="col-md-3">
     <div class="text-danger"><?php if (isset($subsErrMsg)) echo $subsErrMsg; ?></div>
     <div id="subsListPanel" class="panel panel-default">
       <div class="panel-heading dropdown coloredHeader">
            Subscriptions
          <div class="btn-group pull-right">
            <button type="button" class="dropdown-toggle btn btn-default btn-xs" data-toggle="dropdown" >Add <span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu">
             <li><a href="#" onclick="showAddSubsDialog(); return false;">New subscription</a></li>
             <li><a href="#" onclick="showAddFolderDialog(); return false;">New folder</a></li>
            </ul>
          </div>
        </div>
        <div class="panel-body"> 
            <ul id="feedList" class="list-group">
              <li id="allItems" class="list-group-item" onclick="setActiveFeed(-1, $(this));"><span class="badge"></span>All Items</li>
            </ul> 
        </div>
      </div>
    </div>
    <div id="itemPanelContainer" class="col-md-9">
      <div id="itemPanel" class="panel panel-default">
        <div class="panel-heading coloredHeader">
           View: &nbsp;
           <div class="btn-group" data-toggle="buttons">
             <label class="btn btn-default btn-xs active" >
               <input name="filter" type="radio" onchange="filter='all'; filterView();" checked>&nbsp;All&nbsp;</input>
             </label>
             <label class="btn btn-default btn-xs" >
               <input name="filter" type="radio" onchange="filter='read'; filterView();">&nbsp;Read&nbsp;</input>
             </label>
             <label class="btn btn-default btn-xs" >
               <input name="filter" type="radio" onchange="filter='unread'; filterView();">&nbsp;Unread&nbsp;</input>
             </label>
             <label class="btn btn-default btn-xs" >
               <input name="filter" type="radio" onchange="filter='starred'; filterView();">&nbsp;Starred&nbsp;</input>
             </label>
           </div>
        </div>
        <div id="entryList" class="panel-body">
          <p> You do not have any subscriptions currently.<a href="settings.php?import">Import</a> an OPML file or 
            <a href="#" onclick="showAddSubsDialog(); return false;">Subscribe</a> to a feed.</p>
       </div>

     </div> <!-- item panel -->
   </div> <!-- item panel container -->
  </div> <!-- row -->
 </div> <!-- container -->
  <!-- Add subscription dialog -->
  <div class="modal" id="addSubsDialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header coloredHeader">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Add Subscription</h4>
        </div>
        <div class="modal-body">
          <form id="subsForm" role="form" method="post" action="manage_feeds.php?subscribeToFeed" 
            onsubmit="$(this).find('input[name=\'folderId\']').val(activeFolderId);">
            <div class="form-group">
              <label for="su" >HTML/RSS/Atom Link:</label> 
              <input id="su" type="url" name="url" class="form-control subsUrl" value=""  placeholder="subscription url" required />
              <input type="hidden" name="folderId" />    
            </div>
          </form>  
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <input type="submit" class="btn btn-primary" form="subsForm" value="Subscribe" />
        </div>
      </div>
    </div>
  </div>
   <!-- Add folder dialog -->
  <?php include_once "includes/dialogs.php"; ?>
</body>
</html>

