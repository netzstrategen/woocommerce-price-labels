<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Admin.
 */

namespace Netzstrategen\WooCommercePriceLabels;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * @implements admin_init
   */
  public static function init() {
    if (function_exists('register_field_group')) {
      static::register_acf();
    }

    // Adds a configuration section to woocommerce products settings tab.
    add_filter('woocommerce_get_sections_products', __CLASS__ . '::woocommerce_get_sections_products');
    add_filter('woocommerce_get_settings_products', __CLASS__ . '::woocommerce_get_settings_products', 10, 2);

    // Adds price label format and print controls to product price sections.
    add_action('woocommerce_product_options_pricing', __CLASS__ . '::woocommerce_product_options_pricing', 9);
    add_action('woocommerce_variation_options_pricing', __CLASS__ . '::woocommerce_variation_options_pricing', 10, 3);

    // Enqueues backend plugin scripts.
    add_filter('admin_enqueue_scripts', __CLASS__ . '::admin_enqueue_scripts');
  }

  public static function register_acf() {
    $products_attributes = [];
    foreach (wc_get_attribute_taxonomies() as $attribute) {
      $products_attributes[$attribute->attribute_name] = $attribute->attribute_label;
    }
    acf_add_local_field_group([
      'key' => 'acf_group_' . Plugin::PREFIX . '_products_attributes',
      'title' => __('Price label', Plugin::L10N),
      'fields' => [
        [
          'key' => 'acf_' . Plugin::PREFIX . '_products_attributes',
          'label' => __('Attributes to print', Plugin::L10N),
          'name' => 'acf_' . Plugin::PREFIX . '_products_attributes',
          'type' => 'select',
          'required' => 0,
          'conditional_logic' => 0,
          'choices' => $products_attributes,
          'default_value' => [],
          'allow_null' => 0,
          'multiple' => 1,
          'ui' => 1,
          'ajax' => 1,
          'return_format' => 'array',
          'placeholder' => '',
        ],
      ],
      'location' => [
        [
          [
            'param' => 'taxonomy',
            'operator' => '==',
            'value' => 'product_cat',
          ],
        ],
      ],
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ]);

  }

  /**
   * Adds a configuration section to woocommerce products settings tab.
   *
   * @implements woocommerce_get_sections_products
   */
  public static function woocommerce_get_sections_products($sections) {
    $sections[Plugin::PREFIX] = __('Price label', Plugin::L10N);
    return $sections;
  }

  /**
   * Adds configuration settings to woocommerce products settings tab.
   *
   * @implements woocommerce_get_settings_products
   */
  public static function woocommerce_get_settings_products($settings, $current_section) {
    if ($current_section === Plugin::PREFIX) {
      $settings = [
        [
          'id' => Plugin::PREFIX,
          'name' => __('Price label settings', Plugin::L10N),
          'type' => 'title',
        ],
        [
          'id' => Plugin::PREFIX . '-format',
          'name' => __('Default label format', Plugin::L10N),
          'type' => 'select',
          'options' => Label::PDF_LABEL_FORMATS,
        ],
        [
          'type' => 'sectionend',
          'id' => Plugin::L10N,
        ],
      ];
    }
    return $settings;
  }

  /**
   * Adds price label format and print controls to simple product price section.
   *
   * @implements woocommerce_product_options_pricing
   */
  public static function woocommerce_product_options_pricing() {
    $product_id = get_the_ID();
    $label_format_default = get_option(Plugin::PREFIX . '-format');
    Label::addProductPriceLabelControls($product_id, $label_format_default);
  }

  /**
   * Adds price label print controls to product variation price section.
   *
   * @implements woocommerce_variation_options_pricing
   */
  public static function woocommerce_variation_options_pricing($loop, $variation_data, $variation) {
    $product_id = $variation->ID;
    $label_format_default = get_option(Plugin::PREFIX . '-format');
    Label::addProductPriceLabelControls($product_id, $label_format_default);
  }

  /**
   * Enqueues backend plugin scripts.
   *
   * @implements wp_enqueue_scripts
   */
  public static function admin_enqueue_scripts($hook) {
    if ($hook !== 'post.php' && get_post_type() === 'product') {
      return;
    }

    $baseDir = Plugin::getScriptsBaseDir();
    $suffix = Plugin::getScriptsMinSuffix();

    // Enqueues scripts.
    wp_enqueue_script(
      Plugin::PREFIX,
      Plugin::getBaseUrl() . $baseDir . '/scripts/main' . $suffix . '.js',
      ['jquery'],
      FALSE,
      TRUE
    );
  }

}
