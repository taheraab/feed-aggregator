<?php
include_once "PHPMailer/class.phpmailer.php";
include_once "constants.php";
$smtpHost = "smtp.gmail.com";
$smtpPort = 587;
$smtpUsername = "";
$smtpPassword = "";

function createRedirectURL($path) {
	$url = ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")) ? "https://" : "http://";
	$url.= $_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"])."/$path";
	return $url;
}

function sendHTMLMail($emailId, $username, $msg) {
	$content = "<html><body>".$msg."</body></html>";
	$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch

    $mail->IsSMTP(); // telling the class to use SMTP

    try {
      $mail->SMTPAuth   = true;                  // enable SMTP authentication
      $mail->SMTPSecure = "tls";                 // sets the prefix to the servier
      $mail->Host       = $smtpHost;     // sets GMAIL as the SMTP server
      $mail->Port       = $smtpPort;                   // set the SMTP port for the GMAIL server
      $mail->Username   = $smtpUsername;  // GMAIL username
      $mail->Password   = $smtpPassword;            // GMAIL password
      $mail->AddAddress($emailId, $username);
      $mail->SetFrom('admin@feedreader.com', 'Feed Reader');
      $mail->Subject = 'Email confirmation for your account';
      $mail->MsgHTML($content);
      $mail->Send();
	  return true;
    } catch (phpmailerException $e) {
      error_log("FeedAggregator::sendHTMLMail: ".$e->errorMessage(), 0); //Pretty error messages from PHPMailer
    } 

	return false;
}

// Parse an html document and return first feed Url (from Link tag) if present 
function getFeedUrlFromHtml($url) {
    $feedUrl = "";
    $htmlFile = file_get_contents($url);
    if (preg_match("/<html.*?>.*?<\/html>/s", $htmlFile)) {
      if (preg_match("/<head.*?>.*?<\/head>/s", $htmlFile, $head)) {
        if (preg_match_all("/<link.*?(?:\/>|><\/link>)/", $head[0], $linkTags)) {
          foreach($linkTags[0] as $linkTag) {
            if (preg_match("/rel=[\"']alternate[\"']/", $linkTag)) {
              if (preg_match("/type=[\"']application\/(atom|rss)\+xml[\"']/", $linkTag, $type)) {
                // found link tag with feed url
               if (preg_match("/href=[\"\'](.*?)[\"\']/", $linkTag, $matches)) {
                  $feedUrl = $matches[1];
                  break;  //return after first match
               }
             }    
            }
          }
        }
      }
    }else $feedUrl = $url;

    return $feedUrl;
}

?>
