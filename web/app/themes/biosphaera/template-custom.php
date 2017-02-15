<?php
/**
 * Template Name: Custom Search
 */
?>

  <?php
global $post;
$search = isset($_GET['searchwpquery']) ? sanitize_text_field($_GET['searchwpquery']) : '';
$page  = isset($_GET['swppage']) ? absint($_GET['swppage']) : 1;
$query = new SWP_Query(
  array(
    's' => $search, // search query
    'engine' => 'supplemental'
  )
);
?>


        <header class="page-header">
          <h1 class="page-title">Search Results for: <?php echo $search; ?></h1>
        </header>

<?php if (empty($query->posts)): ?>
  <div class="alert alert-warning">
    <?php _e('Sorry, no results were found.', 'sage');?>
  </div>
  <form role="search" method="get" class="search-form" action="<?php echo get_permalink(922); ?>">
      <?php echo '<label>
                    <span class="screen-reader-text">' . _x('Search for:', 'label') . '</span>
                    <input type="search" class="search-field" placeholder="' . esc_attr_x('Search &hellip;', 'placeholder') . '" value="' . get_search_query() . '" name="searchwpquery" />
                </label>
                <input type="submit" class="search-submit" value="' . esc_attr_x('Search', 'submit button') . '" />
            </form>';

endif; ?>

<?php if ( ! empty( $query->posts ) ) {
    foreach( $query->posts as $post ) : setup_postdata( $post ); ?>




                    <article>
                      <div class="search-thumbnail">
          <?php the_post_thumbnail('medium');?>
        </div>
        <?php if(get_the_title()!==''){?>
        <header>
          <h2 class="entry-title"><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
          <?php if (get_post_type() === 'post') {get_template_part('templates/entry-meta');}?>
        </header>
        <?php } ?>
        <div class="entry-summary">
          <?php the_excerpt();?>
        </div>
                    </article>

          <?php endforeach;?>



<?php
wp_reset_postdata();

?>
<?php };?>

<?php the_posts_navigation();?>







