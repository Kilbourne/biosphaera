window.wp = window.wp || {};



( function( $, _ ) {

    var media = wp.media,
        l10n = media.view.l10n;




    _.extend( media.view.Attachment.Library.prototype, {

        buttons: {
            check  : true,
            edit   : true,
            remove : false, // TODO: consider 'delete' button
            attach : false // TODO: consider 'attach' button
        }
    });




    media.view.Attachment.emlGridViewDetails = media.view.Attachment.Details.extend({

        editAttachment: function( event ) {

            if ( this.controller.isModeActive( 'eml-grid' ) ) {
                if ( this.controller.isModeActive( 'edit' ) ) {

                    event.preventDefault();
                    this.controller.trigger( 'edit:attachment', this.model);
                }
            }
        },

        deleteAttachment: function( event ) {

            event.preventDefault();

            if ( confirm( l10n.warnDelete ) ) {
                this.model.destroy();
            }
        },

        trashAttachment: function( event ) {

            var selection = this.controller.state().get( 'selection' ),
                library = this.controller.state().get( 'library' );

            event.preventDefault();

            if ( media.view.settings.mediaTrash ) {

                this.model.set( 'status', 'trash' );
                this.model.save();
                selection.remove( this.model );
                library.remove( this.model );
            } else {
                this.model.destroy();
            }

            // Clean queries' cache
            media.model.Query.cleanQueries();

            selection.reset();
        },

        untrashAttachment: function( event ) {

            var selection = this.controller.state().get( 'selection' ),
                library = this.controller.state().get( 'library' );

            event.preventDefault();

            this.model.set( 'status', 'inherit' );
            this.model.save();
            library.remove( this.model );

            // Clean queries' cache
            media.model.Query.cleanQueries();

            selection.reset();
        }
    });




    media.view.MediaFrame.emlManage = media.view.MediaFrame.Select.extend({

        initialize: function() {

            var self = this;

            _.defaults( this.options, {
                title    : '',
                modal    : false,
                multiple : 'reset',
                state    : 'library',
                mode     : [ 'eml-grid', 'edit' ]
            });

            $( document ).on( 'click', '.add-new-h2', _.bind( this.addNewClickHandler, this ) );

            this.gridRouter = new media.view.MediaFrame.Manage.Router();

            // Call 'initialize' directly on the parent class.
            media.view.MediaFrame.Select.prototype.initialize.apply( this, arguments );

            // Append the frame view directly the supplied container.
            this.$el.appendTo( this.options.container );

            this.render();

            // Update the URL when entering search string (at most once per second)
            $( '#media-search-input' ).on( 'input', _.debounce( function(e) {
                var val = $( e.currentTarget ).val(), url = '';
                if ( val ) {
                    url += '?search=' + val;
                }
                self.gridRouter.navigate( self.gridRouter.baseUrl( url ) );
            }, 1000 ) );
        },

        createStates: function() {

            var options = this.options;

            if ( this.options.states ) {
                return;
            }

            this.states.add([

                new media.controller.Library({
                    library            : media.query( options.library ),
                    title              : options.title,
                    multiple           : options.multiple,

                    content            : 'browse',
                    toolbar            : 'bulk-edit',
                    menu               : false,
                    router             : false,

                    contentUserSetting : true,

                    searchable         : true,
                    filterable         : 'all',

                    autoSelect         : true,
                    idealColumnWidth   : $( window ).width() < 640 ? 135 : 150
                })
            ]);
        },

        bindHandlers: function() {

            media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments );

            this.on( 'toolbar:create:bulk-edit', this.createToolbar, this );
            this.on( 'toolbar:render:bulk-edit', this.selectionStatusToolbar, this );
            this.on( 'edit:attachment', this.openEditAttachmentModal, this );
        },

        selectionStatusToolbar: function( view ) {

            view.set( 'selection', new media.view.Selection({
                controller: this,
                collection: this.state().get('selection'),
                priority:   -40,
            }).render() );
        },

        addNewClickHandler: function( event ) {

            event.preventDefault();

            this.trigger( 'toggle:upload:attachment' );
        },

        browseContent: function( contentRegion ) {

            var state = this.state();

            this.$el.removeClass('hide-toolbar');

            // Browse our library of attachments.
            this.browserView = contentRegion.view = new media.view.AttachmentsBrowser({
                controller: this,
                collection: state.get('library'),
                selection:  state.get('selection'),
                model:      state,
                sortable:   state.get('sortable'),
                search:     state.get('searchable'),
                filters:    state.get('filterable'),
                date:       state.get('date'), // ???
                display:    state.has('display') ? state.get('display') : state.get('displaySettings'),
                dragInfo:   state.get('dragInfo'),

                idealColumnWidth: state.get('idealColumnWidth'),
                suggestedWidth:   state.get('suggestedWidth'),
                suggestedHeight:  state.get('suggestedHeight'),

                AttachmentView: state.get('AttachmentView')
            });

            this.browserView.on( 'ready', _.bind( this.bindDeferred, this ) );
        },

        bindDeferred: function() {

            if ( ! this.browserView.dfd ) {
                return;
            }
            this.browserView.dfd.done( _.bind( this.startHistory, this ) );
        },

        startHistory: function() {

            // Verify pushState support and activate
            if ( window.history && window.history.pushState ) {
                Backbone.history.start( {
                    root: _wpMediaGridSettings.adminUrl,
                    pushState: true
                } );
            }
        },

        openEditAttachmentModal: function( model ) {

            wp.media( {
                frame:       'edit-attachments',
                controller:  this,
                library:     this.state().get('library'),
                model:       model
            } );
        }
    });




    _.extend( media.view.UploaderInline.prototype, {

        show: function() {

            this.$el.removeClass( 'hidden' );
            if ( this.controller.browserView ) {
                this.controller.browserView.attachments.$el.css( 'top', this.$el.outerHeight() + 20 );
            }
        },

        hide: function() {

            this.$el.addClass( 'hidden' );
            if ( this.controller.browserView ) {
                this.controller.browserView.attachments.$el.css( 'top', 0 );
            }
        }
    });




    $( document ).ready( function() {

        media.frame = new media.view.MediaFrame.emlManage({
            container: $('#wp-media-grid')
        });
    });




    // TODO: move to PHP side
    $('body').addClass('eml-grid');


})( jQuery, _ );
