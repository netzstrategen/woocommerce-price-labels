<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Pdf.
 */

namespace Netzstrategen\WooCommercePriceLabels;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Pdf document rendering.
 */
class Pdf {

  /**
   * Returns a rendered PDF document based on a HTML template.
   *
   * @param array $data
   *   Data to be included in the PDF document.
   * @param string $size
   *   DIN size of the document.
   * @param string $orientation
   *   Orientation (portrait, landscape) of the document.
   *
   * @return string
   *   The rendered PDF document.
   */
  public static function render(array $data, $size = 'A4', $orientation = 'portrait') {
    $tmp = sys_get_temp_dir();

    $dompdf = new Dompdf([
      'logOutputFile' => '',
      'isRemoteEnabled' => TRUE,
      'fontDir' => $tmp,
      'fontCache' => $tmp,
      'tempDir' => $tmp,
      'chroot' => $tmp,
    ]);

    ob_start();
    $label_template = apply_filters(
      Plugin::PREFIX . '/label/template',
      ['templates/label-' . $orientation .'.php']
    );
    Plugin::renderTemplate($label_template, $data);
    $html = ob_get_clean();

    // Save the resulting HTML for debugging purposes.
    if (defined('WP_DEBUG') && WP_DEBUG === TRUE) {
      file_put_contents(WP_CONTENT_DIR . '/uploads/price-label-' . $orientation . '.html', $html);
    }

    $dompdf->loadHtml($html);
    $dompdf->setPaper($size, $orientation);

    $dompdf->getOptions()->setIsFontSubsettingEnabled(TRUE);
    $dompdf->getOptions()->setDefaultMediaType('all');
    $dompdf->render();

    $dompdf->stream();
    die();
  }

}
