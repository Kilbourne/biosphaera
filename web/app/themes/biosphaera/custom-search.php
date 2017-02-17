<?php
/**
 * Template Name: Custom Search
 */
?>

  <?php
global $post;
$search = isset($_GET['swpquery']) ? sanitize_text_field($_GET['swpquery']) : '';
$swppg = isset( $_REQUEST['swppg'] ) ? absint( $_REQUEST['swppg'] ) : 1;
$query = new SWP_Query(
  array(
    's' => $search, // search query
    'engine' => 'supplemental',
    'page'   => $swppg,
  )
);
$pagination = paginate_links( array(
    'format'  => '?swppg=%#%',
    'current' => $swppg,
    'total'   => $query->max_num_pages,
  ) );
?>


        <header class="page-header">
          <h1 class="page-title"><?php _e('Search Results for','sage') ?>: <?php echo $search; ?></h1>
        </header>

<?php if (empty($query->posts)): ?>
  <div class="alert alert-warning">
    <?php _e('Sorry, no results were found.', 'sage');?>
  </div>
  <form role="search" method="get" class="search-form" action="<?php echo apply_filters( 'wpml_object_id', get_page_by_title('Search Results')->ID, 'page', false, get_locale() ); ?>">
      <?php echo '<label>
                    <span class="screen-reader-text">' . _x('Search for:', 'label') . '</span>
                    <input type="search" class="search-field" placeholder="' . esc_attr_x('Search &hellip;', 'placeholder') . '" value="' . get_search_query() . '" name="swpquery" />
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
          <h2 class="entry-title" hidden><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
          <?php if (get_post_type() === 'post') {get_template_part('templates/entry-meta');}?>
        </header>
        <?php } ?>
        <div class="entry-summary">
          <a href="<?php the_permalink();?>">
<?php the_excerpt();?>
</a>
        </div>
                    </article>

          <?php endforeach;?>



<?php
wp_reset_postdata();
if ( $query->max_num_pages > 1 ) { ?>
          <div class="navigation pagination" role="navigation">
            <h2 class="screen-reader-text">Posts navigation</h2>
            <div class="nav-links">
              <?php echo wp_kses_post( $pagination ); ?>
            </div>
          </div>
<?php }
?>
<?php };?>

<?php the_posts_navigation();?>






