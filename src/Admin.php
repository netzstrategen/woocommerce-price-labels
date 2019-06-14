<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Admin.
 */

namespace Netzstrategen\WooCommercePriceLabels;

use Endroid\QrCode\QrCode;

/**
 * Administrative back-end functionality.
 */
class Admin {

  const PDF_LABEL_PRINT_BUTTON = 'Print Sale PDF label';

  const PDF_LABEL_FORMATS = [
    'A5|portrait|12px' => 'A5, portrait',
    'A4|portrait|16px' => 'A4, portrait',
    'A3|portrait|24px' => 'A3, portrait',
    'A3|landscape|24px' => 'A3, landscape',
  ];

  /**
   * Size of QR code to include in the PDF label document.
   *
   * @integer
   */
  const QR_CODE_SIZE = 400;

  /**
   * @implements admin_init
   */
  public static function init() {
    add_action('post_action_label', __CLASS__ . '::post_action_label');
    add_action('woocommerce_product_options_pricing', __CLASS__ . '::woocommerce_product_options_pricing', 1);
    add_action('woocommerce_variation_options_pricing', __CLASS__ . '::woocommerce_variation_options_pricing', 10, 3);
  }

  /**
   * @implements post_action_{$action}
   */
  public static function post_action_label($post_id) {
    $product = wc_get_product($post_id);

    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price() ?: $regular_price;
    if ($regular_price > $sale_price) {
      $discount_percentage = round(100 * ($regular_price - $sale_price) / $regular_price);
    }
    else {
      $discount_percentage = 0;
    }

    if (isset($_GET['format'])) {
      $labelFormat = explode('|', $_GET['format']);
    }
    else {
      $labelFormat = explode('|', get_option(Plugin::PREFIX . '-format'));
    }

    $data = [
      'title' => $product->get_title(),
      'price' => wc_price($product->get_price()),
      'regular_price' => wc_price($regular_price),
      'sale_price' => wc_price($sale_price),
      'discount_percentage' => $discount_percentage,
      'font_base_size' => $labelFormat[2],
    ];

    $attributes = static::getProductPricesLabelAttributes($product);

    // Group dimensions LxWxH in a single entry.
    if (!empty($attributes['Tiefe']) && !empty($attributes['Breite']) && !empty($attributes['Höhe'])) {
      $attributes[__('Dimensions (LWH)', Plugin::L10N)] = $attributes['Tiefe'] . ' x ' . $attributes['Breite'] . ' x ' . $attributes['Höhe'];
      unset($attributes['Tiefe']);
      unset($attributes['Breite']);
      unset($attributes['Höhe']);
    }
    $data['attributes'] = $attributes;

    $qr_code = static::getProductQrCode($post_id, static::QR_CODE_SIZE);
    $data['qr_code'] = 'data:image/png;base64,' . base64_encode($qr_code);

    Pdf::render($data, $labelFormat[0], $labelFormat[1]);
  }

  public static function woocommerce_product_options_pricing() {
    global $post;

    $link = add_query_arg('action', 'label');
    echo '<p><a class="button" href="' . $link . '" target="blank">' . __(static::PDF_LABEL_PRINT_BUTTON, Plugin::L10N) . '</a></p>';
  }

  public static function woocommerce_variation_options_pricing($loop, $variation_data, $variation) {
    $link = add_query_arg(
      [
        'post' => $variation->ID,
        'action' => 'label',
      ],
      get_admin_url() . 'post.php'
    );
    echo '<p><a class="button" href="' . $link . '" target="blank">' . __(static::PDF_LABEL_PRINT_BUTTON, Plugin::L10N) . '</a></p>';
  }

  /**
   * Displays the button to print the prices label.
   *
   * @param string $link
   *   URL to trigger the label PDF doc generation.
   * @param string $printLabelButtonText
   *   Text to show in the print label button.
   */
  public static function displayPricesLabelPrintButton($link, $printLabelButtonText) {
    echo '<a id="' . Plugin::PREFIX . '-button" class="button" href="' . esc_url($link) . '" target="_blank">' . $printLabelButtonText . '</a></p>';
  }

  /**
   * Adds a configuration section to woocommerce products settings tab.
   *
   * @implements woocommerce_get_sections_products
   */
  public static function woocommerce_get_sections_products($sections) {
    $sections[Plugin::PREFIX] = __('Prices label PDF', Plugin::L10N);
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
          'name' => __('Price label PDF settings', Plugin::L10N),
          'type' => 'title',
          'desc' => __('The following options are used to configure the product prices label', Plugin::L10N),
        ],
        [
          'id' => Plugin::PREFIX . '-format',
          'name' => __('Default label format', Plugin::L10N),
          'type' => 'select',
          'options' => static::PDF_LABEL_FORMATS,
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
   * Retrieves the attributes to be printed in the product prices label.
   *
   * @param WC_Product $product
   *   Product for which attributes should be retrieved.
   *
   * @return array
   *   List of attributes of the product.
   */
  public static function getProductAttributes(\WC_Product $product) {
    if ($parent_id = $product->get_parent_id()) {
      $product = wc_get_product($parent_id);
    }
    $attributes = [];

    foreach ($product->get_attributes() as $attribute) {
      if (!$attribute->get_visible()) {
        continue;
      }
      if ($attribute->is_taxonomy()) {
        $values = [];
        $attribute_taxonomy = $attribute->get_taxonomy_object();
        $name = $attribute_taxonomy->attribute_label;
        $values = wc_get_product_terms($product->get_id(), $attribute->get_name(), [
          'fields' => 'names',
        ]);
      }
      $attributes[$name] = $values;
    }
    return $attributes;
  }

  /**
   * Retrieves basic data (SKU, dimensions and weight) for a given product.
   *
   * @param WC_Product $product
   *   Product for which data has to be retrieved.
   *
   * @return array
   *   Set of product data including weight, dimensions and SKU.
   */
  public static function getProductData(\WC_Product $product) {
    $product_data = [];

    // Adds sku to the cart item data.
    if ($sku = $product->get_sku()) {
      $product_data[__('SKU', 'woocommerce')] = $sku;
    }

    // Adds dimensions to the cart item data.
    if ($dimensions_value = array_filter($product->get_dimensions(FALSE))) {
      $product_data[__('Dimensions', 'woocommerce')] = wc_format_dimensions($dimensions_value);
    }

    // Adds weight to the cart item data.
    if ($weight_value = $product->get_weight()) {
      $product_data[__('Weight', 'woocommerce')] = $weight_value . ' kg';
    }

    return apply_filters(Plugin::PREFIX . '_product_data', $product_data);
  }

  /**
   * Generates the QR code graphic of a given content.
   *
   * @param string $content
   *   Content to encode as QR.
   * @param int $size
   *   Size in pixels of the QR code graphic.
   * @param string $type
   *   Graphic type: jpg, png.
   *
   * @return string
   *   QR code.
   */
  public static function getProductQrCode($content, $size = 400, $type = 'png') {
    $qrCode = new QrCode(get_permalink($content));
    ob_start();
    header('Content-Type: ' . $qrCode->getContentType());
    $qrCode->setSize($size);
    $qrCode->setWriterByName($type);
    echo $qrCode->writeString();

    return ob_get_clean();
  }

}
