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

  public static function render($data, $size = 'A4', $orientation = 'portrait') {
    $dompdf = new Dompdf();

    ob_start();
    Plugin::renderTemplate(['templates/label-' . $orientation . '.php'], $data);
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper($size, $orientation);
    $dompdf->render();
    $dompdf->stream();

    exit;
  }

}
