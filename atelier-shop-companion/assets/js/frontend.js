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
		}).done(function (res) {
			if (res && res.success && res.data) {
				const updated = saveLocalWishlist(res.data.wishlistIds || []);
				syncWishlistUI(updated);
				if (res.data.html) {
					$('#atelier-wishlist-drawer-items, #atelier-wishlist-page-container').html(res.data.html);
				}
			}
		}).always(function () {
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

	// Keep swatches synced when WooCommerce resets or updates available variations
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

	$(function () {
		const initialIds = saveLocalWishlist(getLocalWishlist());
		syncWishlistUI(initialIds);
		initStickyCart();
		initLiveSearch();
	});
})(jQuery);
