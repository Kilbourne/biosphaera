( function ( $ ) {
	"use strict";
	
	var Dimensions = function (options) {
		this.init( 'dimensions', options, Dimensions.defaults );
	};

	$.fn.editableutils.inherit( Dimensions, $.fn.editabletypes.abstractinput );

	$.extend( Dimensions.prototype, {
		activate: function() {
			this.$input.find( 'input:first' ).focus();
		},

		value2input: function( value ) {
			if ( ! value ) {
				return;
			}
			this.$input.filter( '[name="length"]' ).val( value.length );
			this.$input.filter( '[name="width"]' ).val( value.width );
			this.$input.filter( '[name="height"]' ).val( value.height );
		},

		input2value: function() {
			return {
				length: this.$input.filter( '[name="length"]' ).val(),
				width: this.$input.filter( '[name="width"]' ).val(),
				height: this.$input.filter( '[name="height"]' ).val()
			};
		}
	} );

	var template;

	template += '<input type="text" class="form-control input-sm small-text" name="length" placeholder="Length">';
	template += '<input type="text" class="form-control input-sm small-text" name="width" placeholder="Width">';
	template += '<input type="text" class="form-control input-sm small-text" name="height" placeholder="Height">';

	Dimensions.defaults = $.extend( {}, $.fn.editabletypes.abstractinput.defaults, {
		tpl: template
	} );

	$.fn.editabletypes.dimensions = Dimensions;
} ( window.jQuery ) );