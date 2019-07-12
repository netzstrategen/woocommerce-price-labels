/* global URL jQuery */
(function pageLoad($) {
  const $formats = $('#woocommerce-price-labels-format');

  // Allows user to select the format of the price label to be printed.
  $formats.change(() => {
    const format = $formats.val();
    const url = new URL($('#woocommerce-price-labels-button').attr('href'));
    url.searchParams.set('format', format);
    $('#woocommerce-price-labels-button').attr('href', url);
  });
}(jQuery));
