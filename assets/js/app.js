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

        var scrollThreshold = 10;

        function handleScroll() {
            if (window.scrollY > scrollThreshold) {
                siteHeader.classList.add('is-sticky');
            } else {
                siteHeader.classList.remove('is-sticky');
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
            primaryNavigation.querySelectorAll('.navbar__link').forEach(function (link) {
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

        primaryNavigation.querySelectorAll('.navbar__link').forEach(function (link) {
            var linkPath = link.getAttribute('href').split('/').pop().split('?')[0];

            if (linkPath === currentPath) {
                link.classList.add('is-active');
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
        var gallery = document.getElementById('product-gallery');
        var thumbs = document.getElementById('product-image-thumbs');

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

        function formatPrice(value) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(Number(value) || 0);
        }

        function renderGallery(images) {
            if (!gallery || !thumbs) {
                return;
            }

            var oldMain = document.getElementById('product-main-image');
            var oldPlaceholder = document.getElementById('product-image-placeholder');

            if (oldMain) {
                oldMain.remove();
            }

            if (oldPlaceholder) {
                oldPlaceholder.remove();
            }

            thumbs.replaceChildren();

            if (!Array.isArray(images) || images.length === 0) {
                var placeholder = document.createElement('div');
                var icon = document.createElement('i');
                placeholder.id = 'product-image-placeholder';
                placeholder.className = 'product-detail__placeholder';
                placeholder.setAttribute('aria-hidden', 'true');
                icon.className = 'fa-solid fa-shirt';
                placeholder.appendChild(icon);
                gallery.insertBefore(placeholder, thumbs);
                thumbs.hidden = true;
                return;
            }

            var mainImage = document.createElement('img');
            mainImage.id = 'product-main-image';
            mainImage.className = 'product-detail__image';
            mainImage.src = images[0];
            mainImage.alt = data.product_name || '';
            gallery.insertBefore(mainImage, thumbs);

            images.forEach(function (imagePath) {
                var thumb = document.createElement('img');
                thumb.className = 'product-detail__thumb';
                thumb.src = imagePath;
                thumb.alt = '';
                thumb.addEventListener('click', function () {
                    mainImage.src = imagePath;
                });
                thumbs.appendChild(thumb);
            });

            thumbs.hidden = false;
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

    function init() {
        initStickyNavbar();
        initMobileMenu();
        initSearchToggle();
        initLanguageDropdown();
        initActiveNavLink();
        initHeroSlider();
        initSmoothScroll();
        initProductVariants();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
