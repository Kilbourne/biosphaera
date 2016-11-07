<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Setup;

/**
 * Add <body> classes
 */
function body_class($classes) {
  // Add page slug if it doesn't exist
  if (is_single() || is_page() && !is_front_page()) {
    if (!in_array(basename(get_permalink()), $classes)) {
      $classes[] = basename(get_permalink());
    }
  }
  if(is_singular( 'aree_terapeutiche' )){
    $classes[]='woocommerce';
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
function excerpt_more() {
  return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');

add_filter( 'et_project_posttype_args', __NAMESPACE__ . '\\mytheme_et_project_posttype_args', 10, 1 );
function mytheme_et_project_posttype_args( $args ) {
  return array_merge( $args, array(
    'public'              => false,
    'exclude_from_search' => false,
    'publicly_queryable'  => false,
    'show_in_nav_menus'   => false,
    'show_ui'             => false
    ));
}

remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_excerpt', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb',  20 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 31 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_show_product_images', 5 );
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_open_price_container', 9 );
add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\\woocommerce_close_price_container', 31 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
add_action( 'woocommerce_after_single_product_summary', __NAMESPACE__ . '\\get_product_reviews', 5 );
remove_filter( 'woocommerce_product_tabs', 'woocommerce_default_product_tabs' );
remove_action( 'woocommerce_review_before','woocommerce_review_display_gravatar' ,10 );
add_filter( 'woocommerce_product_tabs', function($tabs){
  if(is_singular('aree_terapeutiche' )) return [];
  $keys = [__('Modalità d\'uso','sage'),__('Ingredienti','sage'),__('Benefici','sage')];
  foreach ($keys as $key => $value) {
    $tabs[]=[
    'title'=>$value,
    'callback'=>__NAMESPACE__ . '\\get_tab_content',
    'priority'=>10*$key
    ];
  }
  return $tabs;
} );

function get_tab_content($key, $tab){
  global $product;
  echo get_field(sanitize_title($tab['title']),$product->id);
}
function get_product_reviews(){
  comments_template();
}
function woocommerce_open_price_container(){
 echo '<div class="buy-wrapper">';
}
function woocommerce_close_price_container(){
  echo '</div>';
}

add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\woocommerce_header_add_to_cart_fragment' );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
  ob_start();
  ?>
  <a class="wcmenucart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php __('Carrello', 'sage'); ?>"><i class="fa fa-shopping-cart"></i> <span class="wcmenucart-text">(<span class="cart-length">
    <?php echo WC()->cart->get_cart_contents_count(); ?> </span>) <?php _e('Carrello', 'sage')?></span></a>

  <?php

  $fragments['a.wcmenucart-contents'] = ob_get_clean();

  return $fragments;
}





add_action( 'init', __NAMESPACE__ . '\\init_aree_terap_tax' );
function init_aree_terap_tax() {
  $labels = array(
  "name" => __( 'Categorie Aree Terapeutiche', 'sage' ),
  "singular_name" => __( 'Categoria Aree Terapeutiche', 'sage' ),
  );

$args = array(
"label" => __( 'Categorie Aree Terapeutiche', 'sage' ),
"labels" => $labels,
"public" => true,
"hierarchical" => true,
"label" => "Categorie Aree Terapeutiche",
"show_ui" => true,
"show_in_menu" => true,
"show_in_nav_menus" => true,
"query_var" => true,
"rewrite" => array( 'slug' => '.', 'with_front' => false, ),
"show_admin_column" => false,
"show_in_rest" => false,
"rest_base" => "",
"show_in_quick_edit" => false,
);
register_taxonomy( "aree_terapeutice_tax", array( "aree_terapeutiche" ), $args );

// End cptui_register_my_taxes_producer()
}

function area_terap_attribute_template($field){
  $field_obj = get_field_object($field);
  echo '<div class="area_terap_attribute">
  <h4 class="area_terap_attribute_title">'.$field_obj['label'].'</h4>
  <div class="area_terap_attribute_content">'.$field_obj['value'].'</div>
</div> ';
}

add_action( 'woocommerce_share', __NAMESPACE__ . '\\bios_social');

function bios_social(){
  echo '<div class="social-container"><div  >
  <p>Consiglialo ai tuoi amici</p>
  <div class="fb-share-button" data-href="'.get_permalink().'" data-layout="button_count" data-size="small" data-mobile-iframe="true"><a class="fb-xfbml-parse-ignore" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u='.urlencode(get_permalink().'&src=sdkpreparse').'" >'.__('Condividi','sage').'</a></div></div>
  <div class="disponibilita">
    <p style="font-size:1rem;margin-bottom:0;"><span>DISPONIBILITA IMMEDIATA</span><span>Consegna Express 1-3 giorni</span></p>

  </div>
</div>';
}


add_filter( 'nav_menu_link_attributes', __NAMESPACE__ . '\\filter_function_name', 10, 3 );

function filter_function_name( $atts, $item, $args ) {

  if(in_array('no-title', $item->classes)) unset($atts['href']);
  if($item->object === 'aree_terapeutice_tax' ){
    $atts['style']='background-color:'.get_field('color',$item->object.'_'.$item->object_id).';';
  }elseif($item->object === 'aree_terapeutiche' && $item->object_id > 0 ){



    $atts['style']='background-color:'.get_field('color','aree_terapeutice_tax_'.wp_get_post_terms( $item->object_id, 'aree_terapeutice_tax',['fields'=>'ids'])[0]).';';
  }

  return $atts;
}
function display_breadcrumb(){
  global $post;
  if(!in_array($post->post_type,  ['product','aree_terapeutiche'] )) return false;
  return true;
}

function theme_breadcrumb(){
  global $post;

  $breadcrumbs_parts = [];
  if($post->post_type === 'product' ){
    $term = wp_get_post_terms( $post->ID, 'aree_terapeutiche' )[0];
    $term_tax = wp_get_post_terms( $term->term_id, 'aree_terapeutice_tax' )[0];


  }else{
    $term_tax = wp_get_post_terms( $post->ID, 'aree_terapeutice_tax' )[0];
  }
  $breadcrumbs_parts[]=['label'=> strtoupper($term_tax->name) ];
  $breadcrumbs_parts[]=['label'=> $post->post_title];
  $breadcrumb='<div class="breadcrumbs">
  <span> > '.__('sei in','sage').'</span>';
  foreach ($breadcrumbs_parts as $key => $bread) {
    if(isset($bread['link']))$breadcrumb.='<a href="'.$bread['link'].'">';
    $breadcrumb.='<span>'.$bread['label'].'</span>';
    if(isset($bread['link']))$breadcrumb.='</a>';
    if($key !== count($breadcrumbs_parts)-1 ) $breadcrumb.='<span>/</span>';
  }
  $breadcrumb.= '</div>';
  echo $breadcrumb;
}

function get_product_color($post_id){
  return get_field('color', 'aree_terapeutice_tax_'.wp_get_post_terms( wp_get_post_terms( $post_id, 'aree_terapeutiche', ['fields'=> 'ids'] )[0], 'aree_terapeutice_tax', ['fields'=> 'ids'] )[0]);
}
