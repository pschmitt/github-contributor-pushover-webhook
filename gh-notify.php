<?php

require_once '../passwd/pushover.inc.php';

// Constants
$DEBUG = true;
$LOGFILE = '../logs/gh_log.txt';
$GITHUB_IPS = array('204.232.175.64/27', '192.30.252.0/22');
$CLIENT_IP = $_SERVER['REMOTE_ADDR'];

// Source: http://php.net/manual/fr/ref.network.php
function ipCIDRCheck ($IP, $CIDR) {
    list ($net, $mask) = split ('/', $CIDR);
    
    $ip_net = ip2long ($net);
    $ip_mask = ~((1 << (32 - $mask)) - 1);
    $ip_ip = ip2long ($IP);
    $ip_ip_net = $ip_ip & $ip_mask;

    return ($ip_ip_net == $ip_net);
}

// IP validity check 
$valid_ip = false;
foreach ($GITHUB_IPS as &$gh_ip_range) {
    if (ipCIDRCheck($CLIENT_IP, $gh_ip_range)) {
        $valid_ip = true;
        break;
    }
}
unset($gh_ip_range);

// Logging 
if ($DEBUG) {
    $file = fopen($LOGFILE, 'a') or die("File creation error.");
    // fwrite($file, $output);
    fwrite($file, "CLIENT IP: " . $CLIENT_IP . "\nvalid ? " . ($valid_ip ? "true" : "false") . "\n"); 
    fclose($file);
}

$valid_ip or die("Who the hell do you think you are ?");

// Retrieve JSON payload
try {
    // Decode the payload json string
    $payload = json_decode($_REQUEST['payload']);
} catch(Exception $e) {
    echo 'Exception: ',  $e->getMessage(), "\n";
    exit(1);
}
                
// Parse payload
$repo_name = $payload->repository->name; 
$repo_url = $payload->repository->url;
$branch = end(explode('/', $payload->ref));
$commit_num = count($payload->commits);
$last_commit = $payload->head_commit; 
$commit_url = $last_commit->url;
$last_commiter = $last_commit->author->username;
$title = $repo_name . ": " . ($commit_num > 1 ? $commit_num : "new") . " commit" . ($commit_num > 1 ? 's' : '') . " to " . $branch;
$message = $last_commit->message; 
if ($commit_num > 1) {
    $message = '';
    foreach ($payload->commits as &$commit) {
        $cat_msg = $message . $commit->message . "; ";
        if (strlen($cat_msg) >= 512) {
            break;
        }
        $message = $cat_msg;
    }
    unset($commit);
}
 
// Logging 
if ($DEBUG) {
    ob_start();
    var_dump($payload);
    $output = ob_get_clean();
    $file = fopen($LOGFILE, 'a') or die("File creation error.");
    //fwrite($file, $output);
    fwrite($file, "repo_name = " . $repo_name . "\ntitle = "  . $title . "\nmessage = " . $message . "\nurl = " . $repo_url . "\nlast_commiter = " . $last_commiter . "\nCommits: " . print_r($payload->commits) . "\n");
    fclose($file);
}

// Send notification
curl_setopt_array($ch = curl_init(), array(
              CURLOPT_URL => "https://api.pushover.net/1/messages.json",
              CURLOPT_POSTFIELDS => array(
                  "token" => "$PUSHOVER_TOKEN",
                  "user" => "$PUSHOVER_USER",
                  "title" => "$title",
                  "message" => "$message",
                  "url" => "$commit_url",
                  "url_title" => "View commit @GitHub",
              )));
// TODO ?
//if ($last_commiter != "pschmitt")
    curl_exec($ch);

curl_close($ch);

?>
