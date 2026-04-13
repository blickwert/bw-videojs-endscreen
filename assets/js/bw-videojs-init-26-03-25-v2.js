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
        if (!b) return;

        var label = escapeHtml(b.label || "Mehr");

        if (b.modal) {
          inner +=
            '<button type="button" class="bw-cta__btn bw-cta__btn--modal" data-modal="' +
            encodeURIComponent(b.modal) +
            '">' +
            label +
            "</button>";
          return;
        }

        if (!b.url) return;

        var targetAttr = b.target ? ' target="' + escapeHtml(b.target) + '"' : "";
        var relAttr = b.rel ? ' rel="' + escapeHtml(b.rel) + '"' : "";

        inner +=
          '<a class="bw-cta__btn" href="' +
          escapeHtml(b.url) +
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

  function bindModalButtons(player) {
    var wrap = player.el().querySelector(".bw-cta-wrap");
    if (!wrap) return;

    wrap.querySelectorAll(".bw-cta__btn--modal").forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";

      btn.addEventListener("click", function (e) {
        e.preventDefault();

        var content = btn.dataset.modal
          ? decodeURIComponent(btn.dataset.modal)
          : "";

        if (!content) return;

        player.pause();



        var modal = player.createModal(content, {
          temporary: true,
          pauseOnOpen: false
        });




/*
        var ModalDialog = videojs.getComponent('ModalDialog');
        
        var modal = new ModalDialog(player, {
          content: content,
          temporary: true,
          pauseOnOpen: false
        });
        
        // WICHTIG: aus Player lösen
        player.removeChild(modal);
        
        // direkt ins <body> hängen
        document.body.appendChild(modal.el());
*/
/*
        var ModalDialog = videojs.getComponent('ModalDialog');
        
        var modal = new ModalDialog(player, {
          content: content,
          temporary: true,
          pauseOnOpen: false
        });
        
        // ❗ aus Player lösen
        player.removeChild(modal);
        
        // Wrapper holen
        var wrapper = player.el().closest('.bw-vjs-wrap');
        
        // Fallback (falls irgendwas schiefgeht)
        if (!wrapper) wrapper = player.el();
        
        // in Wrapper einfügen
        wrapper.appendChild(modal.el());
*/





        modal.open();
      });
    });
  }

  function showCta(player, data) {
    removeCta(player);
    var el = ensureCtaEl(player, data);
    el.style.display = "flex";
    bindModalButtons(player);
  }

  function hideCta(player) {
    removeCta(player);
  }

  function initOneVideo(el) {
    if (!window.videojs) return;
    if (!el || !el.id) return;

    var player = window.videojs(el.id);

    var data = {
      ctaHeading: el.dataset.ctaHeading,
      ctaText: el.dataset.ctaText,
      ctaAlign: el.dataset.ctaAlign || "bottom-right",
      ctaButtons: el.dataset.ctaButtons || "[]",
    };

    player.on("pause", function () {
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