// 缓存所有数据
var data = {};

var cache = {
	set: function(id, url, content) {
		if (data[id] == undefined) {
			data[id] = {};
		}
		console.log(content);
		if (content['Query'] != undefined && content['Query'] != "") {
			url = url + '?' + content['Query'];
			delete content.Query;
		}
		data[id]['Url'] = !data[id].hasOwnProperty('Url') ? url : data[id]['Url'];
		for(var index in content) {
			data[id][index] = content[index];
		}
	},
	get: function(id) {
		if (data[id] == undefined) {
			return {};
		}

		return data[id];
	}
};