<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Plugin.
 */

namespace Netzstrategen\WooCommercePriceLabels;

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
   * Plugin base URL.
   *
   * @var string
   */
  private static $baseUrl;

  /**
   * @implements init
   */
  public static function init() {
    // Ensure user is allowed to print the price labels.
    if (!current_user_can('print_price_label')) {
      return;
    }

    // Adds price label printing controls to product summary.
    add_action('woocommerce_simple_add_to_cart', __NAMESPACE__ . '\Label::addPrintPriceLabelControls');
    add_action('woocommerce_single_variation', __NAMESPACE__ . '\Label::addPrintPriceLabelControls');

    // Prints product price label PDF document.
    add_action('post_action_label', __NAMESPACE__ . '\Label::post_action_label');

    // Schedule the cleanup of expired products price labels.
    add_action(Plugin::PREFIX . '_delete_old_price_labels', __NAMESPACE__ . '\Label::deleteOldPriceLabels');

    if (is_admin()) {
      return;
    }

    // Enqueues frontend plugin scripts and styles.
    add_action('wp_enqueue_scripts', __CLASS__ . '::wp_enqueue_scripts');
  }

  /**
   * Enqueues frontend plugin scripts and styles.
   *
   * @implements wp_enqueue_scripts
   */
  public static function wp_enqueue_scripts() {
    if (function_exists('is_product') && is_product()) {
      $git_version = static::getGitVersion();
      $baseDir = static::getScriptsBaseDir();
      $suffix = static::getScriptsMinSuffix();

      // Enqueues scripts.
      wp_enqueue_script(
        Plugin::PREFIX,
        static::getBaseUrl() . $baseDir . '/scripts/main' . $suffix . '.js',
        ['jquery'],
        $git_version,
        TRUE
      );

      // Enqueues styles.
      wp_enqueue_style(
        Plugin::PREFIX,
        static::getBaseUrl() . '/dist/styles/main.min.css',
        [],
        $git_version
      );
    }
  }

  /**
   * Returns the scripts base dir.
   *
   * @return string
   *   Scripts base dir.
   */
  public static function getScriptsBaseDir() {
    return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '/assets' : '/dist';
  }

  /**
   * Returns suffix for minified scripts.
   *
   * @return string
   *   Minified scripts suffix.
   */
  public static function getScriptsMinSuffix() {
    return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
  }

  /**
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(static::$baseUrl)) {
      static::$baseUrl = plugins_url('', static::getBasePath() . '/plugin.php');
    }
    return static::$baseUrl;
  }

  /**
   * Retrieves the absolute filesystem base path of this plugin.
   *
   * @return string
   *   Filesystem base path of this plugin.
   */
  public static function getBasePath() {
    return dirname(__DIR__);
  }

  /**
   * Renders a given plugin template, optionally overridden by the theme.
   *
   * WordPress offers no built-in function to allow plugins to render templates
   * with custom variables, respecting possibly existing theme template overrides.
   * Inspired by Drupal (5-7).
   *
   * @param array $template_subpathnames
   *   An prioritized list of template (sub)pathnames within the plugin/theme to
   *   discover; the first existing wins.
   * @param array $variables
   *   An associative array of template variables to provide to the template.
   *
   * @throws \InvalidArgumentException
   *   If none of the $template_subpathnames files exist in the plugin itself.
   */
  public static function renderTemplate(array $template_subpathnames, array $variables = []) {
    $template_pathname = locate_template($template_subpathnames, FALSE, FALSE);
    extract($variables, EXTR_SKIP | EXTR_REFS);
    if ($template_pathname !== '') {
      include $template_pathname;
    }
    else {
      while ($template_pathname = current($template_subpathnames)) {
        if (file_exists($template_pathname = static::getBasePath() . '/' . $template_pathname)) {
          include $template_pathname;
          return;
        }
        next($template_subpathnames);
      }
      throw new \InvalidArgumentException("Missing template '$template_pathname'");
    }
  }

  /**
   * Encodes an image as a Base64 string.
   *
   * @param string $image_path
   *   Absolute path to the image file.
   *
   * @return string
   *   Base64 encoded image.
   */
  public static function imageToBase64($image_path) {
    $type = pathinfo($image_path, PATHINFO_EXTENSION);
    $data = file_get_contents($image_path);
    $base64 ='';

    if ($type == 'svg') {
      $base64 = 'data:image/svg+xml;base64,' . base64_encode($data);
    }
    else {
      $base64 = 'data:image/'. $type .';base64,' . base64_encode($data);
    }
    return $base64;
  }

  /**
   * Generates a version out of the current commit hash.
   *
   * @return string
   *   Current commit hash.
   */
  public static function getGitVersion() {
    $git_version = NULL;
    if (is_dir(ABSPATH . '.git')) {
      $ref = trim(file_get_contents(ABSPATH . '.git/HEAD'));
      if (strpos($ref, 'ref:') === 0) {
        $ref = substr($ref, 5);
        if (file_exists(ABSPATH . '.git/' . $ref)) {
          $ref = trim(file_get_contents(ABSPATH . '.git/' . $ref));
        }
        else {
          $ref = substr($ref, 11);
        }
      }
      $git_version = substr($ref, 0, 8);
    }
    return $git_version;
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}
