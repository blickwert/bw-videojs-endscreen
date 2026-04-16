(function () {
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

    if (data.debug) {
      wrap.classList.add("is-debug");
    }

    areas.forEach(function (area, index) {
      if (!area) return;

      var isLink = area.action === "link" && !!area.url;
      var isModal = area.action === "modal" && !!area.modal_content;

      if (!isLink && !isModal) return;

      var el = document.createElement(isLink ? "a" : "button");
      el.className = "bw-hotspot";

      if (area.class) {
        String(area.class)
          .split(/\s+/)
          .filter(Boolean)
          .forEach(function (cls) {
            el.classList.add(cls);
          });
      }

      if (isLink) {
        el.classList.add("bw-hotspot--link");
        el.setAttribute("href", area.url);

        if (area.target) el.setAttribute("target", area.target);
        if (area.rel) el.setAttribute("rel", area.rel);
      }

      if (isModal) {
        el.classList.add("bw-hotspot--modal");
        el.setAttribute("type", "button");
        el.dataset.modalContent = area.modal_content;
      }

      el.dataset.index = String(index);
      var w = Number(area.w) || 0;
      var h = Number(area.h) || 0;
      el.style.width  = w + "%";
      el.style.height = h + "%";
      el.style.left   = (Number(area.x) - w / 2) + "%";
      el.style.top    = (Number(area.y) - h / 2) + "%";

      if (area.label) {
        el.setAttribute("aria-label", area.label);
        el.setAttribute("title", area.label);

        var label = document.createElement("span");
        label.className = "bw-hotspot__label";
        label.textContent = area.label;
        el.appendChild(label);
      } else {
        el.setAttribute("aria-label", "Interaktiver Bereich");
      }

      wrap.appendChild(el);
    });

    player.el().appendChild(wrap);
    bindHotspots(player);

    return wrap;
  }

  function createModalContentEl(html) {
    var wrapper = document.createElement("div");
    wrapper.className = "bw-modal-content";
    wrapper.innerHTML = html;
    return wrapper;
  }

  function bindHotspots(player) {
    var wrap = player.el().querySelector(".bw-hotspots");
    if (!wrap) return;

    wrap.querySelectorAll(".bw-hotspot--modal").forEach(function (el) {
      if (el.dataset.bound === "1") return;
      el.dataset.bound = "1";

      el.addEventListener("click", function (e) {
        e.preventDefault();

        var content = el.dataset.modalContent || "";
        if (!content) return;

        player.pause();

        var contentEl = createModalContentEl(content);

        var modal = player.createModal(contentEl, {
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
    if (player && player.el()) {
      removeHotspots(player);
    }
  }

  function shouldShowOnPause(mode) {
    return mode === "pause" || mode === "pause-ended";
  }

  function shouldShowOnEnded(mode) {
    return mode === "ended" || mode === "pause-ended";
  }

  function requestFullscreenOnFirstPlay(player, data) {
    if (!data.fullscreenOnPlay) return;
    if (player.__bwFullscreenTriggered) return;

    player.__bwFullscreenTriggered = true;

    try {
      if (typeof player.requestFullscreen === "function" && !player.isFullscreen()) {
        var result = player.requestFullscreen();

        if (result && typeof result.catch === "function") {
          result.catch(function () {});
        }
      }
    } catch (e) {}
  }

  function initOneVideo(el) {
    if (!window.videojs || !el || !el.id) return;

    var player = window.videojs(el.id);

    var data = {
      hotspots: el.dataset.hotspots || "[]",
      hotspotsOn: el.dataset.hotspotsOn || "pause-ended",
      debug: el.dataset.debug === "1",
      fullscreenOnPlay: el.dataset.fullscreenOnPlay === "1"
    };

    if (data.debug) {
      player.ready(function () {
        showHotspots(player, data);
      });
    }

    player.on("pause", function () {
      var dur = player.duration ? player.duration() : 0;
      var ct = player.currentTime ? player.currentTime() : 0;
      var nearEnd = dur && (dur - ct) < 0.25;

      if (!data.debug && !player.ended() && !nearEnd && shouldShowOnPause(data.hotspotsOn)) {
        showHotspots(player, data);
      }
    });

    player.on("ended", function () {
      if (!data.debug && shouldShowOnEnded(data.hotspotsOn)) {
        showHotspots(player, data);
      }
    });

    player.on("play", function () {
      requestFullscreenOnFirstPlay(player, data);

      if (!data.debug) {
        hideHotspots(player);
      }
    });

    player.on("seeking", function () {
      if (!data.debug) {
        hideHotspots(player);
      }
    });

    player.on("dispose", function () {
      hideHotspots(player);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("video.bw-vjs").forEach(initOneVideo);
  });
})();