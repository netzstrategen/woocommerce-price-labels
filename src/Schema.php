<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Schema.
 */

namespace Netzstrategen\WooCommercePriceLabels;

/**
 * Generic plugin lifetime and maintenance functionality.
 */
class Schema {

  /**
   * Callback for register_activation_hook().
   */
  public static function activate() {
    // Add print price label capability to allowed roles.
    Label::addPrintPriceLabelCapability(
      apply_filters(
        Plugin::PREFIX . '_roles_can_print_price_label',
        Label::getRolesCanPrintPriceLabel()
      )
    );
  }

  /**
   * Callback for register_deactivation_hook().
   */
  public static function deactivate() {
    // Remove print price label capability from allowed roles.
    Label::removePrintPriceLabelCapability(
      apply_filters(
        Plugin::PREFIX . '_roles_can_print_price_label',
        Label::getRolesCanPrintPriceLabel()
      )
    );
  }

  /**
   * Callback for register_uninstall_hook().
   */
  public static function uninstall() {
  }

}
