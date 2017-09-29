( function ( $ ) {
	"use strict";
	
	var WC_Price = function (options) {
		this.init( 'wc_price', options, WC_Price.defaults );
	};

	$.fn.editableutils.inherit( WC_Price, $.fn.editabletypes.abstractinput );

	$.extend( WC_Price.prototype, {
		render: function() {
			var container = this.$input;

			container.find( '.toggle-price-schedule' ).click( function( e ) {
				e.preventDefault();

				var el = container.find( '.price-schedule' );

				if ( el.is( ':visible' ) ) {
					el.hide();
					$( this ).text( 'Schedule' );
					container.find( '.price-schedule input' ).val( '' );
				}
				else {
					el.show();
					$( this ).text( 'Cancel' );
				}
			} );

			var dates = $( '.price-schedule input' ).datepicker( {
				defaultDate: '',
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
				showOn: 'button',
				buttonImage: woocommerce_admin_meta_boxes.calendar_image,
				buttonImageOnly: true,
				onSelect: function( selectedDate ) {
					var option = $( this ).is( '[name="sale_price_dates_from"]') ? 'minDate' : 'maxDate';

					var instance = $( this ).data( 'datepicker' );
					var date = $.datepicker.parseDate(
						instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
						selectedDate,
						instance.settings
					);

					dates.not( this ).datepicker( 'option', option, date );
				}
			} );
		},

		activate: function() {
			var value = this.input2value();

			this.$input.find( 'input:first' ).focus();

			if ( value.sale_price_dates_from || value.sale_price_dates_to ) {
				this.$input.find( '.toggle-price-schedule' ).trigger( 'click' );
			}
		},

		value2input: function( value ) {
			if ( ! value ) {
				return;
			}

			this.$input.find( '[name="regular_price"]' ).val( value.regular_price );
			this.$input.find( '[name="sale_price"]' ).val( value.sale_price );
			this.$input.find( '[name="sale_price_dates_from"]' ).val( value.sale_price_dates_from );
			this.$input.find( '[name="sale_price_dates_to"]' ).val( value.sale_price_dates_to );
		},

		input2value: function() {
			return {
				regular_price: this.$input.find( '[name="regular_price"]' ).val(),
				sale_price: this.$input.find( '[name="sale_price"]' ).val(),
				sale_price_dates_from: this.$input.find( '[name="sale_price_dates_from"]' ).val(),
				sale_price_dates_to: this.$input.find( '[name="sale_price_dates_to"]' ).val()
			};
		}
	} );

	var template = '';
	var currency_symbol = '$';

	if ( typeof woocommerce_admin_meta_boxes != 'undefined' && typeof woocommerce_admin_meta_boxes.currency_format_symbol != 'undefined' ) {
		currency_symbol = woocommerce_admin_meta_boxes.currency_format_symbol;
	}

	template += '<div>';

		template += '<div>';
		template += '<label>Regular (' + currency_symbol + ')</label>';
		template += '<input type="text" class="form-control input-sm" name="regular_price">';
		template += '</div>';

		template += '<div>';
		template += '<label>Sale (' + currency_symbol + ')</label>';
		template += '<input type="text" class="form-control input-sm" name="sale_price">';
		template += '</div>';

		template += '<div class="price-schedule">';

			template += '<div>';
			template += '<label>Sale from</label>';
			template += '<input type="text" placeholder="yyyy-mm-dd" class="form-control input-sm" name="sale_price_dates_from">';
			template += '</div>';

			template += '<div>';
			template += '<label>Sale to</label>';
			template += '<input type="text" placeholder="yyyy-mm-dd" class="form-control input-sm" name="sale_price_dates_to">';
			template += '</div>';

		template += '</div>';

		template += '<div>';
		template += '<a href="#" class="toggle-price-schedule">Schedule</a>';
		template += '</div>';

	template += '</div>';

	WC_Price.defaults = $.extend( {}, $.fn.editabletypes.abstractinput.defaults, {
		tpl: template
	} );

	$.fn.editabletypes.wc_price = WC_Price;
} ( window.jQuery ) );