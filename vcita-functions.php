<?php

/* --- Wordpress Hooks Implementations --- */

/**
 * Add the JS code for the vCita Active Engage feature
 */
function vcita_add_active_engage() {
	$vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
	
	if (!empty($vcita_widget['uid']) && !is_admin() && $vcita_widget['engage_active'] == 'true') {
		// Will be added to the head of the page
		?>
		 <script type="text/javascript">//<![CDATA[
			var vcHost = document.location.protocol == "https:" ? "https://" : "http://";
			var vcUrl = "<?php echo VCITA_SERVER_BASE; ?>" + "/" + "<?php echo $vcita_widget['uid'] ?>" + '/loader.js';
			document.write(unescape("%3Cscript src='" + vcHost + vcUrl + "' type='text/javascript'%3E%3C/script%3E"));	
		//]]></script>
		
		<?php
	}
}

/**
 * Add the JS code for the Admin section vCita Active Engage feature
 */
function vcita_add_active_engage_admin() {
	$vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
	
	if (isset($vcita_widget["uid"]) && !empty($vcita_widget['uid'])) {
		// Will be added to the head of the page
		// Currently disabled
	}
}

/**
 * Place a notification in the admin pages in one of two cases: 
 * 1. User available but didn't complete registration
 * 2. User not created and didn't request to dismiss the message
 */
function vcita_wp_add_admin_notices() {
	$vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
	
	if (!isset($_GET['page']) || !preg_match('/'.VCITA_WIDGET_UNIQUE_ID.'\//',$_GET['page'])) {
	
		$vcita_section_url = admin_url("plugins.php?page=".plugin_basename(__FILE__));
		$vcita_dismiss_url = admin_url("plugins.php?page=".plugin_basename(__FILE__)."&dismiss=true");
		$prefix = "<p><b>".VCITA_WIDGET_PLUGIN_NAME." - </b>";
		$suffix = "</p>";
		$class = "error";
		$user_available = isset($vcita_widget["uid"]) && !empty($vcita_widget["uid"]);
		
		if ($user_available && !$vcita_widget['confirmed'] && !empty($vcita_widget['confirmation_token'])) {
			echo "<div class='".$class."'>".$prefix." <a href='".$vcita_section_url."'>Click here to configure your contact and meeting preferences</a>".$suffix."</div>";
			
		} else if (!$user_available && (!isset($vcita_widget["dismiss"]) || !$vcita_widget["dismiss"])) {
			echo "<div class='".$class."'>".$prefix."You still haven't completed your Meeting Scheduler settings. <a href='".$vcita_section_url."'>Click here to learn more</a>, or <a href='".$vcita_dismiss_url."'>Dismiss.</a>".$suffix."</div>";
		} 
	}
}

/**
 *  Add the vCita widget to the "Settings" Side Menu
 */
function vcita_admin_actions() {
    if ( function_exists('add_options_page') ) {
		$admin_page_suffix = add_submenu_page('plugins.php', __(VCITA_WIDGET_MENU_NAME, VCITA_WIDGET_SHORTCODE), __(VCITA_WIDGET_MENU_NAME, VCITA_WIDGET_SHORTCODE), 'manage_options', __FILE__,'vcita_settings_menu');
		add_action('admin_print_scripts-'.$admin_page_suffix, 'vcita_add_active_engage_admin');
		add_action('admin_notices', 'vcita_wp_add_admin_notices');
    }
}

/**
 * Create the Main vCita Settings form content.
 *
 * The form is constructed from a list of input fields and a preview for the result
 */
function vcita_settings_menu() {

	// Disconnect should change the widget values before the prepare settings method is called.
	if (isset($_POST) && isset($_POST['Submit']) && $_POST['Submit'] == 'Disconnect') {
		$vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
		vcita_trash_current_page($vcita_widget);
		
		$vcita_widget = create_initial_parameters();
		$vcita_widget["dismiss"] = "true"; // Make sure the notification won't appear 
		update_option(VCITA_WIDGET_KEY, $vcita_widget);
	}

	extract(vcita_prepare_widget_settings("settings"));

	// Check the dedicated page flag - If it is on, make sure a page is available, if not - Trash the page
	if ($update_made) {
		if ($_POST['Submit'] == "Disable Page") {
			vcita_trash_current_page($vcita_widget);
				
			$vcita_widget['contact_page_active'] = 'false';
			update_option(VCITA_WIDGET_KEY, $vcita_widget);
						
		// Make sure page is live if requested to or as default
		} else if ($_POST['Submit'] == "Activate Page" || $vcita_widget['contact_page_active'] == 'true') {
			$vcita_widget = make_sure_page_published($vcita_widget);
		}
	}

	$vcita_dismissed = false;
	
	if (isset($_GET) && isset($_GET['dismiss']) && $_GET['dismiss'] == "true") {
		$vcita_widget["dismiss"] = true;
		$vcita_dismissed = true;
		update_option(VCITA_WIDGET_KEY, $vcita_widget);
	}
	
	if (is_page_available($vcita_widget)) {

		$page_action = "Disable Page";
		$page_status = "<span style='color:green;font-weight:bold;'>Currently Active</span>";
		$page_available = true;
	} else {
		$page_action = "Activate Page";
		$page_status = "<span style='color:red;font-weight:bold;'>Currently  Disabled</span>";
		$page_available = false;
	}


	if ($first_time) {
		$form_hidden = "";
	} else {
		$form_hidden = "display:none";
	}
	
	if ($vcita_widget['engage_active'] == 'true') {
		$engage_configure_visibility = "";
		$engage_active_state = 'Currently Active'; 
		$engage_active_style = 'color:green;font-weight:bold;';
		$engage_toggle_action = 'Disable';
	} else {
		$engage_configure_visibility = "display:none;";
		$engage_active_state = 'Currently Disabled';
		$engage_active_style = 'color:red;font-weight:bold;';
		$engage_toggle_action = 'Activate';
	}
	
	vcita_embed_toggle_visibility();
	
	
    ?>
    <div class="wrap" style="max-width:855px;">
        <h2><img src="http://<?php echo VCITA_SERVER_BASE; ?>/images/logo.png"></h2>
        <?php echo vcita_create_user_message($vcita_widget, $update_made); ?>

		<?php if ($vcita_dismissed) { ?>
			<div class='updated below-h2' ><p>vCita Meeting Scheduler notification has been dismissed</p></div>
		<?php } ?>
		
        <?php if ($first_time) { ?>
            <div><p>Please provide an email to which contact and scheduling requests from your site will be sent:</p></div>
		<?php } ?>

        <div style="float:left;margin-right:20px;width:800px;margin-top:5px;">

        <form name="vcita_form" method="post" id="vcita_form_<?php echo $form_uid;?>" action="<?php echo str_replace('&dismiss=true','',str_replace( '%7E', '~', $_SERVER['REQUEST_URI'])); ?>" style="<?php echo $form_hidden; ?>">
			<input type="hidden" value="main_settings" name="form_type" />
			<div style="width:310px">
				<div style="display:block;clear:both;">
					<span style="display:inline-block;width:110px;"><?php _e("Email: " ); ?></span>
                    <input type="text" onkeypress="vcita_clearNames();" name="vcita_email" value="<?php echo vcita_default_if_non($vcita_widget, 'email'); ?>" size="25">
                </div>

				<div id="vcita_new_user_details" style="display:block;">
					<div style="display:block;clear:both;">
						<span style="display:inline-block;width:110px;"><?php _e("First name: " ); ?></span>
						<input type="text" name="vcita_first-name" id="vcita_first-name_<?php echo $form_uid;?>" <?php echo $disabled; ?> value="<?php echo vcita_default_if_non($vcita_widget, 'first_name'); ?>" size="25">
					</div>

					<div style="display:block;clear:both;">
						<span style="display:inline-block;width:110px;"><?php _e("Last user: " ); ?></span>
						<input type="text" name="vcita_last-name" id="vcita_last-name_<?php echo $form_uid;?>" <?php echo $disabled; ?> value="<?php echo vcita_default_if_non($vcita_widget, 'last_name'); ?>" size="25">
					</div>
				</div>
				
                <p class="submit" style="padding-top:5px;">
                    <input id="vcita_create_user" type="submit" style="float:left;display:block;" name="Submit" value="Create vCita Account" />
					<input id="vcita_connect_user" type="submit" style="float:left;display:none;" name="Submit" value="Connect" />
					<a href="#" id="vcita_already_user_toggle" style="text-align:left;float:left;padding:5px 0 0 5px;display:block;" onclick="vcita_toggle_visibility('vcita_connect_user');vcita_toggle_visibility('vcita_create_user_toggle');vcita_toggle_visibility('vcita_create_user');vcita_toggle_visibility('vcita_new_user_details');vcita_toggle_visibility('vcita_already_user_toggle');return false;">Already have a vCita account?</a>
					<a href="#" id="vcita_create_user_toggle" style="text-align:left;float:left;padding:5px 0 0 5px;display:none;" onclick="vcita_toggle_visibility('vcita_connect_user');vcita_toggle_visibility('vcita_create_user_toggle');vcita_toggle_visibility('vcita_create_user');vcita_toggle_visibility('vcita_new_user_details');vcita_toggle_visibility('vcita_already_user_toggle');return false;">Create a new account</a>
					
                    <?php if (!$first_time) { ?>
						<input type="hidden" name="user_change" value="true" >
						<div style="clear:both;"></div>
                        <a href="#" style="text-align:left;margin:15px 0 0 5px;overflow:hidden;display:block;"
                            onclick="document.getElementById('vcita_active_<?php echo $form_uid;?>').style.display='block';document.getElementById('vcita_form_<?php echo $form_uid;?>').style.display= 'none';">Cancel</a>
                    <?php } ?>
                </p>
            </div>
        </form>
		
        <?php if (!$first_time) { ?>
			<div id="vcita_active_<?php echo $form_uid;?>" style="width:350px;">
				<div style="float:left;width:210px;">Account:&nbsp;<b><?php echo $vcita_widget['first_name']." ".$vcita_widget['last_name']; ?></b></div>
				
				<div style="float:left;">
					<form id="vcita_disconnect_control_<?php echo $form_uid ?>" name="vcita_disconnect_control" style="margin-left:10px;float:right;width:100px;" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
						<input type="submit"
								style="float:left;width:100px;"
								name="Submit"
								onclick="if (confirm('Are you sure you wish to disconnect?')) return true; else return false;"
								value="Disconnect" />
					</form>
				</div>
			</div>

			<?php echo $config_html; ?>

			<p style="margin-bottom:15px;margin-top:5px;font-weight:bold;">Choose how to add vCita to your site: (<?php echo vcita_create_link('Not sure? see all options', 'widget_implementations', 'key='.$vcita_widget['implementation_key']); ?> ) </p>
			
			<div style="overflow:hidden;clear:both;margin-top: 20px;border: 1px solid #DFDFDF;border-radius: 4px;">
				<h4 style="cursor:pointer;clear:both;padding: 5px 0 5px 5px;margin:0px;overflow:hidden;background: #EEE;margin:0px" onclick="vcita_toggle_option_visibility('vcita_option_active_engage');return false;">
					<span style="float:left;margin-right:5px;padding:0 3px; background-color:white;text-align:center;width:10px;" id="vcita_option_active_engage_marker">+</span>
					<span style="float:left;">vCita Active Engage - &nbsp;</span>
					<div style="float:left;<?php echo $engage_active_style ?>"><?php echo $engage_active_state; ?></div>
				</h4>
				<div style="clear:both;margin-top:5px;overflow:hidden;display:none;margin-left:5px;width:650px;" id="vcita_option_active_engage">
					<div style="display:block;margin:5px 15px 10px 15px;overflow:hidden;">
						<div style="overflow:hidden;margin:5px 0;">
							<div>
								<div >Generate more leads by actively inviting visitors to contact and meet.</div>
							</div>
							<div style="overflow:hidden;margin-top:10px;">
								<?php echo vcita_create_link('Preview / Customize', 'widget_implementations', 'widget=active_engage&key='.$vcita_widget['implementation_key']); ?> 
							</div>
						</div>
						<div style="margin-top: 10px;float: left;" id="vcita_engage_configure_<?php echo $form_uid;?>">
							<form id="vcita_engage_configure_control_<?php echo $form_uid ?>" name="vcita_engage_configure_control" style="float:left;" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
								<input type="hidden" value="engage_enable_control" name="form_type" />
								<input type="submit" style="float:left;width:100px;" name="Submit" value="<?php echo $engage_toggle_action; ?>" />
							</form>
						</div>

					</div>
				</div>
			</div>
			
			<div style="overflow:hidden;clear:both;margin-top: 20px;border: 1px solid #DFDFDF;border-radius: 4px;">
				<h4 style="cursor:pointer;clear:both;padding: 5px 0 5px 5px;margin:0px;overflow:hidden;background: #EEE;margin:0px" onclick="vcita_toggle_option_visibility('vcita_option_contact_form');return false;">
					<span style="float:left;margin-right:5px;padding:0 3px; background-color:white;text-align:center;width:10px;" id="vcita_option_contact_form_marker" >+</span>
					<span style="float:left;">vCita Contact Page - &nbsp;</span>
					<div style="float:left;margin-right:5px;"><?php echo $page_status;?></div>
				</h4>
				<div style="margin-top:5px;overflow:hidden;display:none;margin-left:5px;width:650px;" id="vcita_option_contact_form">
					<div style="display:block;margin:5px 15px;overflow:hidden;">
						<div style="overflow:hidden;margin:5px 0;width:510px;">
							<div>
								<div >A contact page with a contact form and meeting scheduler will be created for you</div>
							</div>
							<div style="overflow:hidden;margin-top:10px">
								<?php echo vcita_create_link('Preview / Customize', 'widget_implementations', 'widget=contact&key='.$vcita_widget['implementation_key']); ?> 
							</div>
						</div>
						<form name="vcita_page_control_form" style="float:left;margin:10px 0;" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
							<input type="hidden" value="page_control" name="form_type" />
							<input type="submit" style="float:left;width:100px;" name="Submit" value="<?php echo $page_action; ?>" />
						</form>
						<div style="clear:both;display: block;margin-top:10px;font-size: 11px;">* If you already have a contact page and wish to add vCita to that page, please refer to the "Shortcode" integration option below</div>
					</div>
				</div>
			</div>

			<div style="overflow:hidden;clear:both;margin-top: 20px;border: 1px solid #DFDFDF;border-radius: 4px;">
				<h4 style="cursor:pointer;clear:both;padding: 5px 0 5px 5px;margin:0px;overflow:hidden;background: #EEE;margin:0px" onclick="vcita_toggle_option_visibility('vcita_option_sidebar_widget');return false;">
					<span style="float:left;margin-right:5px;padding:0 3px; background-color:white;text-align:center;width:10px;" id="vcita_option_sidebar_widget_marker">+</span>
					<div style="float:left;">Sidebar / Widget </div>
				</h4>
				
				<div style="overflow:hidden;margin-top:10px;px;margin-left:10px;margin-bottom:10px;display:none;" id="vcita_option_sidebar_widget">
					<ol style="margin-top:0;" id="vcita_sidebar_int">
						<li><?php echo vcita_create_link('Preview and Customize', 'widget_implementations', 'widget=widget&key='.$vcita_widget['implementation_key']); ?> the vCita widget</li>
						<li><b>Click</b> the "Widgets" menu on the left (Under "Appearance"). <br/></li>
						<li><b>Drag</b> the "vCita Widget" to the desired location on the right (Recommended as Sidebar).</li>
					</ol>
					
					<div style="clear:both;display: block;margin-top:10px;font-size: 11px;">* Size will be set automatically. If you wish to change it, please choose the "shortcode" integration</div>
				</div>
			</div>

			<div style="overflow:hidden;clear:both;margin-top: 20px;border: 1px solid #DFDFDF;border-radius: 4px;">
				<h4 style="cursor:pointer;clear:both;padding: 5px 0 5px 5px;margin:0px;overflow:hidden;background: #EEE;margin:0px" onclick="vcita_toggle_option_visibility('vcita_option_shortcode');return false;">
					<span style="float:left;margin-right:5px;padding:0 3px; background-color:white;text-align:center;width:10px;" id="vcita_option_shortcode_marker">+</span>
					<div style="float:left;">Wordpress Shortcode</div>
					
				</h4>		

				<div style="overflow:hidden;margin-top: 10px;margin-left:5px;margin-bottom:10px;display:none;" id="vcita_option_shortcode">				
					<div style="overflow:hidden;margin-top:5px;">
						<div style="overflow:hidden;margin-left:15px;" id="vcita_shortcode_int">
							<div style="margin-bottom:10px;">
								Copy the shortcode below to the relevant page.<br>
								Please use height and width parameters to match your site. Width and height set at vCita.com are ignored.
							</div>
							<div style="clear:both;overflow:hidden;">
								<div style="float:left;line-height:50px;width:110px;">Contact form :&nbsp;</div>
								<input readonly style="float:left;width:550px;height:30px;margin: 10px 0;"type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=contact width=500 height=450]"</input>
							</div>
							<div style="clear:both;overflow:hidden;">
								<div style="float:left;line-height:50px;width:110px;">Vertical Sidebar :&nbsp;</div>
								<input readonly style="float:left;width:550px;height:30px;margin: 10px 0;"type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=widget height=400 width=200]"</input>
							</div>
							<div style="clear:both;overflow:hidden;">
								<div style="float:left;line-height:50px;width:110px;">Horizontal Widget :&nbsp;</div>
								<input readonly style="float:left;width:550px;height:30px;margin: 10px 0;"type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=widget height=200]"</input>
							</div>
							<div style="clear:both;overflow:hidden;">
								<div style="float:left;line-height:50px;width:110px;">Buttons only :&nbsp;</div>
								<input readonly style="float:left;width:550px;height:30px;margin: 10px 0;"type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=widget height=100]"</input>
							</div>
							<div style="clear:both;overflow:hidden;">
								<div style="float:left;line-height:50px;width:110px;">Multiple accounts :&nbsp;</div>
								<input readonly style="float:left;width:550px;height:30px;margin: 10px 0;"type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=widget height=200 email=XXX first_name=YYY last_name=ZZZ]"</input>
							</div>

							<p style="margin-bottom:5px;margin-top:5px;">For more advanced options, please <a href="http://wordpress.org/extend/plugins/<?php echo VCITA_WIDGET_UNIQUE_ID; ?>/faq/" target="_blank">look at our FAQ</a></p>
						</div>
					</div>
				</div>
				
				
			</div>
            
        <?php } ?>

			<div style="display:block;clear:both;overflow:hidden;margin-top:20px;">
				<?php if (VCITA_WIDGET_SHOW_EMAIL_PRIVACY == 'true' && $first_time) { ?>
					<p>We will never spam or share your email address. <?php echo vcita_create_link('See our Privacy Policy', 'about/privacy_policy', ''); ?> </p>
				<?php } ?>

				<p style="margin-bottom:5px;margin-top:5px;">
					<div style="float:left;"><b>vCita has a lot more to offer! </b></div>
                    <div style="float:left;">&nbsp;&nbsp;<?php echo vcita_create_link('visit vcita.com', '', 'no_redirect=true'); ?></div>
					<div style="float:left;">&nbsp;or <?php echo vcita_create_link('watch a video', '', 'autoplay=1&no_redirect=true'); ?></div>
                </p>
            </div>
        </div>
    </div>
	
    <?php
}

/**
 * Create the vCita floatting widget Settings form content.
 *
 * This is based on Wordpress guidelines for creating a single widget.
 */
function vcita_widget_admin() {
    extract(vcita_prepare_widget_settings("widget"));

    ?>

    <div id="vcita_config">
        <label for="vcita_title">Title:</label>
        <input type="text" value="<?php echo $vcita_widget['title']; ?>" name="vcita_title" id="vcita_title" class="widefat">

        <hr style="margin: 15px 0;"/>
        <div id="vcita_config_params" style="text-align:right;">
            <label style="display:block;line-height:30px;text-align:left;"> Email:
                <input type="text" onkeypress="vcita_clearNames();" style="float:right;" id="vcita_email" name="vcita_email" value="<?php echo ($vcita_widget['email']); ?>" />
            </label>
            <label style="display:block;line-height:30px;text-align:left;"> First Name:
                <input type="text" style="float:right;" id="vcita_first-name_<?php echo $form_uid;?>" name="vcita_first-name"  <?php echo $disabled; ?> value="<?php echo ($vcita_widget['first_name']); ?>" />
            </label>
            <label style="display:block;line-height:30px;text-align:left;"> Last Name:
                <input type="text" style="float:right;" id="vcita_last-name_<?php echo $form_uid;?>" name="vcita_last-name"  <?php echo $disabled; ?> value="<?php echo ($vcita_widget['last_name']); ?>" />
            </label>
			
            <?php echo vcita_create_user_message($vcita_widget, $update_made); ?>
            <?php echo $config_html; ?>
        </div>
    </div>

    <?php
}

/**
 * Use the current settings and create the vCita widget. - simply call the main vcita_add_contact function with the required parameters
 */
function vcita_widget_content($args) {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);

    echo vcita_add_contact( array('type' => 'widget', 'title' => $vcita_widget['title'], 'height' => '430px'));
}

/**
 * Main function for creating the widget html representation.
 * Transforms the shorcode parameters to the desired iframe call.
 *
 * Syntax as follows:
 * shortcode name - VCITA_WIDGET_SHORTCODE
 *
 * Arguments:
 * @param  type - Type of widget, can be "contact" or "widget". default is "contact"
 * @param email - The associated expert email. default is the currently saved "UID"
 * @param first_name - The first name of the expert. default is using the name of the associated Expert's UID
 * @param last_name - The last name of the expert. default is using the name of the associated Expert's UID
 * @param uid - The Unique identification for the user - if this is used it overrides the email / first name / last name
 * @param title - The title which will be above the widget. default is empty
 * @param widget - The width of the widget. default is "100%"
 * @param height - The height of the widget. default is "450px"
 *
 */
function vcita_add_contact($atts) {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);

    extract(shortcode_atts(array(
        'type' => 'contact',
        'email' => '',
        'first_name' => '',
        'last_name' => '',
        'uid' => $vcita_widget['uid'],
        'id' => '',
        'title' => '',
        'width' => '100%',
        'height' => '400px',
		'theme' => 'blue',
    ), $atts));

	// If user isn't available - try and create one.
    if (!empty($email)) {
		$vcita_widget['email'] = $email;
		$vcita_widget['first_name'] = $first_name;
		$vcita_widget['last_name'] = $last_name;
		$vcita_widget['uid'] = '';
        $vcita_widget = generate_or_validate_user($vcita_widget);
		
		// Don't save the user as the widget user - just use it 
		$id = $vcita_widget["uid"]; 
		
    } else if (empty($id)) {
		$id = $uid;
	}
	
	// Only display content if the id is available (merge of the id and uid) 
	if (!empty($id)) { 
		if (!empty($title)) {
			echo "<h2 style=\"margin-bottom:8px;\">$title</h2>";
		}

		return vcita_create_embed_code($type, $id, $width, $height, $theme);
	}
}

/**
 * Initialize the vCita widget by registering the widget hooks
 */
function vcita_init() {
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ){
        return;
    }

	vcita_initialize_data();
			
	wp_register_sidebar_widget('vcita_widget_id', 'vCita Widget', 'vcita_widget_content', array('description' => "Encourage visitors to contact or schedule meetings with you."));
	wp_register_widget_control('vcita_widget_id', 'vCita Widget', 'vcita_widget_admin', array('description' => "Encourage visitors to contact or schedule meetings with you."));
	add_filter('plugin_action_links', 'vcita_add_settings_link', 10, 2 );

	register_uninstall_hook(VCITA_WIDGET_UNIQUE_LOCATION, 'vcita_uninstall');
}

/**
 * Remove the vCita widget and page if available
 */
function vcita_uninstall() {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
	
	vcita_trash_current_page($vcita_widget);
	
    delete_option(VCITA_WIDGET_KEY);
}

/**
 * Initialiaze the widget data system params
 */
function vcita_initialize_data() {
	$vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
	
	// Save if this is a new installation or not.
	if (empty($vcita_widget) || (!isset($vcita_widget['uid']) && !isset($vcita_widget['version']))) {
		$vcita_widget = array ('new_install' => 'true');

	} else if ($vcita_widget['new_install'] != 'true')  {
		$vcita_widget['new_install'] = 'false';
	}
	
	// Currently no migration is needed
	$vcita_widget['version'] = VCITA_WIDGET_VERSION;
	
	update_option(VCITA_WIDGET_KEY, $vcita_widget);
}

/** 
 * Check the current user user - either by the saved property or from wordpress
 */
function vcita_get_email($widget_params) {
	return empty($widget_params['email']) ? get_option('admin_email') : $widget_params['email'];
}

/**
 * Update the settings link to point to the correct location
 */
function vcita_add_settings_link($links, $file) {
	if ($file == plugin_basename(VCITA_WIDGET_UNIQUE_LOCATION)) {
		$settings_link = '<a href="' . admin_url("plugins.php?page=".plugin_basename(__FILE__)) . '">Settings</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
 }


/* --- Internal Methods --- */

/**
 * Get the edit link to the requested page
 */
function get_page_edit_link($page_id) {
	$page = get_page($page_id);
	return get_edit_post_link($page_id);
}

/**
 * Prepare all the common parameters for creating the vCita settings.
 *
 * It also initializes the widget for the first time and stores the form data after the user saves the changes
 *
 * @param widget_type - The type of widget to be stored for next usage
 */
function vcita_prepare_widget_settings($type) {
    $form_uid = rand();
	$uninitialized = false;

    if(empty($_POST)) {
        //Normal page display
        $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
        $update_made = false;

        // Create a initial parameters - This form wasn't created yet - set to default
        if (!isset($vcita_widget['created']) || empty($vcita_widget['created'])) {
            $vcita_widget = create_initial_parameters();
			$uninitialized = true;
        }

    } else {
        //Form data sent
        $update_made = true;

        if ($_POST["form_type"] == "page_control") {
            $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
        } else {
            $vcita_widget = (array) save_user_params();
        }
    }

    if ($type == "widget") {
        $config_floating = "";
    } else {
        $config_floating = "float:left;";
    }

	// In case not empty user
	// Generate the user if he isn't available or update to the latest user data from vCita server
	if (!$uninitialized) {
		if (empty($vcita_widget["uid"])) {
			$vcita_widget = (array) generate_or_validate_user($vcita_widget);
		} else {
			$vcita_widget = (array) vcita_get_user($vcita_widget);
		}

		update_option(VCITA_WIDGET_KEY, $vcita_widget);
	}

    $config_html = "<div style='clear:both;text-align:left;display:block;padding-top:5px;'></div>";

    if (empty($vcita_widget["uid"])) {
        $disabled = "";
        $first_time = true;
		
	} else {
		$first_time = false;
        $disabled= "disabled=true title='To change your details, ";
		
        if ($vcita_widget['confirmed']) {
			$customize_link = vcita_create_link('Customize', 'widget_implementations', 'key='.$vcita_widget['implementation_key'].'&widget=widget');
			$set_meeting_pref_link = vcita_create_link('Meeting Preferences', 'settings', 'section=meetings');
			$set_profile_link = vcita_create_link('Edit Email/Profile', 'settings', 'section=profile');
			
			$disabled .= "please use the \"Customize\" link below.'";
            $config_html = "<div style='clear:both;text-align:left;display:block;padding:5px 0 10px 0;overflow:hidden;'>
                            <div style='margin-right:5px;".$config_floating."'>".$set_meeting_pref_link."</div>
							<div style='margin-right:5px;".$config_floating."'>".$set_profile_link."</div>";
							
			if ($type == "widget") {
				$config_html .= "<div style='margin-right:5px;".$config_floating."'>".$customize_link."</div>";
			}
			
			$config_html .= "</div>";
        } else {
			$disabled .= "please follow the instructions emailed to ".$vcita_widget["email"]."'";
		}
    }

    vcita_embed_clear_names(array("vcita_first-name_".$form_uid, "vcita_last-name_".$form_uid));

    return compact('vcita_widget', 'disabled', 'config_html', 'form_uid', 'update_made', 'first_time');
}

/**
 * Utility function to create a link with the correct host and all the required information.
 */
function vcita_create_link($caption, $page, $params = "", $options = array()) {
	$origin = empty($options['origin']) ? 'int.4' : $options['origin'];
	$style = empty($options['style']) ? '' : $options['style'];
	$new_page = empty($options['new_page']) ? true : $options['new_page'];
	
	$params_prefix = empty($params) ? "" : "&";
	$origin_prefix = empty($origin) ? "" : "&";
	
	$link = "http://".VCITA_SERVER_BASE."/".$page."?ref=".VCITA_WIDGET_API_KEY.$params_prefix.$params.$origin_prefix.$origin;
	
	return "<a href=\"".$link."\"".($new_page ? " target='_blank'" : "")." style=".$style.">".$caption."</a>";
}

/**
 * Save the form data into the Wordpress variable
 */
function save_user_params() {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);

	if ($_POST['form_type'] == 'engage_enable_control') {
		$vcita_widget['engage_active'] = ($_POST['Submit'] == 'Activate') ? 'true' : 'false';
	
	} else {
		$previous_email = vcita_default_if_non($vcita_widget, 'email');
		$vcita_widget['created'] = 1;
		$vcita_widget['email'] = vcita_default_if_non($_POST, 'vcita_email');
		$vcita_widget['first_name'] = isset($_POST['vcita_first-name']) ? stripslashes($_POST['vcita_first-name']) : '';
		$vcita_widget['last_name'] = isset($_POST['vcita_last-name']) ? stripslashes($_POST['vcita_last-name']) : '';
		$vcita_widget['title'] = isset($_POST['vcita_title']) ? stripslashes($_POST['vcita_title']) : '';

		if ($previous_email != $vcita_widget['email']) { // Email changes - reset id and keys
			$vcita_widget['uid'] = '';
			$vcita_widget['confirmation_token'] = '';
			$vcita_widget['implementation_key'] = '';
		}
	}

    update_option(VCITA_WIDGET_KEY, $vcita_widget);
	
    return $vcita_widget;
}

/**
 *  Use the vCita API to get a user, either create a new one or get the id of an available user
 *
 * @return array of the user name, id and if he finished the registration or not
 */
function generate_or_validate_user($widget_params) {
    extract(vcita_post_contents("http://".VCITA_SERVER_BASE."/api/experts?".
						"&id=" .urlencode($widget_params['uid']).
						"&email=" .urlencode($widget_params['email']).
                        "&first_name=" .urlencode($widget_params['first_name']).
                        "&last_name=" . urlencode($widget_params['last_name']).
						"&confirmation_token=".urlencode($widget_params['confirmation_token']).
                        "&ref=".VCITA_WIDGET_API_KEY.""));
						
	return vcita_parse_expert_data($success, $widget_params, $raw_data);
}

/**
 * Get the content for the current user
 *
 * @return array of the user name, id and if he finished the registration or not
 */
function vcita_get_user($widget_params) {
    extract(vcita_get_contents("http://".VCITA_SERVER_BASE."/api/experts/".urlencode($widget_params['uid'])));
						
	return vcita_parse_expert_data($success, $widget_params, $raw_data);
}

/**
 * Take the received data and parse it
 * 
 * Returns the newly updated widgets parameters.
 */
function vcita_parse_expert_data($success, $widget_params, $raw_data) {

    $previous_id = $widget_params['uid'];
	$widget_params['uid'] = '';
	
    if (!$success) {
        $widget_params['last_error'] = "Temporary problems, please try again";

    } else {
        $data = json_decode($raw_data);

        if ($data->{'success'} == 1) {
			$widget_params['first_name'] = $data->{'first_name'};
            $widget_params['last_name'] = $data->{'last_name'};
			$widget_params['engage_delay'] = $data->{'engage_delay'};
			$widget_params['engage_text'] = $data->{'engage_text'};
            $widget_params['confirmed'] = $data->{'confirmed'};
	        $widget_params['last_error'] = "";
			$widget_params['uid'] = $data->{'id'};

			if ($previous_id != $data->{'id'} || !empty($data->{'confirmation_token'})) {
				$widget_params['confirmation_token'] = $data->{'confirmation_token'};
				$widget_params['implementation_key'] = $data->{'implementation_key'};
				
				// Active by Default if not previsouly disabled
				$widget_params['engage_active'] = vcita_default_if_non($widget_params, 'engage_active', 'true');
			}

        } else {
            $widget_params['last_error'] = $data-> {'error'};
        }
    }

    return $widget_params;
}

/**
 * Perform an HTTP GET Call to retrieve the data for the required content.
 * 
 * @param $url
 * @return array - raw_data and a success flag
 */
function vcita_get_contents($url) {
    $response = wp_remote_get($url, array('header' => array('Accept' => 'application/json; charset=utf-8'),
                                          'timeout' => 10));

    return vcita_parse_response($response);
}

/**
 * Perform an HTTP POST Call to retrieve the data for the required content.
 *
 * @param $url
 * @return array - raw_data and a success flag
 */
function vcita_post_contents($url) {
    $response  = wp_remote_post($url, array('header' => array('Accept' => 'application/json; charset=utf-8'),
                                          'timeout' => 10));

    return vcita_parse_response($response);
}

/**
 * Parse the HTTP response and return the data and if was successful or not.
 */
function vcita_parse_response($response) {
    $success = false;
    $raw_data = "Unknown error";
    
    if (is_wp_error($response)) {
        $raw_data = $response->get_error_message();
    
    } elseif (!empty($response['response'])) {
        if ($response['response']['code'] != 200) {
            $raw_data = $response['response']['message'];
        } else {
            $success = true;
            $raw_data = $response['body'];
        }
    }
    
    return compact('raw_data', 'success');
}

/**
 * Initials the vCita Widget parameters
 */
function create_initial_parameters() {
	$old_params = (array) get_option(VCITA_WIDGET_KEY);
	
    $vcita_widget = array('title' => "Contact me using vCita", 
						   'uid' => '',
						   'new_install' => isset($old_params['new_install']) ? $old_params['new_install'] : 'false',
						   'version' => VCITA_WIDGET_VERSION,
						   'contact_page_active' => VCITA_WIDGET_CONTACT_FORM_WIDGET,
						   'engage_active' => isset($old_params['new_install']) ? $old_params['new_install'] : 'false', // Only active if this is new install
						   'confirmation_token' => '',
						   'implementation_key' => '',
						   'dismiss' => vcita_default_if_non($old_params, 'dismiss'),
						   );
	update_option(VCITA_WIDGET_KEY, $vcita_widget);
	
    return $vcita_widget;
}

/**
 * Create the The iframe HTML Tag according to the given paramters
 */
function vcita_create_embed_code($type, $uid, $width, $height, $theme = 'blue') {

    // Only present if UID is available 
    if (isset($uid) && !empty($uid)) {
        $code = "<iframe frameborder='0' src='http://".VCITA_SERVER_BASE."/" . urlencode($uid) . "/" . $type . "/?ref=".VCITA_WIDGET_API_KEY."&theme=".$theme.
        "' width='".$width."' height='".$height."'></iframe>";
    }

	return $code;
}

/*
 * Make sure the page is published:
 * 1. If none available - Create a new one
 * 2. If page is in the Trash - Restore it
 * 3. If page is in a different state - Create a new one
 */
function make_sure_page_published($vcita_widget) {
    $page_id = vcita_default_if_non($vcita_widget, 'page_id');
	$page = get_page($page_id);

	if (empty($page)) {
		$page_id = add_contact_page();

	} elseif ($page->{"post_status"} == "trash") {
		wp_untrash_post($page_id);

	} elseif ($page->{"post_status"} != "publish") {
		$page_id = add_contact_page();
	}

    $vcita_widget['page_id'] = $page_id;
	$vcita_widget['contact_page_active'] = 'true';
	update_option(VCITA_WIDGET_KEY, $vcita_widget);

	return $vcita_widget;
}

/**
 * Check that the page is available and published
 */
function is_page_available($vcita_widget) {
	if (!isset($vcita_widget['page_id']) || empty($vcita_widget['page_id'])) {
		return false;
	}
	
	$page_id = $vcita_widget['page_id'];
	$page = get_page($page_id);

	return !empty($page) && $page->{"post_status"} == "publish";
}

/**
 * Add A new contact page with vCita widget content in it.
 */
function add_contact_page() {
    return wp_insert_post(array(
        'post_name' => 'Contact',
        'post_title' => 'Contact',
        'post_type' => 'page',
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'post_content' => '['.VCITA_WIDGET_SHORTCODE.']'));
}

/**
 * Move a page to the Trash according to its ID.
 * This only takes place if the given page is available and currently published.
 */
function vcita_trash_current_page($widget_params) {
	
	if (isset($widget_params['page_id']) && !empty($widget_params['page_id'])) {
		$page_id = $widget_params['page_id'];
		$page = get_page($page_id);
		
		if (!empty($page) && $page->{"post_status"} == "publish") {
			wp_trash_post($page_id);
		}
	}
}

/**
 * Create the message which will be displayed to the user after performing an update to the widget settings.
 * The message is created according to if an error had happen and if the user had finished the registration or not.
 */
function vcita_create_user_message($vcita_widget, $update_made) {

    if (!empty($vcita_widget['uid'])) {

        // If update wasn't made, keep the message without info about the last change
        if ($update_made) {
			if ($_POST['Submit'] == "Save Settings") {
				$message .= "<div>Account <b>".$vcita_widget['email']."</b> Saved.</div><br> ";
			} else {
				$message = "<b>Changes saved</b>";
			}
        } else {
            $message = "";
        }

        $message_type = "updated below-h2"; // Wordpress classes for showing a notification box
		
        if (!$vcita_widget['confirmed']) {
			if ($update_made) {
				$message .= "<br>";
			}
			
			$message .= "<div style='overflow:hidden'>";
            $prefix = "";

			if (!empty($vcita_widget['confirmation_token'])) {
				$message .= "<div style='float:left;'>Please <b>".vcita_create_link('configure your contact and meeting preferences', 'users/confirmation', 'confirmation_token='.$vcita_widget['confirmation_token'], array('style' => 'text-decoration:underline;'))."</b> or </div>";
			} else {
				$prefix = "Please";
			}
			
			$message .= "<div style='float:left;display:block;'>".$prefix."&nbsp;follow instructions sent to your email.</div>";
			
			if (empty($vcita_widget['confirmation_token'])) {
				$message .= "&nbsp;".vcita_create_link("Send email again", 'user/send_confirmation', 'email='.$vcita_widget['email'], array('style' => 'font-weight:bold;'));
			}
			
			$message .= "</div>";
        }

    } elseif (!empty($vcita_widget['last_error'])) {
        $message = "<b>".$vcita_widget['last_error']."</b>";
        $message_type = "error below-h2";
    }

    if (empty($message)) {
        return "";
    } else {
        return "<div class='".$message_type."' style='padding:5px;text-align:left;'>".$message."</div>";
    }
}

/**
 * Generic method to return default value if index doesn't exist in the array
 * Default value for the default is empty string
 */
function vcita_default_if_non($arr_obj, $index, $default_value = '') {
	return isset($arr_obj) && isset($arr_obj[$index]) ? $arr_obj[$index] : $default_value;
}

/**
 * Embed the function for toggling visibility of an item.
 */
function vcita_embed_toggle_visibility() {
	?>
	<script type='text/javascript'>
	    function vcita_toggle_visibility(id) {
		    document.getElementById(id).style.display = (document.getElementById(id).style.display == 'block') ? 'none' : 'block';
	    }
		
		function vcita_toggle_option_visibility(id) {
			vcita_toggle_visibility(id);
		    document.getElementById(id + "_marker").innerHTML = (document.getElementById(id).style.display == 'block') ? '-' : '+';
	    }
	</script>
	<?php
}

/**
 * Create a Javascript function which go over on all the given ids and for each, clear the field and enable it
 */
function vcita_embed_clear_names($ids) {
	?>
	<script type='text/javascript'>
		function vcita_clearNames() {

	<?php
		foreach ($ids as $id) {
			vcita_embed_clear_name($id);
		}
	?>

	    }
	</script>
	<?php
}

/**
 * Create a Javascript snippet which will take the id and will clear the field.
 * By clear it will do the following: erase the field content, enable the field and clear the title element.
 *
 * This only changes fields which are disabled
 */
function vcita_embed_clear_name($id) {
	?>
    element = document.getElementById("<?php echo $id ?>");

    if (element.disabled) {
        element.value = '';
        element.removeAttribute('disabled');
        element.removeAttribute('title');
    }

	<?php
}
?>