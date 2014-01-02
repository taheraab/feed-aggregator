**Welcome to Feed Reader**
The idea for this project was born out of the demise of Google Reader. Since I was a regular user of Google Reader, I thought this was a perfect opportunity to develop a Google Reader clone and in the process learn the fundamentals of developing a web application.

*Features:*
* Import your subscriptions/feeds (from OPML file or from the website URL).
* View entries per subscription/feed or all-together.
* Subscriptions are automatically updated periodically, so you can see the latest entries.
* Maintain entry status as Read, Unread, Starred.
* Organize subscriptions/feeds into folders.
* Export your subscriptions into an OPML file.

**Installation**
*Requirements:*
You need the following software to run the code in the feed-aggregator repository.
* Webserver. I used Apache 2.2.22 on Ubuntu.
* PHP 5.4 (with modules xmlreader, xmlwriter, posix)  
* A relational database. I used MySQL 5.5.
* Browser. I used Chromium (version 28.0.1500.71 Built on Ubuntu 12.04)
External Libraries:
* PHPMailer 5.1.0
* JQuery 
* HTMLPurifier 4.5.0
* Twitter Bootstrap
* Google Fonts 

*Deployment*
* Clone feed-aggregator repository into your webserver's document root
* Extract PHPMailer and HTMLPurifier libraries into "includes" folder and create soft links "htmlpurifier" and "PHPMailer" to their respective directories.
* Create a database user (e.g. FeedAggrUser) and grant him all permissions to a database (e.g. FeedAggrDB).  
* Edit "includes/constants.php" to provide your database hostname and connection information and SMTP connection info.
* Create two directories "log" and "files" with read/write permissions to apache user.  
* Execute "php private/create_db.php" to create the database schema.
* Execute "php private/feed_updater.php" to start the daemon process that periodically updates the feeds.
* Execute "crontab -e"  and add "@daily <full path to private/check_feed_updater.php>" to add a daily cron job to make sure the daemon is running. 

The web application is now setup to accept new users and their subscriptions.

For more information and screenshots, visit [project wiki](https://github.com/taheraab/feed-aggregator/wiki)
