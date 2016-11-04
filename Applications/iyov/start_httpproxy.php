<?php

use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Autoloader;
use \Applications\iyov\Lib\Http;
use \Applications\iyov\HttpProxy;
// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

$httproxy_worker = new Worker('tcp://0.0.0.0:9733');

$httproxy_worker->count = 1;

$httproxy_worker->name = 'iyov-http-proxy';

$httproxy_worker->onWorkerStart = function() {
	Timer::add(1, array('\Applications\iyov\HttpProxy', 'Broadcast'), array(), true);
};

$httproxy_worker->onConnect = function($connection) {
	HttpProxy::Instance($connection)->initClientCapture();
};

$httproxy_worker->onMessage = function($connection, $buffer) {
	if (!HttpProxy::Instance($connection)->asyncConnection) {
		HttpProxy::Instance($connection)->data .= $buffer;
		if (!($length = Http::input(HttpProxy::Instance($connection)->data))) {
			return ;
		}

		HttpProxy::Instance($connection)->requestProcess(HttpProxy::Instance($connection)->data);
		HttpProxy::Instance($connection)->data = '';
	}
};
