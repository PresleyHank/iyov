// websocket

var ws = {
	init: function() {
		ws.gateway = new WebSocket('ws://test.iyov.io:4355');
		console.log('ws init');
		ws.gateway.onmessage = function(event) {
			if (event.data == "") {
				return ;
			}
			var package = eval('(' + event.data + ')');
			console.log(package);
			var hashHost ;
			var data;
			for(var time in package) {
				for (var host in package[time]) {
					data = package[time];
					hashHost = hash(host);
					if (host == 'http://iyov.io:8080' || host == '0.0.0.0:4355') {
						continue;
					}
					if (!tree.exists(hashHost)) {
						tree.createNode('root', hashHost, host, false);
					}
					var id;
					var holeUrl;
					for (var url in data[host]) {
						holeUrl = url == 'default' ? host : host+'/'+url;
						id = hash(holeUrl + '_t_' + time);
						cache.set(id,  holeUrl, data[host][url]);
						if ($("#iyov-content").children().length == 0) {
							tree.showData(id);
						}
						if (data[host][url].hasOwnProperty('Path') && path(data[host][url]['Path'])) {
							parseUrl(host, data[host][url], time);
							continue;
						}
						if (!tree.exists(id)) {
							tree.createNode(hashHost, id, url, true);
						}
					}
				}
			}
		}
	}
};

/**
 * Md5,过滤特殊符号
 */
function hash(id) {
	return $.md5(id);
}

/**
 * 检查url的路径深度
 */
function path(path) {
	if (path.split('/').length <= 2) {
		return false;
	}

	return true
}

/**
 * 添加路径
 */
function parseUrl(parent, data, starttime) {
	var path = data['Path'];
	var spices = path.split('/');
	var leaf = true;
	for (var index in spices) {
		if (spices[index] == '') {
			// 过滤首个空字符串
			continue;
		}
		var url = parent + '/' + spices[index];
		var id = (spices.length == parseInt(index) + 1) ? url + '_t_' + starttime : url;
		var hashId = hash(id);
		var hashParentId = hash(parent);
		if (tree.exists(hashId)) {
			parent = id;
			continue;
		}

		leaf = (index < spices.length - 1) ? false : true;
		if (!leaf) {
			tree.insertBefore(hashParentId, hashId, spices[index], leaf);
		} else {
			tree.createNode(hashParentId, hashId, spices[index], leaf);
			// cache.set(hashId, url, data);
		}
		parent = id;
	}
}
