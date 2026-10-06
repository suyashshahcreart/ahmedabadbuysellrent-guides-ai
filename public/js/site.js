(function () {
  "use strict";

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var menuBtn = document.querySelector("[data-menu]");
  var mobileNav = document.querySelector("[data-nav-mobile]");
  if (menuBtn && mobileNav) {
    menuBtn.addEventListener("click", function () {
      var open = mobileNav.classList.toggle("is-open");
      menuBtn.setAttribute("aria-expanded", open ? "true" : "false");
      menuBtn.textContent = open ? "Close" : "Menu";
    });
  }

  var header = document.querySelector(".site-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 12);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  var bar = document.querySelector("[data-progress]");
  if (bar) {
    var article = document.querySelector(".article-page");
    window.addEventListener(
      "scroll",
      function () {
        if (!article) return;
        var rect = article.getBoundingClientRect();
        var total = article.offsetHeight - window.innerHeight;
        var scrolled = Math.min(1, Math.max(0, -rect.top / (total || 1)));
        bar.style.transform = "scaleX(" + scrolled + ")";
      },
      { passive: true }
    );
  }

  // Scroll-spy: highlight "On this page" TOC item for the section in view
  (function initTocScrollSpy() {
    var toc = document.querySelector(".toc-desktop .toc, .toc[data-toc]");
    if (!toc) return;
    var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
    if (!links.length) return;

    var sections = links
      .map(function (link) {
        var id = decodeURIComponent((link.getAttribute("href") || "").slice(1));
        if (!id) return null;
        var el = document.getElementById(id);
        return el ? { id: id, el: el, link: link } : null;
      })
      .filter(Boolean);

    if (!sections.length) return;

    var activeId = null;

    function setActive(id) {
      if (activeId === id) return;
      activeId = id;
      sections.forEach(function (s) {
        var on = s.id === id;
        s.link.classList.toggle("is-active", on);
        if (s.link.parentElement) {
          s.link.parentElement.classList.toggle("is-active", on);
        }
      });
    }

    function updateActive() {
      var marker = 120; // sticky header + a bit of breathing room
      var current = sections[0] ? sections[0].id : null;
      for (var i = 0; i < sections.length; i++) {
        var top = sections[i].el.getBoundingClientRect().top;
        if (top <= marker) {
          current = sections[i].id;
        } else {
          break;
        }
      }
      // Near page bottom: force last section active
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 40) {
        current = sections[sections.length - 1].id;
      }
      if (current) setActive(current);
    }

    var ticking = false;
    window.addEventListener(
      "scroll",
      function () {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(function () {
          updateActive();
          ticking = false;
        });
      },
      { passive: true }
    );
    window.addEventListener("resize", updateActive, { passive: true });
    updateActive();

    links.forEach(function (link) {
      link.addEventListener("click", function () {
        var id = decodeURIComponent((link.getAttribute("href") || "").slice(1));
        if (id) setActive(id);
      });
    });
  })();

  if (!reducedMotion) {
    var heroItems = document.querySelectorAll(".hero-content > *, .hero-visual > *, .hero-stat-bar > *");
    heroItems.forEach(function (el, i) {
      el.classList.add("hero-animate");
      el.style.setProperty("--hero-delay", i * 55 + "ms");
    });

    var heroVisual = document.querySelector(".hero-visual");
    if (heroVisual) {
      heroVisual.classList.add("reveal", "scale-in");
    }

    [
      ".section > .container > h2",
      ".section > .container > .section-intro",
      ".section-head",
      ".category-tile",
      ".guide-card",
      ".cta-strip",
      ".locality-card",
      ".insight-card",
      ".newsletter-box",
      ".trust-strip",
    ].forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        if (!el.classList.contains("reveal") && !el.classList.contains("hero-animate")) {
          el.classList.add("reveal");
        }
      });
    });

    ["category-grid", "card-grid", "locality-grid", "insight-grid"].forEach(function (gridClass) {
      document.querySelectorAll("." + gridClass).forEach(function (grid) {
        grid.querySelectorAll(".reveal").forEach(function (child, i) {
          child.style.setProperty("--reveal-delay", i * 55 + "ms");
        });
      });
    });

    if ("IntersectionObserver" in window) {
      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
              observer.unobserve(entry.target);
            }
          });
        },
        { rootMargin: "0px 0px -6% 0px", threshold: 0.08 }
      );
      document.querySelectorAll(".reveal").forEach(function (el) {
        observer.observe(el);
      });
    } else {
      document.querySelectorAll(".reveal").forEach(function (el) {
        el.classList.add("is-visible");
      });
    }
  } else {
    document.querySelectorAll(".reveal").forEach(function (el) {
      el.classList.add("is-visible");
    });
  }

  var filterRoot = document.querySelector("[data-filter-root]");
  if (filterRoot) {
    var buttons = filterRoot.querySelectorAll("[data-filters] button");
    var items = filterRoot.querySelectorAll("[data-item]");
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var group = btn.getAttribute("data-group");
        buttons.forEach(function (b) {
          b.classList.toggle("is-on", b === btn);
        });
        items.forEach(function (item) {
          var show = group === "all" || item.getAttribute("data-item") === group;
          item.setAttribute("data-hidden", show ? "false" : "true");
        });
      });
    });
  }

  var share = document.querySelector("[data-share]");
  if (share) {
    var url = location.href;
    var title = document.title;
    var wa = share.querySelector("[data-share-wa]");
    var x = share.querySelector("[data-share-x]");
    if (wa) wa.href = "https://wa.me/?text=" + encodeURIComponent(title + " " + url);
    if (x) x.href = "https://twitter.com/intent/tweet?text=" + encodeURIComponent(title) + "&url=" + encodeURIComponent(url);
    var copy = share.querySelector("[data-copy-link]");
    if (copy) {
      copy.addEventListener("click", function () {
        navigator.clipboard.writeText(url).then(function () {
          copy.textContent = "Copied";
        });
      });
    }
  }

  var news = document.querySelector("[data-newsletter]");
  if (news) {
    news.addEventListener("submit", function (e) {
      e.preventDefault();
      var note = document.querySelector("[data-newsletter-ok]");
      if (note) note.hidden = false;
      news.hidden = true;
    });
  }

  var searchInput = document.getElementById("pagefind-input");
  var resultsEl = document.querySelector("[data-search-results]");
  if (searchInput && resultsEl) {
    var params = new URLSearchParams(location.search);
    var q = params.get("q") || "";
    searchInput.value = q;
    var status = document.querySelector("[data-search-status]");
    var empty = document.querySelector("[data-search-empty]");
    var skeleton = document.querySelector("[data-search-skeleton]");
    var activeFilter = "";

    function render(hits) {
      resultsEl.innerHTML = "";
      var filtered = hits.filter(function (hit) {
        if (!activeFilter) return true;
        return (hit.meta && hit.meta.category === activeFilter) || (hit.url || "").indexOf("/" + activeFilter + "/") !== -1;
      });
      if (status) status.textContent = q ? filtered.length + " result" + (filtered.length === 1 ? "" : "s") + " for “" + q + "”" : "";
      if (!filtered.length) {
        if (empty) empty.hidden = false;
        return;
      }
      if (empty) empty.hidden = true;
      filtered.forEach(function (hit) {
        var a = document.createElement("a");
        a.className = "guide-card";
        a.href = hit.url;
        a.innerHTML =
          '<div class="body"><p class="tag">Guide</p><h3>' +
          (hit.meta && hit.meta.title ? hit.meta.title : hit.url) +
          "</h3><p>" +
          (hit.excerpt || "") +
          "</p></div>";
        resultsEl.appendChild(a);
      });
    }

    async function run() {
      if (!q) {
        if (status) status.textContent = "Enter a search term to find guides.";
        return;
      }
      if (skeleton) skeleton.hidden = false;
      try {
        var pagefind = await import("/pagefind/pagefind.js");
        await pagefind.init();
        var search = await pagefind.search(q);
        var data = await Promise.all(search.results.slice(0, 24).map(function (r) { return r.data(); }));
        if (skeleton) skeleton.hidden = true;
        render(data);
      } catch (err) {
        if (skeleton) skeleton.hidden = true;
        if (empty) empty.hidden = false;
        if (status) status.textContent = "Search is unavailable right now.";
      }
    }

    document.querySelectorAll("[data-search-filters] button").forEach(function (btn) {
      btn.addEventListener("click", function () {
        document.querySelectorAll("[data-search-filters] button").forEach(function (b) {
          b.classList.toggle("is-on", b === btn);
        });
        activeFilter = btn.getAttribute("data-filter") || "";
        run();
      });
    });

    run();
  }

})();
