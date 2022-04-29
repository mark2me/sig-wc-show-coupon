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

if ( ! class_exists( 'Sig_Wc_Show_Coupon' ) ) :

    final class Sig_Wc_Show_Coupon {

        public function __construct() {

            add_action( 'wc_ajax_update_my_coupon', array( __CLASS__, 'ajax_update_my_coupon' ) );
            add_action( 'wc_ajax_use_my_coupon', array( __CLASS__, 'ajax_use_my_coupon' ) );

            add_action( 'wp_footer', array( __CLASS__, 'footer_html' ) );

            add_action( 'woocommerce_cart_collaterals' , array( __CLASS__, 'load_coupon' ) );
        }


        public static function footer_html() {
            global $woocommerce;
        ?>
            <style type="text/css">
            #coupons_list{ float: left;width: 48%; }
            #coupons_list .item_coupon{ width: 45%; padding:10px; margin: 0 5px 15px; border: 2px dashed #bbbbbb; display:flex;flex-direction: column; justify-content: space-between;  min-height: 130px;}
            #coupons_list .item_coupon.active{ background-color: #00dd88; cursor: pointer; border-color: #555555 }
            #coupons_list .item_coupon.used{ background-color: #eeeeee; border-color: #555555 }
            #coupons_list .item_coupon strong{ font-size: 26px; text-align: center;}
            </style>
            <script>

            jQuery( function( $ ) {

                $( 'body' ).on( 'updated_cart_totals updated_shipping_method', function(e){
                    //code
                    console.log('=='+e.type);
                    $.get( "<?php echo WC_Ajax::get_endpoint( 'update_my_coupon' ) ?>", function( data ) {
                        $( "#coupons_list" ).html( data );
                    });
                });


                $('#coupons_list').on('click','.active',function(){
                    var code = $(this).data('code');
                    if(code){

                        $.post( "<?php echo WC_Ajax::get_endpoint( 'use_my_coupon' ) ?>", { code: code } )
                        .done(function( data ) {
                            console.log('coupon...');
                            if( data == 'ok' ) {
                                //$("[name='update_cart']").trigger("click");
                                //$( 'body' ).trigger( 'applied_coupon', [ code ] );
                                //$( 'body' ).trigger( 'updated_cart_totals' );

                                //$( document.body ).trigger( 'updated_wc_div' );

                                //$( document.body ).trigger( 'updated_shipping_method' );
                                //jQuery('refresh_cart_fragment');

                            }

                        });

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
            echo '<div style="display:flex;flex-wrap: wrap;">';

            global $wpdb;
            global $woocommerce;

            $coupon_codes = $wpdb->get_results("SELECT post_title,post_name FROM $wpdb->posts WHERE post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_name ASC");

            // Display available coupon codes
            foreach($coupon_codes as $code)
            {
                $c = new WC_Coupon($code->post_title);

                $note = (!empty($c->get_description()) ? $c->get_description().'，':'');
                $minimum_amount = (empty($c->get_minimum_amount())) ? 0: $c->get_minimum_amount();
                $note .= '滿 '. $minimum_amount;

                if( $c->get_discount_type() == 'fixed_cart' ){
                    $note .= '，折 ' . $c->get_amount() ;
                }else if( $c->get_discount_type() == 'fixed_product' ){

                }else if( $c->get_discount_type() == 'percent' ){
                    $note .= '，打 ' . (100 - $c->get_amount()) .' 折' ;
                }

                $class_name = [];
                $class_name[] = 'item_coupon';

                if( in_array( strtolower($code->post_title), $woocommerce->cart->applied_coupons) ) {  //使用中
                    $class_name[] =  'used';
                }else{
                    if( $c->is_valid() ) $class_name[] = 'active';  //可使用
                }

        ?>
                <div class="<?php echo implode(" ",$class_name)?>" data-code="<?php echo strtolower($code->post_title)?>">
                    <small><i><?php echo $note;?></i></small>
                    <strong><?php echo $code->post_title?></strong>
                    <small>使用期限：<?php echo (empty($c->get_date_expires()) ? '無': $c->get_date_expires()->date( 'Y-m-d' ).' 前')  ?></small>
                </div>
        <?php

            }

            echo '</div>';

        }

        public static function ajax_use_my_coupon(){

            global $woocommerce;

            if( !empty($_POST['code']) ){
                $coupon_code = sanitize_text_field( wp_unslash( $_POST['code'] ) );

                if( WC()->cart->add_discount( sanitize_text_field( $coupon_code ) ) ){
                    echo 'ok';
                    //WC_Ajax::get_refreshed_fragments();
                }else{
                    echo 'not ok';
                }
            }

            //wp_send_json($data)
            wp_die();
        }


    }

endif;

new Sig_Wc_Show_Coupon();