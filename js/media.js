(function ($) {

	$(document).ready(function () {
		$('.site-contacts-media-insert').click(function () {
			$('.site-contacts-popup').removeClass('site-contacts-inactive');

			var index = 0;
			var options = '';

			for (index = 0; index < site_contacts_media_ids.length; index++) {
				if (typeof site_contacts_media_ids[index] !== 'undefined') {
					options += '<option value="' + site_contacts_media_ids[index]['id'] + '">' + site_contacts_media_ids[index]['title'] + '</option>';
				}
			}

			if (options && (options.length > 0)) {
				$('.site-contacts-select').html(options);
			} else {
				$('.site-contacts-select').html('<option value="0">---</option>');
			}
		});

		$('.site-contacts-button-insert').click(function (event) {
			if (!$('.site-contacts-popup').hasClass('site-contacts-inactive')) {
				if ($(this).closest('.site-contacts-popup-content').find('.site-contacts-select').length) {
					var object_id = $(this).closest('.site-contacts-popup-content').find('.site-contacts-select').val();

					var win = window.dialogArguments || opener || parent || top;

					if (typeof win !== 'undefined') {
						win.send_to_editor('[site_contact id="' + object_id + '"]');
					}

					$('.site-contacts-popup').addClass('site-contacts-inactive');
				}
			}
		});

		$('.site-contacts-popup').click(function (event) {
			if (event.target === this) {
				if (!$('.site-contacts-popup').hasClass('site-contacts-inactive')) {
					$('.site-contacts-popup').addClass('site-contacts-inactive');
				}
			}
		});

		$('.site-contacts-button-cancel').click(function (event) {
			if (!$('.site-contacts-popup').hasClass('site-contacts-inactive')) {
				$('.site-contacts-popup').addClass('site-contacts-inactive');
			}
		});

		$('.site-contacts-popup-close a').click(function (event) {
			event.preventDefault();

			if (!$('.site-contacts-popup').hasClass('site-contacts-inactive')) {
				$('.site-contacts-popup').addClass('site-contacts-inactive');
			}
		});

		$(document).on('keyup.site-contacts', function (event) {
			if (event.keyCode === 27) {
				if (!$('.site-contacts-popup').hasClass('site-contacts-inactive')) {
					$('.site-contacts-popup').addClass('site-contacts-inactive');
				}
			}
		});
	});
})(jQuery);
