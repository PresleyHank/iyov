<?php
require_once './Workerman/Autoloader.php';
use Workerman\WebServer;
use Workerman\Worker;
use Applications\Lib\Config;

Config::setNameSpace('Applications\Config');
// WebServer
$web = new WebServer("http://" . Config::get('Iyov.Web.host') . ":" . Config::get('Iyov.Web.port'));

// 4 processes
$web->count = 4;

// Set the root of domains
foreach(Config::get('Iyov.Web.domain') as $domain) {
    $web->addRoot($domain, __DIR__);
};

// run all workers
Worker::runAll();