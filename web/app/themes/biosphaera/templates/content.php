<article <?php post_class(); ?>>
 <?php  if(get_the_post_thumbnail( get_the_id(), 'medium' ) !==''){ ?> <div class="post-image"><?php the_post_thumbnail( get_the_id(), 'medium' ) ?></div>
 <?php  } ?>
  <header>
    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php get_template_part('templates/entry-meta'); ?>
  </header>
  <div class="entry-summary">
    <?php the_excerpt(); ?>
  </div>
</article>
