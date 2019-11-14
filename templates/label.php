<?php

namespace Netzstrategen\WooCommercePriceLabels;

$is_portrait = isset($orientation) && $orientation === 'portrait';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    html,
    body {
      width: 100%;
      font-size: <?= $font_base_size ?>;
    }

    <?php
      if (isset($orientation)) {
        include Plugin::getBasePath() . '/dist/styles/label-' . $orientation . '.min.css';
      }
    ?>
  </style>
</head>
<body>
  <?php if ($is_portrait): ?>
    <div class="label-logo">
      <img src="<?= $label_logo ?>" />
    </div>
  <?php endif; ?>
  <div class="container">
    <h1 class="title"><?= __('Exhibit', Plugin::L10N); ?></h1>
      <div class="prices">
        <div class="prices__discount">
          <span>-<?= $discount_percentage ?>%<span>
        </div>
        <?php if (!$is_portrait): ?>
          <div class="prices__discount-bg"></div>
        <?php endif; ?>
        <div class="prices__regular">
          <h2><?= __('Instead of', Plugin::L10N) ?></h2>
          <p class="prices__amount"><?= $regular_price ?></p>
        </div>
        <div class="prices__sale">
          <h2><?= __('Now', Plugin::L10N) ?></h2>
          <p class="prices__amount"><?= $sale_price ?></p>
        </div>
      </div>
      <div class="details">
        <?php if ($is_portrait): ?>
          <h2><?= $title ?></h2>
        <?php endif; ?>
        <?php if ($attributes): ?>
          <table class="attributes">
            <?php foreach ($attributes as $label => $value): ?>
            <tr>
              <td class="attribute__label"><?= $label ?></td>
              <td class="attribute__value"><?= $value ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>
      </div>
      <div class="qr-code">
        <img class="qr-code__image" src="<?= $qr_code ?>"" />
        <p class="qr-code__help">
          <?= __('Scan this QR code with your smartphone camera. This will take you directly to the item in our online shop with more information and details.', Plugin::L10N) ?>
        </p>
      </div>
      <div class="footer">
        <p><?= __('These are collection prices in EUR incl. VAT. Shipping possible against surcharge. Errors without prior sale reserve.', Plugin::L10N) ?></p>
      </div>
    </div>
  </div>
</body>
</html>
