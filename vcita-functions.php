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
			var vcUrl = "www.vcita.com/" + "<?php echo $vcita_widget['uid'] ?>" + '/loader.js';
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
	
	if (!empty($vcita_widget['uid'])) {
		// Will be added to the head of the page
		// Currently disabled
		// wp_enqueue_script('vcita-engage', 'http://www.vcita.com/'.$vcita_widget['uid'].'/loader.js?auto_load=0');
	}
}

/**
 *  Add the vCita widget to the "Settings" Side Menu
 */
function vcita_admin_actions() {
    if ( function_exists('add_options_page') ) {
		$admin_page_suffix = add_submenu_page('plugins.php', __(VCITA_WIDGET_MENU_NAME, VCITA_WIDGET_SHORTCODE), __(VCITA_WIDGET_MENU_NAME, VCITA_WIDGET_SHORTCODE), 'manage_options', __FILE__,'vcita_settings_menu');
		add_action('admin_print_scripts-'.$admin_page_suffix, 'vcita_add_active_engage_admin');
    }
}

/**
 * Create the Main vCita Settings form content.
 *
 * The form is constructed from a list of input fields and a preview for the result
 */
function vcita_settings_menu() {

	extract(vcita_prepare_widget_settings($_POST['vcita_widget-type'], "settings"));

	// Check the dedicated page flag - If it is on, make sure a page is available, if not - Trash the page
	if ($update_made) {
		if ($_POST['Submit'] == "Disable Page") {
			trash_page($vcita_widget['page_id']);
				
			$vcita_widget['contact_page_active'] = 'false';
			update_option(VCITA_WIDGET_KEY, $vcita_widget);
						
		// Make sure page is live if requested to or as default
		} else if ($_POST['Submit'] == "Activate Page" || $vcita_widget['contact_page_active'] == 'true') {
			$vcita_widget = make_sure_page_published($vcita_widget);
		}
	}

	if (is_page_available($vcita_widget)) {

		$page_action = "Disable Page";
		$page_status = "Contact page has been <span style='color:green;'><b>created</b></span>";
		$page_available = true;
	} else {
		$page_action = "Activate Page";
		$page_status = "Contact page is <span style='color:red;'><b>disabled</b></span>";
		$page_available = false;
	}


	if ($first_time) {
		$form_hidden = "";
	} else {
		$form_hidden = "display:none";
	}
	
	vcita_embed_toggle_preview_visibility();
	vcita_embed_control_engage_edit_visibility();
	vcita_embed_validateEngageForm();
	
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
	
	
    ?>
    <div class="wrap" style="max-width:830px;">
        <h2><img src="http://www.vcita.com/images/logo.png"></h2>
        <?php echo vcita_create_user_message($vcita_widget, $update_made); ?>

        <?php if ($first_time) { ?>
            <div><p>To Create your Contact Form please provide your details below:</p></div>
		<?php } ?>

        <div style="float:left;margin-right:20px;width:310px;margin-top:5px;">

        <form name="vcita_form" method="post" id="vcita_form_<?php echo $form_uid;?>" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="<?php echo $form_hidden; ?>">
			<input type="hidden" value="main_settings" name="form_type" />
			<div style="width:310px">
				<div style="display:block;clear:both;">
					<span style="display:inline-block;width:110px;"><?php _e("Email: " ); ?></span>
                    <input type="text" onkeypress="vcita_clearNames();" name="vcita_email" value="<?php echo $vcita_widget['email']; ?>" size="25">
                </div>

                <div style="display:block;clear:both;">
                    <span style="display:inline-block;width:110px;"><?php _e("First name: " ); ?></span>
                    <input type="text" name="vcita_first-name" id="vcita_first-name_<?php echo $form_uid;?>" <?php echo $disabled; ?> value="<?php echo $vcita_widget['first_name']; ?>" size="25">
                </div>

                <div style="display:block;clear:both;">
                    <span style="display:inline-block;width:110px;"><?php _e("Last user: " ); ?></span>
                    <input type="text" name="vcita_last-name" id="vcita_last-name_<?php echo $form_uid;?>" <?php echo $disabled; ?> value="<?php echo $vcita_widget['last_name']; ?>" size="25">
                </div>

                <div style="display:block;clear:both;">
                    <span style="display:inline-block;width:110px;"><?php _e("Professional Title: " ); ?></span>
                    <input type="text" name="vcita_prof-til" id="vcita_prof-til_<?php echo $form_uid;?>" <?php echo $disabled; ?> value="<?php echo $vcita_widget['prof_title']; ?>" size="25">
                </div>

                <p class="submit" style="padding-top:5px;">
                    <input type="submit" style="float:left;" name="Submit" value="<?php _e('Save Settings') ?>" />
                    <?php if (!$first_time) { ?>
						<input type="hidden" name="user_change" value="true" >
                        <a href="#" style="text-align:left;float:left;padding:5px 0 0 5px;"
                            onclick="document.getElementById('vcita_active_<?php echo $form_uid;?>').style.display='block';document.getElementById('vcita_form_<?php echo $form_uid;?>').style.display= 'none';">Cancel</a>
                    <?php } ?>
                </p>
            </div>
        </form>
		
        <?php if (!$first_time) { ?>
			<div>
                <div id="vcita_active_<?php echo $form_uid;?>">
                    Account:&nbsp;<b><?php echo $vcita_widget['email']; ?></b>
                    <input type="button"
                            style="margin-left:10px;float:right;width:100px;"
                            name="Change"
                            onclick="document.getElementById('vcita_active_<?php echo $form_uid;?>').style.display= 'none';document.getElementById('vcita_form_<?php echo $form_uid;?>').style.display= 'block'; "
                            value="<?php _e('Change Account') ?>" />
                </div>

                <?php echo $config_html; ?>

                <h4 style="clear:both;border-bottom:1px solid gray;padding-top:10px;margin:0px;">vCita Contact Page</h4>
				<div style="margin-top:5px;">
                <div style="float:left;line-height:20px;margin-right:5px;"><?php echo $page_status;?></div>

				<form name="vcita_page_control_form" style="float:right;" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
					<input type="hidden" value="page_control" name="form_type" />
					<input type="submit" style="float:left;width:100px;" name="Submit" value="<?php _e($page_action) ?>" />
				</form>
				</div>
			</div>
				
				
			<h4 style="clear:both;float:left;border-bottom:1px solid gray;padding-top: 25px;margin:0px;width:100%;">
				<span style="float:left;color:red;margin-right:5px;">New!</span>
				<span style="float:left;">vCita Active Engage</span>
				<a href="http://www.vcita.com/about/active_engage" style="float:right;text-decorations:none;" target="_blank">See an example</a>
			</h4>
			<div style="clear:both;margin-top:5px;">
				<div style="display:block;margin-top:10px;">
					<div >Generate more leads by actively offering visitors</div>
					<div style="float:left;">to contact - </div>
					<div style="margin-left:2px;float:left;<?php echo $engage_active_style ?>"><?php echo $engage_active_state; ?></div>
					<div style="clear:both;margin-top:8px;width:100%;float:left;" id="vcita_engage_configure_<?php echo $form_uid;?>">
						<form id="vcita_engage_configure_control_<?php echo $form_uid ?>" name="vcita_engage_configure_control" style="float:right;" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
							<input type="hidden" value="engage_enable_control" name="form_type" />
							<input type="submit" style="float:left;width:100px;" name="Submit" value="<?php _e($engage_toggle_action) ?>" />
						</form>
						<input type="button" value="Configure" style="width:100px;float:right;<?php echo $engage_configure_visibility ?>" onclick='vcita_control_engage_edit_visibility("<?php echo $form_uid;?>", true)'>
					</div>
					
					<div id="vcita_active_engage_details_<?php echo $form_uid;?>" style="margin-top:10px;clear:both;display:none;overflow:hidden;">
						<form name="vcita_engage_form" method="post" id="vcita_engage_form_<?php echo $form_uid;?>" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" onsubmit="return vcita_validateEngageForm('<?php echo $form_uid;?>');">
							<input type="hidden" value="engage_settings" name="form_type" />
							<div style="display:block;clear:both;margin-top:10px;">
								<span >Wait</span>
								<input type="text" name="vcita_engage-delay" style="width:30px;margin-left: 5px;" id="vcita_engage-delay_<?php echo $form_uid;?>" <?php echo $engage_disabled_attr; ?> <?php echo $engage_input_title; ?> value="<?php echo $vcita_widget['engage_delay']; ?>" size="25">
								<span >seconds before approaching a visitor.</span>
							</div>
							<div style="display:block;">
								<span>Include the following message:</span>
								<textarea name="vcita_engage-text" style="width:300px;display:block;margin-top:5px;" rows="4" id="vcita_engage-text_<?php echo $form_uid;?>" <?php echo $engage_disabled_attr; ?> <?php echo $engage_input_title; ?> ><?php echo $vcita_widget['engage_text']; ?></textarea>
							</div>
							<div style="margin-top:10px">
								<input type="submit" style="float:left;" name="Submit" value="<?php _e('Save Settings') ?>" >
								<a href="#" id="vcita_engage_cancel_button_<?php echo $form_uid;?>" onclick="vcita_control_engage_edit_visibility('<?php echo $form_uid;?>', false);" style="margin-left:10px;display:none;"> Cancel</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		
		<?php } ?>

        <?php if (!$first_time) { ?>
            <h4 style="clear:both;border-bottom:1px solid gray;padding-top: 25px;margin:0px;">Add vCita to ALL pages as a sidebar</h4>
            <div>
                <ol>
                    <li><b>Click</b> the "Widgets" menu on the left (Under "Appearance"). <br/></li>
                    <li><b>Drag</b> the "vCita Widget" to the desired location on the right (Recommended as Sidebar).</li>
                </ol>
            </div>

            <h4 style="clear:both;border-bottom:1px solid gray;padding-top: 25px;margin:0px;">Add vCita to an existing page</h4>
                <div style="clear:both;margin-top:5px;">
                    You can also use the following code to manually add vCita to any post or page :

                    <div style="display:block;margin-top:10px;">
                        <input id="vcita_embed_type_contact" name="vcita_embed_type" <?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? 'checked="checked"' : ""); ?> type="radio" style="border:0 none;outline:0 none;" value="contact" onclick="vcita_toggle_preview('contact', '<?php echo $form_uid;?>', true);">
			            <label for="vcita_embed_type_contact" onclick="vcita_toggle_preview('contact', '<?php echo $form_uid;?>');">Contact Form</label>
                        <input id="vcita_embed_type_widget" name="vcita_embed_type" <?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "": 'checked="checked"'); ?> type="radio" value="widget" style="border:0 none;outline:0 none;" onclick="vcita_toggle_preview('widget', '<?php echo $form_uid;?>', true);">
			            <label for="vcita_embed_type_widget" onclick="vcita_toggle_preview('widget', '<?php echo $form_uid;?>');">Widget</label>
                    </div>

                    <input readonly type="text" id="vcita_embed_contact_<?php echo $form_uid;?>" style="width:300px;<?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "": 'display:none;'); ?>height:30px;margin: 10px 0;" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?>]"</input>
                    <input readonly type="text" id="vcita_embed_widget_<?php echo $form_uid;?>" style="width:300px;<?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "display:none;": ""); ?>height:30px;margin: 10px 0;" onclick="this.select();" value="[<?php echo VCITA_WIDGET_SHORTCODE; ?> type=widget height=350]"</input>

                </div>
        <?php } ?>

			<div style="display:block;clear:both;">
                <br/>
				<p>For more advanced options, please <a href="http://wordpress.org/extend/plugins/".<?php echo VCITA_WIDGET_UNIQUE_ID; ?>."/faq/">look at the FAQ</a></p>
                <p><b>vCita has a lot more to offer! </b> <br/>
                    <a href="http://www.vcita.com/?autoplay=1&no_redirect=true" target="_blank">To learn more Take a Tour</a>
                </p>
            </div>
        </div>

        <div style="float:left;">
            <div style="clear:both;border-bottom:1px solid gray;padding: 0 0 5px 0;margin:0px;width:490px;overflow: hidden;">
				<div style="float:left">Preview:</div>
				<div style="float:left;padding: 0 0 0px 5px;line-height: 13px;">
					<input id="vcita_preview_type_contact" <?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? 'checked="checked"' : ""); ?> name="vcita_preview_type" type="radio" style="border:0 none;outline:0 none;" value="contact" onclick="vcita_toggle_preview('contact', '<?php echo $form_uid;?>');">
					<label for="vcita_preview_type_contact" onclick="vcita_toggle_preview('contact', '<?php echo $form_uid;?>');">Contact Form</label>
					<input id="vcita_preview_type_widget" <?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "": 'checked="checked"'); ?> name="vcita_preview_type" type="radio" value="widget" style="border:0 none;outline:0 none;" onclick="vcita_toggle_preview('widget', '<?php echo $form_uid;?>');">
					<label for="vcita_preview_type_widget" onclick="vcita_toggle_preview('widget', '<?php echo $form_uid;?>');">Widget</label>
				</div>
			</div>
            <p>
                <div id="vcita_preview_contact_<?php echo $form_uid;?>" style="<?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "": 'display:none;'); ?>width:490px;">
                    <?php echo vcita_create_embed_code("contact", $vcita_widget['uid'], $vcita_widget['email'], $vcita_widget['first_name'], $vcita_widget['last_name'], '490px', '400px', $vcita_widget['prof_title'], empty($vcita_widget['uid'])) ?>
                </div>
                <div id="vcita_preview_widget_<?php echo $form_uid;?>" style="<?php echo ((VCITA_WIDGET_CONTACT_FORM_WIDGET == 'true') ? "display:none;": ""); ?>;width:490px;">
                    <?php echo vcita_create_embed_code("widget", $vcita_widget['uid'], $vcita_widget['email'], $vcita_widget['first_name'], $vcita_widget['last_name'], '200px', '400px', $vcita_widget['prof_title'], empty($vcita_widget['uid'])) ?>
                </div>
            </p>
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
    extract(vcita_prepare_widget_settings("", "widget"));

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
			<label style="display:block;line-height:30px;text-align:left;"> Prof. Title:
                <input type="text" style="float:right;" id="vcita_prof-til_<?php echo $form_uid;?>" name="vcita_prof-til"  <?php echo $disabled; ?> value="<?php echo ($vcita_widget['prof_title']); ?>" />
            </label>
			
			<?php echo vcita_create_theme_selection($vcita_widget); ?>

            <?php echo vcita_create_user_message($vcita_widget, $update_made); ?>
            <?php echo $config_html; ?>
        </div>
    </div>

    <?php
}

/** 
 * Doc
 */ 
function vcita_create_theme_selection($widget_params) {

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
        'prof_title' => '',
        'uid' => $vcita_widget['uid'],
        'id' => $vcita_widget['uid'],
        'title' => '',
        'width' => '100%',
        'height' => '400px',
		'theme' => 'blue',
    ), $atts));

    if (!empty($title)) {
        echo "<h2 style=\"margin-bottom:8px;\">$title</h2>";
    }

    if (empty($id)) {
        $id = $uid;
    }

    return vcita_create_embed_code($type, $id, $email, $first_name, $last_name, $width, $height, $prof_title, false, $theme);
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
	add_filter('plugin_action_links', 'add_settings_link', 10, 2 );

	register_uninstall_hook(VCITA_WIDGET_UNIQUE_LOCATION, 'vcita_uninstall');
}

/**
 * Remove the vCita widget and page if available
 */
function vcita_uninstall() {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
    trash_page($vcita_widget["page_id"]);

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
	
	if (!isset($vcita_widget['version'])) {
		// New install 
		$vcita_widget = vcita_check_expert_available($vcita_widget, 'new');
		
	} else if ($vcita_widget['version'] != VCITA_WIDGET_VERSION) {
		// Upgrade 
		$vcita_widget = vcita_check_expert_available($vcita_widget, $vcita_widget['version']);
	}
	
	// Currently no migration is needed
	$vcita_widget['version'] = VCITA_WIDGET_VERSION;
	
	update_option(VCITA_WIDGET_KEY, $vcita_widget);
}

/* 
 * Check if the user is already available in vCita
 */
function vcita_check_expert_available($widget_params, $vcita_version) {
	extract(vcita_get_contents("http://www.vcita.com/experts/check_available?ref=".VCITA_WIDGET_API_KEY."&email=".
			urlencode(vcita_get_email($widget_params))."&version=".VCITA_WIDGET_VERSION."&previous=".$vcita_version));

	if ($success && !empty($raw_data)) {
		$data = json_decode($raw_data);
		
		if (!empty($data) && $data->{'available'}) {
			$widget_params = vcita_parse_expert_data($success, $widget_params, $raw_data);
		}
	}
	
	return $widget_params;
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
function add_settings_link($links, $file) {
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
function vcita_prepare_widget_settings($widget_type, $type) {
    $form_uid = rand();
    $uninitialized = false;

    if(empty($_POST)) {
        //Normal page display
        $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
        $update_made = false;


        // Create a initial parameters
        if (is_null($vcita_widget['created'])) {
            $vcita_widget = create_initial_parameters();
			$uninitialized = true;
        }

    } else {
        //Form data sent
        $update_made = true;

        if ($_POST["form_type"] == "page_control") {
            $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);
        } else {
            $vcita_widget = (array) save_user_params($widget_type);
        }
    }

    if ($type == "widget") {
        $config_floating = "";
    } else {
        $config_floating = "float:left;";
    }

    if (!$uninitialized) {
        $vcita_widget = (array) generate_or_validate_user($vcita_widget);
        update_option(VCITA_WIDGET_KEY, $vcita_widget);
    }

    $config_html = "<div style='clear:both;text-align:left;display:block;padding-top:5px;'></div>";

    if (!empty($vcita_widget["uid"])) {
        $first_time = false;
        $disabled= "disabled=true title='To change your details, ";

        if ($vcita_widget['confirmed']) {
			$disabled .= "please use the \"Edit Profile\" link below.'";
            $config_html = "<div style='clear:both;".$config_floating."text-align:left;display:block;padding:5px 0 10px 0;'>
                            <div style='margin-right:5px;".$config_floating."'><a href='http://www.vcita.com/settings?section=profile' target='_blank'>Edit Profile</a></div>
                            <div style='margin-right:5px;".$config_floating."'><a href='http://www.vcita.com/settings?section=schedule' target='_blank'>Edit Availability</a></div>
                            <div style='margin-right:5px;".$config_floating."'><a href='http://www.vcita.com/settings?section=configuration' target='_blank'>Meeting Preferences</a></div></div>";
        } else {
			$disabled .= "please follow the instructions emailed to ".$vcita_widget["email"]."'";
			
			if (!empty($vcita_widget['confirmation_token'])) {
				$config_html = "<div style='clear:both;".($type == "widget" ? "" : "float:right").";text-align:right;display:block;padding:5px 0 10px 0;'>
                                  <div style='margin-right:5px;".$config_floating."'><b><a href='http://www.vcita.com/users/confirmation?confirmation_token=".$vcita_widget['confirmation_token']."&o=int.4' target='_blank'>Set meeting preferences</a></b></div>
							   </div>";
			}
		}

    } else {
        $disabled = "";
        $first_time = true;
    }

    vcita_embed_clear_names(array("vcita_first-name_".$form_uid, "vcita_last-name_".$form_uid, "vcita_prof-til_".$form_uid));

    return compact('vcita_widget', 'disabled', 'config_html', 'form_uid', 'update_made', 'first_time');
}

/**
 * Save the form data into the Wordpress variable
 */
function save_user_params($widget_type) {
    $vcita_widget = (array) get_option(VCITA_WIDGET_KEY);

	if ($_POST['form_type'] == 'engage_settings') {
		$vcita_widget['engage_text'] = stripslashes($_POST['vcita_engage-text']);
		$vcita_widget['engage_delay'] = $_POST['vcita_engage-delay'];
		
	} else if ($_POST['form_type'] == 'engage_enable_control') {
		$vcita_widget['engage_active'] = ($_POST['Submit'] == 'Activate') ? 'true' : 'false';
	
	} else {
		if (!is_null($_POST['vcita_title'])) {
			$vcita_widget['title'] = stripslashes($_POST['vcita_title']);
		}

		$vcita_widget['created'] = 1;
		$vcita_widget['email'] = $_POST['vcita_email'];
		$vcita_widget['first_name'] = stripslashes($_POST['vcita_first-name']);
		$vcita_widget['last_name'] = stripslashes($_POST['vcita_last-name']);
		$vcita_widget['prof_title'] = stripslashes($_POST['vcita_prof-til']);

		if ($_POST['main_settings'] == 'Y') {
			$vcita_widget['dedicated_page'] = $_POST['vcita_dedicated-page'];
		}
		
		if (!empty($widget_type)) {
			$vcita_widget['widget_type'] = $widget_type;
		}
		
		if (!empty($_POST['user_change'])) {
			$vcita_widget['engage_text'] = null;
			$vcita_widget['engage_delay'] = null;
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
    extract(vcita_get_contents("http://www.vcita.com/experts/widgets/otf?email=" .
                        urlencode($widget_params['email']).
						"&engage_text=" .urlencode($widget_params['engage_text']).
						"&engage_delay=" .urlencode($widget_params['engage_delay']).
                        "&first_name=" .urlencode($widget_params['first_name']).
                        "&last_name=" . urlencode($widget_params['last_name']).
                        "&professional_title=".urlencode($widget_params['prof_title']).
                        "&api=true&enforce=true&ref=".VCITA_WIDGET_API_KEY.""));
						
	return vcita_parse_expert_data($success, $widget_params, $raw_data);
}

/**
 * Take the received data and parse it
 * 
 * Returns the newly updated widgets parameters.
 */
function vcita_parse_expert_data($success, $widget_params, $raw_data) {

	$first_generate = empty($widget_params['first_generate']);
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
            $widget_params['prof_title'] = $data->{'title'};
            $widget_params['confirmed'] = $data->{'confirmed'};
            $widget_params['last_error'] = "";
			$widget_params['uid'] = $data->{'id'};

			if ($previous_id != $data->{'id'}) {
				$widget_params['confirmation_token'] = $data->{'confirmation_token'};
			}
			
			// Because of an uninstall bug in version perior to 1.4, 
			// engage is enabled by default only for new users.
			if ($first_generate) {
				$widget_params['engage_active'] = empty($data->{'confirmation_token'}) ? 'false' : 'true';
				$widget_params['first_generate'] = 'true';
			}
			

        } else {
            $widget_params['last_error'] = $data-> {'error'};
        }
    }

    return $widget_params;
}

/**
 *  Perform an HTTP GET Call to retrieve the data for the required content.
 * @param $url
 * @return array - raw_data and a success flag
 */
function vcita_get_contents($url) {
    $get_result = wp_remote_get($url, array('timeout' => 10));

    $success = false;
    $raw_data = "Unknown error";

    if (is_wp_error($get_result)) {
		$raw_data = $get_result->get_error_message();
		
    } elseif (!empty($get_result['response'])) {
		if ($get_result['response']['code'] != 200) {
	        $raw_data = $get_result['response']['message'];
	    } else {
	        $success = true;
	        $raw_data = $get_result['body'];
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
						   'dedicated_page' => 'on', 
						   'uid' => '',
						   'prof_title' => "Consultant", 
						   'new_install' => $old_params['new_install'],
						   'version' => VCITA_WIDGET_VERSION,
						   'contact_page_active' => VCITA_WIDGET_CONTACT_FORM_WIDGET,
						   'first_generate' => $old_params['first_generate'],
						   'engage_active' => $old_params['new_install'] // Only active if this is new install
						   );
	update_option(VCITA_WIDGET_KEY, $vcita_widget);
	
    return $vcita_widget;
}

/**
 * Create the The iframe HTML Tag according to the given paramters
 */
function vcita_create_embed_code($type, $uid, $email, $first_name, $last_name, $width, $height, $prof_title, $preview, $theme = 'blue') {
    $preview_text = "";

    if ($preview) {
        $preview_text = "&preview=wp"; // Preview as WP - just a generic preview for wp, the ref param will be used to distinguish it
    }

    // If No ID is present - use the OTF Interface to create or get the associated error, Otherwise use the normal API.
    if (empty($uid) || (!empty($email) && !empty($first_name))) {
	    $title = (empty($prof_title) ? "" : "&professional_title=".urlencode($prof_title));

        $code = "<iframe frameborder='0' src='http://www.vcita.com/experts/widgets/otf/?email=".urlencode($email).
                "&first_name=".urlencode($first_name)."&last_name=".urlencode($last_name)."&enforce=true&widget=" . $type.
                $title."&ref=".VCITA_WIDGET_API_KEY."".$preview_text."&theme=".$theme."' width='".$width."' height='".$height."'></iframe>";

    } else {
        $code = "<iframe frameborder='0' src='http://www.vcita.com/" . urlencode($uid) . "/" . $type . "/?ref=".VCITA_WIDGET_API_KEY."&theme=".$theme.
        $preview_text."' width='".$width."' height='".$height."'></iframe>";
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
    $page_id = $vcita_widget['page_id'];
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
function trash_page($page_id) {
	$page = get_page($page_id);

	if (!empty($page) && $page->{"post_status"} == "publish") {
		wp_trash_post($page_id);
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
            $message = "<b>Changes saved</b>";
        } else {
            $message = "";
        }

        $message_type = "updated below-h2";

        if (!$vcita_widget['confirmed']) {
            if ($update_made) {
                $message .= "<br/><br/>";
            }

            $message .= "<div style='overflow:hidden'><div>New account created for <b>".$vcita_widget['email']."</b>.</div><br><div style='float:left;'>Please </div>";
			
			if (!empty($vcita_widget['confirmation_token'])) {
				$message .= "<div style='float: left;margin-left: 3px;'><b><a style='text-decoration:underline;' href='http://www.vcita.com/users/confirmation?confirmation_token=".$vcita_widget['confirmation_token']."&o=int.4' target='_blank'>Confirm your account</a></b> or </div>";
			}
			
			$message .= "<div style='float: left;margin-left: 3px;'>follow instructions in the email to complete vCita configuration.</div></div>";
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

function vcita_embed_control_engage_edit_visibility() {
	?>
	<script type='text/javascript'>
	
		function vcita_control_engage_edit_visibility(rand, show) {
			details = document.getElementById('vcita_active_engage_details_' + rand);
			configure_button = document.getElementById('vcita_engage_configure_' + rand);
			control_button = document.getElementById('vcita_engage_configure_control_' + rand);
			cancel_button = document.getElementById('vcita_engage_cancel_button_' + rand);
			delay_input = document.getElementById('vcita_engage-delay_' + rand);
			text_input = document.getElementById('vcita_engage-text_' + rand);
			
			cancel_button.style.display = (show) ? "" : "none";
			details.style.display = (show) ? "block" : "none";
			configure_button.style.display = (show) ? "none" : "block";
			control_button.style.display = (show) ? "none" : "block";
			
			if (show) {
				delay_input.setAttribute('original-value', delay_input.value);
				text_input.setAttribute('original-value', text_input.value);
			} else {
				delay_input.value = delay_input.getAttribute('original-value');
				text_input.value = text_input.getAttribute('original-value');
			}
			
		}
	</script>
	<?php
}


/**
 * Embed the function for toggling the preview visibility
 */
function vcita_embed_toggle_preview_visibility() {
	?>
	<script type='text/javascript'>
	    function vcita_toggle_preview(type, rand, switchEmbed) {
		    var widgetVisibility = (type == 'widget') ? 'block' : 'none';
		    var contactVisibility = (type == 'contact') ? 'block' : 'none';

		    if (switchEmbed && document.getElementById('vcita_embed_contact_' + rand)  != null) {
			    document.getElementById('vcita_embed_contact_' + rand).style.display = contactVisibility;
			    document.getElementById('vcita_embed_widget_' + rand).style.display = widgetVisibility;
		    }

		    document.getElementById('vcita_preview_contact_' + rand).style.display = contactVisibility;
		    document.getElementById('vcita_preview_widget_' + rand).style.display = widgetVisibility;
	    }
	</script>
	<?php
}

/**
 * Embed the function for validation the engage form before submission.
 * Fields should be filled and the delay field should contain integers over 0
 */
function vcita_embed_validateEngageForm() {
	?>
	<script type='text/javascript'>
		function vcita_validateEngageForm(rand) {
			engage_text = document.getElementById('vcita_engage-text_' + rand);
			engage_delay = document.getElementById('vcita_engage-delay_' + rand);
			valid = false;
			
			// Validating the data is correct
			// 1. Engage Text is available and larger than minimum size of 2
			// 2. Delay available and is a number
			if (engage_text.value == null || engage_text.value.replace(/^\s+|\s+$/g,"").length < 2) {
				alert("Please specify the text you would like to use");
			} else if (engage_delay.value == null || 
					   engage_delay.value.replace(/^\s+|\s+$/g,"") == "" || 
					   isNaN(parseInt(engage_delay.value.replace(/^\s+|\s+$/g,"")))) {
				alert("Please make sure to set a valid number of seconds (10 to 20 is recommended)");
			} else {
				valid = true;
			}
			
			return valid;
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