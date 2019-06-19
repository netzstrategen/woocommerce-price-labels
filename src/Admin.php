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

  /**
   * Text for PDF label print button.
   *
   * @string
   */
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
    if (function_exists('register_field_group')) {
      static::register_acf();
    }

    // Adds prices label PDF document generation action.
    add_action('post_action_label', __CLASS__ . '::post_action_label');
    // Adds prices label format and print controls to simple product prices section.
    add_action('woocommerce_product_options_pricing', __CLASS__ . '::woocommerce_product_options_pricing', 9);
    // Adds prices label format and print controls to product variation prices section.
    add_action('woocommerce_variation_options_pricing', __CLASS__ . '::woocommerce_variation_options_pricing', 10, 3);

    // Adds a configuration section to woocommerce products settings tab.
    add_filter('woocommerce_get_sections_products', __CLASS__ . '::woocommerce_get_sections_products');
    add_filter('woocommerce_get_settings_products', __CLASS__ . '::woocommerce_get_settings_products', 10, 2);

    // Enqueues plugin scripts.
    add_filter('admin_enqueue_scripts', __CLASS__ . '::admin_enqueue_scripts');
  }

  public static function register_acf() {
    $products_attributes = [];
    foreach (wc_get_attribute_taxonomies() as $attribute) {
      $products_attributes[$attribute->attribute_name] = $attribute->attribute_label;
    }
    acf_add_local_field_group([
      'key' => 'acf_group_' . Plugin::PREFIX . '_products_attributes',
      'title' => __('Prices label', Plugin::L10N),
      'fields' => [
        [
          'key' => 'acf_' . Plugin::PREFIX . '_products_attributes',
          'label' => __('Attributes to print', Plugin::L10N),
          'name' => 'acf_' . Plugin::PREFIX . '_products_attributes',
          'type' => 'select',
          'instructions' => __('Select the attributes to print in the product prices label.', Plugin::L10N),
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
   * Adds prices label PDF document generation action.
   *
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
      $labelFormat = explode('|', get_option(Plugin::PREFIX . '-format', array_keys(static::PDF_LABEL_FORMATS)[0]));
    }

    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price() ?: $regular_price;
    if ($regular_price > $sale_price) {
      $discount_percentage = round(100 * ($regular_price - $sale_price) / $regular_price);
    }
    else {
      $discount_percentage = 0;
    }

    $data = [
      'label_logo' => Plugin::getBasePath() . '/templates/images/label-logo.png',
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

  /**
   * Adds prices label format and print controls to simple product prices section.
   *
   * @implements woocommerce_product_options_pricing
   */
  public static function woocommerce_product_options_pricing() {
    $labelFormatDefault = get_option(Plugin::PREFIX . '-format');
    $link = add_query_arg(
      [
        'post' => get_the_ID(),
        'action' => 'label',
        'format' => $labelFormatDefault,
      ],
      get_admin_url() . 'post.php'
    );
    static::displayPricesLabelFormatsSelect(static::PDF_LABEL_FORMATS, $labelFormatDefault);
    static::displayPricesLabelPrintButton($link, __(static::PDF_LABEL_PRINT_BUTTON, Plugin::L10N), static::PDF_LABEL_FORMATS);
  }

  /**
   * Adds prices label format and print controls to product variation prices section.
   *
   * @implements woocommerce_variation_options_pricing
   */
  public static function woocommerce_variation_options_pricing($loop, $variation_data, $variation) {
    $labelFormatDefault = get_option(Plugin::PREFIX . '-format');
    $link = add_query_arg(
      [
        'post' => $variation->ID,
        'action' => 'label',
        'format' => $labelFormatDefault,
      ],
      get_admin_url() . 'post.php'
    );
    static::displayPricesLabelFormatsSelect(static::PDF_LABEL_FORMATS, $labelFormatDefault);
    static::displayPricesLabelPrintButton($link, __(static::PDF_LABEL_PRINT_BUTTON, Plugin::L10N), static::PDF_LABEL_FORMATS);
  }

  /**
   * Displays a select list with the available prices label formats.
   *
   * @param array $labelFormats
   *   Available sizes, orientation and font size for the prices label.
   * @param string $labelFormatDefault
   *   Default label format.
   */
  public static function displayPricesLabelFormatsSelect($labelFormats, $labelFormatDefault='') {
    if (empty($labelFormatDefault)) {
      $labelFormatDefault = array_keys($labelFormats)[2];
    }
    $selected = ' selected="selected"';
    ?>
    <p class="form-field _price_label_print">
      <select id="<?= Plugin::PREFIX ?>-format" style="margin-right: 8px;">
      <?php foreach ($labelFormats as $key => $format): ?>
        <option value="<?= $key ?>" <?= $key === $labelFormatDefault ? $selected : '' ?>><?= $format ?></option>
      <?php endforeach; ?>
      </select>
<?php
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
   *   List of attributes for the product prices label.
   */
  public static function getProductPricesLabelAttributes(\WC_Product $product) {
    $attributes = [];
    $query = [
      'orderby' => 'name',
      'order' => 'ASC',
      'fields' => 'ids',
    ];

    $product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();
    $product_categories = wc_get_product_terms($product_id, 'product_cat', $query);

    if ($product_categories) {
      $category_attributes = static::getAttributesByProductCategoryId($product_categories[0]);
      foreach ($category_attributes as $category_attribute) {
        $values = wc_get_product_terms($product_id, 'pa_' . $category_attribute['value'], [
          'fields' => 'names',
        ]);
        if ($values) {
          $attributes[$category_attribute['label']] = $values[0];
        }
      }
    }

    return $attributes;
  }

  /**
   * Retrieves the attributes to print on the prices label for the given category.
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

  /**
   * Enqueues plugin scripts.
   *
   * @implements wp_enqueue_scripts
   */
  public static function admin_enqueue_scripts() {
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
      $script = '/assets/scripts/main.js';
    }
    else {
      $script = '/dist/scripts/main.min.js';
    }
    wp_enqueue_script(Plugin::PREFIX, Plugin::getBaseUrl() . $script, ['jquery'], Plugin::getGitVersion(), TRUE);
  }

}
