(function() {

	tinymce.PluginManager.add('tinyWYM', function(editor, url) {

		/**
		 * Converts attributes object to a string of attributes
		 *
		 * @param {object} attrsObj - The attributes parameter of a HTML element
		 *
		 * @return {string} - HTML attributes string, e.g. class="class" title="title"
		 */
		function attrsToString( attrsObj ) {

			var attrsString = '';

			for ( var i = 0; i < attrsObj.length; i++ ) {
				attrsString += attrsObj[i].name + '="' + attrsObj[i].value + '" ';
			}

			return attrsString;

		}

		/**
		 * Controls the 'twym_any_tag' button and keyboard shortcut
		 *
		 * Get either the current selection or current element if nothing is selected then executes openModal
		 *
		 * @uses openModal()
		 */
		function doAnyTag() {

			// Make sure editor has focus.
			editor.focus();

			// Get current selection/node
			var selection = editor.selection;

			// Set var selection to parent node of caret if no text is selected
			// or to selection contents if some text is selected
			selection = selection.isCollapsed() ? selection.getNode() : selection.getContent();

			// Create element array to pass to openModal function
			var element = [ selection, '', '' ];

			// Open modal window to edit element/selection
			openModal( 'button', element );

		}

		/**
		 * Converts an attributes string from text input to an attributes object
		 *
		 * @param {string} input - HTML attributes string, e.g. class="class" title="title"
		 *
		 * @return {object} - Name/Value attributes object
		 */
		function attrsToObject( input ) {

			// Set attributes input string to new variable
			var attrString = input;

			// Regex to find attribute names & values
			var regN = /[\b\w-]+(?==")/g; // Matches 'attr' or 'attr-name' when followed by '="'
			var regV = /="{1}[^"]+"{1}|(="")/g; // Matches anything between '="' & '"' or '=""' for empty attribute

			// Create two arrays; one for attribute names and the other values
			var attrNames  = attrString.match(regN);
			var attrValues = attrString.match(regV);

			// Strip the '="' & '"' from the beginning and end of attr values
			for (var i = 0; i < attrNames.length; i++) {

				if (attrValues[i] == '=""') { // If empty attr value set to empty string
					attrValues[i] = "";
				} else {
					attrValues[i] = attrValues[i].substring(2, attrValues[i].length - 1);
				}

			};

			// Create new object from attr name and value arrays
			var attrObject = ( function() {

				var obj       = {};
				var objString = "";

				// Create JSON string
				for ( var i = 0; i < attrNames.length; i++ ) {
					objString += '"' + attrNames[i] + '": ';
					objString += '"' + attrValues[i] + '"';

					// Add comma after each name/value pair except the last
					if ( i !== attrNames.length - 1 ) {
						objString += ', ';
					}
				}

				// Wrap JSON string in curly braces
				objString = '{' + objString + '}';

				// Convert JSON string to object
				obj = JSON.parse( objString );

				return obj;

			})();

			// Return new object of attribute names and values
			return attrObject;

		}

		/**
		 * Opens modal window to edit element
		 *
		 * This function handles the modal form for both the twym_any_tag button and alt clicking
		 * an element directly. When alt clicking, the elemnt clicked gets edited. When clicking
		 * the twym_any_tag button; 
		 *   1. If a selection is made, the selection gets wrapped in a new tag,
		 *   2. If no selection is made the element containing the current caret
		 *      position get wrapped in the new tag.
		 *
		 * On submition of the form the target node or selection is replaced by a new one
		 * containing the current node or selections contents.
		 *
		 * @param {string} event  - Whether clicking on the twym_any_tag button or alt clicking
		 *                          an element directly - 'altClick' or 'button'
		 * @param {array} element - Array containing the target HTML element or selection [0],
		 *                          tag name [1], and attributes string [2]
		 *
		 * @uses attrsToObject()
		 */
		function openModal( event, element ) {

			// Set items in element array
			var target    = element[0];
			var tagName   = element[1];
			var listAttrs = element[2];

			// Remove the data-mce and data-wym attributes so
			// they don't show in the modal input field
			var regA = /data-mce[^"]+="[^"]+"|data-wym-align="[^"]+"/g;
			listAttrs = listAttrs.replace( regA, "" );

			// Set title for modal form depending on whether the twym_any_tag
			// button is clicked or element is alt clicked directly
			var title;

			if ( event === 'altClick' ) {
				title = editor.getLang( 'twym_editor.title_edit_modal' );
			} else if ( event === 'button' ) {
				title = editor.getLang( 'twym_editor.title_create_modal' );
			}

			// Opens popup form for editing element tag and attributes
			editor.windowManager.open({
				title: title,
				classes: 'wym',
				minWidth: 320,
				body: [
				{
					type  : 'textbox',
					name  : 'tag',
					label : editor.getLang( 'twym_editor.label_tag' ),
					value : tagName
				},
				{
					type  : 'textbox',
					name  : 'attrs',
					multiline : true,
					minHeight : '100',
					label : editor.getLang( 'twym_editor.label_attributes' ),
					// tooltip : '';
					value : listAttrs.trim()
				}],

				buttons: [
				{
					text: editor.getLang( 'twym_editor.modal_cancel' ),
					classes: 'cancel',
					onclick: 'close',
					styles: 'color: red; float: right;'
				},
				{
					text: editor.getLang( 'twym_editor.modal_submit' ),
					classes: 'submit widget btn',
					onclick: 'submit',
				}],
				onsubmit: function(s) {

					// Set input from modal form to variables
					// If input is empty set to empty string
					var newTag   = s.data.tag ? s.data.tag : "";
					var newAttrs = s.data.attrs ? " " + s.data.attrs : "";

					// Trigger alert if tag field is not set then reopen form
					if ( ! newTag ) {
						// target = target.innerHTML;
						editor.windowManager.alert( editor.getLang( 'twym_editor.alert_no_tag' ), function() {
							openModal( event, element );
						});
						return;
					}

					// Set contents for the new node/selection. Allows the new node/selection
					// to inherit the contents of the current node/selection
					var nodeContents;

					if ( event === 'altClick' ) {

						// Set nodeContents to innerHTML so target element is changed
						nodeContents = target.innerHTML;

					} else if ( event === 'button' ) {

						if ( typeof target === 'object' ) { // No selection

							// Set nodeContents to outerHTML so target element is wrapped
							nodeContents = target.outerHTML;	

						} else if ( typeof target === 'string' ) { // Selection

							// Create new element around selection
							var newString = '<' + newTag + newAttrs + '>' + target + '</' + newTag + '>';

							// Replaces selection with newString instead of creating new node
							editor.insertContent( newString );

							return;

						}

					}

					// Convert attributes text input into object (see attrsToObject() function)
					var attrsObject = newAttrs ? attrsToObject(newAttrs) : {};

					// Create new node based on user input
					var newNode = editor.dom.create(newTag, attrsObject, nodeContents);

					// Replace old node with new
					editor.dom.replace(newNode, target);

				}

			});

		}

		/**
		 * Add custom class to editor <html> and filter out the custom data-wym attributes
		 *
		 */
		editor.on( 'PreInit', function() {

			// Add custom body class to editor
			editor.dom.addClass( editor.$( 'html' ), 'tiny-wym' );

			// Filter out the data-wym attribute in text output
			editor.serializer.addAttributeFilter( 'href', function( nodes, name ) {

				var i = nodes.length;

				while ( i-- ) {
					node = nodes[i];
					node.attr( 'data-wym-align', null );
				}

			});

		});

		/**
		 * Add the data-wym attribute to links with an image as a child
		 *
		 * This is so links with images inside can be styled differently to text links
		 * to allow proper floating.
		 *
		 */
		editor.on( 'NodeChange', function( event ) {

			// Find all images and number of images
			var images = editor.$( 'img' );
			var i = images.length;

			while ( i-- ) {

				// If the image parent is a link, get alignment and
				// add it as a data attribute to the link
				if ( images[i].parentElement.nodeName === "A" ) {

					// Get various image attributes
					var classes   = images[i].className;
					var alignment = classes.match( /align[a-z]+/ );

					if ( alignment !== null ) {
						editor.$( images[i].parentElement ).attr( 'data-wym-align', alignment[0] );
					}

				}

			}

		});

		/**
		 * Add anyTag button
		 *
		 * Allow users to wrap selection or current element (from caret position) in any HTML tag
		 *
		 * @uses doAnyTag()
		 */
		editor.addButton( 'twym_any_tag', {
			title: editor.getLang( 'twym_editor.tooltip_button' ),
			icon: 'wp_code',
			classes: 'wym-snippet widget btn',
			onclick: function() {
				doAnyTag();
			}
		});

		/**
		 * Keyboard shortcut for the 'twym_any_tag' button
		 *
		 * @uses doAnyTag()
		 */
		editor.addShortcut( 'ctrl+T', 'Create/Edit Tag shortcut', function() {
			doAnyTag();
		});

		/**
		 * Allow user to edit any element & attributes by alt-clicking or
		 * unwrap an element by shift+alt-clicking. 
		 *
		 * @uses openModal()
		 */
		editor.on( 'click', function(e) {

			// Make sure not clicking editor <body>
			if ( e.target.tagName === 'BODY' ) {
				return;
			}

			if ( e.altKey ) {

				if ( e.shiftKey ) {
					e.preventDefault();

					// Collopase selection after shift clicking
					editor.selection.collapse();

					// Unwrap click target then end function
					editor.$( e.target ).contents().unwrap();
					return;
				}

				// Get click target, tag name, and attributes
				var target    = editor.$( e.target ), target = target[0];
				var tagName   = target.tagName.toLowerCase();
				var listAttrs = attrsToString( target.attributes );

				// Create array of relevant info for the node
				var element = [ target, tagName, listAttrs ];

				// Open modal window to edit target element
				openModal( 'altClick', element );
			}
		});

		/************************************
		 *** tinyWYM toggle functionality ***
		 ************************************/
		/**
		 * Toggle tiny-wym class on editor's html element to hide and show
		 * tinyWYM styles. 
		 */
		function twymToggle() {
			editor.dom.toggleClass( editor.$( 'html' ), 'tiny-wym' );
		}

		/**
		 * Toggle tinyWYM button
		 *
		 * Allow users to toggle the tinyWYM styles by toggling the tiny-wym class
		 * on the editor's html element. Also toggles the button icon also to show
		 * relevant state.
		 *
		 * @uses twymToggle()
		 */
		editor.addButton( 'twym_toggle', {
			title: editor.getLang( 'twym_editor.tooltip_toggle' ),
			icon: 'twym-hide',
			classes: 'wym-toggle widget btn',
			onclick: function() {
				// Toggle tinyWYM
				twymToggle();

				// Change the button icon depending on whether tinyWYM is shown or not.
				if ( editor.dom.hasClass( editor.$( 'html' ), 'tiny-wym' ) ) {
					this.icon( 'twym-hide' );
				} else {
					this.icon( 'twym-show' );
				}
			}
		});

		/**
		 * Keyboard shorcut for toggling tinyWYM styles
		 *
		 * @uses twymToggle()
		 */
		editor.addShortcut( 'ctrl+W', 'Toggle tinyWYM styles', function() {
			twymToggle();

			var buttons = editor.theme.panel.find( '.toolbar .btn' );

			for ( var i = 0; i < buttons.length; i++ ) {
				
				var button = buttons[i];

				if ( button.classes.cls.indexOf( 'wym-toggle' ) !== -1 ) {

					if ( button.settings.icon === 'twym-show' ) {
						button.icon( 'twym-hide' );
					} else {
						button.icon( 'twym-show' );
					}

				}

			};
		});

	});

})();