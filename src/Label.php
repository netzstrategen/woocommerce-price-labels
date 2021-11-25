<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Label.
 */

namespace Netzstrategen\WooCommercePriceLabels;

use WC_Product;
use Endroid\QrCode\QrCode;

/**
 * Price label related functionality.
 */
class Label {

  /**
   * Paper size, orientation and font size for the price labels.
   *
   * @array
   */
  const PDF_LABEL_FORMATS = [
    'A6|portrait|8px' => 'A6, portrait',
    'A5|portrait|12px' => 'A5, portrait',
    'A4|portrait|15px' => 'A4, portrait',
    'A3|portrait|24px' => 'A3, portrait',
    'A3|landscape|24px' => 'A3, landscape',
  ];

  /**
   * Size of QR code to include in the PDF label document.
   *
   * @integer
   */
  const QR_CODE_SIZE_PORTRAIT = 400;
  const QR_CODE_SIZE_LANDSCAPE = 600;

  /**
   * Adds print price label capability to given roles.
   *
   * @param array $roles
   *   List of role names to add price label printing capability to.
   */
  public static function addPrintPriceLabelCapability(array $roles) {
    foreach ($roles as $role) {
      get_role($role)->add_cap('print_price_label', TRUE);
    }
  }

  /**
   * Removes print price label capability from given roles.
   *
   * @param array $roles
   *   List of role names to remove price label printing capability from.
   */
  public static function removePrintPriceLabelCapability(array $roles) {
    foreach ($roles as $role) {
      get_role($role)->remove_cap('print_price_label', TRUE);
    }
  }

  /**
   * Returns the list of roles allowed to print price labels.
   *
   * @return array
   *   Roles that can print products price labels.
   */
  public static function getRolesCanPrintPriceLabel() {
    return defined('ROLES_CAN_PRINT_PRICE_LABEL') && ROLES_CAN_PRINT_PRICE_LABEL ?
      ROLES_CAN_PRINT_PRICE_LABEL :
      ['sale-editor', 'administrator'];
  }

  /**
   * Adds price label printing controls to product summary.
   *
   * @implements woocommerce_single_product_summary
   * @implements woocommerce_single_variation
   */
  public static function addPrintPriceLabelControls() {
    $product_id = get_the_ID();
    $label_format_default = get_option(Plugin::PREFIX . '-format');
    Label::addProductPriceLabelControls($product_id, $label_format_default);
  }

  /**
   * Generates a price label PDF document.
   *
   * @implements post_action_{$action}
   */
  public static function post_action_label($post_id = 0) {
    // Removes the capability 'edit_posts' from role 'sale-editor' to prevent
    // unauthorised access to the site backend.
    get_role('sale-editor')->remove_cap('edit_posts', TRUE);

    if (!current_user_can('print_price_label') || !$post_id || !$product = wc_get_product($post_id)) {
      return;
    }

    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price() ?: $regular_price;
    if ($regular_price > $sale_price) {
      $discount_percentage = round(100 * ($regular_price - $sale_price) / $regular_price);
    }
    else {
      $discount_percentage = 0;
    }

    if (isset($_GET['format'])) {
      $label_format = explode('|', $_GET['format']);
    }
    else {
      $label_format = explode('|', get_option(Plugin::PREFIX . '-format', array_keys(static::PDF_LABEL_FORMATS)[0]));
    }

    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price() ?: $regular_price;
    if ($regular_price > $sale_price) {
      $discount_percentage = round(100 * ($regular_price - $sale_price) / $regular_price);
    }
    else {
      $discount_percentage = 0;
    }

    $price_suffix = ',-';
    $formatted_regular_price = wc_price($regular_price, ['price_format' => '%2$s']);
    // Replace ",00" with ",-".
    $formatted_regular_price = preg_replace('@,00@', $price_suffix, $formatted_regular_price);
    $formatted_sale_price = wc_price($sale_price, ['price_format' => '%2$s']);
    $formatted_sale_price = preg_replace('@,00@', $price_suffix, $formatted_sale_price);
    $label_logo = apply_filters(
      Plugin::PREFIX . '/label/header_image_path',
      Plugin::getBasePath() . '/templates/images/label-logo.png'
    );

    $data = [
      'orientation' => $label_format[1],
      'label_logo' => Plugin::imageToBase64($label_logo),
      'title' => $product->get_title(),
      'price' => wc_price($product->get_price()),
      'regular_price' => preg_replace( '/[^0-9.,]/', '', wc_price($regular_price)),
      'formatted_regular_price' => $formatted_regular_price,
      'formatted_sale_price' => $formatted_sale_price,
      'sale_price' => preg_replace( '/[^0-9.,]/', '', wc_price($sale_price)),
      'discount_percentage' => $discount_percentage,
      'currency_symbol' => get_woocommerce_currency_symbol(),
      'font_base_size' => $label_format[2],
    ];

    $attributes = static::getProductPriceLabelAttributes($product);

    // Add product dimensions.
    if ($dimensions = static::getProductDimensions($product, $attributes)) {
      if (isset($dimensions['depth'])) {
        $dimensions_label = sprintf(__('Dimensions (D&times;W&times;H) (%s)', Plugin::L10N), get_option('woocommerce_dimension_unit'));
      }
      else {
        $dimensions_label = sprintf(__('Dimensions (L&times;W&times;H) (%s)', 'woocommerce'), get_option('woocommerce_dimension_unit'));
      }
      $attributes[$dimensions_label] = wc_format_dimensions($dimensions);
    }

    // Add unique product ID.
    $moeve_ids = static::getMoeveIds($post_id);
    if (!empty($moeve_ids)) {
      $product_id_label = __('SKU:', Plugin::L10N);
      $attributes[$product_id_label] = implode(', ', $moeve_ids);
    }

    // Ensure custom attributes related to product dimensions
    // are not added to the label.
    unset($attributes['Tiefe']);
    unset($attributes['Breite']);
    unset($attributes['Höhe']);

    $data['attributes'] = $attributes;

    $qr_code_size = $label_format[1] === 'landscape' ? self::QR_CODE_SIZE_LANDSCAPE : self::QR_CODE_SIZE_PORTRAIT;
    $qr_code = static::getProductQrCode($post_id, $qr_code_size);

    $data['qr_code'] = 'data:image/png;base64,' . base64_encode($qr_code);

    return Pdf::render($data, $label_format[0], $label_format[1]);
  }

  /**
   * Adds price label printing controls to a given product.
   *
   * @param int $product_id
   *   The product unique identifier.
   * @param string $label_format_default
   *   The default format for the price label.
   * @param bool $enabled
   *   If TRUE the controls are enabled.
   */
  public static function addProductPriceLabelControls($product_id, $label_format_default = '', $enabled = TRUE) {
    Label::displayPriceLabelFormatsSelect(Label::PDF_LABEL_FORMATS, $label_format_default, $enabled);
    Label::displayPriceLabelPrintButton($product_id, __('Print Sale PDF label', Plugin::L10N), $enabled);
  }

  /**
   * Displays a select list with the available price label formats.
   *
   * @param array $label_formats
   *   Available sizes, orientation and font size for the price label.
   * @param string $label_format_default
   *   Default label format.
   */
  public static function displayPriceLabelFormatsSelect(array $label_formats, $label_format_default = '') {
    if (empty($label_format_default)) {
      $label_format_default = array_keys($label_formats)[0];
    }
    ?>
    <p class="form-field price-label-print">
      <select
        class="<?= Plugin::PREFIX ?>-format"
        style="margin-right: 8px;"
      >
      <?php foreach ($label_formats as $key => $format): ?>
        <option
          value="<?= $key ?>"
          <?= selected($key, $label_format_default, FALSE) ?>>
          <?= $format ?>
        </option>
      <?php endforeach; ?>
      </select>
    <?php
  }

  /**
   * Displays the button to print the price label.
   *
   * @param int $product_id
   *   Product unique identifier.
   * @param string $printLabelButtonText
   *   Text to show in the print label button.
   */
  public static function displayPriceLabelPrintButton($product_id, $printLabelButtonText) {
    $args = [
      'post' => $product_id,
      'action' => 'label',
      'format' => get_option(Plugin::PREFIX . '-format', array_keys(static::PDF_LABEL_FORMATS)[0]),
    ];
    $link = add_query_arg($args, get_admin_url() . 'post.php');
    ?>
      <a
        href="<?= $link ?>"
        class="button <?= Plugin::PREFIX ?>-button"
        target="_blank"
      >
      <?= $printLabelButtonText ?>
      </a>
    </p>
    <?php
  }

  /**
   * Retrieves Moeve IDs for given product.
   *
   * @param int $product_id
   *   Product unique identifier.
   *
   * @return array
   *   Moeve IDs for given product.
   */
  public static function getMoeveIds($product_id) {
    global $wpdb;
    $query = 'SELECT meta_value FROM wp_postmeta WHERE meta_key LIKE %s AND post_id = %d';
    $query = $wpdb->prepare($query, '_woocommerce-moeve_id_%', $product_id);
    return array_filter($wpdb->get_col($query));
  }

  /**
   * Retrieves dimensions for given product.
   *
   * If standard product dimensions values of WooCommerce are not set,
   * attempt to retrieve custom attributes 'Tiefe' (depth), 'Breite' (width),
   * 'Höhe' (height).
   *
   * @param \WC_Product $product
   *   Product to get the dimensions from.
   * @param array $attributes
   *   Product attributes.
   *
   * @return string
   *   Product dimensions.
   */
  public static function getProductDimensions(WC_Product $product, array $attributes): array {
    $dimensions = $product->get_dimensions(FALSE);
    if (array_filter($dimensions)) {
      return $dimensions;
    }
    elseif (!empty($attributes['Tiefe']) && !empty($attributes['Breite']) && !empty($attributes['Höhe'])) {
      // Group dimensions DxWxH in a single entry.
      return [
        'depth' => $attributes['Tiefe'],
        'width' => $attributes['Breite'],
        'height' => $attributes['Höhe'],
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Retrieves the attributes to be printed in the product price label.
   *
   * @param \WC_Product $product
   *   Product for which attributes should be retrieved.
   *
   * @return array
   *   List of attributes for the product price label.
   */
  public static function getProductPriceLabelAttributes(WC_Product $product) {
    $attributes = [];
    $query = [
      'orderby' => 'name',
      'order' => 'ASC',
      'fields' => 'ids',
    ];

    $product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();

    // Get the attributes assigned to the product primary category if it exists.
    if (function_exists('yoast_get_primary_term_id')) {
      if ($primary_term_product_id = yoast_get_primary_term_id('product_cat')) {
        $category_attributes = static::getAttributesByProductCategoryId($primary_term_product_id);
      };
    }

    // Find the first product category with assigned attributes and retrieve
    // them.
    if (empty($category_attributes)) {
      $product_categories = wc_get_product_terms($product_id, 'product_cat', $query);
      foreach ($product_categories as $product_category) {
        if ($category_attributes = static::getAttributesByProductCategoryId($product_category)) {
          break;
        };
      }
    }

    // Collect the names of the retrieved product attributes.
    foreach ($category_attributes as $category_attribute) {
      $values = wc_get_product_terms($product_id, 'pa_' . $category_attribute['value'], [
        'fields' => 'names',
      ]);
      if ($values) {
        $attributes[$category_attribute['label']] = implode(', ', $values);
      }
    }

    return $attributes;
  }

  /**
   * Retrieves attributes to print on the price label for the given category.
   *
   * Traverses up recursively the product categories list until it finds a
   * a category with assigned product attributes.
   *
   * @param int $category_id
   *   Unique category identifier.
   *
   * @return array
   *   Category attributes.
   */
  public static function getAttributesByProductCategoryId($category_id) {
    if (!$attributes = get_field('acf_' . Plugin::PREFIX . '_products_attributes', 'product_cat_' . $category_id)) {
      if ($parent_category_id = get_term_by('id', $category_id, 'product_cat')->parent) {
        $attributes = static::getAttributesByProductCategoryId($parent_category_id);
      }
    };

    return $attributes;
  }

  /**
   * Retrieves basic data (SKU, dimensions and weight) for a given product.
   *
   * @param \WC_Product $product
   *   Product for which data has to be retrieved.
   *
   * @return array
   *   Set of product data including weight, dimensions and SKU.
   */
  public static function getProductData(WC_Product $product) {
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
