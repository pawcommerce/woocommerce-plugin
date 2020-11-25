<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: PawCommerce Gateway
 * Plugin URI: https://github.com/pawcommerce/woocommerce-plugin
 * Description: WooCommerce plugin to for PawCommerce
 * Author: Liberate, Inc.
 * Author URI: https://www.pawcommerce.com/
 * Version: 1.0.4-dev
 */

/**
 * PawCommerce Gateway
 * Based on the PayPal Standard Payment Gateway
 *
 * Provides a plugin to accept Dogecoin
 *
 * @class      WC_Gateway_PawCommerce
 * @extends    WC_Payment_Gateway
 * @version    1.0.4-dev
 * @package    WooCommerce/Classes/Payment
 * @author     Liberate, Inc.
 */

add_action( 'plugins_loaded', 'pawc_gateway_load', 0 );

function pawc_gateway_load() {

  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
      // WooCommerce is not installed
      return;
  }

  // ADD DOGECOIN
  add_filter( 'woocommerce_currencies', 'add_dogecoin' );
  add_filter( 'woocommerce_currency_symbol', 'add_dogecoin_symbol', 10, 2);

  function add_dogecoin( $currencies ) {
    $currencies['DOGE'] = __( 'Dogecoin', 'woocommerce' );
    return $currencies;
  }

  function add_dogecoin_symbol( $currency_symbol, $currency ) {
    switch( $currency ) {
        case 'DOGE': $currency_symbol = 'DOGE'; break;
    }
    return $currency_symbol;
  }

  add_filter( 'woocommerce_payment_gateways', 'pawc_add_gateway' );

  function pawc_add_gateway( $methods ) {
    if (!in_array('WC_Gateway_Coinpayments', $methods)) {
      $methods[] = 'WC_Gateway_PawCommerce';
    }
    return $methods;
  }


  class WC_Gateway_PawCommerce extends WC_Payment_Gateway {

    var $ipn_url;

    public function __construct() {
      global $woocommerce;

      $this->ipn_url      = add_query_arg( 'wc-api', 'WC_Gateway_PawCommerce', home_url( '/' ) );
      $this->pay_addr     = "https://pay.pawcommerce.com/pay";

      $this->id           = 'pawcommerce';
      $this->icon         = apply_filters( 'woocommerce_pawc_icon', plugins_url().'/pawcommerce-for-woocommerce/assets/images/icons/pawc-pay-button.png' );
      $this->has_fields   = false;
      $this->method_title = __( 'PawCommerce', 'woocommerce' );
      $this->method_description = __( 'Accept Dogecoin securely and save.', 'woocommerce' );

      $this->init_form_fields();
      $this->init_settings();

      $this->title         = $this->get_option( 'title' );
      $this->description   = $this->get_option( 'description' );
      $this->token        = $this->get_option( 'token' );

      $this->log = new WC_Logger();

      add_action( 'woocommerce_receipt_pawc', array( $this, 'receipt_page' ) );
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      add_action( 'woocommerce_api_wc_gateway_pawcommerce', array( $this, 'pawc_ipn' ) );

    }

    function is_valid_for_use() {
      //TODO fix this
      return true;
    }

    public function admin_options() {

      ?>
      <h3><?php _e( 'PawCommerce', 'woocommerce' ); ?></h3>
      <p><?php _e( 'Payment via PawCommerce', 'woocommerce' ); ?></p>

        <table class="form-table"><?php
            $this->generate_settings_html();
        ?></table><?php

    }

    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable PawCommerce', 'woocommerce' ),
                'default' => 'yes'),
        'title' => array(
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'Dogecoin', 'woocommerce' ),
                'desc_tip'      => true),
        'description' => array(
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'Pay with Dogecoin', 'woocommerce' )),
        'token' => array(
                'title' => __( 'Token', 'woocommerce' ),
                'type'       => 'text',
                'description' => __( 'Please enter your PawCommerce token.', 'woocommerce' ),
                'default' => ''),
      );

    }

    function make_pay_request( $order ) {
      return array(
          't' => $this->token_key(),
          'c' => 'DOGE',
          'a' => number_format( $order->get_total(), 8, '.', '' ),
          'u' => $this->ipn_url,
          'z' => $this->get_return_url( $order ),
          'n' => strval(rand ( 1000000 , 9999999 )),
          'r' => $order->get_id(),
          'd' => "Payment for " . home_url( '/' ),
          'v' => '1',
        );
    }

    function generate_pawc_url($order) {
      global $woocommerce;

      if ( $order->get_status() != 'completed' && get_post_meta($order->get_id(), 'PawCommerce payment complete', true ) != 'Yes' ) {
        $order->update_status('pending', 'Customer is being redirected to the PawCommerce payment page...');
      }

      $pay_qs = $this->make_pay_request( $order );
      $pay_qs['s'] = $this->sign_request( $pay_qs );
      return $this->pay_addr . '?' . http_build_query( $pay_qs, '', '&' );

    }

    function process_payment( $order_id ) {
      return array(
          'result'   => 'success',
          'redirect' => $this->generate_pawc_url( wc_get_order( $order_id ) ),
      );
    }

    function receipt_page( $order ) {  }

    function token_parts() {
      return explode( '/' , trim( $this->token ) );
    }

    function token_key() {
      return $this->token_parts()[0];
    }

    function token_secret() {
      return $this->token_parts()[1];
    }

    function sign_request( $args ) {
      $str = implode( '|', array($args['n'], $args['c'], $args['r'], $args['a'], $args['u']) );
      $hmac = hash_hmac( 'sha256', $str, hex2bin( $this->token_secret() ) );
      return $hmac;
    }

    function sign_ipn( $args ) {
      $str = implode( '|', array($args['ref'], $args['status'], $args['addr']) );
      $this->log->add( 'PawCommerce', 'DEBUG: ' . $str );
      $this->log->add( 'PawCommerce', 'DEBUG: ' . $this->token_secret() );
      $hmac = hash_hmac( 'sha256', $str, hex2bin( $this->token_secret() ) );
      return $hmac;
    }

    function pawc_ipn() {
      @ob_clean();

      if (!$this->validate_ipn()) {
        wp_die("PawCommerce IPN failed to validate!");
        return;
      }

      $order = wc_get_order( $_POST['ref'] );

      if ($order === FALSE) {
        wp_die("PawCommerce IPN failed to provide valid order id!");
        return;
      }

      $this->process_ipn( $order, $_POST );

    }

    function validate_ipn() {
      global $woocommerce;


      $order = false;
      $ipn_ok = false;
      $sig_ok = false;
      $error_msg = "Unknown error";

      if (empty( $_POST )) {
        $error_msg = "IPN sent no data! Contact PawCommerce Support!";
      } else if (!isset($_POST['status']) || !isset($_POST['addr']) || !isset($_POST['sig'])) {
        $error_msg = "IPN sent incomplete data! Contact PawCommerce Support!";
      } else if (!in_array($_POST['status'], array('pending', 'paid', 'cancelled'))) {
        $error_msg = "IPN sent unknown status! Contact PawCommerce Support!";
      } else {

        $ipn_ok = true;
        $sig = $this->sign_ipn($_POST);

        if ($sig == $_POST['sig']) {
          $sig_ok = true;
        } else {
          $error_msg = "IPN signature mismatch! got " . $_POST['sig'] . " expected " . $sig;
        }

      }

      if (isset($_POST['ref'])) {
        $order = wc_get_order( $_POST['ref'] );

        if ($order === FALSE) {
          $ipn_ok = false;
        }

      } else {
        $error_msg = "IPN didn't send ref! Contact PawCommerce Support!";
      }

      if ($ipn_ok && $sig_ok) {
        return true;
      }

      if ($order !== FALSE) {
        $order->update_status('on-hold', sprintf( __( 'ERROR: %s', 'woocommerce' ), $error_msg ) );
      }

      $this->log->add( 'PawCommerce', 'IPN ERROR: ' . $error_msg );

      return false;

    }

    function process_ipn( $order, $data ) {
      global $woocommerce;

      $data = stripslashes_deep( $data );

      $this->log->add( 'PawCommerce', 'Order #'.$order->get_id().' payment status: ' . $data['status'] );
      $note = 'PawCommerce confirmed payment status: ' . $data['status'];
      if ( isset( $data['conf'] ) &&  $data['status'] == 'paid' ) {
        $note .= ', ' . $data['conf'] . ' confirmations';
      }
      $order->add_order_note( $note );

      update_post_meta( $order->get_id(), 'address', $data['addr'] );

      if ( isset( $data['alias'] ) ) {
        update_post_meta( $order->get_id(), 'alias', $data['alias'] );
      }

      if ( isset( $data['txs'] ) ) {
        update_post_meta( $order->get_id(), 'transactions', str_replace( ',', ' ', $data['txs'] ) );
      }

      switch ($data['status']) {
        case 'pending':
          $order->update_status( 'pending', 'PawCommerce payment pending to ' . $data['addr'] );
          break;
        case 'paid':
          $order->payment_complete();
          break;
        case 'cancelled':
          $order->update_status( 'cancelled', 'PawCommerce payment cancelled');
          break;
        default:
          // nothing to do
          break;
      }

      die("OK");

    }

  }
}
