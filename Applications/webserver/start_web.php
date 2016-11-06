<?php
require_once './Workerman/Autoloader.php';
use Workerman\WebServer;
use Workerman\Worker;

// WebServer
$web = new WebServer("http://0.0.0.0:8080");

// 4 processes
$web->count = 4;

// Set the root of domains
$web->addRoot('www.iyov.io', __DIR__);
$web->addRoot('iyov.io', __DIR__);

// run all workers
Worker::runAll();