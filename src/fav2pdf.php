<?php

date_default_timezone_set('Europe/Kiev');
error_reporting(E_ERROR);

// Prepare arguments and options
$args = array_values($argv);

// Comments flag
$comments = in_array('-c', $args) || in_array('--comments', $args);
if ($comments) {
    unset($args[array_search('-c', $args)], $args[array_search('--comments', $args)]);
    $args = array_values($args);
}

// User nickname
$nick = $args[0];
if (empty($nick)) exit('Nickname should be defined!' . PHP_EOL);

// Where to save
$dir = __DIR__;
if (strpos($dir, 'phar://') === 0) $dir = substr($dir, 7);
define('ROOT_DIR', dirname($dir));

$saveDir = isset($args[1]) ? $args[1] : 'pdf';
// Path should be absolute for phar
if (substr($saveDir, 0, 1) != '/') $saveDir = ROOT_DIR . '/' . $saveDir;

// Include composer's autoloader
include_once 'vendor/autoload.php';

// RUN!
$saver = new FavSaver($nick, $saveDir, $comments);
$saver->parseUrls()->savePdf();