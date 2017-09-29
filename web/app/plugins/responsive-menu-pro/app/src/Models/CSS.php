<?php

class ResponsiveMenuPro_Models_CSS extends ResponsiveMenuPro_Models_Base {
    
    /**
     * Function to format, create and get the CSS itself
     *
     * @param string $args
     * @return string
     * @added 1.0
     */
    
    static function getCSS( $options ) {
        

        $important = empty( $options['remove_important'] ) ? ' !important;' : ';';
        
        $position = $options['is_fixed'] == 'fixed' ? 'fixed' : 'absolute';
        $overflowy = $options['is_fixed'] == 'fixed' ? 'overflow-y: auto;' : 'overflow-y: auto;';
        $bottom = $options['is_fixed'] == 'fixed' ? 'bottom: 0px;' : '';

        $right = empty($options['right']) ? '0' : $options['right'];
        
        $top = empty( $options['top']) ? '0' : $options['top'];
        
        $width = empty($options['width']) ? '75' : $options['width'];
        $mainBkg = empty($options['background_colour']) ? "#43494C" : $options['background_colour'];
        $mainBkgH = empty($options['background_colour_hover']) ? "#3C3C3C" : $options['background_colour_hover'];
        $font = empty($options['font']) ? '' : 'font-family: ' . $options['font'] . $important;
        $titleCol = empty($options['title_colour']) ? '#FFFFFF' : $options['title_colour'];
        $titleColH = empty($options['title_colour_hover']) ? '#FFFFFF' : $options['title_colour_hover'];
        $txtCol = empty($options['text_colour']) ? "#FFFFFF" : $options['text_colour'];
        $txtColH = empty($options['text_colour_hover']) ? "#FFFFFF" : $options['text_colour_hover'];
        $clickCol = empty($options['button_line_colour']) ? "#FFFFFF" : $options['button_line_colour'];
        $clickBkg = empty($options['is_button_background_transparent']) ? "background: {$options['button_background_colour']};" : '';
        $borCol = empty($options['link_border_colour']) ? "#3C3C3C" : $options['link_border_colour'];
        $breakpoint = empty($options['breakpoint']) ? "600" : $options['breakpoint'];
        $titleBkg = empty($options['title_background_colour']) ? "#43494C" : $options['title_background_colour'];
        
        $fontSize = empty($options['link_text_size']) ? 13 : $options['link_text_size'];
        $titleSize = empty($options['title_text_size']) ? 14 : $options['title_text_size'];                        
        $btnSize = empty($options['button_text_size']) ? 13 : $options['button_text_size'];
        
        $curBkg = empty($options['current_page_link_background']) ? $mainBkg : $options['current_page_link_background'];
        $curCol = empty($options['current_page_link_colour']) ? $txtCol : $options['current_page_link_colour'];
        
        /* Added 1.7 */
        $trans = empty( $options['transition_speed'] ) ? 1 : $options['transition_speed'];
        $align = empty( $options['text_align'] ) ? 'left' : $options['text_align'];
        $linkPadding = $options['text_align'] == 'right' ? '12px 5% 12px 0px' : '12px 0px 12px 5%';
        $titlePadding = $options['text_align'] == 'right' ? '20px 5% 20px 0px' : '20px 0px 20px 5%';
        $paddingAlign = $align == 'center' ? 'left' : $align;
        $height = empty( $options['link_height'] ) ? 19 : $options['link_height'];
        $subBtnAlign =   $align == 'right' ? 'left' : 'right';
        
        /* Added 1.8 */
        $side = empty( $options['slide_side'] ) ? 'left' : $options['slide_side'];
        
        /* Added 1.9 */
        $minWidth = empty( $options['min_width'] ) ? '' : 'min-width: ' . $options['min_width'] . 'px' . $important;
        
        /* Added 2.0 */
        $maxWidth = empty( $options['max_width'] ) ? '' : 'max-width: ' . $options['max_width'] . 'px' . $important;
        
        switch( $options['slide_side'] ) :
            case 'left' : $topRM = 'top: 0px'; $botRM = ''; break;
            case 'right' : $topRM = 'top: 0px'; $botRM = ''; break;
            case 'top' : $topRM = 'top: -100%'; $botRM = ''; break;
            case 'bottom' : $topRM = 'top: 100%'; $botRM = 'bottom: 0px' . $important; break;
        endswitch;
        
        switch( $side ) :
            case 'left' : $pushSide = $side; $pushWidth = $width; $pushPos = 'relative'; break;
            case 'right' : $pushSide = $side; $pushWidth = $width; $pushPos = 'relative'; break;
            case 'top' : $pushSide = 'top'; $pushWidth = '100'; $pushPos = 'relative'; break;
            case 'bottom' : $pushSide = 'bottom'; $pushWidth = '-100'; $pushPos = 'relative'; break;
            default : $pushSide = $side; $pushWidth = $width; break;
        endswitch;
  
        /* Added 2.2 */
        
        $lineHeight = empty( $options['line_height'] ) ? 6 : $options['line_height'];
        $lineWidth = empty( $options['line_width'] ) ? 33 : $options['line_width'];
        $lineMargin = empty( $options['line_margin'] ) ? 6 : $options['line_margin'];        
        $clickMenuHeight = ( $lineMargin * 2 ) + ( $lineHeight * 3 );
        
        /* Added 2.3 */
        
        $curBkgHov = empty( $options['current_background_hover'] ) ? $mainBkg : $options['current_background_hover'];
        $curColHov = empty( $options['current_colour_hover'] ) ? $txtCol : $options['current_colour_hover'];
        
        /* Added 2.5 */
        
        $location = $options['location'];
		
		/* Added PRO 1.0 */
		$use_as_only_menu = empty( $options['use_single_site_menu'] ) ? false : true;
		$set_auto_menu_height = empty( $options['set_auto_menu_height'] ) ? '' : 'height: auto; padding-bottom: 25px;';
        $use_header_bar = empty( $options['use_header_bar'] ) ? false : true;
		$header_bar_background_colour = $options['header_bar_background_colour'];
		$header_bar_height = $options['header_bar_height'];
		$single_menu_height = $options['single_menu_height'];
		$single_menu_link_colour = $options['single_menu_link_colour'];
		$single_menu_link_colour_hover = $options['single_menu_link_colour_hover'];
		$disable_scrolling = $options['disable_scrolling'];
		$word_wrap = !empty( $options['word_wrap'] ) ? ' white-space: normal' . $important . ' height: auto' . $important : ' white-space: nowrap' . $important;
		
 /*
|--------------------------------------------------------------------------
| Initialise Output
|--------------------------------------------------------------------------
|
| Initialise the JavaScript output variable ready for appending
|
*/   
        
$css = null;
        
/*
|--------------------------------------------------------------------------
| Strip Tags If Needed
|--------------------------------------------------------------------------
|
| Determine whether to use the <style> tags
|
*/       

$css .= $options['create_external_scripts'] ? '' : '<style>';       

/*
|--------------------------------------------------------------------------
| Styles for Disable Scrolling Overlay Option
|--------------------------------------------------------------------------
|
| Decided by the Disable scrolling option in admin
|
*/  

 if( $disable_scrolling ) :
	$css .= "
	
	body.responsive_menu_pro_disable_scrolling_body {
		height: 100%;
		overflow: hidden;
		position: fixed;
	}
	
	#responsive_menu_pro_disable_scrolling.responsive_menu_pro_disable_scrolling_active {
		position:fixed;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background: rgba( 0, 0, 0, 0.7 );
		width: 100%;
		height: 100%;
		z-index: 9998;
		overflow-y: scroll;
	} ";	
endif;

if($use_header_bar) :
    
    $css .= " 

        #responsive_menu_pro {
            overflow: auto;
            height: auto;
        } ";

endif;

    $css .= "

        #responsive_menu_pro.responsive_menu_pro_opened {
            position: fixed;
            bottom: 0;
        }

        ";
    
/*
|--------------------------------------------------------------------------
| Build Header Bar if Required
|--------------------------------------------------------------------------
|
| Decided by the Only Show On Mobile Option
|
*/   

if( $use_header_bar && !$use_as_only_menu ) :
	
	$css .= "	
	#responsive_menu_pro_header_bar {
		display: none;
	} ";

endif;

if( $use_header_bar ) :

    $css .= "
        #responsive_menu_pro_header_bar_logo,
        #responsive_menu_pro_header_search
        {
            display: inline;
        }

    ";

    if( !$use_as_only_menu ) :
	   $css .= " @media only screen and ( max-width : " . $breakpoint . "px ) { ";
	endif;

	$css .=	"

        #responsive_menu_pro_header_bar_left
        {
            float: left;
        }

        #responsive_menu_pro_header_bar_right
        {
            float: right;
        }

		#responsive_menu_pro_header_bar
		{
			display: block;
		}

		#responsive_menu_pro_header_bar 
		{
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 9998;
			background: {$header_bar_background_colour};
		}

        #responsive_menu_pro_header_bar,
        #responsive_menu_pro_header_bar_left,
        #responsive_menu_pro_disable_scrolling
        {
            height: " . $header_bar_height . "px;
        }

        #responsive_menu_pro_header_bar_left
        {
            line-height: " . $header_bar_height . "px;
        }
		
		#responsive_menu_pro_header_bar_logo
		{
			height:  " . $header_bar_height . "px;
			padding-left: {$right}%;
			padding-top: {$top}px;
			padding-bottom: {$top}px;
		}
		
		#responsive_menu_pro_header_bar_logo #responsive_menu_pro_header_bar_logo_image,
		#responsive_menu_pro_header_bar_logo img
		{
			height:  " . ( ( $header_bar_height ) - ( $top * 2 ) ) . "px;
		} ";


        if( !$options['include_header_search'] ) :

            $css .= " #responsive_menu_pro_header_search
            {
                display: none;
            } ";

        endif;
		
        if( !$use_as_only_menu ) : 

	       $css .= " } ";
	   
       endif;

endif;


/*
|--------------------------------------------------------------------------
| Build Normal Menu Styles If Required
|--------------------------------------------------------------------------
|
| Decided by the Only Show On Mobile Option
|
*/   

if( $use_as_only_menu )	:
	
	$css .= $options['css'] ? " @media only screen and ( max-width : {$breakpoint}px ) { " . $options['css'] . " { display: none !important; } }" : '';

	$css .= "
	
	@media only screen and ( min-width : {$breakpoint}px ) { 
	
	#responsive_menu_pro #responsive_menu_pro_additional_content,
	#responsive_menu_pro .responsive_menu_pro_append_link,
	#responsive_menu_pro_title,
	#responsive_menu_pro_search,
	#responsive_menu_pro_button
	{
		display: none;
	}
	
	#responsive_menu_pro_menu 					
	{ 	
	    width: auto;
	    float: right;
	    position: relative;
	    list-style-type: none;	 
	    font-size: 12px;	
		padding-right: {$right}%;			
	}
	
	#responsive_menu_pro_menu li 					
	{ 
	    display: inline-block; 
	    position: relative;
	} ";

    if( $use_header_bar ) : 

    $css .= "

        #responsive_menu_pro_container
        {
            position: absolute;
            right: 0;
            left: 0;
            z-index: -1;
        } ";
      
    endif;

    $css .= "
    
	#responsive_menu_pro_menu a 					
	{ 
	    display: block; 
	    padding: 0px 15px; 
	    text-decoration: none;
	    font-weight: 100;
	    font-size: 16px;
	    color: {$single_menu_link_colour};
	    height: {$single_menu_height}px;
	    line-height: {$single_menu_height}px;
	}
	
	#responsive_menu_pro_menu a:hover
	{
		color: {$single_menu_link_colour_hover};
	}
	
	#responsive_menu_pro_menu ul a
	{
	    font-weight: 600;
	    text-transform: uppercase;
	}
	
	#responsive_menu_pro_menu ul 				
	{ 
	    display: none; 
	    float: left; 
	    position: absolute; 
	    z-index: 99999; 
	    top: {$single_menu_height}px; 
	    left: 0px; 
	    box-shadow: 0px 1px 3px #CCCCCC;
	}
	
	#responsive_menu_pro_menu ul ul 				
	{ 
	    left: 100%; 
	    margin-left: 0px;
	    top: 0px;
	}
	
	#responsive_menu_pro_menu ul a 				
	{ 
	    line-height: {$single_menu_height}px; 
	    height: 40px;
		line-height: 40px; 
	    background: $mainBkg; 
	    width: 250px; 
	    font-size: 12px;
	    border: 0px;	
		color: $txtColH;
	}
	
	#responsive_menu_pro_menu ul a:hover
	{
	    background: $mainBkgH;
	    color: $txtColH;
	}
	
	#responsive_menu_pro_menu li:hover > ul[style] 		
	{ 
	    display: block !important; 
	}
	
	} ";
			
endif;

/*
|--------------------------------------------------------------------------
| Build Responsive Menu Styles
|--------------------------------------------------------------------------
|
| Determine whether to use the <style> tags
|
*/   

		/* Only use media queries on all if using as only menu */
        if( $use_as_only_menu ) :
        	$css .= " @media only screen and ( min-width : 0px ) and ( max-width : {$breakpoint}px ) {";
	    endif;

        $css .= "
            
			#responsive_menu_pro #responsive_menu_pro_additional_content,
			#responsive_menu_pro .responsive_menu_pro_append_link
			{
				display: block;
			}
		
            #responsive_menu_pro .responsive_menu_pro_append_link, 
            #responsive_menu_pro .responsive_menu_pro_menu li a, 
            #responsive_menu_pro #responsive_menu_pro_title a,
            #responsive_menu_pro .responsive_menu_pro_menu, 
            #responsive_menu_pro div, 
            #responsive_menu_pro .responsive_menu_pro_menu li, 
            #responsive_menu_pro 
            {
                box-sizing: content-box{$important}
                -moz-box-sizing: content-box{$important}
                -webkit-box-sizing: content-box{$important}
                -o-box-sizing: content-box{$important}
            }

            .responsive_menu_pro_push_open
            {
                width: 100%{$important}
                overflow-x: hidden{$important}
                height: 100%{$important}
            }

            .responsive_menu_pro_push_slide
            {
                position: $pushPos;
                $pushSide: $pushWidth%;
            }

            #responsive_menu_pro								
            { 
                position: $position;
                $overflowy
                $bottom
                width: $width%;
                $side: -$width%;
                $topRM;
                background: $mainBkg;
                z-index: 9999;  
                box-shadow: 0px 1px 8px #333333; 
                font-size: {$fontSize}px{$important}
                max-width: 999px;
                display: none;
                $minWidth
                $maxWidth
			}
			
			#responsive_menu_pro[style]
			{
                $set_auto_menu_height
            }
            
            #responsive_menu_pro.responsive_menu_pro_admin_bar_showing
            {
                padding-top: 32px;
            }
            
            #responsive_menu_pro_header_bar.responsive_menu_pro_admin_bar_showing,
            #responsive_menu_pro_button.responsive_menu_pro_admin_bar_showing
            {
                margin-top: 32px;
            }

            #responsive_menu_pro_header_bar #responsive_menu_pro_button.responsive_menu_pro_admin_bar_showing
            {
                margin-top: 0;
            }
                
            #responsive_menu_pro #responsive_menu_pro_additional_content
            {
                padding: 10px 5%{$important}
                width: 90%{$important}
                color: $txtCol;
            }
            
            #responsive_menu_pro .responsive_menu_pro_append_link
            {
                $subBtnAlign: 0px{$important}
                position: absolute{$important}
                border: 1px solid $borCol{$important}
                padding: 12px 10px{$important}
                color: $txtCol{$important}
                background: $mainBkg{$important}
                height: {$height}px{$important}
                line-height: {$height}px{$important}
                border-right: 0px{$important}
            }
            
            #responsive_menu_pro .responsive_menu_pro_append_link:hover
            {
                cursor: pointer;
                background: $mainBkgH{$important}
                color: $txtColH{$important}
            }

            #responsive_menu_pro .responsive_menu_pro_menu, 
            #responsive_menu_pro div, 
            #responsive_menu_pro .responsive_menu_pro_menu li,
            #responsive_menu_pro
            {
                text-align: $align{$important}
            }
                    
            #responsive_menu_pro .responsive_menu_title_image
            {
                vertical-align: middle;
                margin-right: 10px;
                display: inline-block;
            }

            #responsive_menu_pro.responsive_menu_pro_opened
            {
                $botRM
            }
            
            #responsive_menu_pro,
            #responsive_menu_pro input {
                $font
            }      
            
            #responsive_menu_pro #responsive_menu_pro_title			
            {
                width: 95%{$important} 
                font-size: {$titleSize}px{$important} 
                padding: $titlePadding{$important}
                margin-left: 0px{$important}
                background: $titleBkg{$important}
                white-space: nowrap{$important}
            }
      
            #responsive_menu_pro #responsive_menu_pro_title,
            #responsive_menu_pro #responsive_menu_pro_title a 
            {
                color: $titleCol{$important}
                text-decoration: none{$important}
                overflow: hidden{$important}
            }
            
            #responsive_menu_pro #responsive_menu_pro_title a:hover {
                color: $titleColH{$important}
                text-decoration: none{$important}
            }
   
            #responsive_menu_pro .responsive_menu_pro_append_link,
            #responsive_menu_pro .responsive_menu_pro_menu li a,
            #responsive_menu_pro #responsive_menu_pro_title a
            {

                transition: {$trans}s all;
                -webkit-transition: {$trans}s all;
                -moz-transition: {$trans}s all;
                -o-transition: {$trans}s all;

            }
            
            #responsive_menu_pro .responsive_menu_pro_menu			
            { 
                width: 100%{$important} 
                list-style-type: none{$important}
                margin: 0px{$important}
            }
                        
            #responsive_menu_pro .responsive_menu_pro_menu li.current-menu-item > a,
            #responsive_menu_pro .responsive_menu_pro_menu li.current-menu-item > .responsive_menu_pro_append_link,
            #responsive_menu_pro .responsive_menu_pro_menu li.current_page_item > a,
            #responsive_menu_pro .responsive_menu_pro_menu li.current_page_item > .responsive_menu_pro_append_link
            {
                background: $curBkg{$important}
                color: $curCol{$important}
            } 
                                            
            #responsive_menu_pro .responsive_menu_pro_menu li.current-menu-item > a:hover,
            #responsive_menu_pro .responsive_menu_pro_menu li.current-menu-item > .responsive_menu_pro_append_link:hover,
            #responsive_menu_pro .responsive_menu_pro_menu li.current_page_item > a:hover,
            #responsive_menu_pro .responsive_menu_pro_menu li.current_page_item > .responsive_menu_pro_append_link:hover
            {
                background: $curBkgHov{$important}
                color: $curColHov{$important}
            } 
                                            
            #responsive_menu_pro  .responsive_menu_pro_menu ul
            {
                margin-left: 0px{$important}
            }

            #responsive_menu_pro .responsive_menu_pro_menu li		
            { 
                list-style-type: none{$important}
                position: relative{$important}
            }

            #responsive_menu_pro .responsive_menu_pro_menu ul li:last-child	
            { 
                padding-bottom: 0px{$important} 
            }

            #responsive_menu_pro .responsive_menu_pro_menu li a	
            { 
                padding: $linkPadding{$important}
                width: 95%{$important}
                display: block{$important}
                height: {$height}px{$important}
                line-height: {$height}px{$important}
                overflow: hidden{$important}
                $word_wrap
                color: $txtCol{$important}
                border-top: 1px solid $borCol{$important} 
                text-decoration: none{$important}
				$word_wrap
            }

            #responsive_menu_pro_button						
            { 
                text-align: center;
                cursor: pointer; 
                font-size: {$btnSize}px{$important}
                position: $position;
                display: none;
                $location: $right%;
                top: {$top}px;
                color: $clickCol;
                $clickBkg
                padding: 5px;
                z-index: 9999;
            }

            #responsive_menu_pro #responsive_menu_pro_search
            {
                display: block{$important}
                width: 95%{$important}
                padding-$paddingAlign: 5%{$important}
                border-top: 1px solid $borCol{$important} 
                clear: both{$important}
                padding-top: 10px{$important}
                padding-bottom: 10px{$important}
                line-height: 40px{$important}
            }

            #responsive_menu_pro #responsive_menu_pro_search_submit
            {
                display: none{$important}
            }
            
            #responsive_menu_pro #responsive_menu_pro_search_input
            {
                width: 91%{$important}
                padding: 5px 0px 5px 3%{$important}
                -webkit-appearance: none{$important}
                border-radius: 2px{$important}
                border: 1px solid $borCol{$important}
            }
  
            #responsive_menu_pro .responsive_menu_pro_menu,
            #responsive_menu_pro div,
            #responsive_menu_pro .responsive_menu_pro_menu li
            {
                width: 100%{$important}
                margin-left: 0px{$important}
                padding-left: 0px{$important}
            }

            #responsive_menu_pro .responsive_menu_pro_menu li li a
            {
                padding-$paddingAlign: 10%{$important}
                width: 90%{$important}
                overflow: hidden{$important}
            }
 
            #responsive_menu_pro .responsive_menu_pro_menu li li li a
            {
                padding-$paddingAlign: 15%{$important}
                width: 85%{$important}
                overflow: hidden{$important}
            }
            
            #responsive_menu_pro .responsive_menu_pro_menu li li li li a
            {
                padding-$paddingAlign: 20%{$important}
                width: 80%{$important}
                overflow: hidden{$important}
            }
            
            #responsive_menu_pro .responsive_menu_pro_menu li li li li li a
            {
                padding-$paddingAlign: 25%{$important}
                width: 75%{$important}
                overflow: hidden{$important}
            }

            #responsive_menu_pro .responsive_menu_pro_menu li a:hover
            {       
                background: $mainBkgH{$important}
                color: $txtColH{$important}
                list-style-type: none{$important}
                text-decoration: none{$important}
            }
            
            #responsive_menu_pro_button #responsive_menu_x {

                display: none;
                font-size: 24px;
                line-height: {$clickMenuHeight}px{$important}
                height: {$clickMenuHeight}px{$important}
                color: $clickCol{$important}
            }
            
            #responsive_menu_pro_button .responsive_menu_pro_button_lines
            {
                width: {$lineWidth}px{$important}
                height: {$clickMenuHeight}px{$important}
                margin: auto{$important}
            }

            #responsive_menu_pro_button .responsive_menu_pro_button_lines .responsive_menu_pro_button_line
            {
                height: {$lineHeight}px{$important}
                margin-bottom: {$lineMargin}px{$important}
                background: $clickCol{$important}
                width: 100%{$important}
            }
            
            #responsive_menu_pro_button .responsive_menu_pro_button_lines .responsive_menu_pro_button_line.last
            {
                margin-bottom: 0px{$important}
            }";
            
		/* Only use media queries on all if using as only menu */
        if( $use_as_only_menu ) :
        	$css .= "}";
    	endif;

        /*
        |--------------------------------------------------------------------------
        | Media Breakpoint Checks
        |--------------------------------------------------------------------------
        |
        | Only use breakpoint widths if the "only use on mobile" option is not checked
        |
        */               
        
    	$css .= " @media only screen and ( min-width : 0px ) and ( max-width : {$breakpoint}px ) { 

        #responsive_menu_pro_button	
        {
            display: block;
        }";


        $css .= $options['css'] ? $options['css'] . " { display: none !important; } " : '';

        $css .= " }";
	        

				
        $css .= $options['animation_type'] == 'push' && $options['push_animation_container_css'] ? $options['push_animation_container_css'] . " { position: {$pushPos}{$important} left: 0px; } " : '';
 
        /* 
        |--------------------------------------------------------------------------
        | Add Custom CSS
        |--------------------------------------------------------------------------
        |
        | Add final user specified custom CSS
        |
        */
        
        $css .= $options['custom_css'];

        /*
        |--------------------------------------------------------------------------
        | Strip Tags If Needed
        |--------------------------------------------------------------------------
        |
        | Determine whether to use the <style> tags
        |
        */       

        $css .= $options['create_external_scripts'] ? '' : '</style>';

        /*
        |--------------------------------------------------------------------------
        | Return Finished Styles
        |--------------------------------------------------------------------------
        |
        | Finally we return the final script back
        |
        */   

        return $css;
        
        
    }

    
}