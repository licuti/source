$(document).ready(function () {

    // ─── CONFIG ─────────────────────────────────────────
    const CONFIG = {
        contentSelector:          "#content",  // CSS selector vùng nội dung chứa headings
        tocSelector:              "#toc",       // CSS selector wrapper TOC
        tocHeaderSelector:        ".toc-header",// CSS selector nút bấm toggle TOC
        tocContentSelector:       ".toc-content",// CSS selector vùng chứa danh sách ol

        headingSelectors:         "h2, h3, h4, h5, h6", // Các cấp heading đưa vào TOC
        minHeadingLevel:          2,            // Cấp heading cao nhất (h2=2, h3=3...)

        scrollOffset:             120,          // px — khoảng cách từ top khi click scroll đến heading
        fixedTocScrollThreshold:  300,          // px — scroll bao nhiêu thì TOC chuyển sang fixed
        highlightOffset:          300,           // px — offset nhỏ khi xác định heading đang active

        animationDuration:        280,          // ms — tốc độ slideDown/Up toàn bộ TOC
        nestedAnimationDuration:  200,          // ms — tốc độ slideDown/Up danh sách con

        startOpen:                false,        // true = TOC mở sẵn khi tải trang
        numberingEnabled:         true,         // true = hiển thị số thứ tự (1.2.3)
        numberingSeparator:       ".",          // ký tự ngăn cách số thứ tự ("." → 1.2.3)
        idPrefix:                 "toc-heading",// prefix cho id tự động gán vào heading
    };
    // ────────────────────────────────────────────────────

    const $content    = $(CONFIG.contentSelector);
    const $toc        = $(CONFIG.tocSelector);
    const $tocHeader  = $toc.find(CONFIG.tocHeaderSelector);
    const $tocContent = $toc.find(CONFIG.tocContentSelector);
    const $headings   = $content.find(CONFIG.headingSelectors);
    const $fab        = $("#toc-fab");
    const $panel      = $("#toc-panel");
    const $panelContent = $panel.find(".panel-toc-content");

    if ($headings.length === 0) { $toc.hide(); $fab.hide(); return; }

    let isTocOpen  = CONFIG.startOpen;
    let isPanelOpen = false;

    // ─── Build TOC list ──────────────────────────────────
    function buildTocList() {
        const $list     = $("<ol class='toc'></ol>");
        let currentLevel = CONFIG.minHeadingLevel;
        let $parentList  = $list;
        let counters     = [];

        $headings.each(function (index) {
            const $heading     = $(this);
            const headingLevel = parseInt(this.tagName[1]);
            const levelIndex   = headingLevel - CONFIG.minHeadingLevel;

            if (CONFIG.numberingEnabled) {
                while (counters.length <= levelIndex) counters.push(0);
                counters[levelIndex]++;
                counters = counters.slice(0, levelIndex + 1);
            }

            const numbering = CONFIG.numberingEnabled
                ? counters.join(CONFIG.numberingSeparator) + " "
                : "";

            if (!$heading.attr("id")) {
                $heading.attr("id", `${CONFIG.idPrefix}-${index}`);
            }

            if (headingLevel > currentLevel) {
                for (let i = currentLevel; i < headingLevel; i++) {
                    const $lastItem = $parentList.children("li").last();
                    if ($lastItem.length) {
                        const $nestedList = $("<ol></ol>").hide();
                        const $toggleBtn  = $("<button></button>")
                            .addClass("toggle")
                            .attr("aria-expanded", "false")
                            .html('<i class="fa-solid fa-chevron-right"></i>');
                        $lastItem.addClass("has-children").prepend($toggleBtn);
                        $lastItem.append($nestedList);
                        $parentList = $nestedList;
                    }
                }
            } else if (headingLevel < currentLevel) {
                for (let i = currentLevel; i > headingLevel; i--) {
                    const $ancestor = $parentList.closest("ol").parent().closest("ol");
                    if ($ancestor.length) $parentList = $ancestor;
                }
            }

            const $listItem = $("<li></li>");
            const $link     = $("<a></a>")
                .attr("href", `#${$heading.attr("id")}`)
                .text(`${numbering}${$heading.text()}`)
                .data("headingId", $heading.attr("id"));

            $listItem.append($link);
            $parentList.append($listItem);
            currentLevel = headingLevel;
        });

        return $list;
    }

    const $tocList   = buildTocList();
    const $panelList = buildTocList(); // bản sao độc lập cho panel

    $tocContent.append($tocList);
    $panelContent.append($panelList);

    if (CONFIG.startOpen) {
        $tocList.show();
        $tocList.find("ol").show();
        $tocList.find("li.has-children").addClass("open");
        $tocList.find(".toggle").attr("aria-expanded", "true");
        $toc.addClass("show");
        isTocOpen = true;
    }

    // ─── Highlight active link ───────────────────────────
    const $allLinks = $tocList.add($panelList).find("a");
    let activeId = "";

    function updateActiveLink(id) {
        $allLinks.each(function () {
            $(this).toggleClass("active", $(this).data("headingId") === id);
        });
    }

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        activeId = entry.target.id;
                        updateActiveLink(activeId);
                    }
                });
            },
            { rootMargin: `-${CONFIG.highlightOffset}px 0px -75% 0px`, threshold: 0 }
        );
        $headings.each(function () { observer.observe(this); });
    } else {
        let headingOffsets = computeOffsets();
        function computeOffsets() {
            return $headings.map(function () {
                return { id: $(this).attr("id"), offset: $(this).offset().top };
            }).get();
        }
        let ticking = false;
        $(window).on("scroll.tocHL", function () {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const scrollY = $(window).scrollTop() + CONFIG.highlightOffset;
                    let newId = "";
                    headingOffsets.forEach(({ id, offset }) => { if (scrollY >= offset) newId = id; });
                    if (newId !== activeId) { activeId = newId; updateActiveLink(activeId); }
                    ticking = false;
                });
                ticking = true;
            }
        });
        $(window).on("resize.tocHL", debounce(() => { headingOffsets = computeOffsets(); }, 200));
    }

    // ─── Scroll: ẩn/hiện FAB ────────────────────────────
    let fixedTicking = false;
    $(window).on("scroll.tocFixed", function () {
        if (!fixedTicking) {
            window.requestAnimationFrame(() => {
                const shouldFix = $(window).scrollTop() > CONFIG.fixedTocScrollThreshold;

                // Ẩn TOC in-flow (giữ nguyên height, không collapse layout)
                $toc.toggleClass("toc-inflow-hidden", shouldFix);

                // Hiện/ẩn FAB
                if (shouldFix && !$fab.hasClass("visible")) {
                    $fab.addClass("visible");
                } else if (!shouldFix && $fab.hasClass("visible")) {
                    $fab.removeClass("visible panel-open");
                    $panel.removeClass("open");
                    isPanelOpen = false;
                }

                fixedTicking = false;
            });
            fixedTicking = true;
        }
    });

    // ─── FAB toggle panel ────────────────────────────────
    $fab.on("click", function (e) {
        e.stopPropagation();
        isPanelOpen = !isPanelOpen;
        $panel.toggleClass("open", isPanelOpen);
        $fab.toggleClass("panel-open", isPanelOpen);
    });

    // Đóng panel khi click ra ngoài
    $(document).on("click", function (e) {
        if (isPanelOpen && !$(e.target).closest("#toc-panel, #toc-fab").length) {
            isPanelOpen = false;
            $panel.removeClass("open");
            $fab.removeClass("panel-open");
        }
    });

    // ─── Scroll đến heading (in-flow TOC) ───────────────
    $tocList.on("click", "a", function (e) {
        e.preventDefault();
        scrollToHeading($(this).data("headingId"));
    });

    // ─── Scroll đến heading (panel) ─────────────────────
    $panelList.on("click", "a", function (e) {
        e.preventDefault();
        scrollToHeading($(this).data("headingId"));
        isPanelOpen = false;
        $panel.removeClass("open");
        $fab.removeClass("panel-open");
    });

    function scrollToHeading(id) {
        const $target = $("#" + id);
        if ($target.length) {
            $("html, body").animate(
                { scrollTop: $target.offset().top - CONFIG.scrollOffset },
                CONFIG.animationDuration
            );
        }
    }

    // ─── Toggle toàn bộ TOC in-flow ─────────────────────
    $tocHeader.on("click", function () {
        isTocOpen = !isTocOpen;
        if (isTocOpen) {
            $tocList.slideDown(CONFIG.animationDuration);
            $toc.addClass("show");
        } else {
            $tocList.slideUp(CONFIG.animationDuration, function () {
                $tocList.find("ol").hide();
                $tocList.find("li.has-children").removeClass("open");
                $tocList.find(".toggle").attr("aria-expanded", "false");
            });
            $toc.removeClass("show");
        }
    });

    // ─── Toggle nested (in-flow) ─────────────────────────
    $tocContent.on("click", ".toggle", function (e) {
        e.stopPropagation();
        const $item   = $(this).closest(".has-children");
        const $nested = $item.children("ol");
        const isOpen  = $item.hasClass("open");
        if (isOpen) {
            $nested.slideUp(CONFIG.nestedAnimationDuration);
            $item.removeClass("open");
            $(this).attr("aria-expanded", "false");
        } else {
            $nested.slideDown(CONFIG.nestedAnimationDuration);
            $item.addClass("open");
            $(this).attr("aria-expanded", "true");
        }
    });

    // ─── Toggle nested (panel) ───────────────────────────
    $panelContent.on("click", ".toggle", function (e) {
        e.stopPropagation();
        const $item   = $(this).closest(".has-children");
        const $nested = $item.children("ol");
        const isOpen  = $item.hasClass("open");
        if (isOpen) {
            $nested.slideUp(CONFIG.nestedAnimationDuration);
            $item.removeClass("open");
            $(this).attr("aria-expanded", "false");
        } else {
            $nested.slideDown(CONFIG.nestedAnimationDuration);
            $item.addClass("open");
            $(this).attr("aria-expanded", "true");
        }
    });

    // ─── Utility ─────────────────────────────────────────
    function debounce(fn, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

});