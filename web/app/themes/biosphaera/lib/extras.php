<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Setup;

/**
 * Add <body> classes
 */
function body_class($classes)
{
    // Add page slug if it doesn't exist
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }
    if (is_singular('aree_terapeutiche')) {
        $classes[] = 'woocommerce';
    }
    // Add class if sidebar is active
    if (Setup\display_sidebar()) {
        $classes[] = 'sidebar-primary';
    }

    return $classes;
}
add_filter('body_class', __NAMESPACE__ . '\\body_class');

/**
 * Clean up the_excerpt()
 */
function excerpt_more()
{
    return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');

add_filter('et_project_posttype_args', __NAMESPACE__ . '\\mytheme_et_project_posttype_args', 10, 1);
function mytheme_et_project_posttype_args($args)
{
    return array_merge($args, array(
        'public'              => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => false,
        'show_in_nav_menus'   => false,
        'show_ui'             => false,
    ));
}

remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
add_action('woocommerce_before_single_product_summary', 'woocommerce_template_single_excerpt', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 31);
add_action('woocommerce_single_product_summary', 'woocommerce_show_product_images', 5);
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_open_price_container', 9);
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_close_price_container', 31);
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_formato', 8);
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_sale_info', 32);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
add_action('woocommerce_after_single_product_summary', __NAMESPACE__ . '\\get_product_reviews', 5);
remove_filter('woocommerce_product_tabs', 'woocommerce_default_product_tabs');
remove_action('woocommerce_review_before', 'woocommerce_review_display_gravatar', 10);
remove_action( 'woocommerce_checkout_order_review',  'woocommerce_order_review', 10 );
//add_action( 'woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 50 );;
remove_all_actions('woocommerce_before_shop_loop_item_title');
remove_all_actions('woocommerce_after_shop_loop_item_title');
//add_action('woocommerce_shop_loop_item_title', function () {echo '<div class="content">';}, 11);
add_action('woocommerce_shop_loop_item_title', function () {
    $attachment_id = get_post_meta(get_the_ID(), 'logo', true);

    echo
    /*<div class="product-logo"><img src="' . wp_get_attachment_image_src($attachment_id)[0] . '" alt="' . get_post_meta($attachment_id, '_wp_attachment_image_alt', true) . '"></div>*/
  '<div class="product-loop-excerpt">' . get_post_meta(get_the_ID(), 'descrizione_pagina_prodotto', true) . '</div>';
}, 12);
//add_action('woocommerce_shop_loop_item_title', function () {echo '</div>';}, 18);
add_action('woocommerce_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 14);
add_action('woocommerce_shop_loop_item_title', function () {echo '<div class="woocommerce-loop-image-wrapper">';}, 15);
add_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 16);
add_action('woocommerce_shop_loop_item_title', function () {echo '</div>';}, 17);
//add_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_add_to_cart', 16);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
remove_all_actions('woocommerce_before_shop_loop');
//add_action('woocommerce_shop_loop_item_title', function () {return '</div>';}, 16);
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_filter('woocommerce_product_tabs', function ($tabs) {
    if (is_singular('aree_terapeutiche')) {
        return [];
    }
    $values=['modalita-duso','ingredienti','benefici'];
    $keys = [__('How to use', 'sage'), __('Ingredients', 'sage'), __('Benefits', 'sage')];
    foreach ($keys as $key => $value) {
        $tabs[] = [
            'title'    => __($value,'sage'),
            'callback' => __NAMESPACE__ . '\\get_tab_content',
            'priority' => 10 * $key,
            'custom_val'=>$values[$key],
        ];
    }
    return $tabs;
});

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

function get_tab_content($key, $tab)
{
    global $product;
    echo get_field($tab['custom_val'], $product->id);
}
function woocommerce_formato(){
    global $product;
    echo '<div class="container-formato">
        <p class="product-formato">'.get_field('formato',$product->id).'</p>
    </div> ';
}
function woocommerce_sale_info(){
    global $product;
    echo '<div class="container-sale-info">
        <p class="product-sale-info">'.get_field('sale_info',$product->id).'</p>
    </div>';
}
function get_product_reviews()
{
    comments_template();
}
function woocommerce_open_price_container()
{
    echo '<div class="buy-wrapper">';
}
function woocommerce_close_price_container()
{
    echo '</div>';
}

add_filter('woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\woocommerce_header_add_to_cart_fragment');
function woocommerce_header_add_to_cart_fragment($fragments)
{
    ob_start();
    ?>
  <a class="wcmenucart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php __('Cart', 'sage');?>"><i class="fa fa-shopping-cart"></i> <span class="wcmenucart-text">(<span class="cart-length">
    <?php echo WC()->cart->get_cart_contents_count(); ?> </span>) <?php _e('Cart', 'sage')?></span></a>

  <?php

    $fragments['a.wcmenucart-contents'] = ob_get_clean();

    return $fragments;
}

add_action('init', __NAMESPACE__ . '\\init_aree_terap_tax');
function init_aree_terap_tax()
{
    $labels = array(
        "name"          => __('Categorie Aree Terapeutiche', 'sage'),
        "singular_name" => __('Categoria Aree Terapeutiche', 'sage'),
    );

    $args = array(
        "label"              => __('Categorie Aree Terapeutiche', 'sage'),
        "labels"             => $labels,
        "public"             => true,
        "hierarchical"       => true,
        "label"              => "Categorie Aree Terapeutiche",
        "show_ui"            => true,
        "show_in_menu"       => true,
        "show_in_nav_menus"  => true,
        "query_var"          => true,
        "rewrite"            => array('slug' => '.', 'with_front' => false),
        "show_admin_column"  => false,
        "show_in_rest"       => false,
        "rest_base"          => "",
        "show_in_quick_edit" => false,
    );
    register_taxonomy("aree_terapeutice_tax", array("aree_terapeutiche"), $args);

// End cptui_register_my_taxes_producer()
}

function get_areat_color($areat_id){
 return  get_field('color', 'aree_terapeutice_tax_' . wp_get_post_terms( $areat_id
, 'aree_terapeutice_tax', ['fields' => 'ids'])[0]);
}

function area_terap_attribute_template($field)
{
  $field_obj = get_field_object($field);

  global $post;
  $areat_id = $post->ID;
  $area_color=get_areat_color($areat_id);
$label = get_field('label_'.$field)?:$field_obj['label'];
    echo '<div class="area_terap_attribute">
  <h4 class="area_terap_attribute_title" style="background-color:'.$area_color.'">' . $label . '</h4>
  <div class="area_terap_attribute_content">' . $field_obj['value'] . '</div>
</div> ';


}

add_action('woocommerce_share', __NAMESPACE__ . '\\bios_social');

function bios_social()
{
    echo '<div class="social-container"><div  >
  <p>'.__('Recommend to your friends','sage').'</p>
  <div class="fb-share frame"><iframe data-ce-src="" class="ce-iframe" ></iframe>
  <div class="fb-share-button" data-href="' . get_permalink() . '" data-layout="button_count" data-size="small" data-mobile-iframe="true"><a class="fb-xfbml-parse-ignore" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink() . '&src=sdkpreparse') . '" >' . __('Share', 'sage') . '</a></div></div></div></div>';
  /* <div class="disponibilita">
    <p style="font-size:1rem;margin-bottom:0;"><span>'.__('IMMEDIATE AVAILABILITY','sage').'</span><span>'.__(' Express Delivery 1-3 days','sage').'</span></p>

  </div>-->*/

}

add_filter('nav_menu_link_attributes', __NAMESPACE__ . '\\filter_function_name', 10, 3);

function filter_function_name($atts, $item, $args)
{

    if (in_array('no-title', $item->classes)) {
        unset($atts['href']);
    }
if($args->menu->name !== 'Menu') return $atts;
    if ($item->object === 'aree_terapeutice_tax') {
        $atts['style'] = 'background-color:' . get_field('color', $item->object . '_' . $item->object_id) . ';';
    } elseif ($item->object === 'aree_terapeutiche' && $item->object_id > 0) {

        $atts['style'] = 'background-color:' . get_field('color', 'aree_terapeutice_tax_' . wp_get_post_terms($item->object_id, 'aree_terapeutice_tax', ['fields' => 'ids'])[0]) . ';';
    }

    return $atts;
}
function display_breadcrumb()
{
    global $post;
    /*
    if ($post && !in_array($post->post_type, ['product', 'aree_terapeutiche'])) {
        return false;
    }

    return true;
    */
     return false;
}

function theme_breadcrumb()
{
    if(!display_breadcrumb())return'';
    global $post;
    $breadcrumbs_parts = [];
    if ($post->post_type === 'product') {
        $term     = get_field('prodotti_to_areeterapeutiche')[0];

        $term_tax = wp_get_post_terms($term->ID, 'aree_terapeutice_tax')[0];

    } else {
        $term_tax = wp_get_post_terms($post->ID, 'aree_terapeutice_tax')[0];
    }
    $breadcrumbs_parts[] = ['label' => strtoupper($term_tax->name)];
    $breadcrumbs_parts[] = ['label' => $post->post_title];
    $breadcrumb          = '<div class="breadcrumbs">
  <span> > ' . __('you are in', 'sage') . '</span>';
    foreach ($breadcrumbs_parts as $key => $bread) {
        if (isset($bread['link'])) {
            $breadcrumb .= '<a href="' . $bread['link'] . '">';
        }

        $breadcrumb .= '<span>' . $bread['label'] . '</span>';
        if (isset($bread['link'])) {
            $breadcrumb .= '</a>';
        }

        if ($key !== count($breadcrumbs_parts) - 1) {
            $breadcrumb .= '<span>/</span>';
        }

    }
    $breadcrumb .= '</div>';
    echo $breadcrumb;
}

function get_product_color($post_id)
{
   return get_field('color', $post_id);


}

add_filter('loop_shop_columns', __NAMESPACE__ . '\\wc_product_columns_frontend');
function wc_product_columns_frontend()
{
    global $woocommerce;

    // Default Value also used for categories and sub_categories
    $columns = 4;

    // Product List
    if (is_shop()):
        $columns = 2;
    endif;

    return $columns;

}

function hex2rgba($color, $opacity = false, $return_arr=false)
{

    $default = 'rgb(0,0,0)';

    //Return default if no color provided
    if (empty($color)) {
        return $default;
    }

    //Sanitize $color if "#" is provided
    if ($color[0] == '#') {
        $color = substr($color, 1);
    }

    //Check if color has 6 or 3 characters and get values
    if (strlen($color) == 6) {
        $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
    } elseif (strlen($color) == 3) {
        $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
    } else {
        return $default;
    }

    //Convert hexadec to rgb
    $rgb = array_map('hexdec', $hex);

    //Check if opacity is set(rgba or rgb)
    if ($opacity) {
        if (abs($opacity) > 1) {
            $opacity = 1.0;
        }

        $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    } else {
        $output = 'rgb(' . implode(",", $rgb) . ')';
    }

    //Return rgb(a) color string
    return $return_arr? $rgb:$output;
}

function lumdiff($arr){
  $red=$arr[0];$green=$arr[1];$blue=$arr[2];
 return ($red*0.299 + $green*0.587 + $blue*0.114) > 186  ? '#444' : '#ffffff';
}
function bios_mobile_account_links($items){
    global $woocommerce;
    $text='<div class="lang-wrap">'.bios_lang_sel('',false).'</div>'.($woocommerce->cart->cart_contents_count>0?sk_wcmenucart(false):'');

    $links=[

is_user_logged_in()?'<a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Profile', 'sage' ).' </a>':'<a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Login', 'sage' ).' </a>',
is_user_logged_in()?'<a href="'. esc_url( wc_get_endpoint_url( 'customer-logout', '', wc_get_page_permalink( 'myaccount' ) ) ) .'">'.__('Logout', 'sage' ).'</a>':'<a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '?action=register">'.__('Register', 'sage' ).' </a>',
['before'=>true,'text'=>$text,'classes'=>'lang-sel'],
    ];
    foreach ($links as $link) {
        $item= is_array($link)?'<li class="menu-item '.(isset($link['classes'])?$link['classes']:'').'">'.$link['text'].'</li>':'<li class="menu-item ">'.$link.'</li>';
        $items=is_array($link) && $link['before'] ? $item.$items : $items.$item;
    }
    return $items;
}
add_filter( 'wp_nav_menu_menu-mobile_items',  __NAMESPACE__ . '\\bios_mobile_account_links');
add_filter( 'wp_nav_menu_menu-mobile-inglese_items',  __NAMESPACE__ . '\\bios_mobile_account_links');

function sk_wcmenucart($text=true) {

  // Check if WooCommerce is active and add a new item to a menu assigned to Primary Navigation Menu location
  if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  )
    return ;

  ob_start();
    global $woocommerce;
    $viewing_cart = __('Cart', 'sage');
    $cart_url = $woocommerce->cart->get_cart_url();
    $cart_contents_count = $woocommerce->cart->cart_contents_count;
    $cart_contents = sprintf(_n('%d item', '%d items', $cart_contents_count, 'sage'), $cart_contents_count);
    // Uncomment the line below to hide nav menu cart item when there are no items in the cart
     if ( $cart_contents_count > 0 ) {

        $menu_item = '<a '.($text?'class="wcmenucart-contents"':'').' href="'. $cart_url .'" title="'. ($text?$viewing_cart:'') .'">';


      $menu_item .= '<i class="fa fa-shopping-cart"></i> ';

      $menu_item .= '<span class="wcmenucart-text">(<span class="cart-length">' . $cart_contents_count . '</span>) '.($text?$viewing_cart:'');
      $menu_item .= '</span></a>';
    // Uncomment the line below to hide nav menu cart item when there are no items in the cart
      echo $menu_item;
     }

  $social = ob_get_clean();
  return $social;

}
function bios_wc_link(){
     if (is_user_logged_in()) {
      return '<div class="account-link"><div><a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Profile', 'sage' ).' </a> | <a href="'. esc_url( wc_get_endpoint_url( 'customer-logout', '', wc_get_page_permalink( 'myaccount' ) ) ) .'">'.__('Logout', 'sage' ).'</a></div></div>';
    }
    elseif (!is_user_logged_in() ) {
      return '<div class="account-link"><div><a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Login', 'sage' ).' </a> | <a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '?action=register">'.__('Register', 'sage' ).' </a></div></div>';
    }
}

function bios_search(){
     $page_id=get_page_by_title('Risultati ricerca')->ID;
            $lang_id=apply_filters( 'wpml_object_id', $page_id, 'page', false, ICL_LANGUAGE_CODE );

  echo '<form action="'. get_permalink( get_page($lang_id))   .' " id="responsive_menu_pro_search" method="get" role="search">
     <i class="fa fa-search"></i>
            <input type="search" name="swpquery" value="" placeholder="'.  __( 'Search', 'sage' )  .'" id="responsive_menu_pro_search_input">
        </form>';
}

add_action( 'before_responsive_menu_search',__NAMESPACE__ . '\\bios_search' );



add_action( 'init', function() {

    /**
     * Post Type: Aree terapeutiche.
     */

    $labels = array(
        "name" => __( 'Aree terapeutiche', 'sage' ),
        "singular_name" => __( 'Aree terapeutica', 'sage' ),
    );

    $args = array(
        "label" => __( 'Aree terapeutiche', 'sage' ),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => false,
        "rest_base" => "",
        "has_archive" => true,
        "show_in_menu" => true,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => array( "slug" => _x( 'aree_terapeutiche', 'URL slug', 'sage' ), "with_front" => true ),
        "query_var" => true,
        "supports" => array( "title", "editor", "thumbnail" ),
        "taxonomies" => array( "aree_terapeutice_tax" ),
    );

    register_post_type( "aree_terapeutiche", $args );
} );


add_action('admin_init',function(){
    $role = get_role('shop_manager');
    // remove full access in case it was added previously
    $role->remove_cap('gform_full_access');
    $role->add_cap('gravityforms_view_entries');
    $role->add_cap('gravityforms_edit_entries');
});

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array (
    'key' => 'group_58b7faa48a5aa',
    'title' => 'Biosphaera aree',
    'fields' => array (
        array (
            'return_format' => 'id',
            'preview_size' => 'full',
            'library' => 'all',
            'min_width' => '',
            'min_height' => '',
            'min_size' => '',
            'max_width' => '',
            'max_height' => '',
            'max_size' => '',
            'mime_types' => '',
            'key' => 'field_58b7fac5757c9',
            'label' => 'Immagine',
            'name' => 'immagine',
            'type' => 'image',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'taxonomy',
                'operator' => '==',
                'value' => 'aree_terapeutice_tax',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

acf_add_local_field_group(array (
    'key' => 'group_589aeb2b080e2',
    'title' => 'AreeTerapeutiche to Prodotti',
    'fields' => array (
        array (
            'post_type' => array (
                0 => 'product',
            ),
            'taxonomy' => array (
            ),
            'min' => '',
            'max' => '',
            'filters' => array (
                0 => 'search',
                1 => 'taxonomy',
            ),
            'elements' => '',
            'return_format' => 'object',
            'key' => 'field_589aeb3f1b438',
            'label' => 'Prodotti to AreeTerapeutiche Relation',
            'name' => 'prodotti_to_areeterapeutiche',
            'type' => 'relationship',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'aree_terapeutiche',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

acf_add_local_field_group(array (
    'key' => 'group_589ae60c878c4',
    'title' => 'Prodotti to AreeTerapeutiche',
    'fields' => array (
        array (
            'post_type' => array (
                0 => 'aree_terapeutiche',
            ),
            'taxonomy' => array (
            ),
            'min' => '',
            'max' => '',
            'filters' => array (
                0 => 'search',
                1 => 'taxonomy',
            ),
            'elements' => '',
            'return_format' => 'object',
            'key' => 'field_589ae614641c5',
            'label' => 'Prodotti to AreeTerapeutiche Relation',
            'name' => 'prodotti_to_areeterapeutiche',
            'type' => 'relationship',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'product',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

acf_add_local_field_group(array (
    'key' => 'group_580f35450a54b',
    'title' => 'Attrbuti area terapeutica',
    'fields' => array (
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_58a6cfcfeadb8',
            'label' => 'Label Che cos\'è?',
            'name' => 'label_cosa',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'default_value' => '',
            'delay' => 0,
            'key' => 'field_580f3556482f0',
            'label' => 'Che cos\'è?',
            'name' => 'cosa',
            'type' => 'wysiwyg',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_58a6cfe9eadb9',
            'label' => 'Label Da cosa è provocato?',
            'name' => 'label_cause',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'default_value' => '',
            'delay' => 0,
            'key' => 'field_580f355e482f3',
            'label' => 'Da cosa è provocato?',
            'name' => 'cause',
            'type' => 'wysiwyg',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_58a6d003eadba',
            'label' => 'Label Come si manifesta?',
            'name' => 'label_manifestazioni',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'default_value' => '',
            'delay' => 0,
            'key' => 'field_580f355c482f2',
            'label' => 'Come si manifesta?',
            'name' => 'manifestazioni',
            'type' => 'wysiwyg',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_58a6d022eadbb',
            'label' => 'Label Rimedi Naturali',
            'name' => 'label_rimedi_naturali',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'default_value' => '',
            'delay' => 0,
            'key' => 'field_580f355b482f1',
            'label' => 'Rimedi Naturali',
            'name' => 'rimedi_naturali',
            'type' => 'wysiwyg',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'aree_terapeutiche',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

acf_add_local_field_group(array (
    'key' => 'group_580f14c52049c',
    'title' => 'Custom Attributes',
    'fields' => array (
        array (
            'return_format' => 'url',
            'preview_size' => 'full',
            'library' => 'all',
            'min_width' => '',
            'min_height' => '',
            'min_size' => '',
            'max_width' => '',
            'max_height' => '',
            'max_size' => '',
            'mime_types' => '',
            'key' => 'field_5824791ce42a6',
            'label' => 'Logo',
            'name' => 'logo',
            'type' => 'image',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'new_lines' => 'wpautop',
            'maxlength' => '',
            'placeholder' => '',
            'rows' => '',
            'key' => 'field_580f14d1707a2',
            'label' => 'Modalità d\'uso',
            'name' => 'modalita-duso',
            'type' => 'textarea',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'new_lines' => 'wpautop',
            'maxlength' => '',
            'placeholder' => '',
            'rows' => '',
            'key' => 'field_580f14ef707a3',
            'label' => 'Ingredienti',
            'name' => 'ingredienti',
            'type' => 'textarea',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'new_lines' => 'wpautop',
            'maxlength' => '',
            'placeholder' => '',
            'rows' => '',
            'key' => 'field_580f14f1707a4',
            'label' => 'Benefici',
            'name' => 'benefici',
            'type' => 'textarea',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'new_lines' => 'wpautop',
            'maxlength' => '',
            'placeholder' => '',
            'rows' => '',
            'key' => 'field_582486386fd56',
            'label' => 'Descrizione Pagina Prodotto',
            'name' => 'descrizione_pagina_prodotto',
            'type' => 'textarea',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_589c7e8cac2ff',
            'label' => 'Formato',
            'name' => 'formato',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
        array (
            'default_value' => '',
            'maxlength' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'key' => 'field_589c881a543fc',
            'label' => 'Sale info',
            'name' => 'sale_info',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'product',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

acf_add_local_field_group(array (
    'key' => 'group_57e39beea402d',
    'title' => 'Custom product fields',
    'fields' => array (
        array (
            'default_value' => '',
            'key' => 'field_57e39c0351265',
            'label' => 'Color',
            'name' => 'color',
            'type' => 'color_picker',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array (
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        ),
    ),
    'location' => array (
        array (
            array (
                'param' => 'taxonomy',
                'operator' => '==',
                'value' => 'aree_terapeutice_tax',
            ),
        ),
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'product',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
    'description' => '',
));

endif;

function bios_lang_sel($div='',$echo=false){
  $languages = icl_get_languages('skip_missing=1');
  if(1 < count($languages)){
    foreach($languages as $l){

      if(!$l['active']) {$langs[] = '<a href="'.$l['url'].'" data-lang="' . $l['language_code'] . '">'.strtoupper($l['code']).'</a>';}
      else{ $langs[]='<span class="active" >'.strtoupper($l['code']).'</span>';}
    }
    $result=join($div, $langs);
    if($echo) {echo $result;}
    else{return $result;}
  }
}

function bios_footer_link($el){
     $page_id=get_page_by_title($el)->ID;

            $lang_id=apply_filters( 'wpml_object_id', $page_id, 'page', true, ICL_LANGUAGE_CODE );
    echo '<a href="'.get_permalink( $lang_id)  .'" class="last-line-link">'.get_the_title($lang_id).'</a> | ';
}




