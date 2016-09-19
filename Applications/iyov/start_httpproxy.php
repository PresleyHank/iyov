<?php

use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Autoloader;
use \Workerman\Protocols\Http;
use \Applications\iyov\HttpProxy;
// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

$httproxy_worker = new Worker('tcp://0.0.0.0:9733');

$httproxy_worker->count = 1;

$httproxy_worker->name = 'iyov-http-proxy';

$httproxy_worker->onWorkerStart = function() {
	HttpProxy::init();
	Timer::add(1, function() {
		HttpProxy::Broadcast();
	}, array(), true);
};

$httproxy_worker->onConnect = function($connection) {
	echo "connected\n";
	HttpProxy::Instance($connection);
};

$httproxy_worker->onMessage = function($connection, $buffer) {
	// echo "$buffer\n";
	HttpProxy::Instance($connection)->process($buffer);
};

$httproxy_worker->onWorkerStop = function() {
	
};
