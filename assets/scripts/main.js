/* global URL jQuery */

(function pageLoad($) {
  // set the default label selected from the beginning
  setPrintButtonLink();
  const $printLabelControls = $('.price-label-print');

  // Sets the price label print format and color.
  // Allows user to select the format and the color of the price label to be printed.
  $('body').on('change', '.woocommerce-price-labels-format, .woocommerce-price-labels-color', (event) => {
    setPrintButtonLink();
  });

  // Shows the price label controls when a variation is selected.
  $('.single_variation_wrap').on('show_variation', (event, variation) => {
    const $printLabelButton = $('.woocommerce-price-labels-button');
    const url = new URL($printLabelButton.attr('href'));

    // Prepares the button to print the price label of the selected variation.
    url.searchParams.set('post', variation.variation_id);
    $printLabelButton.attr('href', url);

    $printLabelControls.show();
  });

  // Hides the price label controls if no variation is selected.
  $('.single_variation_wrap').on('hide_variation', (event) => {
    $printLabelControls.hide();
  });

  /**
   * Set the link according to the selected label format.
   */
  function setPrintButtonLink() {
    const $select = $('.woocommerce-price-labels-format');
    const $printButton = $select.siblings('.woocommerce-price-labels-button');
    const format = $select.val();
    const color = $('.woocommerce-price-labels-color').val();
    const url = new URL($printButton.attr('href'));

    url.searchParams.set('format', format);
    url.searchParams.set('color', color);
    $printButton.attr('href', url);
  }
}(jQuery));
