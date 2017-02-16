<?php
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
      <div class="area-bios-wrap" style="background-color:<?php   echo get_field('color', 'aree_terapeutice_tax_' . $areat_id) ?> ">
         <div class="area-bios"><a >
             <h3><?php  echo $area->name; ?></h3>
           </a></div>
       </div>

      <?php
  }
?>
</div>
<?php
}



