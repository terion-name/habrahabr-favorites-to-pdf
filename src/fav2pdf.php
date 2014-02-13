<?php

date_default_timezone_set('Europe/Kiev');
error_reporting(E_ERROR);

$nick = $argv[1];
$saveDir = isset($argv[2]) ? $argv[2] : 'pdf';
if (empty($nick)) exit('Nickname should be defined!' . PHP_EOL);

include_once 'vendor/autoload.php';

$saver = new FavSaver($nick, $saveDir);
$saver->parseUrls()->savePdf();