<?php

date_default_timezone_set('Europe/Kiev');
error_reporting(E_ERROR);

/*
 * Helper functions
 */
function defineContext()
{
    $dir = __DIR__;
    if (strpos($dir, 'phar://') === 0) {
        $dir = substr($dir, 7);
        define('PHAR_CONTEXT', true);
    } else {
        define('PHAR_CONTEXT', false);
    }
    define('ROOT_DIR', dirname($dir));
}

function prepareArgs($argv_input)
{
    $args = array_values($argv_input);
    if (PHAR_CONTEXT) {
        unset($args[0]);
        $args = array_values($args);
    }
    if (array_search('-c', $args) !== false) {
        unset($args[array_search('-c', $args)]);
    }
    if (array_search('--comments', $args) !== false) {
        unset($args[array_search('--comments', $args)]);
    }
    return $args;
}

function getNick($args)
{
    $nick = $args[0];
    if (empty($nick)) exit('Nickname should be defined!' . PHP_EOL);
    return $nick;
}

function getSaveDir($args)
{
    $saveDir = isset($args[1]) ? $args[1] : 'pdf';
    // Path should be absolute for phar
    if (substr($saveDir, 0, 1) != '/') $saveDir = ROOT_DIR . '/' . $saveDir;
    return $saveDir;
}

function parseComments($argv)
{
    return in_array('-c', $argv) || in_array('--comments', $argv);
}

/*
 * Init
 */
defineContext();

$args = prepareArgs($argv);
$nick = getNick($args);
$saveDir = getSaveDir($args);
$comments = parseComments($argv);


// Include composer's autoloader
include_once 'vendor/autoload.php';

// RUN!
$saver = new FavSaver($nick, $saveDir, $comments);
$saver->parseUrls()->savePdf();