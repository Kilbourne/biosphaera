<footer class="content-info">
  <div class="container">
    <?php //dynamic_sidebar('sidebar-footer'); ?>
    <hr>
    <div class="contatti footer-section">
    <h2 class="footer-title">Contatti</h2>
      <div class="footer-content"><span>Viale Luigi Majno, 18</span>
      <span>20129 Milano</span>
      <span>Tel: +39 02 76003400</span>
      <a href="" class="parlaci">Parla con noi</a>
      <a href="" class="faqs">FAQs</a> </div>
     </div>
     <div class="social footer-section">
      <h2 class="footer-title">Social</h2>
      <div class="footer-content"><a href="" class="fb"><img src="<?=esc_url(get_stylesheet_directory_uri());?>/dist/images/facebook.png" alt="Facebook"></a><a href="" class="yt"><img src="<?=esc_url(get_stylesheet_directory_uri());?>/dist/images/youtube.png" alt="YouTube"></a>
    </div>
     </div>
     <div class="newsletter footer-section">
      <h2 class="footer-title">Newsletter</h2>
      <div class="footer-content">
        <p>Scopri nuovi prodotti e le offerte
speciali prima degli altri! Restiamo
in contatto!</p>
    <?php echo do_shortcode('[gravityform id="1" title="false" description="false" ajax="true"]'); ?>
     </div>
  </div>
  </div>
  <div class="last-line-container">
    <span>© 2016 Biosphaera</span> |
     <a href="" class="last-line-link">Termini e condizioni</a> |
     <a href="" class="last-line-link">Privacy</a> |
     <a href="" class="last-line-link">Informazioni sui cookie</a> |
     <a href="" class="last-line-link">Mappa del sito</a>
     <a href="" class="last-line-link">Credits</a>
  </div>
</footer>
