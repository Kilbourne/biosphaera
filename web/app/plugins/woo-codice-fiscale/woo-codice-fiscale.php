<?php
/*
Plugin Name: WOO Codice Fiscale
Plugin URI: http://softrade.it/woocommerce-codice-fiscale-plugin
Description: Adds two fields: Codice Fiscale (mandatory) and P.IVA to  Woocommerce orders and checkout.
Version: 1.6.3
Author:       Andrea Somovigo
Author URI:   http://it.linkedin.com/in/andreasomovigo
Text Domain: woocommerce-codice-fiscale
Domain Path: /languages
WC requires at least: 2.2
WC tested up to: 2.4
 **************************************************************************
Copyright (C) 2008-2016 SOFTRADE
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

 **************************************************************************/
define('WOOCF','woocommerce-codice-fiscale');
function woocf_l10n(){

    load_plugin_textdomain( WOOCF, false, dirname(plugin_basename( __FILE__ )).'/languages/' );

}
add_action('init','woocf_l10n');
////////////////////////////////////////////////PLUGIN DEPENDENDENT FROM WOOCOMMERCE

is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', $filter) ) ) {
        /**

         * @@@@@@@@@@@@@@@@@ ADD FIELDS @@@@@@@@@@@

         * aggiunto CF ( obbligatorio) e P. iva

         * */

    add_action( 'template_redirect', 'woocf_on_checkout' );

    function woocf_on_checkout(){

        if(is_checkout()){
            wp_enqueue_script('woocfbase', plugin_dir_url( __FILE__ ) .'js/woocf.js',array('jquery'),'',false);
            $protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
            $params = array(
                    // Get the url to the admin-ajax.php file using admin_url()
                    'ajaxurl' => admin_url( 'admin-ajax.php', $protocol ),
                    );
            wp_localize_script( 'woocfbase', 'woocf_params', $params );
        }
    }
    add_action( 'wp_ajax_set_woocf', 'set_woocf',10 );
    add_action( 'wp_ajax_nopriv_set_woocf', 'set_woocf',10 );
    function set_woocf() {
      $lang = $_POST['country'];
       echo $lang;
       exit();
    }

    //ADD fields CF e IVA at checkout

    function SOFT_add_field_to_checkout( $fields ){
        $session= WC()->session;
        if(is_user_logged_in()){
            $user=wp_get_current_user();
            $user_id = get_current_user_id();

            isset($session->customer['shipping_country']) ? $currentCountry=$session->customer['shipping_country'] : $currentCountry=$user->billing_country[0];
        }
        else{
            isset($session->customer['shipping_country']) ? $currentCountry=$session->customer['shipping_country'] : $currentCountry="";
        }
        isset($currentCountry) ? $currentCountry=$currentCountry : $currentCountry="";
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_shipping_countries('countries');
        if(array_key_exists('IT',$countries) && ( $currentCountry=='IT' ||  $currentCountry=='' ) ){
            $fields['billing']['billing_iva'] = array(
                'label'     => __('PARTITA IVA', WOOCF),
                'placeholder'   => _x('xxxxxxxxxxx', 'placeholder', WOOCF),
                'required'   => false,
                'class'     => array('form-row-first'),
                'clear'     => false,
                'default'=>isset($user_id)?get_user_meta( $user_id, 'IVA', true ):''
                    );
            $fields['billing']['billing_CF'] = array(
                'label'     => __('CODICE FISCALE', WOOCF),
                'placeholder'   => _x('', 'placeholder', WOOCF),
                'required'   => true,
                'class'     => array('form-row-first'),
                'clear'     => false,
                'default'=> isset($user_id)?get_user_meta( $user_id, 'CF', true ) :''
                    );
        }

        return $fields;
    }

    add_filter( 'woocommerce_checkout_fields' , 'SOFT_add_field_to_checkout', 9 , 1 );
    add_filter( 'woocommerce_billing_fields' , 'SOFT_CF_in_billing_form',9,1);
    add_action( 'woocommerce_edit_account_form', 'SOFT_edit_account' );

    add_action( 'woocommerce_save_account_details', 'SOFT_save_details' );

    function SOFT_edit_account() {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        if ( !$user )
            return;

        $CF = get_user_meta( $user_id, 'CF', true );
        $IVA = get_user_meta( $user_id, 'IVA', true );
?>

  <fieldset>
    <p>
        <label for="CF"><?php _e( 'Codice Fiscale', WOOCF ) ?><br />
        <input type="text" name="CF" id="CF" class="input" value="<?php echo esc_attr( wp_unslash( $CF ) ); ?>" maxlength="16" required/></label>
    </p>
    <p>
        <label for="IVA"><?php _e( 'P.IVA', WOOCF ) ?><br />
        <input type="text" name="IVA" id="IVA" class="input" value="<?php echo esc_attr( wp_unslash( $IVA ) ); ?>" maxlength="25" /></label>
    </p>
  </fieldset>

  <?php
    }
    function SOFT_CF_in_billing_form($fields) {
        $user=wp_get_current_user();
        $user_id = get_current_user_id();

        isset($session->customer['shipping_country']) ? $currentCountry=$session->customer['shipping_country'] : $currentCountry=$user->billing_country;

        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_shipping_countries('countries');

        if(array_key_exists('IT',$countries) && ( $currentCountry=='IT' ||  $currentCountry=='' ) ){
            $fields['billing_iva'] = array(
                'label'     => __('PARTITA IVA', WOOCF),
                'placeholder'   => _x('xxxxxxxxxxx', 'placeholder', WOOCF),
                'required'   => false,
                'class'     => array('form-row-first'),
                'clear'     => false,
                'default'=>get_user_meta( $user_id, 'IVA', true )
                    );
            $fields['billing_CF'] = array(
                'label'     => __('CODICE FISCALE', WOOCF),
                'placeholder'   => _x('', 'placeholder', WOOCF),
                'required'   => true,
                'class'     => array('form-row-first'),
                'clear'     => false,
                'default'=> get_user_meta( $user_id, 'CF', true )
                    );
        }

        return $fields;
    }


    function SOFT_save_details( $user_id ) {

        update_user_meta( $user_id, 'CF', htmlentities( $_POST[ 'CF' ] ) );
        update_user_meta( $user_id, 'IVA', htmlentities( $_POST[ 'IVA' ] ) );
        $user = wp_update_user( array( 'ID' => $user_id ) );

    }

    //MOSTRO i Valori dei 2 campi nell'area admin

    function SOFT_add_field_to_admin($order){
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->get_shipping_countries('countries');
        $session= WC()->session;

        if(array_key_exists('IT',$countries) ){
?>
            <script>
                jQuery(function ($) {
                    $('.address').first()
                        .append('<p><strong><?php _e("P.iva",WOOCF)?>:</strong><?php echo get_post_meta( $order->id, '_billing_iva', true )?> </p>')
                        .append('<p><strong><?php _e("Cod. Fisc",WOOCF)?>:</strong><?php echo strtoupper(get_post_meta( $order->id, '_billing_CF', true ))?> </p>');
                })
            </script>
        <?php
        }
    }
    add_action( 'woocommerce_admin_order_data_after_billing_address', 'SOFT_add_field_to_admin', 10 , 1 );
    //update dei valori custom
    function SOFT_update_CF( $order_id ) {
        if ( ! empty( $_POST['_billing_iva'] ) ) {
            update_post_meta( $order_id, '_billing_iva', sanitize_text_field( $_POST['_billing_iva'] ) );
        }
        if ( ! empty( $_POST['_billing_CF'] ) ) {
            update_post_meta( $order_id, '_billing_CF', sanitize_text_field( $_POST['_billing_CF'] ) );
        }
    }
    add_action( 'woocommerce_checkout_update_order_meta', 'SOFT_update_CF' , 10 , 1);

    //mostro i campi anche nell'order review
    /**
     * @@@@@@@@@@@ show fields in order review @@@@@@@@@@@@@@@@@@@@@
     * */

   function SOFT_display_CF( $order_id ){  ?>
    <table class="shop_table shop_table_responsive additional_info">
        <tbody>
            <tr>
                <th><?php _e( "Codice Fiscale:" ,WOOCF); ?></th>
                <td><?php echo get_post_meta( $order_id, '_billing_CF', true ); ?></td>
            </tr>
            <tr>
                <th><?php _e("Partita Iva",WOOCF);?></th>
                <td><?php echo get_post_meta( $order_id, '_billing_iva', true ); ?></td>
            </tr>
        </tbody>
    </table>
<?php }

        add_action( 'woocommerce_thankyou', 'SOFT_display_CF', 20 , 1);
        add_action( 'woocommerce_view_order', 'SOFT_display_CF', 20 , 1 );
          /**
     * @@@@@@@@@@@ Add fields in order email @@@@@@@@@@@@@@@@@@@@@

     * */
add_filter('woocommerce_email_order_meta_keys', 'SOFT_add_cf_to_email', 10 , 1);

    function SOFT_add_cf_to_email( $keys ) {
        $keys[__('P.Iva',WOOCF)] = '_billing_iva';
        $keys[__('Cod. Fisc',WOOCF)] = '_billing_CF';
        return $keys;
    }
}

else{//deactivate this plugin
    add_action( 'admin_init', 'deactivate_me' );
    function deactivate_me(){
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
?>
