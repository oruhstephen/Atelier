/**
 * Atelier Visual Builder — Frontend & Canvas Interactivity
 * Powers Countdown Timers, Lookbook Hotspots, Shop Catalog Filters & Sorting,
 * Single Product Gallery Switcher, and Cart Quantity Steppers.
 */
(function () {
	'use strict';

	function initCountdowns(root) {
		var scope = root || document;
		var timers = scope.querySelectorAll('.avb-countdown-wrap[data-target-date]');
		timers.forEach(function (wrap) {
			if (wrap._avbTimerInterval) {
				clearInterval(wrap._avbTimerInterval);
			}
			var targetStr = wrap.getAttribute('data-target-date');
			var targetTime = targetStr ? new Date(targetStr).getTime() : 0;
			if (!targetTime || isNaN(targetTime)) {
				targetTime = Date.now() + 4 * 86400000 + 18 * 3600000;
			}

			function tick() {
				var diff = Math.max(0, targetTime - Date.now());
				var days = Math.floor(diff / 86400000);
				var hours = Math.floor((diff % 86400000) / 3600000);
				var mins = Math.floor((diff % 3600000) / 60000);
				var secs = Math.floor((diff % 60000) / 1000);

				var dEl = wrap.querySelector('[data-unit="days"]');
				var hEl = wrap.querySelector('[data-unit="hours"]');
				var mEl = wrap.querySelector('[data-unit="mins"]');
				var sEl = wrap.querySelector('[data-unit="secs"]');

				if (dEl) dEl.textContent = String(days).padStart(2, '0');
				if (hEl) hEl.textContent = String(hours).padStart(2, '0');
				if (mEl) mEl.textContent = String(mins).padStart(2, '0');
				if (sEl) sEl.textContent = String(secs).padStart(2, '0');
			}

			tick();
			wrap._avbTimerInterval = setInterval(tick, 1000);
		});
	}

	function initHotspots(root) {
		var scope = root || document;
		var stages = scope.querySelectorAll('.avb-lookbook-stage');
		stages.forEach(function (stage) {
			if (stage._avbBound) return;
			stage._avbBound = true;

			stage.addEventListener('click', function (e) {
				var btn = e.target.closest('.avb-hotspot-btn');
				if (!btn) return;
				e.preventDefault();
				var hotspot = btn.closest('.avb-hotspot');
				var card = hotspot ? hotspot.querySelector('.avb-hotspot-card') : null;
				if (!card) return;

				var wasOpen = card.classList.contains('is-open');
				stage.querySelectorAll('.avb-hotspot-card.is-open').forEach(function (c) {
					c.classList.remove('is-open');
				});
				if (!wasOpen) {
					card.classList.add('is-open');
				}
			});
		});
	}

	function initShopCatalog(root) {
		var scope = root || document;
		var catalogs = scope.querySelectorAll('[data-avb-catalog]');
		catalogs.forEach(function (catalog) {
			if (catalog._avbCatalogBound) return;
			catalog._avbCatalogBound = true;

			var grid = catalog.querySelector('[data-avb-catalog-grid]');
			var countEl = catalog.querySelector('[data-avb-count]');
			if (!grid) return;

			catalog.addEventListener('click', function (e) {
				var pill = e.target.closest('[data-avb-cat-filter]');
				if (!pill) return;
				e.preventDefault();
				var cat = pill.getAttribute('data-avb-cat-filter');

				catalog.querySelectorAll('[data-avb-cat-filter]').forEach(function (p) {
					p.classList.toggle('is-active', p === pill);
				});

				var visible = 0;
				grid.querySelectorAll('.avb-product-card').forEach(function (card) {
					var cats = (card.getAttribute('data-item-cats') || '').split(/\s+/);
					var show = cat === 'all' || cats.indexOf(cat) !== -1;
					card.style.display = show ? '' : 'none';
					if (show) visible++;
				});

				if (countEl) {
					countEl.textContent = 'Showing ' + visible + ' objects';
				}
			});

			var sortSelect = catalog.querySelector('[data-avb-sort]');
			if (sortSelect) {
				sortSelect.addEventListener('change', function () {
					var mode = sortSelect.value;
					var cards = Array.prototype.slice.call(
						grid.querySelectorAll('.avb-product-card')
					);
					cards.sort(function (a, b) {
						var pa = parseFloat(a.getAttribute('data-item-price') || '0');
						var pb = parseFloat(b.getAttribute('data-item-price') || '0');
						var ta = (a.getAttribute('data-item-title') || '').toLowerCase();
						var tb = (b.getAttribute('data-item-title') || '').toLowerCase();
						if (mode === 'price-asc') return pa - pb;
						if (mode === 'price-desc') return pb - pa;
						if (mode === 'title-asc') return ta.localeCompare(tb);
						return 0;
					});
					cards.forEach(function (c) {
						grid.appendChild(c);
					});
				});
			}
		});
	}

	function initProductSpotlights(root) {
		var scope = root || document;
		scope.querySelectorAll('[data-avb-product-spotlight]').forEach(function (spot) {
			if (spot._avbSpotBound) return;
			spot._avbSpotBound = true;

			spot.addEventListener('click', function (e) {
				var thumb = e.target.closest('[data-avb-thumb]');
				if (thumb) {
					e.preventDefault();
					var mainImg = spot.querySelector('[data-avb-main-img]');
					if (mainImg) {
						mainImg.src = thumb.getAttribute('data-avb-thumb');
					}
					spot.querySelectorAll('[data-avb-thumb]').forEach(function (t) {
						t.classList.toggle('is-active', t === thumb);
					});
				}
			});
		});
	}

	function initQtySteppers(root) {
		var scope = root || document;
		scope.querySelectorAll('.avb-qty-control').forEach(function (ctrl) {
			if (ctrl._avbQtyBound) return;
			ctrl._avbQtyBound = true;

			ctrl.addEventListener('click', function (e) {
				var btn = e.target.closest('[data-avb-qty]');
				if (!btn) return;
				e.preventDefault();
				var delta = parseInt(btn.getAttribute('data-avb-qty'), 10);
				var input = ctrl.querySelector('input');
				if (!input) return;
				var next = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
				input.value = next;

				// If inside a cart table row, update row total and summary subtotal
				var row = ctrl.closest('[data-avb-cart-row]');
				if (row) {
					var unit = parseFloat(row.getAttribute('data-unit-price') || '0');
					var rowTotal = row.querySelector('.avb-row-total');
					if (rowTotal) {
						rowTotal.textContent = '$' + (unit * next).toFixed(2);
					}
					var suite = row.closest('.avb-cart-suite');
					if (suite) {
						var sum = 0;
						suite.querySelectorAll('[data-avb-cart-row]').forEach(function (r) {
							var u = parseFloat(r.getAttribute('data-unit-price') || '0');
							var q = parseInt(r.querySelector('input').value, 10) || 1;
							sum += u * q;
						});
						var subEl = suite.querySelector('[data-avb-cart-subtotal]');
						var totEl = suite.querySelector('[data-avb-cart-total]');
						if (subEl) subEl.textContent = '$' + sum.toFixed(2);
						if (totEl) totEl.textContent = '$' + sum.toFixed(2);
					}
				}
			});
		});
	}

	window.AVBFrontend = {
		refresh: function (root) {
			initCountdowns(root);
			initHotspots(root);
			initShopCatalog(root);
			initProductSpotlights(root);
			initQtySteppers(root);
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			window.AVBFrontend.refresh(document);
		});
	} else {
		window.AVBFrontend.refresh(document);
	}
})();
