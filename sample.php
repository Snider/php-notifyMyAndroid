<?php
/**
 *
 * User: paul
 * Date: 31/03/12
 * Time: 04:55
 * To change this template use File | Settings | File Templates.
 */
require dirname(__FILE__).'/nmaApi.class.php';

$nma = new nmaApi(array('apikey' => 'ae87ee05b547fc07cc129eeef0406b061a32ecdf189197e6'));



if($nma->verify()){
    if($nma->notify('My Test', 'New Gizmo', 'Kinda cool, php to my droid... nice')){
        echo "Notifcation sent!";
    }
}

