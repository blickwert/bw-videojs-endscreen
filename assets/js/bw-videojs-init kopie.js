(function () {
  function escapeHtml(str) {
    return String(str || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function parseJson(str, fallback) {
    try {
      return JSON.parse(str || "");
    } catch (e) {
      return fallback;
    }
  }

  function removeHotspots(player) {
    player.el().querySelectorAll(".bw-hotspots").forEach(function (node) {
      node.remove();
    });
  }

  function buildHotspots(player, data) {
    removeHotspots(player);

    var areas = parseJson(data.hotspots, []);
    if (!areas.length) return null;

    var wrap = document.createElement("div");
    wrap.className = "bw-hotspots";

    areas.forEach(function (area, index) {
      if (!area) return;

      var hasModal = !!area.modal;
      var hasUrl = !!area.url;

      if (!hasModal && !hasUrl) return;

      var el = document.createElement(hasUrl ? "a" : "button");

      el.className = "bw-hotspot";
      if (area.class) {
        el.className += " " + area.class;
      }

      el.setAttribute("data-index", String(index));
      el.style.left = (Number(area.x) || 0) + "%";
      el.style.top = (Number(area.y) || 0) + "%";
      el.style.width = (Number(area.w) || 0) + "%";
      el.style.height = (Number(area.h) || 0) + "%";

      if (area.label) {
        el.setAttribute("aria-label", area.label);
        el.setAttribute("title", area.label);
      } else {
        el.setAttribute("aria-label", "Interaktiver Bereich");
      }

      if (hasModal) {
        el.classList.add("bw-hotspot--modal");
        el.setAttribute("type", "button");
        el.dataset.modal = encodeURIComponent(area.modal);
      }

      if (hasUrl) {
        el.classList.add("bw-hotspot--link");
        el.setAttribute("href", area.url);

        if (area.target) {
          el.setAttribute("target", area.target);
        }
        if (area.rel) {
          el.setAttribute("rel", area.rel);
        }
      }

      if (area.label) {
        var label = document.createElement("span");
        label.className = "bw-hotspot__label";
        label.textContent = area.label;
        el.appendChild(label);
      }

      wrap.appendChild(el);
    });

    player.el().appendChild(wrap);
    bindHotspots(player);

    return wrap;
  }

  function bindHotspots(player) {
    var wrap = player.el().querySelector(".bw-hotspots");
    if (!wrap) return;

    wrap.querySelectorAll(".bw-hotspot--modal").forEach(function (el) {
      if (el.dataset.bound === "1") return;
      el.dataset.bound = "1";

      el.addEventListener("click", function (e) {
        e.preventDefault();

        var content = el.dataset.modal ? decodeURIComponent(el.dataset.modal) : "";
        if (!content) return;

        player.pause();

        var modal = player.createModal(content, {
          temporary: true,
          pauseOnOpen: false
        });

        modal.open();
      });
    });
  }

  function showHotspots(player, data) {
    buildHotspots(player, data);
  }

  function hideHotspots(player) {
    removeHotspots(player);
  }

  function shouldShowOnPause(mode) {
    return mode === "pause" || mode === "pause-ended";
  }

  function shouldShowOnEnded(mode) {
    return mode === "ended" || mode === "pause-ended";
  }

  function initOneVideo(el) {
    if (!window.videojs || !el || !el.id) return;

    var player = window.videojs(el.id);

    var data = {
      hotspots: el.dataset.hotspots || "[]",
      hotspotsOn: el.dataset.hotspotsOn || "pause-ended"
    };

    player.on("pause", function () {
      var dur = player.duration ? player.duration() : 0;
      var ct = player.currentTime ? player.currentTime() : 0;
      var nearEnd = dur && (dur - ct) < 0.25;

      if (!player.ended() && !nearEnd && shouldShowOnPause(data.hotspotsOn)) {
        showHotspots(player, data);
      }
    });

    player.on("ended", function () {
      if (shouldShowOnEnded(data.hotspotsOn)) {
        showHotspots(player, data);
      }
    });

    player.on("play", function () {
      hideHotspots(player);
    });

    player.on("seeking", function () {
      hideHotspots(player);
    });

    player.on("dispose", function () {
      hideHotspots(player);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("video.bw-vjs").forEach(initOneVideo);
  });
})();