(function ($) {
  "use strict";

  // --- Media uploader for prize image ---
  $("#upload-prize-image").on("click", function (e) {
    e.preventDefault();
    var frame = wp.media({
      title: "Seleccionar imagen del premio",
      multiple: false,
      library: { type: "image" },
    });

    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      $("#prize_image").val(attachment.url);
      $("#prize-image-preview").html(
        '<img src="' +
          attachment.url +
          '" style="max-width:200px;display:block;margin-bottom:10px;">',
      );
      $("#remove-prize-image").show();
    });

    frame.open();
  });

  $("#remove-prize-image").on("click", function (e) {
    e.preventDefault();
    $("#prize_image").val("");
    $("#prize-image-preview").html("");
    $(this).hide();
  });

  // --- Draw winner ---
  $("#draw-winner-btn").on("click", function () {
    if (
      !confirm(
        "¿Estás seguro de realizar el sorteo? Esta acción no se puede deshacer.",
      )
    ) {
      return;
    }

    var btn = $(this);
    var raffleId = btn.data("raffle-id");
    btn.prop("disabled", true).text("Sorteando...");

    $.post(
      raffleAdmin.ajax_url,
      {
        action: "raffle_draw",
        raffle_id: raffleId,
        nonce: raffleAdmin.draw_nonce,
      },
      function (response) {
        if (response.success) {
          btn.hide();
          $("#draw-result")
            .show()
            .html(
              "<h3>🏆 ¡Ganador seleccionado!</h3>" +
                "<p><strong>Nombre:</strong> " +
                response.data.buyer_name +
                "</p>" +
                "<p><strong>Boleto:</strong> #" +
                response.data.ticket_number +
                "</p>" +
                "<p><strong>Email:</strong> " +
                response.data.buyer_email +
                "</p>",
            );
        } else {
          alert(response.data.message);
          btn.prop("disabled", false).html("🎲 Seleccionar Ganador");
        }
      },
    ).fail(function () {
      alert("Error de conexión. Inténtalo de nuevo.");
      btn.prop("disabled", false).html("🎲 Seleccionar Ganador");
    });
  });

  // --- Auto-fix duplicates toggle ---
  $("#raffle-auto-fix-toggle").on("change", function () {
    var enabled = $(this).is(":checked") ? "1" : "0";
    $.post(raffleAdmin.ajax_url, {
      action: "raffle_toggle_auto_fix",
      nonce: raffleAdmin.draw_nonce,
      enabled: enabled,
    });
  });

  // --- Check duplicates ---
  $("#check-duplicates-btn").on("click", function () {
    var btn = $(this);
    var raffleId = btn.data("raffle-id");
    btn.prop("disabled", true).text("Comprobando...");

    $.post(
      raffleAdmin.ajax_url,
      {
        action: "raffle_check_duplicates",
        raffle_id: raffleId,
        nonce: raffleAdmin.draw_nonce,
      },
      function (response) {
        btn.prop("disabled", false).text("🔎 Comprobar Duplicados");
        if (response.success) {
          if (response.data.count === 0) {
            $("#duplicates-result").html(
              '<div class="raffle-duplicates-ok">✅ No se encontraron boletos duplicados.</div>',
            );
            $("#fix-duplicates-btn").hide();
          } else {
            var details = response.data.details
              .map(function (d) {
                return (
                  "Boleto #" + d.ticket_number + " (" + d.copies + " copias)"
                );
              })
              .join(", ");
            $("#duplicates-result").html(
              '<div class="raffle-duplicates-warn">⚠️ Se encontraron <strong>' +
                response.data.count +
                "</strong> boletos duplicados: " +
                details +
                "</div>",
            );
            $("#fix-duplicates-btn").show();
          }
        } else {
          alert(response.data.message);
        }
      },
    ).fail(function () {
      btn.prop("disabled", false).text("🔎 Comprobar Duplicados");
      alert("Error de conexión.");
    });
  });

  // --- Fix duplicates ---
  $("#fix-duplicates-btn").on("click", function () {
    var btn = $(this);
    var raffleId = btn.data("raffle-id");
    btn.prop("disabled", true).text("Corrigiendo...");

    $.post(
      raffleAdmin.ajax_url,
      {
        action: "raffle_fix_duplicates",
        raffle_id: raffleId,
        nonce: raffleAdmin.draw_nonce,
      },
      function (response) {
        btn.prop("disabled", false).text("🔧 Corregir Duplicados");
        if (response.success) {
          btn.hide();
          $("#duplicates-result").html(
            '<div class="raffle-duplicates-fixed">✅ ' +
              response.data.message +
              "</div>",
          );
        } else {
          alert(response.data.message);
        }
      },
    ).fail(function () {
      btn.prop("disabled", false).text("🔧 Corregir Duplicados");
      alert("Error de conexión.");
    });
  });
})(jQuery);
