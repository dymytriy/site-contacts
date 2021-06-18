<?php

if (!defined('ABSPATH')) {
	exit;
}

function site_contacts_save_regular($new_data_id, $contact_form, $fields_info, $indexed_titles) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	if (!empty($fields_info) && is_array($fields_info)) {
		foreach ($fields_info as $field) {
			if (!empty($field['type'])) {
				switch ($field['type']) {
					case SITE_CONTACTS_FIELD_TEXTFIELD:
					case SITE_CONTACTS_FIELD_PHONE:
					case SITE_CONTACTS_FIELD_EMAIL:
					case SITE_CONTACTS_FIELD_DROPDOWN:
					case SITE_CONTACTS_FIELD_TEXTAREA:
					case SITE_CONTACTS_FIELD_RADIOBOXES:
					case SITE_CONTACTS_FIELD_URL:

						$wpdb->query($wpdb->prepare('
							INSERT INTO `' . $table_prefix . 'data_fields`
								(`data_id`, `form_id`, `field_id`, `type`, `weight`, `required`, `label`, `value`, `content`, `config`)
							VALUES
								(%d, %d, %d, %d, %d, %d, %s, "", %s, "");',
								$new_data_id,
								$contact_form['form']['id'],
								$field['field_id'],
								$field['type'],
								$field['weight'],
								$field['required'],
								((!empty($indexed_titles[$field['field_id']]['label'])) ? $indexed_titles[$field['field_id']]['label'] : ''),
								((!empty($field['content'])) ? $field['content'] : '')));

						break;

					case SITE_CONTACTS_FIELD_CHECKBOXES:

						$wpdb->query($wpdb->prepare('
							INSERT INTO `' . $table_prefix . 'data_fields`
								(`data_id`, `form_id`, `field_id`, `type`, `weight`, `required`, `label`, `value`, `content`, `config`)
							VALUES
								(%d, %d, %d, %d, %d, %d, %s, "", "", "");',
								$new_data_id,
								$contact_form['form']['id'],
								$field['field_id'],
								$field['type'],
								$field['weight'],
								$field['required'],
								((!empty($indexed_titles[$field['field_id']]['label'])) ? $indexed_titles[$field['field_id']]['label'] : '')));

						$new_data_field_id = $wpdb->insert_id;

						if (!empty($field['entries']) && is_array($field['entries'])) {
							foreach ($field['entries'] as $entry) {
								$wpdb->query($wpdb->prepare('
									INSERT INTO `' . $table_prefix . 'data_entries`
										(`data_id`, `data_field_id`, `form_id`, `field_id`, `entry_id`, `type`, `weight`, `value`, `content`, `config`)
									VALUES
										(%d, %d, %d, %d, %d, %d, %d, %s, "", "");',
										$new_data_id,
										$new_data_field_id,
										$contact_form['form']['id'],
										$field['field_id'],
										$entry['entry_id'],
										SITE_CONTACTS_FIELD_ENTRY_STANDARD,
										$entry['weight'],
										((!empty($entry['value'])) ? $entry['value'] : '')));
							}
						}

						break;
				}
			}
		}
	}
}

function site_contacts_save_submission($contact_form, $fields_info) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$current_time = round(time() / 3600) * 3600;

	// Insert data row
	$current_time = gmdate('Y-m-d H:i:s', time());

	$wpdb->query($wpdb->prepare('
		INSERT INTO `' . $table_prefix . 'data`
			(`form_id`, `created`, `updated`, `type`, `title`, `description`, `value`, `content`, `config`)
		VALUES
			(%d, %s, %s, %d, %s, %s, "", "", "");',
			$contact_form['form']['id'], $current_time, $current_time, $contact_form['form']['type'], $contact_form['form']['title'], $contact_form['form']['description']));

	$new_data_id = $wpdb->insert_id;

	// Get field titles
	$field_titles = $wpdb->get_results($wpdb->prepare('
		SELECT field_id, label FROM `' . $table_prefix . 'fields`
		WHERE form_id = %d;', $contact_form['form']['id']), ARRAY_A);

	$indexed_titles = array();

	foreach ($field_titles as $title) {
		if (!empty($title['field_id'])) {
			$indexed_titles[$title['field_id']] = $title;
		}
	}

	site_contacts_save_regular($new_data_id, $contact_form, $fields_info, $indexed_titles);
}

function site_contacts_submissions_count($form_id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	return $wpdb->get_var($wpdb->prepare(
		'SELECT COUNT(id) FROM `' . $table_prefix . 'data` WHERE form_id = %d;', intval($form_id)));
}

function site_contacts_submissions($form_id, $current_page, $items_per_page) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	// Get submissions
	$data = $wpdb->get_results($wpdb->prepare('
		SELECT id, created
		FROM `' . $table_prefix . 'data`
		WHERE form_id = %d
		ORDER BY created DESC
		LIMIT %d, %d;', intval($form_id), $current_page * $items_per_page, $items_per_page), ARRAY_A);

	if (!empty($data)) {
		$submissions = array();

		foreach ($data as $item) {
			if (!empty($item['id'])) {
				$submissions[$item['id']] = $item;
			}
		}

		// Get list of fields for current page
		$fields = $wpdb->get_results($wpdb->prepare('
			SELECT data.id, data_fields.field_id, data_fields.value, data_fields.content FROM
				(SELECT * FROM `' . $table_prefix . 'data` WHERE form_id = %d ORDER BY created DESC LIMIT %d, %d) as data
			LEFT JOIN `' . $table_prefix . 'data_fields` as data_fields
			ON data.id = data_fields.data_id
			ORDER BY data.id ASC;', intval($form_id), $current_page * $items_per_page, $items_per_page), ARRAY_A);

		if (!empty($fields)) {
			foreach ($fields as $item) {
				if ((!empty($item['id'])) && (!empty($item['field_id']))) {
					if (!empty($submissions[$item['id']])) {
						$submissions[$item['id']]['fields'][$item['field_id']] = $item;
					}
				}
			}

			// Get list of field entries for current page
			$field_entries = $wpdb->get_results($wpdb->prepare('
				SELECT data.id, data_entries.field_id, data_entries.entry_id, data_entries.value, data_entries.content FROM
					(SELECT * FROM `' . $table_prefix . 'data` WHERE form_id = %d ORDER BY created DESC LIMIT %d, %d) as data
				LEFT JOIN `' . $table_prefix . 'data_entries` as data_entries
				ON data.id = data_entries.data_id
				ORDER BY data.id ASC;', intval($form_id), $current_page * $items_per_page, $items_per_page), ARRAY_A);

			if (!empty($field_entries)) {
				foreach ($field_entries as $item) {
					if ((!empty($item['id'])) && (!empty($item['field_id'])) && (!empty($item['entry_id']))) {
						if (!empty($submissions[$item['id']]['fields'][$item['field_id']])) {
							$submissions[$item['id']]['fields'][$item['field_id']]['entries'][$item['entry_id']] = $item;
						}
					}
				}
			}
		}

		return $submissions;
	}

	return null;
}

function site_contacts_delete_submission($data_id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'data` WHERE id = %d;', intval($data_id)));
	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'data_entries` WHERE data_id = %d;', intval($data_id)));
	$wpdb->query($wpdb->prepare('DELETE FROM `' . $table_prefix . 'data_fields` WHERE data_id = %d;', intval($data_id)));
}

function site_contacts_get_data($data_id) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	// Get submissions
	$data = $wpdb->get_row($wpdb->prepare('
		SELECT id, created, title FROM `' . $table_prefix . 'data`
		WHERE id = %d
		LIMIT 1;', intval($data_id)), ARRAY_A);

	if (!empty($data)) {
		// Get list of fields for current data entry
		$fields = $wpdb->get_results($wpdb->prepare('
			SELECT data_id, field_id, type, weight, label, value, content FROM `' . $table_prefix . 'data_fields`
			WHERE data_id = %d
			ORDER BY weight ASC;', intval($data_id)), ARRAY_A);

		if (!empty($fields)) {
			foreach ($fields as $item) {
				if (!empty($item['field_id'])) {
					$data['fields'][$item['field_id']] = $item;
				}
			}

			// Get list of field entries for current page
			$field_entries = $wpdb->get_results($wpdb->prepare('
				SELECT data_id, field_id, entry_id, type, weight, value, content FROM `' . $table_prefix . 'data_entries`
				WHERE data_id = %d
				ORDER BY weight ASC;', intval($data_id)), ARRAY_A);

			if (!empty($field_entries)) {
				foreach ($field_entries as $item) {
					if ((!empty($item['field_id'])) && (!empty($item['entry_id']))) {
						if (!empty($data['fields'][$item['field_id']])) {
							$data['fields'][$item['field_id']]['entries'][$item['entry_id']] = $item;
						}
					}
				}
			}
		}

		return $data;
	}

	return null;
}

function site_contacts_submissions_preview($entry, $field) {
	if (!empty($field['field_id'])) {
		switch ($field['type']) {
			case SITE_CONTACTS_FIELD_TEXTFIELD:
			case SITE_CONTACTS_FIELD_PHONE:
			case SITE_CONTACTS_FIELD_EMAIL:
			case SITE_CONTACTS_FIELD_DROPDOWN:
			case SITE_CONTACTS_FIELD_TEXTAREA:
			case SITE_CONTACTS_FIELD_RADIOBOXES:
			case SITE_CONTACTS_FIELD_URL:

				if (!empty($entry['fields'][$field['field_id']]['content'])) {
					echo esc_html($entry['fields'][$field['field_id']]['content']);
				}

			break;

			case SITE_CONTACTS_FIELD_CHECKBOXES:

				if ((!empty($entry['fields'][$field['field_id']]['entries'])) &&
					(is_array($entry['fields'][$field['field_id']]['entries']))) {
					$index = 0;

					foreach ($entry['fields'][$field['field_id']]['entries'] as $item_entry) {
						if ($index > 0) {
							echo ', ';
						}

						echo esc_html($item_entry['value']);

						$index++;
					}
				}

			break;
		}
	}
}

function site_contacts_submissions_list($submissions_list_url) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$contact_form_id = 0;

	if ((!empty($_POST['site_contacts_select_form'])) && ($_POST['site_contacts_select_form'] == 'yes')) {
		if (!empty($_POST['site_contacts_contact_form'])) {
			$contact_form_id = intval($_POST['site_contacts_contact_form']);
		} else {
			$contact_form_id = 0;
		}

		update_option('site_contacts_submissions_form', $contact_form_id);
	} else {
		$contact_form_id = get_option('site_contacts_submissions_form', 0);
	}

	// Get list of all forms
	$contacts = site_contacts_get_all();

	// Check if contact form exists
	if ($contacts && count($contacts) > 0) {
		$found_form = false;

		foreach ($contacts as $item) {
			if ($item['id'] == $contact_form_id) {
				$found_form = true;

				break;
			}
		}

		if ($found_form == false) {
			// No contact form found, invalidate the id
			$contact_form_id = 0;
		}
	} else {
		// No contact forms found, invalidate the id
		$contact_form_id = 0;
	}

	// Try to get first available form
	if ($contact_form_id <= 0) {
		// Get first available form
		$contact_form_id = $wpdb->get_var('SELECT id FROM `' . $table_prefix . 'forms` ORDER BY id ASC LIMIT 1;');
	}

?>
<h3><?php _e('Recent submissions for your contact forms.', 'site-contacts'); ?></h3>
<div class="site-contacts-main">
	<div class="site-contacts-text">
<?php

	if ($contact_form_id > 0) {
		if ($contacts && count($contacts) > 0) {

?>
		<form method="post">
			<input type="hidden" name="site_contacts_select_form" value="yes" />
			<p>
				<label><?php _e('Select contact form:', 'site-contacts') ?></label>
				<select class="site-contacts-select site-contacts-wide" name="site_contacts_contact_form" onchange="this.form.submit()">
<?php

			foreach ($contacts as $item) {
				$item_title = '';

				if (!empty($item['title'])) {
					$item_title = __('ID #', 'site-contacts') . $item['id'] . ': ' . esc_html($item['title']);
				} else {
					$item_title = __('ID #', 'site-contacts') . $item['id'] . ': ' . __('(No title)', 'site-contacts');
				}

				echo '<option value="' . $item['id'] . '"'
					. selected($contact_form_id, $item['id'], false)
					. '>' . $item_title . '</option>';
			}

?>
				</select>
			</p>
		</form>
<?php

		}

		$total_items = site_contacts_submissions_count($contact_form_id);

		$items_per_page = 10;
		$current_page = 1;
		$total_pages = floor($total_items / $items_per_page);

		if (($total_items % $items_per_page) > 0) {
			$total_pages++;
		}

		if (!empty($_GET['subpage'])) {
			$current_page = intval($_GET['subpage']);
		}

		$submissions = site_contacts_submissions($contact_form_id, $current_page - 1, $items_per_page);

		$pagelink_args = array(
			'base'					=> $submissions_list_url . '%_%',
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

		$page_links = paginate_links($pagelink_args);

		if (!empty($page_links)) {

?>
		<p><?php echo $page_links ?></p>
<?php

		}

		// Get first 5 fields for the active form
		$fields = $wpdb->get_results($wpdb->prepare('
			SELECT field_id, form_id, type, label FROM `' . $table_prefix . 'fields`
			WHERE form_id = %d AND type != %d
			ORDER BY weight ASC
			LIMIT 5;', intval($contact_form_id), SITE_CONTACTS_FIELD_SUBMIT_BUTTON), ARRAY_A);

?>
		<div class="site-contacts-submissions">
			<table class="site-contacts-table">
				<tr>
					<th><?php _e('ID', 'site-contacts'); ?></th>
					<th><?php _e('Created', 'site-contacts'); ?></th>
<?php

				foreach ($fields as $item) {
?>
					<th><?php

					if (!empty($item['label'])) {
						echo esc_html($item['label']);
					} else {
						echo __('(No title)', 'site-contacts');
					}

?></th>
<?php
				}

?>
					<th><?php _e('Actions', 'site-contacts'); ?></th>
				</tr>
<?php

		if (empty($submissions) || !is_array($submissions) || (count($submissions) <= 0)) {

?>
				<tr>
					<td colspan="5"><p><?php _e('Currently, you do not have any submissions.', 'site-contacts'); ?></p></td>
				</tr>
<?php

		} else {
			foreach ($submissions as $entry) {

?>
				<tr>
					<td class="site-contacts-id"><?php echo $entry['id']; ?></td>
					<td><?php echo site_contacts_local_time($entry['created']) ?></td>
<?php

				foreach ($fields as $item) {

?>
					<td>
<?php

					site_contacts_submissions_preview($entry, $item);

?>
					</td>
<?php
				}
?>
					<td class="site-contacts-actions">
						<a href="<?php echo add_query_arg(array('view' => $entry['id']), $submissions_list_url); ?>" title="<?php _e('View', 'site-contacts'); ?>"><?php _e('View', 'site-contacts'); ?></a>
						<a href="<?php echo add_query_arg(array('delete' => $entry['id']), $submissions_list_url); ?>" title="<?php _e('Delete', 'site-contacts'); ?>" onclick="return confirm('<?php _e('Are you sure?', 'site-contacts'); ?>');"><?php _e('Delete', 'site-contacts'); ?></a>
					</td>
				</tr>
<?php
			}
		}

?>
			</table>
		</div>
<?php

		$page_links = paginate_links($pagelink_args);

		if (!empty($page_links)) {

?>
		<p><?php echo $page_links ?></p>
<?php

		}
	} else {

?>
		<p><?php _e('No contact forms found.', 'site-contacts'); ?></p>
<?php

	}

?>
	</div>
</div>
<?php
}

function site_contacts_submissions_field($field) {
	switch ($field['type']) {
		case SITE_CONTACTS_FIELD_TEXTFIELD:
		case SITE_CONTACTS_FIELD_PHONE:
		case SITE_CONTACTS_FIELD_EMAIL:
		case SITE_CONTACTS_FIELD_DROPDOWN:
		case SITE_CONTACTS_FIELD_TEXTAREA:
		case SITE_CONTACTS_FIELD_RADIOBOXES:
		case SITE_CONTACTS_FIELD_URL: {
?>
	<p><label><strong><?php

			if (empty($field['label'])) {
				echo __('(No field label)', 'site-contacts');
			} else {
				echo esc_html($field['label']);
			}

?>:</strong></label><br/><?php

			if (empty($field['content'])) {
				echo __('(Empty)', 'site-contacts');
			} else {
				echo esc_html($field['content']);
			}
?></p>
<?php
		}
		break;

		case SITE_CONTACTS_FIELD_CHECKBOXES: {
?>
	<p><label><strong><?php

			if (empty($field['label'])) {
				echo __('(No field label)', 'site-contacts');
			} else {
				echo esc_html($field['label']);
			}

?>:</strong></label><br/><?php

			if ((!empty($field['entries'])) && (is_array($field['entries'])) && (count($field['entries']) > 0)) {
				$entries = $field['entries'];

				usort($entries, function($item1, $item2) {
					if (isset($item1['weight']) && isset($item2['weight'])) {
						if ($item1['weight'] < $item2['weight']) {
							return -1;
						} else if ($item1['weight'] > $item2['weight']) {
							return 1;
						}

						return 0;
					}
				});

				$index = 0;

				foreach ($entries as $item_entry) {
					if ($index > 0) {
						echo ', ';
					}

					echo esc_html($item_entry['value']);

					$index++;
				}
			} else {
				echo __('(Empty)', 'site-contacts');
			}
?></p>
<?php
		}
		break;
	}
}

function site_contacts_submission_form($show_submission_list, $submission) {
	$fields = null;

?>
<h3><?php echo __('Fields for form submission ID#', 'site-contacts') . $submission['id'] . '.'; ?></h3>
<div class="site-contacts-main">
	<div class="site-contacts-text">
	<p><label><strong><?php echo __('Title', 'site-contacts') ?>:</strong></label><br/><?php

	if (empty($submission['title'])) {
		echo __('(No title)', 'site-contacts');
	} else {
		echo esc_html($submission['title']);
	}

	?></p>
<?php

	if ((!empty($submission['fields'])) && (is_array($submission['fields']))) {
		$fields = $submission['fields'];

		usort($fields, function($item1, $item2) {
			if (isset($item1['weight']) && isset($item2['weight'])) {
				if ($item1['weight'] < $item2['weight']) {
					return -1;
				} else if ($item1['weight'] > $item2['weight']) {
					return 1;
				}

				return 0;
			}
		});

		foreach ($fields as $item) {
			site_contacts_submissions_field($item);
		}
	} else {
?>
		<p><?php _e('Currently, you do not have any field data for this submission.', 'site-contacts'); ?></p>
<?php
	}

?>
	</div>
</div>
<?php
}

function site_contacts_submissions_page() {
	$submissions_list_url = add_query_arg(array('page' => SITE_CONTACTS_SUBMISSIONS_PAGE), strtok($_SERVER['REQUEST_URI'], '?'));

	$show_submission_list = true;

?>
<h1><?php _e('Submissions', 'site-contacts'); ?></h1>
<?php

	if (!empty($_GET['view'])) {
		// Loading existing data if we are viewing submission
		$submission = site_contacts_get_data($_GET['view']);

		if (!empty($submission)) {
			$show_submission_list = site_contacts_submission_form($show_submission_list, $submission);
		} else {

?>
<h3><?php _e('Recent submissions for your contact forms.', 'site-contacts'); ?></h3>

<div class="site-contacts-error-container">
	<div class="site-contacts-error"><?php _e('Error: failed to get information from the database.', 'site-contacts'); ?></div>
</div>
<?php

			$show_submission_list = false;
		}
	}

	if ($show_submission_list) {
		// Delete submisssion if user wants that
		if (!empty($_GET['delete'])) {
			site_contacts_delete_submission($_GET['delete']);
		}

		site_contacts_submissions_list($submissions_list_url);
	}
}
