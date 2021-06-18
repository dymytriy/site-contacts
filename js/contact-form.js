function site_contacts_submit_form(form_data, sender, form_id, ajax_url) {
	var parent_form = null;

	if (sender) {
		parent_form = jQuery(sender).closest('form.site-contacts-form');
	}

	if (!parent_form || (parent_form.attr('id') !== ('site_contacts_form_' + form_id))) {
		parent_form = jQuery('#site_contacts_form_' + form_id);
	}

	var has_field_errors = false;

	if (parent_form.find('.site-contacts-field-error-container').length > 0) {
		parent_form.find('.site-contacts-field-error-container').remove();
	}

	parent_form.find('.site-contacts-field').each(function () {
		if (jQuery(this).hasClass('site-contacts-field-checkboxes') || jQuery(this).hasClass('site-contacts-field-radioboxes')) {
			var input_elements = jQuery(this).find('.site-contacts-field-body input');

			if (input_elements.length > 0) {
				var required_attribute = input_elements.attr('data-site-contacts-required');

				if (required_attribute && (required_attribute == 'true')) {
					var has_checked_elements = false;

					input_elements.each(function () {
						if (jQuery(this).is(':checked')) {
							has_checked_elements = true;
						}
					});

					if (!has_checked_elements) {
						jQuery(this).append('<div class="site-contacts-field-error-container"><div class="site-contacts-field-error">' + form_data.option_messages_required_option + '</div></div>');

						has_field_errors = true;
					}
				}
			}
		}
		else if (jQuery(this).hasClass('site-contacts-field-textarea')) {
			var textarea_element = jQuery(this).find('.site-contacts-field-body textarea');

			var input_value = textarea_element.val();

			var required_attribute = textarea_element.attr('data-site-contacts-required');

			if ((!input_value || (input_value.length <= 0)) && (required_attribute == 'true')) {
				textarea_element.parent().append('<div class="site-contacts-field-error-container"><div class="site-contacts-field-error">' + form_data.option_messages_required_field + '</div></div>');

				has_field_errors = true;
			}
		}
		else {
			jQuery(this).find('.site-contacts-field-body input').each(function () {
				var required_attribute = jQuery(this).attr('data-site-contacts-required');

				var input_value = jQuery(this).val();

				if ((required_attribute && (required_attribute == 'true')) && (!input_value || (input_value.length <= 0))) {
					jQuery(this).parent().append('<div class="site-contacts-field-error-container"><div class="site-contacts-field-error">' + form_data.option_messages_required_field + '</div></div>');

					has_field_errors = true;
				}
			});
		}
	});

	if (has_field_errors) {
		return false;
	}

	if (jQuery(sender).next('.site-contacts-spinner').length <= 0) {
		jQuery(sender).after('<div class="site-contacts-spinner"><div class="site-contacts-spin-elem"></div><div class="site-contacts-spin-elem"></div><div class="site-contacts-spin-elem"></div></div>');
	}

	var form_serialized_values = parent_form.serializeArray();

	var data = {
		'action': 'site_contacts_submit',
		'site_contacts_form_id': form_id,
		'site_contacts_fields': form_serialized_values,
	};

	jQuery.post(ajax_url, data, function (response) {
		var success = false;
		var validation_failed = false;

		jQuery(sender).next('.site-contacts-spinner').remove();

		try {
			if (response) {
				var result = jQuery.parseJSON(response);

				if ((typeof result !== 'undefined') && (typeof result['status'] !== 'undefined')) {
					if (result['status'].indexOf('success') >= 0) {
						parent_form.find('.site-contacts-form-body').hide();

						var success_html = '<div class="site-contacts-message-block">';
						success_html += '<div class="site-contacts-message-success">';
						success_html += form_data.option_messages_success;
						success_html += '</div>';
						success_html += '</div>';

						if (parent_form.find('.site-contacts-submission-status').length > 0) {
							parent_form.find('.site-contacts-submission-status').remove();
						}

						parent_form.append(success_html);

						success = true;
					}
					else if (result['status'].indexOf('error') >= 0) {
						if ((typeof result['code'] !== 'undefined') &&
							(result['code'] == 'validation_failed') &&
							(typeof result['fields'] !== 'undefined')) {
							for (var index = 0; index < result['fields'].length; ++index) {
								if (typeof result['fields'][index] !== 'undefined') {
									var field_id = result['fields'][index].field_id;
									var error_message = result['fields'][index].message;

									jQuery('#site_contacts_field_' + form_id + '_' + field_id + ' .site-contacts-field-error-container').remove();

									jQuery('#site_contacts_field_' + form_id + '_' + field_id).append('<div class="site-contacts-field-error-container"><div class="site-contacts-field-error">' + error_message + '</div></div>');
								}
							}
						}

						validation_failed = true;
					}
				}
			}
		}
		catch (exception) {
			success = false;
		}

		if (!success) {
			var success_html = '<div class="site-contacts-submission-status">';
			success_html += '<div class="site-contacts-submission-error">';

			if (validation_failed) {
				success_html += form_data.option_messages_validation_failed;
			}
			else {
				success_html += form_data.option_messages_failure;
			}

			success_html += '</div>';
			success_html += '</div>';

			if (parent_form.find('.site-contacts-submission-status').length > 0) {
				parent_form.find('.site-contacts-submission-status').remove();
			}

			parent_form.append(success_html);
		}
	});

	return false;
}

jQuery(document).ready(function ($) {
	$('.site-contacts .site-contacts-input-url').change(function (event) {
		var url_address = $(this).val();

		if (url_address) {
			url_address = url_address.trim();

			if (url_address && (url_address.indexOf(':') >= 0)) {
				var url_parts = url_address.split(':');

				if (url_parts[0] && (!url_parts[0].match(/^[a-z0-9+.-]+$/i))) {
					url_address = url_address.replace(/^\/+/, '');

					url_address = 'http://' + url_address;
				}
			}
			else {
				url_address = url_address.replace(/^\/+/, '');

				url_address = 'http://' + url_address;
			}

			$(this).val(url_address);
		}
	});
});
