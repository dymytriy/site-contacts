<?php

/*
	Plugin Name: Site Contacts
	Plugin URI: https://github.com/dymytriy/site-contacts
	Description: Contact form plugin for WordPress. Simple, easy to use and lightweight. You can use shortcodes to place forms in any posts or pages.
	Author: Dymytriy
	Author URI: https://github.com/dymytriy
	Text Domain: site-contacts
	Version: 1.0
	License: GNU General Public License v2 or later
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

define("SITE_CONTACTS_VERSION", "1.0");

define("SITE_CONTACTS_DB_SCHEMA", 1);
define("SITE_CONTACTS_DB_PREFIX", "site_contacts_");

define("SITE_CONTACTS_FORM_SHORTCODE", "site_contact");

define("SITE_CONTACTS_ADMIN_PAGE", "site_contacts_list");
define("SITE_CONTACTS_SUBMISSIONS_PAGE", "site_contacts_submissions");
define("SITE_CONTACTS_SETTINGS_PAGE", "site_contacts_settings");

define("SITE_CONTACTS_TYPE_STANDARD_FORM", 0);

define("SITE_CONTACTS_FORM_STATUS_PUBLISHED", 0);

// Field types

define("SITE_CONTACTS_FIELD_TEXTFIELD",				1);
define("SITE_CONTACTS_FIELD_PHONE",					2);
define("SITE_CONTACTS_FIELD_EMAIL",					3);
define("SITE_CONTACTS_FIELD_DROPDOWN",				4);
define("SITE_CONTACTS_FIELD_CHECKBOXES",			5);
define("SITE_CONTACTS_FIELD_RADIOBOXES",			6);
define("SITE_CONTACTS_FIELD_TEXTAREA",				7);
define("SITE_CONTACTS_FIELD_SUBMIT_BUTTON",			8);
define("SITE_CONTACTS_FIELD_URL",					9);

define("SITE_CONTACTS_FIELD_ENTRY_STANDARD",		0);

// Include required files

require_once(dirname(__FILE__) . '/contacts-lib/widgets.php');
require_once(dirname(__FILE__) . '/contacts-lib/core.php');
require_once(dirname(__FILE__) . '/contacts-lib/submissions.php');

// Actions which are required for our plugin
add_action('init', 'site_contacts_init');

add_action('admin_menu', 'site_contacts_register_pages');
add_action('admin_enqueue_scripts', 'site_contacts_admin_scripts');

add_action('wp_enqueue_scripts', 'site_contacts_enqueue_scripts');

add_action('wp_ajax_site_contacts_submit', 'site_contacts_submit');
add_action('wp_ajax_nopriv_site_contacts_submit', 'site_contacts_submit');

add_action('wp_enqueue_media', 'site_contacts_enqueue_media');
add_action('media_buttons', 'site_contacts_media_buttons');

register_activation_hook(__FILE__, 'site_contacts_activate');
register_uninstall_hook(__FILE__, 'site_contacts_uninstall');

if (is_multisite()) {
	add_action('wpmu_new_blog', 'site_contacts_wpmu_new_blog', 10, 6);
	add_filter('wpmu_drop_tables', 'site_contacts_wpmu_drop_tables', 10, 1);
}

function site_contacts_init() {
	add_shortcode(SITE_CONTACTS_FORM_SHORTCODE, 'site_contacts_show');
}

function site_contacts_register_pages() {
	add_menu_page(
		__('Site Contacts', 'site-contacts'),
		__('Contacts', 'site-contacts'),
		'manage_options',
		SITE_CONTACTS_ADMIN_PAGE,
		'site_contacts_page',
		'dashicons-clipboard',
		'50.9332');

	add_submenu_page(
		SITE_CONTACTS_ADMIN_PAGE,
		__('All Contact Forms', 'site-contacts'),
		__('All Contact Forms', 'site-contacts'),
		'manage_options',
		SITE_CONTACTS_ADMIN_PAGE,
		'site_contacts_page');

	add_submenu_page(
		SITE_CONTACTS_ADMIN_PAGE,
		__('Submissions', 'site-contacts'),
		__('Submissions', 'site-contacts'),
		'manage_options',
		SITE_CONTACTS_SUBMISSIONS_PAGE,
		'site_contacts_submissions_page');

	add_submenu_page(
		SITE_CONTACTS_ADMIN_PAGE,
		__('Settings', 'site-contacts'),
		__('Settings', 'site-contacts'),
		'manage_options',
		SITE_CONTACTS_SETTINGS_PAGE,
		'site_contacts_settings_page');
}

function site_contacts_admin_scripts($name) {
	// Register plugin stylesheet file
	if (stripos($name, SITE_CONTACTS_ADMIN_PAGE) !== FALSE) {
		wp_register_style('site_contacts_admin_style', plugins_url('css/admin-style.css', __FILE__), array(), SITE_CONTACTS_VERSION);
		wp_register_script('site_contacts_admin_script', plugins_url('js/management.js', __FILE__),
			array('jquery','jquery-ui-core','jquery-ui-droppable','jquery-ui-draggable','jquery-ui-sortable'), SITE_CONTACTS_VERSION);

		wp_enqueue_style('site_contacts_admin_style');
		wp_enqueue_script('site_contacts_admin_script');
	} else if ((stripos($name, SITE_CONTACTS_SETTINGS_PAGE) !== FALSE) ||
			(stripos($name, SITE_CONTACTS_SUBMISSIONS_PAGE) !== FALSE)) {
		wp_register_style('site_contacts_admin_style', plugins_url('css/admin-style.css', __FILE__), array(), SITE_CONTACTS_VERSION);
		wp_register_script('site_contacts_admin_script', plugins_url('js/management.js', __FILE__), array('jquery'), SITE_CONTACTS_VERSION);

		wp_enqueue_style('site_contacts_admin_style');
		wp_enqueue_script('site_contacts_admin_script');
	}
}

function site_contacts_enqueue_scripts() {
	wp_register_style('site_contacts_style', plugins_url('/css/contact-form.css' , __FILE__), array(), SITE_CONTACTS_VERSION);
	wp_register_script('site_contacts_script', plugins_url('/js/contact-form.js' , __FILE__), array('jquery'), SITE_CONTACTS_VERSION);

	wp_enqueue_style('site_contacts_style');
	wp_enqueue_script('site_contacts_script');
}

function site_contacts_info_bar() {
?>
<div class="site-contacts-bar">
	<div class="site-contacts-widget">
		<div class="site-contacts-header"><?php _e('About', 'site-contacts'); ?></div>
		<div class="site-contacts-info">
			<p><?php _e('This plugin was developed and maintained by <a href="https://github.com/dymytriy" target="_blank">Dymytriy</a>.', 'site-contacts'); ?></p>
			<p><?php _e('Version:', 'site-contacts'); ?> <b><?php echo SITE_CONTACTS_VERSION; ?></b></p>
			<p><?php _e('Project home: <a href="https://github.com/dymytriy/site-contacts" target="_blank">view repository</a>', 'site-contacts'); ?></p>
		</div>
	</div>
</div>
<?php
}

function site_contacts_submit() {
	$result = array();

	if (	!empty($_POST['site_contacts_form_id']) &&
			!empty($_POST['site_contacts_fields']) &&
			is_array($_POST['site_contacts_fields'])) {
		$contact_form = site_contacts_get($_POST['site_contacts_form_id']);

		// Validate form id
		if (!empty($contact_form)) {
			$fields_info = site_contacts_submitted_fields($contact_form);

			$submission_errors = site_contacts_validate_data($contact_form, $fields_info);

			if (!empty($submission_errors) && is_array($submission_errors) && (count($submission_errors) > 0)) {
				$result['status'] = 'error';
				$result['code'] = 'validation_failed';

				if (!empty($submission_errors)) {
					$result['fields'] = $submission_errors;
				}

				echo json_encode($result);

				exit;
			} else {
				site_contacts_save_submission($contact_form, $fields_info);

				if (site_contacts_notify($contact_form, $fields_info)) {
					// Status code
					$result['status'] = 'success';

					echo json_encode($result);

					exit;
				}
			}
		}
	}

	// Status code
	$result['status'] = 'error';

	echo json_encode($result);

	exit;
}

function site_contacts_media_buttons($editor_id = 'content') {
	printf( '<button type="button" class="button site-contacts-media-insert" data-editor="%s">%s</button>',
		esc_attr( $editor_id ),
		__( 'Contact Form' )
	);
}

function site_contacts_enqueue_media() {
	wp_register_script('site_contacts_media_script', plugins_url('js/media.js', __FILE__), array('jquery'), SITE_CONTACTS_VERSION);
	wp_enqueue_script('site_contacts_media_script');

	wp_register_style('site_contacts_media_style', plugins_url('css/media.css', __FILE__), array('dashicons'), SITE_CONTACTS_VERSION);
	wp_enqueue_style('site_contacts_media_style');

	add_action('admin_footer', 'site_contacts_media_inline');
}

function site_contacts_activate($networkwide) {
	global $wpdb;

	if (is_multisite()) {
		if ($networkwide) {
			$old_blog = $wpdb->blogid;

			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

			if ($blogids && (!empty($blogids))) {
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);

					site_contacts_create_db();
				}
			}

			switch_to_blog($old_blog);

			return;
		}
	}

	site_contacts_create_db();
}

function site_contacts_uninstall() {
	global $wpdb;

	if (is_multisite()) {
		$old_blog = $wpdb->blogid;

		$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

		if ($blogids && (!empty($blogids))) {
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);

				site_contacts_check_removal_state();
			}
		}

		switch_to_blog($old_blog);

		return;
	}

	site_contacts_check_removal_state();
}

function site_contacts_wpmu_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
	global $wpdb;

	if (is_plugin_active_for_network(basename(dirname(__FILE__)) . '/' . basename(__FILE__))) {
		$old_blog = $wpdb->blogid;

		switch_to_blog($blog_id);

		site_contacts_create_db();

		switch_to_blog($old_blog);
	}
}

function site_contacts_wpmu_drop_tables($tables) {
	global $wpdb;

	$table_prefix = $wpdb->prefix . SITE_CONTACTS_DB_PREFIX;

	$tables[] = $table_prefix . 'forms';
	$tables[] = $table_prefix . 'fields';
	$tables[] = $table_prefix . 'field_entries';
	$tables[] = $table_prefix . 'data';
	$tables[] = $table_prefix . 'data_entries';
	$tables[] = $table_prefix . 'data_fields';

	return $tables;
}

function site_contacts_check_removal_state() {
	// Drop all tables in case if user wants to remove all information
	if (get_option('site_contacts_remove_db', false)) {
		site_contacts_remove_data();

		delete_option('site_contacts_remove_db');
	}
}
