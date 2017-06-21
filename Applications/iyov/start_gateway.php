<?php

use \Workerman\Worker;
use \Workerman\Autoloader;
use \Applications\iyov\Gateway;
use \Applications\Lib\Config;

Config::setNameSpace('Applications\Config');

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

$gateway_worker = new Worker('websocket://' . Config::get('Iyov.Gateway.host') . ':' . Config::get('Iyov.Gateway.port'));

$gateway_worker->name = 'iyov-gateway';

$gateway_worker->count = 1;

$gateway_worker->onWorkerStart = function($gateway_worker) {
	Gateway::Init($gateway_worker);
};