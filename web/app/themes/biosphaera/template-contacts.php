<?php
/**
 * Template Name: Contacts
 */
?>

<div class="img-container">

<?php global $post; echo get_the_post_thumbnail( $post->ID, 'full' ); ?>
</div>
<?php while (have_posts()) : the_post(); ?>
  <?php get_template_part('templates/page', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>

