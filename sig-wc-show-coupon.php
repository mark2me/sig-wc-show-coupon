<?php
/**
 * Plugin Name: Woocommerce show coupon
 * Description: 顯示可用的折價券
 * Version:     1.0
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me/
 * License:     GPLv2
 * Text Domain: sig-wc-show-coupon
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIG_SWSC_VERSION', '1.0' );

if ( ! class_exists( 'Sig_Wc_Show_Coupon' ) ) :

    final class Sig_Wc_Show_Coupon {

        public function __construct() {

            add_action( 'wc_ajax_update_my_coupon', array( __CLASS__, 'ajax_update_my_coupon' ) );

            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'use_plugin_style' ) );

            add_action( 'wp_footer', array( __CLASS__, 'footer_html' ) );

            add_action( 'woocommerce_cart_collaterals' , array( __CLASS__, 'load_coupon' ) );
        }


        public static function use_plugin_style() {
            wp_enqueue_style( 'sig-wc-show-coupon', plugin_dir_url( __FILE__ ) . '/css/style.css', [], SIG_SWSC_VERSION );
        }

        public static function footer_html() {
            global $woocommerce;
        ?>
            <script>
            jQuery( function( $ ) {

                $( 'body' ).on( 'updated_cart_totals', function(e){
                    //code
                    console.log('=='+e.type);
                    $.get( "<?php echo WC_Ajax::get_endpoint( 'update_my_coupon' ) ?>", function( data ) {
                        $( "#coupons_list" ).html( data );
                    });
                });

                $('#coupons_list').on('click','button[name=user_coupon]',function(){
                    var code = $(this).val();
                    if(code){
                        $('#coupon_code').val(code);
                        $('[name=apply_coupon]').trigger('click');
                    }
                });
            });
            </script>
        <?php
        }

        public static function load_coupon(){

            if ( wc_coupons_enabled() ) {
                echo '<div id="coupons_list">';
                self::ajax_update_my_coupon();
                echo '</div>';
            }

        }


        public static function ajax_update_my_coupon(){

            echo '<h2>'.__('Coupon','woocommerce').'</h2>';
            echo '<div style="">';

            global $wpdb;
            global $woocommerce;

            $coupon_codes = $wpdb->get_results("SELECT post_title,post_name FROM $wpdb->posts WHERE post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_name ASC");

            // Display available coupon codes
            foreach($coupon_codes as $code)
            {
                $c = new WC_Coupon($code->post_title);
                $amount = [];
                $class_name = [];
                $btn_name = '';

                $minimum_amount = (empty($c->get_minimum_amount())) ? 0: $c->get_minimum_amount();
                if( $minimum_amount > 0 ) $amount[] = '滿 '. $minimum_amount;

                if( $c->get_discount_type() == 'fixed_cart' ){
                    $amount[] = '折 ' . $c->get_amount() ;
                }else if( $c->get_discount_type() == 'fixed_product' ){
                    $amount[] = '折 ' . $c->get_amount() ;
                }else if( $c->get_discount_type() == 'percent' ){
                    $amount[] = '打 ' . (100 - $c->get_amount()) .' 折' ;
                }

                $class_name[] = 'item_coupon';
                if( in_array( strtolower($code->post_title), $woocommerce->cart->applied_coupons) ) {  //使用中
                    $class_name[] =  'used';
                    $btn_name = '<a href="https://woo.abc.com/cart/?remove_coupon=' . $c->get_code() . '" class="btn_coupon woocommerce-remove-coupon" data-coupon="'. strtolower($code->post_title) .'">移除折價券</a>';
                }else if( $c->is_valid() ) {
                    $class_name[] = 'active';  //可使用
                    $btn_name = '<button class="btn_coupon" type="button" name="user_coupon" value="' . $c->get_code() . '">使用折價券</button>';
                }else{
                    $class_name[] = 'disabled';
                    $btn_name = '<button class="btn_coupon" type="button" disabled>未符合資格</button>';
                }

        ?>
                <div class="<?php echo implode(" ",$class_name)?>">
                    <div class="code"><?php
                        echo $code->post_title;
                        if( count($amount) > 0 ) echo '<i>'.implode("，",$amount).'</i>';
                    ?></div>
                    <div class="note">
                        <?php if( !empty($c->get_description()) ) echo '<div>' . $c->get_description() . '</div>'; ?>
                        <small>使用期限：<?php echo (empty($c->get_date_expires()) ? '無': $c->get_date_expires()->date( 'Y-m-d' ))  ?></small>
                        <?php if( !empty($btn_name)) echo $btn_name ?>
                    </div>
                </div>
        <?php

            }

            echo '</div>';

        }

    }

endif;

new Sig_Wc_Show_Coupon();