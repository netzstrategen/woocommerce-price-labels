<?php
  namespace Netzstrategen\WooCommercePriceLabels;
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
      include Plugin::getBasePath() . '/dist/styles/label.min.css';
      include Plugin::getBasePath() . '/dist/styles/label-landscape.min.css';
    ?>
  </style>
</head>
<body>
  <div class="container">
    <h1 class="label__title"><?= __('Exhibit', Plugin::L10N); ?></h1>
    <div class="prices">
      <div class="prices__discount">
        <p class="discount__title"><?= __('Now', Plugin::L10N) ?></p>
        <p class="discount__value">-<?= $discount_percentage ?>%</p>
      </div>
      <div class="prices__sale">
        <p class="prices__amount price--current"><?= $formatted_sale_price ?> <span class="price__currency"><?= $currency_symbol ?></span></p>
        <p class="prices__amount price--regular"><?= __('Instead of', Plugin::L10N) ?>: <?= $formatted_regular_price  ?> <span class="price__currency"><?= $currency_symbol ?></span></p>
      </div>
    </div>
    <div class="details">
      <h2 class="product-name"><?= $title ?></h2>
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
      <img class="qr-code__image" src="<?= $qr_code ?>" />
      <p class="qr-code__help">
        <?= __('Scan this QR code for more Infos directly in our online shop.', Plugin::L10N) ?>
        </p>
    </div>
    <div class="footer">
      <p><?= __('These are collection prices in EUR incl. VAT. Shipping possible against surcharge. Errors without prior sale reserve.', Plugin::L10N) ?></p>
    </div>
  </div>
</body>
</html>
