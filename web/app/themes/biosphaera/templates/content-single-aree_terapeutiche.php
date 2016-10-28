<?php
use Roots\Sage\Extras;
?>
<div class="area-content">
	<div class="area-img-wrapper"><?php the_post_thumbnail( 'full' ); ?></div>
	<?php   Extras\theme_breadcrumb(); ?>
	<div class="area-content-wrapper">
	<div class="first-col">
		<?php Extras\area_terap_attribute_template('cosa'); ?>		
		<?php Extras\area_terap_attribute_template('cause'); ?>		
	</div>
	<div class="second-col">
		<?php Extras\area_terap_attribute_template('manifestazioni'); ?>		
		<?php Extras\area_terap_attribute_template('rimedi_naturali'); ?>		
	</div>
	</div> 
	<?php 
	$query=get_posts(['post_type' => 'product',
	'aree_terapeutiche' => get_the_ID(),'suppress_filters' => false]);	
	foreach ($query as $key => $product_1) {
		global $product,$post;
		$post=$product_1;		
		setup_postdata( $post );
		?> <div id="content"><?php
		get_template_part( 'woocommerce/content-areat-product' ); 
		?>
		</div> <?php
	}
	 ?>
</div>