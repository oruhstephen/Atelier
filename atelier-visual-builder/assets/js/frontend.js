/**
 * Atelier Visual Builder — Frontend & Canvas Interactivity
 * Handles Countdown Timers and Interactive Lookbook Hotspots.
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

	window.AVBFrontend = {
		refresh: function (root) {
			initCountdowns(root);
			initHotspots(root);
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
