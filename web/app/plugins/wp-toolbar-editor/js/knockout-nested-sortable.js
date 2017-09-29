//knockout-nested-sortable by Janis Elsts. Based on knockout-sortable by Ryan Niemeyer.
//This is a stripped-down implementation that is not suitable for general use.
(function($) {

	var ITEM_KEY = "ko_ns_Item",
		SOURCE_INDEX_KEY = "ko_ns_sourceIndex",
		LIST_KEY = "ko_ns_list",
		PARENT_LIST_KEY = "ko_ns_parentList",
		PARENT_ITEM_KEY = "ko_ns_parentItem",
		SORTABLE_CREATED_KEY = "ws_ko_nested_sortable_created",
		dataGet = ko.utils.domData.get,
		dataSet = ko.utils.domData.set;

	var addMetaDataAfterRender = function(elements, data) {
		ko.utils.arrayForEach(elements, function(element) {
			if (element.nodeType === 1) { //ELEMENT_NODE
				dataSet(element, ITEM_KEY, data);
				dataSet(element, PARENT_LIST_KEY, dataGet(element.parentNode, LIST_KEY));
			}
		});
	};

	//Prepare the options for our custom template binding.
	var prepareTemplateOptions = function(options) {
		var result = {};

		result.foreach = options.data;
		result.name = options.template;

		//Use an afterRender function to add meta-data.
		result.afterRender = addMetaDataAfterRender;

		return result;
	};

	//Prepare a custom binding context. We use it to pass a couple of settings to nested nestedSortable bindings.
	var prepareInnerBindingContext = function(options, bindingContext) {
		return bindingContext.extend({
			ko_ns_DefaultTemplate: options.template,
			ko_ns_IsInsideSortable: true
		});
	};

	//Parse options passed to the nestedSortable binding.
	var parseBindingOptions = function(valueAccessor, bindingContext) {
		var options = ko.utils.unwrapObservable(valueAccessor());

		//When using the binding inside another nestedSortable (e.g. in a recursive template), you can
		//just pass in an observableArray and let it inherit other settings from the parent binding.
		if (!options.data) {
			options = { data: valueAccessor() };
		}

		options = $.extend({
				data     : null,
				template : bindingContext.ko_ns_DefaultTemplate,
				isRoot   : ! bindingContext.ko_ns_IsInsideSortable,
				isAllowed: null,
				afterMove: null
			},
			options
		);

		if (options.isRoot) {
			options.parentItem = null;
		} else {
			options.parentItem = bindingContext.$data;
		}

		//Default options for the nested sortable.
		options.sortableOptions = {
			handle: 'div',
			items : 'li',
			toleranceElement: '> div'
		};
		if (options.options) {
			options.sortableOptions = $.extend(options.sortableOptions, options.options);
			delete options.options;
		}

		return options;
	};

	ko.bindingHandlers.nestedSortable = {
		init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
			var options = parseBindingOptions(valueAccessor, bindingContext);
			var innerBindingContext = prepareInnerBindingContext(options, bindingContext),
				templateOptions = prepareTemplateOptions(options);

			dataSet(element, LIST_KEY, options.data);
			dataSet(element, PARENT_ITEM_KEY, options.parentItem);
			//Note: It might seem tempting to just walk up the DOM tree and grab the parent item data from
			//the closest <li>, but that won't work since it hasn't been set yet at this point. addMetaDataAfterRender()
			//only runs after all of the element's children have been rendered.

			//Wrap the template binding. We need to use templates because we need the afterRender callback
			//to add metadata to each sortable item.
			ko.bindingHandlers.template.init(
				element,
				function() { return templateOptions; },
				allBindingsAccessor,
				viewModel,
				innerBindingContext
			);

			//Skip sortable initialization if we're already inside a nested sortable.
			if (!options.isRoot) {
				return { 'controlsDescendantBindings': true };
			}

			//Initialize sortable binding after template binding has rendered in the update function.
			var createTimeout = setTimeout(function() {

				$(element).nestedSortable( ko.utils.extend(options.sortableOptions, {
					doNotClear: true, //Must be true or nestedSortable will remove empty lists
					                  //and replace them with its own, un-bound lists later.
					forcePlaceholderSize: true,
					//helper: 'clone',
					isTree: false, //The tree feature hasn't been tested, so disable it to avoid potential bugs.

					isAllowed: function(placeholder, parent, originalItem) {
						var parentData = parent ? dataGet(parent.get(0), ITEM_KEY) : null;
						var itemData = dataGet(originalItem.get(0), ITEM_KEY);

						if (options.isAllowed) {
							return options.isAllowed(itemData, parentData);
						}
						return true;
					},

					start: function(event, ui) {
						var element = ui.item[0];
						var item = dataGet(element, ITEM_KEY);

						if ( !item ) {
							if (window.console && console.log) {
								console.log('Trying to drag an element that has no item data!', element);
							}
							return;
						}

						//Track the original index.
						var sourceIndex = ko.utils.arrayIndexOf(ui.item.parent().children(), element);
						dataSet(element, SOURCE_INDEX_KEY, sourceIndex);

						//Make sure that fields have a chance to update the view-model.
						ui.item.find("input:focus").change();
					},

					update: function(event, ui) {
						var sourceList, targetList, sourceIndex, targetIndex, targetParentItem,
							element = ui.item[0],
							parentElement = ui.item.parent()[0],
							itemData = dataGet(element, ITEM_KEY);

						//Make sure that moves only run once, as update fires on multiple containers.
						if (itemData && (this === parentElement || $.contains(this, parentElement))) {
							//Identify parents.
							sourceList = dataGet(element, PARENT_LIST_KEY);
							sourceIndex = dataGet(element, SOURCE_INDEX_KEY);
							targetList = dataGet(element.parentNode, LIST_KEY);
							targetIndex = ko.utils.arrayIndexOf(ui.item.parent().children(), element);
							targetParentItem = dataGet(element.parentNode, PARENT_ITEM_KEY);

							if (window.console && console.log) {
								console.log(
									'update: move from index', sourceIndex,
									'to', targetIndex,
									'source length:', sourceList().length,
									'target length:', targetList().length,
									element
								);
							}

							//Rendering is handled by manipulating the observableArray(s); ignore the dropped element.
							dataSet(element, ITEM_KEY, null);
							ui.item.remove();

							if (targetIndex >= 0) {
								//Remove the item from the original list.
								if (sourceList) {
									sourceList.splice(sourceIndex, 1);
								}
								//Add it to the new list.
								targetList.splice(targetIndex, 0, itemData);

								if (options.afterMove) {
									options.afterMove(itemData, targetParentItem);
								}
							}
						}
					}
				}) );

				dataSet(element, SORTABLE_CREATED_KEY, true);
			}, 0);

			//Destroy the nested sortable when Knockout removes the bound element.
			ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
				//Do not create the sortable if the element has been removed from DOM.
				clearTimeout(createTimeout);

				//Only call destroy if sortable has actually been created.
				if (dataGet(element, SORTABLE_CREATED_KEY)) {
					$(element).nestedSortable("destroy");
				}
			});

			return { 'controlsDescendantBindings': true };
		},

		update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
			var options = parseBindingOptions(valueAccessor, bindingContext);
			var innerBindingContext = prepareInnerBindingContext(options, bindingContext),
				templateOptions = prepareTemplateOptions(options);

			ko.bindingHandlers.template.update(
				element,
				function() { return templateOptions; },
				allBindingsAccessor,
				viewModel,
				innerBindingContext
			);
		}
	};

})(jQuery);
