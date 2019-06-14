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

    * {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
    }

    h1,
    h2 {
      font-weight: regular;
    }

    h1 {
      margin-top: 1.5rem;
      padding-bottom: 0.5rem;
      font-size: 2.5rem;
      text-transform: uppercase;
      text-align: center;
    }

    h2 {
      font-size: 2.5rem;
    }

    th {
      text-align: left;
    }

    .label-logo img {
      width: 100%;
      height: auto;
    }

    .container {
      margin: 0 1.5rem;
    }

    .prices {
      position: relative;
      margin-bottom: 0.5rem;
      border-top: 1px black solid;
      border-bottom: 1px black solid;
    }

    .prices:after {
      height: 0;
      content: '';
      clear: both;
    }

    .prices__discount {
      position: absolute;
      z-index: 100;
      top: 1.25rem;
      left: 50%;
      width: 7rem;
      height: 7rem;
      margin-left: -3.5rem;
      border-radius: 3.5rem;
      font-size: 2.25rem;
      line-height: 5rem;
      font-weight: bolder;
      text-align: center;
      color: white;
      background: #d12d37;
    }

    .prices__regular {
      float: left;
      width: 50%;
    }

    .prices__regular h2,
    .prices__regular p {
      margin-right: 3rem;
    }

    .prices__sale,
    .prices__regular {
      padding: 1.25rem 0;
      text-align: center;
    }

    .prices__sale {
      float: right;
      width: 50%;
      border-left: 1px black solid;
      font-weight: bolder;
      color: #d12d37;
    }

    .prices__sale h2,
    .prices__sale p {
      margin-left: 3rem;
    }

    .prices__amount {
      font-size: 3.125rem;
    }

    .prices__sale h2 {
      font-weight: bolder;
    }

    .details {
      margin-bottom: 1rem;
    }
    
    .details > h2 {
      margin-bottom: 1rem;
      text-align: center;
      font-size: 1.5rem;
      font-weight: bold;
    }

    .details,
    .qr-code {
      font-size: 0.9em;
    }

    .attribute__label {
      padding-right: 1rem;
      font-weight: bold;
    }

    .qr-code:after {
      height: 0;
      content: '';
      clear: both;
    }

    .qr-code__image {
      float: left;
      display: block;
      width: 6rem;
      margin-top: -0.35em;
      margin-left: -0.35em;
    }

    .qr-code__help {
      margin-left: 9rem;
    }

    .footer {
      position: fixed;
      right: 1rem;
      bottom: 2rem;
      left: 1rem;
      padding: 0.25rem;
      border-top: 1px black solid;
      font-size: 0.8rem;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="label-logo">
    <img src="<?= $label_logo ?>" />
  </div>
  <div class="container">
    <h1 class="title"><?= __('Exhibit', Plugin::L10N); ?></h1>
      <div class="prices">
        <div class="prices__discount">
          <span>-<?= $discount_percentage ?>%<span>
        </div>
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
        <h2><?= $title ?></h2>
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
