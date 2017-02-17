<?php
/**
 * Template Name: noautop
 */
?>

<?php remove_filter('the_content', 'wpautop');while (have_posts()): the_post();?>
        <?php get_template_part('templates/page', 'header');?>
        <?php get_template_part('templates/content', 'page');?>
      <?php endwhile;?>
