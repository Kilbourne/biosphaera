<?php
/**
 * Template Name: Aree Terapeutiche category List Archive
 */
?>

<?php
use Roots\Sage\Extras;
$terms = get_terms( array(
    'taxonomy' => 'aree_terapeutice_tax',
));
if($terms){
?>
<div class="aree-bios-wrapper">


<?php


  foreach ($terms as $key => $term) {
    $term_id= $term->term_id;
    $posts = get_posts(array(
      'post_type' => 'aree_terapeutiche',
      'numberposts' => -1,
      'tax_query' => array(
        array(
          'taxonomy' => 'aree_terapeutice_tax',
          'field' => 'id',
          'terms' => $term_id,
          'include_children' => false
        )
      )
    ));
?>
      <div class="area-bios-wrap" style="background-color:<?php   echo get_field('color', 'aree_terapeutice_tax_' . $term_id) ?>;color:<?php  echo Extras\lumdiff(Extras\hex2rgba(get_field('color', 'aree_terapeutice_tax_' . $term_id),false,true));  ?> ; ">
    <div class="area-bios">

             <?php

             echo '<div class="area-bios-img-wrapper">'.wp_get_attachment_image( get_field('immagine','term_'.$term_id), 'full' ).'</div><div class="area-bios-content-wrapper"> <h3>'. $term->name.'</h3>';

    if($posts){
          echo '<ul class="aree_tera_list">';
      foreach ($posts as $key => $larea) { ?>
    <li class="aree_tera_list_el"><a class="aree_tera_list_link" href="<?php echo get_the_permalink($larea->ID); ?>"><?php echo get_the_title($larea->ID) ?></a></li>
      <?php
    }
       echo'</ul>';
    }

    ?>
    </div>
        </div>
       </div>
       <?php
  }
  ?>
  </div>
  <?php
}
 ?>

