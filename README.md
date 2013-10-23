github-contributor-pushover-webhook
===================================

Receive notifications via Pushover even if you are not owner of the GitHub repo

## Note

You still need to ask the owner to add a WebHook to call your script

## Installation

* Copy this script to your server
* Ask the owner to add a WebHook to the repo (e.g. domain.com/gh-notify.php)
* Create a php file in /root/passwd/pushover.inc.php (/root being the parent directory of your public_html) with following content: 


```php
<?php
  $PUSHOVER_TOKEN = '[YOUR_PUSHOVER_TOKEN]';
  $PUSHOVER_USER = '[YOUR_PUSHOVER_USER_KEY]';
?>
