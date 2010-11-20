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
	var $title_map = array();				// keeps rendered titles and urls in sync
	var $impressions_count_map = array();	// ensures that impressions only get incremented once
	var $alt_clicks_map = array();			// ensures that alt titles only get incremented once
	var $pri_clicks_map = array();			// ensures that primary titles only get incremented once
    
	function HEADST4WP ($meta = 'headst4wp', $control_in_head = true) {
		$this->__construct($meta, $control_in_head);
	}
	
	
	function __construct($meta = 'headst4wp', $control_in_head = true) {
		$this->meta = $meta;
		$this->control_in_head = $control_in_head;
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
		register_activation_hook(__FILE__,'set_headline_split_options');
	    register_deactivation_hook(__FILE__,'unset_headline_split_options');
	    add_action('admin_menu',array(&$this,'modify_menu'));
	}
	
	
	function set_headline_split_options() {
	  add_option('headline_split_impressions','250','Number of impressions to show before deciding');
	}
	
	
	function unset_headline_split_options() {
	  delete_option('headline_split_impressions');
	}	
	
	
	function update_headline_split_options() {
	  $ok = false;
	  
	  if($_REQUEST['headline_split_impressions']) {
	    update_option('headline_split_impressions',$_REQUEST['headline_split_impressions']);
	    $ok = true;
	  }
	  
	  if($ok) {
	    ?><div id="message" class="updated fade">
	      <p>New impression target saved.</p>
	    </div><?php
	  } else {
	    ?><div id="message" class="error fade">
	      <p>Failed to save new impression target.</p>
	    </div><?php
	  }
	}
	
	
	function print_headline_split_form() {
  		$default_impressions = get_option('headline_split_impressions');
?>
<form method="post">
	<label for="headline_split_impressions">Number of Impressions to Show Before Deciding:</label>
    <input type="text" name="headline_split_impressions" value="<?=$default_impressions?>" />
	<input type="submit" name="submit" value="Submit" />
</form>
<?php
	}
	
	
	function admin_headline_split_options() {
	  ?><div class="wrap"><h2>Headline Split Tester Options</h2><?php
	  
	  if(isset($_REQUEST['submit'])) {
	    $this->update_headline_split_options();
	  }
	  $this->print_headline_split_form();
	  
	}
	

	function modify_menu() {
	  add_options_page('Headline Split Tester','Headline Split Tester','manage_options',__FILE__,array(&$this,'admin_headline_split_options'));
	}
	
	
	function get_alt_headline($id){
		$options = get_post_meta($id, $this->meta, true);

		if (is_array($options)) {
			$options['alt_headline'] = isset($options['alt_headline']) ? trim($options['alt_headline']) : '';
		} else {
			$options['alt_headline'] = '';
		}
		
		return $options['alt_headline'];
	}
	
	
	function increment_headline_clicks($id, $is_alt) {
		if ($is_alt && array_key_exists($id, $this->alt_clicks_map))
			return;
			
		if (array_key_exists($id, $this->pri_clicks_map))
			return;
			
		$options = get_post_meta($id, $this->meta, true);
		$clicks = 0;
		$index = $is_alt? 'alt_headline_clicks': 'pri_headline_clicks';
		
		if (is_array($options)) {
			if (isset($options[$index]))
				$clicks = (int) $options[$index];
		} else {
			$options = array();
		}
		
		$default_impressions = (int) get_option('headline_split_impressions');
		$impressions = isset($options['headline_impressions'])? (int) $options['headline_impressions']: 0;
		
		// if we already have a winner, stop
		if ($impressions >= $default_impressions)
			return;

		$clicks++;
		$options[$index] = $clicks;
		
		if (!update_post_meta($id, $this->meta, $options))
			add_post_meta($id, $this->meta, $options);
		
		if ($is_alt)
			$this->alt_clicks_map[$id] = $id;
		else
			$this->pri_clicks_map[$id] = $id;
	}
	
	
	function increment_headline_impressions($id) {
		if (array_key_exists($id, $this->impressions_count_map))
			return;
			
		$options = get_post_meta($id, $this->meta, true);
		$impressions = 0;
		
		if (is_array($options)) {
			 if (isset($options['headline_impressions']))
				$impressions = (int) $options['headline_impressions'];
		} else {
			$options = array();
		}
		
		$default_impressions = (int) get_option('headline_split_impressions');
		
		// if we already have a winner, stop
		if ($impressions >= $default_impressions)
			return;
		
		$impressions++;
		$options['headline_impressions'] = $impressions;
		
		if (!update_post_meta($id, $this->meta, $options))
			add_post_meta($id, $this->meta, $options);
			
		$this->impressions_count_map[$id] = $id;
	}
	
	
	function title_filter($title, $id) {
		// check to see if we have a winner and act accordingly
		$options = get_post_meta($id, $this->meta, true);
		if (is_array($options)) {
			$default_impressions = (int) get_option('headline_split_impressions');
			$impressions = isset($options['headline_impressions'])? (int) $options['headline_impressions']: 0;
			$pri_clicks = isset($options['pri_headline_clicks'])? (int) $options['pri_headline_clicks']: 0;
			$alt_clicks = isset($options['alt_headline_clicks'])? (int) $options['alt_headline_clicks']: 0;
			
			if ($impressions >= $default_impressions) {
				$alt_headline = $this->get_alt_headline($id);
				if (strlen($alt_headline) > 0 && $alt_clicks > $pri_clicks )
					return $alt_headline;
				
				return $title;
			}
		}
		
		$is_alt = $this->get_is_alt($id);
		$new_title = $title;

		if ($is_alt == true) {
			$alt_headline = $this->get_alt_headline($id);

			if (strlen($alt_headline) > 0)
				$new_title = $alt_headline;
		}
		
		$this->increment_headline_impressions($id, $is_alt);
		return $new_title;
	}
	

	function link_filter($permalink, $post) {
		// check to see if we have a winner and act accordingly
		$options = get_post_meta($post->ID, $this->meta, true);
		if (is_array($options)) {
			$default_impressions = (int) get_option('headline_split_impressions');
			$impressions = isset($options['headline_impressions'])? (int) $options['headline_impressions']: 0;
			
			if ($impressions >= $default_impressions) {
				return $permalink;
			}
		}
		
		$is_alt = $this->get_is_alt($post->ID) == true? 1: 0;

		return "$permalink&isalt=$is_alt";
	}
	

	function get_is_alt($id) {
		// the first thing we do is check to see if an alternate title
		// is defined -- if not, this doesn't apply to us, so we just
		// bail
		$alt_headline = $this->get_alt_headline($id);
		if (strlen($alt_headline) == 0)
			return false;
		
		// if we are looking at a page, we need to
		// return the value of the isalt get parameter
		// if our caller is asking for the title
		// of the current page
		if (array_key_exists('isalt', $_GET)) {
			$is_alt = $_GET['isalt'] != 0? true: false;
			
			if ($id == $_GET['p']) {
				$this->increment_headline_clicks($id, $is_alt);
				
				return $is_alt;
			}
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
		$alt_headline = '';
		$total_impressions = 0;
		$pri_clicks = 0;
		$alt_clicks = 0;
		$default_impressions = get_option('headline_split_impressions');
		
		if (is_array($options))
		{
			$alt_headline = isset($options['alt_headline'])? trim($options['alt_headline']): '';
			$total_impressions = (int) (isset($options['headline_impressions'])? $options['headline_impressions']: 0 );
			$pri_clicks = (int) (isset($options['pri_headline_clicks'])? $options['pri_headline_clicks']: 0);
			$alt_clicks = (int) (isset($options['alt_headline_clicks'])? $options['alt_headline_clicks']: 0);
		}
?>
<table border="0" width="100%">
	<tr><td colspan=2><textarea rows="1" cols="40" name="alt_headline"
		tabindex="5" id="alt_headline" style="width: 98%"><?php echo(htmlentities($alt_headline)); ?></textarea>
	</td></tr>
	<tr><td>Total Impressions: </td><td><?php echo(htmlentities($total_impressions)); ?> out of <?=$default_impressions?></td></tr>
	<tr><td>Primary Headline Clicks: </td><td><?php echo(htmlentities($pri_clicks)); ?></td></tr>
	<tr><td>Alternate Headline Clicks: </td><td><?php echo(htmlentities($alt_clicks)); ?></td></tr>
</table>
<?php
	}
}
    
$headline_split_test = new HEADST4WP();
    
?>