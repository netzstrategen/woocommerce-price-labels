/* global URL jQuery */

(function pageLoad($) {
  const $printLabelControls = $('.price-label-print');

  // Sets the price label print format.
  // Allows user to select the format of the price label to be printed.
  $('body').on('change', '.woocommerce-price-labels-format', (event) => {
    const $this = $(event.target);
    const $printButton = $this.siblings('.woocommerce-price-labels-button');
    const format = $this.val();
    const url = new URL($printButton.attr('href'));

    url.searchParams.set('format', format);
    $printButton.attr('href', url);
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
}(jQuery));
