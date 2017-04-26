<?php

use Roots\Sage\Setup;
use Roots\Sage\Extras;
use Roots\Sage\Wrapper;

?>

<!doctype html>
<html <?php language_attributes(); ?>>
  <?php get_template_part('templates/head'); ?>
  <body <?php body_class(); ?>>
  <div class="page-wrapper">
    <!--[if IE]>
      <div class="alert alert-warning">
        <?php _e('You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.', 'sage'); ?>
      </div>
    <![endif]-->
    <?php do_action('body_open');?>
    <?php
      do_action('get_header');
      get_template_part('templates/header');
    ?>
    <div class="wrap container" role="document">
      <div class="content row">
        <main class="main">
          <?php   if(Setup\display_header_slider()) {
            $page_id=get_page_by_title('Header Slider')->ID;
            $lang_id=apply_filters( 'wpml_object_id', $page_id, 'page', false, ICL_LANGUAGE_CODE );
            echo '   <div class="divi-slider header-slide">
                        <div class="et_builder_outer_content" id="et_builder_outer_content">
                  <div class="et_builder_inner_content et_pb_gutters3">
                  '.do_shortcode(get_page($lang_id)->post_content).'</div></div></div>  ';} ?>

          <?php include Wrapper\template_path(); ?>
        </main><!-- /.main -->
        <?php if (false) : ?>
          <aside class="sidebar">
            <?php include Wrapper\sidebar_path(); ?>
          </aside><!-- /.sidebar -->
        <?php endif; ?>
      </div><!-- /.content -->
    </div><!-- /.wrap -->
    <?php
      echo do_shortcode('[responsive_menu_pro] ');
      do_action('get_footer');
      get_template_part('templates/footer');
      do_action('body_close');
      wp_footer();
      if(is_product()){
    ?>
<div id="fb-root"></div>
<script type="text/plain" class="ce-script" >(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/<?php echo get_locale(); ?>/sdk.js#xfbml=1&version=v2.8";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php } ?>
    </div>
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-97185973-1', 'auto');
  ga('send', 'pageview');

</script>
  </body>
</html>
