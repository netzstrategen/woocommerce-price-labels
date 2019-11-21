<?php

/*
  Plugin Name: WooCommerce Price Labels
  Version: 1.1.1
  Text Domain: woocommerce-price-labels
  Description: Generates price labels as PDFs for WooCommerce products.
  Author: netzstrategen
  Author URI: https://netzstrategen.com/sind
  License: GPL-2.0+
  License URI: http://www.gnu.org/licenses/gpl-2.0
*/

namespace Netzstrategen\WooCommercePriceLabels;

if (!defined('ABSPATH')) {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  exit;
}

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class) {
  static $ns_offset;
  if (strpos($class, __NAMESPACE__ . '\\') === 0) {
    if ($ns_offset === NULL) {
      $ns_offset = strlen(__NAMESPACE__) + 1;
    }
    include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
  }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');

register_activation_hook(__FILE__, __NAMESPACE__ . '\Schema::activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\Schema::deactivate');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Schema::uninstall');

add_action('plugins_loaded', __NAMESPACE__ . '\Plugin::loadTextdomain');
add_action('init', __NAMESPACE__ . '\Plugin::init', 20);
add_action('admin_init', __NAMESPACE__ . '\Admin::init');
