<?php

class ResponsiveMenuPro_Theme {
   
   
	protected $data;
	protected $menu_colour;
	protected $menu_colour_hover;
	protected $text_colour;
	protected $text_colour_hover;
	
	public function __construct( $data ) {
			
		if( is_array( $data ) )
			$this->data = $data;
		else
			throw new Exception( 'Data must be an array' );
			
	}
	
    public function apply( $colour ) {
    
		switch( $colour ) :
			case 'blue' : $this->blue(); break;
			case 'red' : $this->red(); break;
			case 'green' : $this->green(); break;
			case 'yellow' : $this->yellow(); break;
			default : $this->reset(); break;
		endswitch;
        
		$this->applyColourChanges();
		
		return $this->data;
		
    }
	
	protected function applyColourChanges() {
	
		/* Button Colours */
		$this->data['button_line_colour'] = $this->text_colour;
		$this->data['button_background_colour'] = $this->menu_colour;
		$this->data['is_button_background_transparent'] = 'is_button_background_transparent';
		
		/* Menu Link Background Colour */
		$this->data['background_colour'] = $this->menu_colour;
		$this->data['link_border_colour'] = $this->menu_colour;
		$this->data['title_background_colour'] = $this->menu_colour;
		
		/* Menu Link Background Hover Colour */
		$this->data['current_page_link_background'] = $this->menu_colour_hover;
		$this->data['background_colour_hover'] = $this->menu_colour_hover;
		$this->data['current_background_hover'] = $this->menu_colour_hover;
		
		/* Menu Link Colour */
		$this->data['title_colour'] = $this->text_colour;
		$this->data['text_colour'] = $this->text_colour;
		$this->data['current_page_link_colour'] = $this->text_colour;
		
		/* Menu Link Hover Colour */
		$this->data['text_colour_hover'] = $this->text_colour_hover;
		$this->data['current_colour_hover'] = $this->text_colour_hover;
		$this->data['title_colour_hover'] = $this->text_colour_hover;	
		
		/* Header Bar Background Colour */
		$this->data['header_bar_background_colour'] = $this->menu_colour;
		
	}
	
	protected function blue() {
		
		$this->menu_colour = '#009EDD';
		$this->menu_colour_hover = '#0182B5';
		
		$this->text_colour = '#FFFFFF';
		$this->text_colour_hover = '#FFFFFF';
		
	}

	
	protected function red() {
		
		$this->menu_colour = '#DE4B42';
		$this->menu_colour_hover  = '#CE3931';
		
		$this->text_colour = '#FFFFFF';
		$this->text_colour_hover = '#FFFFFF';
				
	}
	
		
	protected function green() {

		$this->menu_colour = '#4DD827';
		$this->menu_colour_hover  = '#37B714';
		
		$this->text_colour = '#FFFFFF';
		$this->text_colour_hover = '#FFFFFF';
				
	}
	
		
	protected function yellow() {
		
		$this->menu_colour = '#FFE316';
		$this->menu_colour_hover  = '#EDD012';
		
		$this->text_colour = '#000000';
		$this->text_colour_hover = '#000000';
		
	}
	
		
	protected function reset() {
		
		$this->menu_colour = '#43494C';
		$this->menu_colour_hover  = '#3C3C3C';
		
		$this->text_colour = '#FFFFFF';
		$this->text_colour_hover = '#FFFFFF';		
		
	}
    
}