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
    @font-face {
      font-family: HelveticaNeueCondensedBold;
      src: local('Helvetica Neue Condensed Bold'), local('HelveticaNeue-CondensedBold');
      font-weight: 700;
      font-style: normal;
    }
    @font-face {
      font-family: HelveticaNeueCondensedBlack;
      src: local('Helvetica Neue Condensed Black'), local('HelveticaNeue-CondensedBlack');
      font-weight: 900;
      font-style: normal;
    }
    <?php
    include Plugin::getBasePath() . '/dist/styles/label.min.css';
    include Plugin::getBasePath() . '/dist/styles/label-portrait.min.css';
    ?>
  </style>
</head>
<body class="<?= $paper_size ?>-body-wrapper">
  <div class="label-logo">
    <img src="<?= $label_logo ?>" />
  </div>
  <div class="container">
    <h1 class="label__title <?= $paper_size ?>"><?= __('Exhibit', Plugin::L10N); ?></h1>
    <h2 class="product-name"><?= $title ?></h2>
    <div class="prices">
      <div class="prices__sale">
        <p class="prices__amount price--current"><?= $formatted_sale_price ?> <span class="price__currency"><?= $currency_symbol ?></span></p>
        <p class="prices__amount price--regular"><?= __('Instead of', Plugin::L10N) ?>: <?= $formatted_regular_price  ?> <span class="price__currency"><?= $currency_symbol ?></span> <span class="price__label">(UVP)</span></p>
      </div>
    </div>
    <div class="details">
      <?php
      if (!empty($short_description)) : ?>
        <div class="short-description">
          <?= $short_description ?>
        </div>
      <?php elseif ($attributes) : ?>
        <table class="attributes">
          <?php foreach ($attributes as $label => $value) : ?>
          <tr>
            <td class="attribute__label"><?= $label ?></td>
            <td class="attribute__value"><?= $max_row_chars && strlen($value) > $max_row_chars ? sprintf('%s...', substr($value, 0, $max_row_chars)) : $value ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
      
      <div class="qr-code">
        <p class="qr-code__help">
          <?= __('Alles info:', Plugin::L10N) ?>
        </p>
        <img class="qr-code__image" src="<?= $qr_code ?>" />
      </div>
    </div>
    <div class="footer">
      <p><strong><?= __('These are collection prices in EUR incl. VAT.', Plugin::L10N) ?></strong> <span class="footer__additional-text"><?= __('Shipping possible against surcharge. Errors without prior sale reserve.', Plugin::L10N) ?></span></p>
    </div>
  </div>
</body>
</html>
