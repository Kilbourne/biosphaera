<?php

/*
|--------------------------------------------------------------------------
| Configuration Settings
|--------------------------------------------------------------------------
|
| Although some people aren't fans of array configurations, here we have one!
| This is where we set paths and our version number among other things.
|
*/

$config = array( 
    
    'current_version' => "1.0.9",    
    
    'plugin_base_dir' => dirname( plugin_dir_path( __FILE__ ) ),
    
    'plugin_base_uri' => plugin_dir_url( dirname( __FILE__ ) ),
    
    'plugin_data_uri' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'responsive-menu-pro-data/',
    
    'plugin_data_dir' => dirname( dirname( plugin_dir_path( __FILE__ ) ) ) . '/responsive-menu-pro-data/',
    
);

/*
|--------------------------------------------------------------------------
| Save Config to the Registry
|--------------------------------------------------------------------------
|
| Again, some people don't like Registry's in PHP Applications, but here we
| have one and it is very useful for retrieving our settings throughout the app
|
*/

ResponsiveMenuPro_Registry::set( 'config', $config );

/*
|--------------------------------------------------------------------------
| Responsive Menu Defaults
|--------------------------------------------------------------------------
|
| Another configuration array of type, this time we hold all the application
| default options.
|
*/

$defaults = array( 
    
    'menu' => '',    
    
    'breakpoint' => 1100,
        
    'depth' => 5,
        
    'top' => 10,
        
    'right' => 5,
        
    'css' => '',
        
    'title' => null,
        
    'button_line_colour' => '#FFFFFF',
    
    'button_background_colour' => '#000000',
    
    'button_text' => '',
    
    'is_button_background_transparent' => false,
    
    'font' => '',
    
    'is_fixed' => '',
    
    'title_image' => '',
    
    'width' => '75',
    
    'background_colour' => '#43494C',
    
    'background_colour_hover' => '#3C3C3C',
    
    'title_colour' => '#FFFFFF',
    
    'text_colour' => '#FFFFFF',
    
    'link_border_colour' => '#3C3C3C',
    
    'text_colour_hover' => '#FFFFFF',
    
    'title_colour_hover' => '#FFFFFF',

    /* Added in 1.6 */
        
    'animation_type' => 'overlay',
    
    'push_animation_container_css' => '',
    
    'title_background_colour' => '#43494C',
    
    'link_text_size' => 13,
    
    'title_text_size' => 14,
    
    'button_text_size' => 13,
    
    'current_page_link_background' => '#43494C',
    
    'current_page_link_colour' => '#FFFFFF',
    
    'animation_speed' => 0.5,
    
    /* Added in 1.7 */
    
    'transition_speed' => 1,
  
    'text_align' => 'left',
      
    'include_search' => false,
    
    'auto_expand_submenus' => false,
    
    'link_height' => 20,

    /* Added in 1.8 */
    
    'create_external_scripts' => false,
    
    'slide_side' => 'left',
    
    /* Added in 1.9 */
    
    'scripts_in_footer' => true,
    
    'button_image' => false,
    
    'minify' => null,
    
    'close_on_link_click' => false,
    
    'remove_important' => false,
    
    'use_x' => false,
    
    'min_width' => null,
    
    /* Added in 2.0 */
        
    'max_width' => null,
    
    'expand_parents' => true,
    
    'ignore_parent_clicks' => false,

    'click_to_close' => false,
    
    'search_position' => 'below',
    
    'title_link' => null,
    
    'title_target' => '_self',
    
    'html' => null,
     
    'html_location' => 'bottom',
    
    /* Added in 2.1 */
    
    'use_shortcode' => false,
    
    /* Added in 2.2 */
    
    'line_height' => 5,
        
    'line_width' => 33,
    
    'line_margin' => 6,
    
    'button_image_clicked' => null,
    
    'use_accordion' => false,
    
    'arrow_shape_active' => json_encode( '&#x25B2;' ),
    
    'arrow_shape_inactive' => json_encode( '&#x25BC;' ),
    
    'array_image_active' => false,
    
    'arrow_image_inactive' => false,
    
    
    /* Added in 2.3 */
    
    'trigger' => '#responsive_menu_pro_button',
 
    'use_push_animation' => false,
    
    'current_background_hover' => '#3C3C3C',
    
    'current_colour_hover' => '#FFFFFF',
    
    'walker' => null,
    
    /* Added in 2.4 */
    
    'use_transient_caching' => null,
 
    'location' => 'right',
       
    /* Added in 2.6 by Mkdgs*/
    
 	'theme_location' => null,
    
	/* Added in PRO 1.0 */
	
	'use_only_on_mobile' => false,
	
	'use_single_site_menu' => false,
	
	'set_auto_menu_height' => false,
	
	'use_header_bar' => false,
	
	'header_bar_logo' => null,
	
	'header_bar_html' => null,
	
	'header_bar_height' => 54,
	
	'header_bar_background_colour' => '#43494C',
	
	'single_menu_height' => 50,
	
	'single_menu_link_colour' => '#000000',
	
	'single_menu_link_colour_hover' => '#000000',
	
	'disable_scrolling' => false,
	
	'word_wrap' => false,

    'search_text' => 'Search',

    /* Added in PRO 1.0.8 */

    'include_header_search' => false,

    'header_search_position' => 'before_logo',

    'custom_css' => null,
    
    'header_bar_logo_link' => null,
	
);


/*
|--------------------------------------------------------------------------
| Save Defaults to the Registry
|--------------------------------------------------------------------------
|
| Again, some people don't like Registry's in PHP Applications, but here we
| have it again and it is very useful for retrieving our default values
| throughout the app
|
*/

ResponsiveMenuPro_Registry::set( 'defaults', $defaults );
