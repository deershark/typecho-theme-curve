(function () {
  "use strict";

  var html = document.documentElement;
  var body = document.body;
  var app = document.getElementById("app");
  var key = "curve-typecho-";
  var toastTimer;

  function message(text, type, duration) {
    var box = document.querySelector("[data-message]");
    if (!box) return;
    var content = box.querySelector(".text");
    if (content) content.textContent = text;
    box.hidden = false;
    box.classList.remove("success", "warning", "error", "info");
    box.classList.add(type || "info");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () { box.hidden = true; }, duration || 2200);
  }

  function applyResolvedTheme(theme) {
    html.classList.remove("dark", "light");
    html.classList.add(theme);
    var themeBackground = document.querySelector("[data-background]");
    if (themeBackground) {
      themeBackground.classList.toggle("dark", theme === "dark");
      themeBackground.classList.toggle("light", theme !== "dark");
    }
    syncHomeTypeBarGapBackground();
  }

  var systemTheme = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
  var savedThemeMode = localStorage.getItem(key + "theme-mode");
  var legacyTheme = localStorage.getItem(key + "theme");
  var themeMode = ["auto", "dark", "light"].indexOf(savedThemeMode) !== -1
    ? savedThemeMode
    : (["dark", "light"].indexOf(legacyTheme) !== -1 ? legacyTheme : "auto");
  function themeName(mode) {
    return mode === "dark" ? "深色模式" : mode === "light" ? "浅色模式" : "跟随系统";
  }
  function resolvedTheme(mode) {
    return mode === "auto" && systemTheme ? (systemTheme.matches ? "dark" : "light") : (mode === "dark" ? "dark" : "light");
  }
  function syncThemeControls() {
    var themeToggleIcon = document.querySelector("[data-theme-icon]");
    if (themeToggleIcon) {
      themeToggleIcon.classList.remove("icon-auto", "icon-dark", "icon-light");
      themeToggleIcon.classList.add("icon-" + themeMode);
    }
    if (themeToggle) {
      themeToggle.title = "当前：" + themeName(themeMode) + "，点击切换";
      themeToggle.setAttribute("aria-label", "当前：" + themeName(themeMode));
    }
    Array.prototype.forEach.call(document.querySelectorAll("[data-setting-theme]"), function (item) {
      item.classList.toggle("choose", item.dataset.settingTheme === themeMode);
    });
  }
  function setThemeMode(mode, notify) {
    if (["auto", "dark", "light"].indexOf(mode) === -1) mode = "auto";
    themeMode = mode;
    localStorage.setItem(key + "theme-mode", themeMode);
    localStorage.setItem(key + "theme", resolvedTheme(themeMode));
    applyResolvedTheme(resolvedTheme(themeMode));
    syncThemeControls();
    if (notify) message("当前主题为" + themeName(themeMode));
  }
  var themeToggle = document.querySelector("[data-theme-toggle]");
  setThemeMode(themeMode, false);
  syncThemeControls();
  if (themeToggle) themeToggle.addEventListener("click", function () {
    if (typeof backgroundType !== "undefined" && backgroundType === "image") {
      message("图片壁纸模式下无法切换明暗主题");
      return;
    }
    var nextMode = themeMode === "auto" ? "dark" : (themeMode === "dark" ? "light" : "auto");
    setThemeMode(nextMode, true);
  });
  if (systemTheme) {
    var systemThemeChanged = function () {
      if (themeMode === "auto") {
        applyResolvedTheme(resolvedTheme(themeMode));
        syncThemeControls();
      }
    };
    if (systemTheme.addEventListener) systemTheme.addEventListener("change", systemThemeChanged);
    else if (systemTheme.addListener) systemTheme.addListener(systemThemeChanged);
  }

  var nav = document.querySelector("[data-main-nav]");
  var lastScroll = window.scrollY || 0;
  function scrollState() {
    var current = window.scrollY || 0;
    var max = document.documentElement.scrollHeight - window.innerHeight;
    if (nav) {
      nav.classList.toggle("top", current < 8);
      nav.classList.toggle("down", current > lastScroll && current > 80);
      nav.classList.toggle("up", current < lastScroll);
    }
    lastScroll = current;
    var top = document.querySelector(".to-top[data-scroll-top]");
    if (top) {
      top.classList.toggle("hidden", current < 8);
      var percentageValue = Math.round(max > 0 ? current / max * 100 : 0);
      var isNearBottom = percentageValue > 90;
      var percent = top.querySelector("[data-scroll-percent]");
      if (percent) {
        /* The original uses <Transition mode="out-in"> keyed only by
         * `percentage > 90`: changing 12 to 13 is immediate, while
         * switching between the number and “返回顶部” takes the full fade. */
        var nextMode = isNearBottom ? "long" : "number";
        var nextText = isNearBottom ? "返回顶部" : String(percentageValue);
        var currentMode = percent.dataset.scrollMode;
        /* Expanding can start with the text transition; when collapsing keep
         * the wide button until “返回顶部” has faded out, so it never wraps. */
        if (nextMode === "long") top.classList.add("long");
        if (percent._curveTextChangePendingMode && percent._curveTextChangePendingMode !== nextMode) {
          window.clearTimeout(percent._curveTextChangeTimer);
          percent._curveTextChangePendingMode = "";
          percent._curveTextChangePendingText = "";
          percent.classList.remove("scroll-text-changing");
        }
        if (!currentMode) {
          percent.dataset.scrollMode = nextMode;
          percent.textContent = nextText;
          top.classList.toggle("long", nextMode === "long");
        } else if (currentMode !== nextMode) {
          if (percent._curveTextChangePendingMode !== nextMode) {
            window.clearTimeout(percent._curveTextChangeTimer);
            percent._curveTextChangePendingMode = nextMode;
            percent._curveTextChangePendingText = nextText;
            percent.classList.remove("scroll-text-changing");
            percent.classList.add("scroll-text-changing");
            percent._curveTextChangeTimer = window.setTimeout(function () {
              if (percent._curveTextChangePendingMode === "number") top.classList.remove("long");
              percent.textContent = percent._curveTextChangePendingText;
              percent.dataset.scrollMode = percent._curveTextChangePendingMode;
              percent._curveTextChangePendingMode = "";
              percent._curveTextChangePendingText = "";
              window.requestAnimationFrame(function () { percent.classList.remove("scroll-text-changing"); });
            }, 300);
          } else if (nextMode === "number") {
            percent._curveTextChangePendingText = nextText;
          }
        } else if (nextMode === "number") {
          top.classList.remove("long");
          percent.textContent = nextText;
        }
      }
    }
    var bar = document.querySelector("[data-scroll-progress]");
    if (bar) bar.style.width = (max > 0 ? current / max * 100 : 0) + "%";
  }
  window.addEventListener("scroll", scrollState, { passive: true });
  scrollState();

  var homeTypeBar = document.querySelector(".home:not(.archive-page) .type-bar");
  var homeBackground = document.querySelector("[data-background]");
  var homeBackgroundGap = null;
  if (homeTypeBar && homeBackground && homeTypeBar.parentNode) {
    homeBackgroundGap = homeBackground.cloneNode(true);
    homeBackgroundGap.removeAttribute("data-background");
    homeBackgroundGap.classList.add("home-type-bar-gap");
    homeBackgroundGap.hidden = true;
    var gapCover = homeBackgroundGap.querySelector("[data-background-cover]");
    if (gapCover) {
      gapCover.removeAttribute("id");
      gapCover.removeAttribute("data-background-cover");
    }
    homeTypeBar.parentNode.insertBefore(homeBackgroundGap, homeTypeBar);
  }
  function syncHomeTypeBarGapBackground() {
    if (!homeBackground || !homeBackgroundGap) return;
    Array.prototype.forEach.call(["patterns", "image", "dark", "light", "is-blurred"], function (name) {
      homeBackgroundGap.classList.toggle(name, homeBackground.classList.contains(name));
    });
    var sourceCover = homeBackground.querySelector("[data-background-cover]");
    var gapCover = homeBackgroundGap.querySelector(".cover");
    if (sourceCover && gapCover) {
      gapCover.className = sourceCover.className;
      gapCover.hidden = sourceCover.hidden;
      if (sourceCover.getAttribute("src")) gapCover.setAttribute("src", sourceCover.getAttribute("src"));
      else gapCover.removeAttribute("src");
    }
  }
  var homeTypeBarMarker = null;
  if (homeTypeBar && homeTypeBar.parentNode) {
    homeTypeBarMarker = document.createElement("span");
    homeTypeBarMarker.className = "home-type-bar-marker";
    homeTypeBarMarker.setAttribute("aria-hidden", "true");
    homeTypeBar.parentNode.insertBefore(homeTypeBarMarker, homeTypeBar);
  }
  function syncHomeTypeBarFloat() {
    if (!homeTypeBar || !homeTypeBarMarker) return;
    if (!homeTypeBar.getClientRects().length) {
      homeTypeBar.classList.remove("is-stuck");
      if (homeBackgroundGap) homeBackgroundGap.hidden = true;
      return;
    }
    var stickyTop = parseFloat(window.getComputedStyle(homeTypeBar).top) || 0;
    var isStuck = homeTypeBarMarker.getBoundingClientRect().top <= stickyTop;
    homeTypeBar.classList.toggle("is-stuck", isStuck);
    if (homeBackgroundGap) {
      var contentRect = homeTypeBar.parentNode.getBoundingClientRect();
      var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
      var gapLeft = Math.max(0, Math.floor(contentRect.left));
      var gapRight = Math.min(viewportWidth, Math.ceil(contentRect.right));
      homeBackgroundGap.style.setProperty("--home-type-bar-gap-left", gapLeft + "px");
      homeBackgroundGap.style.setProperty("--home-type-bar-gap-right", Math.max(gapLeft, gapRight) + "px");
      var coverHeight = Math.ceil(homeTypeBar.getBoundingClientRect().height / 2);
      homeBackgroundGap.style.setProperty("--home-type-bar-cover-bottom", (stickyTop + coverHeight) + "px");
      homeBackgroundGap.hidden = !isStuck;
      if (isStuck) syncHomeTypeBarGapBackground();
    }
  }
  window.addEventListener("scroll", syncHomeTypeBarFloat, { passive: true });
  window.addEventListener("resize", syncHomeTypeBarFloat, { passive: true });
  syncHomeTypeBarFloat();

  Array.prototype.forEach.call(document.querySelectorAll("[data-scroll-top], [data-scroll-home]"), function (item) {
    item.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      var banner = document.getElementById("main-banner");
      window.scrollTo({ top: item.hasAttribute("data-scroll-home") && banner ? banner.offsetHeight : 0, behavior: "smooth" });
    });
  });

  /* The summary widget writes the supplied description one character at a time:
   * first wait 2.5–3.8 seconds, then use a random 30–150ms cadence. */
  Array.prototype.forEach.call(document.querySelectorAll("[data-fake-gpt]"), function (card) {
    var source = card.getAttribute("data-fake-gpt-summary") || "";
    var output = card.querySelector("[data-fake-gpt-text]");
    var cursor = card.querySelector("[data-fake-gpt-point]");
    var toggle = card.querySelector("[data-fake-gpt-toggle]");
    if (!source || !output || !cursor || !toggle) return;
    var typeTimer;
    var waitTimer;
    var index = 0;
    var loadingFakeGpt = true;
    var showingIntroduction = false;
    var introduction = card.getAttribute("data-fake-gpt-introduction") || "你好，我是 FakeGPT：名字听起来很懂，实际上只负责把作者认真写好的摘要一个字一个字端上来。没有联网、没有偷看正文，也没有在后台煮咖啡；这段内容由作者亲自审核，放心食用。";
    function setFakeGptLoading(loadingState) {
      loadingFakeGpt = loadingState;
      toggle.classList.toggle("fake-gpt-loading", loadingState);
      cursor.hidden = !loadingState;
      card.setAttribute("aria-busy", loadingState ? "true" : "false");
    }
    function typeWriter(text) {
      if (index < text.length) {
        output.textContent += text.charAt(index++);
        typeTimer = window.setTimeout(function () { typeWriter(text); }, Math.random() * 120 + 30);
        return;
      }
      setFakeGptLoading(false);
    }
    function beginTyping(text, wait) {
      window.clearTimeout(typeTimer);
      window.clearTimeout(waitTimer);
      index = 0;
      output.textContent = wait ? "加载中..." : "";
      setFakeGptLoading(true);
      waitTimer = window.setTimeout(function () {
        output.textContent = "";
        typeWriter(text);
      }, wait);
    }
    function toggleFakeGpt() {
      if (loadingFakeGpt) return;
      showingIntroduction = !showingIntroduction;
      beginTyping(showingIntroduction ? introduction : source, 0);
    }
    toggle.addEventListener("click", toggleFakeGpt);
    toggle.addEventListener("keydown", function (event) {
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      toggleFakeGpt();
    });
    beginTyping(source, Math.random() * 1300 + 2500);
  });

  var randomPost = document.querySelector("[data-random-post]");
  if (randomPost) randomPost.addEventListener("click", function () {
    var posts = document.querySelectorAll("[data-post-link]");
    if (posts.length) window.location.href = posts[Math.floor(Math.random() * posts.length)].dataset.postLink;
  });

  var footerFriendRoots = document.querySelectorAll("[data-footer-friends]");
  Array.prototype.forEach.call(footerFriendRoots, function (root) {
    var list = root.querySelector("[data-footer-friend-list]");
    var refresh = root.querySelector("[data-footer-friends-refresh]");
    var friends = [];
    try {
      friends = JSON.parse(root.getAttribute("data-friends") || "[]");
    } catch (error) {
      friends = [];
    }
    if (!list || !friends.length) return;

    var count = parseInt(root.getAttribute("data-friend-count") || "3", 10);
    count = Math.max(1, Math.min(count, friends.length));

    function randomFriends() {
      var shuffled = friends.slice();
      for (var index = shuffled.length - 1; index > 0; index--) {
        var swapIndex = Math.floor(Math.random() * (index + 1));
        var item = shuffled[index];
        shuffled[index] = shuffled[swapIndex];
        shuffled[swapIndex] = item;
      }
      return shuffled.slice(0, count);
    }

    function renderFriends(selected) {
      list.textContent = "";
      selected.forEach(function (friend) {
        var link = document.createElement("a");
        link.className = "link-text";
        link.href = friend.url || "#";
        link.target = "_blank";
        link.rel = "noopener";
        link.title = friend.desc || friend.name || "友情链接";
        link.textContent = friend.name || "未命名站点";
        list.appendChild(link);
      });
    }

    renderFriends(randomFriends());
    if (refresh) refresh.addEventListener("click", function () {
      renderFriends(randomFriends());
      refresh.blur();
    });
  });

  function samePath(a, b) {
    try {
      var left = new URL(a, window.location.href);
      var right = new URL(b, window.location.href);
      return left.pathname.replace(/\/$/, "") === right.pathname.replace(/\/$/, "") && left.search === right.search;
    } catch (error) {
      return a === b;
    }
  }

  function homeFilterUrl(link) {
    try {
      var target = new URL(link.getAttribute("href") || window.location.href, window.location.href);
      /* Typecho may emit a different host inside Docker. Keep the request same-origin. */
      return target.pathname + target.search;
    } catch (error) {
      return link.getAttribute("href") || window.location.pathname;
    }
  }

  function homeFilterRequestUrl(target) {
    try {
      var request = new URL(target, window.location.href);
      request.searchParams.set("curve_home_filter", "1");
      return request.pathname + request.search;
    } catch (error) {
      return target;
    }
  }

  function setHomeFilterChoice(home, target) {
    var links = home.querySelectorAll("[data-category-filter]");
    Array.prototype.forEach.call(links, function (item) {
      var isActive = samePath(homeFilterUrl(item), target);
      item.classList.toggle("choose", isActive);
    });
  }

  function scrollHomeListToStart(home, list) {
    if (!home || !list) return;
    var stage = list.closest(".post-list-stage") || list;
    var typeBar = home.querySelector(".type-bar");
    var nav = document.querySelector("[data-main-nav]");
    var navHeight = nav ? nav.getBoundingClientRect().height : 60;
    var topInset = navHeight + 16;
    if (typeBar && window.getComputedStyle(typeBar).position === "sticky") {
      var typeBarStyle = window.getComputedStyle(typeBar);
      var stickyTop = parseFloat(typeBarStyle.top) || navHeight;
      var typeBarGap = parseFloat(typeBarStyle.marginBottom) || 0;
      topInset = stickyTop + typeBar.getBoundingClientRect().height + typeBarGap;
    }
    var stageTop = stage.getBoundingClientRect().top + (window.scrollY || 0);
    window.scrollTo({ top: Math.max(0, stageTop - topInset), behavior: "smooth" });
  }

  function loadHomeFilter(home, target, clickedLink) {
    if (!home || home.classList.contains("is-category-loading")) return;
    var list = home.querySelector(".post-lists");
    var pagination = home.querySelector(".pagination");
    var localLoading = home.querySelector("[data-category-loading]");
    if (!list) return;
    var filterRoot = home.dataset.categoryFilter;
    if (!filterRoot) {
      var homeChoice = home.querySelector(".type-bar [data-category-filter].choose");
      filterRoot = homeChoice ? homeFilterUrl(homeChoice) : target;
    }
    if (clickedLink && clickedLink.hasAttribute("data-category-filter")) filterRoot = target;
    home.classList.add("is-category-loading");
    if (localLoading) {
      window.clearTimeout(localLoading._curveHideTimer);
      localLoading.hidden = false;
      localLoading.classList.remove("is-leaving");
      window.requestAnimationFrame(function () { localLoading.classList.add("is-visible"); });
    }
    fetch(homeFilterRequestUrl(target), { headers: { "X-Requested-With": "XMLHttpRequest" } })
      .then(function (response) {
        if (!response.ok) throw new Error("category request failed");
        return response.text();
      })
      .then(function (htmlText) {
        var documentView = new DOMParser().parseFromString(htmlText, "text/html");
        var nextList = documentView.querySelector(".post-lists");
        if (!nextList) throw new Error("category list not found");
        var renderedList = document.importNode(nextList, true);
        renderedList.classList.add("category-list-enter");
        list.replaceWith(renderedList);
        var nextPagination = documentView.querySelector(".pagination");
        if (pagination && nextPagination) pagination.replaceWith(document.importNode(nextPagination, true));
        bindFastJumps(home);
        setHomeFilterChoice(home, filterRoot);
        home.dataset.categoryFilter = filterRoot;
        if (clickedLink) clickedLink.blur();
        scrollHomeListToStart(home, renderedList);
      })
      .catch(function () {
        message("分类文章加载失败，请稍后重试");
      })
      .then(function () {
        home.classList.remove("is-category-loading");
        if (localLoading) {
          localLoading.classList.remove("is-visible");
          localLoading.classList.add("is-leaving");
          localLoading._curveHideTimer = window.setTimeout(function () {
            localLoading.hidden = true;
            localLoading.classList.remove("is-leaving");
          }, 280);
        }
      });
  }

  var relatedRandom = document.querySelector("[data-related-random]");
  if (relatedRandom) relatedRandom.addEventListener("click", function () {
    var posts = document.querySelectorAll(".related-post [data-post-link]");
    if (posts.length) window.location.href = posts[Math.floor(Math.random() * posts.length)].dataset.postLink;
  });

  function pageUrl(page, basePath) {
    var path = basePath || window.location.pathname;
    var pageStyle = /\/page\/\d+\/?$/.test(path);
    if (pageStyle) {
      path = path.replace(/\/page\/\d+\/?$/, "/");
      return path + (page > 1 ? "page/" + page + "/" : "") + window.location.search;
    }
    var taxonomyStyle = /\/(?:category|tag|author|date)\/[^/]+\/\d+\/?$/.test(path);
    if (taxonomyStyle) {
      path = path.replace(/\/\d+\/?$/, "/");
      return path + page + "/" + window.location.search;
    }
    path = path.replace(/\/$/, "") + "/";
    return path + (page > 1 ? "page/" + page + "/" : "") + window.location.search;
  }
  function bindFastJumps(root) {
    var fastJumps = (root || document).querySelectorAll("[data-fast-jump]");
    Array.prototype.forEach.call(fastJumps, function (jump) {
      if (jump.dataset.bound === "1") return;
      jump.dataset.bound = "1";
      var input = jump.querySelector("input");
      var submit = jump.querySelector("[data-fast-jump-submit]");
      var total = parseInt(jump.dataset.totalPages || "1", 10);
      var pageBase = "";
      var pageNav = jump.closest(".page-navigator");
      var sampleLink = pageNav && pageNav.querySelector("a[href]");
      if (sampleLink) {
        try { pageBase = new URL(sampleLink.href, window.location.href).pathname; } catch (error) { pageBase = ""; }
      }
      function validateJump() {
        var value = parseInt(input.value, 10);
        if (!Number.isFinite(value)) {
          input.value = "";
          if (submit) submit.classList.remove("click");
          return null;
        }
        value = Math.max(1, Math.min(total, value));
        input.value = value;
        if (submit) submit.classList.add("click");
        return value;
      }
      function fastJump() {
        var value = validateJump();
        jump.classList.remove("focus");
        if (!value) return;
        var home = jump.closest(".home:not(.archive-page)");
        if (home) loadHomeFilter(home, pageUrl(value, pageBase), jump);
        else window.location.href = pageUrl(value, pageBase);
      }
      if (input) {
        input.addEventListener("focus", function () { jump.classList.add("focus"); });
        input.addEventListener("input", validateJump);
        input.addEventListener("blur", fastJump);
        input.addEventListener("keydown", function (event) { if (event.key === "Enter") fastJump(); });
      }
      if (submit) submit.addEventListener("click", function (event) { event.stopPropagation(); fastJump(); });
    });
  }
  bindFastJumps(document);

  var mobile = document.querySelector("[data-mobile-menu]");
  function toggleMobile(show) { if (mobile) mobile.hidden = !show; }
  var mobileOpen = document.querySelector("[data-mobile-open]");
  if (mobileOpen) mobileOpen.addEventListener("click", function () { toggleMobile(true); });
  Array.prototype.forEach.call(document.querySelectorAll("[data-mobile-close]"), function (item) { item.addEventListener("click", function () { toggleMobile(false); }); });

  var search = document.querySelector("[data-search-modal]");
  function toggleSearch(show) { if (search) search.hidden = !show; }
  var searchOpen = document.querySelector("[data-search-open]");
  if (searchOpen) searchOpen.addEventListener("click", function () { toggleSearch(true); var input = search.querySelector("input"); if (input) input.focus(); });
  Array.prototype.forEach.call(document.querySelectorAll("[data-search-close]"), function (item) { item.addEventListener("click", function () { toggleSearch(false); }); });

  var control = document.querySelector("[data-control]");
  function toggleControl(show) {
    if (control) control.hidden = !show;
    body.style.overflowY = show ? "hidden" : "";
  }
  var controlOpen = document.querySelector("[data-control-open]");
  if (controlOpen) controlOpen.addEventListener("click", function () { toggleControl(true); });
  Array.prototype.forEach.call(document.querySelectorAll("[data-control-close]"), function (item) { item.addEventListener("click", function () { toggleControl(false); }); });
  var blurToggle = document.querySelector("[data-blur-toggle]");
  var blurEnabled = localStorage.getItem(key + "background-blur") === "1";
  function applyBlur(enabled, notify) {
    blurEnabled = !!enabled;
    if (app) app.classList.remove("blur");
    var backgroundLayer = document.querySelector("[data-background]");
    if (backgroundLayer) backgroundLayer.classList.toggle("is-blurred", blurEnabled);
    if (blurToggle) {
      blurToggle.classList.toggle("open", blurEnabled);
      blurToggle.setAttribute("aria-pressed", blurEnabled ? "true" : "false");
      blurToggle.title = blurEnabled ? "关闭背景模糊" : "开启背景模糊";
    }
    localStorage.setItem(key + "background-blur", blurEnabled ? "1" : "0");
    if (notify) message(blurEnabled ? "已开启背景模糊" : "已关闭背景模糊");
  }
  applyBlur(blurEnabled, false);
  if (blurToggle) blurToggle.addEventListener("click", function () { applyBlur(!blurEnabled, true); });

  var background = document.querySelector("[data-background]");
  var backgroundCover = background && background.querySelector("[data-background-cover]");
  var defaultBackground = html.getAttribute("data-curve-default-background");
  if (["close", "patterns", "image"].indexOf(defaultBackground) === -1) defaultBackground = "patterns";
  var defaultBackgroundUrl = html.getAttribute("data-curve-default-background-url") || "";
  var backgroundUrlConfigurable = html.getAttribute("data-curve-background-url-configurable") !== "0";
  var savedBackground = localStorage.getItem(key + "background");
  var backgroundType = ["close", "patterns", "image"].indexOf(savedBackground) !== -1 ? savedBackground : defaultBackground;
  var savedBackgroundUrl = localStorage.getItem(key + "background-url") || "";
  var backgroundUrl = backgroundUrlConfigurable ? savedBackgroundUrl : defaultBackgroundUrl;
  var infoPosition = localStorage.getItem(key + "info-position") || "normal";
  function applyBackground(type, url) {
    backgroundType = type;
    backgroundUrl = url || backgroundUrl;
    if (type === "image" && themeMode !== "dark") setThemeMode("dark", false);
    html.classList.toggle("image", type === "image");
    if (background) {
      background.classList.toggle("patterns", type === "patterns");
      background.classList.toggle("image", type === "image");
      background.classList.toggle("dark", html.classList.contains("dark"));
      background.classList.toggle("light", !html.classList.contains("dark"));
      background.hidden = type === "close";
      syncHomeTypeBarGapBackground();
    }
    if (backgroundCover) {
      backgroundCover.hidden = type !== "image" || !backgroundUrl;
      backgroundCover.classList.remove("loaded");
      if (type === "image" && backgroundUrl) {
        backgroundCover.onload = function () {
          backgroundCover.classList.add("loaded");
          syncHomeTypeBarGapBackground();
        };
        backgroundCover.onerror = function () {
          backgroundCover.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' version='1.1' width='100%25' height='100%25'%3E%3C/svg%3E";
          message("背景图片加载失败，请重新设置");
        };
        backgroundCover.src = backgroundUrl;
      } else {
        backgroundCover.removeAttribute("src");
      }
    }
    localStorage.setItem(key + "background", type);
    if (backgroundUrl) localStorage.setItem(key + "background-url", backgroundUrl);
  }
  applyBackground(backgroundType, backgroundUrl);

  var hello = document.querySelector("[data-hello]");
  if (hello) {
    var helloCard = document.querySelector("[data-hello-card]") || hello.parentNode;
    var helloClick = 0;
    var helloTimer;

    function getGreetings() {
      var hour = new Date().getHours();
      if (hour < 6) return "凌晨好，昨晚睡得怎么样？";
      if (hour < 9) return "早上好，今天也要开心哦！";
      if (hour < 12) return "上午好，今天也要加油哦！";
      if (hour < 14) return "中午好，吃饱了精神好！";
      if (hour < 17) return "下午好，继续加油！";
      if (hour < 19) return "傍晚好，是时候放松一下了！";
      if (hour < 22) return "晚上好，是时候休息了！";
      return "夜深了，明天继续加油！";
    }

    function greetArtalkUser() {
      try {
        var userData = window.localStorage.getItem("ArtalkUser");
        if (!userData) return false;
        var user = JSON.parse(userData);
        if (!user || !user.nick) return false;
        var greetings = ["很高兴见到你", "好久不见", "欢迎回来"];
        hello.textContent = greetings[Math.floor(Math.random() * greetings.length)] + "，" + user.nick;
        return true;
      } catch (error) {
        return false;
      }
    }

    function resetHello() {
      helloClick = 0;
      hello.classList.remove("is-counting", "is-milestone");
      if (greetArtalkUser()) return;
      hello.textContent = getGreetings();
    }

    function replayHelloEffect(effect) {
      hello.classList.remove("is-counting", "is-milestone");
      void hello.offsetWidth;
      hello.classList.add(effect);
    }

    hello.textContent = getGreetings();
    greetArtalkUser();
    hello.addEventListener("click", function () {
      window.clearTimeout(helloTimer);
      helloClick += 1;
      if (helloClick === 1) {
        hello.textContent = "点这里干什么？";
      } else if (helloClick === 2) {
        hello.textContent = "怎么还点？";
      } else if (helloClick === 3) {
        hello.textContent = "那你点吧！";
      } else if (helloClick === 100) {
        hello.textContent = "怎么还在点？？？";
        replayHelloEffect("is-milestone");
      } else if (helloClick > 3) {
        hello.textContent = "x " + (helloClick - 3);
        replayHelloEffect("is-counting");
      } else {
        hello.classList.remove("is-counting", "is-milestone");
      }
      helloTimer = window.setTimeout(resetHello, 3000);
    });
    if (helloCard) helloCard.addEventListener("mouseleave", resetHello);
    window.addEventListener("pagehide", function () { window.clearTimeout(helloTimer); }, { once: true });
  }

  var clocks = document.querySelectorAll("[data-clock]");
  if (clocks.length) {
    var clockTimer;
    function updateClock() {
      var now = new Date();
      var hour = now.getHours() % 12;
      var minute = now.getMinutes();
      var second = now.getSeconds();
      Array.prototype.forEach.call(clocks, function (clock) {
        var hourPointer = clock.querySelector(".pointer.hour");
        var minutePointer = clock.querySelector(".pointer.minute");
        var secondPointer = clock.querySelector(".pointer.second");
        if (hourPointer) hourPointer.style.transform = "rotate(" + hour / 12 * 360 + "deg)";
        if (minutePointer) minutePointer.style.transform = "rotate(" + minute / 60 * 360 + "deg)";
        if (secondPointer) secondPointer.style.transform = "rotate(" + second / 60 * 360 + "deg)";
      });
    }
    updateClock();
    clockTimer = window.setInterval(updateClock, 1000);
    window.addEventListener("pagehide", function () { window.clearInterval(clockTimer); }, { once: true });
  }

  var countdown = document.querySelector("[data-countdown]");
  if (countdown) {
    var target = new Date(countdown.dataset.date + "T00:00:00").getTime();
    function updateCountdown() {
      var now = new Date();
      var diff = target - now.getTime();
      var days = Math.floor(diff / 86400000);
      var value = countdown.querySelector("[data-countdown-value]");
      if (value) value.textContent = days + " 天";

      var year = now.getFullYear();
      var month = now.getMonth();
      var dayStart = new Date(year, month, now.getDate());
      var yearStart = new Date(year, 0, 1);
      var nextYearStart = new Date(year + 1, 0, 1);
      var values = {
        day: { total: 24, passed: now.getHours(), unit: "小时" },
        week: { total: 7, passed: now.getDay() === 0 ? 6 : now.getDay(), unit: "天" },
        month: { total: new Date(year, month + 1, 0).getDate(), passed: now.getDate() - 1, unit: "天" },
        year: { total: Math.round((nextYearStart - yearStart) / 86400000), passed: Math.floor((dayStart - yearStart) / 86400000), unit: "天" }
      };
      Array.prototype.forEach.call(countdown.querySelectorAll("[data-countdown-item]"), function (item) {
        var data = values[item.dataset.countdownItem];
        if (!data) return;
        var remaining = Math.max(0, data.total - data.passed);
        var percentage = data.passed / data.total * 100;
        var bar = item.querySelector("[data-countdown-bar]");
        var percent = item.querySelector("[data-countdown-percent]");
        var remainingValue = item.querySelector("[data-countdown-remaining]");
        var remainingUnit = item.querySelector(".remaining .tip:last-child");
        if (bar) { bar.style.width = percentage.toFixed(2) + "%"; bar.style.opacity = String(percentage / 100); }
        if (percent) { percent.textContent = percentage.toFixed(2) + "%"; percent.classList.toggle("many", percentage >= 46); }
        if (remainingValue) remainingValue.textContent = remaining;
        if (remainingUnit) remainingUnit.textContent = data.unit;
        var remainingNode = item.querySelector(".remaining");
        if (remainingNode) remainingNode.classList.toggle("many", percentage >= 60);
      });
    }
    updateCountdown();
    window.setInterval(updateCountdown, 1000);
  }

  var toc = document.querySelector("[data-toc]");
  var article = document.getElementById("page-content");
  if (toc && article) {
    var tocHeadings = article.querySelectorAll("h2, h3");
    var tocCard = toc.closest(".toc");
    if (!tocHeadings.length) {
      if (tocCard) tocCard.hidden = true;
    }
    Array.prototype.forEach.call(tocHeadings, function (heading, index) {
      if (!heading.id) heading.id = "heading-" + (index + 1);
      var item = document.createElement("span");
      item.id = "toc-" + heading.id;
      item._curveTocHeading = heading;
      item.className = "toc-item " + heading.tagName;
      item.textContent = heading.textContent.replace(/[\u200B]/g, "").trim();
      item.addEventListener("click", function () { scrollToAnchor(heading, true); });
      toc.appendChild(item);
    });
    var headings = tocHeadings;
    function activeToc() {
      var activeLine = anchorOffset() + 24;
      var current = headings.length ? headings[0] : null;
      Array.prototype.forEach.call(headings, function (heading) {
        if (heading.getBoundingClientRect().top <= activeLine) current = heading;
      });
      var activeItem = null;
      Array.prototype.forEach.call(toc.querySelectorAll(".toc-item"), function (item) {
        var isActive = !!current && item._curveTocHeading === current;
        item.classList.toggle("active", isActive);
        if (isActive) activeItem = item;
      });
      if (activeItem) {
        var listRect = toc.getBoundingClientRect();
        var itemRect = activeItem.getBoundingClientRect();
        var indicatorTop = itemRect.top - listRect.top + itemRect.height / 2 - 18;
        toc.style.setProperty("--height", Math.max(0, indicatorTop) + "px");
      }
    }
    window.addEventListener("scroll", activeToc, { passive: true });
    activeToc();
  }

  function setRewardModal(open) {
    var modal = document.querySelector("[data-reward-modal]");
    if (!modal) return;
    modal.hidden = !open;
    body.style.overflowY = open ? "hidden" : "";
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-reward-open]"), function (button) { button.addEventListener("click", function () { setRewardModal(true); }); });
  Array.prototype.forEach.call(document.querySelectorAll("[data-reward-close]"), function (button) { button.addEventListener("click", function () { setRewardModal(false); }); });

  var rightMenu = document.querySelector("[data-right-menu]");
  var rightEnabled = localStorage.getItem(key + "right-menu") !== "0";
  var rightToggle = document.querySelector("[data-right-toggle]");
  function applyRightMenu(enabled, notify) {
    rightEnabled = !!enabled;
    if (rightToggle) rightToggle.classList.toggle("open", rightEnabled);
    localStorage.setItem(key + "right-menu", rightEnabled ? "1" : "0");
    if (notify) message(rightEnabled ? "已开启右键菜单" : "已关闭右键菜单");
  }
  applyRightMenu(rightEnabled, false);
  if (rightToggle) rightToggle.addEventListener("click", function () { applyRightMenu(!rightEnabled, true); });
  if (rightMenu) {
    document.addEventListener("contextmenu", function (event) {
      if (!rightEnabled || event.target.closest("input,textarea,[contenteditable='true']")) return;
      event.preventDefault();
      rightMenu.hidden = false;
      var menu = rightMenu.querySelector(".menu-content");
      menu.style.position = "fixed";
      menu.style.left = Math.min(event.clientX, window.innerWidth - 300) + "px";
      menu.style.top = Math.min(event.clientY, window.innerHeight - 200) + "px";
    });
    document.addEventListener("click", function () { rightMenu.hidden = true; });
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-history]"), function (button) { button.addEventListener("click", function () { if (button.dataset.history === "reload") window.location.reload(); else window.history[button.dataset.history](); }); });
  var copyLink = document.querySelector("[data-copy-link]");
  if (copyLink) copyLink.addEventListener("click", function () { if (navigator.clipboard) navigator.clipboard.writeText(window.location.href).then(function () { message("本页地址已复制"); }); });

  var bannerText = document.querySelector("[data-home-subtitle]");
  if (bannerText && bannerText.getAttribute("data-home-subtitle-mode") === "hitokoto") {
    window.setTimeout(function () { fetch("https://v1.hitokoto.cn").then(function (response) { return response.json(); }).then(function (data) { if (data && data.hitokoto) bannerText.textContent = data.hitokoto; }).catch(function () { bannerText.textContent = "一言暂时不可用"; }); }, 2000);
  }

  var imageViewer = document.querySelector("[data-image-viewer]");
  var imageViewerImg = imageViewer && imageViewer.querySelector("[data-image-viewer-img]");
  if (imageViewer && imageViewerImg) {
    Array.prototype.forEach.call(document.querySelectorAll("#page-content img, .markdown-main-style img"), function (image) { image.addEventListener("click", function () { imageViewerImg.src = image.currentSrc || image.src; imageViewerImg.alt = image.alt || "文章图片"; imageViewer.hidden = false; body.style.overflow = "hidden"; }); });
    function closeImage() { imageViewer.hidden = true; imageViewerImg.src = ""; body.style.overflow = ""; }
    Array.prototype.forEach.call(imageViewer.querySelectorAll("[data-image-close]"), function (button) { button.addEventListener("click", closeImage); });
    imageViewer.addEventListener("click", function (event) { if (event.target === imageViewer) closeImage(); });
    document.addEventListener("keydown", function (event) { if (event.key === "Escape" && !imageViewer.hidden) closeImage(); });
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-copy-code]"), function (button) {
    button.addEventListener("click", function () {
      var code = button.parentElement && button.parentElement.querySelector("pre code");
      if (!code) return;
      var copy = function () { message("代码已复制"); button.classList.add("copied"); window.setTimeout(function () { button.classList.remove("copied"); }, 1400); };
      if (navigator.clipboard) navigator.clipboard.writeText(code.textContent).then(copy).catch(function () {});
    });
  });

  /* Curve's original vitepress-plugin-tabs rendered Vue buttons. Typecho
   * keeps the same class names, so only the small selection controller is
   * needed here. Shared keys synchronize tabs in separate tab groups. */
  var curveTabGroups = Array.prototype.slice.call(document.querySelectorAll("[data-curve-tabs]"));
  curveTabGroups.forEach(function (group) {
    var buttons = Array.prototype.slice.call(group.querySelectorAll("[data-curve-tab]"));
    var panels = Array.prototype.slice.call(group.querySelectorAll("[data-curve-tab-panel]"));
    if (!buttons.length || !panels.length) return;
    var selectTab = function (index, saveState) {
      index = Math.max(0, Math.min(index, buttons.length - 1));
      buttons.forEach(function (button, buttonIndex) {
        button.setAttribute("aria-selected", buttonIndex === index ? "true" : "false");
      });
      panels.forEach(function (panel, panelIndex) {
        panel.hidden = panelIndex !== index;
      });
      if (saveState) {
        var sharedKey = group.dataset.curveTabsKey;
        if (sharedKey) {
          curveTabGroups.forEach(function (otherGroup) {
            if (otherGroup !== group && otherGroup.dataset.curveTabsKey === sharedKey) {
              var otherButtons = otherGroup.querySelectorAll("[data-curve-tab]");
              if (otherButtons.length > index && otherGroup._curveSelectTab) otherGroup._curveSelectTab(index, false);
            }
          });
        }
      }
    };
    group._curveSelectTab = selectTab;
    buttons.forEach(function (button, index) {
      button.addEventListener("click", function () { selectTab(index, true); });
      button.addEventListener("keydown", function (event) {
        if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;
        event.preventDefault();
        var nextIndex = event.key === "ArrowRight" ? (index + 1) % buttons.length : (index - 1 + buttons.length) % buttons.length;
        buttons[nextIndex].focus();
        selectTab(nextIndex, true);
      });
    });
    selectTab(buttons.findIndex(function (button) { return button.getAttribute("aria-selected") === "true"; }), false);
  });

  /* Format absolute comment timestamps in the viewer's local timezone. The
   * server-rendered text remains a useful fallback before this script runs. */
  function formatCommentTimes(root) {
    var scope = root || document;
    var times = scope.querySelectorAll ? scope.querySelectorAll("time[data-comment-time]") : [];
    if (!times.length) return;

    var formatter = null;
    if (window.Intl && typeof window.Intl.DateTimeFormat === "function") {
      try {
        formatter = new window.Intl.DateTimeFormat(undefined, {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
          hour: "2-digit",
          minute: "2-digit"
        });
      } catch (error) {}
    }

    Array.prototype.forEach.call(times, function (time) {
      var timestamp = Number(time.getAttribute("data-comment-time"));
      if (!isFinite(timestamp) || timestamp <= 0) return;
      var date = new Date(timestamp * 1000);
      if (isNaN(date.getTime())) return;

      var formatted;
      if (formatter) {
        formatted = formatter.format(date);
      } else {
        var pad = function (value) { return value < 10 ? "0" + value : String(value); };
        formatted = date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate()) + " " + pad(date.getHours()) + ":" + pad(date.getMinutes());
      }
      time.title = formatted;
      if (time.getAttribute("data-comment-absolute") === "1") time.textContent = formatted;
    });
  }

  /* Submit Typecho comments in place. The endpoint still handles validation,
   * moderation and nested parents; we only replace the rendered comment area
   * with its redirected HTML response so the article itself never reloads. */
  function bindCommentForms() {
    Array.prototype.forEach.call(document.querySelectorAll(".comment-form"), function (form) {
      if (form.dataset.curveAjaxBound === "1") return;
      form.dataset.curveAjaxBound = "1";
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        var button = form.querySelector("button[type=submit]");
        var textField = form.querySelector("textarea[name=text]");
        var submittedText = textField ? textField.value.trim() : "";
        if (button) {
          button.disabled = true;
          button.dataset.originalText = button.textContent;
          button.textContent = "提交中…";
        }
        message("评论状态：提交中…", "info", 2200);

        fetch(form.action, {
          method: "POST",
          body: new FormData(form),
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" }
        }).then(function (response) {
          if (!response.ok) throw new Error("comment request failed");
          return response.text();
        }).then(function (htmlText) {
          var parsed = new DOMParser().parseFromString(htmlText, "text/html");
          var nextComment = parsed.querySelector("#main-comment");
          var currentComment = document.querySelector("#main-comment");
          if (!nextComment || !currentComment) throw new Error("comment response is invalid");

          var visible = false;
          var pending = false;
          var pendingLabel = "";
          Array.prototype.forEach.call(nextComment.querySelectorAll(".comment-item"), function (item) {
            var commentText = item.querySelector(".comment-item__text");
            if (!commentText || submittedText === "" || commentText.textContent.indexOf(submittedText) === -1) return;
            visible = true;
            var status = item.querySelector(".comment-item__status.is-pending");
            pending = !!status;
            pendingLabel = status ? status.textContent.trim() : "";
          });
          currentComment.replaceWith(nextComment);
          formatCommentTimes(nextComment);
          bindCommentForms();
          var nextForm = nextComment.querySelector(".comment-form");
          var restoredTextField = nextForm && nextForm.querySelector("textarea[name=text]");
          var rejected = restoredTextField && restoredTextField.value.trim() === submittedText && submittedText !== "";
          if (pending) {
            message("评论状态：" + (pendingLabel === "待审核" ? "待审核，审核通过后会显示。" : (pendingLabel || "待处理") + "。"), "warning", 5200);
          } else if (visible) {
            message("评论状态：已发布。", "success", 3200);
          } else if (rejected) {
            message("评论状态：提交失败，请检查昵称、邮箱和评论内容。", "error", 4800);
          } else {
            message("评论状态：待审核，审核通过后会显示。", "warning", 5200);
          }
        }).catch(function () {
          message("评论状态：提交失败，请检查输入内容或稍后重试。", "error", 4800);
        }).finally(function () {
          if (button && button.isConnected) {
            button.disabled = false;
            button.textContent = button.dataset.originalText || "提交评论";
          }
        });
      });
    });
  }
  formatCommentTimes(document);
  bindCommentForms();

  var settings = document.querySelector("[data-settings-modal]");
  var settingsOpen = document.querySelector("[data-settings-open]");
  var settingsHideTimer;
  function toggleSettings(show) {
    if (!settings) return;
    window.clearTimeout(settingsHideTimer);
    if (show) {
      settings.hidden = false;
      settings.classList.remove("fade-leave-active", "fade-leave-to");
      settings.classList.add("fade-enter-active", "fade-enter-from");
      body.style.overflowY = "hidden";
      window.requestAnimationFrame(function () { settings.classList.remove("fade-enter-from"); });
      return;
    }
    if (settings.hidden) return;
    settings.classList.remove("fade-enter-active", "fade-enter-from");
    settings.classList.add("fade-leave-active", "fade-leave-to");
    body.style.overflowY = "";
    settingsHideTimer = window.setTimeout(function () {
      settings.hidden = true;
      settings.classList.remove("fade-leave-active", "fade-leave-to");
    }, 300);
  }
  if (settingsOpen) settingsOpen.addEventListener("click", function () { toggleSettings(true); });
  Array.prototype.forEach.call(document.querySelectorAll("[data-settings-close]"), function (item) { item.addEventListener("click", function () { toggleSettings(false); }); });
  function syncSettingChoices() {
    Array.prototype.forEach.call(document.querySelectorAll("[data-setting-theme]"), function (item) { item.classList.toggle("choose", item.dataset.settingTheme === themeMode); });
    Array.prototype.forEach.call(document.querySelectorAll("[data-setting-font]"), function (item) { item.classList.toggle("choose", item.dataset.settingFont === fontFamily); });
    Array.prototype.forEach.call(document.querySelectorAll("[data-setting-background]"), function (item) { item.classList.toggle("choose", item.dataset.settingBackground === backgroundType); });
    Array.prototype.forEach.call(document.querySelectorAll("[data-setting-info]"), function (item) { item.classList.toggle("choose", item.dataset.settingInfo === infoPosition); });
    var backgroundRow = document.querySelector("[data-background-url-row]");
    if (backgroundRow) backgroundRow.hidden = !backgroundUrlConfigurable || backgroundType !== "image";
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-setting-theme]"), function (item) { item.addEventListener("click", function () { setThemeMode(item.dataset.settingTheme, true); syncSettingChoices(); }); });
  var defaultFont = html.getAttribute("data-curve-default-font");
  if (["hmos", "lxgw", "vivo", "xiaolai"].indexOf(defaultFont) === -1) defaultFont = "vivo";
  var savedFont = localStorage.getItem(key + "font");
  var fontFamily = ["hmos", "lxgw", "vivo", "xiaolai"].indexOf(savedFont) !== -1 ? savedFont : defaultFont;
  function applyFontFamily(font) {
    if (["hmos", "lxgw", "vivo", "xiaolai"].indexOf(font) === -1) font = defaultFont;
    fontFamily = font;
    html.classList.remove("hmos", "lxgw", "vivo", "xiaolai");
    html.classList.add(fontFamily);
    localStorage.setItem(key + "font", fontFamily);
    syncSettingChoices();
    window.requestAnimationFrame(syncHomeTypeBarFloat);
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-setting-font]"), function (item) { item.addEventListener("click", function () { applyFontFamily(item.dataset.settingFont); }); });
  applyFontFamily(savedFont);
  syncSettingChoices();
  Array.prototype.forEach.call(document.querySelectorAll("[data-setting-background]"), function (item) { item.addEventListener("click", function () { applyBackground(item.dataset.settingBackground, backgroundUrl); syncSettingChoices(); }); });
  var backgroundInput = document.querySelector("[data-background-url]");
  if (backgroundInput) {
    backgroundInput.value = backgroundUrl;
    backgroundInput.addEventListener("change", function () { backgroundUrl = backgroundInput.value.trim(); applyBackground("image", backgroundUrl); syncSettingChoices(); });
  }
  function applyInfoPosition() {
    Array.prototype.forEach.call(document.querySelectorAll("[data-next-post]"), function (item) {
      item.classList.toggle("fixed", infoPosition === "fixed");
      if (infoPosition !== "fixed") item.classList.remove("show");
    });
    syncNextPost();
  }
  applyInfoPosition();
  Array.prototype.forEach.call(document.querySelectorAll("[data-setting-info]"), function (item) { item.addEventListener("click", function () { infoPosition = item.dataset.settingInfo; localStorage.setItem(key + "info-position", infoPosition); applyInfoPosition(); syncSettingChoices(); }); });
  var fontSize = parseInt(localStorage.getItem(key + "font-size") || "16", 10);
  function applyFontSize() { fontSize = Math.max(14, Math.min(20, fontSize)); html.style.fontSize = fontSize + "px"; localStorage.setItem(key + "font-size", String(fontSize)); var value = document.querySelector("[data-font-value]"); if (value) value.textContent = fontSize; window.requestAnimationFrame(syncHomeTypeBarFloat); }
  applyFontSize();
  Array.prototype.forEach.call(document.querySelectorAll("[data-font-change]"), function (item) { item.addEventListener("click", function () { fontSize += Number(item.dataset.fontChange); applyFontSize(); }); });
  var defaultBanner = html.getAttribute("data-curve-default-banner");
  if (["half", "full"].indexOf(defaultBanner) === -1) defaultBanner = "half";
  var bannerMode = localStorage.getItem(key + "banner");
  if (["half", "full"].indexOf(bannerMode) === -1) bannerMode = defaultBanner;
  function syncBannerChoices() {
    Array.prototype.forEach.call(document.querySelectorAll("[data-banner]"), function (item) { item.classList.toggle("choose", item.dataset.banner === bannerMode); });
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-banner]"), function (item) { item.addEventListener("click", function () { var banner = document.getElementById("main-banner"); bannerMode = item.dataset.banner; if (banner) { banner.classList.remove("half", "full"); banner.classList.add(bannerMode); } localStorage.setItem(key + "banner", bannerMode); syncBannerChoices(); }); });
  var mainBanner = document.getElementById("main-banner");
  if (mainBanner) { mainBanner.classList.remove("half", "full"); mainBanner.classList.add(bannerMode); }
  var bannerArrow = document.querySelector("[data-scroll-home]");
  function syncBannerArrow() {
    if (bannerArrow && mainBanner) bannerArrow.hidden = !mainBanner.classList.contains("full");
  }
  Array.prototype.forEach.call(document.querySelectorAll("[data-banner]"), function (item) { item.addEventListener("click", syncBannerArrow); });
  syncBannerChoices();
  syncBannerArrow();

  var footer = document.getElementById("main-footer");
  var nextPost = document.querySelector("[data-next-post]");
  var nextPostTrigger = document.querySelector("[data-next-post-trigger]");
  var footerVisible = false;
  var settingsButton = document.querySelector("[data-settings-open]");
  var syncLeftMenuWithFooter = function () {};
  function syncNextPost() {
    if (!nextPost) return;
    var reachedArticleEnd = nextPostTrigger && nextPostTrigger.getBoundingClientRect().top <= window.innerHeight;
    nextPost.classList.toggle("show", infoPosition === "fixed" && reachedArticleEnd && !footerVisible);
  }
  if (footer && settingsButton) {
    /* Match the original theme: the floating menu follows the visibility of
     * the bottom copyright footer, not the preceding footer-link section. */
    syncLeftMenuWithFooter = function () {
      var rect = footer.getBoundingClientRect();
      footerVisible = rect.top < window.innerHeight && rect.bottom > 0;
      settingsButton.classList.toggle("footer-hidden", footerVisible);
      syncNextPost();
    };
    if ("IntersectionObserver" in window) {
      var footerObserver = new IntersectionObserver(function () { syncLeftMenuWithFooter(); });
      footerObserver.observe(footer);
    }
    window.addEventListener("scroll", syncLeftMenuWithFooter, { passive: true });
    window.addEventListener("resize", syncLeftMenuWithFooter, { passive: true });
    syncLeftMenuWithFooter();
  }
  if (nextPost && nextPostTrigger) {
    window.addEventListener("scroll", syncNextPost, { passive: true });
    window.addEventListener("resize", syncNextPost, { passive: true });
    syncNextPost();
  }

  document.addEventListener("keydown", function (event) { if (event.key === "Escape") { toggleMobile(false); toggleSearch(false); toggleControl(false); toggleSettings(false); } });
  var loading = document.querySelector("[data-loading]");
  var loadingTipTimer;
  var loadingHideTimer;
  var loadingStartedAt = 0;
  var initialHash = !!window.location.hash;
  function startLoading() {
    if (!loading) return;
    window.clearTimeout(loadingHideTimer);
    window.clearTimeout(loadingTipTimer);
    loading.hidden = false;
    loadingStartedAt = Date.now();
    loading.classList.remove("fade-leave-active", "fade-leave-to");
    loading.classList.add("fade-enter-active", "fade-enter-from");
    window.requestAnimationFrame(function () { loading.classList.remove("fade-enter-from"); });
    var loadingTip = loading.querySelector(".tip");
    if (loadingTip) loadingTip.classList.remove("show");
    if (app) { app.classList.add("is-loading"); app.setAttribute("aria-busy", "true"); }
    loadingTipTimer = window.setTimeout(function () {
      var tip = loading.querySelector(".tip");
      if (tip) tip.classList.add("show");
    }, 3000);
  }
  function finishLoading() {
    if (!loading) return;
    window.clearTimeout(loadingTipTimer);
    if (initialHash) {
      loading.hidden = true;
      loading.classList.remove("fade-enter-active", "fade-enter-from", "fade-leave-active", "fade-leave-to");
      if (app) { app.classList.remove("is-loading"); app.setAttribute("aria-busy", "false"); }
      syncLeftMenuWithFooter();
      window.requestAnimationFrame(syncHomeTypeBarFloat);
      scrollToHash(false);
      scheduleHashScroll(false);
      return;
    }
    loading.classList.remove("fade-enter-active", "fade-enter-from");
    loading.classList.add("fade-leave-active", "fade-leave-to");
    loadingHideTimer = window.setTimeout(function () {
      loading.hidden = true;
      loading.classList.remove("fade-leave-active", "fade-leave-to");
      if (app) { app.classList.remove("is-loading"); app.setAttribute("aria-busy", "false"); }
      syncLeftMenuWithFooter();
      window.requestAnimationFrame(syncHomeTypeBarFloat);
      if (window.location.hash) scheduleHashScroll(false);
    }, 300);
  }
  function isInternalNavigation(link, event) {
    if (!link || !link.href || link.target === "_blank" || link.hasAttribute("download") || event.defaultPrevented) return false;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.dataset.noLoading !== undefined || link.closest("[data-no-loading]")) return false;
    if (link.closest(".pagination")) return false;
    if (link.closest(".home:not(.archive-page)") && link.hasAttribute("data-category-filter")) return false;
    var url;
    try { url = new URL(link.href, window.location.href); } catch (error) { return false; }
    if (url.origin !== window.location.origin || url.protocol === "mailto:" || url.protocol === "tel:") return false;
    return url.pathname !== window.location.pathname || url.search !== window.location.search;
  }
  function findAnchorTarget(hash) {
    if (!hash) return null;
    var rawId = hash.charAt(0) === "#" ? hash.slice(1) : hash;
    var id;
    try { id = decodeURIComponent(rawId); } catch (error) { id = rawId; }
    if (!id) return null;
    var target = document.getElementById(id);
    if (target) return target;
    var namedTargets = document.getElementsByName(id);
    return namedTargets.length ? namedTargets[0] : null;
  }
  function sameDocumentHash(link) {
    if (!link || !link.href) return null;
    var url;
    try { url = new URL(link.href, window.location.href); } catch (error) { return null; }
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || url.search !== window.location.search || !url.hash) return null;
    return findAnchorTarget(url.hash);
  }
  function anchorOffset() {
    var nav = document.querySelector(".main-header .main-nav");
    var navRect = nav ? nav.getBoundingClientRect() : null;
    var navBottom = navRect ? navRect.bottom : 60;
    return Math.max(60, navBottom) + 16;
  }
  function scrollToAnchor(target, smooth) {
    if (!target || (app && app.classList.contains("is-loading"))) return false;
    var top = target.getBoundingClientRect().top + (window.pageYOffset || window.scrollY || 0) - anchorOffset();
    top = Math.max(0, top);
    /* `behavior: auto` still inherits html's smooth scrolling in browsers.
     * Use the two-argument form for the settling corrections so repeated
     * initial-hash fixes do not keep restarting the smooth animation. */
    if (smooth) window.scrollTo({ top: top, behavior: "smooth" });
    else window.scrollTo(0, top);
    return true;
  }
  function scrollToHash(smooth) {
    return scrollToAnchor(findAnchorTarget(window.location.hash), smooth);
  }
  function scheduleHashScroll(smooth) {
    var attempts = 0;
    var maxAttempts = smooth ? 1 : 10;
    function tryScroll() {
      scrollToHash(attempts === 0 ? smooth : false);
      attempts++;
      /* The browser may perform its own initial fragment scroll after the
       * loading layer is removed. Re-apply the offset for a short settling
       * window so that the fixed nav cannot cover the target. */
      if (attempts < maxAttempts) window.setTimeout(tryScroll, 80);
    }
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(tryScroll);
    });
  }
  function finishWhenReady() {
    var elapsed = Date.now() - loadingStartedAt;
    var wait = initialHash ? 0 : Math.max(0, 350 - elapsed);
    window.clearTimeout(loadingHideTimer);
    loadingHideTimer = window.setTimeout(finishLoading, wait);
  }
  startLoading();
  if (document.readyState === "complete") finishWhenReady();
  else window.addEventListener("load", finishWhenReady, { once: true });
  loading && loading.addEventListener("click", finishLoading);
  document.addEventListener("click", function (event) {
    var scrollTarget = event.target.closest && event.target.closest("[data-scroll-top], [data-scroll-home]");
    if (scrollTarget) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var banner = document.getElementById("main-banner");
      window.scrollTo({ top: scrollTarget.hasAttribute("data-scroll-home") && banner ? banner.offsetHeight : 0, behavior: "smooth" });
      var rightMenuTarget = document.querySelector("[data-right-menu]");
      if (rightMenuTarget) rightMenuTarget.hidden = true;
      return;
    }
    var typeBar = event.target.closest && event.target.closest(".home:not(.archive-page) .type-bar");
    if (typeBar && !event.target.closest("a[data-category-filter], a.more-type")) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }
    var articleLink = event.target.closest && event.target.closest("a.post-title[href]");
    if (articleLink) {
      event.preventDefault();
      event.stopImmediatePropagation();
      window.location.assign(articleLink.href);
      return;
    }
    /* Only the category tabs and the explicit category label in a post card
     * are AJAX filters. Do not infer a filter from arbitrary descendants. */
    var homeCategoryLink = event.target.closest && event.target.closest(".home:not(.archive-page) a.type-item[data-category-filter], .home:not(.archive-page) a.cat-name[data-category-filter]");
    var homePageLink = event.target.closest && event.target.closest(".home:not(.archive-page) .pagination a");
    if (homeCategoryLink || homePageLink) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var home = (homeCategoryLink || homePageLink).closest(".home:not(.archive-page)");
      var selectedTarget = homeFilterUrl(homeCategoryLink || homePageLink);
      if (homeCategoryLink) setHomeFilterChoice(home, selectedTarget);
      loadHomeFilter(home, selectedTarget, homeCategoryLink || homePageLink);
      return;
    }
    var link = event.target.closest && event.target.closest("a");
    /* Typecho handles reply/cancel links in their inline callbacks. */
    var typechoCommentLink = link && (
      link.id === "cancel-comment-reply-link" ||
      /TypechoComment\.(?:reply|cancelReply)\s*\(/.test(link.getAttribute("onclick") || "")
    );
    if (typechoCommentLink) {
      event.preventDefault();
      return;
    }
    if (link && (link.getAttribute("href") === "#" || link.getAttribute("href") === "")) {
      event.preventDefault();
      return;
    }
    var hashTarget = sameDocumentHash(link);
    if (hashTarget) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var hashUrl;
      try { hashUrl = new URL(link.href, window.location.href); } catch (error) { hashUrl = null; }
      if (hashUrl && window.history && window.history.pushState) {
        window.history.pushState(null, "", hashUrl.pathname + hashUrl.search + hashUrl.hash);
      } else if (hashUrl) {
        window.location.hash = hashUrl.hash;
      }
      scrollToHash(true);
      return;
    }
    if (isInternalNavigation(link, event)) startLoading();
  }, true);
  window.addEventListener("hashchange", function () { scheduleHashScroll(true); });
  window.addEventListener("popstate", function () { scheduleHashScroll(true); });
  window.addEventListener("beforeunload", function () { startLoading(); });
}());
