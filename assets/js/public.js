(function ($) {
  "use strict";

  var modal = $("#raffle-modal");
  var confirmation = $("#raffle-confirmation");

  // --- Open purchase modal ---
  $(".raffle-buy-btn").on("click", function (e) {
    e.preventDefault();
    var qty = $(this).data("quantity");
    var price = $(this)
      .closest(".raffle-package-card")
      .find(".raffle-package-price")
      .text();

    $("#raffle-quantity").val(qty);
    $(".raffle-modal-summary").text(qty + " boletos — " + price);

    // Reset form and show modal
    $("#raffle-purchase-form")[0].reset();
    $("#raffle-purchase-form").show();
    modal.find(".raffle-loading").hide();
    modal.find(".raffle-error-msg").remove();
    modal.show();
  });

  // --- Close modals ---
  $(document).on("click", ".raffle-modal-close", function () {
    modal.hide();
    confirmation.hide();
  });

  modal.on("click", function (e) {
    if (e.target === this) modal.hide();
  });

  confirmation.on("click", function (e) {
    if (e.target === this) confirmation.hide();
  });

  // Escape key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") {
      modal.hide();
      confirmation.hide();
    }
  });

  /**
   * Show error in the modal.
   */
  function showModalError(msg) {
    var form = $("#raffle-purchase-form");
    form.show();
    form.find(".raffle-submit-btn").prop("disabled", false);
    modal.find(".raffle-loading").hide();
    modal.find(".raffle-error-msg").remove();
    form.before(
      '<div class="raffle-error-msg" style="background:#fee;color:#c00;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:14px;">' +
        msg +
        "</div>",
    );
  }

  /**
   * Show confirmation with ticket numbers.
   */
  function showConfirmation(tickets) {
    modal.hide();
    var ticketsHtml = tickets.join(" &nbsp;·&nbsp; ");
    $("#raffle-ticket-numbers").html(ticketsHtml);
    confirmation.show();
  }

  // --- Handle purchase form submission ---
  $("#raffle-purchase-form").on("submit", function (e) {
    e.preventDefault();

    var form = $(this);
    var submitBtn = form.find(".raffle-submit-btn");

    submitBtn.prop("disabled", true);
    form.hide();
    modal.find(".raffle-error-msg").remove();
    modal.find(".raffle-loading").show();

    // Check if WooCommerce is enabled
    if (
      typeof rafflePublic.wc_enabled !== "undefined" &&
      rafflePublic.wc_enabled === "1"
    ) {
      // --- WOOCOMMERCE FLOW ---
      handleWooCommercePurchase(form);
    } else {
      // --- DIRECT FLOW (no payment gateway) ---
      handleDirectPurchase(form);
    }
  });

  /**
   * Direct purchase flow (Wompi disabled).
   */
  function handleDirectPurchase(form) {
    $.post(
      rafflePublic.ajax_url,
      {
        action: "raffle_purchase",
        nonce: rafflePublic.nonce,
        raffle_id: form.find('[name="raffle_id"]').val(),
        quantity: form.find('[name="quantity"]').val(),
        buyer_name: form.find('[name="buyer_name"]').val(),
        buyer_email: form.find('[name="buyer_email"]').val(),
      },
      function (response) {
        if (response.success) {
          showConfirmation(response.data.tickets);
          form[0].reset();
          form.find(".raffle-submit-btn").prop("disabled", false);
        } else {
          showModalError(response.data.message);
        }
      },
    ).fail(function () {
      showModalError("Error de conexión. Inténtalo de nuevo.");
    });
  }

  /**
   * WooCommerce purchase flow:
   * 1. AJAX to create WC order → get pay URL
   * 2. Redirect to WooCommerce order-pay page
   */
  function handleWooCommercePurchase(form) {
    $.post(
      rafflePublic.ajax_url,
      {
        action: "raffle_create_order",
        nonce: rafflePublic.nonce,
        raffle_id: form.find('[name="raffle_id"]').val(),
        quantity: form.find('[name="quantity"]').val(),
        buyer_name: form.find('[name="buyer_name"]').val(),
        buyer_email: form.find('[name="buyer_email"]').val(),
      },
      function (response) {
        if (response.success && response.data.pay_url) {
          // Redirect to WooCommerce payment page
          window.location.href = response.data.pay_url;
        } else {
          showModalError(response.data.message || "Error al crear el pedido.");
        }
      },
    ).fail(function () {
      showModalError("Error de conexión. Inténtalo de nuevo.");
    });
  }

  // --- Reload page on confirmation close (to refresh progress bar) ---
  confirmation.on("click", ".raffle-modal-close", function () {
    location.reload();
  });

  // --- Countdown timer ---
  var countdownEl = document.getElementById("raffle-countdown");
  if (countdownEl) {
    var drawDate = new Date(
      countdownEl.getAttribute("data-draw-date"),
    ).getTime();
    var expiredEl = document.getElementById("raffle-countdown-expired");

    function updateCountdown() {
      var now = Date.now();
      var diff = drawDate - now;

      if (diff <= 0) {
        countdownEl.style.display = "none";
        if (expiredEl) expiredEl.style.display = "block";
        return;
      }

      var days = Math.floor(diff / 86400000);
      var hours = Math.floor((diff % 86400000) / 3600000);
      var minutes = Math.floor((diff % 3600000) / 60000);
      var seconds = Math.floor((diff % 60000) / 1000);

      var dEl = document.getElementById("cd-days");
      var hEl = document.getElementById("cd-hours");
      var mEl = document.getElementById("cd-minutes");
      var sEl = document.getElementById("cd-seconds");

      if (dEl) dEl.textContent = days;
      if (hEl) hEl.textContent = hours < 10 ? "0" + hours : hours;
      if (mEl) mEl.textContent = minutes < 10 ? "0" + minutes : minutes;
      if (sEl) sEl.textContent = seconds < 10 ? "0" + seconds : seconds;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
  }
})(jQuery);
