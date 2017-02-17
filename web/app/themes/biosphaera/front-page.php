<?php
use Roots\Sage\Extras;
$aree= get_terms( array(
    'taxonomy' => 'aree_terapeutice_tax',
    'hide_empty'=> false,
) );
if($aree){
?>
<div class="aree-bios-wrapper">
<?php
  foreach ($aree as $key => $area) {

    $areat_id=$area->term_id;

      ?>
      <div class="area-bios-wrap" style="background-color:<?php   echo get_field('color', 'aree_terapeutice_tax_' . $areat_id) ?>;color:<?php  echo Extras\lumdiff(Extras\hex2rgba(get_field('color', 'aree_terapeutice_tax_' . $areat_id),false,true));  ?> ; ">
         <div class="area-bios">
             <h3><?php  echo $area->name; ?></h3>
             <?php
             $areat=get_posts(array(
    'post_type' => 'aree_terapeutiche',
    'tax_query' => array(
        array(
            'taxonomy' => 'aree_terapeutice_tax',
            'field'    => 'id',
            'terms'    => $areat_id,
        ),
    ),
));
             if($areat){
              echo '<ul class="aree_tera_list">';

              foreach ($areat as $key => $larea) {
                ?>
<li class="aree_tera_list_el"><a class="aree_tera_list_link" href="<?php echo get_the_permalink($larea->ID); ?>"><?php echo get_the_title($larea->ID) ?></a></li>
                <?php
              }
              echo'</ul>';
              } ?>
          </div>
       </div>

      <?php
  }
?>
</div>
<?php
}



