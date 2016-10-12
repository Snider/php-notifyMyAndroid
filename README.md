snider/php-notify-my-android
============================

This is a PHP library for [NotifyMyAndroid.com][0] that does not require curl.

If you find a bug, or think of an improvement, please fork the repository and
submit a pull request.

I have had a nexus 5 since it came out and am now actually using the lib. If I
find any improvements, I can test them myself. :) Yay!


Usage
-----

A basic approach at using this package could look like this:

```php
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
```

Use the included `sample.php` to try it for yourself.


License
-------

See LICENSE.txt for full license details.


[0]: http://notifymyandroid.com
