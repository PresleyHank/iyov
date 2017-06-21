<?php

use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Applications\Lib\Config;
use \Workerman\Autoloader;
use \Applications\iyov\Lib\Http;
use \Applications\iyov\HttpProxy;

Config::setNameSpace('Applications\Config');

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

$httproxy_worker = new Worker('tcp://' . Config::get('Iyov.Proxy.host') . ':' . Config::get('Iyov.Proxy.port'));

$httproxy_worker->count = 5;

$httproxy_worker->name = 'iyov-http-proxy';

$httproxy_worker->onWorkerStart = function() {
	Timer::add(1, array('\Applications\iyov\HttpProxy', 'Broadcast'), array(), true);
};

$httproxy_worker->onConnect = function($connection) {
	HttpProxy::Instance($connection)->initClientCapture();
};

$httproxy_worker->onMessage = function($connection, $buffer) {
	if (!HttpProxy::Instance($connection)->asyncTcpConnection) {
		HttpProxy::Instance($connection)->data .= $buffer;
		if (!($length = Http::input(HttpProxy::Instance($connection)->data))) {
			return ;
		}

		HttpProxy::Instance($connection)->requestProcess(HttpProxy::Instance($connection)->data);
		HttpProxy::Instance($connection)->data = '';
	}
};

$httproxy_worker->onClose = function($connection) {
	HttpProxy::Instance($connection)->unInstance($connection);
};
