<?php

if (!defined('ABSPATH'))
{
	exit;
}

class SiteContactsWidget extends WP_Widget
{
	function __construct()
	{
		parent::__construct('site-contacts-widget', 'Site Contacts', array('description' => __('Widget that displays AJAX contact forms.', 'site-contacts')));
	}

	function widget($args, $instance)
	{
		// Get contact form
		if (empty($instance['contact_form_id']))
		{
			return;
		}
		
		$contact_form_id = intval($instance['contact_form_id']);

		$instance['title'] = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);

		echo $args['before_widget'];

		if (!empty($instance['title']))
		{
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}

		$contact_form = site_contacts_get($contact_form_id);

		if (!empty($contact_form) && !empty($contact_form['form']))
		{
			echo site_contacts_render($contact_form);
		}
		else
		{
			echo '<p><b>' . __('Error: no contact form found with the specified ID', 'site-contacts') . '</b></p>';
		}

		echo $args['after_widget'];
	}

	function update($new_instance, $old_instance)
	{
		$instance = array();

		if (!empty($new_instance['title']))
		{
			$instance['title'] = sanitize_text_field($new_instance['title']);
		}

		if (!empty($new_instance['contact_form_id']))
		{
			$instance['contact_form_id'] = (int)$new_instance['contact_form_id'];
		}

		return $instance;
	}

	function form($instance)
	{
		$title = isset( $instance['title'] ) ? esc_attr($instance['title']) : '';
		$contact_form_id = isset( $instance['contact_form_id'] ) ? $instance['contact_form_id'] : '';

		$contact_forms = site_contacts_get_all();
		
		if ($contact_forms && count($contact_forms) > 0)
		{
?>
	<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'site-contacts') ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
	</p>
	<p>
		<?php _e('Please choose desired contact form:', 'site-contacts') ?>
	</p>
	<p>
	<select style="width:100%;" id="<?php echo $this->get_field_id('contact_form_id'); ?>" name="<?php echo $this->get_field_name('contact_form_id'); ?>">
		<option value="0"><?php _e('&mdash; Select &mdash;', 'site-contacts') ?></option>
<?php
			foreach ($contact_forms as $item)
			{
				$item_title = '';

				if (!empty($item['title']))
				{
					$item_title = __('ID #', 'site-contacts') . $item['id'] . ': ' . esc_html($item['title']);
				}
				else
				{
					$item_title = __('ID #', 'site-contacts') . $item['id'] . ': ' . __('(No title)', 'site-contacts');
				}

				echo '<option value="' . $item['id'] . '"'
					. selected($contact_form_id, $item['id'], false)
					. '>' . $item_title . '</option>';
			}
?>
	</select>
	</p>
<?php
		}
		else
		{
			echo '<p>'. sprintf(__('No contact forms have been created yet. <a href="%s">Create some</a>.', 'site-contacts'), admin_url('admin.php?page=' . SITE_CONTACTS_ADMIN_PAGE)) . '</p>';
		}
	}
}

?>