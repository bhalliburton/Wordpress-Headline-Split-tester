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

function getIsAlt($id)
{
	static $titleMap = array();
	
	// if we are looking at a page, we need to 
	// return the value of the isalt get parameter
	// if our caller is asking for the title
	// of the current page
	if (array_key_exists('isalt', $_GET)) {
		if ($id == $_GET['p'])
			return $_GET['isalt'] == 1? true: false;
	}
	
	if (array_key_exists($id, $titleMap)) {
		return $titleMap[$id];
	}
	
	$isAlt = false;
	if (rand(1, 10) > 5) {
		$isAlt = true;
	}
	
	$titleMap[$id] = $isAlt;
	return $isAlt;
}

function addHeaderCode($title, $id) {
	$isAlt = getIsAlt($id);
	$newTitle = $title;
	
	if ($isAlt == true) {
		$options = get_post_meta($id, 'headline-split-test', true);


		if (is_array($options)) {
			$options['alt_headline']    = isset($options['alt_headline'])    ? trim($options['alt_headline'])    : '';	
		} else {
			$options['alt_headline']    = '';		
		}
		
		if (strlen($options['alt_headline']) > 0)
			$newTitle = $options['alt_headline'];
	}
	
	return "$newTitle";
}

function addLinkCode($permalink, $post) {
	$isAlt = getIsAlt($post->ID) == true? 1: 0;
	
  	return "$permalink&isalt=$isAlt";
}


function action_save_post($post_id, $post) {
	if ($post->post_type != 'revision') {
		$options = array();
		$options['alt_headline']    = isset($_POST['alt_headline'])    ? trim($_POST['alt_headline'])    : '';
		if (!update_post_meta($post->ID, 'headline-split-test', $options)) {
			add_post_meta($post->ID, 'headline-split-test', $options); 
		}
	} 
}

function meta_box_post($post) {
	$options = get_post_meta($post->ID, 'headline-split-test', true);

	if (is_array($options)) {
		$options['alt_headline']    = isset($options['alt_headline'])    ? trim($options['alt_headline'])    : '';	
	} else {
		$options['alt_headline']    = '';		
	}	


?>
<table border="0" width="100%">
  <tr>
    <td><textarea rows="1" cols="40" name="alt_headline" tabindex="5" id="alt_headline" style="width: 98%"><?php echo(htmlentities($options['alt_headline'])); ?></textarea>
    </td>
  </tr>
</table>
<?php

}



if (is_admin()) {
	require_once(ABSPATH . 'wp-admin/includes/template.php'); // Needed for add_meta_box()
	add_meta_box('headsplittest_section', 'Set Alternate Headline', 'meta_box_post', 'post', 'normal', 'high');
	add_action('save_post', 'action_save_post', 1, 2);
} else {
  add_filter('the_title', 'addHeaderCode', 1, 2);
  add_filter('post_link', 'addLinkCode', 1, 2);
}


?>