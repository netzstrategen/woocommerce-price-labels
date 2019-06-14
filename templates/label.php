<?php

namespace Netzstrategen\PriceLabels;

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    th {
      text-align: left;
    }
  </style>
  <title>Price label</title>
</head>
<body>
<table>
  <tr>
    <th>Price</th>
    <td><?= $price ?></td>
  </tr>
  <tr>
    <th>Regular Price</th>
    <td><?= $regular_price ?></td>
  </tr>
  <tr>
    <th>Sale Price</th>
    <td><?= $sale_price ?></td>
  </tr>
  <tr>
    <th>Brand</th>
    <td><?= $brand ?></td>
  </tr>
  <tr>
    <th>Title</th>
    <td><?= $title ?></td>
  </tr>
  <?php foreach ($attributes as $label => $values): ?>
    <tr>
      <th><?= $label ?></th>
      <td><?= implode(', ', $values) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<p>
    <img src="<?= $qr_code ?>"" />
</p>
</body>
</html>
