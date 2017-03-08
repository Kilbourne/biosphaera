<?php get_template_part('templates/page', 'header'); ?>


<div class="alert alert-warning">
  <?php _e('Sorry, but the page you were trying to view does not exist.', 'sage'); ?>
</div>

<form role="search" method="get" class="search-form" action="<?php echo get_the_permalink(apply_filters( 'wpml_object_id', get_page_by_title('Search Results')->ID, 'page', false, ICL_LANGUAGE_CODE)); ?>">
      <?php echo '<label>
                    <span class="screen-reader-text">' . _x('Search for:', 'label') . '</span>
                    <input type="search" class="search-field" placeholder="' . esc_attr_x('Search &hellip;', 'placeholder') . '" value="' . get_search_query() . '" name="swpquery" />
                </label>
                <input type="submit" class="search-submit" value="' . esc_attr_x('Search', 'submit button') . '" />
            </form>';

?>
