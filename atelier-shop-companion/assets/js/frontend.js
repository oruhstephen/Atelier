/**
 * Atelier Shop Companion — Frontend Interactive Script
 *
 * Handles:
 * 1. Wishlist toggle, cookie/localStorage persistence & slide-out drawer
 * 2. Variation Color/Image/Label Swatches synced with WooCommerce variation forms
 * 3. Product Quick View modal & gallery switcher
 * 4. Size & Fit Guide modal
 * 5. Sticky Add-to-Cart scroll observer
 * 6. Debounced Instant Live Product Search
 * 7. Bloghash Pro Ported Features:
 *    - Plus / Minus (+/-) Quantity Stepper Buttons
 *    - Dark / Light Mode Switcher (localStorage + data-darkmode)
 *    - Sticky Header scroll shadow state
 *    - Top Marquee Ticker Play/Pause & Live Date
 *    - Smooth Scroll-to-Top Floating Button
 *    - Smooth TOC anchor scrolling & Article Quick-Like counter
 *    - Interactive Desktop Cursor Dot Follower
 */
(function ($) {
	'use strict';

	const cfg = window.atelierCompanion || {};
	const COOKIE_NAME = 'atelier_wishlist_ids';

	/* ==========================================================================
	   1. Wishlist Persistence & Drawer
	   ========================================================================== */
	function getLocalWishlist() {
		try {
			const raw = localStorage.getItem(COOKIE_NAME);
			const parsed = raw ? JSON.parse(raw) : cfg.wishlistIds || [];
			return Array.isArray(parsed) ? parsed.map(Number).filter(Boolean) : [];
		} catch (e) {
			return Array.isArray(cfg.wishlistIds) ? cfg.wishlistIds : [];
		}
	}

	function saveLocalWishlist(ids) {
		const clean = Array.from(new Set(ids.map(Number).filter(Boolean)));
		try {
			localStorage.setItem(COOKIE_NAME, JSON.stringify(clean));
		} catch (e) {}
		document.cookie =
			COOKIE_NAME +
			'=' +
			encodeURIComponent(JSON.stringify(clean)) +
			'; path=/; max-age=' +
			60 * 60 * 24 * 30 +
			'; SameSite=Lax';
		return clean;
	}

	function syncWishlistUI(ids) {
		const count = ids.length;
		$('[data-wishlist-count]').each(function () {
			$(this).text(count);
			if ($(this).hasClass('atelier-wishlist-count')) {
				$(this).toggleClass('is-visible', count > 0);
			}
		});

		$('.atelier-wishlist-btn').each(function () {
			const pid = Number($(this).data('product-id'));
			const active = ids.includes(pid);
			$(this)
				.toggleClass('is-active', active)
				.attr('aria-pressed', active ? 'true' : 'false');
			$(this)
				.find('.atelier-wishlist-btn__text')
				.text(active ? cfg.i18n.addedToWishlist : 'Save to Wishlist');
		});
	}

	function openDrawer($drawer) {
		$drawer.addClass('is-open').attr('aria-hidden', 'false');
		$('.atelier-header-wishlist-trigger').attr('aria-expanded', 'true');
	}

	function closeDrawersAndModals() {
		$('.atelier-drawer, .atelier-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('.atelier-header-wishlist-trigger').attr('aria-expanded', 'false');
	}

	$(document).on('click', '.atelier-wishlist-btn, .atelier-wishlist-remove', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const $btn = $(this);
		const productId = Number($btn.data('product-id'));
		if (!productId) return;

		$btn.addClass('is-loading');

		$.post(cfg.ajaxUrl, {
			action: 'atelier_toggle_wishlist',
			nonce: cfg.nonce,
			product_id: productId,
			ids: getLocalWishlist(),
		})
			.done(function (res) {
				if (res && res.success && res.data) {
					const updated = saveLocalWishlist(res.data.wishlistIds || []);
					syncWishlistUI(updated);
					if (res.data.html) {
						$('#atelier-wishlist-drawer-items, #atelier-wishlist-page-container').html(res.data.html);
					}
				}
			})
			.always(function () {
				$btn.removeClass('is-loading');
			});
	});

	$(document).on('click', '.atelier-header-wishlist-trigger', function (e) {
		e.preventDefault();
		openDrawer($('#atelier-wishlist-drawer'));
	});

	$(document).on('click', '[data-atelier-close-drawer], [data-atelier-close-modal]', function (e) {
		e.preventDefault();
		closeDrawersAndModals();
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			closeDrawersAndModals();
			$('.atelier-live-search-results').removeClass('is-open');
		}
	});

	/* ==========================================================================
	   2. Variation Swatches Interaction & WooCommerce Sync
	   ========================================================================== */
	$(document).on('click', '.atelier-swatch', function (e) {
		e.preventDefault();
		const $swatch = $(this);
		if ($swatch.hasClass('is-disabled')) return;

		const value = $swatch.data('value');
		const $wrap = $swatch.closest('.atelier-swatches-wrap');
		const $select = $wrap.find('select');

		if ($swatch.hasClass('is-selected')) {
			$swatch.removeClass('is-selected').attr('aria-checked', 'false');
			$select.val('').trigger('change');
		} else {
			$wrap.find('.atelier-swatch').removeClass('is-selected').attr('aria-checked', 'false');
			$swatch.addClass('is-selected').attr('aria-checked', 'true');
			$select.val(value).trigger('change');
		}
	});

	$(document).on('woocommerce_update_variation_values', '.variations_form', function () {
		$(this)
			.find('.atelier-swatches-wrap')
			.each(function () {
				const $wrap = $(this);
				const $select = $wrap.find('select');
				const currentVal = $select.val();

				$wrap.find('.atelier-swatch').each(function () {
					const val = String($(this).data('value'));
					const exists = $select.find('option[value="' + val.replace(/"/g, '\\"') + '"]').length > 0;
					$(this)
						.toggleClass('is-disabled', !exists)
						.toggleClass('is-selected', val === String(currentVal))
						.attr('aria-checked', val === String(currentVal) ? 'true' : 'false');
				});
			});
	});

	$(document).on('click', '.reset_variations', function () {
		$(this)
			.closest('.variations_form')
			.find('.atelier-swatch')
			.removeClass('is-selected is-disabled')
			.attr('aria-checked', 'false');
	});

	/* ==========================================================================
	   3. Product Quick View Modal
	   ========================================================================== */
	$(document).on('click', '.atelier-quick-view-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const productId = Number($(this).data('product-id'));
		if (!productId) return;

		const $modal = $('#atelier-quick-view-modal');
		const $content = $('#atelier-quick-view-content');

		$content.html('<p class="atelier-live-search-empty">Loading…</p>');
		$modal.addClass('is-open').attr('aria-hidden', 'false');

		$.post(cfg.ajaxUrl, {
			action: 'atelier_quick_view',
			nonce: cfg.nonce,
			product_id: productId,
		}).done(function (res) {
			if (res && res.success && res.data && res.data.html) {
				$content.html(res.data.html);
				const $variationForm = $content.find('.variations_form');
				if ($variationForm.length && $.fn.wc_variation_form) {
					$variationForm.wc_variation_form();
				}
				initQuantityButtons();
			}
		});
	});

	$(document).on('click', '.atelier-qv-thumb', function () {
		const src = $(this).data('full-src');
		if (src) {
			$('#atelier-qv-active-image').attr('src', src);
			$('.atelier-qv-thumb').removeClass('is-active');
			$(this).addClass('is-active');
		}
	});

	/* ==========================================================================
	   4. Size & Fit Guide Modal
	   ========================================================================== */
	$(document).on('click', '[data-atelier-open-size-guide]', function (e) {
		e.preventDefault();
		$('#atelier-size-guide-modal').addClass('is-open').attr('aria-hidden', 'false');
	});

	/* ==========================================================================
	   5. Sticky Add-to-Cart Bar on Single Product Scroll
	   ========================================================================== */
	function initStickyCart() {
		const stickyEl = document.getElementById('atelier-sticky-cart');
		const formEl = document.querySelector('form.cart');
		if (!stickyEl || !formEl || !('IntersectionObserver' in window)) return;

		const observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					const shouldShow = !entry.isIntersecting && entry.boundingClientRect.top < 0;
					stickyEl.classList.toggle('is-visible', shouldShow);
					stickyEl.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
				});
			},
			{ threshold: 0 }
		);
		observer.observe(formEl);

		$(document).on('click', '[data-atelier-sticky-scroll]', function (e) {
			e.preventDefault();
			formEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
		});
	}

	/* ==========================================================================
	   6. Instant AJAX Live Product Search
	   ========================================================================== */
	function initLiveSearch() {
		if (!cfg.enableLiveSearch) return;

		let debounceTimer = null;

		$('.wp-block-search').each(function () {
			const $searchBlock = $(this);
			const $input = $searchBlock.find('input[type="search"]');
			if (!$input.length) return;

			const $dropdown = $('<div class="atelier-live-search-results" role="listbox"></div>');
			$searchBlock.append($dropdown);

			$input.attr('autocomplete', 'off').on('input', function () {
				const term = $.trim($(this).val());
				clearTimeout(debounceTimer);

				if (term.length < 2) {
					$dropdown.removeClass('is-open').empty();
					return;
				}

				debounceTimer = setTimeout(function () {
					$dropdown
						.addClass('is-open')
						.html('<div class="atelier-live-search-empty">' + (cfg.i18n.searching || 'Searching…') + '</div>');

					$.get(cfg.ajaxUrl, {
						action: 'atelier_live_search',
						nonce: cfg.nonce,
						term: term,
					}).done(function (res) {
						if (!res || !res.success || !res.data || !res.data.results.length) {
							$dropdown.html(
								'<div class="atelier-live-search-empty">' + (cfg.i18n.noResults || 'No products found.') + '</div>'
							);
							return;
						}

						const itemsHtml = res.data.results
							.map(function (item) {
								return (
									'<a class="atelier-live-search-item" role="option" href="' +
									item.url +
									'">' +
									'<img src="' +
									item.image +
									'" alt="" />' +
									'<div>' +
									'<span class="atelier-live-search-item__title">' +
									$('<div>').text(item.title).html() +
									'</span>' +
									'<span class="atelier-live-search-item__price">' +
									item.price_html +
									'</span>' +
									'</div>' +
									'</a>'
								);
							})
							.join('');

						$dropdown.html(itemsHtml);
					});
				}, 240);
			});
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.wp-block-search').length) {
				$('.atelier-live-search-results').removeClass('is-open');
			}
		});
	}

	/* ==========================================================================
	   7. Bloghash Pro Ported Interactive Features
	   ========================================================================== */

	// 7.1 Plus / Minus Quantity Stepper Buttons (from BloghashWC.quantButtons)
	function initQuantityButtons() {
		if (!cfg.enableQtyButtons) return;

		$('div.quantity:not(.atelier-qty-enhanced), td.quantity:not(.atelier-qty-enhanced)').each(function () {
			const $wrap = $(this);
			const $input = $wrap.find('input.qty');
			if (!$input.length || $input.prop('type') === 'hidden' || $input.prop('type') === 'date') {
				return;
			}
			$wrap.addClass('atelier-qty-enhanced');
			$input.before('<button type="button" class="atelier-qty-btn atelier-qty-minus" aria-label="Decrease quantity">&minus;</button>');
			$input.after('<button type="button" class="atelier-qty-btn atelier-qty-plus" aria-label="Increase quantity">&#43;</button>');
		});
	}

	$(document).on('click', '.atelier-qty-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $input = $btn.closest('.quantity').find('input.qty');
		if (!$input.length) return;

		const current = parseFloat($input.val()) || 0;
		const min = parseFloat($input.attr('min')) || 0;
		const max = parseFloat($input.attr('max'));
		const step = parseFloat($input.attr('step')) || 1;

		let nextVal = current;
		if ($btn.hasClass('atelier-qty-plus')) {
			nextVal = current + step;
			if (!isNaN(max) && max > 0 && nextVal > max) {
				nextVal = max;
			}
		} else {
			nextVal = Math.max(min, current - step);
		}

		$input.val(nextVal).trigger('change');
	});

	$(document).ajaxComplete(function () {
		initQuantityButtons();
	});

	// 7.2 Dark / Light Mode Switcher (from bloghash.js)
	$(document).on('click', '[data-atelier-darkmode-toggle]', function (e) {
		e.preventDefault();
		const isDark = document.documentElement.getAttribute('data-darkmode') === 'dark';
		const nextMode = isDark ? 'light' : 'dark';
		document.documentElement.setAttribute('data-darkmode', nextMode);
		try {
			localStorage.setItem('atelier_darkmode', nextMode);
		} catch (err) {}
	});

	// 7.3 Top Announcement Ticker Play/Pause & Live Date
	function initTopTicker() {
		const dateEl = document.getElementById('atelier-live-date');
		if (dateEl) {
			const opts = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
			dateEl.textContent = new Date().toLocaleDateString(document.documentElement.lang || 'en-GB', opts);
		}

		$(document).on('click', '[data-atelier-ticker-toggle]', function (e) {
			e.preventDefault();
			const $track = $('#atelier-ticker-track');
			$track.toggleClass('is-paused');
			const paused = $track.hasClass('is-paused');
			$(this).find('.atelier-ticker-icon-pause').toggle(!paused);
			$(this).find('.atelier-ticker-icon-play').toggle(paused);
		});
	}

	// 7.4 Sticky Header Shadow & Scroll-to-Top Floating Button
	function initScrollBehaviors() {
		const scrollTopBtn = document.getElementById('atelier-scroll-top');

		function onScroll() {
			const y = window.pageYOffset || document.documentElement.scrollTop;
			if (cfg.enableStickyHeader) {
				document.body.classList.toggle('is-header-pinned', y > 24);
			}
			if (scrollTopBtn) {
				scrollTopBtn.classList.toggle('is-visible', y > 450);
			}
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();

		$(document).on('click', '#atelier-scroll-top', function (e) {
			e.preventDefault();
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

	// 7.5 Smooth TOC Anchor Scroll & Article Quick-Like Counter
	$(document).on('click', '.atelier-toc-link', function (e) {
		const href = $(this).attr('href');
		if (href && href.startsWith('#')) {
			const target = document.getElementById(href.slice(1));
			if (target) {
				e.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}
	});

	$(document).on('click', '.atelier-post-like-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const postId = Number($btn.data('post-id'));
		if (!postId || $btn.prop('disabled')) return;

		const storageKey = 'atelier_liked_post_' + postId;
		const alreadyLiked = localStorage.getItem(storageKey) === '1';

		$btn.prop('disabled', true);
		$.post(cfg.ajaxUrl, {
			action: 'atelier_post_like',
			nonce: cfg.nonce,
			post_id: postId,
			liked: alreadyLiked ? '1' : '0',
		})
			.done(function (res) {
				if (res && res.success && res.data) {
					$btn.toggleClass('is-liked', !!res.data.liked);
					$btn.find('.atelier-post-like-count').text(res.data.count);
					try {
						if (res.data.liked) {
							localStorage.setItem(storageKey, '1');
						} else {
							localStorage.removeItem(storageKey);
						}
					} catch (err) {}
				}
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});

	// 7.6 Interactive Desktop Cursor Follower (from bloghash.js Cursor)
	function initCursorDot() {
		const cursor = document.getElementById('atelier-js-cursor');
		if (!cursor || !cfg.enableCursorDot || window.innerWidth < 992) return;

		cursor.classList.add('is-enabled');
		let clientX = -100;
		let clientY = -100;

		document.addEventListener(
			'mousemove',
			function (e) {
				clientX = e.clientX;
				clientY = e.clientY;
			},
			{ passive: true }
		);

		function renderCursor() {
			cursor.style.transform = 'translate(' + (clientX - 12) + 'px, ' + (clientY - 12) + 'px)';
			requestAnimationFrame(renderCursor);
		}
		requestAnimationFrame(renderCursor);

		$(document).on('mouseenter', 'a, button, input, select, textarea', function () {
			cursor.classList.add('is-active');
		});
		$(document).on('mouseleave', 'a, button, input, select, textarea', function () {
			cursor.classList.remove('is-active');
		});
	}

	$(function () {
		const initialIds = saveLocalWishlist(getLocalWishlist());
		syncWishlistUI(initialIds);
		initStickyCart();
		initLiveSearch();
		initQuantityButtons();
		initTopTicker();
		initScrollBehaviors();
		initCursorDot();

		// Restore liked state on blog posts
		$('.atelier-post-like-btn').each(function () {
			const pid = $(this).data('post-id');
			if (pid && localStorage.getItem('atelier_liked_post_' + pid) === '1') {
				$(this).addClass('is-liked');
			}
		});
	});
})(jQuery);
