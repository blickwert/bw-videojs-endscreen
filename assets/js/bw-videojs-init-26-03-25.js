(function () {
  function escapeHtml(str) {
    return String(str || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }


function buildCtaHTML(d) {
  var heading = escapeHtml(d.ctaHeading);
  var text = escapeHtml(d.ctaText);

  var buttons = [];
  try {
    buttons = JSON.parse(d.ctaButtons || "[]");
  } catch (e) {
    buttons = [];
  }

  var inner = '<div class="bw-cta">';
  if (heading) inner += "<h3>" + heading + "</h3>";
  if (text) inner += "<p>" + text + "</p>";

  if (buttons.length) {
    inner += '<div class="bw-cta__btns">';
    buttons.forEach(function (b) {
      if (!b || !b.url) return;

      var label = escapeHtml(b.label || "Mehr");
      var targetAttr = b.target ? ' target="' + escapeHtml(b.target) + '"' : "";
      var relAttr = b.rel ? ' rel="' + escapeHtml(b.rel) + '"' : "";

      inner +=
        '<a class="bw-cta__btn" href="' +
        b.url +
        '"' +
        targetAttr +
        relAttr +
        ">" +
        label +
        "</a>";
    });
    inner += "</div>";
  }

  inner += "</div>";
  return inner;
}




  function removeCta(player) {
    player.el().querySelectorAll(".bw-cta-wrap").forEach(function (n) {
      n.remove();
    });
  }

  function ensureCtaEl(player, data) {
    var el = player.el().querySelector(".bw-cta-wrap");
    if (!el) {
      el = document.createElement("div");
      el.className = "bw-cta-wrap bw-cta-align-" + (data.ctaAlign || "bottom-right");
      el.innerHTML = buildCtaHTML(data);
      player.el().appendChild(el);
    }
    return el;
  }

  function showCta(player, data) {
    removeCta(player); // <- verhindert Duplikate zuverlŠssig
    var el = ensureCtaEl(player, data);
    el.style.display = "flex";
  }

  function hideCta(player) {
    removeCta(player);
  }

  function initOneVideo(el) {
    if (!window.videojs) return;
    if (!el || !el.id) return;

    var player = window.videojs(el.id);
    
/*
    var player = window.videojs(el.id, {
    fluid: true,
    aspectRatio: "16:9"
    });
*/

var data = {
  ctaHeading: el.dataset.ctaHeading,
  ctaText: el.dataset.ctaText,
  ctaAlign: el.dataset.ctaAlign || "bottom-right",
  ctaButtons: el.dataset.ctaButtons || "[]",
};


    // --- Events ---
    player.on("pause", function () {
      // Manche Browser/Player feuern pause sehr spŠt/nahe am Ende => dann NICHT doppelt anzeigen
      var dur = player.duration ? player.duration() : 0;
      var ct = player.currentTime ? player.currentTime() : 0;
      var nearEnd = dur && (dur - ct) < 0.25;

      if (!player.ended() && !nearEnd) showCta(player, data);
    });

    player.on("ended", function () {
      showCta(player, data);
    });

    player.on("play", function () {
      hideCta(player);
    });

    player.on("seeking", function () {
      hideCta(player);
    });

    player.on("dispose", function () {
      hideCta(player);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("video.bw-vjs").forEach(initOneVideo);
  });
})();
