<?php use Roots\Sage\Extras; ?>
<footer class="content-info">
  <div class="container">
    <?php //dynamic_sidebar('sidebar-footer'); ?>
    <hr>
    <div class="contatti footer-section">
    <h2 class="footer-title"><?php _e('Contacts','sage'); ?></h2>
      <div class="footer-content"><span>Viale Luigi Majno, 18</span>
      <span>20129 Milano</span>
      <span>Tel: +39 02 76003400</span>
      <a href="<?php echo get_the_permalink(get_page_by_title('Contatti')) ?> " class="parlaci"><?php _e('Talk to us','sage'); ?></a>
        <span style="max-width: 117px;display: inline-block;margin-top: 2rem;"><img src="<?php echo get_stylesheet_directory_uri() ?>/dist/images/feder-salus.png" alt="Logo federsalus"></span>
      </div>
     </div>
     <div class="social footer-section">
      <h2 class="footer-title">Social</h2>
      <div class="footer-content"><a href="" class="fb"><img src="<?=esc_url(get_stylesheet_directory_uri());?>/dist/images/facebook.png" alt="Facebook"></a><a href="" class="yt"><img src="<?=esc_url(get_stylesheet_directory_uri());?>/dist/images/youtube.png" alt="YouTube"></a>
    </div>
     </div>
     <div class="newsletter footer-section">
      <h2 class="footer-title">Newsletter</h2>
      <div class="footer-content">
        <p><?php  _e('Discover new products and offerings
Special before the others! We remain
in contact!','sage'); ?> </p>
    <?php echo do_shortcode('[gravityform id="1" title="false" description="false" ajax="true"]'); ?>
     </div>
  </div>
  </div>

  <div class="last-line-container">
    <span>© 2017 Biosphaera</span> |
 <?php
  array_map('Roots\Sage\Extras\bios_footer_link', ['Termini e condizioni', 'Privacy', 'Informativa sui cookie']);
  echo '<a href="http://ec.europa.eu/consumers/odr/" class="last-line-link"> ODR </a> | ';
  ?>

     <span class="last-line-link" ><span style="display: inline-block;
vertical-align: middle;">Web agency</span><span style="display: inline-block;
vertical-align: middle;margin-left: 6px;" ><a  href="http://www.menthalia.com"><img src="<?php echo get_stylesheet_directory_uri().'/dist/images/menthalia-logo.png' ?>" alt="MENTHALIA LOGO" style="display: inline;vertical-align: middle;max-width: 118px;"></a></span></span>
  </div>
</footer>
