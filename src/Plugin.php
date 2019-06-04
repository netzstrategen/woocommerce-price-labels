<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommerce\PriceLabels\Plugin.
 */

namespace Netzstrategen\WooCommerce\PriceLabels;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'woocommerce-price-labels';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * @implements init
   */
  public static function init() {
    if (is_admin()) {
      return;
    }
  }


}
