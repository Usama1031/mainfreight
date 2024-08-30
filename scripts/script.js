jQuery(document).ready(function ($) {
  var addressFields = [
    "#billing_country",
    "#billing_state",
    "#billing_postcode",
    "#billing_suburb",
    "#billing_city",
    "#shipping_country",
    "#shipping_state",
    "#shipping_postcode",
    "#shipping_suburb",
    "#shipping_city",
  ];

  addressFields.forEach(function (field) {
    $(document).on("change", field, function () {
      setTimeout(() => {
        $("body").trigger("update_checkout");
      }, 3000);
    });
  });
});
