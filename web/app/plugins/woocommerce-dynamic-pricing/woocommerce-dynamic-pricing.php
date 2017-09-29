<?php

/*
  Plugin Name: WooCommerce Dynamic Pricing
  Plugin URI: http://www.woothemes.com/woocommerce
  Description: WooCommerce Dynamic Pricing lets you configure dynamic pricing rules for products, categories and members. For WooCommerce 1.4+
  Version: 2.12.2
  Author: Lucas Stark
  Author URI: http://lucasstark.com
  Requires at least: 3.3
  Tested up to: 4.6.1

  Copyright: © 2009-2016 Lucas Stark.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Required functions
 */
if (!function_exists('woothemes_queue_update')) {
    require_once('woo-includes/woo-functions.php');
}

/**
 * Plugin updates
 */
woothemes_queue_update(plugin_basename(__FILE__), '9a41775bb33843f52c93c922b0053986', '18643');

if (is_woocommerce_active()) {


    /**
     * Localisation
     * */
    load_plugin_textdomain('wc_pricing', false, dirname(plugin_basename(__FILE__)) . '/languages/');


    /**
     * Boot up dynamic pricing
     */
    WC_Dynamic_Pricing::init();
}

class WC_Dynamic_Pricing
{

    /**
     * @var WC_Dynamic_Pricing
     */
    private static $instance;

    public static function init()
    {
        if (self::$instance == null) {
            self::$instance = new WC_Dynamic_Pricing();
        }
    }

    /**
     * @return WC_Dynamic_Pricing The instance of the plugin.
     */
    public static function instance()
    {
        if (self::$instance == null) {
            self::init();
        }

        return self::$instance;
    }

    private $cached_adjustments = array();

    public $modules = array();

    public $db_version = '2.1';

    public function __construct()
    {
        add_action('woocommerce_cart_loaded_from_session', array(&$this, 'on_cart_loaded_from_session'), 98, 1);

        //Add the actions dynamic pricing uses to trigger price adjustments
        add_action('woocommerce_before_calculate_totals', array(&$this, 'on_calculate_totals'), 98, 1);


        if (is_admin()) {
            require 'admin/admin-init.php';

            //Include and boot up the installer.
            include 'classes/class-wc-dynamic-pricing-installer.php';
            WC_Dynamic_Pricing_Installer::init();

        }

        //Include additional integrations
        if (wc_dynamic_pricing_is_groups_active()) {
            include 'integrations/groups/groups.php';
        }

        //Paypal express
        include 'integrations/paypal-express.php';
        include 'classes/class-wc-dynamic-pricing-compatibility.php';

        if (!is_admin() || defined('DOING_AJAX')) {
            //Include helper classes
            include 'classes/class-wc-dynamic-pricing-context.php';
            include 'classes/class-wc-dynamic-pricing-counter.php';
            include 'classes/class-wc-dynamic-pricing-tracker.php';
            include 'classes/class-wc-dynamic-pricing-cart-query.php';


            include 'classes/class-wc-dynamic-pricing-adjustment-set.php';
            include 'classes/class-wc-dynamic-pricing-adjustment-set-category.php';
            include 'classes/class-wc-dynamic-pricing-adjustment-set-product.php';
            include 'classes/class-wc-dynamic-pricing-adjustment-set-totals.php';
            include 'classes/class-wc-dynamic-pricing-adjustment-set-taxonomy.php';

            //The base pricing module.
            include 'classes/modules/class-wc-dynamic-pricing-module-base.php';

            //Include the advanced pricing modules.
            include 'classes/modules/class-wc-dynamic-pricing-advanced-base.php';
            include 'classes/modules/class-wc-dynamic-pricing-advanced-product.php';
            include 'classes/modules/class-wc-dynamic-pricing-advanced-category.php';
            include 'classes/modules/class-wc-dynamic-pricing-advanced-totals.php';
            include 'classes/modules/class-wc-dynamic-pricing-advanced-taxonomy.php';

            //Include the simple pricing modules.
            include 'classes/modules/class-wc-dynamic-pricing-simple-base.php';
            include 'classes/modules/class-wc-dynamic-pricing-simple-product.php';
            include 'classes/modules/class-wc-dynamic-pricing-simple-category.php';
            include 'classes/modules/class-wc-dynamic-pricing-simple-membership.php';
            include 'classes/modules/class-wc-dynamic-pricing-simple-taxonomy.php';


            //Include the UX module - This controls the display of discounts on cart items and products.
            include 'classes/class-wc-dynamic-pricing-frontend-ux.php';


            //Boot up the instances of the pricing modules
            $modules['advanced_product'] = WC_Dynamic_Pricing_Advanced_Product::instance();
            $modules['advanced_category'] = WC_Dynamic_Pricing_Advanced_Category::instance();

            $modules['simple_product'] = WC_Dynamic_Pricing_Simple_Product::instance();
            $modules['simple_category'] = WC_Dynamic_Pricing_Simple_Category::instance();
            $modules['simple_membership'] = WC_Dynamic_Pricing_Simple_Membership::instance();

            if (wc_dynamic_pricing_is_groups_active()) {
                include 'integrations/groups/class-wc-dynamic-pricing-simple-group.php';
                $modules['simple_group'] = WC_Dynamic_Pricing_Simple_Group::instance();
            }

            if (wc_dynamic_pricing_is_memberships_active()) {
                include 'integrations/woocommerce-memberships.php';
                WC_Dynamic_Pricing_Memberships_Integration::register();
            }

            $modules['advanced_totals'] = WC_Dynamic_Pricing_Advanced_Totals::instance();

            $this->modules = apply_filters('wc_dynamic_pricing_load_modules', $modules);


            /* Boot up required classes */
            WC_Dynamic_Pricing_Context::register();

            //Initialize the dynamic pricing counter.  Records various counts when items are restored from session.
            WC_Dynamic_Pricing_Counter::register();

            //Initialize the FrontEnd UX modifications
            WC_Dynamic_Pricing_FrontEnd_UX::init();


            add_action('plugins_loaded', array($this, 'on_plugins_loaded'), 0);

            //Filters for simple adjustment types
            add_action('woocommerce_before_mini_cart', array($this, 'remove_price_filters'));
            add_action('woocommerce_after_mini_cart', array($this, 'add_price_filters'));
            add_filter('woocommerce_product_is_on_sale', array($this, 'on_get_product_is_on_sale'), 10, 2);


            add_filter('woocommerce_variation_prices_price', array($this, 'on_get_variation_prices_price'), 10, 3);
            add_filter('woocommerce_get_variation_price', array($this, 'on_get_variation_price'), 10, 4);

            add_filter('woocommerce_get_price', array($this, 'on_get_price'), 10, 2);

            add_filter('woocommerce_composite_get_price', array($this, 'on_get_composite_price'), 10, 2);
            add_filter('woocommerce_composite_get_base_price', array($this, 'on_get_composite_base_price'), 10, 2);

            add_filter('woocommerce_coupon_is_valid', array($this, 'check_cart_coupon_is_valid'), 99, 2);
            add_filter('woocommerce_coupon_is_valid_for_product', array($this, 'check_coupon_is_valid'), 99, 4);
        }

        if (isset($_POST['createaccount'])) {
            add_filter('woocommerce_dynamic_pricing_is_rule_set_valid_for_user', array(
                $this,
                'new_account_overrides'
            ), 10, 2);
        }

        add_filter('woocommerce_dynamic_pricing_get_rule_amount', array($this, 'convert_decimals'), 99, 4);
    }

    public function on_plugins_loaded() {

        $additional_taxonomies = apply_filters('wc_dynamic_pricing_get_discount_taxonomies', array());

        if ($additional_taxonomies) {
            foreach ($additional_taxonomies as $additional_taxonomy) {
                $this->modules['simple_taxonomy_' . $additional_taxonomy] = WC_Dynamic_Pricing_Simple_Taxonomy::instance($additional_taxonomy);
                $this->modules['advanced_taxonomy_' . $additional_taxonomy] = WC_Dynamic_Pricing_Advanced_Taxonomy::instance($additional_taxonomy);
            }
        }
    }

    public function check_coupon_is_valid($valid, $product, $coupon, $values)
    {

        if (((method_exists($coupon, 'get_exclude_sale_items') && $coupon->get_exclude_sale_items()) || (method_exists($coupon, 'exclude_sale_items') && $coupon->exclude_sale_items())) && isset($values['discounts']) && isset($values['discounts']['applied_discounts']) && !empty($values['discounts']['applied_discounts'])) {
            $valid = false;
        }

        return $valid;
    }

    public function check_cart_coupon_is_valid($valid, $coupon)
    {
        if (((method_exists($coupon, 'get_exclude_sale_items') && $coupon->get_exclude_sale_items()) || (method_exists($coupon, 'exclude_sale_items') && $coupon->exclude_sale_items()))) {
            foreach (WC()->cart->get_cart() as $values) {
                if (isset($values['discounts']) && isset($values['discounts']['applied_discounts']) && !empty($values['discounts']['applied_discounts'])) {
                    return false;
                }
            }
        }

        return $valid;
    }

    public function new_account_overrides($result, $condition)
    {
        switch ($condition['type']) {
            case 'apply_to':
                if (is_array($condition['args']) && isset($condition['args']['applies_to'])) {
                    if ($condition['args']['applies_to'] == 'everyone') {
                        $result = 1;
                    } elseif ($condition['args']['applies_to'] == 'unauthenticated') {
                        $result = 1; //The user wasn't logged in, but now will be.  Hardcode to true
                    } elseif ($condition['args']['applies_to'] == 'authenticated') {
                        $result = 0; //The user wasn't logged in previously.
                    } elseif ($condition['args']['applies_to'] == 'roles' && isset($condition['args']['roles']) && is_array($condition['args']['roles'])) {
                        $result = 0;
                    }
                }
                break;
            default:
                $result = 0;
                break;
        }

        return $result;
    }

    /**
     * Remove the price filter when mini-cart is triggered.
     * @since 2.10.2
     */
    public function remove_price_filters()
    {
        remove_filter('woocommerce_get_price', array($this, 'on_get_price'), 10, 2);
    }

    /**
     * Add the price filters back in after mini-cart is done.
     * @since 2.10.2
     */
    public function add_price_filters()
    {
        add_filter('woocommerce_get_price', array($this, 'on_get_price'), 10, 2);
    }

    public function convert_decimals($amount, $rule, $cart_item, $module)
    {
        if (function_exists('wc_format_decimal')) {
            $amount = wc_format_decimal(str_replace(get_option('woocommerce_price_thousand_sep'), '', $amount));
        }

        return $amount;
    }

    public function on_cart_loaded_from_session($cart)
    {
        global $woocommerce;

        $sorted_cart = array();
        if (sizeof($cart->cart_contents) > 0) {
            foreach ($cart->cart_contents as $cart_item_key => $values) {

                if (isset($cart->cart_contents[$cart_item_key]['discounts'])) {
                    unset($cart->cart_contents[$cart_item_key]['discounts']);
                }

                $sorted_cart[$cart_item_key] = $values;
            }
        }

        //Sort the cart so that the lowest priced item is discounted when using block rules.
        @uasort($sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price');

        $modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
        foreach ($modules as $module) {
            $module->adjust_cart($sorted_cart);
        }

        //Reset the subtotal on ajax requests to force the mini cart to refresh itself.
        if (defined('WC_DOING_AJAX') && WC_DOING_AJAX) {
            $cart->subtotal = false;
        }
    }

    public function on_calculate_totals($cart)
    {
        global $woocommerce;

        $sorted_cart = array();
        if (sizeof($cart->cart_contents) > 0) {
            foreach ($cart->cart_contents as $cart_item_key => $values) {
                $sorted_cart[$cart_item_key] = $values;
            }
        }

        //Sort the cart so that the lowest priced item is discounted when using block rules.
        uasort($sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price');

        $modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
        foreach ($modules as $module) {
            $module->adjust_cart($sorted_cart);
        }
    }

    public function on_get_composite_price($base_price, $_product)
    {
        return $this->on_get_price($base_price, $_product);
    }

    public function on_get_composite_base_price($base_price, $_product)
    {
        return $this->on_get_price($base_price, $_product);
    }

    /**
     * @since 2.6.1
     *
     * @param type $base_price
     * @param type $_product
     *
     * @return float
     */
    public function on_get_price($base_price, $_product, $force_calculation = false)
    {
        global $product;
        $composite_ajax = did_action('wp_ajax_woocommerce_show_composited_product') | did_action('wp_ajax_nopriv_woocommerce_show_composited_product') | did_action('wc_ajax_woocommerce_show_composited_product');

	    $result_price = $base_price;
        //Is Product check so this does not run on the cart page.  Cart items are discounted when loaded from session.
        if ((is_object($product) && $product->id == $_product->id) || (function_exists('is_shop') && is_shop()) || is_product() || is_tax() || $force_calculation || $composite_ajax) {
	        $cache_id = $_product->get_id() . spl_object_hash( $_product );
	        if ( isset( $this->cached_adjustments[ $cache_id ] ) ) {
		        return $this->cached_adjustments[ $cache_id ];
	        }

        	$id = isset($_product->variation_id) ? $_product->variation_id : $_product->id;
            $discount_price = false;
            $working_price = isset($this->discounted_products[$id]) ? $this->discounted_products[$id] : $base_price;

            $modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
            foreach ($modules as $module) {
                if ($module->module_type == 'simple') {
                    //Make sure we are using the price that was just discounted.
                    $working_price = $discount_price ? $discount_price : $base_price;
                    $working_price = $module->get_product_working_price($working_price, $_product);
                    if ($working_price !== false) {
                        $discount_price = $module->get_discounted_price_for_shop($_product, $working_price);
                    }
                }
            }

	        if ( $discount_price !== false ) {
		        $result_price = $discount_price;
	        } else {
		        $result_price = $base_price;
	        }

	        $this->cached_adjustments[ $cache_id ] = $result_price;
        }


	    return $result_price;
    }

    /**
     * @since 2.9.8
     *
     * @param type $base_price
     * @param type $_product
     *
     * @return float
     */
    private function get_discounted_price($base_price, $_product)
    {

	    $cache_id = $_product->get_id() . spl_object_hash( $_product );
	    if ( isset( $this->cached_adjustments[ $cache_id ] ) ) {
		    return $this->cached_adjustments[ $cache_id ];
	    }

        $id = isset($_product->variation_id) ? $_product->variation_id : $_product->id;
        $discount_price = false;
        $working_price = isset($this->discounted_products[$id]) ? $this->discounted_products[$id] : $base_price;

        $modules = apply_filters('wc_dynamic_pricing_load_modules', $this->modules);
        foreach ($modules as $module) {
            if ($module->module_type == 'simple') {
                //Make sure we are using the price that was just discounted.
                $working_price = $discount_price ? $discount_price : $base_price;
                $working_price = $module->get_product_working_price($working_price, $_product);
                if (floatval($working_price)) {
                    $discount_price = $module->get_discounted_price_for_shop($_product, $working_price);
                }
            }
        }

        if ($discount_price) {
	        $this->cached_adjustments[ $cache_id ] = $discount_price;
	        return $discount_price;
        } else {
	        $this->cached_adjustments[ $cache_id ] = $base_price;
            return $base_price;
        }
    }

    /**
     * Filters the variation price from WC_Product_Variable->get_variation_prices()
     * @since 2.11.1
     *
     * @param float $price
     * @param WC_Product_Variation $variation
     * @param WC_Product_Variable $variable
     *
     * @return float
     */
    public function on_get_variation_prices_price($price, $variation, $variable)
    {
        return $this->get_discounted_price($price, $variation);
    }

    public function on_get_variation_price($price, $product, $min_or_max, $display)
    {
        $min_price = $price;
        $max_price = $price;
        $tax_display_mode = get_option('woocommerce_tax_display_shop');

        $children = $product->get_children();
        if (isset($children) && !empty($children)) {
            foreach ($children as $variation_id) {
                if ($display) {
                    $variation = $product->get_child($variation_id);
                    if ($variation) {
                        remove_filter('woocommerce_get_price', array($this, 'on_get_price'), 10, 2);
                        $base_price = $tax_display_mode == 'incl' ? $variation->get_price_including_tax() : $variation->get_price_excluding_tax();
                        $calc_price = $base_price;
                        $discount_price = $this->get_discounted_price($base_price, $variation);
                        if ($discount_price && $base_price != $discount_price) {
                            $calc_price = $discount_price;
                        }
                        add_filter('woocommerce_get_price', array($this, 'on_get_price'), 10, 2);
                    } else {
                        $calc_price = '';
                    }
                } else {
                    $calc_price = get_post_meta($variation_id, '_price', true);
                }


                if ($min_price == null || $calc_price < $min_price) {
                    $min_price = $calc_price;
                }

                if ($max_price == null || $calc_price > $max_price) {
                    $max_price = $calc_price;
                }
            }
        }

        if ($min_or_max == 'min') {
            return $min_price;
        } elseif ($min_or_max == 'max') {
            return $max_price;
        } else {
            return $price;
        }
    }

    /**
     * Overrides the default woocommerce is on sale to ensure sale badges show properly.
     * @since 2.10.8
     *
     * @param bool $is_on_sale
     * @param WC_Product $product
     *
     * @return bool
     */
    public function on_get_product_is_on_sale($is_on_sale, $product)
    {
        if ($is_on_sale) {
            return $is_on_sale;
        }

        if ($product->is_type('bundle') && $product->per_product_pricing_active) {
            return $is_on_sale;
        }

        if ($product->is_type('variable')) {
            $is_on_sale = false;

            $prices = $product->get_variation_prices();

            $regular = array_map('strval', $prices['regular_price']);
            $actual_prices = array_map('strval', $prices['price']);

            $diff = array_diff_assoc($regular, $actual_prices);

            if (!empty($diff)) {
                $is_on_sale = true;
            }
        } else {

            $dynamic_price = $this->on_get_price($product->price, $product, true);
            $regular_price = $product->get_regular_price();

            if (empty($regular_price) || empty($dynamic_price)) {
                return $is_on_sale;
            } else {
                $is_on_sale = $regular_price != $dynamic_price;
            }
        }

        return $is_on_sale;
    }

    //Helper functions to modify the woocommerce cart.  Called from the individual modules.
    public static function apply_cart_item_adjustment($cart_item_key, $original_price, $adjusted_price, $module, $set_id)
    {
        global $woocommerce;
        do_action('wc_memberships_discounts_disable_price_adjustments');
        $adjusted_price = apply_filters('wc_dynamic_pricing_apply_cart_item_adjustment', $adjusted_price, $cart_item_key, $original_price, $module);

        //Allow extensions to stop processing of applying the discount.  Added for subscriptions signup fee compatibility
        if ($adjusted_price === false) {
            return;
        }

        if (isset($woocommerce->cart->cart_contents[$cart_item_key])) {
            $_product = $woocommerce->cart->cart_contents[$cart_item_key]['data'];

            if (apply_filters('wc_dynamic_pricing_get_use_sale_price', true, $_product)) {
                $display_price = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();
            } else {
                $display_price = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax(1, $original_price) : $_product->get_price_including_tax(1, $original_price);
            }

            $woocommerce->cart->cart_contents[$cart_item_key]['data']->price = $adjusted_price;

            if ($_product->product_type == 'composite') {
                $woocommerce->cart->cart_contents[$cart_item_key]['data']->base_price = $adjusted_price;
            }


            if (!isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {

                $discount_data = array(
                    'by' => array($module),
                    'set_id' => $set_id,
                    'price_base' => $original_price,
                    'display_price' => $display_price,
                    'price_adjusted' => $adjusted_price,
                    'applied_discounts' => array(
                        array(
                            'by' => $module,
                            'set_id' => $set_id,
                            'price_base' => $original_price,
                            'price_adjusted' => $adjusted_price
                        )
                    )
                );
                $woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;
            } else {

                $existing = $woocommerce->cart->cart_contents[$cart_item_key]['discounts'];

                $discount_data = array(
                    'by' => $existing['by'],
                    'set_id' => $set_id,
                    'price_base' => $original_price,
                    'display_price' => $existing['display_price'],
                    'price_adjusted' => $adjusted_price
                );

                $woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;

                $history = array(
                    'by' => $existing['by'],
                    'set_id' => $existing['set_id'],
                    'price_base' => $existing['price_base'],
                    'price_adjusted' => $existing['price_adjusted']
                );
                array_push($woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'], $module);
                $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['applied_discounts'][] = $history;
            }
        }
        do_action('wc_memberships_discounts_enable_price_adjustments');
        do_action('woocommerce_dynamic_pricing_apply_cartitem_adjustment', $cart_item_key, $original_price, $adjusted_price, $module, $set_id);
    }

    /** Helper functions ***************************************************** */

    /**
     * Get the plugin url.
     *
     * @access public
     * @return string
     */
    public static function plugin_url()
    {
        return plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    /**
     * Get the plugin path.
     *
     * @access public
     * @return string
     */
    public static function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

}

/* Helper Functions */

function wc_dynamic_pricing_is_groups_active()
{
    $result = false;
    $result = in_array('groups/groups.php', (array)get_option('active_plugins', array()));
    if (!$result && is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        $result = isset($plugins['groups/groups.php']);
    }

    return $result;
}

function wc_dynamic_pricing_is_memberships_active()
{
    $result = false;
    $result = in_array('woocommerce-memberships/woocommerce-memberships.php', (array)get_option('active_plugins', array()));
    if (!$result && is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        $result = isset($plugins['woocommerce-memberships/woocommerce-memberships.php']);
    }

    return $result;
}

function wc_dynamic_pricing_is_brands_active()
{
    $result = false;
    $result = in_array('woocommerce-brands/woocommerce-brands.php', (array)get_option('active_plugins', array()));
    if (!$result && is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        $result = isset($plugins['woocommerce-brands/woocommerce-brands.php']);
    }

    return $result;
}


add_filter('wc_dynamic_pricing_get_discount_taxonomies', 'wc_dynamic_pricing_maybe_load_product_brands');
function wc_dynamic_pricing_maybe_load_product_brands($taxonomies)
{
    if (wc_dynamic_pricing_is_brands_active()) {
        $taxonomies[] = 'product_brand';
    }

    return $taxonomies;
}