<?php 
function sk_wcmenucart() {

  // Check if WooCommerce is active and add a new item to a menu assigned to Primary Navigation Menu location
  if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  )
    return ;

  ob_start();
    global $woocommerce;
    $viewing_cart = __('Carrello', 'sage');
    $cart_url = $woocommerce->cart->get_cart_url();
    $cart_contents_count = $woocommerce->cart->cart_contents_count;
    $cart_contents = sprintf(_n('%d item', '%d items', $cart_contents_count, 'sage'), $cart_contents_count);    
    // Uncomment the line below to hide nav menu cart item when there are no items in the cart
     if ( $cart_contents_count > 0 ) {

        $menu_item = '<a class="wcmenucart-contents" href="'. $cart_url .'" title="'. $viewing_cart .'">';
      

      $menu_item .= '<i class="fa fa-shopping-cart"></i> ';

      $menu_item .= '<span class="wcmenucart-text">(<span class="cart-length">' . $cart_contents_count . '</span>) '.$viewing_cart;
      $menu_item .= '</span></a>';
    // Uncomment the line below to hide nav menu cart item when there are no items in the cart
      echo $menu_item;
     }
    
  $social = ob_get_clean();
  return $social;

}
 ?>
<header class="banner">
  <div class="container">
     <div class="left"><?php echo sk_wcmenucart(); ?>        <form action="<?php echo get_home_url(); ?>" id="responsive_menu_pro_search" method="get" role="search">
     <i class="fa fa-search"></i>
            <input type="search" name="s" value="" placeholder="<?php _e( 'Cerca', 'responsive-menu-pro' ); ?>" id="responsive_menu_pro_search_input">            
        </form>    </div>
     <div class="center"><a href="<?php echo get_home_url(); ?>"><img src="<?= esc_url(get_stylesheet_directory_uri()); ?>/dist/images/biosphaera_logo.svg" alt="Logo Biosphera" class="logo">    </a>
    </div>
     <div class="right">
       
       
         <?php        if (is_user_logged_in()) {
      echo '<div class="account-link"><div><a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Profilo', 'sage' ).' </a> | <a href="'. esc_url( wc_get_endpoint_url( 'customer-logout', '', wc_get_page_permalink( 'myaccount' ) ) ) .'">'.__('Scollegati', 'sage' ).'</a></div></div>';
    }
    elseif (!is_user_logged_in() ) {
      echo '<div class="account-link"><div><a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '">'.__('Accedi', 'sage' ).' </a> | <a href="'.get_permalink(woocommerce_get_page_id('myaccount')). '?action=register">'.__('Registrati', 'sage' ).' </a></div></div>';
    } ?>
       <div class="lang-switcher">IT | EN</div>
     </div> 
   

  </div>
      <nav class="nav-primary">
      <?php
      if (has_nav_menu('primary_navigation')) :
        wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']);
      endif;
      ?>
    </nav>
</header>
