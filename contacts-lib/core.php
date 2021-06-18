<?php

if (!defined('ABSPATH')) {
	exit;
}

define('SITE_CONTACTS_INDEX_TEMPLATE', '%%%INDEX%%%');

// Add actions which are required by our plugin

add_action('widgets_init', 'site_contacts_widgets_init');
add_filter('widget_text', 'site_contacts_do_shortcode');
add_action('plugins_loaded', 'site_contacts_plugins_loaded');

// Functions
function site_contacts_true($value) {
	if (!empty($value)) {
		$value = trim(strtolower($value));

		if (($value == 'on') ||
			($value == 'true') ||
			($value == 'yes')) {
			return true;
		}
	}

	return false;
}

function site_contacts_checked($value_name) {
	if (!empty($_POST[$value_name])) {
		$post_value = trim(strtolower($_POST[$value_name]));

		if (($post_value == 'on') ||
			($post_value == 'true') ||
			($post_value == 'yes')) {
			return true;
		}
	}

	return false;
}

function site_contacts_local_time($value) {
	if (!empty($value)) {
		$value = get_date_from_gmt($value);

		return date_i18n('Y/m/d g:i:s A', strtotime($value));
	}

	return '';
}

function site_contacts_plugins_loaded() {
	$current_version = get_option('site_contacts_version', '');

	if (empty($current_version)) {
		site_contacts_create_db();
	}
}

function site_contacts_create_db() {
	// Create required database structure

	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'forms` (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`created` datetime NOT NULL,
			`status` bigint(20) UNSIGNED NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`title` text NOT NULL,
			`description` text NOT NULL,
			`config` longtext NOT NULL,
			`style` longtext NOT NULL,
			PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'fields` (
			`field_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`form_id` bigint(20) UNSIGNED NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`weight` bigint(20) NOT NULL,
			`required` tinyint(1) NOT NULL,
			`name` text NOT NULL,
			`label` text NOT NULL,
			`title` text NOT NULL,
			`description` text NOT NULL,
			`placeholder` text NOT NULL,
			`value` longtext NOT NULL,
			`data` longtext NOT NULL,
			`config` longtext NOT NULL,
			`style` longtext NOT NULL,
			PRIMARY KEY (`field_id`),
			KEY `form_id` (`form_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'field_entries` (
			`entry_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`field_id` bigint(20) UNSIGNED NOT NULL,
			`form_id` bigint(20) UNSIGNED NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`weight` bigint(20) NOT NULL,
			`name` text NOT NULL,
			`title` text NOT NULL,
			`value` longtext NOT NULL,
			`data` longtext NOT NULL,
			`config` longtext NOT NULL,
			PRIMARY KEY (`entry_id`),
			KEY `field_id` (`field_id`),
			KEY `form_id` (`form_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'data` (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`form_id` bigint(20) UNSIGNED NOT NULL,
			`created` datetime NOT NULL,
			`updated` datetime NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`title` text NOT NULL,
			`description` text NOT NULL,
			`value` longtext NOT NULL,
			`content` longtext NOT NULL,
			`config` longtext NOT NULL,
			PRIMARY KEY (`id`),
			KEY `form_id` (`form_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'data_fields` (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`data_id` bigint(20) UNSIGNED NOT NULL,
			`form_id` bigint(20) UNSIGNED NOT NULL,
			`field_id` bigint(20) UNSIGNED NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`weight` bigint(20) NOT NULL,
			`required` tinyint(1) NOT NULL,
			`label` longtext NOT NULL,
			`value` longtext NOT NULL,
			`content` longtext NOT NULL,
			`config` longtext NOT NULL,
			PRIMARY KEY (`id`),
			KEY `data_id` (`data_id`),
			KEY `form_id` (`form_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	$wpdb->query('
		CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'data_entries` (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`data_id` bigint(20) UNSIGNED NOT NULL,
			`data_field_id` bigint(20) UNSIGNED NOT NULL,
			`form_id` bigint(20) UNSIGNED NOT NULL,
			`field_id` bigint(20) UNSIGNED NOT NULL,
			`entry_id` bigint(20) UNSIGNED NOT NULL,
			`type` bigint(20) UNSIGNED NOT NULL,
			`weight` bigint(20) NOT NULL,
			`value` longtext NOT NULL,
			`content` longtext NOT NULL,
			`config` longtext NOT NULL,
			PRIMARY KEY (`id`),
			KEY `data_id` (`data_id`),
			KEY `data_field_id` (`data_field_id`),
			KEY `form_id` (`form_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');

	update_option('site_contacts_version', SITE_CONTACTS_VERSION);
	update_option('site_contacts_db_schema', SITE_CONTACTS_DB_SCHEMA);
}

function site_contacts_remove_data() {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'forms`;');
	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'fields`;');
	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'field_entries`;');
	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'data`;');
	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'data_entries`;');
	$wpdb->query('DROP TABLE IF EXISTS `' . $table_prefix . 'data_fields`;');

	delete_option('site_contacts_version');
	delete_option('site_contacts_db_schema');
	delete_option('site_contacts_submissions_form');
}

function site_contacts_widgets_init() {
	register_widget('SiteContactsWidget');
}

function site_contacts_parse_shortcodes($value) {
	$admin_email = get_option('admin_email');

	if (!empty($admin_email)) {
		$value = str_replace('[admin_email]', $admin_email, $value);
	}

	return $value;
}

function site_contacts_field_type($field) {
	if (!empty($field['type'])) {
		switch ($field['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
				return 'text';
				break;

			case SITE_CONTACTS_FIELD_PHONE:
				return 'phone';
				break;

			case SITE_CONTACTS_FIELD_EMAIL:
				return 'email';
				break;

			case SITE_CONTACTS_FIELD_URL:
				return 'url';
				break;

			case SITE_CONTACTS_FIELD_DROPDOWN:
				return 'dropdown';
				break;

			case SITE_CONTACTS_FIELD_CHECKBOXES:
				return 'checkbox';
				break;

			case SITE_CONTACTS_FIELD_RADIOBOXES:
				return 'radio';
				break;

			case SITE_CONTACTS_FIELD_TEXTAREA:
				return 'textarea';
				break;

			case SITE_CONTACTS_FIELD_SUBMIT_BUTTON:
				return 'submit';
				break;
		}
	}

	return '';
}

function site_contacts_default_notification() {
	$default = array(
		'sendto'	=> __('[admin_email]', 'site-contacts'),
		'from'		=> __('Administrator <[admin_email]>', 'site-contacts'),
		'subject'	=> __('New form submission', 'site-contacts')
	);

	return $default;
}

function site_contacts_default_messages() {
	$default = array(
		'success'				=> __('Your form has been successfully submitted! Thank you for contacting us.', 'site-contacts'),
		'failure'				=> __('Failed to send form data! Please try again later.', 'site-contacts'),
		'validation_failed'		=> __('Form validation failed. Please fix errors and try again.', 'site-contacts'),
		'required_field'		=> __('Please provide a value for this field!', 'site-contacts'),
		'required_option'		=> __('Please select at least one option!', 'site-contacts'),
		'invalid_email'			=> __('Invalid e-mail address!', 'site-contacts'),
		'invalid_url'			=> __('Invalid URL address!', 'site-contacts')
	);

	return $default;
}

function site_contacts_submitted_fields($contact_form) {
	$form_data = array();

	$fields_info = array();

	if (!empty($_POST['site_contacts_fields']) && is_array($_POST['site_contacts_fields'])) {
		foreach ($_POST['site_contacts_fields'] as $item) {
			if ((!empty($item['name'])) && (!empty($item['value']))) {
				$form_data[$item['name']] = wp_unslash($item['value']);
			}
		}
	}

	if (!empty($form_data) && !empty($contact_form['fields']) && is_array($contact_form['fields'])) {
		foreach ($contact_form['fields'] as $field) {
			if (!empty($field['type'])) {
				switch ($field['type']) {
					case SITE_CONTACTS_FIELD_TEXTFIELD:
					case SITE_CONTACTS_FIELD_PHONE:
					case SITE_CONTACTS_FIELD_EMAIL:
					case SITE_CONTACTS_FIELD_URL:
					case SITE_CONTACTS_FIELD_DROPDOWN:
					case SITE_CONTACTS_FIELD_TEXTAREA:
					case SITE_CONTACTS_FIELD_RADIOBOXES: {
						$field_name = 'site_contacts_' . site_contacts_field_type($field) . '_' . $field['field_id'];

						$new_field = array();

						$new_field['field_id'] = $field['field_id'];
						$new_field['type'] = $field['type'];
						$new_field['weight'] = $field['weight'];
						$new_field['required'] = $field['required'];
						$new_field['label'] = $field['label'];

						if (!empty($form_data[$field_name])) {
							$new_field['content'] = $form_data[$field_name];
						}

						$fields_info[] = $new_field;
					}
					break;

					case SITE_CONTACTS_FIELD_CHECKBOXES: {
						$field_entries = array();

						if (!empty($field['entries']) && is_array($field['entries'])) {
							foreach ($field['entries'] as $entry) {
								$entry_name = 'site_contacts_' . site_contacts_field_type($field) .
									'_' . $field['field_id'] . '_' . $entry['entry_id'];

								if (!empty($form_data[$entry_name])) {
									$new_entry = array();

									$new_entry['entry_id'] = $entry['entry_id'];
									$new_entry['type'] = $entry['type'];
									$new_entry['weight'] = $entry['weight'];
									$new_entry['value'] = $form_data[$entry_name];

									$field_entries[] = $new_entry;
								}
							}
						}

						$new_field = array();

						$new_field['field_id'] = $field['field_id'];
						$new_field['type'] = $field['type'];
						$new_field['weight'] = $field['weight'];
						$new_field['required'] = $field['required'];
						$new_field['label'] = $field['label'];

						if (!empty($field_entries) && is_array($field_entries) && (count($field_entries) > 0)) {
							$new_field['entries'] = $field_entries;
						}

						$fields_info[] = $new_field;
					}
					break;
				}
			}
		}
	}

	return $fields_info;
}

function site_contacts_validate_regular($contact_form, $fields_info, $messages) {
	$submission_errors = array();

	foreach ($fields_info as $item) {
		switch ($item['type']) {
			case SITE_CONTACTS_FIELD_PHONE:
			case SITE_CONTACTS_FIELD_TEXTFIELD:
			case SITE_CONTACTS_FIELD_TEXTAREA:
			case SITE_CONTACTS_FIELD_DROPDOWN:
			case SITE_CONTACTS_FIELD_RADIOBOXES: {
				if ($item['required']) {
					if (!isset($item['content'])) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['required_field'];

						$submission_errors[] = $error_info;
					}
				}
			}
			break;

			case SITE_CONTACTS_FIELD_EMAIL: {
				$has_error = false;

				if ($item['required']) {
					if (!isset($item['content'])) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['required_field'];

						$submission_errors[] = $error_info;

						$has_error = true;
					}
				}

				if (!$has_error) {
					if ((!empty($item['content'])) && (!is_email($item['content']))) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['invalid_email'];

						$submission_errors[] = $error_info;
					}
				}
			}
			break;

			case SITE_CONTACTS_FIELD_URL: {
				$has_error = false;

				if ($item['required']) {
					if (!isset($item['content'])) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['required_field'];

						$submission_errors[] = $error_info;

						$has_error = true;
					}
				}

				if (!$has_error) {
					if ((!empty($item['content'])) && (filter_var($item['content'], FILTER_VALIDATE_URL) == false)) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['invalid_url'];

						$submission_errors[] = $error_info;
					}
				}
			}
			break;

			case SITE_CONTACTS_FIELD_CHECKBOXES: {
				if ($item['required']) {
					if (empty($item['entries'])) {
						$error_info = array();

						$error_info['field_id'] = $item['field_id'];
						$error_info['message'] = $messages['required_field'];

						$submission_errors[] = $error_info;
					}
				}
			}
			break;
		}
	}

	return $submission_errors;
}

function site_contacts_validate_data($contact_form, $fields_info) {
	$messages = array();

	if ($contact_form && isset($contact_form['form']['config']['messages'])) {
		$messages = wp_parse_args($contact_form['form']['config']['messages'], site_contacts_default_messages());
	} else {
		$messages = site_contacts_default_messages();
	}

	return site_contacts_validate_regular($contact_form, $fields_info, $messages);
}

function site_contacts_send($contact_form, $message) {
	// Send notification by email
	if (!empty($contact_form['form']['config']['notification']['sendto'])) {
		// Mail to field

		$mail_to = $contact_form['form']['config']['notification']['sendto'];

		$mail_to = site_contacts_parse_shortcodes($mail_to);

		// Mail from field

		$mail_from = __('Administrator <[admin_email]>', 'site-contacts');

		if (isset($contact_form['form']['config']['notification']['from'])) {
			$mail_from = $contact_form['form']['config']['notification']['from'];
		}

		$mail_from = site_contacts_parse_shortcodes($mail_from);

		// Subject

		$mail_subject = __('New form submission', 'site-contacts');

		if (isset($contact_form['form']['config']['notification']['subject'])) {
			$mail_subject = $contact_form['form']['config']['notification']['subject'];
		}

		// Headers
		$mail_headers = '';

		if (!empty($mail_from)) {
			$mail_headers = 'From: ' . $mail_from . "\n";
		}

		// Send email

		wp_mail($mail_to, $mail_subject, $message, $mail_headers);

		return true;
	}

	return false;
}

function site_contacts_notify($contact_form, $fields_info) {
	// Construct message body
	$message = '';

	foreach ($fields_info as $item) {
		switch ($item['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
			case SITE_CONTACTS_FIELD_PHONE:
			case SITE_CONTACTS_FIELD_EMAIL:
			case SITE_CONTACTS_FIELD_URL:
			case SITE_CONTACTS_FIELD_DROPDOWN:
			case SITE_CONTACTS_FIELD_TEXTAREA:
			case SITE_CONTACTS_FIELD_RADIOBOXES: {
				$message .= $item['label'] . ":\n";

				if (!empty($item['content'])) {
					$message .= $item['content'] . "\n\n";
				} else {
					$message .= "\n";
				}
			}
			break;

			case SITE_CONTACTS_FIELD_CHECKBOXES: {
				$message .= $item['label'] . ":\n";

				if (!empty($item['entries']) && is_array($item['entries'])) {
					$index = 0;

					foreach ($item['entries'] as $entry) {
						if ($index > 0) {
							$message .= ", ";
						}

						$message .= $entry['value'];

						$index++;
					}
				}

				$message .= "\n\n";
			}
			break;
		}
	}

	return site_contacts_send($contact_form, $message);
}

function site_contacts_do_shortcode($text) {
	if (stripos($text, SITE_CONTACTS_FORM_SHORTCODE) !== FALSE) {
		return do_shortcode($text);
	}

	return $text;
}

function site_contacts_media_inline() {
	$contacts_js_data = array();

	$contacts = site_contacts_get_all();

	foreach ($contacts as $item) {
		$new_item = array();

		$new_item['id'] = $item['id'];

		if (!empty($item['title'])) {
			$new_item['title'] = __('ID #', 'site-contacts') . $item['id'] . ': ' . esc_html(stripslashes($item['title']));
		} else {
			$new_item['title'] = __('ID #', 'site-contacts') . $item['id'] . ': ' . __('(No title)', 'site-contacts');
		}

		$contacts_js_data[] = $new_item;
	}

?>
<div class="site-contacts-popup site-contacts-inactive">
	<div class="site-contacts-popup-content">
		<div class="site-contacts-popup-close"><a href="#" class="dashicons dashicons-no"></a></div>
		<div class="site-contacts-popup-title">Insert contact form</div>
		<?php if ($contacts && count($contacts) > 0) { ?>
		<div class="site-contacts-popup-description">Please select your contact form in the list below</div>
		<select class="site-contacts-select"><option value="0">---</option></select>
		<div class="site-contacts-popup-button">
			<button class="site-contacts-button-insert button button-primary">Insert shortcode</button>
			<button class="site-contacts-button-cancel button">Cancel</button>
		</div>
		<?php } else { ?>
		<div class="site-contacts-popup-description">Sorry, no contact forms have been created yet.</div>
		<div class="site-contacts-popup-button">
			<button class="site-contacts-button-cancel button">Cancel</button>
		</div>
		<?php } ?>
	</div>
</div>
<script type="text/javascript">
var site_contacts_media_ids = <?php echo json_encode($contacts_js_data) ?>;
</script>
<?php
}

function site_contacts_field_class($field) {
	if (!empty($field['type'])) {
		switch ($field['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
				return 'site-contacts-field-textfield';
				break;

			case SITE_CONTACTS_FIELD_PHONE:
				return 'site-contacts-field-phone';
				break;

			case SITE_CONTACTS_FIELD_EMAIL:
				return 'site-contacts-field-email';
				break;

			case SITE_CONTACTS_FIELD_URL:
				return 'site-contacts-field-url';
				break;

			case SITE_CONTACTS_FIELD_DROPDOWN:
				return 'site-contacts-field-dropdown';
				break;

			case SITE_CONTACTS_FIELD_CHECKBOXES:
				return 'site-contacts-field-checkboxes';
				break;

			case SITE_CONTACTS_FIELD_RADIOBOXES:
				return 'site-contacts-field-radioboxes';
				break;

			case SITE_CONTACTS_FIELD_TEXTAREA:
				return 'site-contacts-field-textarea';
				break;

			case SITE_CONTACTS_FIELD_SUBMIT_BUTTON:
				return 'site-contacts-field-submit-button';
				break;
		}
	}

	return '';
}

function site_contacts_render_textfield($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_text_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<input type="text" class="site-contacts-input-text" ' . $required_data_attribute . 'id="site_contacts_text_' . $field['field_id'] . '" name="site_contacts_text_' . $field['field_id'] . '" value="" />';

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_phone($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_phone_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<input type="tel" class="site-contacts-input-tel" ' . $required_data_attribute . 'id="site_contacts_phone_' . $field['field_id'] . '" name="site_contacts_phone_' . $field['field_id'] . '" value="" />';

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_email($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_email_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<input type="email" class="site-contacts-input-email" ' . $required_data_attribute . 'id="site_contacts_email_' . $field['field_id'] . '" name="site_contacts_email_' . $field['field_id'] . '" value="" />';

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_dropdown($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_dropdown_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<select class="site-contacts-select" ' . $required_data_attribute . 'id="site_contacts_dropdown_' . $field['field_id'] . '" name="site_contacts_dropdown_' . $field['field_id'] . '">';

	if (!empty($field['entries']) && is_array($field['entries'])) {
		foreach ($field['entries'] as $entry) {
			$result .= '
				<option>' . esc_html($entry['title']) . '</option>';
		}
	}

	$result .= '
			</select>';

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_checkboxes($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$entry_index = 0;

	if (!empty($field['entries']) && is_array($field['entries'])) {
		foreach ($field['entries'] as $entry) {
			$result .= '
			<div class="site-contacts-field-option"><label><input type="checkbox" class="site-contacts-input-checkbox" ' . $required_data_attribute . 'id="site_contacts_checkbox_' . $field['field_id'] . '_' . $entry['entry_id'] . '" name="site_contacts_checkbox_' . $field['field_id'] . '_' . $entry['entry_id'] . '" value="' . esc_html($entry['title']) . '"/>' . esc_html($entry['title']) . '</label></div>';

			$entry_index++;
		}
	}

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_radioboxes($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$entry_index = 0;

	if (!empty($field['entries']) && is_array($field['entries'])) {
		foreach ($field['entries'] as $entry) {
			$result .= '
			<div class="site-contacts-field-option"><label><input type="radio" class="site-contacts-input-radio" ' . $required_data_attribute . 'name="site_contacts_radio_' . $field['field_id'] . '" value="' . esc_html($entry['title']) . '" />' . esc_html($entry['title']) . '</label></div>';

			$entry_index++;
		}
	}

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_textarea($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_textarea_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<textarea class="site-contacts-textarea" ' . $required_data_attribute . 'id="site_contacts_textarea_' . $field['field_id'] . '" name="site_contacts_textarea_' . $field['field_id'] . '"></textarea>';

	$result .= '
		</div>';

	return $result;
}

function site_contacts_render_submit($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<input type="submit" class="site-contacts-input-submit" name="site_contacts_submit_' . $field['field_id'] . '" onclick="return site_contacts_submit_form(site_contacts_form_' . $form_id . '_data, this, ' . $form_id . ', \'' . admin_url('admin-ajax.php') . '\');" value="' . esc_html($field['title']) . '" />';

	$result .= '
		</div>';

	return $result;
}

function site_contacts_render_url($form_id, $field) {
	$result = '';

	$required_sign = '';
	$required_data_attribute = '';

	if ($field['required']) {
		$required_data_attribute = 'data-site-contacts-required="true" ';
		$required_sign = '<span class="site-contacts-field-required">*</span>';
	}

	if (!empty($field['label'])) {
		$result .= '
			<div class="site-contacts-field-label"><label for="site_contacts_url_' . $field['field_id'] . '" class="site-contacts-label-content">' . esc_html($field['label']) . $required_sign . '</label></div>';
	}

	$result .= '
			<div class="site-contacts-field-body">';

	$result .= '
			<input type="url" class="site-contacts-input-url" ' . $required_data_attribute . 'id="site_contacts_url_' . $field['field_id'] . '" name="site_contacts_url_' . $field['field_id'] . '" value="" />';

	$result .= '
			</div>';

	return $result;
}

function site_contacts_render_field($form_id, $field) {
	$result = '';

	if (!empty($field['type'])) {
		switch ($field['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
					return site_contacts_render_textfield($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_PHONE:
					return site_contacts_render_phone($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_EMAIL:
					return site_contacts_render_email($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_DROPDOWN:
					return site_contacts_render_dropdown($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_CHECKBOXES:
					return site_contacts_render_checkboxes($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_RADIOBOXES:
					return site_contacts_render_radioboxes($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_TEXTAREA:
					return site_contacts_render_textarea($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_SUBMIT_BUTTON:
					return site_contacts_render_submit($form_id, $field);
				break;

			case SITE_CONTACTS_FIELD_URL:
					return site_contacts_render_url($form_id, $field);
				break;
		}
	}

	return $result;
}

function site_contacts_render($contact_form) {
	$result = '';

	$messages = array();

	if ($contact_form && isset($contact_form['form']['config']['messages'])) {
		$messages = wp_parse_args($contact_form['form']['config']['messages'], site_contacts_default_messages());
	} else {
		$messages = site_contacts_default_messages();
	}

	$result .= '
<script type="text/javascript">
var site_contacts_form_' . $contact_form['form']['id'] . '_data = {
	"option_messages_success" : "' . esc_html($messages['success']) . '",
	"option_messages_failure" : "' . esc_html($messages['failure']) . '",
	"option_messages_validation_failed" : "' . esc_html($messages['validation_failed']) . '",
	"option_messages_required_field" : "' . esc_html($messages['required_field']) . '",
	"option_messages_required_option" : "' . esc_html($messages['required_option']) . '"
};
</script>
<div id="site_contacts_' . $contact_form['form']['id'] . '" class="site-contacts site-contacts-' . $contact_form['form']['id'] . '">
	<form method="post" novalidate="novalidate" id="site_contacts_form_' . $contact_form['form']['id'] . '" name="site_contacts_form_' . $contact_form['form']['id'] . '" class="site-contacts-form site-contacts-form-' . $contact_form['form']['id'] . '">
		<div class="site-contacts-form-body">';

	if (!empty($contact_form['form']['title'])) {
		$result .= '
			<div class="site-contacts-title">' . esc_html($contact_form['form']['title']) . '</div>';
	}

	$result .= '
			<div class="site-contacts-form-content">';

	if (!empty($contact_form['fields']) && is_array($contact_form['fields'])) {
		foreach ($contact_form['fields'] as $item) {
			$required_css_classes = site_contacts_field_class($item);

			if ($item['required']) {
				$required_css_classes .= ' site-contacts-field-required';
			}

			$required_css_classes .= ' site-contacts-field-fullwidth';

			$result .= '
				<div id="site_contacts_field_' . $contact_form['form']['id'] . '_' . $item['field_id'] . '" class="site-contacts-field ' . $required_css_classes . '">';

			$result .= site_contacts_render_field($contact_form['form']['id'], $item);

			$result .= '
				</div>';
		}
	}

	$result .= '
			</div>
		</div>
	</form>
</div>';

	return $result;
}

function site_contacts_show($atts, $content = null) {
	extract(shortcode_atts(array('id' => '0'), $atts));

	if (!empty($id)) {
		$contact_form = site_contacts_get($id);

		if (!empty($contact_form) && !empty($contact_form['form'])) {
			return site_contacts_render($contact_form);
		}
	}

	return '<p><b>' . __('Error: no contact form found with the specified ID', 'site-contacts') . '</b></p>';
}

function site_contacts_unserialize_form(&$form) {
	if (!empty($form['config'])) {
		$config = unserialize($form['config']);

		if ((!empty($config)) && is_array($config)) {
			$form['config'] = $config;
		} else {
			$form['config'] = null;
		}
	} else {
		$form['config'] = null;
	}

	if (!empty($form['style'])) {
		$style = unserialize($form['style']);

		if ((!empty($style)) && is_array($style)) {
			$form['style'] = $style;
		} else {
			$form['style'] = null;
		}
	} else {
		$form['style'] = null;
	}
}

function site_contacts_get_fields($id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$contact_form_fields = $wpdb->get_results($wpdb->prepare('
		SELECT * FROM `' . $table_prefix . 'fields`
		WHERE form_id = %d
		ORDER BY weight ASC;', intval($id)), ARRAY_A);

	if (!empty($contact_form_fields) && is_array($contact_form_fields)) {
		foreach ($contact_form_fields as $key => $item) {
			if (!empty($contact_form_fields[$key]['config'])) {
				$config = unserialize($contact_form_fields[$key]['config']);

				if ((!empty($config)) && is_array($config)) {
					$contact_form_fields[$key]['config'] = $config;
				} else {
					$contact_form_fields[$key]['config'] = null;
				}
			} else {
				$contact_form_fields[$key]['config'] = null;
			}

			if (($item['type'] == SITE_CONTACTS_FIELD_DROPDOWN) ||
				($item['type'] == SITE_CONTACTS_FIELD_CHECKBOXES) ||
				($item['type'] == SITE_CONTACTS_FIELD_RADIOBOXES)) {
				$contact_form_field_entries = $wpdb->get_results($wpdb->prepare('
					SELECT * FROM `' . $table_prefix . 'field_entries`
					WHERE field_id = %d
					ORDER BY weight ASC;', intval($item['field_id'])), ARRAY_A);

				if (!empty($contact_form_field_entries) && is_array($contact_form_field_entries)) {
					$contact_form_fields[$key]['entries'] = $contact_form_field_entries;
				}
			}
		}

		return $contact_form_fields;
	}

	return null;
}

function site_contacts_get($id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$contact_form_base = $wpdb->get_row($wpdb->prepare('
		SELECT * FROM `' . $table_prefix . 'forms`
		WHERE id = %d;', intval($id)), ARRAY_A);

	if (!empty($contact_form_base)) {
		site_contacts_unserialize_form($contact_form_base);

		$contact_form = array();

		$contact_form['form'] = $contact_form_base;

		$contact_form_fields = site_contacts_get_fields($id);

		if (!empty($contact_form_fields)) {
			$contact_form['fields'] = $contact_form_fields;
		}

		return $contact_form;
	}

	return null;
}

function site_contacts_get_range($current_page, $items_per_page) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$contact_forms = $wpdb->get_results($wpdb->prepare('
		SELECT * FROM `' . $table_prefix . 'forms`
		ORDER BY id ASC
		LIMIT %d, %d;', $current_page * $items_per_page, $items_per_page), ARRAY_A);

	if (!empty($contact_forms) && is_array($contact_forms)) {
		foreach ($contact_forms as $form_key => $form) {
			site_contacts_unserialize_form($contact_forms[$form_key]);
		}
	}

	return $contact_forms;
}

function site_contacts_get_all() {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$contact_forms = $wpdb->get_results('SELECT * FROM `' . $table_prefix . 'forms` ORDER BY id ASC;', ARRAY_A);

	if (!empty($contact_forms) && is_array($contact_forms)) {
		foreach ($contact_forms as $form_key => $form) {
			site_contacts_unserialize_form($contact_forms[$form_key]);
		}
	}

	return $contact_forms;
}

function site_contacts_get_all_count() {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	return $wpdb->get_var('SELECT COUNT(id) FROM `' . $table_prefix . 'forms`;');
}

function site_contacts_delete_form($id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'forms` WHERE id = %d;', intval($id)));
	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'fields` WHERE form_id = %d;', intval($id)));
	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'field_entries` WHERE form_id = %d;', intval($id)));
}

function site_contacts_get_post_fields() {
	$post_fields = array();

	foreach ($_POST as $post_key => $post_value) {
		if (strpos($post_key, 'site_contacts_option_') !== FALSE) {
			$field_info = str_replace('site_contacts_option_', '', $post_key);

			$field_info_list = explode('_', $field_info);

			if ($field_info_list && is_array($field_info_list) && (count($field_info_list) >= 2)) {
				$field_index = intval(array_pop($field_info_list));
				$field_name = implode('_', $field_info_list);

				$post_fields[$field_index][$field_name] = wp_unslash($post_value);
			}
		}
	}

	return $post_fields;
}

function site_contacts_standard_options($post_value) {
	$processed_item = array();

	if (isset($post_value['field_id'])) {
		$processed_item['field_id'] = intval($post_value['field_id']);
	}

	if (isset($post_value['type'])) {
		$processed_item['type'] = intval($post_value['type']);
	}

	if (isset($post_value['weight'])) {
		$processed_item['weight'] = intval($post_value['weight']);
	} else {
		$processed_item['weight'] = 0;
	}

	if (isset($post_value['required']) && site_contacts_true($post_value['required'])) {
		$processed_item['required'] = 1;
	} else {
		$processed_item['required'] = 0;
	}

	if (!empty($post_value['label'])) {
		$processed_item['label'] = $post_value['label'];
	} else {
		$processed_item['label'] = '';
	}

	if (!empty($post_value['title'])) {
		$processed_item['title'] = $post_value['title'];
	} else {
		$processed_item['title'] = '';
	}

	if (!empty($post_value['value'])) {
		$processed_item['value'] = $post_value['value'];
	} else {
		$processed_item['value'] = '';
	}

	$processed_item['config'] = array();

	return $processed_item;
}

function site_contacts_process_fields($post_fields) {
	$processed_fields = array();

	if ((!empty($post_fields)) && is_array($processed_fields)) {
		foreach ($post_fields as $post_key => $post_value) {
			if (!empty($post_value['type'])) {
				$field_type = intval($post_value['type']);

				switch ($field_type) {
					case SITE_CONTACTS_FIELD_TEXTFIELD:
					case SITE_CONTACTS_FIELD_PHONE:
					case SITE_CONTACTS_FIELD_EMAIL:
					case SITE_CONTACTS_FIELD_URL:
					case SITE_CONTACTS_FIELD_TEXTAREA:
					case SITE_CONTACTS_FIELD_SUBMIT_BUTTON: {
						$processed_item = site_contacts_standard_options($post_value);

						$processed_fields[$post_key] = $processed_item;
					}
					break;

					case SITE_CONTACTS_FIELD_DROPDOWN:
					case SITE_CONTACTS_FIELD_CHECKBOXES:
					case SITE_CONTACTS_FIELD_RADIOBOXES: {
						$processed_item = site_contacts_standard_options($post_value);

						if (isset($post_value['entries'])) {
							$entries = explode("\n", str_replace("\r", "", $post_value['entries']));

							if (!empty($entries) && is_array($entries)) {
								$processed_item['entries'] = array();

								foreach ($entries as $entry_value) {
									if (!empty($entry_value)) {
										$new_entry = array();

										$new_entry['title'] = $entry_value;

										$processed_item['entries'][] = $new_entry;
									}
								}
							}
						}

						$processed_fields[$post_key] = $processed_item;
					}
					break;
				}
			}
		}
	}

	return $processed_fields;
}

function site_contacts_get_post_config_field(&$config, $scope, $target_field, $post_field) {
	if (!empty($_POST[$post_field])) {
		$config[$scope][$target_field] = wp_unslash($_POST[$post_field]);
	} else {
		$config[$scope][$target_field] = '';
	}
}

function site_contacts_get_post_config_data() {
	$config = array();

	site_contacts_get_post_config_field($config, 'notification', 'sendto', 'site_contacts_notification_sendto');
	site_contacts_get_post_config_field($config, 'notification', 'from', 'site_contacts_notification_from');
	site_contacts_get_post_config_field($config, 'notification', 'subject', 'site_contacts_notification_subject');
	site_contacts_get_post_config_field($config, 'messages', 'success', 'site_contacts_messages_success');
	site_contacts_get_post_config_field($config, 'messages', 'failure', 'site_contacts_messages_failure');
	site_contacts_get_post_config_field($config, 'messages', 'validation_failed', 'site_contacts_messages_validation_failed');
	site_contacts_get_post_config_field($config, 'messages', 'required_field', 'site_contacts_messages_required_field');
	site_contacts_get_post_config_field($config, 'messages', 'required_option', 'site_contacts_messages_required_option');
	site_contacts_get_post_config_field($config, 'messages', 'invalid_email', 'site_contacts_messages_invalid_email');
	site_contacts_get_post_config_field($config, 'messages', 'invalid_url', 'site_contacts_messages_invalid_url');

	return $config;
}

function site_contacts_update_form($form_id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$config = site_contacts_get_post_config_data();

	$wpdb->query($wpdb->prepare('
		UPDATE `' . $table_prefix . 'forms`
			SET `title` = %s, `config` = %s
		WHERE
			id = ' . $form_id . ';', wp_unslash($_POST['site_contacts_title']), serialize($config)));
}

function site_contacts_update_single_field($form_id, $item) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query($wpdb->prepare('
		UPDATE `' . $table_prefix . 'fields`
			SET
				`weight` = %d, `required` = %d, `label` = %s, `title` = %s, `value` = %s, `config` = %s
		WHERE
			field_id = %d;', $item['weight'], $item['required'], $item['label'], $item['title'], $item['value'], serialize($item['config']), $item['field_id']));
}

function site_contacts_check_fields($form_id, $post_fields) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	// We need to check if we have to delete existing fields
	$fields = $wpdb->get_results($wpdb->prepare('
		SELECT * FROM `' . $table_prefix . 'fields`
		WHERE `form_id` = %d;', $form_id), ARRAY_A);

	if (!empty($fields) && is_array($fields)) {
		foreach ($fields as $item) {
			$found_field = false;

			foreach ($post_fields as $post_item) {
				if ((!empty($post_item['field_id'])) && (!empty($item['field_id']))) {
					if (intval($item['field_id']) == intval($post_item['field_id'])) {
						$found_field = true;
					}
				}
			}

			if (!$found_field) {
				$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'fields` WHERE field_id = %d;', intval($item['field_id'])));
				$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'field_entries` WHERE field_id = %d;', intval($item['field_id'])));
			}
		}
	}
}

function site_contacts_update_fields($form_id, $post_fields) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$processed_fields = site_contacts_process_fields($post_fields);

	foreach ($processed_fields as $item) {
		if (!empty($item['field_id'])) {
			site_contacts_update_single_field($form_id, $item);

			if (($item['type'] == SITE_CONTACTS_FIELD_DROPDOWN) ||
				($item['type'] == SITE_CONTACTS_FIELD_CHECKBOXES) ||
				($item['type'] == SITE_CONTACTS_FIELD_RADIOBOXES)) {
				// Delete existing entries
				$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'field_entries` WHERE field_id = %d;', $item['field_id']));

				// Resave entries for case if they are edited
				if (!empty($item['entries']) && is_array($item['entries'])) {
					$index = 1;

					foreach ($item['entries'] as $entry) {
						if (!empty($entry['title'])) {
							$wpdb->query($wpdb->prepare('
								INSERT INTO `' . $table_prefix . 'field_entries`
									(`field_id`, `form_id`, `type`, `weight`, `name`, `title`, `value`, `data`, `config`)
								VALUES
									(%d, %d, %d, %d, "", %s, "", "", "");', $item['field_id'], $form_id, SITE_CONTACTS_FIELD_ENTRY_STANDARD, $index, $entry['title']));

							$index++;
						}
					}
				}
			}
		} else {
			$new_field_id = site_contacts_insert_single_field($form_id, $item);

			if (($item['type'] == SITE_CONTACTS_FIELD_DROPDOWN) ||
				($item['type'] == SITE_CONTACTS_FIELD_CHECKBOXES) ||
				($item['type'] == SITE_CONTACTS_FIELD_RADIOBOXES)) {
				// Resave entries for case if they are edited
				if (!empty($item['entries']) && is_array($item['entries'])) {
					$index = 1;

					foreach ($item['entries'] as $entry) {
						if (!empty($entry['title'])) {
							$wpdb->query($wpdb->prepare('
								INSERT INTO `' . $table_prefix . 'field_entries`
									(`field_id`, `form_id`, `type`, `weight`, `name`, `title`, `value`, `data`, `config`)
								VALUES
									(%d, %d, %d, %d, "", %s, "", "", "");', $new_field_id, $form_id, SITE_CONTACTS_FIELD_ENTRY_STANDARD, $index, $entry['title']));

							$index++;
						}
					}
				}
			}
		}
	}
}

function site_contacts_insert_form() {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$config = site_contacts_get_post_config_data();

	$wpdb->query($wpdb->prepare('
		INSERT INTO `' . $table_prefix . 'forms`
			(`created`, `status`, `type`, `title`, `description`, `config`, `style`)
		VALUES
			(%s, %d, %d, %s, "", %s, "");', gmdate('Y-m-d H:i:s', time()), SITE_CONTACTS_TYPE_STANDARD_FORM, SITE_CONTACTS_FORM_STATUS_PUBLISHED, wp_unslash($_POST['site_contacts_title']), serialize($config)));

	return $wpdb->insert_id;
}

function site_contacts_insert_single_field($new_form_id, $item) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query($wpdb->prepare('
		INSERT INTO `' . $table_prefix . 'fields`
			(`form_id`, `type`, `weight`, `required`, `name`, `label`, `title`, `description`, `placeholder`, `value`, `data`, `config`, `style`)
		VALUES
			(%d, %d, %d, %d, "", %s, %s, "", "", %s, "", %s, "");', $new_form_id, $item['type'], $item['weight'], $item['required'], $item['label'], $item['title'], $item['value'], serialize($item['config'])));

	return $wpdb->insert_id;
}

function site_contacts_insert_fields($new_form_id, $post_fields) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$processed_fields = site_contacts_process_fields($post_fields);

	foreach ($processed_fields as $item) {
		$new_field_id = site_contacts_insert_single_field($new_form_id, $item);

		if (($item['type'] == SITE_CONTACTS_FIELD_DROPDOWN) ||
			($item['type'] == SITE_CONTACTS_FIELD_CHECKBOXES) ||
			($item['type'] == SITE_CONTACTS_FIELD_RADIOBOXES)) {
			if (!empty($item['entries']) && is_array($item['entries'])) {
				$index = 1;

				foreach ($item['entries'] as $entry) {
					if (!empty($entry['title'])) {
						$wpdb->query($wpdb->prepare('
							INSERT INTO `' . $table_prefix . 'field_entries`
								(`field_id`, `form_id`, `type`, `weight`, `name`, `title`, `value`, `data`, `config`)
							VALUES
								(%d, %d, %d, %d, "", %s, "", "", "");', $new_field_id, $new_form_id, SITE_CONTACTS_FIELD_ENTRY_STANDARD, $index, $entry['title']));

						$index++;
					}
				}
			}
		}
	}
}

function site_contacts_proto_textfield($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Label', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-textfield">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_TEXTFIELD . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_textfield_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<input type="text" id="site_contacts_proto_textfield_' . $index . '" class="site-contacts-input-text" value="" disabled="disabled" />
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_phone($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Phone number', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-phone">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_PHONE . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_phone_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<input type="tel" id="site_contacts_proto_phone_' . $index . '" class="site-contacts-input-tel" value="" disabled="disabled" />
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_email($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('E-mail', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-email">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_EMAIL . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_email_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<input type="email" id="site_contacts_proto_email_' . $index . '" class="site-contacts-input-email" value="" disabled="disabled" />
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_dropdown($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Label', 'site-contacts');
	}

	$entries_html = '';
	$entries_text = '';

	if (!empty($field['entries']) && is_array($field['entries'])) {
		$entry_index = 0;

		foreach ($field['entries'] as $entry) {
			$entries_html .= '<option>' . esc_html($entry['title']) . '</option>';

			if ($entry_index > 0) {
				$entries_text .= "\n";
			}

			$entries_text .= $entry['title'];

			$entry_index++;
		}
	} else {
		$entries_html = '<option>' . __('Option #1', 'site-contacts') . '</option><option>' . __('Option #2', 'site-contacts') . '</option><option>' . __('Option #3', 'site-contacts') . '</option>';
		$entries_text = __('Option #1', 'site-contacts') . "\n" . __('Option #2', 'site-contacts') . "\n" . __('Option #3', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-dropdown">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_DROPDOWN . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_dropdown_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<select id="site_contacts_proto_dropdown_' . $index . '" class="site-contacts-select">' . $entries_html . '</select>
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-entries">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_entries_' . $index . '" class="site-contacts-label">' . __('Entries:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<textarea class="site-contacts-textarea" id="site_contacts_option_entries_' . $index . '" name="site_contacts_option_entries_' . $index . '">' . esc_html($entries_text) . '</textarea>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_checkboxes($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Label', 'site-contacts');
	}

	$entries_html = '';
	$entries_text = '';

	if (!empty($field['entries']) && is_array($field['entries'])) {
		$entry_index = 0;

		foreach ($field['entries'] as $entry) {
			$entries_html .= '<p><label><input type="checkbox" class="site-contacts-input-checkbox" />' . esc_html($entry['title']) . '</label></p>';

			if ($entry_index > 0) {
				$entries_text .= "\n";
			}

			$entries_text .= $entry['title'];

			$entry_index++;
		}
	} else {
		$entries_html =	'<p><label><input type="checkbox" class="site-contacts-input-checkbox" />' . __('Option #1', 'site-contacts') . '</label></p>' .
					'<p><label><input type="checkbox" class="site-contacts-input-checkbox" />' . __('Option #2', 'site-contacts') . '</label></p>' .
					'<p><label><input type="checkbox" class="site-contacts-input-checkbox" />' . __('Option #3', 'site-contacts') . '</label></p>';

		$entries_text = __('Option #1', 'site-contacts') . "\n" . __('Option #2', 'site-contacts') . "\n" . __('Option #3', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-checkboxes">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_CHECKBOXES . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">' . $entries_html . '</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-entries">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_entries_' . $index . '" class="site-contacts-label">' . __('Entries:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<textarea class="site-contacts-textarea" id="site_contacts_option_entries_' . $index . '" name="site_contacts_option_entries_' . $index . '">' . esc_html($entries_text) . '</textarea>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_radioboxes($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Label', 'site-contacts');
	}

	$entries_html = '';
	$entries_text = '';

	if (!empty($field['entries']) && is_array($field['entries'])) {
		$entry_index = 0;

		foreach ($field['entries'] as $entry) {
			$entries_html .= '<p><label><input type="radio" class="site-contacts-input-radio" name="site_contacts_radio_' . $index . '" />' . esc_html($entry['title']) . '</label></p>';

			if ($entry_index > 0) {
				$entries_text .= "\n";
			}

			$entries_text .= $entry['title'];

			$entry_index++;
		}
	} else {
		$entries_html =	'<p><label><input type="radio" class="site-contacts-input-radio" name="site_contacts_radio_' . $index . '" />' . __('Option #1', 'site-contacts') . '</label></p>' .
					'<p><label><input type="radio" class="site-contacts-input-radio" name="site_contacts_radio_' . $index . '" />' . __('Option #2', 'site-contacts') . '</label></p>' .
					'<p><label><input type="radio" class="site-contacts-input-radio" name="site_contacts_radio_' . $index . '" />' . __('Option #3', 'site-contacts') . '</label></p>';

		$entries_text = __('Option #1', 'site-contacts') . "\n" . __('Option #2', 'site-contacts') . "\n" . __('Option #3', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-radioboxes">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_RADIOBOXES . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">' . $entries_html . '</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-entries">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_entries_' . $index . '" class="site-contacts-label">' . __('Entries:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<textarea class="site-contacts-textarea" id="site_contacts_option_entries_' . $index . '" name="site_contacts_option_entries_' . $index . '">' . esc_html($entries_text) . '</textarea>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_textarea($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Label', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-textarea">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_TEXTAREA . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_textarea_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<textarea id="site_contacts_proto_textarea_' . $index . '" class="site-contacts-textarea" disabled="disabled"></textarea>
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_submit($index, $field = null) {
	$field_id = '';
	$required = false;
	$title = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['title'])) {
		$title = $field['title'];
	} else {
		$title = __('Submit', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-submit-button">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_SUBMIT_BUTTON . '" />
							<div class="site-contacts-field-body">
								<input type="button" class="site-contacts-button site-contacts-input-submit" value="' . esc_html($title) . '" />
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-button-title">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_title_' . $index . '" class="site-contacts-label">' . __('Button title:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_title_' . $index . '" name="site_contacts_option_title_' . $index . '" value="' . esc_html($title) . '" />
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_proto_url($index, $field = null) {
	$field_id = '';
	$required = false;
	$label = '';

	if ((isset($field['field_id'])) && ($field['field_id'])) {
		$field_id = $field['field_id'];
	}

	if ((isset($field['required'])) && ($field['required'])) {
		$required = true;
	}

	if (isset($field['label'])) {
		$label = $field['label'];
	} else {
		$label = __('Web address', 'site-contacts');
	}

	$html = '
					<div class="site-contacts-field site-contacts-field-url">
						<div class="site-contacts-field-prototype">
							<input type="hidden" name="site_contacts_option_field_id_' . $index . '" value="' . $field_id . '" />
							<input type="hidden" name="site_contacts_option_type_' . $index . '" value="' . SITE_CONTACTS_FIELD_URL . '" />
							<div class="site-contacts-field-label">
								<label id="site_contacts_label_' . $index . '" for="site_contacts_proto_url_' . $index . '" class="site-contacts-label">' . esc_html($label) . ($required ? '<span class="site-contacts-required">*</span>' : '') . '</label>
							</div>
							<div class="site-contacts-field-body">
								<input type="url" id="site_contacts_proto_url_' . $index . '" class="site-contacts-input-url" value="" disabled="disabled" />
							</div>
						</div>

						<div class="site-contacts-field-options site-contacts-hidden">
							<div class="site-contacts-option site-contacts-option-type-label">
								<div class="site-contacts-option-label">
									<label for="site_contacts_option_label_' . $index . '" class="site-contacts-label">' . __('Field label:', 'site-contacts') . '</label>
								</div>
								<div class="site-contacts-option-field">
									<input type="text" class="site-contacts-input-text" id="site_contacts_option_label_' . $index . '" name="site_contacts_option_label_' . $index . '" value="' . esc_html($label) . '" />
								</div>
							</div>
							<div class="site-contacts-option site-contacts-option-type-required">
								<div class="site-contacts-option-field">
									<label><input type="checkbox" class="site-contacts-input-checkbox" name="site_contacts_option_required_' . $index . '" ' . ($required ? 'checked="checked" ': '') . '/>' . __('This field is required', 'site-contacts') . '</label>
								</div>
							</div>
							<a href="#" class="site-contacts-close">' . __('Close', 'site-contacts') . '</a>
						</div>
					</div>';

	return $html;
}

function site_contacts_field_wrap($index) {
	$field_wrap = array();

	$field_wrap['before'] = '<div class="site-contacts-form-row">
				<div class="site-contacts-field-control">
					<a href="#" class="site-contacts-configure" title="' . __('Configure', 'site-contacts') . '"></a>
					<a href="#" class="site-contacts-remove" title="' . __('Delete', 'site-contacts') . '"></a>
					<a href="#" class="site-contacts-handle" title="' . __('Move', 'site-contacts') . '"></a>
				</div>
				<div class="site-contacts-meta"><input type="hidden" name="site_contacts_option_weight_' . $index . '" value="' . $index . '" /></div>';

	$field_wrap['after'] = '
			</div>';

	return $field_wrap;
}

function site_contacts_field_proto($index, $field = null) {
	$field_wrap = site_contacts_field_wrap($index);

	if (!empty($field['type'])) {
		switch ($field['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
					return $field_wrap['before'] . site_contacts_proto_textfield($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_PHONE:
					return $field_wrap['before'] . site_contacts_proto_phone($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_EMAIL:
					return $field_wrap['before'] . site_contacts_proto_email($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_DROPDOWN:
					return $field_wrap['before'] . site_contacts_proto_dropdown($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_CHECKBOXES:
					return $field_wrap['before'] . site_contacts_proto_checkboxes($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_RADIOBOXES:
					return $field_wrap['before'] . site_contacts_proto_radioboxes($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_TEXTAREA:
					return $field_wrap['before'] . site_contacts_proto_textarea($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_SUBMIT_BUTTON:
					return $field_wrap['before'] . site_contacts_proto_submit($index, $field) . $field_wrap['after'];
				break;

			case SITE_CONTACTS_FIELD_URL:
					return $field_wrap['before'] . site_contacts_proto_url($index, $field) . $field_wrap['after'];
				break;
		}
	}

	return null;
}

function site_contacts_validate_form() {
	$error = null;

	// We allow empty form for now

	return $error;
}

function site_contacts_field_buttons($field_list) {
	foreach($field_list as $item) {
		if ((!empty($item['label'])) && (!empty($item['id']))) {
?>
					<div class="site-contacts-field-wrapper<?php if (!empty($item['class'])) echo ' ' . $item['class'] ?>">
						<div class="site-contacts-field-container">
							<div class="site-contacts-field-template">
								<div class="site-contacts-template-label"><?php echo $item['label'] ?></div>
								<input type="hidden" value="<?php echo $item['id'] ?>" />
							</div>
						</div>
					</div>
<?php
		}
	}
}

function site_contacts_tab_fields($active, $forms_list_url, $contact_form) {
?>
<div class="site-contacts-main site-contacts-tab-fields<?php if ($active == false){echo ' site-contacts-hidden';}?>">
<?php

	// Prepare form variables
	$form_title = '';

	if (!empty($_POST['site_contacts_title'])) {
		$form_title = esc_html(wp_unslash($_POST['site_contacts_title']));
	} else if ($contact_form != null) {
		$form_title = esc_html($contact_form['form']['title']);
	}

	// Load existing fields
	$fields = array();

	if (!empty($_POST['site_contacts_save_form']) && ($_POST['site_contacts_save_form'] == 'yes')) {
		$post_fields = site_contacts_get_post_fields();

		$fields = site_contacts_process_fields($post_fields);
	} else {
		if (($contact_form != null) && (!empty($contact_form['fields'])) && is_array($contact_form['fields'])) {
			foreach ($contact_form['fields'] as $item) {
				$fields[] = $item;
			}
		}
	}
?>
	<div class="site-contacts-content">
<?php
	if ($contact_form != null) {
?>
				<input type="hidden" name="site_contacts_id" value="<?php echo $contact_form['form']['id']; ?>"/>
<?php
	}
?>
			<div class="site-contacts-text">
				<p><label for="site_contacts_title"><strong><?php _e('Title', 'site-contacts'); ?></strong></label></p>
				<p><input type="text" class="site-contacts-title" id="site_contacts_title" name="site_contacts_title" size="50" value="<?php echo $form_title ?>"/></p>
				<p><label><strong><?php _e('Fields', 'site-contacts'); ?></strong></label></p>
				<div class="site-contacts-form-fields">
<?php

	$index = 1;

	if (!empty($fields)) {
		foreach ($fields as $item) {
			$field_proto = site_contacts_field_proto($index, $item);

			if (!empty($field_proto)) {
				echo $field_proto;
			}

			$index++;
		}
	} else {
?>
					<div class="site-contacts-placeholder"><p><?php _e('Please add some fields to this form by dragging them from the right sidebar.', 'site-contacts'); ?></p></div>
<?php
	}
?>
				</div>
			</div>
			<div class="site-contacts-form-buttons">
				<button class="site-contacts-button site-contacts-save"><?php _e('Save Contact Form', 'site-contacts'); ?></button><button class="site-contacts-button" onclick="return site_contacts_cancel_button('<?php echo $forms_list_url; ?>');"><?php _e('Cancel', 'site-contacts'); ?></button>
			</div>
	</div>
	<div class="site-contacts-bar">
		<div class="site-contacts-widget">
			<div class="site-contacts-header"><?php _e('Fields', 'site-contacts'); ?></div>
			<div class="site-contacts-info">
				<div class="site-contacts-choose-fields">
<?php

					$field_list = array(
							array(
								'id' => SITE_CONTACTS_FIELD_TEXTFIELD,
								'label' => __('Text field', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_PHONE,
								'label' => __('Phone number', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_EMAIL,
								'label' => __('E-mail address', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_URL,
								'label' => __('Web address', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_DROPDOWN,
								'label' => __('Dropdown list', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_CHECKBOXES,
								'label' => __('Checkboxes', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_RADIOBOXES,
								'label' => __('Radioboxes', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_TEXTAREA,
								'label' => __('Textarea', 'site-contacts')
							),
							array(
								'id' => SITE_CONTACTS_FIELD_SUBMIT_BUTTON,
								'label' => __('Submit Button', 'site-contacts')
							),
						);

					site_contacts_field_buttons($field_list);

?>
					<div class="site-contacts-clear"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}

function site_contacts_tab_settings($active, $forms_list_url, $contact_form = null) {
	$notification = array();
	$messages = array();

	if (!empty($_POST['site_contacts_save_form']) && ($_POST['site_contacts_save_form'] == 'yes')) {
		$post_config = site_contacts_get_post_config_data();

		$notification = wp_parse_args($post_config['notification'], site_contacts_default_notification());
		$messages = wp_parse_args($post_config['messages'], site_contacts_default_messages());
	} else {
		if ($contact_form && isset($contact_form['form']['config']['notification'])) {
			$notification = wp_parse_args($contact_form['form']['config']['notification'], site_contacts_default_notification());
		} else {
			$notification = site_contacts_default_notification();
		}

		if ($contact_form && isset($contact_form['form']['config']['messages'])) {
			$messages = wp_parse_args($contact_form['form']['config']['messages'], site_contacts_default_messages());
		} else {
			$messages = site_contacts_default_messages();
		}
	}

?>
<div class="site-contacts-main site-contacts-tab-settings<?php if ($active == false){echo ' site-contacts-hidden';}?>">
	<div class="site-contacts-text">
		<input type="hidden" name="site_contacts_save_form_settings" value="yes" />
		<p><label for="site_contacts_notification_sendto"><strong><?php _e('Notification recipients', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_notification_sendto" name="site_contacts_notification_sendto" value="<?php echo esc_html($notification['sendto']) ?>" /></p>
		<p><label for="site_contacts_notification_from"><strong><?php _e('Notification sender', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_notification_from" name="site_contacts_notification_from" value="<?php echo esc_html($notification['from']) ?>" /></p>
		<p><label for="site_contacts_notification_subject"><strong><?php _e('Notification subject', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_notification_subject" name="site_contacts_notification_subject" value="<?php echo esc_html($notification['subject']) ?>" /></p>
		<p><label for="site_contacts_messages_success"><strong><?php _e('Successful submission message', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_success" name="site_contacts_messages_success" value="<?php echo esc_html($messages['success']) ?>" /></p>
		<p><label for="site_contacts_messages_failure"><strong><?php _e('Unexpected error has occurred', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_failure" name="site_contacts_messages_failure" value="<?php echo esc_html($messages['failure']) ?>" /></p>
		<p><label for="site_contacts_messages_validation_failed"><strong><?php _e('Validation error has occurred', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_validation_failed" name="site_contacts_messages_validation_failed" value="<?php echo esc_html($messages['validation_failed']) ?>" /></p>
		<p><label for="site_contacts_messages_required_field"><strong><?php _e('Required field is not specified', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_required_field" name="site_contacts_messages_required_field" value="<?php echo esc_html($messages['required_field']) ?>" /></p>
		<p><label for="site_contacts_messages_required_option"><strong><?php _e('Required option is not specified', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_required_option" name="site_contacts_messages_required_option" value="<?php echo esc_html($messages['required_option']) ?>" /></p>
		<p><label for="site_contacts_messages_invalid_email"><strong><?php _e('E-mail is not in valid format', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_invalid_email" name="site_contacts_messages_invalid_email" value="<?php echo esc_html($messages['invalid_email']) ?>" /></p>
		<p><label for="site_contacts_messages_invalid_url"><strong><?php _e('URL is not in valid format', 'site-contacts') ?></strong></label></p>
		<p><input type="text" class="site-contacts-input-text" id="site_contacts_messages_invalid_url" name="site_contacts_messages_invalid_url" value="<?php echo esc_html($messages['invalid_url']) ?>" /></p>
	</div>
	<div class="site-contacts-form-buttons">
		<button class="site-contacts-button site-contacts-save"><?php _e('Save Contact Form', 'site-contacts'); ?></button><button class="site-contacts-button" onclick="return site_contacts_cancel_button('<?php echo $forms_list_url; ?>');"><?php _e('Cancel', 'site-contacts'); ?></button>
	</div>
</div>
<?php
}

function site_contacts_proto_regular($fields_proto) {
	$fields_proto[SITE_CONTACTS_FIELD_TEXTFIELD] = site_contacts_proto_textfield(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_PHONE] = site_contacts_proto_phone(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_EMAIL] = site_contacts_proto_email(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_DROPDOWN] = site_contacts_proto_dropdown(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_CHECKBOXES] = site_contacts_proto_checkboxes(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_RADIOBOXES] = site_contacts_proto_radioboxes(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_TEXTAREA] = site_contacts_proto_textarea(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_SUBMIT_BUTTON] = site_contacts_proto_submit(SITE_CONTACTS_INDEX_TEMPLATE);
	$fields_proto[SITE_CONTACTS_FIELD_URL] = site_contacts_proto_url(SITE_CONTACTS_INDEX_TEMPLATE);

	return $fields_proto;
}

function site_contacts_proto_scripts() {
	$field_wrap = site_contacts_field_wrap(SITE_CONTACTS_INDEX_TEMPLATE);

	$fields_proto = array();

	if ((!empty($field_wrap['before'])) && (!empty($field_wrap['after']))) {
		$fields_proto['before'] = $field_wrap['before'];
		$fields_proto['after'] = $field_wrap['after'];
	}

	$fields_proto = site_contacts_proto_regular($fields_proto);

?>
<script type="text/javascript">
	var site_contacts_fields_proto = <?php echo json_encode($fields_proto); ?>;
</script>
<?php
}

function site_contacts_edit_scripts() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	function site_contacts_get_next_id() {
		var next_id = 1;

		$('div.site-contacts-form-row div.site-contacts-meta input').each(function(index, element) {
			var field_name = $(element).attr('name');

			if (field_name && field_name.length > 0) {
				var field_weight = parseInt(field_name.replace('site_contacts_option_weight_', ''));

				if (field_weight >= next_id) {
					next_id = field_weight + 1;
				}
			}
		});

		return next_id;
	}

	function site_contacts_field_prototype(field_type) {
		var field_html = '';

		var field_unique_id = site_contacts_get_next_id();

		var field_order = $('div.site-contacts-form-row').length + 1;

		if (typeof site_contacts_fields_proto != 'undefined') {
			var field_before = '';
			var field_after = '';

			if (typeof site_contacts_fields_proto.before != 'undefined') {
				field_before = site_contacts_fields_proto.before;
			}

			if (typeof site_contacts_fields_proto.after != 'undefined') {
				field_after = site_contacts_fields_proto.after;
			}

			field_html = field_before + site_contacts_fields_proto[field_type] + field_after;

			field_html = field_html.replace(new RegExp('<?php echo SITE_CONTACTS_INDEX_TEMPLATE ?>', 'g'), field_unique_id);
		}

		return field_html;
	}

	function site_contacts_dropdown_change(sender) {
		var select_element = $(sender).closest('div.site-contacts-form-row').find('div.site-contacts-field-prototype select');

		var select_element_id = select_element.attr('id');

		var new_container = jQuery('<select class="site-contacts-select"></select>');

		if (typeof select_element_id !== 'undefined') {
			new_container.attr('id', select_element_id);
		}

		var options_list = $(sender).val();

		if (options_list && (options_list.length > 0)) {
			options_list = options_list.replace('/\r/g', '');

			options_list_array = options_list.split('\n');

			for (var index = 0; index < options_list_array.length; ++index) {
				if (options_list_array[index].length > 0) {
					jQuery('<option></option>').text(options_list_array[index]).appendTo(new_container);
				}
			}
		}

		select_element.replaceWith(new_container);
	}

	function site_contacts_checkboxes_change(sender) {
		var container_element = $(sender).closest('div.site-contacts-form-row').find('div.site-contacts-field-prototype div.site-contacts-field-body');

		var new_container = jQuery('<div class="site-contacts-field-body"></div>');

		var options_list = $(sender).val();

		if (options_list && (options_list.length > 0)) {
			options_list = options_list.replace('/\r/g', '');

			options_list_array = options_list.split('\n');

			for (var index = 0; index < options_list_array.length; ++index) {
				if (options_list_array[index].length > 0) {
					var label_element = jQuery('<label></label>').text(options_list_array[index]);

					label_element.prepend('<input type="checkbox" class="site-contacts-input-checkbox" />');

					var p_element = jQuery('<p></p>').append(label_element);

					p_element.appendTo(new_container);
				}
			}
		}

		container_element.replaceWith(new_container);
	}

	function site_contacts_radioboxes_change(sender) {
		var container_element = $(sender).closest('div.site-contacts-form-row').find('div.site-contacts-field-prototype div.site-contacts-field-body');

		var new_container = jQuery('<div class="site-contacts-field-body"></div>');

		var options_list = $(sender).val();

		if (options_list && (options_list.length > 0)) {
			options_list = options_list.replace('/\r/g', '');

			options_list_array = options_list.split('\n');

			field_unique_id = 0;

			var textarea_name = $(sender).attr('name');

			if (textarea_name && (textarea_name.length > 0)) {
				field_unique_id = parseInt(textarea_name.replace('site_contacts_option_entries_', ''));
			}

			for (var index = 0; index < options_list_array.length; ++index) {
				if (options_list_array[index].length > 0) {
					var label_element = jQuery('<label></label>').text(options_list_array[index]);

					label_element.prepend('<input type="radio" class="site-contacts-input-radio" name="site_contacts_radio_' + field_unique_id + '" />');

					var p_element = jQuery('<p></p>').append(label_element);

					p_element.appendTo(new_container);
				}
			}
		}

		container_element.replaceWith(new_container);
	}

	$('div.site-contacts-choose-fields div.site-contacts-field-container').click(function() {
		var field_type_element = $(this).find('input');

		if (field_type_element.length > 0) {
			if ($('div.site-contacts-form-fields div.site-contacts-placeholder').length > 0) {
				$('div.site-contacts-form-fields div.site-contacts-placeholder').remove();
			}

			var field_type = parseInt(field_type_element.val());

			$('div.site-contacts-form-fields').append(site_contacts_field_prototype(field_type));
		}
	});

	$('div.site-contacts-form-fields').sortable({
		items: 'div.site-contacts-form-row',
		axis: 'y',
		handle: '.site-contacts-handle',
		receive: function (event, ui) {
			$('div.site-contacts-form-fields div.site-contacts-field-container').each(function(index, element) {
				var field_type_element = $(this).find('input');

				if (field_type_element.length > 0) {
					var field_type = parseInt(field_type_element.val());

					var field_html = site_contacts_field_prototype(field_type);

					$(this).replaceWith(field_html);
				}
			});
		},
		over: function(event, ui) {
			if ($('div.site-contacts-form-fields div.site-contacts-placeholder').length > 0) {
				$('div.site-contacts-form-fields div.site-contacts-placeholder').remove();
			}
		},
		out: function(event, ui) {
			if ($('div.site-contacts-form-fields div.site-contacts-form-row').length <= 0) {
				$('div.site-contacts-form-fields').append('<div class="site-contacts-placeholder"><p><?php _e("Please add some fields to this form by dragging them from the right sidebar.", "site-contacts"); ?></p></div>');
			}
		},
		stop: function(event, ui) {
			$('div.site-contacts-form-fields div.site-contacts-form-row').each(function(index, element) {
				$(this).find('div.site-contacts-meta input').val(index);
			});
		}
	});

	$('div.site-contacts-field-container').draggable({
		connectToSortable: 'div.site-contacts-form-fields',
		revert: 'invalid',
		helper: 'clone',
	});

	$('div.site-contacts-form-fields').on('click', 'a.site-contacts-configure', function(event) {
		event.preventDefault();

		$(this).toggleClass('site-contacts-active');

		$(this).closest('div.site-contacts-form-row').find('div.site-contacts-field-options').toggleClass('site-contacts-hidden');
	});

	$('div.site-contacts-form-fields').on('click', 'a.site-contacts-remove', function(event) {
		event.preventDefault();

		if (confirm('<?php _e("Are you sure?", "site-contacts") ?>')) {
			if ($(this).closest('div.site-contacts-form-row').length > 0) {
				$(this).closest('div.site-contacts-form-row').remove();

				if ($('div.site-contacts-form-fields div.site-contacts-form-row').length <= 0) {
					$('div.site-contacts-form-fields').append('<div class="site-contacts-placeholder"><p><?php _e("Please add some fields to this form by dragging them from the right sidebar.", "site-contacts"); ?></p></div>');
				}
			}
		}
	});

	$('div.site-contacts-form-fields').on('click', 'a.site-contacts-handle', function(event) {
		event.preventDefault();
	});

	$('div.site-contacts-form-fields').on('click', 'a.site-contacts-close', function(event) {
		event.preventDefault();

		var form_row = $(this).closest('div.site-contacts-form-row');

		form_row.find('div.site-contacts-field-control a.site-contacts-configure').removeClass('site-contacts-active');

		form_row.find('div.site-contacts-field-options').addClass('site-contacts-hidden');
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-option-type-label input', function(event) {
		var new_value = $(this).val();

		var form_row = $(this).closest('div.site-contacts-form-row');

		form_row.find('div.site-contacts-field-label label').text(new_value);

		if (form_row.find('div.site-contacts-option-type-required input').is(':checked')) {
			if (!form_row.find('div.site-contacts-field-label label').hasClass('site-contacts-optional')) {
				form_row.find('div.site-contacts-field-label label').append('<span class="site-contacts-required">*</span>');
			}
		}
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-option-type-sublabel input', function(event) {
		var new_value = $(this).val();

		var form_row = $(this).closest('div.site-contacts-form-row');

		var element_id = $(this).attr('id').replace('site_contacts_option_', 'site_contacts_label_');

		form_row.find('div.site-contacts-field-sublabel label#' + element_id).text(new_value);
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-option-type-button-title input', function(event) {
		var new_value = $(this).val();

		$(this).closest('div.site-contacts-form-row').find('div.site-contacts-field-prototype div.site-contacts-field-body input').val(new_value);
	});

	$('div.site-contacts-form-fields').on('click', 'div.site-contacts-option-type-required input', function(event) {
		if ($(this).is(':checked')) {
			$(this).closest('div.site-contacts-form-row').find('div.site-contacts-field-label label').each(function() {
				if (!$(this).hasClass('site-contacts-optional')) {
					$(this).append('<span class="site-contacts-required">*</span>');
				}
			});
		} else {
			$(this).closest('div.site-contacts-form-row').find('div.site-contacts-field-label label span.site-contacts-required').remove();
		}
	});

	$('div.site-contacts-tabs li a').click(function(event) {
		event.preventDefault();

		if ($(this).hasClass('site-contacts-link-fields')) {
			$('div.site-contacts-tab-fields').removeClass('site-contacts-hidden');
			$('div.site-contacts-tab-settings').addClass('site-contacts-hidden');
		} else if ($(this).hasClass('site-contacts-link-settings')) {
			$('div.site-contacts-tab-fields').addClass('site-contacts-hidden');
			$('div.site-contacts-tab-settings').removeClass('site-contacts-hidden');
		}

		$(this).closest('div.site-contacts-tabs').find('li.site-contacts-active').removeClass('site-contacts-active');
		$(this).parent().addClass('site-contacts-active');
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-field-dropdown div.site-contacts-option-type-entries textarea', function(event) {
		site_contacts_dropdown_change(this);
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-field-checkboxes div.site-contacts-option-type-entries textarea', function(event) {
		site_contacts_checkboxes_change(this);
	});

	$('div.site-contacts-form-fields').on('keyup', 'div.site-contacts-field-radioboxes div.site-contacts-option-type-entries textarea', function(event) {
		site_contacts_radioboxes_change(this);
	});
});
</script>
<?php
}

function site_contacts_edit_form($forms_list_url, $contact_form = null, $error = null) {
	$active_tab = 'fields';

	if ($contact_form == null) {
?>
<h3><?php _e('Add new contact form.', 'site-contacts'); ?></h3>
<?php
	} else {
?>
<h3><?php _e('Edit your existing contact form.', 'site-contacts'); ?></h3>
<?php
	}

	// Show error message
	if (!empty($error)) {
?>
	<div class="site-contacts-error-container">
		<div class="site-contacts-error"><?php echo $error; ?></div>
	</div>
<?php

	}

?>
<div class="site-contacts-tabs">
	<ul>
		<li<?php if ($active_tab == 'fields'){echo ' class="site-contacts-active"';} ?>><a href="#" class="site-contacts-link-fields"><?php _e('Edit fields', 'site-contacts'); ?></a></li>
		<li<?php if ($active_tab == 'settings'){echo ' class="site-contacts-active"';} ?>><a href="#" class="site-contacts-link-settings"><?php _e('Settings', 'site-contacts'); ?></a></li>
	</ul>
</div>
<form method="post" action="<?php echo $forms_list_url; ?>">
	<input type="hidden" name="site_contacts_save_form" value="yes" />
<?php

	if ($active_tab == 'fields') {
		site_contacts_tab_fields(true, $forms_list_url, $contact_form);
		site_contacts_tab_settings(false, $forms_list_url, $contact_form);
	} else {
		site_contacts_tab_fields(false, $forms_list_url, $contact_form);
		site_contacts_tab_settings(true, $forms_list_url, $contact_form);
	}

?>
</form>
<?php

	site_contacts_proto_scripts();

	site_contacts_edit_scripts();

	return false;
}

function site_contacts_form_list($forms_list_url) {
	$total_items = site_contacts_get_all_count();

	$items_per_page = 10;
	$current_page = 1;
	$total_pages = floor($total_items / $items_per_page);

	if (($total_items % $items_per_page) > 0) {
		$total_pages++;
	}

	if (!empty($_GET['subpage'])) {
		$current_page = intval($_GET['subpage']);
	}

	$contact_forms = site_contacts_get_range($current_page - 1, $items_per_page);

	$pagelink_args = array(
		'base'					=> $forms_list_url . '%_%',
		'format'				=> '&subpage=%#%',
		'total'					=> $total_pages,
		'current'				=> $current_page,
		'show_all'				=> false,
		'end_size'				=> 4,
		'mid_size'				=> 4,
		'prev_next'				=> true,
		'prev_text'				=> __('« Previous', 'site-contacts'),
		'next_text'				=> __('Next »', 'site-contacts'),
		'type'					=> 'plain',
		'add_args'				=> true,
		'add_fragment'			=> '',
		'before_page_number'	=> '',
		'after_page_number'		=> ''
	);
?>
<h3><?php _e('List of your contact forms.', 'site-contacts'); ?></h3>

<div class="site-contacts-main">

<div class="site-contacts-content">
	<div class="site-contacts-text">
		<p><a class="site-contacts-button" href="<?php echo add_query_arg(array('add' => 'yes'), $forms_list_url); ?>"><?php _e('Add Contact Form', 'site-contacts'); ?></a></p>
<?php

	$page_links = paginate_links($pagelink_args);

	if (!empty($page_links)) {
?>
		<p><?php echo $page_links ?></p>
<?php
	}

?>
		<table class="site-contacts-table">
			<tr>
				<th><?php _e('ID', 'site-contacts'); ?></th>
				<th><?php _e('Title', 'site-contacts'); ?></th>
				<th><?php _e('Created', 'site-contacts'); ?></th>
				<th><?php _e('Shortcode', 'site-contacts'); ?></th>
				<th><?php _e('Actions', 'site-contacts'); ?></th>
			</tr>
<?php

	if (empty($contact_forms) || !is_array($contact_forms) || (count($contact_forms) <= 0)) {
?>
			<tr>
				<td colspan="5"><p><?php _e('Currently, you do not have any contact forms.', 'site-contacts'); ?></p></td>
			</tr>
<?php
	} else {
		foreach ($contact_forms as $form) {
?>
			<tr>
				<td class="site-contacts-td-id"><?php echo $form['id']; ?></td>
				<td class="site-contacts-td-title"><?php

					if (!empty($form['title'])) {
						echo esc_html($form['title']);
					} else {
						echo __('(No title)', 'site-contacts');
					}

				?></td>
				<td class="site-contacts-td-created"><?php echo site_contacts_local_time($form['created']) ?></td>
				<td class="site-contacts-td-shortcode"><input type="text" value="<?php echo esc_html('[' . SITE_CONTACTS_FORM_SHORTCODE . ' id="' . $form['id'] . '"]') ?>" /></td>
				<td class="site-contacts-td-actions">
					<a href="<?php echo add_query_arg(array('edit' => $form['id']), $forms_list_url); ?>" title="<?php _e('Edit', 'site-contacts'); ?>"><?php _e('Edit', 'site-contacts'); ?></a>
					<a href="<?php echo add_query_arg(array('delete' => $form['id']), $forms_list_url); ?>" title="<?php _e('Delete', 'site-contacts'); ?>" onclick="return confirm('<?php _e('Are you sure?', 'site-contacts'); ?>');"><?php _e('Delete', 'site-contacts'); ?></a>
				</td>
			</tr>
<?php
		}
	}
?>
		</table>
<?php

	$page_links = paginate_links($pagelink_args);

	if (!empty($page_links)) {
?>
		<p><?php echo $page_links ?></p>
<?php
	}

?>
		<p><?php _e('<b>Hint:</b> you can use <b>shortcodes</b> to place your contact forms in posts or pages.', 'site-contacts'); ?></p>
	</div>
</div>

<?php site_contacts_info_bar(); ?>

<div class="site-contacts-clear"></div>
</div>
<?php
}

function site_contacts_page() {
	$forms_list_url = add_query_arg(array('page' => SITE_CONTACTS_ADMIN_PAGE), strtok($_SERVER['REQUEST_URI'], '?'));

	$show_form_list = true;
?>
<h1><?php _e('Site Contacts', 'site-contacts'); ?></h1>
<?php

	if (!empty($_POST['site_contacts_save_form']) && ($_POST['site_contacts_save_form'] == 'yes')) {
		$error = site_contacts_validate_form();

		if (empty($error)) {
			if (!empty($_POST['site_contacts_id'])) {
				$form_id = intval($_POST['site_contacts_id']);

				site_contacts_update_form($form_id);

				$post_fields = site_contacts_get_post_fields();

				site_contacts_check_fields($form_id, $post_fields);

				site_contacts_update_fields($form_id, $post_fields);
			} else {
				$new_form_id = site_contacts_insert_form();

				$post_fields = site_contacts_get_post_fields();

				site_contacts_insert_fields($new_form_id, $post_fields);
			}
		} else {
			$contact_form = null;

			if (!empty($_POST['site_contacts_id'])) {
				$contact_form = site_contacts_get($_POST['site_contacts_id']);
			}

			site_contacts_edit_form($forms_list_url, $contact_form, $error);

			$show_form_list = false;
		}
	}

	// Render specific pages depending on context
	if (!empty($_GET['add']) && ($_GET['add'] == 'yes')) {
		$show_form_list = site_contacts_edit_form($forms_list_url);
	} else if (!empty($_GET['edit'])) {
		// Loading existing data if we are editing contact form
		$contact_form = site_contacts_get($_GET['edit']);

		if (!empty($contact_form) && is_array($contact_form)) {
			$show_form_list = site_contacts_edit_form($forms_list_url, $contact_form);
		} else {
?>
<h3><?php _e('Edit your existing contact form.', 'site-contacts'); ?></h3>

<div class="site-contacts-error-container">
	<div class="site-contacts-error"><?php _e('Error: failed to get information from the database.', 'site-contacts'); ?></div>
</div>
<?php
			$show_form_list = false;
		}
	}

	if ($show_form_list) {
		// Delete contact form if that is required
		if (!empty($_GET['delete'])) {
			site_contacts_delete_form($_GET['delete']);
		}

		site_contacts_form_list($forms_list_url);
	}
}

function site_contacts_settings_page() {
	$site_contacts_remove_db = false;

	if (!empty($_POST['site_contacts_save_settings']) && ($_POST['site_contacts_save_settings'] == 'yes')) {
		if (site_contacts_checked('site_contacts_remove_db')) {
			$site_contacts_remove_db = true;
		} else {
			$site_contacts_remove_db = false;
		}

		update_option('site_contacts_remove_db', $site_contacts_remove_db);
	} else {
		$site_contacts_remove_db = get_option('site_contacts_remove_db', false);
	}

?>
<h1><?php _e('Settings', 'site-contacts'); ?></h1>
<h3><?php _e('Configure your plugin.', 'site-contacts'); ?></h3>

<div class="site-contacts-main">

<div class="site-contacts-content">
	<div class="site-contacts-text">
		<form method="post">
			<input type="hidden" name="site_contacts_save_settings" value="yes" />
			<p><label><input type="checkbox" name="site_contacts_remove_db" <?php if ($site_contacts_remove_db) echo 'checked="checked"'; ?> /><?php _e('Remove MySQL tables with all data when plugin is uninstalled.', 'site-contacts') ?></label></p>
			<p><button class="site-contacts-button site-contacts-save"><?php _e('Save', 'site-contacts'); ?></button></p>
		</form>
	</div>
</div>

<?php site_contacts_info_bar(); ?>

<div class="site-contacts-clear"></div>
</div>
<?php
}
