<?php

class ResponsiveMenuPro_Models_JS extends ResponsiveMenuPro_Models_Base {
    
    /**
     * Function to format, create and get the JS itself
     *
     * @param array $options
     * @return string
     * @added 1.0
     */
    
    static function getJS( $options ) {
        
        
        /*
        $setHeight = " 

            \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( 
                'height', \$responsive_menu_pro_jquery( document ).height() 
            ); 

        "; */

        $setHeight = '';
        

        $breakpoint = empty($options['breakpoint']) ? "600" : $options['breakpoint'];
        
        $push_animation_container_css = empty( $options['push_animation_container_css'] ) ? "" : $options['push_animation_container_css'];

        $slideOpen = $options['animation_type'] == 'push' && !empty($options['push_animation_container_css']) ? " \$responsive_menu_pro_jquery( 'body' ).addClass( 'responsive_menu_pro_push_open' ); " : '';
        $slideRemove = $options['animation_type'] == 'push' && !empty($options['push_animation_container_css']) ? " \$responsive_menu_pro_jquery( 'body' ).removeClass( 'responsive_menu_pro_push_open' ); " : '';

        /* Added 1.8 */
        switch( $options['slide_side'] ) :
            case 'left' : $side = 'left'; break;
            case 'right' : $side = 'right'; break;
            case 'top' : $side = 'top'; break;
            case 'bottom' : $side = 'top'; break;
            default : $side = 'left'; break;
        endswitch;
                
        /* Added 2.0 */
        switch( $options['slide_side'] ) :
            case 'left' : $width = $options['width']; $neg = '-'; break;
            case 'right' : $width = $options['width']; $neg = '-'; break;
            case 'top' : $width = '100'; $neg = '-'; break;
            case 'bottom' : $width = '100'; $neg = ''; break;
            default : $width = '75'; break;
        endswitch;
        
        switch( $options['slide_side']  ) :
            case 'left' : $pushSide = 'left'; $pos = ''; break;
            case 'right' : $pushSide = 'left'; $pos = '-'; break;
            case 'top' : $pushSide = 'marginTop'; $pos = ''; break;
            case 'bottom' : $pushSide = 'marginTop'; $pos = '-'; break;
        endswitch;

        switch( $options['slide_side']  ) :
            case 'left' : $pushBtnSide = 'left';  break;
            case 'right' : $pushBtnSide = 'right';  break;
            case 'top' : $pushBtnSide = 'top';  break;
            case 'bottom' : $pushBtnSide = 'bottom'; break;
        endswitch;
        
        $sideSlideOpen = $side == 'right' && empty( $slideOpen ) ? " \$responsive_menu_pro_jquery( 'body' ).addClass( 'responsive_menu_pro_push_open' ); " : '';
        $sideSlideRemove =  $side == 'right' && empty( $slideRemove ) ? " \$responsive_menu_pro_jquery( 'body' ).removeClass( 'responsive_menu_pro_push_open' ); " : '';
        
        /* Added 2.3 */
        
        $trigger = isset( $options['trigger'] ) ? $options['trigger'] : ResponsiveMenuPro_Registry::get( 'defaults', 'trigger' );
        
        $speed = $options['animation_speed'] * 1000;
        
        /* Added 2.5 */
        
        $location = $options['location'];
		
		/* Added 1.0 PRO */
		$use_as_only_menu = empty( $options['use_single_site_menu'] ) ? false : true;
        $disable_scrolling = empty( $options['disable_scrolling'] ) ? false : true;
		 
        $resetBottom =  empty( $options['set_auto_menu_height'] ) ? "" : " 

            $setHeight

            currentMenuHeight = \$responsive_menu_pro_jquery( '#responsive_menu_pro_container' ).height() + 
                                \$responsive_menu_pro_jquery( '#responsive_menu_pro_title' ).height() +
                                \$responsive_menu_pro_jquery( '#responsive_menu_pro_search' ).height() + 50;

            currentDocHeight = \$responsive_menu_pro_jquery( window ).height();

            if( currentMenuHeight > currentDocHeight ) {
                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css('bottom', '0');   
            } else {
                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css('bottom', 'auto');
            }

        ";

        /*
        |--------------------------------------------------------------------------
        | Slide Push Animation
        |--------------------------------------------------------------------------
        |
        | This is where we deal with the JavaScript needed to change the main lines
        | to an X if this option has been set
        |
        */

        $slideOver = " var MenuWidth = \$responsive_menu_pro_jquery('#responsive_menu_pro').width(); ";

        if( $options['animation_type'] == 'push' ) :

            if( $options['slide_side'] == 'top' || $options['slide_side'] == 'bottom' ) :

                $slideOver .= "

                    var MenuHeight = \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( 'height' );

                    \$responsive_menu_pro_jquery( '$push_animation_container_css' ).animate( { $pushSide: \"{$pos}\" + MenuHeight }, {$speed}, 'linear' );


                ";

                if( $options['use_push_animation'] ) :

                    $slideOver .= "

                        \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).animate( { $pushBtnSide: \"{$pos}\" + MenuHeight }, {$speed}, 'linear' );
                        \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).css( '$location', 'auto' );

                        ";

                endif;

            else :

                $slideOver .= "

                    \$responsive_menu_pro_jquery( '$push_animation_container_css' ).animate( { $pushSide: {$pos}MenuWidth }, {$speed}, 'linear' );


                ";

                if( $options['use_push_animation']  && ( $pushBtnSide == $location ) ) :

                    $slideOver .= "

                        \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).animate( { $location: MenuWidth + 20 }, {$speed}, 'linear' );
                        \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).css( '{$location}', 'auto' );

                    ";

                endif;


            endif;

        endif;

        $slideOverCss = $options['animation_type'] == 'push' && !empty($options['push_animation_container_css']) ? " \$responsive_menu_pro_jquery( '$push_animation_container_css' ).addClass( 'responsive_menu_pro_push_slide' ); " : '';

        $slideBack = $options['animation_type'] == 'push' && !empty($options['push_animation_container_css']) ? " \$responsive_menu_pro_jquery( '$push_animation_container_css' ).animate( { $pushSide: \"0\" }, {$speed}, 'linear' ); " : '';

        if( $options['use_push_animation'] && $options['animation_type'] == 'push' && ( $pushBtnSide == $location ) ) :

            $slideBack .= "

                \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).animate( { $pushBtnSide: '{$options['right']}%' }, {$speed}, 'linear', function() {

                    \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).removeAttr( 'style' );

                });

            ";

        endif;

        $slideOverCssRemove = $options['animation_type'] == 'push' && !empty($options['push_animation_container_css']) ? " \$responsive_menu_pro_jquery( '$push_animation_container_css' ).removeClass( 'responsive_menu_pro_push_slide' ); " : '';



        /*
        |--------------------------------------------------------------------------
        | Change to X or Clicked Menu Image Option
        |--------------------------------------------------------------------------
        |
        | This is where we deal with the JavaScript needed to change the main lines
        | to an X or click image if this option has been set
        |
        */

        if( $options['use_x'] || $options['button_image_clicked'] ) : 

            $closeX = " \$responsive_menu_pro_jquery( '#responsive_menu_pro_button #responsive_menu_x' ).css( 'display', 'none' );
                        \$responsive_menu_pro_jquery( '#responsive_menu_pro_button #responsive_menu_pro_three_lines' ).css( 'display', 'block' ); ";

            $showX = " \$responsive_menu_pro_jquery( '#responsive_menu_pro_button #responsive_menu_pro_three_lines' ).css( 'display', 'none' );
                         \$responsive_menu_pro_jquery( '#responsive_menu_pro_button #responsive_menu_x' ).css( 'display', 'block' ); ";        
        else :

            $closeX = "";
            $showX = "";

        endif;            

        /*
        |--------------------------------------------------------------------------
        | Menu Expansion Options
        |--------------------------------------------------------------------------
        |
        | This is where we deal with the array of expansion options, the current
        | combinations are:
        |
        | - Auto Expand Current Parent Items ['expand_parents']
        | - Auto Expand Current Parent Items + Auto Expand Sub-Menus ['expand_parents'] && ['auto_expand_submenus']
        | - Auto Expand Sub-Menus ['auto_expand_submenus']
        | - None !['expand_parents'] && !['auto_expand_submenus']
        |
        */

        $activeArrow = $options['array_image_active'] ? '<img src="' . $options['array_image_active'] . '" />' : json_decode( $options['arrow_shape_active'] );
        $inactiveArrow = $options['arrow_image_inactive'] ? '<img src="' . $options['arrow_image_inactive'] . '" />' : json_decode( $options['arrow_shape_inactive'] );
           

        if ( !$options['auto_expand_submenus'] ) :

            $clickedLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_inactive\">' . $inactiveArrow . '</span>';  
            $clickLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_inactive\">' . $inactiveArrow . '</span>';  

        else :

            $clickedLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_active\">' . $activeArrow . '</span>';
            $clickLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_active\">' . $activeArrow . '</span>'; 

        endif;

        if( $options['expand_parents'] ) :

            $clickedLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_active\">' . $activeArrow . '</span>';
            $clickLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_inactive\">' . $inactiveArrow . '</span>'; 

        endif;

        if( $options['expand_parents'] && $options['auto_expand_submenus'] ) :

            $clickedLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_active\">' . $activeArrow . '</span>';
            $clickLink = '<span class=\"responsive_menu_pro_append_link responsive_menu_pro_append_active\">' . $activeArrow . '</span>'; 

        endif;


        /*
        |--------------------------------------------------------------------------
        | Initialise Output
        |--------------------------------------------------------------------------
        |
        | Initialise the JavaScript output variable ready for appending
        |
        */   

        $js = null;

        /*
        |--------------------------------------------------------------------------
        | Strip Tags If Needed
        |--------------------------------------------------------------------------
        |
        | Determine whether to use the <script> tags (when using internal scripts)
        |
        */       

        $js .= $options['create_external_scripts'] ? '' : '<script>';

        /*
        |--------------------------------------------------------------------------
        | Initial Setup
        |--------------------------------------------------------------------------
        |
        | Setup the initial noConflict and document ready checks
        |
        */   

        $js .= "

            var \$responsive_menu_pro_jquery = jQuery.noConflict();

            \$responsive_menu_pro_jquery( document ).ready( function() { ";

        /*
        |--------------------------------------------------------------------------
        | Stop Main Parent Item Clicks
        |--------------------------------------------------------------------------
        |
        | Stop clicks on the main parent items if option selected
        | Added 2.0
        */ 

        if( $options['ignore_parent_clicks'] ) :

            $js .= "

                \$responsive_menu_pro_jquery( '#responsive_menu_pro ul > li.menu-item-has-children' ).children( 'a' ).addClass( 'responsive_menu_parent_click_disabled' );

                \$responsive_menu_pro_jquery( '#responsive_menu_pro ul > li.menu-item-has-children' ).children( 'a' ).on( 'click', function( e ) {

                    e.preventDefault();

                });

                \$responsive_menu_pro_jquery( '#responsive_menu_pro ul > li.page_item_has_children' ).children( 'a' ).addClass( 'responsive_menu_parent_click_disabled' );

                \$responsive_menu_pro_jquery( '#responsive_menu_pro ul > li.page_item_has_children' ).children( 'a' ).on( 'click', function( e ) {

                    e.preventDefault();

                });

            ";

        endif;

        /*
        |--------------------------------------------------------------------------
        | Closes the menu on page clicks
        |--------------------------------------------------------------------------
        |
        | Close menu on page clicks if required
        | Added 2.0
        */ 

        if( $options['click_to_close'] ) :

            $js .= "

                \$responsive_menu_pro_jquery( document ).bind( 'vclick', function( e ) { 

                    if( e.which != 2 && !\$responsive_menu_pro_jquery( e.target ).closest( '#responsive_menu_pro, {$trigger}' ).length ) { 

                        closeRM(); 

                    } 

                });

            ";

        endif;


         /*
        |--------------------------------------------------------------------------
        | Click Menu Function
        |--------------------------------------------------------------------------
        |
        | This is our Click Handler to determine whether or not to open or close 
        | the menu when the click menu button has been clicked.
        |
        */

        $js .= "

            var isOpen = false;

            \$responsive_menu_pro_jquery( document ).on( 'click', '{$trigger}', function() {
            		
		"; 
		
		if( $use_as_only_menu ) 
			$js .= "if( \$responsive_menu_pro_jquery( window ).width() <= $breakpoint ) { ";
         
         $js .= "$setHeight

                !isOpen ? openRM() : closeRM(); ";

        if( $use_as_only_menu ) 
            $js .= "}";
            
          $js .= " });

        ";

        /*
        |--------------------------------------------------------------------------
        | Menu Open Function
        |--------------------------------------------------------------------------
        |
        | This is the main function that deals with opening the menu and then sets
        | its state to open
        |
        */

        $js.= "

            function openRM() {
            				
            			
        		\$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( '$side', '' );

                $slideOpen  
                $sideSlideOpen
                $slideOverCss
                $slideOver
                $showX

                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( 'display', 'block' ); 
                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).addClass( 'responsive_menu_pro_opened' );  
                \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).addClass( 'responsive_menu_pro_button_active' );  

                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).stop().animate( { $side: \"0\" }, $speed, 'linear', function() { 

              	$setHeight ";

				if( $disable_scrolling ) : 
					$js .= " \$responsive_menu_pro_jquery( '#responsive_menu_pro_disable_scrolling' ).addClass( 'responsive_menu_pro_disable_scrolling_active' ); ";
					$js .= " \$responsive_menu_pro_jquery( 'body' ).addClass( 'responsive_menu_pro_disable_scrolling_body' ); ";			  
				endif;
					
                $js .= $resetBottom;
              	$js .= " isOpen = true;

                } ); 

            }

        ";

        /*
        |--------------------------------------------------------------------------
        | Menu Close Function
        |--------------------------------------------------------------------------
        |
        | This is the main function that deals with Closing the Menu and then sets
        | its state to closed
        |
        | Added by Bhupender
        | Modified negative width to take directly width of menu instead of '%', 
        | this works for condition where animation is push and max width of menu is less then %
        |
        */

        $js .= "

            function closeRM() {

                $slideBack

                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).animate( { $side: {$neg}\$responsive_menu_pro_jquery( '#responsive_menu_pro' ).width() }, $speed, 'linear', function() {

                    $slideRemove
                    $sideSlideRemove
                    $slideOverCssRemove
                    $closeX
                    \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( 'display', 'none' );  
                    \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).removeClass( 'responsive_menu_pro_opened' );  
                    \$responsive_menu_pro_jquery( '#responsive_menu_pro_button' ).removeClass( 'responsive_menu_pro_button_active' ); ";
					
					if( $use_as_only_menu ) :
					
						$js .= " if( \$responsive_menu_pro_jquery( window ).width() >= $breakpoint ) { 	
				 		\$responsive_menu_pro_jquery( '#responsive_menu_pro' ).removeAttr( 'style' ); } ";  	
				
					endif;	

				if( $disable_scrolling ) : 
					$js .= " \$responsive_menu_pro_jquery( '#responsive_menu_pro_disable_scrolling' ).removeClass( 'responsive_menu_pro_disable_scrolling_active' ); ";
					$js .= " \$responsive_menu_pro_jquery( 'body' ).removeClass( 'responsive_menu_pro_disable_scrolling_body' ); ";				  
				endif;

            	$js .= " isOpen = false;

                } ); ";
			



            $js .=  "}

        ";

        /*
        |--------------------------------------------------------------------------
        | Menu Resize Function
        |--------------------------------------------------------------------------
        |
        | This is the main function that deals with resizing the page and is used 
        | to judge whether the menu needs closing once the screen is resized
        |
        |
        | Added by Bhupender
        | - Modified negative width to take directly width of menu instead of '%', 
        |   this works for condition where animation is push and max width of menu is less then %

        */

        $js .= "

            \$responsive_menu_pro_jquery( window ).resize( function() { 

                \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).stop( true, true );

                $setHeight

                if( \$responsive_menu_pro_jquery( window ).width() >= $breakpoint ) { 

                    if( \$responsive_menu_pro_jquery( '#responsive_menu_pro' ).css( '$side' ) != -\$responsive_menu_pro_jquery( '#responsive_menu_pro' ).width() ) {

                        closeRM();

                    }

                }

            });

        ";


        /*
        |--------------------------------------------------------------------------
        | Expand children links of parents
        |--------------------------------------------------------------------------
        |
        | Section to automatically expand children links of parents if necessary
        | Added 2.0
        |
        */

        if( $options['expand_parents'] ) :
					
            $js .= "

                \$responsive_menu_pro_jquery( '#responsive_menu_pro ul ul' ).css( 'display', 'none' );

                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current_page_ancestor.menu-item-has-children' ).children( 'ul' ).css( 'display', 'block' );
                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current-menu-ancestor.menu-item-has-children' ).children( 'ul' ).css( 'display', 'block' );
                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current-menu-item.menu-item-has-children' ).children( 'ul' ).css( 'display', 'block' );

                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current_page_ancestor.page_item_has_children' ).children( 'ul' ).css( 'display', 'block' );
                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current-menu-ancestor.page_item_has_children' ).children( 'ul' ).css( 'display', 'block' );
                \$responsive_menu_pro_jquery( '#responsive_menu_pro .current-menu-item.page_item_has_children' ).children( 'ul' ).css( 'display', 'block' );

            ";
			
        endif;

        /*
        |--------------------------------------------------------------------------
        | Add Toggle Buttons
        |--------------------------------------------------------------------------
        |
        | This is the main section that deals with Adding the correct Toggle buttons
        | when needed to the links
        |
        */

        if( $options['auto_expand_submenus'] ) :
            $js .= " \$responsive_menu_pro_jquery( '#responsive_menu_pro ul ul' ).css( 'display', 'block' ); ";
        endif;
        
        $js .= " 

            var clickLink = '{$clickLink}';
            var clickedLink = '{$clickedLink}';

            \$responsive_menu_pro_jquery( '#responsive_menu_pro .responsive_menu_pro_menu li' ).each( function() {

                if( \$responsive_menu_pro_jquery( this ).children( 'ul' ).length > 0 ) {

                    if( \$responsive_menu_pro_jquery( this ).find( '> ul' ).css( 'display' ) == 'none' ) {

                        \$responsive_menu_pro_jquery( this ).prepend( clickLink );  

                    } else {

                        \$responsive_menu_pro_jquery( this ).prepend( clickedLink );  

                    }

                }

            });

        ";
        
        /*
        |--------------------------------------------------------------------------
        | Accordion Animation
        |--------------------------------------------------------------------------
        |
        | This is the part that deals with the accordion animation
        |
        */     

        if( $options['use_accordion'] && $options['use_accordion'] == 'accordion' ) :

            $accordion = " 

            if( \$responsive_menu_pro_jquery( this ).closest( 'ul' ).is( '.responsive_menu_pro_menu' ) ) {

                \$responsive_menu_pro_jquery( '.accordion-open' ).removeClass( 'accordion-open' );
                \$responsive_menu_pro_jquery( this ).parent( 'li' ).addClass( 'accordion-open' );

                \$responsive_menu_pro_jquery( '.responsive_menu_pro_menu > li:not( .accordion-open ) > ul' ).slideUp( function() 
                    {
                        $resetBottom
                    });

		if( \$responsive_menu_pro_jquery( this ).siblings( 'ul' ).is( ':visible' ) ) {
			\$responsive_menu_pro_jquery( this ).parent( 'li' ).removeClass( 'accordion-open' );	
		} else {
			\$responsive_menu_pro_jquery( this ).parent( 'li' ).addClass( 'accordion-open' );	
		}
		
		\$responsive_menu_pro_jquery( '.responsive_menu_pro_menu > li > .responsive_menu_pro_append_link' ).removeClass( 'responsive_menu_pro_append_inactive' );
		\$responsive_menu_pro_jquery( '.responsive_menu_pro_menu > li > .responsive_menu_pro_append_link' ).addClass( 'responsive_menu_pro_append_active' );				
                
                var AllClosed = true;
                
		\$responsive_menu_pro_jquery( '.responsive_menu_pro_menu > li > .responsive_menu_pro_append_link' ).each( function( i ) {
			\$responsive_menu_pro_jquery( this ).html( \$responsive_menu_pro_jquery( this ).hasClass( 'responsive_menu_pro_append_active' ) ? '{$inactiveArrow}' : '{$activeArrow}' );	
			AllClosed = \$responsive_menu_pro_jquery( this ).parent( 'li' ).hasClass( 'accordion-open' )? false : AllClosed;		
		});
		
		\$responsive_menu_pro_jquery( this ).removeClass( 'responsive_menu_pro_append_active' );
		\$responsive_menu_pro_jquery( this ).addClass( 'responsive_menu_pro_append_inactive' );
		
		if( AllClosed ) {
			\$responsive_menu_pro_jquery( this ).removeClass( 'responsive_menu_pro_append_inactive' );
			\$responsive_menu_pro_jquery( this ).addClass( 'responsive_menu_pro_append_active' );
		
		}

            }



            ";

        else :

            $accordion = null;

        endif;


        /*
        |--------------------------------------------------------------------------
        | Toggle Buttons Function
        |--------------------------------------------------------------------------
        |
        | This is the function that deals with toggling the toggle buttons
        |
        */                

        $js .= "   

            \$responsive_menu_pro_jquery( '.responsive_menu_pro_append_link' ).on( 'click', function() { 

                $accordion

                \$responsive_menu_pro_jquery( this ).nextAll( '#responsive_menu_pro ul ul' ).slideToggle(function() {

                    $resetBottom;

                }); 

                \$responsive_menu_pro_jquery( this ).html( \$responsive_menu_pro_jquery( this ).hasClass( 'responsive_menu_pro_append_active' ) ? '{$inactiveArrow}' : '{$activeArrow}' );
                \$responsive_menu_pro_jquery( this ).toggleClass( 'responsive_menu_pro_append_active responsive_menu_pro_append_inactive' );

            });

            \$responsive_menu_pro_jquery( '.responsive_menu_parent_click_disabled' ).on( 'click', function() { 

                $accordion

                \$responsive_menu_pro_jquery( this ).nextAll( '#responsive_menu_pro ul ul' ).slideToggle( function() {
                    $resetBottom
                }); 

                \$responsive_menu_pro_jquery( this ).siblings( '.responsive_menu_pro_append_link' ).html( \$responsive_menu_pro_jquery( this ).hasClass( 'responsive_menu_pro_append_active' ) ? '{$inactiveArrow}' : '{$activeArrow}' );
                \$responsive_menu_pro_jquery( this ).toggleClass( 'responsive_menu_pro_append_active responsive_menu_pro_append_inactive' );

                $setHeight

            }); 

        ";

        /*
        |--------------------------------------------------------------------------
        | Finally Hide Appropriate Hidden Objects
        |--------------------------------------------------------------------------
        |
        | This is the function that deals with toggling the toggle buttons
        |
        */                

        $js .= "   

            \$responsive_menu_pro_jquery( '.responsive_menu_pro_append_inactive' ).siblings( 'ul' ).css( 'display', 'none' );

        ";

        /*
        |--------------------------------------------------------------------------
        | Menu Closing Options
        |--------------------------------------------------------------------------
        |
        | This is where we set the menu to retract if a link is clicked
        | Added 1.9
        |
        */

        if ( isset( $options['close_on_link_click'] ) && $options['close_on_link_click'] == 'close' ) : 

           $js .= " 
               \$responsive_menu_pro_jquery( '#responsive_menu_pro ul li a' ).on( 'click', function() { 

                   closeRM();

               } );";

        endif;
			
        /*
        |--------------------------------------------------------------------------
        | Close Tags
        |--------------------------------------------------------------------------
        |
        | This closes the initial document ready call
        |
        */ 

        $js .= ' }); ';

        /*
        |--------------------------------------------------------------------------
        | Strip Tags If Needed
        |--------------------------------------------------------------------------
        |
        | Determine whether to use the <script> tags (when using internal scripts)
        |
        */       

        $js .= $options['create_external_scripts'] ? '' : '</script>';


        /*
        |--------------------------------------------------------------------------
        | Return Finished Script
        |--------------------------------------------------------------------------
        |
        | Finally we return the final script back
        |
        */   

        return $js;

        
    }
    

}
