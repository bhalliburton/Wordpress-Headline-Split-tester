<?php
/*
Plugin Name: Headline Split Test
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 0.1
Author: Brent Halliburton & Peter Bessman
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/

/*  Copyright 2010 Brent Halliburton & Peter Bessman  (email : headlinesplittest@bhalliburton.otherinbox.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class HEADST4WP {

	var $meta = 'headst4wp';
	var $title_map = array();
    
	function HEADST4WP ($meta = 'headst4wp', $control_in_head = true) {
		$this->__construct($meta, $control_in_head);
	}
	
	
	function __construct($meta = 'headst4wp', $control_in_head = true) {
		$this->meta = $meta; $this->control_in_head = $control_in_head;
		add_action('plugins_loaded', array(&$this, 'action_plugins_loaded'));
	}
	

	function action_plugins_loaded() {
		if (is_admin()) {
			require_once(ABSPATH . 'wp-admin/includes/template.php'); // Needed for add_meta_box()
			add_meta_box('headsplittest_section', 'Set Alternate Headline', array(&$this, 'meta_box_post'), 'post', 'normal', 'high');
			add_action('save_post', array(&$this,'action_save_post'), 1, 2);
		} else {
			add_filter('the_title', array(&$this, 'title_filter'), 1, 2);
			add_filter('post_link', array(&$this, 'link_filter'), 1, 2);
		}
	}
	
	
	function title_filter($title, $id) {
		$is_alt = $this->get_is_alt($id);
		$new_title = $title;

		if ($is_alt == true) {
			$options = get_post_meta($id, $this->meta, true);

			if (is_array($options)) {
				$options['alt_headline'] = isset($options['alt_headline']) ? trim($options['alt_headline']) : '';
			} else {
				$options['alt_headline'] = '';
			}

			if (strlen($options['alt_headline']) > 0) $new_title = $options['alt_headline'];
		}
		
		return "$new_title";
	}
	

	function link_filter($permalink, $post) {
		$is_alt = $this->get_is_alt($post->ID) == true? 1: 0;

		return "$permalink&isalt=$is_alt";
	}
	

	function get_is_alt($id) {
		// if we are looking at a page, we need to
		// return the value of the isalt get parameter
		// if our caller is asking for the title
		// of the current page
		if (array_key_exists('isalt', $_GET)) {
			if ($id == $_GET['p'])
				return $_GET['isalt'] == 1? true: false;
		}

		if (array_key_exists($id, $this->title_map)) {
			return $this->title_map[$id];
		}

		$is_alt = false;
		if (rand(1, 10) > 5) {
			$is_alt = true;
		}

		$this->title_map[$id] = $is_alt;
		return $is_alt;
	}

	
	function action_save_post($post_id, $post) { 
		if ($post->post_type != 'revision') {
			$options = array();
			$options['alt_headline'] = isset($_POST['alt_headline']) ? trim($_POST['alt_headline']) : '';
			
			if (!update_post_meta($post->ID, $this->meta, $options)) {
				add_post_meta($post->ID, $this->meta, $options);
			} 
		}
	}

	function meta_box_post($post) {
		$options = get_post_meta($post->ID, $this->meta, true);

		if (is_array($options)) {
			$options['alt_headline'] = isset($options['alt_headline']) ? trim($options['alt_headline']) : '';
		} else {
			$options['alt_headline'] = '';
		}
?>
<table border="0" width="100%">
	<tr><td><textarea rows="1" cols="40" name="alt_headline"
		tabindex="5" id="alt_headline" style="width: 98%"><?php echo(htmlentities($options['alt_headline'])); ?></textarea>
	</td></tr>
</table>
<?php
	}
}
    
$headline_split_test = new HEADST4WP();
    
?>