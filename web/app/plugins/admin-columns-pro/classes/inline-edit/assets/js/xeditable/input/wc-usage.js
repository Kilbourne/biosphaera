( function ( $ ) {
	"use strict";

	var WC_Usage = function (options) {
		this.init( 'wc_usage', options, WC_Usage.defaults );
	};

	$.fn.editableutils.inherit( WC_Usage, $.fn.editabletypes.abstractinput );

	$.extend( WC_Usage.prototype, {
		value2input: function( value ) {

			if ( ! value ) {
				return;
			}

			this.$input.find( '[name="usage_limit"]' ).val( value.usage_limit );
			this.$input.find( '[name="usage_limit_per_user"]' ).val( value.usage_limit_per_user );
		},

		input2value: function() {
			return {
				usage_limit: this.$input.find( '[name="usage_limit"]' ).val(),
				usage_limit_per_user: this.$input.find( '[name="usage_limit_per_user"]' ).val()
			};
		}
	} );

	var template = '';

	template += '<div>';

		template += '<div>';
		template += '<label>Usage limit per coupon</label>';
		template += '<input type="text" class="form-control input-sm small-text" name="usage_limit">';
		template += '</div>';

		template += '<div>';
		template += '<label>Usage limit per user</label>';
		template += '<input type="text" class="form-control input-sm small-text" name="usage_limit_per_user">';
		template += '</div>';

	template += '</div>';

	WC_Usage.defaults = $.extend( {}, $.fn.editabletypes.abstractinput.defaults, {
		tpl: template
	} );

	$.fn.editabletypes.wc_usage = WC_Usage;
} ( window.jQuery ) );