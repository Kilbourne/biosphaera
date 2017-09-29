/*global AbeData */

var wsAdminBarEditor = wsAdminBarEditor || {};

(function($) {

	/*
	 * A custom binding that prevents click events from bubbling up.
	 */
	ko.bindingHandlers.stopBubble = {
		init: function(element) {
			ko.utils.registerEventHandler(element, "click", function(event) {
				event.cancelBubble = true;
				if (event.stopPropagation) {
					event.stopPropagation();
				}
			});
		}
	};

	//Make a local reference to the node class so that we don't have to write the namespace every time.
	var NodeViewModel = wsAdminBarEditor.NodeViewModel;

	/**
	 * The main view model of the admin bar editor.
	 *
	 * @param {Object} settings Various settings passed in by the plugin.
	 * @constructor
	 */
	wsAdminBarEditor.ApplicationViewModel = function(settings) {
		var self = this;

		/**
		 * Convert a flat list of admin bar nodes to a tree of NodeViewModel's.
		 * This is basically a greatly simplified JavaScript port of WP_Admin_Bar::_bind().
		 *
		 * @param {Array} nodes
		 * @returns {wsAdminBarEditor.NodeViewModel[]} Children of the root node.
		 */
		function buildNodeTree(nodes) {
			var root = { children: [] }, nodeModels = {};

			//Make view-models for each node.
			$.each(nodes, function(id, plainNode) {
				nodeModels[id] = new NodeViewModel(plainNode, null, self);
			});

			//Attach nodes to their parents.
			$.each(nodeModels,
				/**
				 * @param {String} id
				 * @param {NodeViewModel} node
				 */
				function(id, node) {
					var parent = root;
					if (node.parentId && nodeModels.hasOwnProperty(node.parentId)) {
						parent = nodeModels[node.parentId];
						node.parent(parent);
					}
					parent.children.push(node);
				}
			);

			return root.children;
		}

		/**
		 * Convert a tree of node models to a flat dictionary of plain objects that
		 * can be used as input to Abe_Node::fromArray().
		 *
		 * @param tree
		 * @returns {Object}
		 */
		function flattenNodeTree(tree) {
			var nodes = {};

			for (var i = 0; i < tree.length; i++) {
				mapNode(tree[i], null);
			}

			function mapNode(node, parent) {
				var plainNode = (typeof node['toJs'] === 'undefined') ? node : node.toJs();
				plainNode.parent = parent ? parent.id : false;

				var children = plainNode.children;
				delete plainNode.children;
				nodes[plainNode.id]  = plainNode;

				$.each(children, function(index, child) {
					mapNode(child, plainNode);
				});
			}

			return nodes;
		}

		this.defaultConfiguration = settings.defaultConfiguration;
		this.currentConfiguration = settings.currentConfiguration;

		this.nodes = ko.observableArray();

		//The currently selected node.
		this.internalSelectedNode = ko.observable(null);
		//noinspection JSUnusedGlobalSymbols
		this.selectedNode = ko.computed({
			read: self.internalSelectedNode,
			write: function(node) {
				var previousNode = self.internalSelectedNode();
				if (node === previousNode) {
					return;
				}
				//Deselect the previously selected node, if any.
				if (previousNode !== null) {
					previousNode.selected(false);
				}

				//Select the new node.
				self.internalSelectedNode(node);
				if (node !== null) {
					node.selected(true);
				}
			},
			owner: self
		});

		/**
		 * Find a node by ID.
		 *
		 * @param {wsAdminBarEditor.NodeViewModel[]} nodeList Search this list of nodes...
		 * @param {String} id ...for a node that has this ID.
		 * @param {wsAdminBarEditor.NodeViewModel} [excludeNode] Optional. Exclude this node from the search (its children are still included).
		 * @return {wsAdminBarEditor.NodeViewModel} Either the node or NULL if there is no node with that ID.
		 */
		function findNodeById(nodeList, id, excludeNode) {
			if (typeof excludeNode === 'undefined') {
				excludeNode = null;
			}

			function searchArray(nodes) {
				var foundNode = null, i = 0;
				while(!foundNode && i < nodes.length) {
					if ( (nodes[i] !== excludeNode) && (nodes[i].id() == id) ) {
						foundNode = nodes[i];
					} else {
						foundNode = searchArray(nodes[i].children());
					}
					i++;
				}
				return foundNode;
			}

			return searchArray(nodeList);
		}

		/**
		 * Find a node by ID.
		 *
		 * @param {String} id
		 * @param {wsAdminBarEditor.NodeViewModel} [excludeNode]
		 * @return {wsAdminBarEditor.NodeViewModel}
		 */
		this.findNodeById = function(id, excludeNode) {
			return findNodeById(self.nodes(), id, excludeNode);
		};

		var customItemCounter = 0;
		function createUniqueNodeId(prefix, ignoreNode) {
			if (typeof prefix === 'undefined' || prefix === null) {
				prefix = 'custom-node-';
			}
			if (typeof ignoreNode === 'undefined') {
				ignoreNode = null;
			}

			var id;
			do {
				customItemCounter++;
				id = prefix + customItemCounter;
			} while (self.findNodeById(id, ignoreNode) !== null);
			return id;
		}

		//noinspection JSUnusedGlobalSymbols
		this.createItem = function() {
			var node = new NodeViewModel({
				id:        createUniqueNodeId(),
				title:     'Custom Item #' + customItemCounter,
				group:     false,
				is_custom: true,
				is_hidden: false
			}, null, self);

			self.insertNodeAfterSelected(node);
		};

		//noinspection JSUnusedGlobalSymbols
		this.createGroup = function() {
			if ( !self.canCreateGroup() ) {
				alert("You can not create a group inside a group. Put it under a normal item instead.");
				return;
			}

			var node = new NodeViewModel({
				id:        createUniqueNodeId('custom-group-'),
				title:     '',
				group:     true,
				is_custom: true,
				is_hidden: false
			}, null, self);

			self.insertNodeAfterSelected(node);
		};

		/**
		 * Insert a node after the currently selected node.
		 * If no node is currently selected this method will add the new node
		 * to the root level node list instead.
		 *
		 * @param {wsAdminBarEditor.NodeViewModel} node
		 */
		this.insertNodeAfterSelected = function(node) {
			var selectedNode = self.selectedNode();

			var container = self.nodes;
			if (selectedNode && selectedNode.parent()) {
				node.parent(selectedNode.parent());
				container = selectedNode.parent().children;
			} else {
				node.parent(null);
			}

			//Place the new item after the selected one, if any.
			if ( !selectedNode ) {
				container.push(node);
			} else {
				var targetPosition = container.indexOf(selectedNode) + 1;
				container.splice(targetPosition, 0, node);
			}
		};

		this.canCreateGroup = ko.computed(function() {
			if ( !self.selectedNode() || !self.selectedNode().parent() ) {
				//You can create a group at root level.
				return true;
			}
			//WordPress does not allow creating groups inside groups.
			return !self.selectedNode().parent().group();
		}, this);

		//noinspection JSUnusedGlobalSymbols
		this.deleteSelectedNode = function() {
			var node = self.selectedNode();
			if ( !node ) {
				alert('Please select an item to delete first.');
				return;
			}

			if ( !node.is_custom() ) {
				if ( confirm('Sorry, but you cannot delete a default item. Would you like to hide it instead?') ) {
					node.is_hidden(true);
				}
				return;
			}

			if ( node.children().length > 0 && !confirm('Delete this item and all of its children?') ) {
				return;
			}
			//Concern: What if node a custom item but its children are not?

			var container = node.parent() ? node.parent().children : self.nodes;
			container.remove(node);
			self.selectedNode(null);
		};

		/*
		 * Copy & paste support.
		 */
		this.nodeInClipboard = ko.observable(null);

		function copySelectedToClipboard(remove) {
			if ( !self.selectedNode() ) {
				alert('Please select an item first.');
				return;
			}

			var node = self.selectedNode();
			self.nodeInClipboard(node.toJs());

			if (remove) {
				var container = node.parent() ? node.parent().children : self.nodes;
				container.remove(node);
				self.selectedNode(null); //We removed the selected node, so now nothing is selected.
			}
		}

		//noinspection JSUnusedGlobalSymbols
		this.copyNode = function() {
			copySelectedToClipboard(false);
		};
		//noinspection JSUnusedGlobalSymbols
		this.cutNode = function() {
			copySelectedToClipboard(true);
		};

		//noinspection JSUnusedGlobalSymbols
		this.pasteNode = function() {
			if ( !self.nodeInClipboard() ) {
				return; //Nothing to do - the clipboard is empty.
			}

			var pastedNode = new NodeViewModel(self.nodeInClipboard(), null, self);
			self.insertNodeAfterSelected(pastedNode);

			//The pasted node and its children must have unique IDs. This function
			//recursively scans the node tree and assigns new IDs as necessary.
			function ensureIdIsUnique(node) {
				if (self.findNodeById(node.id(), node)) {

					//Use the current ID as the prefix for the new, unique ID.
					//For example, "foo-2" becomes "foo-123", while "bar" becomes "bar-123".
					var prefix = node.id();
					//Strip the old numeric suffix.
					var matches = prefix.match(/^([^\d].*?)(\d+)$/);
					if (matches) {
						prefix = matches[1];
					}
					//Append a dash.
					if (prefix.charAt(prefix.length - 1) != '-') {
						prefix = prefix + '-';
					}

					node.id(createUniqueNodeId(prefix, node));
					node.is_custom(true); //Custom id => custom node.
				}
				ko.utils.arrayForEach(node.children(), ensureIdIsUnique);
			}
			
			ensureIdIsUnique(pastedNode);
		};

		/*
		 * Expand/collapse all nodes.
		 */
		function toggleAllNodes(nodes, expanded) {
			for (var i = 0; i < nodes.length; i++) {
				nodes[i].expanded(expanded);
				if ( nodes[i].children().length > 0 ) {
					toggleAllNodes(nodes[i].children(), expanded);
				}
			}
		}
		//noinspection JSUnusedGlobalSymbols
		this.expandAll = function() {
			toggleAllNodes(self.nodes(), true);
		};
		//noinspection JSUnusedGlobalSymbols
		this.collapseAll = function() {
			toggleAllNodes(self.nodes(), false);
		};

		/*
		 * Support per-role node visibility.
		 */

		//Convert the [slug => role_name] hash table supplied by the plugin to an array of actor objects.
		var tempActors = [];
		for (var slug in AbeData.actors) {
			if (!AbeData.actors.hasOwnProperty(slug)) {
				continue;
			}

			tempActors.push({
				slug: slug,
				name: AbeData.actors[slug],
				selected: ko.observable(false)
			});
		}

		//Add the special "All" option. This lets the user show/hide nodes for all roles at once.
		var actorAll = {
			slug: null,
			name: 'All',
			selected: ko.observable(true)
		};
		tempActors.unshift(actorAll);

		//The list of known actors. It probably won't change during the application lifetime, but lets stick it
		//in an observable array just in case.
		this.actors = ko.observableArray(tempActors);

		//The currently selected actor. Defaults to "All" (no actor).
		this.internalSelectedActor = ko.observable(null);
		this.selectedActor = ko.computed({
			read: self.internalSelectedActor,
			write: function(actor) {
				if (actor === actorAll) {
					actor = null;
				}

				//Don't waste time re-selecting the same actor.
				var previousActor = self.internalSelectedActor();
				if (actor === previousActor) {
					return;
				}

				//Deselect the previously selected actor.
				if (previousActor !== null) {
					previousActor.selected(false);
				} else {
					actorAll.selected(false);
				}

				//Select the new actor.
				self.internalSelectedActor(actor);
				if (actor !== null) {
					actor.selected(true);
				} else {
					actorAll.selected(true);
				}
			},
			owner: self
		});

		/*
		 * Drag & drop sorting utility functions and event handlers.
		 */

		/**
		 * Check if it's okay to move a node to a new parent.
		 *
		 * @param {wsAdminBarEditor.NodeViewModel} node The node being moved.
		 * @param {wsAdminBarEditor.NodeViewModel} newParent The parent node it is being moved to.
		 * @returns {boolean}
		 */
		this.isAllowedMove = function(node, newParent) {
			//Disallow moving groups directly to other groups.
			return ! (node.group() && newParent && newParent.group());
		};

		/**
		 * Event handler for when a node has successfully been moved to a new location.
		 *
		 * @param {wsAdminBarEditor.NodeViewModel} node
		 * @param {wsAdminBarEditor.NodeViewModel} parent
		 */
		this.onNodeMoved = function(node, parent) {
			node.parent(parent);

			//If the parent had no children before it may have been closed.
			//We want to expand its children list now (from experience, it's better for usability).
			if (parent && parent.expanded) {
				parent.expanded(true);
			}
		};

		/*
		 * Save/load admin bar configurations.
		 */
		//noinspection JSUnusedGlobalSymbols
		this.saveMenu = function() {
			var nodes = flattenNodeTree(self.nodes());
			$('#admin-bar-node-list').val($.toJSON(nodes));
			return true;
		};

		this.loadMenu = function(nodeList) {
			var tree = buildNodeTree(nodeList);

			//In Multisite the "My Sites" menu contains a list of the current user's sites.
			//It would be pointless to show it in the editor since the list is completely different
			//for each user, so we'll remove the sites and just leave the group node.
			var siteList = findNodeById(tree, 'my-sites-list');
			if (siteList) {
				siteList.children([]);
			}

			self.nodes(tree);
		};

		//noinspection JSUnusedGlobalSymbols
		this.loadDefaultConfiguration = function() {
			self.loadMenu(self.defaultConfiguration);
		};
		this.loadCurrentConfiguration = function() {
			self.loadMenu(self.currentConfiguration);
		};

		/*
		 * Export/import node configuration.
		 */
		this.exportFormat = {
			name: 'Admin Bar Editor node data',
			version: 1
		};

		this.exportMenu = function() {
			var exportData = {
				format: self.exportFormat,
				created_at: (new Date()).toUTCString(),
				nodes: flattenNodeTree(self.nodes())
			};

			var exportJson = $.toJSON(exportData);
			$('#abe-export-data').val(exportJson);
			return true;
		};

		/* The import dialog and associated elements */
		var importDialog = $('#abe-import-dialog'),
			importForm = $('#abe-import-form'),
			uploadButton = $('#abe-upload-file-button'),
			importFileInput = $('#abe-import-file');

		//Enable the upload button when the user selects a file.
		importFileInput.change(function() {
			var fileName = $(this).val();
			uploadButton.prop('disabled', !fileName);
		});

		importDialog.dialog({
			autoOpen: false,
			modal: true,
			minWidth: 290,
			minHeight: 155
		});

		//Submit the import form through Ajax. Requires the jQuery Form plugin.
		importForm.ajaxForm({
			dataType: 'json',

			beforeSubmit: function() {
				var fileName = importFileInput.val();
				if (fileName == '') {
					alert('Select a file first!');
					return false;
				}

				importDialog.find('.abe-hide-while-importing').hide();
				$('#abe-import-progress-notice').show();

				return true;
			},

			success: function(response) {
				var progressNotice = $('#abe-import-progress-notice');

				if (typeof response['error'] !== 'undefined') {
					progressNotice.hide();
					importDialog.find('.abe-hide-while-importing').show();

					alert('Error: ' + response['error']);
					return;
				}

				var isSupportedFormat = response
					&& (typeof response['format'] !== 'undefined')
					&& (typeof response.format['name'] !== 'undefined')
					&& (response.format.name === self.exportFormat.name)
					&& (response.format.version <= self.exportFormat.version);

				if (!isSupportedFormat) {
					progressNotice.hide();
					importDialog.find('.abe-hide-while-importing').show();

					alert("Error: Unknown file format.");
					return;
				}

				progressNotice.hide();
				$('#abe-import-complete-notice').show();

				self.loadMenu(response.nodes);

				//Pause for a moment before closing the dialog so that the user
				//can see the success message.
				setTimeout(
					function() { importDialog.dialog('close'); },
					900
				);
			},

			error: function(xhr, statusText, error) {
				if ( typeof error['message'] !== 'undefined' ) {
					alert(error.message);
				} else {
					alert('Error: Ajax request failed');
				}

				$('#abe-import-progress-notice').hide();
				importDialog.find('.abe-hide-while-importing').show();
			}
		});

		/**
		 * Open the menu import dialog.
		 */
		this.importMenu = function() {
			importForm.resetForm();
			uploadButton.prop('disabled', true);

			$('#abe-import-progress-notice, #abe-import-complete-notice').hide();
			importDialog.find('.abe-hide-while-importing').show();

			importDialog.dialog('open');
		};

		this.loadCurrentConfiguration();
	};

	$(function() {
		var editorElement = $('#ws_admin_bar_editor');

		wsAdminBarEditor.app = new wsAdminBarEditor.ApplicationViewModel(AbeData);
		ko.applyBindings(wsAdminBarEditor.app, editorElement.get(0));

		editorElement.on('mouseover', '.abe-tooltip-trigger', function(event) {
			// Bind the qTip within the event handler
			$(this).qtip({
				overwrite: false, // Make sure the tooltip won't be overridden once created
				content: true,
				show: {
					event: event.type, // Use the same show event as the one that triggered the event handler
					ready: true // Show the tooltip as soon as it's bound, vital so it shows up the first time you hover!
				},
				position: {
					my: 'left top',
					at: 'right center'
				},
				style: {
					classes: 'qtip-rounded qtip-shadow qtip-plain abe-tooltip'
				}
			}, event); // Pass through our original event to qTip
		});
	});

}(jQuery));