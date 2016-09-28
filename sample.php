<?php
namespace snider\NotifyMyAndroid;

require_once realpath(__DIR__ . '/vendor') . '/autoload.php';

$apiKey      = 'insertYourApiKeyHere';
$application = 'snider/php-notify-my-android';
$event       = 'Sample Event';
$description = 'This is a sample event notification.';

$nma = new Api(array('apikey' => $apiKey));
if ($nma->verify()) {
    if ($nma->notify($application, $event, $description)) {
        echo 'Notification sent';
    }
}
