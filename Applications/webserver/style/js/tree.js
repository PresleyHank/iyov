// yui 
var treeView = null;
var firstDir = true;
var newLineItem = ["RequestHeader","RequestBody","ResponseHeader","ResponseBody"];
var tree = {
	init: function() {
		YUI().use(
			'aui-tree-view',
			function(Y) {
				var children = [{
					id: 'root',
					expanded: true,
					label: 'iyov',
					// icon: 'glyphicon glyphicon-cloud',
					// cssClasses: 'normal',  //'{"normal":["glyphicon", "glyphicon-cloud"]}'
					leaf: false,
				}];

				treeView = new Y.TreeView({
					boundingBox: '#iyov-data',
					children: children
				}).render();

				ws.init();
			}
		);
	},
	exists: function(id) {
		if (treeView.getNodeById(id) == undefined) {
			return false;
		}
		return true;
	},
	createNode: function(parentId, id, label, leaf) {
		var child = tree.getChild(id, label, leaf);
		if (treeView != null) {
			var parentNode = treeView.getNodeById(parentId);
			parentNode.appendChild(parentNode.createNode(child));
			if (leaf) {
				tree.addClientListener(id);
			}
		}
	},
	insertBefore: function(parentId, id, label, leaf) {
		var child = tree.getChild(id, label, leaf);
		var parentNode = treeView.getNodeById(parentId);
		var children = parentNode.getChildren();
		var node = null;
		var insertBeforeFlag = false;
		for (var index in children) {
			node = children[index].getAttrs(['leaf', 'id']);
			if (node.leaf == false) {
				continue;
			}

			parentNode.insertBefore(parentNode.createNode(child), treeView.getNodeById(node.id));
			insertBeforeFlag = true;
			break;
		}

		if (children.length == 0 || !insertBeforeFlag) {
			parentNode.appendChild(parentNode.createNode(child));
		}
	},
	getChild: function(id, label, leaf) {
		var child = {
			id: id,
			label: label,
		};
		if (!leaf) {
			if (firstDir) {
				child.expanded = true; // 是否展开
				firstDir = false;
			}
		}
		child.leaf = leaf; // 是否为叶子节点
		return child;
	},
	addClientListener: function(id) {
		$("#"+id).bind('click', function() {
			tree.showData(id);
		});
	},
	showData: function(id) {
		var data = cache.get(id);
		var content = '';
		var item = '';
		for(var type in data) {
			if ($.inArray(type, newLineItem) == -1) {
				item = ' :<span class="item-content">' + data[type] + '</span><br/>';
			} else if (type == 'ResponseBody') {
				item = ' :<br/><br/><textarea readonly>' + data[type] + '</textarea>';
			} else {
				item = ' :<br/> <p class="item-content">' + data[type] + '</p>';
			}
			content = content + '<span class="item-title">' + type + '</span>' + item;
		}
		content = content != '' ? content : 'oh~ unexpected error happens...';
		$("#iyov-content").html(content);
	}
}