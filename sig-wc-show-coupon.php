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

            add_action( 'wp_footer', array( __CLASS__, 'footer_html' ) );

            add_action( 'woocommerce_cart_collaterals' , array( __CLASS__, 'load_coupon' ) );
        }


        public static function footer_html() {
            global $woocommerce;
        ?>
            <style type="text/css">
            #coupons_list{ float: left;width: 48%; }
            #coupons_list .item_coupon{ box-sizing: border-box; position: relative; display: flex; justify-content: space-between; align-items: center; height: 120px; margin-bottom: 20px; box-shadow:1px 1px 4px  rgba(0,0,0,0.5); -moz-box-shadow:1px 1px 4px  rgba(0,0,0,0.5); -webkit-box-shadow:1px 1px 4px  rgba(0,0,0,0.5);}
            #coupons_list .item_coupon.active{ background: linear-gradient(to right, #ffb300 0%, #ffb300 35%, #eeeeee 35%, #eeeeee 100%); }
            #coupons_list .item_coupon.used{ background: linear-gradient(to right, #ffb300 0%, #ffb300 35%, #eeeeee 35%, #eeeeee 100%); filter: grayscale(0.6); }
            #coupons_list .item_coupon.disabled{ background-color: #f2f2f2; }
            #coupons_list .item_coupon:before{ content: ""; position: absolute; display: block; top: 0; left: calc( 35% - 3px ); width: 3px; height: 100%; border-left: 3px dashed #fff; }
            #coupons_list .item_coupon .code{ box-sizing: border-box; width: 35%; text-align: center; font-size: 26px; font-weight: 600; }
            #coupons_list .item_coupon .note{ box-sizing: border-box; width: 65%; height: 100%; padding: 15px; display: flex; justify-content: flex-start; flex-direction: column; }
            #coupons_list .item_coupon .btn_coupon{ font-size: 14px; font-weight: 400; text-align: center; border: 1px solid #ffb300; color: #ffb300; padding: 2px 10px; margin-left: auto; margin-top: auto; text-decoration: none; }
            #coupons_list .item_coupon .btn_coupon:hover, #coupons_list .item_coupon .btn_coupon:focus{ background-color: #ffb300; color: #ffffff; }
            #coupons_list .item_coupon .btn_coupon[disabled], #coupons_list .item_coupon .btn_coupon[disabled]:hover{ border:1px solid #bbbbbb; color: #bbbbbb; background-color: transparent;}
            </style>
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
                $btn_name = '';

                if( in_array( strtolower($code->post_title), $woocommerce->cart->applied_coupons) ) {  //使用中
                    $class_name[] =  'used';
                    $btn_name = '<a href="https://woo.abc.com/cart/?remove_coupon='. strtolower($code->post_title) .'" class="btn_coupon woocommerce-remove-coupon" data-coupon="'. strtolower($code->post_title) .'">移除折價券</a>';
                }else if( $c->is_valid() ) {
                    $class_name[] = 'active';  //可使用
                    $btn_name = '<button class="btn_coupon" type="button" name="user_coupon" value="'.$code->post_title.'">使用折價券</button>';
                }else{
                    $class_name[] = 'disabled';
                    $btn_name = '<button class="btn_coupon" type="button" disabled>未符合資格</button>';
                }

        ?>
                <div class="<?php echo implode(" ",$class_name)?>" data-code="<?php echo $code->post_title?>">
                    <div class="code">
                        <?php echo $code->post_title?></strong>
                    </div>
                    <div class="note">
                        <div><?php echo $note;?></div>
                        <small>使用期限：<?php echo (empty($c->get_date_expires()) ? '無': $c->get_date_expires()->date( 'Y-m-d' ).' 前')  ?></small>
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