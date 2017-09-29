<?php

class ResponsiveMenuPro_Models_Admin extends ResponsiveMenuPro_Models_Base {
    
        
    /**
     * Saves all the data from the admin page to the database
     *
     * @param  array  $data
     * @return null
     * @added 1.0
     */
    
    static public function save( $data ) {
        
        // Initialise Variables Correctly
        
        $menu = isset($data['menu']) ? $data['menu'] : ResponsiveMenuPro_Registry::get( 'defaults', 'menu' );
        
        $title = isset($data['title']) ? $data['title'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title' );
        
        $breakpoint = $data['breakpoint'] ? $data['breakpoint'] : ResponsiveMenuPro_Registry::get( 'defaults', 'breakpoint' );
        
        $depth = isset($data['depth']) ? $data['depth'] : ResponsiveMenuPro_Registry::get( 'defaults', 'depth' );
        
        $top = isset($data['top']) ? $data['top'] : ResponsiveMenuPro_Registry::get( 'defaults', 'top' );
        
        $right = isset($data['right']) ? $data['right'] : ResponsiveMenuPro_Registry::get( 'defaults', 'right' );
        
        $css = isset($data['css']) ? $data['css'] : ResponsiveMenuPro_Registry::get( 'defaults', 'css' );
        
        $button_line_colour = !empty($data['button_line_colour']) ? $data['button_line_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_line_colour' );
        
        $button_background_colour = !empty($data['button_background_colour']) ? $data['button_background_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_background_colour' );
        
        $button_text = isset($data['button_text']) ? $data['button_text'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_text' );
        
        $is_button_background_transparent = isset($data['is_button_background_transparent']) ? $data['is_button_background_transparent'] : false;
        
        $is_fixed = isset($data['is_fixed']) ? $data['is_fixed'] : ResponsiveMenuPro_Registry::get( 'defaults', 'is_fixed' );
        
        $title_image = isset($data['title_image']) ? $data['title_image'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_image' );
        
        $width = $data['width'] ? $data['width'] : ResponsiveMenuPro_Registry::get( 'defaults', 'width' );
        
        $background_colour = !empty($data['background_colour']) ? $data['background_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'background_colour' );
        
        $background_colour_hover = !empty($data['background_colour_hover']) ? $data['background_colour_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'background_colour_hover' );
        
        $title_colour = !empty($data['title_colour']) ? $data['title_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_colour' );
        
        $text_colour = !empty($data['text_colour']) ? $data['text_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'text_colour' );
        
        $link_border_colour = !empty($data['link_border_colour']) ? $data['link_border_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'link_border_colour' );
        
        $text_colour_hover = !empty($data['text_colour_hover']) ? $data['text_colour_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'text_colour_hover' );
        
        $title_colour_hover = !empty($data['title_colour_hover']) ? $data['title_colour_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_colour_hover' );

        /* Added in 1.6 */
        
        $animation_type = isset($data['animation_type']) ? $data['animation_type'] : ResponsiveMenuPro_Registry::get( 'defaults', 'animation_type' );
        
        $push_animation_container_css = isset($data['push_animation_container_css']) ? $data['push_animation_container_css'] : ResponsiveMenuPro_Registry::get( 'defaults', 'push_animation_container_css' );
        
        $title_background_colour = !empty($data['title_background_colour']) ? $data['title_background_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_background_colour' );
        
        $font =  isset($data['font']) ? $data['font'] : ResponsiveMenuPro_Registry::get( 'defaults', 'font' );
        
        $link_text_size = $data['link_text_size'] ? $data['link_text_size'] : ResponsiveMenuPro_Registry::get( 'defaults', 'link_text_size' );
        
        $title_text_size = $data['title_text_size'] ? $data['title_text_size'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_text_size' );
        
        $button_text_size = $data['button_text_size'] ? $data['button_text_size'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_text_size' );
        
        $current_page_link_background = !empty($data['current_page_link_background']) ? $data['current_page_link_background'] : ResponsiveMenuPro_Registry::get( 'defaults', 'current_page_link_background' );
        
        $current_page_link_colour = !empty($data['current_page_link_colour']) ? $data['current_page_link_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'current_page_link_colour' );
 
        $animation_speed = $data['animation_speed'] !== false ? $data['animation_speed'] : ResponsiveMenuPro_Registry::get( 'defaults', 'animation_speed' );

        /* Added in 1.7 */
        
        $transition_speed = $data['transition_speed'] ? $data['transition_speed'] : ResponsiveMenuPro_Registry::get( 'defaults', 'transition_speed' );
        
        $text_align = isset($data['text_align']) ? $data['text_align'] : ResponsiveMenuPro_Registry::get( 'defaults', 'text_align' );
        
        $include_search = isset($data['include_search']) ? $data['include_search'] : ResponsiveMenuPro_Registry::get( 'defaults', 'include_search' );
        
        $auto_expand_submenus = isset($data['auto_expand_submenus']) ? $data['auto_expand_submenus'] : ResponsiveMenuPro_Registry::get( 'defaults', 'auto_expand_submenus' );
        
        $link_height = isset( $data['link_height'] ) ? $data['link_height'] : ResponsiveMenuPro_Registry::get( 'defaults', 'link_height' );

        /* Added in 1.8 */
        
        $create_external_scripts = isset( $data['create_external_scripts'] ) ? $data['create_external_scripts'] : ResponsiveMenuPro_Registry::get( 'defaults', 'create_external_scripts' );
        
        $slide_side = isset( $data['slide_side'] ) ? $data['slide_side'] : ResponsiveMenuPro_Registry::get( 'defaults', 'slide_side' );

        /* Added in 1.9 */
        
        $scripts_in_footer = isset( $data['scripts_in_footer'] ) ? $data['scripts_in_footer'] : ResponsiveMenuPro_Registry::get( 'defaults', 'scripts_in_footer' );
        
        $button_image = isset( $data['button_image'] ) ? $data['button_image'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_image' );
        
        $minify = isset( $data['minify'] ) ? $data['minify'] : ResponsiveMenuPro_Registry::get( 'defaults', 'minify' );
        
        $close_on_link_click = isset( $data['close_on_link_click'] ) ? $data['close_on_link_click'] : ResponsiveMenuPro_Registry::get( 'defaults', 'close_on_link_click' );
        
        $remove_important = isset( $data['remove_important'] ) ? $data['remove_important'] : ResponsiveMenuPro_Registry::get( 'defaults', 'remove_important' ); 

        $use_x = isset( $data['use_x'] ) ? $data['use_x'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_x' );
        
        $min_width = isset( $data['min_width'] ) ? $data['min_width'] : ResponsiveMenuPro_Registry::get( 'defaults', 'min_width' );

        /* Added in 2.0 */
        
        $max_width = isset( $data['max_width'] ) ? $data['max_width'] : ResponsiveMenuPro_Registry::get( 'defaults', 'max_width' );
        
        $expand_parents = isset( $data['expand_parents'] ) ? $data['expand_parents'] : false;
        
        $ignore_parent_clicks = isset( $data['ignore_parent_clicks'] ) ? $data['ignore_parent_clicks'] : ResponsiveMenuPro_Registry::get( 'defaults', 'ignore_parent_clicks' );
        
        $click_to_close = isset( $data['click_to_close'] ) ? $data['click_to_close'] : ResponsiveMenuPro_Registry::get( 'defaults', 'click_to_close' );
        
        $search_position = isset( $data['search_position'] ) ? $data['search_position'] : ResponsiveMenuPro_Registry::get( 'defaults', 'search_position' );
        
        $title_link = isset( $data['title_link'] ) ? $data['title_link'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_link' );
        
        $title_target = isset( $data['title_target'] ) ? $data['title_target'] : ResponsiveMenuPro_Registry::get( 'defaults', 'title_target' );
        
        $html= isset( $data['html'] ) ? $data['html'] : ResponsiveMenuPro_Registry::get( 'defaults', 'html' );
        
        $html_location = isset( $data['html_location'] ) ? $data['html_location'] : ResponsiveMenuPro_Registry::get( 'defaults', 'html_location' );
        
        /* Added in 2.1 */
        
        $use_shortcode = isset( $data['use_shortcode'] ) ? $data['use_shortcode'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_shortcode' );
        
        /* Added in 2.2 */
        
        $line_height = isset( $data['line_height'] ) ? $data['line_height'] : ResponsiveMenuPro_Registry::get( 'defaults', 'line_height' );
        
        $line_width = isset( $data['line_width'] ) ? $data['line_width'] : ResponsiveMenuPro_Registry::get( 'defaults', 'line_width' );
        
        $line_margin = isset( $data['line_margin'] ) ? $data['line_margin'] : ResponsiveMenuPro_Registry::get( 'defaults', 'line_margin' );
        
        $button_image_clicked = isset( $data['button_image_clicked'] ) ? $data['button_image_clicked'] : ResponsiveMenuPro_Registry::get( 'defaults', 'button_image_clicked' );
        
        $use_accordion = isset( $data['use_accordion'] ) ? $data['use_accordion'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_accordion' );
        
        $arrow_shape_active = isset( $data['arrow_shape_active'] ) ? $data['arrow_shape_active'] : ResponsiveMenuPro_Registry::get( 'defaults', 'arrow_shape_active' );
        
        $arrow_shape_inactive = isset( $data['arrow_shape_inactive'] ) ? $data['arrow_shape_inactive'] : ResponsiveMenuPro_Registry::get( 'defaults', 'arrow_shape_inactive' );
        
        $array_image_active = isset( $data['array_image_active'] ) ? $data['array_image_active'] : ResponsiveMenuPro_Registry::get( 'defaults', 'array_image_active' );
        
        $arrow_image_inactive = isset( $data['arrow_image_inactive'] ) ? $data['arrow_image_inactive'] : ResponsiveMenuPro_Registry::get( 'defaults', 'arrow_image_inactive' );
        
        /* Added in 2.3 */
        
        $trigger = isset( $data['trigger'] ) && !empty( $data['trigger'] ) ? $data['trigger'] : ResponsiveMenuPro_Registry::get( 'defaults', 'trigger' );  
        
        $use_push_animation = isset( $data['use_push_animation'] ) ? $data['use_push_animation'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_push_animation' );  
        
        $current_background_hover = !empty($data['current_background_hover']) ? $data['current_background_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'current_background_hover' );
        
        $current_colour_hover = !empty($data['current_colour_hover']) ? $data['current_colour_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'current_colour_hover' );
        
         /* Add by MKDGS */
        $walker = isset( $data['walker'] ) ? $data['walker'] : ResponsiveMenuPro_Registry::get( 'defaults', 'walker' );

        /* Added in 2.4 */
        
        $use_transient_caching = isset( $data['use_transient_caching'] ) ? $data['use_transient_caching'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_transient_caching' );
        
        $location = isset( $data['location'] ) ? $data['location'] : ResponsiveMenuPro_Registry::get( 'defaults', 'location' );
        
        /* Added in 2.6 Mkdgs */
        
        $theme_location = isset( $data['theme_location'] ) ? $data['theme_location'] : ResponsiveMenuPro_Registry::get( 'defaults', 'theme_location' );
        
		/* Added in PRO 1.0 */
		
		$use_only_on_mobile = isset( $data['use_only_on_mobile'] ) ? $data['use_only_on_mobile'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_only_on_mobile' );
		
		$use_single_site_menu = isset( $data['use_single_site_menu'] ) ? $data['use_single_site_menu'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_single_site_menu' );
		
		$set_auto_menu_height = isset( $data['set_auto_menu_height'] ) ? $data['set_auto_menu_height'] : ResponsiveMenuPro_Registry::get( 'defaults', 'set_auto_menu_height' );
		
		$use_header_bar = isset( $data['use_header_bar'] ) ? $data['use_header_bar'] : ResponsiveMenuPro_Registry::get( 'defaults', 'use_header_bar' );
		
		$header_bar_logo = isset( $data['header_bar_logo'] ) ? $data['header_bar_logo'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_bar_logo' );
		
		$header_bar_html = isset( $data['header_bar_html'] ) ? $data['header_bar_html'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_bar_html' );
		
		$header_bar_height = isset( $data['header_bar_height'] ) ? $data['header_bar_height'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_bar_height' );
		
		$header_bar_background_colour = isset( $data['header_bar_background_colour'] ) ? $data['header_bar_background_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_bar_background_colour' );
        
		$single_menu_height = isset( $data['single_menu_height'] ) ? $data['single_menu_height'] : ResponsiveMenuPro_Registry::get( 'defaults', 'single_menu_height' );
		
		$single_menu_link_colour = isset( $data['single_menu_link_colour'] ) ? $data['single_menu_link_colour'] : ResponsiveMenuPro_Registry::get( 'defaults', 'single_menu_link_colour' );
        
		$single_menu_link_colour_hover = isset( $data['single_menu_link_colour_hover'] ) ? $data['single_menu_link_colour_hover'] : ResponsiveMenuPro_Registry::get( 'defaults', 'single_menu_link_colour_hover' );
		
		$disable_scrolling = isset( $data['disable_scrolling'] ) ? $data['disable_scrolling'] : ResponsiveMenuPro_Registry::get( 'defaults', 'disable_scrolling' );
		
		$word_wrap = isset( $data['word_wrap'] ) ? $data['word_wrap'] : ResponsiveMenuPro_Registry::get( 'defaults', 'word_wrap' ); 
        
        $search_text = isset( $data['search_text'] ) ? $data['search_text'] : ResponsiveMenuPro_Registry::get( 'defaults', 'search_text' ); 
        		
        /* Added in PRO 1.0.8 */

        $include_header_search = isset( $data['include_header_search'] ) ? $data['include_header_search'] : ResponsiveMenuPro_Registry::get( 'defaults', 'include_header_search' ); 
       
        $header_search_position = isset( $data['header_search_position'] ) ? $data['header_search_position'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_search_position' ); 
        
        $custom_css = isset( $data['custom_css'] ) ? $data['custom_css'] : ResponsiveMenuPro_Registry::get( 'defaults', 'custom_css' ); 

        $header_bar_logo_link = isset( $data['header_bar_logo_link'] ) ? $data['header_bar_logo_link'] : ResponsiveMenuPro_Registry::get( 'defaults', 'header_bar_logo_link' ); 

        $optionsArray = array(
            
            // Filter Input Correctly
            
            'menu' => self::Filter($menu),
            
            'breakpoint' => intval($breakpoint),
            
            'depth' => intval($depth),
            
            'top' => intval($top),
            
            'right' => intval($right),
            
            'css' => self::Filter($css),
            
            'title' => self::Filter($title),
            
            'button_line_colour' => self::Filter($button_line_colour),
            
            'button_background_colour' => self::Filter($button_background_colour),
            
            'button_text' => self::Filter($button_text),
            
            'is_button_background_transparent' => self::Filter($is_button_background_transparent),
            
            'font' => self::Filter($font),
            
            'is_fixed' => self::Filter($is_fixed),
            
            'title_image' => self::Filter($title_image),
            
            'width' => intval($width),
            
            'background_colour' => self::Filter($background_colour),
            
            'background_colour_hover' => self::Filter($background_colour_hover),
            
            'title_colour' => self::Filter($title_colour),
            
            'text_colour' => self::Filter($text_colour),
            
            'link_border_colour' => self::Filter($link_border_colour),
            
            'text_colour_hover' => self::Filter($text_colour_hover),
            
            'title_colour_hover' => self::Filter($title_colour_hover),

            /* Added in 1.6 */
            
            'animation_type' => self::Filter($animation_type),
            
            'push_animation_container_css' => self::Filter($push_animation_container_css),
            
            'title_background_colour' => self::Filter( $title_background_colour ),
            
            'link_text_size' => intval( $link_text_size ),
            
            'title_text_size' => intval( $title_text_size ),
            
            'button_text_size' => intval( $button_text_size ),
            
            'current_page_link_background' => self::Filter( $current_page_link_background ),
            
            'current_page_link_colour' => self::Filter( $current_page_link_colour ),
            
            'animation_speed' => floatval( $animation_speed ),

            /* Added in 1.7 */
            
            'transition_speed' => floatval( $transition_speed ),
            
            'text_align' => self::Filter( $text_align ),
            
            'include_search' => self::Filter( $include_search ),
            
            'auto_expand_submenus' => self::Filter( $auto_expand_submenus ),    
            
            'link_height' => intval( $link_height ),

            /* Added in 1.8 */
            
            'create_external_scripts' => self::Filter( $create_external_scripts ),
            
            'slide_side' => self::Filter( $slide_side ),

            /* Added in 1.9 */
            
            'scripts_in_footer' => self::Filter( $scripts_in_footer ),    
            
            'button_image' => self::Filter( $button_image ),
            
            'minify' => self::Filter( $minify ),
            
            'close_on_link_click' => self::Filter( $close_on_link_click ),
            
            'remove_important' => self::Filter( $remove_important ),
            
            'use_x' => self::Filter( $use_x ),
            
            'min_width' => intval( $min_width ),

            /* Added in 2.0 */
            
            'max_width' => intval( $max_width ),
            
            'expand_parents' => self::Filter( $expand_parents ),
            
            'ignore_parent_clicks' => self::Filter( $ignore_parent_clicks ),
            
            'click_to_close' => self::Filter( $click_to_close ),
            
            'search_position' => self::Filter( $search_position ),
            
            'title_link' => self::Filter( $title_link ),
                
            'title_target' => self::Filter( $title_target ),
            
            'html' => self::FilterHtml( $html ),
            
            'html_location' => self::Filter( $html_location ),
            
            
            /* Added in 2.1 */
            
            'use_shortcode' => self::Filter( $use_shortcode ),
                
                
            /* Added in 2.2 */
            
            'line_height' => intval( $line_height ),
            
            'line_width' => intval( $line_width ),
            
            'line_margin' => intval( $line_margin ),
             
            'button_image_clicked' => self::Filter( $button_image_clicked ),
            
            'use_accordion' => self::Filter( $use_accordion ),
            
            'arrow_shape_active' => json_encode( $arrow_shape_active ),
            
            'arrow_shape_inactive' => json_encode( $arrow_shape_inactive ),
            
            'array_image_active' => self::Filter( $array_image_active ),
            
            'arrow_image_inactive' => self::Filter( $arrow_image_inactive ),
            
            
            /* Added in 2.3 */
            
            'trigger' => self::Filter( $trigger ),
            
            'use_push_animation' => self::Filter( $use_push_animation ),
            
            'current_background_hover' => self::Filter( $current_background_hover ),
            
            'current_colour_hover' => self::Filter( $current_colour_hover ),

            /* Add by Mkdgs */
            
            'walker' => ( class_exists( $walker ) ) ? $walker : '',
            
            /* Added in 2.4 */
            
            'use_transient_caching' => self::Filter( $use_transient_caching ),
            
            'location' => self::Filter( $location ),
            
            /* Added in 2.6 Mkdgs */          
            
            'theme_location' => self::Filter( $theme_location ),
            
			/* Added in PRO 1.0 */
			
			'use_only_on_mobile' => self::Filter( $use_only_on_mobile ),
			
			'use_single_site_menu' => self::Filter( $use_single_site_menu ),
			
			'set_auto_menu_height' => self::Filter( $set_auto_menu_height ),
			
			'use_header_bar' => self::Filter( $use_header_bar ),
			
			'header_bar_logo' => self::Filter( $header_bar_logo ),
			
			'header_bar_html' => self::FilterHtml( $header_bar_html ),
			
			'header_bar_height' => intval( $header_bar_height ),
			
			'header_bar_background_colour' => self::Filter( $header_bar_background_colour ),
			
			'single_menu_height' => intval( $single_menu_height ),
			
			'single_menu_link_colour' => self::Filter( $single_menu_link_colour ),
			
			'single_menu_link_colour_hover' => self::Filter( $single_menu_link_colour_hover ),
			
			'disable_scrolling' => self::Filter( $disable_scrolling ),
			
			'word_wrap' => self::Filter( $word_wrap ),

            'search_text' => self::Filter( $search_text ),
			
            /* Added in PRO 1.0.8 */

            'include_header_search' => self::Filter( $include_header_search ),

            'header_search_position' => self::Filter( $header_search_position ),

            'custom_css' => self::Filter( $custom_css ),
            
            'header_bar_logo_link' => self::Filter( $header_bar_logo_link ),            

        );

        // Update Submitted Options 
        
        update_option( 'responsive_menu_pro_options', $optionsArray );
        
        // Clear Transient Menus
        ResponsiveMenuPro_Transient::clearTransientMenus();
        
        // And save the status
        ResponsiveMenuPro_Status::set( 'updated', __( 'You have successfully updated the Responsive Menu Pro options', 'responsive-menu-pro' ) );
           
    }
    
    
}