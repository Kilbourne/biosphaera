<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$tab_title      = $product_permalink = $no_form_plugin = $inquiry_form = $where_show = $yit_contact_form = $contact_form_7 = $gravity_forms = $admin_override = $admin_override_exclusion = $admin_override_exclusion_reverse = $custom_button_url = '';
$query_args     = array(
	'page' => isset( $_GET['page'] ) ? $_GET['page'] : '',
	'tab'  => 'exclusions',
);
$exclusions_url = esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) );

if ( YITH_WCTM()->exists_inquiry_forms() ) {

	$inquiry_form = array(
		'name'    => __( 'Form Plugin', 'yith-woocommerce-catalog-mode' ),
		'type'    => 'select',
		'desc'    => __( 'Choose from which activated plugin you want to display the inquiry form in product page', 'yith-woocommerce-catalog-mode' ),
		'options' => YITH_WCTM()->get_active_form_plugins(),
		'default' => 'none',
		'id'      => 'ywctm_inquiry_form_type'
	);
	$where_show   = array(
		'name'    => __( 'Form Position', 'yith-woocommerce-catalog-mode' ),
		'type'    => 'select',
		'desc'    => __( 'Choose where to show the inquiry form in product page', 'yith-woocommerce-catalog-mode' ),
		'options' => array(
			'tab' => __( 'Tab', 'yith-woocommerce-catalog-mode' ),
			'15'  => __( 'After price', 'yith-woocommerce-catalog-mode' ),
			'25'  => __( 'After short description', 'yith-woocommerce-catalog-mode' ),
			'35'  => __( 'After "Add to cart" button', 'yith-woocommerce-catalog-mode' ),
		),
		'default' => 'tab',
		'id'      => 'ywctm_inquiry_form_where_show'
	);
	$yit_contact_form  = array(
		'name'    => '',
		'type'    => 'select',
		'desc'    => __( 'Choose the form to display', 'yith-woocommerce-catalog-mode' ),
		'options' => apply_filters( 'yit_get_contact_forms', array() ),
		'id'      => 'ywctm_inquiry_yit_contact_form_id',
		'class'   => 'yit-contact-form'
	);
	$contact_form_7    = array(
		'name'    => '',
		'type'    => 'select',
		'desc'    => __( 'Choose the form to display', 'yith-woocommerce-catalog-mode' ),
		'options' => apply_filters( 'wpcf7_get_contact_forms', array() ),
		'id'      => 'ywctm_inquiry_contact_form_7_id',
		'class'   => 'contact-form-7'
	);
	$gravity_forms     = array(
		'name'    => '',
		'type'    => 'select',
		'desc'    => __( 'Choose the form to display', 'yith-woocommerce-catalog-mode' ),
		'options' => apply_filters( 'gravity_get_contact_forms', array() ),
		'id'      => 'ywctm_inquiry_gravity_forms_id',
		'class'   => 'gravity-forms'
	);
	$tab_title         = array(
		'type'              => 'text',
		'name'              => __( 'Tab title', 'yith-woocommerce-catalog-mode' ),
		'id'                => 'ywctm_inquiry_form_tab_title',
		'default'           => __( 'Inquiry form', 'yith-woocommerce-catalog-mode' ),
		'custom_attributes' => array(
			'required' => 'required'
		)
	);
	$product_permalink = array(
		'name'    => __( 'Product Permalink', 'yith-woocommerce-catalog-mode' ),
		'type'    => 'checkbox',
		'desc'    => __( 'Add the product permalink to the email body (it works only with Contact Form 7 and Gravity Forms).', 'yith-woocommerce-catalog-mode' ),
		'id'      => 'ywctm_inquiry_product_permalink',
		'default' => 'no',
	);

	if ( class_exists( 'SitePress' ) ) {

		$contact_form_7   = array(
			'name'    => '',
			'type'    => 'ywctm-languages-form-table',
			'options' => apply_filters( 'wpcf7_get_contact_forms', array() ),
			'id'      => 'ywctm_inquiry_contact_form_7_id_wpml',
			'class'   => 'contact-form-7'
		);
		$gravity_forms    = array(
			'name'    => '',
			'type'    => 'ywctm-languages-form-table',
			'options' => apply_filters( 'gravity_get_contact_forms', array() ),
			'id'      => 'ywctm_inquiry_gravity_forms_id_wpml',
			'class'   => 'gravity-forms'
		);
		$yit_contact_form = array(
			'name'    => '',
			'type'    => 'ywctm-languages-form-table',
			'options' => apply_filters( 'yit_get_contact_forms', array() ),
			'id'      => 'ywctm_inquiry_yit_contact_form_id_wpml',
			'class'   => 'yit-contact-form'
		);

	}

} else {

	$no_form_plugin = __( 'To use this feature, YIT Contact Form, Contact Form 7 or Gravity Forms must be installed and activated.', 'yith-woocommerce-catalog-mode' );

}

if ( class_exists( 'SitePress' ) ) {

	$custom_button_url = array(
		'name' => __( 'URL Link', 'yith-woocommerce-catalog-mode' ),
		'type' => 'ywctm-languages-url-table',
		'id'   => 'ywctm_button_url_wpml',
		'desc' => __( 'Specify the URL (Optional)', 'yith-woocommerce-catalog-mode' ),
	);

} else {

	$custom_button_url = array(
		'name'    => __( 'URL Link', 'yith-woocommerce-catalog-mode' ),
		'type'    => 'text',
		'desc'    => __( 'Specify the URL (Optional)', 'yith-woocommerce-catalog-mode' ),
		'id'      => 'ywctm_button_url',
		'default' => '',
	);

}

if ( YITH_WCTM()->is_multivendor_active() ) {

	$vendor_query_args     = array(
		'page' => isset( $_GET['page'] ) ? $_GET['page'] : '',
		'tab'  => 'vendors',
	);
	$vendor_exclusions_url = esc_url( add_query_arg( $vendor_query_args, admin_url( 'admin.php' ) ) );

	$admin_override = array(
		'name'          => __( 'Admin Override', 'yith-woocommerce-catalog-mode' ),
		'type'          => 'checkbox',
		'desc'          => __( 'Administrator settings override the vendors\' settings', 'yith-woocommerce-catalog-mode' ),
		'id'            => 'ywctm_admin_override',
		'default'       => 'no',
		'checkboxgroup' => 'start'

	);

	$admin_override_exclusion = array(
		'type'          => 'checkbox',
		'desc'          => sprintf( __( 'Do not apply to vendors in the  %s"Vendor Exclusion List" %s(Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $vendor_exclusions_url . '">', '</a>' ),
		'id'            => 'ywctm_admin_override_exclusion',
		'default'       => 'no',
		'checkboxgroup' => ''
	);

	$admin_override_exclusion_reverse = array(
		'type'          => 'checkbox',
		'desc'          => sprintf( __( 'Apply only to vendors  in the %s"Vendor Exclusion List"%s (Reverse Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $vendor_exclusions_url . '">', '</a>' ),
		'id'            => 'ywctm_admin_override_reverse',
		'default'       => 'no',
		'checkboxgroup' => 'end',
	);

}

return array(
	'premium-settings' => array(
		'ywctm_general_title' => array(
			'name' => __( 'General Settings', 'yith-woocommerce-catalog-mode' ),
			'type' => 'title',
		),
		'ywctm_enable_plugin' => array(
			'name'    => __( 'Enable YITH WooCommerce Catalog Mode', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'checkbox',
			'desc'    => '',
			'id'      => 'ywctm_enable_plugin',
			'default' => 'yes',
		),
		'ywctm_general_end'   => array(
			'type' => 'sectionend',
		),

		'ywctm_catalog_mode_title'                         => array(
			'name' => __( 'Catalog Mode Settings', 'yith-woocommerce-catalog-mode' ),
			'type' => 'title',
		),
		'ywctm_catalog_mode_hide_price'                    => array(
			'name'    => __( 'Price and "Add to cart" button', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Hide', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_hide_price',
			'default' => 'no',
		),
		'ywctm_catalog_mode_price_alternative_text'        => array(
			'type'    => 'text',
			'desc'    => __( 'Insert a text that will replace the product price (optional)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_exclude_price_alternative_text',
			'default' => '',
		),
		'ywctm_catalog_mode_variable_products_atc'         => array(
			'type'          => 'checkbox',
			'desc'          => __( 'Hide product variations', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_hide_variations',
			'default'       => 'no',
			'checkboxgroup' => 'start',
			'class'         => 'ywctm-variations-price'

		),
		'ywctm_catalog_mode_exclude_products_full'         => array(
			'type'          => 'checkbox',
			'desc'          => sprintf( __( 'Show price and "Add to cart" options in items of the %s"Exclusion List"%s (Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $exclusions_url . '">', '</a>' ),
			'id'            => 'ywctm_exclude_hide_price',
			'default'       => 'no',
			'checkboxgroup' => ''

		),
		'ywctm_catalog_mode_exclude_products_reverse_full' => array(
			'type'          => 'checkbox',
			'desc'          => sprintf( __( 'Hide price only in items of the %s"Exclusion List"%s (Reverse Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $exclusions_url . '">', '</a>' ),
			'id'            => 'ywctm_exclude_hide_price_reverse',
			'default'       => 'no',
			'checkboxgroup' => '',
			'class'         => 'ywctm-full'
		),
		'ywctm_catalog_mode_exclude_products_reverse_atc'  => array(
			'type'          => 'checkbox',
			'desc'          => sprintf( __( 'Hide "Add to cart" button only in items of the %s"Exclusion List"%s (Reverse Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $exclusions_url . '">', '</a>' ),
			'id'            => 'ywctm_exclude_hide_add_to_cart_reverse',
			'default'       => 'no',
			'checkboxgroup' => 'end',
			'class'         => 'ywctm-reverse-full ywctm-full'
		),
		'ywctm_catalog_mode_disable_add_to_cart_single'    => array(
			'name'          => __( '"Add to cart" button', 'yith-woocommerce-catalog-mode' ),
			'type'          => 'checkbox',
			'desc'          => __( 'Hide in product detail page', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_hide_add_to_cart_single',
			'default'       => 'no',
			'checkboxgroup' => 'start'
		),
		'ywctm_catalog_mode_disable_add_to_cart_loop'      => array(
			'type'          => 'checkbox',
			'desc'          => __( 'Hide in other shop pages', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_hide_add_to_cart_loop',
			'default'       => 'no',
			'checkboxgroup' => ''

		),
		'ywctm_catalog_mode_variable_products'             => array(
			'name'          => __( 'Variable products', 'yith-woocommerce-catalog-mode' ),
			'type'          => 'checkbox',
			'desc'          => __( 'Hide product variations', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_hide_variations',
			'default'       => 'no',
			'checkboxgroup' => '',
			'class'         => 'ywctm-variations ywctm-variations-atc'
		),
		'ywctm_catalog_mode_exclude_products'              => array(
			'type'          => 'checkbox',
			'desc'          => sprintf( __( 'Show "Add to cart" in items of the  %s"Exclusion List" %s(Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $exclusions_url . '">', '</a>' ),
			'id'            => 'ywctm_exclude_hide_add_to_cart',
			'default'       => 'no',
			'checkboxgroup' => ''

		),
		'ywctm_catalog_mode_exclude_products_reverse'      => array(
			'type'          => 'checkbox',
			'desc'          => sprintf( __( 'Hide "Add to cart" button only in items of the %s"Exclusion List"%s (Reverse Exclusion)', 'yith-woocommerce-catalog-mode' ), '<a href="' . $exclusions_url . '">', '</a>' ),
			'id'            => 'ywctm_exclude_hide_add_to_cart_reverse',
			'default'       => 'no',
			'checkboxgroup' => 'end',
			'class'         => 'ywctm-reverse-atc ywctm-atc'
		),
		'ywctm_catalog_mode_hide_price_users'              => array(
			'name'    => __( 'Affected users', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'select',
			'desc'    => __( 'Users who will not see the price and the "Add to cart" button', 'yith-woocommerce-catalog-mode' ),
			'options' => array(
				'all'          => __( 'All users', 'yith-woocommerce-catalog-mode' ),
				'unregistered' => __( 'Unregistered users only', 'yith-woocommerce-catalog-mode' ),
				'country'      => __( 'Selected countries', 'yith-woocommerce-catalog-mode' ),
			),
			'default' => 'all',
			'id'      => 'ywctm_hide_price_users'
		),
		'ywctm_catalog_mode_hide_countries'                => array(
			'name'        => __( 'Select countries', 'yith-woocommerce-catalog-mode' ),
			'id'          => 'ywctm_hide_countries',
			'type'        => 'yith-wc-country-select',
			'placeholder' => __( 'Search for a country&hellip;', 'yith-woocommerce-catalog-mode' ),
			'desc'        => __( 'Users from these countries will not see price and "Add to cart" button', 'yith-woocommerce-catalog-mode' ),
			'multiple'    => 'true',
		),
		'ywctm_catalog_mode_hide_countries_reverse'        => array(
			'name'    => __( 'Reverse Selection', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Users from countries that have not been listed above will not see price and "Add to cart" button', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_hide_countries_reverse',
			'default' => 'no',
		),

		'ywctm_catalog_mode_admin_view'                       => array(
			'name'    => __( 'Admin View', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Enable Catalog Mode also for administrators', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_admin_view',
			'default' => 'yes',
		),
		'ywctm_catalog_mode_admin_override'                   => $admin_override,
		'ywctm_catalog_mode_admin_override_exclusion'         => $admin_override_exclusion,
		'ywctm_catalog_mode_admin_override_exclusion_reverse' => $admin_override_exclusion_reverse,
		'ywctm_catalog_mode_section_end'                      => array(
			'type' => 'sectionend',
		),

		'ywctm_form_section_title'     => array(
			'name' => __( 'Inquiry Form', 'yith-woocommerce-catalog-mode' ),
			'type' => 'title',
			'desc' => $no_form_plugin,
		),
		'ywctm_form_inquiry_form'      => $inquiry_form,
		'ywctm_form_yit_contact_form'  => $yit_contact_form,
		'ywctm_form_contact_form_7'    => $contact_form_7,
		'ywctm_form_gravity_forms'     => $gravity_forms,
		'ywctm_form_where_show'        => $where_show,
		'ywctm_form_tab_title'         => $tab_title,
		'ywctm_form_product_permalink' => $product_permalink,
		'ywctm_form_section_end'       => array(
			'type' => 'sectionend',
		),

		'ywctm_button_section_title'             => array(
			'name' => __( 'Custom Button', 'yith-woocommerce-catalog-mode' ),
			'type' => 'title',
			'desc' => __( 'To use a custom button, you have to hide the "Add to cart" button and/or the price', 'yith-woocommerce-catalog-mode' ),
		),
		'ywctm_button_enable_custom_button'      => array(
			'name'          => __( 'Custom button', 'yith-woocommerce-catalog-mode' ),
			'type'          => 'checkbox',
			'desc'          => __( 'Show in product detail page', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_custom_button',
			'default'       => 'no',
			'checkboxgroup' => 'start'
		),
		'ywctm_button_enable_custom_button_loop' => array(
			'name'          => __( 'Custom button', 'yith-woocommerce-catalog-mode' ),
			'type'          => 'checkbox',
			'desc'          => __( 'Show in shop pages', 'yith-woocommerce-catalog-mode' ),
			'id'            => 'ywctm_custom_button_loop',
			'default'       => 'no',
			'checkboxgroup' => 'end'
		),
		'ywctm_button_custom_button_text'        => array(
			'name'    => __( 'Button text', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'text',
			'default' => '',
			'id'      => 'ywctm_button_text',
		),
		'ywctm_button_custom_button_color'       => array(
			'name'    => __( 'Color', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'color',
			'default' => '#000000',
			'desc'    => __( 'Color of the text (Optional)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_button_color',
		),
		'ywctm_button_custom_button_hover'       => array(
			'name'    => __( 'Hover Color', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'color',
			'default' => '#FF0000',
			'desc'    => __( 'Color of the text on mouse hover (Optional)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_button_hover',
		),
		'ywctm_button_custom_button_bg_color'    => array(
			'name'    => __( 'Background Color', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'color',
			'default' => '#FFFFFF',
			'desc'    => __( 'Color of the background (Optional)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_button_bg_color',
		),
		'ywctm_button_custom_button_bg_hover'    => array(
			'name'    => __( 'Background Hover Color', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'color',
			'default' => '#CCCCCC',
			'desc'    => __( 'Color of the background on mouse hover (Optional)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_button_bg_hover',
		),
		'ywctm_button_custom_button_icon'        => array(
			'name'    => __( 'Icon', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'icon',
			'desc'    => __( 'Show optional icon', 'yith-woocommerce-catalog-mode' ),
			'options' => array(
				'select' => array(
					'none'   => __( 'None', 'yith-woocommerce-catalog-mode' ),
					'icon'   => __( 'Theme Icon', 'yith-woocommerce-catalog-mode' ),
					'custom' => __( 'Custom Icon', 'yith-woocommerce-catalog-mode' )
				),
				'icon'   => YIT_Plugin_Common::get_icon_list(),
			),
			'id'      => 'ywctm_button_icon',
			'default' => array(
				'select' => 'none',
				'icon'   => 'retinaicon-font:retina-the-essentials-082',
				'custom' => ''
			)
		),
		'ywctm_button_custom_button_url_type'    => array(
			'name'    => __( 'URL Protocol Type', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'select',
			'desc'    => __( 'Specify the type of the URL (Optional)', 'yith-woocommerce-catalog-mode' ),
			'options' => array(
				'generic' => __( 'Generic URL', 'yith-woocommerce-catalog-mode' ),
				'mailto'  => __( 'E-mail address', 'yith-woocommerce-catalog-mode' ),
				'tel'     => __( 'Phone number', 'yith-woocommerce-catalog-mode' ),
				'skype'   => __( 'Skype contact', 'yith-woocommerce-catalog-mode' ),
			),
			'default' => 'generic',
			'id'      => 'ywctm_button_url_type'
		),
		'ywctm_button_custom_button_url'         => $custom_button_url,
		'ywctm_button_custom_button_url_target'  => array(
			'name'    => '',
			'type'    => 'checkbox',
			'desc'    => __( 'Open link in new tab (Only for Generic URL)', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_button_url_target',
			'default' => 'no',
		),
		'ywctm_button_section_end'               => array(
			'type' => 'sectionend',
		),

		'ywctm_other_section_title'          => array(
			'name' => __( 'Other Settings', 'yith-woocommerce-catalog-mode' ),
			'type' => 'title',
			'desc' => '',
		),
		'ywctm_other_disable_review'         => array(
			'name'    => __( 'Product Reviews', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'select',
			'desc'    => '',
			'id'      => 'ywctm_disable_review',
			'default' => 'no',
			'options' => array(
				'no'           => __( 'Enabled', 'yith-woocommerce-catalog-mode' ),
				'all'          => __( 'Disabled for all users', 'yith-woocommerce-catalog-mode' ),
				'unregistered' => __( 'Disabled only for unregistered users', 'yith-woocommerce-catalog-mode' )
			)
		),
		'ywctm_other_disable_cart_in_header' => array(
			'name'    => __( 'Disable shop', 'yith-woocommerce-catalog-mode' ),
			'type'    => 'checkbox',
			'desc'    => __( 'Hide and disable "Cart" page, "Checkout" page and all "Add to Cart" buttons', 'yith-woocommerce-catalog-mode' ),
			'id'      => 'ywctm_hide_cart_header',
			'default' => 'no',
		),
		'ywctm_other_section_end'            => array(
			'type' => 'sectionend',
		)
	)
);