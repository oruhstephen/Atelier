/**
 * Atelier Shop Companion — Admin Script
 * Initializes WP Color Picker and WP Media Uploader for Product Attribute Swatches.
 */
(function ($) {
	'use strict';

	$(function () {
		if ($.fn.wpColorPicker) {
			$('.atelier-color-picker').wpColorPicker();
		}

		$(document).on('click', '.atelier-upload-swatch-image', function (e) {
			e.preventDefault();
			const $input = $('#atelier_swatch_image');

			if (typeof wp === 'undefined' || !wp.media) {
				return;
			}

			const frame = wp.media({
				title: 'Select Swatch Image',
				button: { text: 'Use Image' },
				multiple: false,
			});

			frame.on('select', function () {
				const attachment = frame.state().get('selection').first().toJSON();
				if (attachment && attachment.url) {
					$input.val(attachment.url);
				}
			});

			frame.open();
		});
	});
})(jQuery);
