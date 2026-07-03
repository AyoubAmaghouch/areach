/**
 * AREACH Front Office — Vanilla JavaScript
 * No frameworks.
 */

(function () {
    'use strict';

    var siteHeader = document.getElementById('site-header');
    var mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    var primaryNavigation = document.getElementById('primary-navigation');
    var mobileNavOverlay = document.getElementById('mobile-nav-overlay');
    var searchToggle = document.getElementById('search-toggle');
    var searchPanel = document.getElementById('search-panel');
    var searchInput = document.getElementById('search-input');
    var languageDropdown = document.getElementById('language-dropdown');
    var languageToggle = document.getElementById('language-toggle');

    var isMobileMenuOpen = false;
    var isSearchOpen = false;
    var isLanguageOpen = false;

    function closeMobileMenu() {
        if (!mobileMenuToggle || !primaryNavigation) {
            return;
        }

        isMobileMenuOpen = false;
        mobileMenuToggle.classList.remove('is-active');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        mobileMenuToggle.setAttribute('aria-label', 'Ouvrir le menu');
        primaryNavigation.classList.remove('is-open');

        if (mobileNavOverlay) {
            mobileNavOverlay.classList.remove('is-visible');
            mobileNavOverlay.hidden = true;
        }

        document.body.style.overflow = '';
    }

    function openMobileMenu() {
        if (!mobileMenuToggle || !primaryNavigation) {
            return;
        }

        closeSearch();
        closeLanguageDropdown();

        isMobileMenuOpen = true;
        mobileMenuToggle.classList.add('is-active');
        mobileMenuToggle.setAttribute('aria-expanded', 'true');
        mobileMenuToggle.setAttribute('aria-label', 'Fermer le menu');
        primaryNavigation.classList.add('is-open');

        if (mobileNavOverlay) {
            mobileNavOverlay.hidden = false;
            requestAnimationFrame(function () {
                mobileNavOverlay.classList.add('is-visible');
            });
        }

        document.body.style.overflow = 'hidden';
    }

    function toggleMobileMenu() {
        if (isMobileMenuOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }

    function closeSearch() {
        if (!searchPanel || !searchToggle) {
            return;
        }

        isSearchOpen = false;
        searchPanel.hidden = true;
        searchToggle.setAttribute('aria-expanded', 'false');
    }

    function openSearch() {
        if (!searchPanel || !searchToggle) {
            return;
        }

        closeMobileMenu();
        closeLanguageDropdown();

        isSearchOpen = true;
        searchPanel.hidden = false;
        searchToggle.setAttribute('aria-expanded', 'true');

        if (searchInput) {
            searchInput.focus();
        }
    }

    function toggleSearch() {
        if (isSearchOpen) {
            closeSearch();
        } else {
            openSearch();
        }
    }

    function closeLanguageDropdown() {
        if (!languageDropdown || !languageToggle) {
            return;
        }

        isLanguageOpen = false;
        languageDropdown.classList.remove('is-open');
        languageToggle.setAttribute('aria-expanded', 'false');
    }

    function openLanguageDropdown() {
        if (!languageDropdown || !languageToggle) {
            return;
        }

        closeMobileMenu();
        closeSearch();

        isLanguageOpen = true;
        languageDropdown.classList.add('is-open');
        languageToggle.setAttribute('aria-expanded', 'true');
    }

    function toggleLanguageDropdown() {
        if (isLanguageOpen) {
            closeLanguageDropdown();
        } else {
            openLanguageDropdown();
        }
    }

    function initStickyNavbar() {
        if (!siteHeader) {
            return;
        }

        var isHomepage = siteHeader.classList.contains('areach-header')
            && document.querySelector('.hero-slider-section') !== null;

        function handleScroll() {
            if (isHomepage) {
                if (window.scrollY > 60) {
                    siteHeader.classList.add('is-scrolled');
                } else {
                    siteHeader.classList.remove('is-scrolled');
                }
            } else {
                if (window.scrollY > 10) {
                    siteHeader.classList.add('is-sticky');
                } else {
                    siteHeader.classList.remove('is-sticky');
                }
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    }

    function initMobileMenu() {
        if (!mobileMenuToggle) {
            return;
        }

        mobileMenuToggle.addEventListener('click', toggleMobileMenu);

        if (mobileNavOverlay) {
            mobileNavOverlay.addEventListener('click', closeMobileMenu);
        }

        if (primaryNavigation) {
            primaryNavigation.querySelectorAll('.areach-mobile-nav__link').forEach(function (link) {
                link.addEventListener('click', closeMobileMenu);
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768 && isMobileMenuOpen) {
                closeMobileMenu();
            }
        });
    }

    function initSearchToggle() {
        if (!searchToggle) {
            return;
        }

        searchToggle.addEventListener('click', toggleSearch);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isSearchOpen) {
                closeSearch();
                searchToggle.focus();
            }
        });
    }

    function initLanguageDropdown() {
        if (!languageToggle) {
            return;
        }

        languageToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleLanguageDropdown();
        });

        document.addEventListener('click', function (event) {
            if (languageDropdown && !languageDropdown.contains(event.target)) {
                closeLanguageDropdown();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isLanguageOpen) {
                closeLanguageDropdown();
                languageToggle.focus();
            }
        });
    }

    function initActiveNavLink() {
        if (!primaryNavigation) {
            return;
        }

        var currentPath = window.location.pathname.split('/').pop() || 'index.php';

        primaryNavigation.querySelectorAll('.areach-mobile-nav__link').forEach(function (link) {
            var linkPath = link.getAttribute('href').split('/').pop().split('?')[0];

            if (linkPath === currentPath) {
                link.classList.add('areach-mobile-nav__link--active');
            }
        });
    }

    function initHeroSlider() {
        var track = document.getElementById('hero-slider-track');

        if (!track) {
            return;
        }

        var slides = track.querySelectorAll('.hero-slide');
        var dots = document.querySelectorAll('.hero-slider__dot');
        var prevBtn = document.getElementById('hero-slider-prev');
        var nextBtn = document.getElementById('hero-slider-next');
        var currentIndex = 0;
        var autoplayDelay = 6000;
        var autoplayTimer = null;

        if (slides.length <= 1) {
            return;
        }

        function goToSlide(index) {
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            dots.forEach(function (dot, dotIndex) {
                var isActive = dotIndex === currentIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayTimer = window.setInterval(nextSlide, autoplayDelay);
        }

        function stopAutoplay() {
            if (autoplayTimer !== null) {
                window.clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                prevSlide();
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                nextSlide();
                startAutoplay();
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var targetIndex = parseInt(dot.getAttribute('data-slide'), 10);

                if (!isNaN(targetIndex)) {
                    goToSlide(targetIndex);
                    startAutoplay();
                }
            });
        });

        track.addEventListener('mouseenter', stopAutoplay);
        track.addEventListener('mouseleave', startAutoplay);
        track.addEventListener('focusin', stopAutoplay);
        track.addEventListener('focusout', startAutoplay);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                prevSlide();
                startAutoplay();
            }

            if (event.key === 'ArrowRight') {
                nextSlide();
                startAutoplay();
            }
        });

        startAutoplay();
    }

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (event) {
                var targetId = anchor.getAttribute('href');

                if (!targetId || targetId === '#') {
                    return;
                }

                var target = document.querySelector(targetId);

                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function initProductVariants() {
        var dataElement = document.getElementById('product-variant-data');
        var colorSelect = document.getElementById('product-color');
        var sizeSelect = document.getElementById('size');
        var sizeField = document.getElementById('product-size-field');
        var variantInput = document.getElementById('variant_id');
        var stockElement = document.getElementById('product-stock');
        var quantityInput = document.getElementById('quantity');
        var addButton = document.getElementById('product-add-to-cart');
        var currentPrice = document.getElementById('product-current-price');
        var originalPrice = document.getElementById('product-original-price');
        var galleryMain = document.getElementById('product-gallery-main');
        var thumbs = document.getElementById('product-image-thumbs');
        var arrowPrev = document.getElementById('gallery-arrow-prev');
        var arrowNext = document.getElementById('gallery-arrow-next');

        if (!dataElement || !colorSelect || !sizeSelect || !variantInput) {
            return;
        }

        var data;

        try {
            data = JSON.parse(dataElement.textContent);
        } catch (error) {
            return;
        }

        var variants = Array.isArray(data.variants) ? data.variants : [];
        var galleryImages = [];
        var currentIndex = 0;
        var mainImage = null;
        var touchStartX = 0;
        var touchEndX = 0;

        function formatPrice(value) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(Number(value) || 0);
        }

        function setActiveImage(index) {
            if (!mainImage || index < 0 || index >= galleryImages.length) {
                return;
            }

            currentIndex = index;

            mainImage.classList.add('fade-out');

            setTimeout(function () {
                mainImage.src = galleryImages[currentIndex];
                mainImage.classList.remove('fade-out');
            }, 150);

            var allThumbs = thumbs.querySelectorAll('.product-gallery__thumb');
            allThumbs.forEach(function (t, i) {
                t.classList.toggle('is-active', i === currentIndex);
            });

            if (arrowPrev) {
                arrowPrev.style.display = galleryImages.length > 1 ? '' : 'none';
            }
            if (arrowNext) {
                arrowNext.style.display = galleryImages.length > 1 ? '' : 'none';
            }
        }

        function renderGallery(images) {
            if (!galleryMain || !thumbs) {
                return;
            }

            galleryImages = Array.isArray(images) ? images : [];
            currentIndex = 0;

            var oldMain = document.getElementById('product-main-image');
            var oldPlaceholder = document.getElementById('product-image-placeholder');

            if (oldMain) { oldMain.remove(); }
            if (oldPlaceholder) { oldPlaceholder.remove(); }

            thumbs.replaceChildren();
            mainImage = null;

            if (galleryImages.length === 0) {
                var placeholder = document.createElement('div');
                var icon = document.createElement('i');
                placeholder.id = 'product-image-placeholder';
                placeholder.className = 'product-detail__placeholder';
                placeholder.setAttribute('aria-hidden', 'true');
                icon.className = 'fa-solid fa-shirt';
                placeholder.appendChild(icon);
                galleryMain.appendChild(placeholder);
                thumbs.hidden = true;
                if (arrowPrev) arrowPrev.style.display = 'none';
                if (arrowNext) arrowNext.style.display = 'none';
                return;
            }

            mainImage = document.createElement('img');
            mainImage.id = 'product-main-image';
            mainImage.className = 'product-detail__image';
            mainImage.src = galleryImages[0];
            mainImage.alt = data.product_name || '';
            galleryMain.insertBefore(mainImage, arrowPrev || null);

            galleryImages.forEach(function (imagePath, idx) {
                var thumb = document.createElement('img');
                thumb.className = 'product-gallery__thumb' + (idx === 0 ? ' is-active' : '');
                thumb.src = imagePath;
                thumb.alt = '';
                thumb.addEventListener('click', function () {
                    setActiveImage(idx);
                });
                thumbs.appendChild(thumb);
            });

            thumbs.hidden = false;
            if (arrowPrev) arrowPrev.style.display = galleryImages.length > 1 ? '' : 'none';
            if (arrowNext) arrowNext.style.display = galleryImages.length > 1 ? '' : 'none';
        }

        function navigateGallery(direction) {
            if (galleryImages.length < 2) return;
            var next = currentIndex + direction;
            if (next < 0) next = galleryImages.length - 1;
            if (next >= galleryImages.length) next = 0;
            setActiveImage(next);
        }

        if (arrowPrev) {
            arrowPrev.addEventListener('click', function (e) {
                e.stopPropagation();
                navigateGallery(-1);
            });
        }
        if (arrowNext) {
            arrowNext.addEventListener('click', function (e) {
                e.stopPropagation();
                navigateGallery(1);
            });
        }

        if (galleryMain) {
            galleryMain.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') { navigateGallery(-1); e.preventDefault(); }
                if (e.key === 'ArrowRight') { navigateGallery(1); e.preventDefault(); }
            });
            galleryMain.setAttribute('tabindex', '0');

            galleryMain.addEventListener('touchstart', function (e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            galleryMain.addEventListener('touchend', function (e) {
                touchEndX = e.changedTouches[0].screenX;
                var diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 40) {
                    if (diff > 0) { navigateGallery(1); }
                    else { navigateGallery(-1); }
                }
            }, { passive: true });
        }

        function updateSelectedSize(variant) {
            var selectedIndex = sizeSelect.selectedIndex;
            var size = selectedIndex >= 0 ? variant.sizes[selectedIndex] : null;
            var stock = size ? Number(size.stock) : 0;

            variantInput.value = size ? String(size.id_variant) : '';

            if (stockElement) {
                stockElement.innerHTML = stock > 0
                    ? '<span class="product-detail__stock--in">En stock</span>'
                    : '<span class="product-detail__stock--out">Rupture de stock</span>';
            }

            if (quantityInput) {
                quantityInput.max = String(Math.max(1, Math.min(10, stock)));
                quantityInput.disabled = stock <= 0;

                if (Number(quantityInput.value) > Number(quantityInput.max)) {
                    quantityInput.value = quantityInput.max;
                }
            }

            if (addButton) {
                addButton.disabled = stock <= 0;
            }

            if (currentPrice && size) {
                currentPrice.textContent = formatPrice(size.price);
            }

            if (originalPrice) {
                if (size && size.on_sale) {
                    originalPrice.textContent = formatPrice(size.original_price);
                    originalPrice.hidden = false;
                } else {
                    originalPrice.textContent = '';
                    originalPrice.hidden = true;
                }
            }
        }

        function selectColor() {
            var variant = variants[Number(colorSelect.value)];

            if (!variant) {
                return;
            }

            sizeSelect.replaceChildren();

            variant.sizes.forEach(function (size) {
                var option = document.createElement('option');
                option.value = size.size;
                option.textContent = size.size;
                option.disabled = Number(size.stock) <= 0;
                sizeSelect.appendChild(option);
            });

            var firstAvailableIndex = variant.sizes.findIndex(function (size) {
                return Number(size.stock) > 0;
            });
            sizeSelect.selectedIndex = firstAvailableIndex >= 0 ? firstAvailableIndex : 0;

            if (sizeField) {
                sizeField.hidden = variant.sizes.length === 0;
            }

            renderGallery(variant.images);
            updateSelectedSize(variant);
        }

        colorSelect.addEventListener('change', selectColor);
        sizeSelect.addEventListener('change', function () {
            var variant = variants[Number(colorSelect.value)];

            if (variant) {
                updateSelectedSize(variant);
            }
        });

        selectColor();
    }

    function initProductCardSlideshows(container) {
        container = container || document;
        var slideshows = container.querySelectorAll('.product-card__slideshow:not([data-slideshow-init])');

        if (slideshows.length === 0) {
            return;
        }

        slideshows.forEach(function (slideshow) {
            var slides = slideshow.querySelectorAll('.product-card__slide');

            if (slides.length < 2) {
                return;
            }

            slideshow.setAttribute('data-slideshow-init', '1');

            var currentIndex = 0;
            var intervalMs = parseInt(slideshow.getAttribute('data-interval'), 10) || 3000;
            var timerId = null;

            function nextSlide() {
                slides[currentIndex].classList.remove('is-active');
                currentIndex = (currentIndex + 1) % slides.length;
                slides[currentIndex].classList.add('is-active');
            }

            function start() {
                if (timerId !== null) {
                    return;
                }
                timerId = window.setInterval(nextSlide, intervalMs);
            }

            function stop() {
                if (timerId !== null) {
                    window.clearInterval(timerId);
                    timerId = null;
                }
            }

            start();

            slideshow._stop = stop;
            slideshow._start = start;
        });
    }

    function initProductCardVisibilityHandler() {
        var slideshows = document.querySelectorAll('.product-card__slideshow');

        function handleVisibilityChange() {
            if (document.hidden) {
                slideshows.forEach(function (s) {
                    if (s._stop) s._stop();
                });
            } else {
                slideshows.forEach(function (s) {
                    if (s._start) s._start();
                });
            }
        }

        document.addEventListener('visibilitychange', handleVisibilityChange);
    }

    function initNouveautesReveal() {
        var section = document.getElementById('nouveautes');
        if (!section) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            section.classList.add('is-visible');
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    section.classList.add('is-visible');
                    observer.unobserve(section);
                }
            });
        }, { threshold: 0.15 });

        observer.observe(section);
    }

    function initAboutReveal() {
        var section = document.getElementById('about-areach');
        if (!section) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            section.classList.add('is-visible');
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    section.classList.add('is-visible');
                    observer.unobserve(section);
                }
            });
        }, { threshold: 0.15 });

        observer.observe(section);
    }

    function init() {
        initStickyNavbar();
        initMobileMenu();
        initSearchToggle();
        initLanguageDropdown();
        initActiveNavLink();
        initHeroSlider();
        initSmoothScroll();
        initAboutReveal();
        initNouveautesReveal();
        initProductCardSlideshows();
        initProductCardVisibilityHandler();
        initProductVariants();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
