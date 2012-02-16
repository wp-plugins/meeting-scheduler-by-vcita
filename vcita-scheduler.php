<?php
/*
Plugin Name: Meeting Scheduler by vCita
Plugin URI: http://www.vcita.com
Description: vCita meeting scheduler allows website visitors to book online, face to face or phone meetings. It also provides a platform for Payment Processing
Version: 1.0.0
Author: vCita.com
Author URI: http://www.vcita.com
*/


/* --- Static initializer for Wordpress hooks --- */

$other_widget_parms = (array) get_option('vcita_widget'); // Check the key of the other plugin

// Check if vCita plugin already installed.
if (isset($other_widget_parms['version']) || isset($other_widget_parms['uid']) || isset($other_widget_parms['email'])) {
	add_action('admin_notices', 'vcita_next_gen_installed_warning');
} else {
	define('VCITA_WIDGET_VERSION', '1.0.0');
	define('VCITA_WIDGET_PLUGIN_NAME', 'Meeting Scheduler by vCita');
	define('VCITA_WIDGET_KEY', 'vcita_scheduler');
	define('VCITA_WIDGET_API_KEY', 'wp-v-schd');
	define('VCITA_WIDGET_MENU_NAME', 'vCita Meeting Scheduler');
	define('VCITA_WIDGET_SHORTCODE', 'vCitaMeetingScheduler');
	define('VCITA_WIDGET_UNIQUE_ID', 'meeting-scheduler-by-vcita');
	define('VCITA_WIDGET_UNIQUE_LOCATION', __FILE__);
	define('VCITA_WIDGET_CONTACT_FORM_WIDGET', 'false');
	
	require_once(WP_PLUGIN_DIR."/".VCITA_WIDGET_UNIQUE_ID."/vcita-functions.php");

	
	/* --- Static initializer for Wordpress hooks --- */

	add_action('plugins_loaded', 'vcita_init');
	add_shortcode(VCITA_WIDGET_SHORTCODE,'vcita_add_contact');
	add_action('admin_menu', 'vcita_admin_actions');
	add_action('wp_head', 'vcita_add_active_engage');
}

/** 
 * Notify about other vCita plugin already available
 */ 
function vcita_next_gen_installed_warning() {
	echo "<div id='vcita-warning' class='error'><p><B>".__("vCita Plugin is already installed")."</B>".__(', please delete "<B>Meeting Scheduler by vCita</B>" and use the available "<B>Next Gen Contact Form by vCita</B>" plugin')."</p></div>";
}
?>