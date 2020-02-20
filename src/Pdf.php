<?php

/**
 * @file
 * Contains \Netzstrategen\WooCommercePriceLabels\Pdf.
 */

namespace Netzstrategen\WooCommercePriceLabels;

use Dompdf\Dompdf;

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
    $dompdf = new Dompdf();

    ob_start();
    $label_template = apply_filters(
      Plugin::PREFIX . '/label/template',
      ['templates/label.php']
    );
    Plugin::renderTemplate($label_template, $data);
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper($size, $orientation);
    $dompdf->render();

    $dompdf->stream();
    die();
  }

}
