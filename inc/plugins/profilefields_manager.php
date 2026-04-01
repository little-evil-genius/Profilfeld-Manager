<?php
/**
 * Profilfeld-Manager - by little.evil.genius
 * https://github.com/little-evil-genius/Profilfeld-Manager
 * https://storming-gates.de/member.php?action=profile&uid=1712
*/

// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// HOOKS
$plugins->add_hook("admin_rpgstuff_action_handler", "profilefields_manager_admin_rpgstuff_action_handler");
$plugins->add_hook("admin_rpgstuff_permissions", "profilefields_manager_admin_rpgstuff_permissions");
$plugins->add_hook("admin_rpgstuff_menu", "profilefields_manager_admin_rpgstuff_menu");
$plugins->add_hook("admin_load", "profilefields_manager_admin_manage");
$plugins->add_hook("admin_config_profile_fields_add", "profilefields_manager_admin_config_profile_fields_start");
$plugins->add_hook("admin_config_profile_fields_edit", "profilefields_manager_admin_config_profile_fields_start");
$plugins->add_hook("admin_formcontainer_end", "profilefields_manager_admin_formcontainer");
$plugins->add_hook("admin_config_profile_fields_add_commit", "profilefields_manager_admin_config_profile_fields_save");
$plugins->add_hook("admin_config_profile_fields_edit_commit", "profilefields_manager_admin_config_profile_fields_save");
$plugins->add_hook("admin_rpgstuff_update_stylesheet", "profilefields_manager_admin_update_stylesheet");
$plugins->add_hook("admin_rpgstuff_update_plugin", "profilefields_manager_admin_update_plugin");
$plugins->add_hook('usercp_menu', 'profilefields_manager_usercp_menu', 30);
$plugins->add_hook('usercp_start', 'profilefields_manager_usercp_do_pages', 5);
$plugins->add_hook('usercp_start', 'profilefields_manager_usercp_pages', 10);
$plugins->add_hook("global_intermediate", "profilefields_manager_banner");
$plugins->add_hook("modcp_nav", "profilefields_manager_modcp_nav");
$plugins->add_hook("modcp_start", "profilefields_manager_modcp");
$plugins->add_hook('fetch_wol_activity_end', 'profilefields_manager_online_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'profilefields_manager_online_location');
$plugins->add_hook("member_profile_start", "profilefields_manager_memberprofile_start", 0);
$plugins->add_hook("member_profile_end", "profilefields_manager_memberprofile_end", 0);
$plugins->add_hook("postbit", "profilefields_manager_postbit", 0); // normaler Postbit
$plugins->add_hook("postbit_prev", "profilefields_manager_postbit"); // Vorschau
$plugins->add_hook("postbit_pm", "profilefields_manager_postbit"); // Private Nachricht
$plugins->add_hook("postbit_announcement", "profilefields_manager_postbit"); // Ankündigungen
$plugins->add_hook("memberlist_user", "profilefields_manager_memberlist", 0);
$plugins->add_hook("global_start", "profilefields_manager_global");
$plugins->add_hook('global_start', 'profilefields_manager_register_myalerts_formatter_back_compat'); // Backwards-compatible alert formatter registration hook-ins.
$plugins->add_hook('xmlhttp', 'profilefields_manager_register_myalerts_formatter_back_compat', -2/* Prioritised one higher (more negative) than the MyAlerts hook into xmlhttp */);
$plugins->add_hook('myalerts_register_client_alert_formatters', 'profilefields_manager_register_myalerts_formatter'); // Backwards-compatible alert formatter registration hook-ins.

// Die Informationen, die im Pluginmanager angezeigt werden
function profilefields_manager_info()
{
	return array(
		"name"		=> "Profilfeld-Manager",
		"description"	=> "Pluginbeschreibung",
		"website"	=> "https://github.com/little-evil-genius/Profilfeld-Manager",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function profilefields_manager_install() {
    
    global $db, $lang;

    // SPRACHDATEI
    $lang->load("profilefields_manager");

    // RPG Stuff Modul muss vorhanden sein
    if (!file_exists(MYBB_ADMIN_DIR."/modules/rpgstuff/module_meta.php")) {
		flash_message($lang->profilefields_manager_error_rpgstuff, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

    // DATENBANKTABELL & FELDER
    profilefields_manager_database();

    // TEMPLATES ERSTELLEN
	// Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "profilefieldsmanager",
        "title" => $db->escape_string("Profilfeld-Manager"),
    );
    $db->insert_query("templategroups", $templategroup);
    // Templates 
    profilefields_manager_templates();
    
    // STYLESHEET HINZUFÜGEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    $css = profilefields_manager_stylesheet();
    $sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "profilefields_manager.css"), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function profilefields_manager_is_installed() {

    global $db;
    
    if ($db->table_exists("usercp_pages")) {
        return true;
    }
    return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function profilefields_manager_uninstall() {

    global $db, $cache;

    // DATENBANKTABELLE LÖSCHEN
    if($db->table_exists("usercp_pages")) {
        $db->drop_table("usercp_pages");
    }
    if($db->table_exists("profilefields_edit")) {
        $db->drop_table("profilefields_edit");
    }

    // DATENBANKFELDER LÖSCHEN
    if ($db->field_exists("usercp_page", "profilefields")) {
		$db->drop_column("profilefields", "usercp_page");
	}
	if ($db->field_exists("dependenceFID", "profilefields")) {
		$db->drop_column("profilefields", "dependenceFID");
	}
	if ($db->field_exists("dependencecontent", "profilefields")) {
		$db->drop_column("profilefields", "dependencecontent");
	}
	if ($db->field_exists("defaultcontent", "profilefields")) {
		$db->drop_column("profilefields", "defaultcontent");
	}
	if ($db->field_exists("guestpermissions", "profilefields")) {
		$db->drop_column("profilefields", "guestpermissions");
	}
	if ($db->field_exists("guestcontent", "profilefields")) {
		$db->drop_column("profilefields", "guestcontent");
	}
	if ($db->field_exists("editcheck", "profilefields")) {
		$db->drop_column("profilefields", "editcheck");
	}
	$cache->update_forums();

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'profilefieldsmanager'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'profilefieldsmanager%'");

    // STYLESHEET ENTFERNEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'profilefields_manager.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function profilefields_manager_activate() {

    global $db, $lang, $cache;

    // SPRACHDATEI
    $lang->load("profilefields_manager");

    if(!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->profilefields_manager_error_pluginlibrary, "error");
        admin_redirect("index.php?module=config-plugins");
    }

    // PLUGINLIBRARY
    profilefields_manager_pluginlibrary();

    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('profilefields_manager_alert'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }

    // VARIABLEN EINFÜGEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('usercp_profile_profilefields_textarea', '#'.preg_quote('</textarea>').'#', '</textarea>{$codebuttons}');
	find_replace_templatesets('usercp_profile', '#'.preg_quote('{$footer}').'#', '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/profilefields_manager.js"></script>{$footer}');
	find_replace_templatesets('usercp_profile_profilefields_checkbox', '#'.preg_quote('name="profile_fields[$field][]"').'#', 'name="profile_fields[$field][]" id="{$field}_{$key}"');
	find_replace_templatesets('usercp_profile_profilefields_multiselect', '#'.preg_quote('name="profile_fields[$field][]"').'#', 'name="profile_fields[$field][]" id="{$field}"');
	find_replace_templatesets('usercp_profile_profilefields_radio', '#'.preg_quote('name="profile_fields[$field]"').'#', 'name="profile_fields[$field]" id="{$field}_{$key}"');
	find_replace_templatesets('usercp_profile_profilefields_select', '#'.preg_quote('name="profile_fields[$field]"').'#', 'name="profile_fields[$field]" id="{$field}"');
    find_replace_templatesets("usercp_profile_customfield", "#".preg_quote('<tr>')."\s*".preg_quote('<td>')."\s*".preg_quote("<span>{\$profilefield['name']}</span>:")."#i",'<tr class="profilefields_manager_field_row profilefields_manager_field_row_label" id="profilefields_manager_field_label_{$profilefield[\'fid\']}" data-fid="{$profilefield[\'fid\']}" data-dependencefid="{$profilefield[\'dependenceFID\']}" data-dependencecontent="{$profilefield[\'dependencecontent\']}"><td><span>{$profilefield[\'name\']}</span>:');
    find_replace_templatesets("usercp_profile_customfield", "#".preg_quote('<tr>')."\s*".preg_quote('<td>{$code}</td>')."#i", '<tr class="profilefields_manager_field_row profilefields_manager_field_row_input" id="profilefields_manager_field_input_{$profilefield[\'fid\']}" data-fid="{$profilefield[\'fid\']}" data-dependencefid="{$profilefield[\'dependenceFID\']}" data-dependencecontent="{$profilefield[\'dependencecontent\']}"> <td>{$code}</td>');
	find_replace_templatesets('header', '#'.preg_quote('{$pm_notice}').'#', '{$profilefields_manager_banner}{$pm_notice}');
	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch}{$nav_profilefields_manager}');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function profilefields_manager_deactivate() {

    global $db, $cache;

    require_once PLUGINLIBRARY;
    $PL or $PL = new PluginLibrary();
    $PL->edit_core('profilefields_manager', 'usercp.php', [], true);

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('profilefields_manager_alert');
	}
    
    // VARIABLEN ENTFERNEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("usercp_profile_profilefields_textarea", "#".preg_quote('{$codebuttons}')."#i", '', 0);
    find_replace_templatesets("usercp_profile", "#".preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/profilefields_manager.js"></script>')."#i", '', 0);
    find_replace_templatesets("usercp_profile_profilefields_checkbox", "#".preg_quote(' id="{$field}_{$key}"')."#i", '', 0);
    find_replace_templatesets("usercp_profile_profilefields_multiselect", "#".preg_quote(' id="{$field}"')."#i", '', 0);
    find_replace_templatesets("usercp_profile_profilefields_radio", "#".preg_quote(' id="{$field}_{$key}"')."#i", '', 0);
    find_replace_templatesets("usercp_profile_profilefields_select", "#".preg_quote(' id="{$field}"')."#i", '', 0);
    find_replace_templatesets("usercp_profile_customfield", "#".preg_quote('<tr class="profilefields_manager_field_row profilefields_manager_field_row_label" id="profilefields_manager_field_label_{$profilefield[\'fid\']}" data-fid="{$profilefield[\'fid\']}" data-dependencefid="{$profilefield[\'dependenceFID\']}" data-dependencecontent="{$profilefield[\'dependencecontent\']}">')."#i", '<tr>', 0);
    find_replace_templatesets("usercp_profile_customfield", "#".preg_quote('<tr class="profilefields_manager_field_row profilefields_manager_field_row_input" id="profilefields_manager_field_input_{$profilefield[\'fid\']}" data-fid="{$profilefield[\'fid\']}" data-dependencefid="{$profilefield[\'dependenceFID\']}" data-dependencecontent="{$profilefield[\'dependencecontent\']}">')."#i", '<tr>', 0);
    find_replace_templatesets("header", "#".preg_quote('{$profilefields_manager_banner}')."#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_profilefields_manager}')."#i", '', 0);
}

######################
### HOOK FUNCTIONS ###
######################

// ADMIN BEREICH - KONFIGURATION //

// action handler fürs acp konfigurieren
function profilefields_manager_admin_rpgstuff_action_handler(&$actions) {
	$actions['profilefields_manager'] = array('active' => 'profilefields_manager', 'file' => 'profilefields_manager');
}

// Benutzergruppen-Berechtigungen im ACP
function profilefields_manager_admin_rpgstuff_permissions(&$admin_permissions) {

	global $lang;
	
    $lang->load('profilefields_manager');

	$admin_permissions['profilefields_manager'] = $lang->profilefields_manager_permission;

	return $admin_permissions;
}

// im Menü einfügen
function profilefields_manager_admin_rpgstuff_menu(&$sub_menu) {

    global $lang;

    $lang->load('profilefields_manager');

    $sub_menu[] = [
        'id'    => 'profilefields_manager',
        'title' => $lang->profilefields_manager_nav,
        'link'  => 'index.php?module=rpgstuff-profilefields_manager'
    ];
}

// die Seiten Verwaltung
function profilefields_manager_admin_manage() {

    global $mybb, $db, $lang, $page, $run_module, $action_file;

    if ($page->active_action != 'profilefields_manager') {
		return false;
	}

	$lang->load('profilefields_manager');

	if ($run_module == 'rpgstuff' && $action_file == 'profilefields_manager') {

		// Add to page navigation
		$page->add_breadcrumb_item($lang->profilefields_manager_breadcrumb_main, "index.php?module=rpgstuff-profilefields_manager");

        // ÜBERSICHT
		if ($mybb->get_input('action') == "" || !$mybb->get_input('action')) {

            $page->output_header($lang->profilefields_manager_overview_header);

			// Menü
			$sub_tabs['overview'] = [
				"title" => $lang->profilefields_manager_tabs_overview,
				"link" => "index.php?module=rpgstuff-profilefields_manager",
				"description" => $lang->profilefields_manager_tabs_overview_desc
			];
            $sub_tabs['add'] = [
				"title" => $lang->profilefields_manager_tabs_add,
				"link" => "index.php?module=rpgstuff-profilefields_manager&amp;action=add"
			];
            $page->output_nav_tabs($sub_tabs, 'overview');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

            // Übersichtsseite
            $form_container = new FormContainer($lang->profilefields_manager_overview_container);
            $form_container->output_row_header($lang->profilefields_manager_overview_container_page, array('style' => 'text-align: left;'));
            $form_container->output_row_header($lang->profilefields_manager_overview_container_link, array('style' => 'text-align: center; width: 20%;'));
            $form_container->output_row_header($lang->profilefields_manager_overview_container_fields, array('style' => 'text-align: center; width: 25%;'));
            $form_container->output_row_header($lang->profilefields_manager_options_container, array('style' => 'text-align: center; width: 10%;'));
            
            $query_pages = $db->query("SELECT * FROM ".TABLE_PREFIX."usercp_pages
            ORDER BY title ASC
            ");

            while ($pages = $db->fetch_array($query_pages)) {

                // Leer laufen lassen
                $pid = "";
                $identification = "";
                $title = "";
                $linktitle = "";

                // Mit Infos füllen
                $pid = $pages['pid'];
                $identification = $pages['identification'];
                $title = $pages['title'];
                $linktitle = $pages['linktitle'];

                $form_container->output_cell('<strong><a href="index.php?module=rpgstuff-profilefields_manager&amp;action=edit&amp;pid='.$pid.'">'.$title.'</a></strong><br><small><strong>'.$lang->profilefields_manager_overview_linktitle.'</strong> '.$linktitle);   
                $form_container->output_cell('usercp.php?action='.$identification);

                $fields_list = profilefields_manager_get_fields_pages($pid);
                if (!empty($fields_list)) {
                    $form_container->output_cell(implode('<br>', $fields_list));
                } else {
                    $form_container->output_cell($lang->profilefields_manager_overview_noFields);
                }

                // OPTIONEN
				$popup = new PopupMenu("profilefields_manager_".$pid, $lang->profilefields_manager_options_popup);	
                $popup->add_item(
                    $lang->profilefields_manager_options_popup_edit,
                    "index.php?module=rpgstuff-profilefields_manager&amp;action=edit&amp;pid=".$pid
                );
                $popup->add_item(
                    $lang->profilefields_manager_options_popup_delete,
                    "index.php?module=rpgstuff-profilefields_manager&amp;action=delete&amp;pid=".$pid."&amp;my_post_key={$mybb->post_code}", 
					"return AdminCP.deleteConfirmation(this, '".$lang->profilefields_manager_delete_notice."')"
                );
                $form_container->output_cell($popup->fetch(), array("class" => "align_center"));
                $form_container->construct_row();
            }

            if($db->num_rows($query_pages) == 0){
                $form_container->output_cell($lang->profilefields_manager_overview_noPages, array("colspan" => 4, 'style' => 'text-align: center;'));
                $form_container->construct_row();
			}

            $form_container->end();
            $page->output_footer();
			exit;
        }

        // HINZUFÜGEN
        if ($mybb->get_input('action') == "add") {
            
            if ($mybb->request_method == "post") {

                $errors = profilefields_manager_validate_pages();

                // No errors - insert
                if (empty($errors)) {

                    $insert_page = array(
                        "identification" => $db->escape_string($mybb->get_input('identification')),
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "linktitle" => $db->escape_string($mybb->get_input('linktitle'))
                    );
                    $db->insert_query("usercp_pages", $insert_page);

                    flash_message($lang->profilefields_manager_add_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-profilefields_manager");
                }
            }

            $page->add_breadcrumb_item($lang->profilefields_manager_breadcrumb_add);
			$page->output_header($lang->profilefields_manager_breadcrumb_main." - ".$lang->profilefields_manager_add_header);

			// Menü
			$sub_tabs['overview'] = [
				"title" => $lang->profilefields_manager_tabs_overview,
				"link" => "index.php?module=rpgstuff-profilefields_manager"
			];
            $sub_tabs['add'] = [
				"title" => $lang->profilefields_manager_tabs_add,
				"link" => "index.php?module=rpgstuff-profilefields_manager&amp;action=add",
				"description" => $lang->profilefields_manager_tabs_add_desc
			];
            $page->output_nav_tabs($sub_tabs, 'add');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

            // Build the form
            $form = new Form("index.php?module=rpgstuff-profilefields_manager&amp;action=add", "post", "", 1);
            $form_container = new FormContainer($lang->profilefields_manager_add_container);
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

            $form_container->output_row(
                $lang->profilefields_manager_form_title,
                $lang->profilefields_manager_form_title_desc,
                $form->generate_text_box('title', $mybb->get_input('title'))
            );

            $form_container->output_row(
                $lang->profilefields_manager_form_identification,
                $lang->profilefields_manager_form_identification_desc,
                $form->generate_text_box('identification', $mybb->get_input('identification'))
            );

            $form_container->output_row(
                $lang->profilefields_manager_form_linktitle,
                $lang->profilefields_manager_form_linktitle_desc,
                $form->generate_text_box('linktitle', $mybb->get_input('linktitle'))
            );

            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->profilefields_manager_add_button);
            $form->output_submit_wrapper($buttons);
            $form->end();
            $page->output_footer();
            exit;
        }

        // BEARBEITEN
        if ($mybb->get_input('action') == "edit") {

            // Get the data
            $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
            $page_query = $db->simple_select("usercp_pages", "*", "pid = ".$pid);
            $pages = $db->fetch_array($page_query);
            
            if ($mybb->request_method == "post") {
                    
                $pid = $mybb->get_input('pid', MyBB::INPUT_INT);

                $errors = profilefields_manager_validate_pages($pid);

                // No errors - insert
                if (empty($errors)) {

                    $update_page = array(
                        "identification" => $db->escape_string($mybb->get_input('identification')),
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "linktitle" => $db->escape_string($mybb->get_input('linktitle'))
                    );
                    $db->update_query("usercp_pages", $update_page, "pid = ".$pid);

                    flash_message($lang->profilefields_manager_edit_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-profilefields_manager");
                }
            }

            $page->add_breadcrumb_item($lang->profilefields_manager_breadcrumb_edit);
			$page->output_header($lang->profilefields_manager_breadcrumb_main." - ".$lang->profilefields_manager_edit_header);

			// Menü
			$sub_tabs['overview'] = [
				"title" => $lang->profilefields_manager_tabs_overview,
				"link" => "index.php?module=rpgstuff-profilefields_manager"
			];
            $sub_tabs['edit'] = [
				"title" => $lang->profilefields_manager_tabs_edit,
				"link" => "index.php?module=rpgstuff-profilefields_manager&amp;action=edit",
				"description" => $lang->profilefields_manager_tabs_edit_desc
			];
            $page->output_nav_tabs($sub_tabs, 'edit');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
				$identification = $mybb->get_input('identification');
				$title = $mybb->get_input('title');
				$linktitle = $mybb->get_input('linktitle');
			} else {
				$identification = $pages['identification'];
				$title = $pages['title'];
				$linktitle = $pages['linktitle'];
            }

            // Build the form
            $form = new Form("index.php?module=rpgstuff-profilefields_manager&amp;action=edit", "post", "", 1);
            $form_container = new FormContainer($lang->sprintf($lang->profilefields_manager_edit_container, $pages['title']));
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
            echo $form->generate_hidden_field("pid", $pid);

            $form_container->output_row(
                $lang->profilefields_manager_form_title,
                $lang->profilefields_manager_form_title_desc,
                $form->generate_text_box('title', $title)
            );

            $form_container->output_row(
                $lang->profilefields_manager_form_identification,
                $lang->profilefields_manager_form_identification_desc,
                $form->generate_text_box('identification', $identification)
            );

            $form_container->output_row(
                $lang->profilefields_manager_form_linktitle,
                $lang->profilefields_manager_form_linktitle_desc,
                $form->generate_text_box('linktitle', $linktitle)
            );

            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->profilefields_manager_edit_button);
            $form->output_submit_wrapper($buttons);
            $form->end();
            $page->output_footer();
            exit;
        }

        // LÖSCHEN
        if ($mybb->get_input('action') == "delete") {
            
            // Get the data
            $pid = $mybb->get_input('pid', MyBB::INPUT_INT);

			// Error Handling
			if (empty($pid)) {
				flash_message($lang->profilefields_manager_error_invalid, 'error');
				admin_redirect("index.php?module=rpgstuff-profilefields_manager");
			}

			// Cancel button pressed?
			if (isset($mybb->input['no']) && $mybb->input['no']) {
				admin_redirect("index.php?module=rpgstuff-profilefields_manager");
			}

			if ($mybb->request_method == "post") {

                $update_page = array(
                    "usercp_page" => (int)0
                );
                $db->update_query("profilefields", $update_page, "usercp_page = ".$pid);
                
                $db->delete_query('usercp_pages', "pid = ".$pid);

				flash_message($lang->profilefields_manager_delete_flash, 'success');
				admin_redirect("index.php?module=rpgstuff-profilefields_manager");
			} else {
				$page->output_confirm_action(
					"index.php?module=rpgstuff-profilefields_manager&amp;action=delete&amp;pid=".$pid,
					$lang->profilefields_manager_delete_notice
				);
			}
			exit;
        }
    }
}

// Profilfelder Erweiterung
// nur im Profilfeld-Formular anzeigen && Errors
function profilefields_manager_admin_config_profile_fields_start() {

    global $mybb, $errors;

    // Korrekte Anzeige
    if ($mybb->get_input('module') == 'config-profile_fields' && in_array($mybb->get_input('action'), array('add', 'edit'), true)) {
        $GLOBALS['profilefields_manager_profilefield_form'] = true;
    }

    // Errors
    if ($mybb->request_method == 'post') {
        $errors = profilefields_manager_validate_fields();
    }
}

// Formular Erweiterung
function profilefields_manager_admin_formcontainer() {

    global $form_container, $form, $lang, $mybb, $db, $profile_field;

    if (empty($GLOBALS['profilefields_manager_profilefield_form'])) {
        return;
    }

    $GLOBALS['profilefields_manager_profilefield_form'] = false;

    $lang->load("profilefields_manager");

    // Bearbeiten - DB Wert
    if (!empty($profile_field['fid'])) {
        $query = $db->simple_select("profilefields", "fid, usercp_page, dependenceFID, dependencecontent, defaultcontent, guestpermissions, guestcontent, editcheck", "fid = ".$profile_field['fid']);
        $current_values = $db->fetch_array($query);
    } 
    // Hinzufügen - Default
    else {
        $current_values = array(
            'usercp_page' => 0,
            'dependenceFID' => 0,
            'dependencecontent' => '',
            'defaultcontent' => '',
            'guestpermissions' => 0,
            'guestcontent' => '',
            'guestcontent' => '',
            'editcheck' => '',
            'fid' => ''
        );
    }

    // DB or Input
    $selected_page = profilefields_manager_get_value('usercp_page', $current_values['usercp_page'], 'int');
    $selected_field = profilefields_manager_get_value('dependenceFID', $current_values['dependenceFID'], 'int');
    $defaultcontent = profilefields_manager_get_value('defaultcontent', $current_values['defaultcontent']);
    $guestpermissions = profilefields_manager_get_value('guestpermissions', $current_values['guestpermissions'], 'int');
    $guestcontent = profilefields_manager_get_value('guestcontent', $current_values['guestcontent']);

    $selected_dependencecontent = array();
    if(isset($mybb->input['dependencecontent']) && is_array($mybb->input['dependencecontent'])) {
        $selected_dependencecontent = array_map('trim', $mybb->input['dependencecontent']);
    } elseif(!empty($current_values['dependencecontent'])) {
        $selected_dependencecontent = array_map('trim', explode(';', $current_values['dependencecontent']));
    }

    $pages_options = profilefields_manager_get_usercp_pages_options();
    $options_data = profilefields_manager_get_profilfields_options($current_values['fid']);
    $fields_options = $options_data['fields_options'];
    $dependence_options_map = $options_data['dependence_options_map'];
    $dependence_options_json = json_encode($dependence_options_map);
    $selected_dependencecontent_json = json_encode($selected_dependencecontent);


    // UserCP Seite
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_page,
        $lang->profilefields_manager_profilefield_form_page_desc,
        $form->generate_select_box("usercp_page", $pages_options, $selected_page)
    );

    // Abhängigkeit - Feld
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_dependencefield,
        $lang->profilefields_manager_profilefield_form_dependencefield_desc,
        $form->generate_select_box("dependenceFID", $fields_options, $selected_field, array('id' => 'dependenceFID'))
    );

    // Abhängigkeit - Inhalt
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_dependencecontent,
        $lang->profilefields_manager_profilefield_form_dependencecontent_desc,
        $form->generate_select_box("dependencecontent[]", array(), $selected_dependencecontent, array('id' => 'dependencecontent', 'multiple' => 'multiple', 'size' => 5)),
        '',
        array(),
        array('id' => 'row_dependencecontent')
    );

    // Default Inhalt
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_defaultcontent,
        $lang->profilefields_manager_profilefield_form_defaultcontent_desc,
        $form->generate_text_area("defaultcontent", $defaultcontent)
    );

    // Gäste ja/nein
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_guestpermissions,
        $lang->profilefields_manager_profilefield_form_guestpermissions_desc,
        $form->generate_yes_no_radio("guestpermissions", $guestpermissions)
    );

    // Gäste Inhalt
    $form_container->output_row(
        $lang->profilefields_manager_profilefield_form_guestcontent,
        $lang->profilefields_manager_profilefield_form_guestcontent_desc,
        $form->generate_text_area("guestcontent", $guestcontent),
        '',
        array(),
        array('id' => 'row_guestcontent')
    );

    // Gruppen - Überprüfung Edit
    $selected_values = array();
    if(isset($mybb->input['editcheck'])) {
        if($mybb->input['editcheck'] != '' && $mybb->input['editcheck'] != -1) {
            $selected_values = explode(',', $mybb->get_input('editcheck'));
            foreach($selected_values as &$value) {
                $value = (int)$value;
    
            }
            unset($value);
        }
    } elseif(!empty($current_values['editcheck']) && $current_values['editcheck'] != -1) { 
        $selected_values = explode(',', $current_values['editcheck']);
        foreach($selected_values as &$value) {
            $value = (int)$value;
        }
        unset($value);
    }
    
    $group_checked = array( 
        'all' => '',
        'custom' => '',
        'none' => ''
    );
    
    $editcheck_value = '';
    if(isset($mybb->input['editcheck'])) {
        $editcheck_value = $mybb->input['editcheck'];
    } else {
        $editcheck_value = $current_values['editcheck'];
    }
    if($editcheck_value == -1) {
        $group_checked['all'] = 'checked="checked"';
    } elseif($editcheck_value != '') {
        $group_checked['custom'] = 'checked="checked"';
    } else {
        $group_checked['none'] = 'checked="checked"';
    }

	print_selection_javascript();

	$select_code = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"editcheck\" value=\"all\" {$group_checked['all']} class=\"editcheck_forums_groups_check\" onclick=\"checkAction('editcheck');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_groups}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"editcheck\" value=\"custom\" {$group_checked['custom']} class=\"editcheck_forums_groups_check\" onclick=\"checkAction('editcheck');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_groups}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"editcheck_forums_groups_custom\" class=\"editcheck_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->groups_colon}</small></td>
					<td>".$form->generate_group_select('select[editcheck][]', $selected_values, array('id' => 'editcheck', 'multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"editcheck\" value=\"none\" {$group_checked['none']} class=\"editcheck_forums_groups_check\" onclick=\"checkAction('editcheck');\" style=\"vertical-align: middle;\" /> <strong>{$lang->none}</strong></label></dt>
	</dl>
	<script type=\"text/javascript\">
		checkAction('editcheck');
	</script>";
	$form_container->output_row(
        $lang->profilefields_manager_profilefield_form_editcheck, 
        $lang->profilefields_manager_profilefield_form_editcheck_desc, 
        $select_code, 
        '', 
        array(), 
        array('id' => 'row_editcheck')
    );

    echo '<script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
    <script type="text/javascript">
        $(function() {
            var dependenceOptions = '.$dependence_options_json.';
            var selectedDependenceContent = '.$selected_dependencecontent_json.';
            new Peeker($("#dependenceFID"), $("#row_dependencecontent"), /[1-9][0-9]*/, false);
   
            function rebuildDependenceContentOptions() {
                var selectedFid = $("#dependenceFID").val();
                var $select = $("#dependencecontent");
                $select.empty();
                if (!selectedFid || !dependenceOptions[selectedFid]) {
                    return;
                }

                $.each(dependenceOptions[selectedFid], function(index, value) {
                    var option = new Option(value, value, false, $.inArray(value, selectedDependenceContent) != -1);    
                    $select.append(option);
                });
            }

            function toggleGuestContent() {
                if ($("input[name=\'guestpermissions\']:checked").val() == "0") {
                    $("#row_guestcontent").show();
                } else { 
                    $("#row_guestcontent").hide();
                }
            }

            $("#dependenceFID").on("change", function() {
                selectedDependenceContent = [];
                rebuildDependenceContentOptions();
            });
            rebuildDependenceContentOptions();

            toggleGuestContent();
            $("input[name=\'guestpermissions\']").on("change", toggleGuestContent);
        });
    </script>';
}

// Informationen speichern
function profilefields_manager_admin_config_profile_fields_save() {

    global $db, $mybb, $fid, $profile_field;
    
    if (!empty($profile_field['fid'])) {
        $fid = (int)$profile_field['fid'];
    }

    $dependencecontent_input = $mybb->get_input('dependencecontent', MyBB::INPUT_ARRAY);
    if(is_array($dependencecontent_input)) {
        $dependencecontent_input = array_map('trim', $dependencecontent_input);
        $dependencecontent_input = array_filter($dependencecontent_input, 'strlen');
        $dependencecontent = implode(';', $dependencecontent_input);
    } else {
        $dependencecontent = '';
    }

    if($mybb->get_input('editcheck') == 'all') {
		$editcheck = -1;
	} elseif ($mybb->get_input('editcheck') == 'custom') {
		if(isset($mybb->input['select']['editcheck']) && is_array($mybb->input['select']['editcheck'])) {
			foreach($mybb->input['select']['editcheck'] as &$val) {
				$val = (int)$val;
			}
			unset($val);

			$editcheck = implode(',', $mybb->input['select']['editcheck']);
		} else {
			$editcheck = '';
		}
	} else {
		$editcheck = '';
	}

    $fields_save = array(
        "usercp_page" => (int)$mybb->get_input('usercp_page'),
        "dependenceFID" => (int)$mybb->get_input('dependenceFID'),
        "dependencecontent" => $db->escape_string($dependencecontent),
        "defaultcontent" => $db->escape_string($mybb->get_input('defaultcontent')),
        "guestpermissions" => (int)$mybb->get_input('guestpermissions'),
        "guestcontent" => $db->escape_string($mybb->get_input('guestcontent')),
        "editcheck" => $editcheck
    );
    
    $db->update_query("profilefields", $fields_save, "fid = ".$fid);
}

// Stylesheet zum Master Style hinzufügen
function profilefields_manager_admin_update_stylesheet(&$table) {

    global $db, $mybb, $lang;
	
    $lang->load('rpgstuff_stylesheet_updates');

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // HINZUFÜGEN
    if ($mybb->input['action'] == 'add_master' AND $mybb->get_input('plugin') == "profilefields_manager") {

        $css = profilefields_manager_stylesheet();
        
        $sid = $db->insert_query("themestylesheets", $css);
        $db->update_query("themestylesheets", array("cachefile" => "profilefields_manager.css"), "sid = '".$sid."'", 1);
    
        $tids = $db->simple_select("themes", "tid");
        while($theme = $db->fetch_array($tids)) {
            update_theme_stylesheet_list($theme['tid']);
        } 

        flash_message($lang->stylesheets_flash, "success");
        admin_redirect("index.php?module=rpgstuff-stylesheet_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("Profilfeld-Manager")."</b>", array('width' => '70%'));

    // Ob im Master Style vorhanden
    $master_check = $db->fetch_field($db->query("SELECT tid FROM ".TABLE_PREFIX."themestylesheets 
    WHERE name = 'profilefields_manager.css' 
    AND tid = 1
    "), "tid");
    
    if (!empty($master_check)) {
        $masterstyle = true;
    } else {
        $masterstyle = false;
    }

    if (!empty($masterstyle)) {
        $table->construct_cell($lang->stylesheets_masterstyle, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-stylesheet_updates&action=add_master&plugin=profilefields_manager\">".$lang->stylesheets_add."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// Plugin Update
function profilefields_manager_admin_update_plugin(&$table) {

    global $db, $mybb, $lang, $theme;
	
    $lang->load('rpgstuff_plugin_updates');

    // UPDATE
    if ($mybb->input['action'] == 'add_update' AND $mybb->get_input('plugin') == "profilefields_manager") {

        // Templates 
        profilefields_manager_templates('update');

        // Stylesheet
        $update_data = profilefields_manager_stylesheet_update();
        $update_stylesheet = $update_data['stylesheet'];
        $update_string = $update_data['update_string'];
        if (!empty($update_string)) {

            // Ob im Master Style die Überprüfung vorhanden ist
            $masterstylesheet = $db->fetch_field($db->query("SELECT stylesheet FROM ".TABLE_PREFIX."themestylesheets WHERE tid = 1 AND name = 'profilefields_manager.css'"), "stylesheet");
            $masterstylesheet = (string)($masterstylesheet ?? '');
            $update_string = (string)($update_string ?? '');
            $pos = strpos($masterstylesheet, $update_string);
            if ($pos === false) { // nicht vorhanden 
            
                $theme_query = $db->simple_select('themes', 'tid, name');
                while ($theme = $db->fetch_array($theme_query)) {
        
                    $stylesheet_query = $db->simple_select("themestylesheets", "*", "name='".$db->escape_string('profilefields_manager.css')."' AND tid = ".$theme['tid']);
                    $stylesheet = $db->fetch_array($stylesheet_query);
        
                    if ($stylesheet) {

                        require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
        
                        $sid = $stylesheet['sid'];
            
                        $updated_stylesheet = array(
                            "cachefile" => $db->escape_string($stylesheet['name']),
                            "stylesheet" => $db->escape_string($stylesheet['stylesheet']."\n\n".$update_stylesheet),
                            "lastmodified" => TIME_NOW
                        );
            
                        $db->update_query("themestylesheets", $updated_stylesheet, "sid='".$sid."'");
            
                        if(!cache_stylesheet($theme['tid'], $stylesheet['name'], $updated_stylesheet['stylesheet'])) {
                            $db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet=".$sid), "sid='".$sid."'", 1);
                        }
            
                        update_theme_stylesheet_list($theme['tid']);
                    }
                }
            } 
        }

        // Datenbanktabellen & Felder
        profilefields_manager_database();

        flash_message($lang->plugins_flash, "success");
        admin_redirect("index.php?module=rpgstuff-plugin_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("Profilfeld-Manager")."</b>", array('width' => '70%'));

    // Überprüfen, ob Update erledigt
    $update_check = profilefields_manager_is_updated();

    if (!empty($update_check)) {
        $table->construct_cell($lang->plugins_actual, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-plugin_updates&action=add_update&plugin=profilefields_manager\">".$lang->plugins_update."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// FORUM //

// Menü
function profilefields_manager_usercp_menu() {

    global $mybb, $db, $templates, $theme, $lang, $collapse, $collapsed, $collapsedimg, $usercp_pages, $usercpmenu;

    $lang->load("profilefields_manager");

    if (!isset($collapsedimg['profilefields_manager'])) {
        $collapsedimg['profilefields_manager'] = '';
    }
    if (!isset($collapsed['profilefields_manager_e'])) {
        $collapsed['profilefields_manager_e'] = '';
    }
    $expaltext = in_array('profilefields_manager', $collapse) ? $lang->expcol_expand : $lang->expcol_collapse;

    $query_pages = $db->query("SELECT identification, linktitle FROM ".TABLE_PREFIX."usercp_pages
    ORDER BY title ASC
    ");
        
    $usercp_pages = "";
    while($pages = $db->fetch_array($query_pages)) {

        // Leer laufen lassen
        $identification = "";
        $linktitle = "";

        // Mit Infos füllen
        $identification = $pages['identification'];
        $linktitle = $pages['linktitle'];

        eval("\$usercp_pages .= \"".$templates->get("profilefieldsmanager_usercp_menu_bit")."\";");
    }

    eval("\$usercpmenu .= \"".$templates->get("profilefieldsmanager_usercp_menu")."\";");
}

// Speichern
function profilefields_manager_usercp_do_pages() {

    global $db, $mybb, $lang;
    
    // return if the action key isn't part of the input
    $allowed_actions = profilefields_manager_get_usercp_pages('do');
    if (!in_array($mybb->get_input('action', MyBB::INPUT_STRING), $allowed_actions)) return;

    $lang->load("usercp");
    $lang->load("profilefields_manager");

    foreach ($allowed_actions as $pageID) {

        if ($mybb->get_input('action') == $pageID && $mybb->request_method == "post") {

            // Verify incoming POST request
            verify_post_check($mybb->get_input('my_post_key'));

            $identification = str_replace('do_', '', $pageID);

            $user = array();

            $pid = $db->fetch_field($db->simple_select("usercp_pages", "pid", "identification = '".$identification."'"), "pid");

            $posted_profile_fields = $mybb->get_input('profile_fields', MyBB::INPUT_ARRAY);
            $merged_profile_fields = profilefields_manager_merge_profile_fields((int)$mybb->user['uid'], (int)$pid, $posted_profile_fields);
            $merged_profile_fields = profilefields_manager_prepare_profilefield_updates((int)$mybb->user['uid'], (int)$pid,$merged_profile_fields);

            // Set up user handler.
            require_once MYBB_ROOT."inc/datahandlers/user.php";
            $userhandler = new UserDataHandler("update");
            
            $user = array_merge($user, array(
                "uid" => $mybb->user['uid'],
                "profile_fields" => $merged_profile_fields
            ));
            
            $userhandler->set_data($user);
            
            if(!$userhandler->validate_user()) {
                $GLOBALS['profilefields_manager_usercp_errors'] = $userhandler->get_friendly_errors();
                $mybb->input['action'] = $identification;
            } else {
                $userhandler->update_user();
                redirect("usercp.php?action=".$identification, $lang->redirect_profileupdated);
            }
        }
    }
}

// Seiten
function profilefields_manager_usercp_pages() {

    global $db, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $page, $usercpnav;
    
    // return if the action key isn't part of the input
    $allowed_actions = profilefields_manager_get_usercp_pages();
    if (!in_array($mybb->get_input('action', MyBB::INPUT_STRING), $allowed_actions)) return;

    $lang->load("profilefields_manager");

    add_breadcrumb($lang->nav_usercp, "usercp.php");

    foreach ($allowed_actions as $pageID) {

        $template_name = "";
        if ($mybb->get_input('action') == $pageID) {

            $page_query = $db->simple_select("usercp_pages", "*", "identification = '".$pageID."'");
            $pages = $db->fetch_array($page_query);
			$pid = $pages['pid'];
            
            add_breadcrumb($pages['title']);

            $errors = '';
            if(!empty($GLOBALS['profilefields_manager_usercp_errors'])) {
                $errors = inline_error($GLOBALS['profilefields_manager_usercp_errors']);
            }

            $data = profilefields_manager_build_page_profilefields($pid);
            $requiredfields = $data['requiredfields'];
            $customfields = $data['customfields'];
            $fields = $data['fields_map'];

            $lang->profilefields_manager_usercp_page = $lang->sprintf($lang->profilefields_manager_usercp_page, $pages['title']);

            $profilefields_manager_hidden_fields = profilefields_manager_build_page_profilefields_hidden($pid);

            $templatename = profilefields_manager_template_usercppages($pageID);
            eval("\$page = \"".$templates->get($templatename)."\";");
            output_page($page);
            unset($GLOBALS['profilefields_manager_usercp_errors']);
            die();
        }
    }
}

// Banner 
function profilefields_manager_banner() {

	global $db, $mybb, $lang, $templates, $profilefields_manager_banner, $bannertext;

    if ($mybb->usergroup['canmodcp'] != 1){
        $profilefields_manager_banner = "";
        return;
    }
    
    $lang->load("profilefields_manager");

    $count_fieldsedit = $db->num_rows($db->query("SELECT eid FROM ".TABLE_PREFIX."profilefields_edit")); 

    if ($count_fieldsedit > 0) {
        if ($count_fieldsedit == 1) {   
            $bannertext = $lang->profilefields_manager_banner_single;
        } elseif ($count_fieldsedit > 1) {
            $bannertext = $lang->sprintf($lang->profilefields_manager_banner_plural, $count_fieldsedit);
        }
        eval("\$profilefields_manager_banner = \"".$templates->get("profilefieldsmanager_banner")."\";");
    } else {
        $profilefields_manager_banner = "";
    }
}

// ModCP - Navigation
function profilefields_manager_modcp_nav() {

    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_profilefields_manager;

	// SPRACHDATEI
	$lang->load('profilefields_manager');

	eval("\$nav_profilefields_manager = \"".$templates->get ("profilefields_manager_modcp_nav")."\";");
}

// ModCP - Seiten
function profilefields_manager_modcp() {
   
    global $mybb, $templates, $lang, $theme, $header, $headerinclude, $footer, $db, $page, $modcp_nav, $parser_options, $modcp_bit, $refuse_popup;

    // return if the action key isn't part of the input
    $allowed_actions = [
        'profilefields_edit',
        'profilefields_edit_update',
        'profilefields_edit_refuse',
        'profilefields_edit_accepted'
    ];
    if (!in_array($mybb->get_input('action', MyBB::INPUT_STRING), $allowed_actions)) return;

	// SPRACHDATEI
	$lang->load('profilefields_manager');

    // Übersicht
    if($mybb->get_input('action') == 'profilefields_edit') {

        // Add a breadcrumb
        add_breadcrumb($lang->nav_modcp, "modcp.php");
        add_breadcrumb($lang->profilefields_manager_modcp, "modcp.php?action=profilefields_edit");

        $query_edits =  $db->query("SELECT * FROM ".TABLE_PREFIX."profilefields_edit
        ORDER BY uid ASC");
        
        $editBits = "";
        while($edit = $db->fetch_array($query_edits)) {

            // Leer laufen lassen
            $eid = "";
            $uid = "";
            $fid = "";
            $username = "";
            $character = "";
            $profilefield = "";

            // Mit Infos füllen
            $eid = $edit['eid'];
            $uid = $edit['uid'];
            $fid = $edit['fid'];
            $username = get_user($uid)['username'];
            $character = build_profile_link($username, $uid);
            $profilefield = $db->fetch_field($db->simple_select("profilefields", "name", "fid = ".$fid), "name");

            eval("\$editBits .= \"".$templates->get("profilefieldsmanager_modcp_bit")."\";");
        }

        if(empty($editBits)) {
            eval("\$editBits = \"".$templates->get("profilefieldsmanager_modcp_noresults")."\";");
        }
 
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("profilefieldsmanager_modcp")."\";");
        output_page($page);
        die();
    }

    // Vergleich
    if($mybb->get_input('action') == 'profilefields_edit_update') {

        // Add a breadcrumb
        add_breadcrumb($lang->nav_modcp, "modcp.php");
        add_breadcrumb($lang->profilefields_manager_modcp, "modcp.php?action=profilefields_edit");

        // Get the data
        $eid = $mybb->get_input('eid', MyBB::INPUT_INT);
        $edit_query = $db->simple_select("profilefields_edit", "*", "eid = ".$eid);
        $edit = $db->fetch_array($edit_query);
        
        if(empty($edit['eid'])) {
            error_no_permission();
        }
        
        $uid = $edit['uid'];
        $fid = $edit['fid'];
        
        $user = get_user($uid);
        $charactername = htmlspecialchars_uni($user['username']);
        $profilefieldname = $db->fetch_field($db->simple_select("profilefields", "name", "fid = ".$fid), "name");

        $diff_data = profilefields_manager_build_diff_html($edit['oldvalue'], $edit['newvalue']);
        $oldvalue = $diff_data['old'];
        $newvalue = $diff_data['new'];

        eval("\$refuse_popup = \"".$templates->get("profilefieldsmanager_modcp_popup")."\";");

        $lang->profilefields_manager_modcp_edit_name = $lang->sprintf($lang->profilefields_manager_modcp_edit_name, $charactername);
        $lang->profilefields_manager_modcp_edit_field = $lang->sprintf($lang->profilefields_manager_modcp_edit_field, $charactername, $profilefieldname);
 
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("profilefieldsmanager_modcp_edit")."\";");
        output_page($page);
        die();
    }

    // Annehmen - Alert
    if($mybb->get_input('action') == 'profilefields_edit_accepted') {

        // Get the data
        $eid = $mybb->get_input('eid', MyBB::INPUT_INT);
        $edit_query = $db->simple_select("profilefields_edit", "*", "eid = ".$eid);
        $edit = $db->fetch_array($edit_query);
        
        if(empty($edit['eid'])) {
            error_no_permission();
        }
        
        $uid = $edit['uid'];
        $fid = $edit['fid'];

        $profilefieldname = $db->fetch_field($db->simple_select("profilefields", "name", "fid = ".$fid), "name");

        $update_field = array(
            "fid".$fid => $db->escape_string($edit['newvalue'])
        );
        $db->update_query("userfields", $update_field, "ufid = ".$uid);

        $db->delete_query("profilefields_edit", "eid = ".$eid);

        // MyAlert Stuff
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
			$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('profilefields_manager_alert');
			if ($alertType != NULL && $alertType->getEnabled()) {
				$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$uid, $alertType, (int)$mybb->user['uid']);
				$alert->setExtraDetails([
					'username' => $mybb->user['username'],
                    'from' => $mybb->user['uid'],
                    'user' => $uid,
					'profilfeld' => $profilefieldname,
				]);
				MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
			}
		}

        redirect("modcp.php?action=profilefields_edit", "Die Profilfeld-Änderungen wurden erfolgreich akzeptieren und das entsprechende Profilfeld wurde geupdatet.");
    }

    // Ablehnen - PN mit Grund
    if($mybb->get_input('action') == 'profilefields_edit_refuse') {

        // Get the data
        $eid = $mybb->get_input('eid', MyBB::INPUT_INT);
        $edit_query = $db->simple_select("profilefields_edit", "*", "eid = ".$eid);
        $edit = $db->fetch_array($edit_query);
        
        if(empty($edit['eid'])) {
            error_no_permission();
        }
        
        $uid = $edit['uid'];
        $fid = $edit['fid'];
        
        $profilefieldname = $db->fetch_field($db->simple_select("profilefields", "name", "fid = ".$fid), "name");
        
        // DAMIT DIE PN SACHE FUNKTIONIERT
        require_once MYBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        require_once MYBB_ROOT."inc/class_parser.php";
        $parser = new postParser;
        $parser_array = array(
            "allow_html" => 1,
            "allow_mycode" => 1,
            "allow_smilies" => 1,
            "allow_imgcode" => 0,
            "filter_badwords" => 0,
            "nl2br" => 1,
            "allow_videocode" => 0
        );

        $reason = $mybb->get_input('reason');
        $reason = $parser->parse_message($reason, $parser_array);

        $ownip = $db->fetch_field($db->query("SELECT ip FROM ".TABLE_PREFIX."sessions WHERE uid = ".$mybb->user['uid']), "ip");

        $pm_message = $lang->sprintf($lang->profilefields_manager_pm_message, $profilefieldname, $reason);
        $pm_change = array(
            "subject" => $lang->sprintf($lang->profilefields_manager_pm_subject, $profilefieldname),
            "message" => $parser->parse_message($pm_message, $parser_array),
            "fromid" => $mybb->user['uid'], // von wem kommt diese
            "toid" => $uid, // an wen geht diese
            "icon" => 0,
            "do" => "",
            "pmid" => "",
            "ipaddress" => $ownip   
        );
        
        $pm_change['options'] = array(
            'signature' => '1',
            'disablesmilies' => '0',
            'savecopy' => '0',            
            'readreceipt' => '0',
        );

        // $pmhandler->admin_override = true;
        $pmhandler->set_data($pm_change);
        if (!$pmhandler->validate_pm())
            return false;
        else {
            $pmhandler->insert_pm();
        }

        $db->delete_query("profilefields_edit", "eid = ".$eid);
        redirect("modcp.php?action=profilefields_edit", "Die Profilfeld-Änderungen wurden erfolgreich abgelehnt und eine entsprechende Private Nachricht verschickt worden.");
    }
}

// Online Location
function profilefields_manager_online_activity($user_activity) {

	global $parameters, $user;

	$split_loc = explode(".php", $user_activity['location']);
	if(isset($user['location']) && $split_loc[0] == $user['location']) { 
		$filename = '';
	} else {
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

    $allActions = profilefields_manager_get_usercp_pages();

	switch ($filename) {
		case 'usercp':
            foreach ($allActions as $page) {
                if ($parameters['action'] == $page) {
                    $user_activity['activity'] = 'ucp_'.$page;	
                }
            }
        break;
	}

	return $user_activity;
}
function profilefields_manager_online_location($plugin_array) {

	global $db, $lang;
    
    // SPRACHDATEI LADEN
    $lang->load("profilefields_manager");

    $allActions = profilefields_manager_get_usercp_pages();
    foreach ($allActions as $page) {

        $title = $db->fetch_field($db->simple_select("usercp_pages", "title", "identification = '".$page."'"), "title");
        
        if ($plugin_array['user_activity']['activity'] == 'ucp_'.$page) {
            $plugin_array['location_name'] = $lang->sprintf($lang->profilefields_manager_online_location, $title);
        }
    }

	return $plugin_array;
}

// VARIABELN //

// Profile $memprofile
function profilefields_manager_memberprofile_start() {

    global $fields, $memprofile;
    
    $uid = $memprofile['uid'];

    $fields = profilefields_manager_build_view($uid);    
    $memprofile = array_merge($memprofile, $fields);
}

// Profile $userfields
function profilefields_manager_memberprofile_end() {

    global $fields, $memprofile, $userfields;
    
    $uid = $memprofile['uid'];

    $fields = profilefields_manager_build_view($uid);  
    $userfields = array_merge($userfields, $fields);
}

// Postbit
function profilefields_manager_postbit(&$post) {

    global $fields; 
    
    $uid = $post['uid']; 
    
    $fields = profilefields_manager_build_view($uid); 
    $post = array_merge($post, $fields);
}

// Mitgliederliste
function profilefields_manager_memberlist(&$user) {

    global $fields;

    $uid = $user['uid'];

    $fields = profilefields_manager_build_view($uid);    
    $user = array_merge($user, $fields);
}

// Global
function profilefields_manager_global() {

    global $mybb, $db;

    if(!isset($mybb->user['uid'])) {
        return;
    }

    $uid = $mybb->user['uid'];

    $userfields = $db->fetch_array($db->simple_select("userfields", "*", "ufid = ".$uid));

    if(!$userfields) {
        $userfields = array();
    }

    $fields = profilefields_manager_build_view($uid);

    foreach($fields as $key => $value) {
        $mybb->user[$key] = $value;
    }
}

### ALERTS ###
// Backwards-compatible alert formatter registration.
function profilefields_manager_register_myalerts_formatter_back_compat(){

	if (function_exists('myalerts_info')) {
		$myalerts_info = myalerts_info();
		if (version_compare($myalerts_info['version'], '2.0.4') <= 0) {
			profilefields_manager_register_myalerts_formatter();
		}
	}
}

// Alert formatter registration.
function profilefields_manager_register_myalerts_formatter(){

	global $mybb, $lang;
	$lang->load('profilefields_manager');

	if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
	    class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
	    !class_exists('profilefields_managerAlertFormatter')
	) {
		class profilefields_managerAlertFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
		{
			/**
			* Format an alert into it's output string to be used in both the main alerts listing page and the popup.
			*
			* @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
			*
			* @return string The formatted alert string.
			*/
			public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
			{
				$alertContent = $alert->getExtraDetails();
				return $this->lang->sprintf(
					$this->lang->profilefields_manager_alert,
					$outputAlert['from_user'],
					$alertContent['profilfeld'],
					$alertContent['user']
				);
		
			}

			/**
			* Init function called before running formatAlert(). Used to load language files and initialize other required
			* resources.
			*
			* @return void
			*/
			public function init()
			{
				if (!$this->lang->profilefields_manager_alert) {
					$this->lang->load('profilefields_manager');
				}
			}
		
			/**
			* Build a link to an alert's content so that the system can redirect to it.
			*
			* @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
			*
			* @return string The built alert, preferably an absolute link.
			*/
			public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
			{
				$alertContent = $alert->getExtraDetails();
				$postLink = $this->mybb->settings['bburl'] . '/member.php?action=profile&uid='.$alertContent['user'];
				return $postLink;
			}
		}

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager) {
		        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		if ($formatterManager) {
			$formatterManager->registerFormatter(new profilefields_managerAlertFormatter($mybb, $lang, 'profilefields_manager_alert'));
		}
	}
}

#########################
### PRIVATE FUNCTIONS ###
#########################

// ACP //
// alle Felder auf einer Seite
function profilefields_manager_get_fields_pages($pid = 0) {

    global $db;

    $query_fields = $db->query("SELECT name FROM ".TABLE_PREFIX."profilefields
    WHERE usercp_page = ".$pid."
    ORDER BY disporder ASC, name ASC
    ");
        
    $fields_list = [];
    while($field = $db->fetch_array($query_fields)) {
        $fields_list[] = $field['name'];
    }

    return $fields_list;
}

// Error - Seiten
function profilefields_manager_validate_pages($pid = ''){

    global $mybb, $lang, $db;

    $lang->load('profilefields_manager');

    $errors = [];

    // Title
    $title = $mybb->get_input('title');
    if (empty($title)) {
        $errors[] = $lang->profilefields_manager_profilefield_form_error_title;
    } else {
        if (!empty($pid)) {
            $titleCheck = $db->fetch_field($db->simple_select("usercp_pages", "title", "title = '".$db->escape_string($title)."' AND pid != ".$pid), "title");
        } else {
            $titleCheck = $db->fetch_field($db->simple_select("usercp_pages", "title", "title = '".$db->escape_string($title)."'"), "title");
        }

        if (!empty($titleCheck)) {
            $errors[] = $lang->profilefields_manager_profilefield_form_error_title_double;
        }
    }

    // Identifikation
    $identification = $mybb->get_input('identification');
    if (empty($identification)) {
        $errors[] = $lang->profilefields_manager_profilefield_form_error_identification;
    } else {
        if (!empty($pid)) {
            $identificationCheck = $db->fetch_field($db->simple_select("usercp_pages", "identification", "identification = '".$db->escape_string($identification)."' AND pid != ".$pid), "identification");
        } else {
            $identificationCheck = $db->fetch_field($db->simple_select("usercp_pages", "identification", "identification = '".$db->escape_string($identification)."'"), "identification");
        }

        if (!empty($identificationCheck)) {
            $errors[] = $lang->profilefields_manager_form_error_identification_double;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identification)) {
            $errors[] = $lang->profilefields_manager_form_error_identification_machine;
        }
    }

    // Navigation
    $linktitle = $mybb->get_input('linktitle');
    if (empty($linktitle)) {
        $errors[] = $lang->profilefields_manager_form_error_linktitle;
    } else {
        if (!empty($pid)) {
            $linktitleCheck = $db->fetch_field($db->simple_select("usercp_pages", "linktitle", "linktitle = '".$db->escape_string($linktitle)."' AND pid != ".$pid), "linktitle");
        } else {
            $linktitleCheck = $db->fetch_field($db->simple_select("usercp_pages", "linktitle", "linktitle = '".$db->escape_string($linktitle)."'"), "linktitle");
        }
        
        if (!empty($linktitleCheck)) {
            $errors[] = $lang->profilefields_manager_form_error_linktitle_double;
        }
    }

    return $errors;
}

// Error - Profilfelder
function profilefields_manager_validate_fields(){

    global $mybb, $lang, $db;

    $lang->load('profilefields_manager');

    $errors = [];

    $dependenceFID = $mybb->get_input('dependenceFID');

    if ($dependenceFID != 0) {
        // Pflich - Abhängigkeit
        if ($mybb->get_input('required') == 1) {
            $dependenceCheck = $db->fetch_field($db->simple_select("profilefields", "required", "fid = '".$dependenceFID."'"), "required");
            if ($dependenceCheck == 0) {
                $errors[] = "Du machst dieses Pflichtfeld abhängig von einem Pfeld, welches nicht verpflichtend ist. Ändere bei einem der beiden die Verplfichtung.";
            }
        }

        // Inhalt
        if (empty($mybb->get_input('dependencecontent', MyBB::INPUT_ARRAY))) {
            $errors[] = "Wähle noch mindestens ein Abhängigkeitswert aus.";
        }
    }

    return $errors;
}

// Formularwerte - Profilfelder
function profilefields_manager_get_value($name, $default, $type = 'string') {

    global $mybb;

    if (isset($mybb->input[$name])) {
        $value = $mybb->input[$name];
    } else {
        $value = $default;
    }

    if ($type == 'int') {
        return (int)$value;
    }

    return $value;
}

// alle UserCP-Seiten
function profilefields_manager_get_usercp_pages_options() {

    global $db, $lang;

    $lang->load("profilefields_manager");

    $pages_list = array(
        0 => $lang->profilefields_manager_profilefield_form_page_profile
    );

    $query_pages = $db->query("SELECT pid, title FROM ".TABLE_PREFIX."usercp_pages
    ORDER BY title ASC
    ");
        
    while($pages = $db->fetch_array($query_pages)) {
        $pages_list[$pages['pid']] = $pages['title'];
    }

    return $pages_list;
}

// alle Abhängikeits-Felder
function profilefields_manager_get_profilfields_options($fid = '') {

    global $db, $lang;

    $lang->load("profilefields_manager");

    $edit_and = "";
    if (!empty($fid)) {
        $edit_and = "AND fid != ".$fid;
    }

    $fields_options = array(
        0 => $lang->profilefields_manager_profilefield_form_dependencefield_none
    );

    $dependence_options_map = array();

    $query_profilefield = $db->query("SELECT * FROM ".TABLE_PREFIX."profilefields
    WHERE (type LIKE 'select%' OR type LIKE 'radio%' OR type LIKE 'multiselect%' OR type LIKE 'checkbox%')
    ".$edit_and."
    ORDER BY disporder ASC, name ASC
    ");
    
    while($field = $db->fetch_array($query_profilefield)) {

        $fid = (int)$field['fid'];
        $fields_options[$fid] = $field['name'];
        
        $type_parts = explode("\n", $field['type'], 2);
        $field_options = array();
        
        if(!empty($type_parts[1])) {
            $raw_options = explode("\n", $type_parts[1]);
            
            foreach($raw_options as $option) {
                $option = trim($option);
    
                if($option != '') {
                    $field_options[] = $option;    
                }
            }    
        }

        $dependence_options_map[$fid] = $field_options;
    }

    return array(
        'fields_options' => $fields_options,
        'dependence_options_map' => $dependence_options_map
    );
}

// Forum //
// alle UserCP-Seiten
function profilefields_manager_get_usercp_pages($mode = '') {

    global $db;

    $query_pages = $db->query("SELECT identification, title FROM ".TABLE_PREFIX."usercp_pages
    ORDER BY title ASC
    ");

    $pages_list = [];
    while($pages = $db->fetch_array($query_pages)) {
        if (!empty($mode)) {
            $identification = "do_".$pages['identification'];
        } else {
            $identification = $pages['identification'];
        }
        $pages_list[] = $identification;
    }

    return $pages_list;
}

// Verschiedene Templates für UserCP
function profilefields_manager_template_usercppages($pageID = '') {

    global $db, $theme;

    if (empty($pageID)) {
        $template_to_use = "profilefieldsmanager_usercp_page";
        return $template_to_use;
    }

    $template_name = "profilefieldsmanager_usercp_page_" . $pageID;
    
    // prüfen ob Template existiert
    $db_template = $db->fetch_field($db->query("SELECT tid FROM ".TABLE_PREFIX."templates
    WHERE title = '".$template_name."'
    AND sid IN ('-2','-1','".$theme['templateset']."')
    ORDER BY sid DESC
    LIMIT 1
    "),"tid");
    
    if(!empty($db_template)) {
        $template_to_use = $template_name;
    } else {
        $template_to_use = "profilefieldsmanager_usercp_page";
    }

    return $template_to_use;
}

// Profilfelder auslesen
function profilefields_manager_build_page_profilefields($pid) {

    global $db, $mybb, $templates, $cache, $lang, $errors, $theme;

    $user = $mybb->user;

    // Custom profile fields baby!
	$altbg = "trow1";
	$requiredfields = $customfields = '';
    $fields_map = [];
	$mybb->input['profile_fields'] = $mybb->get_input('profile_fields', MyBB::INPUT_ARRAY);

	$pfcache = $cache->read('profilefields');

	if(is_array($pfcache)) {

		foreach($pfcache as $profilefield) {

			if(!is_member($profilefield['editableby']) || ($profilefield['postnum'] && $profilefield['postnum'] > $mybb->user['postnum']) || (int)$profilefield['usercp_page'] != (int)$pid) {
				continue;
			}

            $dependency_style = '';
            if((int)$profilefield['dependenceFID'] > 0) {
                $dependency_style = ' style="display: none;"';
            }

			$userfield = $code = $select = $val = $options = $expoptions = $useropts = '';
			$seloptions = array();
			$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
			$profilefield['name'] = htmlspecialchars_uni($profilefield['name']);
			$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
			$thing = explode("\n", $profilefield['type'], "2");
			$type = $thing[0];
			if(isset($thing[1]))
			{
				$options = $thing[1];
			}
			else
			{
				$options = array();
			}
			$field = "fid{$profilefield['fid']}";
			
            if($errors){
				if(!isset($mybb->input['profile_fields'][$field]))
				{
					$mybb->input['profile_fields'][$field] = '';
				}
				$userfield = $mybb->input['profile_fields'][$field];
			}
			else
			{
				$userfield = $user[$field];
			}
			if($type == "multiselect")
			{
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$val = htmlspecialchars_uni($val);
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);

						$sel = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$sel = " selected=\"selected\"";
						}

						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 3;
					}

					eval("\$code = \"".$templates->get("usercp_profile_profilefields_multiselect")."\";");
				}
			}
			elseif($type == "select")
			{
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);
						$sel = "";
						if($val == htmlspecialchars_uni($userfield))
						{
							$sel = " selected=\"selected\"";
						}

						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 1;
					}

					eval("\$code = \"".$templates->get("usercp_profile_profilefields_select")."\";");
				}
			}
			elseif($type == "radio")
			{
				$userfield = htmlspecialchars_uni($userfield);
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if($val == $userfield)
						{
							$checked = " checked=\"checked\"";
						}

						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_radio")."\";");
					}
				}
			}
			elseif($type == "checkbox")
			{
				$userfield = htmlspecialchars_uni($userfield);
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$checked = " checked=\"checked\"";
						}

						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_checkbox")."\";");
					}
				}
			}
			elseif($type == "textarea")
			{
				$value = htmlspecialchars_uni($userfield);
                if ($profilefield['allowhtml'] == 1 || $profilefield['allowmycode'] == 1) {
					$codebuttons = build_mycode_inserter($field);
					if(function_exists('markitup_run_build')) {
						markitup_run_build($field);
					};
				} else {
					$codebuttons = "";
				}
				eval("\$code = \"".$templates->get("usercp_profile_profilefields_textarea")."\";");
			}
			else
			{
				$value = htmlspecialchars_uni($userfield);
				$maxlength = "";
				if($profilefield['maxlength'] > 0)
				{
					$maxlength = " maxlength=\"{$profilefield['maxlength']}\"";
				}

				eval("\$code = \"".$templates->get("usercp_profile_profilefields_text")."\";");
			}

			if($profilefield['required'] == 1)
			{
				eval("\$requiredfields .= \"".$templates->get("usercp_profile_customfield")."\";");
			}
			else
			{
				eval("\$customfields .= \"".$templates->get("usercp_profile_customfield")."\";");
			}
            eval("\$fields_map['{$field}'] = \"".$templates->get("usercp_profile_customfield")."\";");
			$altbg = alt_trow();
		}
	}
	if($customfields)
	{
		eval("\$customfields = \"".$templates->get("usercp_profile_profilefields")."\";");
	}

    return [
        "requiredfields" => $requiredfields,
        "customfields"   => $customfields,
        "fields_map"     => $fields_map
    ];
}

// Hidden Inputs
function profilefields_manager_build_page_profilefields_hidden($pid = 0) {
    
    global $db, $mybb;

    $uid = $mybb->user['uid'];

    if ($pid <= 0 || $uid <= 0) {
        return '';
    }

    // alle Felder der aktuellen Seite
    $current_page_fields = [];
    $current_page_fids = [];

    $fieldquery = $db->query("SELECT fid, dependenceFID FROM ".TABLE_PREFIX."profilefields
    WHERE usercp_page = ".$pid."
    ");

    while ($field = $db->fetch_array($fieldquery)) {

        $fid = $field['fid'];
        $depFid = $field['dependenceFID'];

        $current_page_fids[] = $fid;

        if ($depFid > 0) {
            $current_page_fields[] = $depFid;
        }
    }

    $needed_dependence_fids = array_unique($current_page_fields);

    if (empty($needed_dependence_fids)) {
        return '';
    }

    // Profilfeld-Werte
    $userquery = $db->query("SELECT * FROM ".TABLE_PREFIX."userfields
    WHERE ufid = ".$uid."
    ");
    $userfields = $db->fetch_array($userquery);

    if (!$userfields) {
        $userfields = [];
    }

    // Hidden Inputs
    $hidden_fields_html = '';
    foreach ($needed_dependence_fids as $fid) {

        if (in_array($fid, $current_page_fids)) {
            continue;
        }
        $value = '';
        if (!empty($userfields['fid'.$fid])) {
            $value = $userfields['fid'.$fid];
        }

        $hidden_fields_html .= '<input type="hidden" name = "profile_fields[fid'.$fid.']" value = "'.$value.'" id= "fid'.$fid.'">'."\n";
    }

    return $hidden_fields_html;
}

// Speichern - Daten zusammenführen
function profilefields_manager_merge_profile_fields($uid, $pid, $posted_fields) {

    global $db, $cache;

    if(!is_array($posted_fields)) {
        $posted_fields = array();
    }

    $merged_fields = array();
    $userfields = $db->fetch_array(
        $db->simple_select("userfields", "*", "ufid = ".(int)$uid)
    );

    if(!$userfields) {
        $userfields = array();
    }

    $editwait_fields = profilefields_manager_get_editwait_profilefield_edits($uid);

    $pfcache = $cache->read('profilefields');

    if(is_array($pfcache)) {
        foreach($pfcache as $profilefield) {

            $field = "fid".$profilefield['fid'];

            // aktuelle Seite - Formular
            if($profilefield['usercp_page'] == $pid) {
                if(isset($posted_fields[$field])) {
                    $merged_fields[$field] = $posted_fields[$field];
                } else {
                    $merged_fields[$field] = '';
                }
            }
            // andere Seiten - erst EditDB-Wert, dann DB-Wert, sonst leer
            else {
                if(isset($editwait_fields[$field])) {
                    $merged_fields[$field] = $editwait_fields[$field];
                }
                elseif(isset($userfields[$field])) {
                    $merged_fields[$field] = $userfields[$field];
                }
                else {
                    $merged_fields[$field] = '';
                }
            }
        }
    }

    return $merged_fields;
}

// ob Bearbeitung überprüft werden mus
function profilefields_manager_user_matches_editcheck($editcheck, $user) {

    if($editcheck === '' || $editcheck === null) {
        return false;
    }

    if($editcheck == '-1') {
        return true;
    }

    $groups = array();

    if(!empty($user['usergroup'])) {
        $groups[] = (int)$user['usergroup'];
    }

    if(!empty($user['additionalgroups'])) {
        $additional = array_map('intval', explode(',', $user['additionalgroups']));
        $groups = array_merge($groups, $additional);
    }

    $groups = array_unique(array_filter($groups));

    $allowed_groups = array_map('intval', explode(',', $editcheck));
    $allowed_groups = array_unique(array_filter($allowed_groups));

    foreach($groups as $gid) {
        if(in_array($gid, $allowed_groups, true)) {
            return true;
        }
    }

    return false;
}

// Feldänderungen überprüfen - fürs speichern vorbeireiten
function profilefields_manager_prepare_profilefield_updates($uid, $pid, $merged_profile_fields) {

    global $db, $cache, $mybb;

    $current_userfields = $db->fetch_array($db->simple_select("userfields", "*", "ufid = ".$uid));

    if(!$current_userfields) {
        $current_userfields = array();
    }

    $prepared_fields = $merged_profile_fields;

    $pfcache = $cache->read('profilefields');

    if(!is_array($pfcache)) {
        return $prepared_fields;
    }

    foreach($pfcache as $profilefield) {

        if((int)$profilefield['usercp_page'] !== $pid) {
            continue;
        }

        $field = "fid".$profilefield['fid'];

        $old_value = isset($current_userfields[$field]) ? $current_userfields[$field] : '';
        $new_value = isset($prepared_fields[$field]) ? $prepared_fields[$field] : '';

        // multiselect/checkbox in String
        if(is_array($new_value)) {
            $new_value = implode("\n", array_map('trim', $new_value));
        } else {
            $new_value = trim((string)$new_value);
        }

        $old_value = trim((string)$old_value);

        // keine Änderung
        if($new_value === $old_value) {
            continue;
        }

        // Überprüfung?
        if(!profilefields_manager_user_matches_editcheck($profilefield['editcheck'], $mybb->user)) {
            // darf normal gespeichert werden
            $prepared_fields[$field] = $new_value;
            continue;
        }

        // Änderung prüfen -> in Edit-Tabelle
        profilefields_manager_save_editwait_profilefield_edit($uid, $profilefield['fid'], $old_value, $new_value);

        // für speichern alten Wert beibehalten
        $prepared_fields[$field] = $old_value;
    }

    return $prepared_fields;
}

// in Edit-Tabelle
function profilefields_manager_save_editwait_profilefield_edit($uid, $fid, $old_value, $new_value) {

    global $db;

    // Falls es schon einen offenen Eintrag für User+Feld gibt -> aktualisieren
    $existing = $db->fetch_field($db->simple_select("profilefields_edit", "eid", "uid = ".$uid." AND fid = ".$fid, array("limit" => 1)),"eid");

    $data = array(
        "uid" => $uid,
        "fid" => $fid,
        "oldvalue" => $db->escape_string($old_value),
        "newvalue" => $db->escape_string($new_value)
    );

    if($existing) {
        $db->update_query("profilefields_edit", $data, "eid = ".$existing);
    } else {
        $db->insert_query("profilefields_edit", $data);
    }
}

// Wartende Überprüfungs-Daten
function profilefields_manager_get_editwait_profilefield_edits($uid) {

    global $db;

    $editwait = array();

    $query = $db->simple_select("profilefields_edit", "fid, newvalue", "uid = ".$uid);

    while($edit = $db->fetch_array($query)) {
        $fid = "fid".$edit['fid'];
        $editwait[$fid] = $edit['newvalue'];
    }

    return $editwait;
}

// Vergleich
function profilefields_manager_build_diff_html($old_text, $new_text) {

    $old_text = (string)$old_text;
    $new_text = (string)$new_text;

    // Beide leer
    if($old_text === '' && $new_text === '') {
        return array(
            'old' => '',
            'new' => ''
        );
    }

    // Identisch
    if($old_text === $new_text) {
        $same = nl2br(htmlspecialchars_uni($old_text));

        return array(
            'old' => '<span class="pfm-diff-same">'.$same.'</span>',
            'new' => '<span class="pfm-diff-same">'.$same.'</span>'
        );
    }

    // Einer leer
    if($old_text === '') {
        return array(
            'old' => '',
            'new' => '<span class="pfm-diff-added">'.nl2br(htmlspecialchars_uni($new_text)).'</span>'
        );
    }

    if($new_text === '') {
        return array(
            'old' => '<span class="pfm-diff-removed">'.nl2br(htmlspecialchars_uni($old_text)).'</span>',
            'new' => ''
        );
    }

    $old_words = preg_split('/(\s+)/u', $old_text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $new_words = preg_split('/(\s+)/u', $new_text, -1, PREG_SPLIT_DELIM_CAPTURE);

    $matrix = array();
    $maxlen = 0;
    $old_index = 0;
    $new_index = 0;

    foreach($old_words as $o => $old_word) {

        $nkeys = array_keys($new_words, $old_word, true);

        foreach($nkeys as $n) {
            $matrix[$o][$n] = isset($matrix[$o - 1][$n - 1]) ? $matrix[$o - 1][$n - 1] + 1 : 1;

            if($matrix[$o][$n] > $maxlen) {
                $maxlen = $matrix[$o][$n];
                $old_index = $o + 1 - $maxlen;
                $new_index = $n + 1 - $maxlen;
            }
        }
    }

    // Kein gemeinsamer Teil
    if($maxlen === 0) {
        return array(
            'old' => '<span class="pfm-diff-removed">'.nl2br(htmlspecialchars_uni($old_text)).'</span>',
            'new' => '<span class="pfm-diff-added">'.nl2br(htmlspecialchars_uni($new_text)).'</span>'
        );
    }

    $old_before = implode('', array_slice($old_words, 0, $old_index));
    $old_match  = implode('', array_slice($old_words, $old_index, $maxlen));
    $old_after  = implode('', array_slice($old_words, $old_index + $maxlen));

    $new_before = implode('', array_slice($new_words, 0, $new_index));
    $new_match  = implode('', array_slice($new_words, $new_index, $maxlen));
    $new_after  = implode('', array_slice($new_words, $new_index + $maxlen));

    // leeres Match nicht rekursiv weiterlaufen
    if($old_match === '' && $new_match === '') {
        return array(
            'old' => '<span class="pfm-diff-removed">'.nl2br(htmlspecialchars_uni($old_text)).'</span>',
            'new' => '<span class="pfm-diff-added">'.nl2br(htmlspecialchars_uni($new_text)).'</span>'
        );
    }

    $before = profilefields_manager_build_diff_html($old_before, $new_before);
    $after  = profilefields_manager_build_diff_html($old_after, $new_after);

    return array(
        'old' => $before['old'].'<span class="pfm-diff-same">'.nl2br(htmlspecialchars_uni($old_match)).'</span>'.$after['old'],
        'new' => $before['new'].'<span class="pfm-diff-same">'.nl2br(htmlspecialchars_uni($new_match)).'</span>'.$after['new']
    );
}

// Variable Inhalte
function profilefields_manager_build_view($uid) {

    global $db, $cache, $mybb, $parser, $parser_options;

    require_once MYBB_ROOT."inc/class_parser.php";
    $parser = new postParser;

    $fields = array();

    $userfields = $db->fetch_array($db->simple_select("userfields", "*", "ufid = ".$uid));

    if(!$userfields) {
        $userfields = array();
    }

    $pfcache = $cache->read('profilefields');

    if(is_array($pfcache)) {

        foreach($pfcache as $profilefield) {

            $parser_options = array(
                "allow_html" => $profilefield['allowhtml'],
                "allow_mycode" => $profilefield['allowmycode'],
                "allow_smilies" => $profilefield['allowsmilies'],
                "allow_imgcode" => $profilefield['allowimgcode'],
                "allow_videocode" => $profilefield['allowvideocode'],
                "filter_badwords" => 1,
                "nl2br" => 1
            );

            $field = "fid".$profilefield['fid'];

            // Abhängigkeit
            if(!profilefields_manager_dependency_matches_view($profilefield, $userfields)) {
                $fields[$field] = '';
                continue;
            }

            if(isset($userfields[$field])) {
                $user_value = trim($userfields[$field]);
                $user_value = $parser->parse_message($user_value, $parser_options);
            } else {
                $user_value = '';
            }

            $default_value = trim($profilefield['defaultcontent']);
            $default_value = $parser->parse_message($default_value, $parser_options);
            $guest_value = trim($profilefield['guestcontent']);
            $guest_value = $parser->parse_message($guest_value, $parser_options);
            $guestpermissions = (int)$profilefield['guestpermissions'];

            // Gäste
            if($mybb->user['uid'] == 0 && $guestpermissions == 0) {
                if($guest_value != '') {
                    $fields[$field] = $guest_value;
                }
                continue;
            }

            if($user_value != '') {
                $fields[$field] = $user_value;
            } elseif($default_value != '') {
                $fields[$field] = $default_value;
            }
        }
    }

    return $fields;
}

// Variable Inhalte - Abhängigkeit
function profilefields_manager_dependency_matches_view($profilefield, $userfields) {

    if(empty($profilefield['dependenceFID'])) {
        return true;
    }

    $dependence_fid = (int)$profilefield['dependenceFID'];
    $dependence_field = "fid".$dependence_fid;

    if(!isset($userfields[$dependence_field])) {
        return false;
    }

    $current_value = $userfields[$dependence_field];
    $allowed_values = array_map('trim', explode(';', $profilefield['dependencecontent']));
    $allowed_values = array_filter($allowed_values, 'strlen');

    if(empty($allowed_values)) {
        return false;
    }

    // Checkbox / Multiselect
    if(is_string($current_value) && str_contains($current_value, "\n")) {
        $current_values = array_map('trim', explode("\n", $current_value));
    } else {
        $current_values = array(trim((string)$current_value));
    }

    foreach($current_values as $value) {
        if(in_array($value, $allowed_values, true)) {
            return true;
        }
    }

    return false;
}

#########################################################
### DATABASE | TEMPLATES | PLUGINLIBRARY | STYLESHEET ###
#########################################################

// DATENBANKTABELLE & FELD
function profilefields_manager_database() {

    global $db;

    // UCP - SEITEN
    if (!$db->table_exists("usercp_pages")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."usercp_pages(
            `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `identification` VARCHAR(500) NOT NULL, 
            `title` varchar(500) NOT NULL, 
			`linktitle` varchar(500) NOT NULL,
            PRIMARY KEY(`pid`),
            KEY `pid` (`pid`)
            ) ENGINE=InnoDB ".$db->build_create_table_collation().";"
        );
    }

    // BEARBEItUNG
    if (!$db->table_exists("profilefields_edit")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."profilefields_edit(
            `eid` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `uid` int(11) unsigned NOT NULL,
            `fid` int(11) unsigned NOT NULL, 
            `oldvalue` text NOT NULL, 
            `newvalue` text NOT NULL, 
            PRIMARY KEY(`eid`),
            KEY `eid` (`eid`)
            ) ENGINE=InnoDB ".$db->build_create_table_collation().";"
        );
    }

    // PROFILFELDER
	// Seite
    if (!$db->field_exists("usercp_page", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `usercp_page` INT(11) unsigned NOT NULL DEFAULT '0';");
    }

    // Abhängigkeit - Feld
    if (!$db->field_exists("dependenceFID", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `dependenceFID` INT(11) unsigned NOT NULL DEFAULT '0';");
    }

    // Abhängikeit - Inhalt
    if (!$db->field_exists("dependencecontent", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `dependencecontent` TEXT NOT NULL DEFAULT '';");
    }

	// Default Inhalt
    if (!$db->field_exists("defaultcontent", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `defaultcontent` TEXT NOT NULL DEFAULT '';");
    }

	// Gäste ja/nein
    if (!$db->field_exists("guestpermissions", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `guestpermissions` INT(1) unsigned NOT NULL DEFAULT '1';");
    }

	// Gäste Inhalt
    if (!$db->field_exists("guestcontent", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `guestcontent` TEXT NOT NULL DEFAULT '';");
    }

	// Bearbeitung überprüfen
    if (!$db->field_exists("editcheck", "profilefields")) {
        $db->query("ALTER TABLE `".TABLE_PREFIX."profilefields` ADD `editcheck` TEXT NOT NULL DEFAULT '';");
    }
}

// TEMPLATES
function profilefields_manager_templates($mode = '') {

    global $db;

    $info = profilefields_manager_info();
    $version = $info['version'];

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_banner',
        'template'	=> $db->escape_string('<div class="red_alert"><a href="modcp.php?action=profilefields_edit">{$bannertext}</a></div>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_modcp',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->profilefields_manager_modcp}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top">
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="3"><strong>{$lang->profilefields_manager_modcp}</strong></td>
						</tr>
						<tr>
							<td class="tcat"><strong>{$lang->profilefields_manager_modcp_character}</strong></td>
							<td class="tcat"><strong>{$lang->profilefields_manager_modcp_profilfield}</strong></td>
							<td class="tcat"><strong>{$lang->profilefields_manager_modcp_option}</strong></td>
						</tr>
						{$editBits}
					</table>
				</td>
			</tr>
		</table>
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_modcp_bit',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" valign="top">{$character}</td>
        <td class="trow1" valign="top">{$profilefield}</td>
        <td class="trow1" valign="top"><a href="modcp.php?action=profilefields_edit_update&eid={$eid}">{$lang->profilefields_manager_modcp_bit}</a></td>
        </tr>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_modcp_edit',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->profilefields_manager_modcp_edit_name}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top">
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="2"><strong>{$lang->profilefields_manager_modcp_edit_field}</strong></td>
						</tr>
						<tr class="trow1">
							<td width="50%" valign="top">
								<div class="tcat"><strong>Neu Neu</strong></div>
								<div>{$oldvalue}</div>
							</td>
							<td width="50%" valign="top">
								<div class="tcat"><strong>{$lang->profilefields_manager_modcp_edit_newvalue}</strong></div>
								<div>{$newvalue}</div>
							</td>
						</tr>
						<tr>
							<td class="trow1" colspan="2" align="center">
								<a href="" class="button" onclick="$(\'#profilefieldsEdit_{$eid}\').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== \'undefined\' ? modal_zindex : 9999) }); return false;">{$lang->profilefields_manager_modcp_edit_refuse}</a>
								<a href="modcp.php?action=profilefields_edit_accepted&eid={$eid}" class="button">{$lang->profilefields_manager_modcp_edit_accepted}</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		{$refuse_popup}
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_modcp_nav',
        'template'	=> $db->escape_string('<tr><td class="trow1 smalltext"><a href="modcp.php?action=profilefields_edit" class="modcp_nav_item modcp_nav_modqueue">{$lang->profilefields_manager_modcp_nav}</td></tr>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_modcp_popup',
        'template'	=> $db->escape_string('<div id="profilefieldsEdit_{$eid}" class="modal" style="display: none;">
        <form action="modcp.php" method="post" name="input">
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead"><strong>{$lang->profilefields_manager_modcp_edit_popup}</strong></td>
			</tr>
			<tr class="trow1">
				<td>
					{$lang->profilefields_manager_modcp_edit_popup_desc}
					<textarea name="reason" style="width: 98%; height: 100px;" maxlength="5000"></textarea>
				</td>
			</tr>
			<tr class="trow1" align="center">
				<td>
					<input type="hidden" name="eid" value="{$eid}" />
					<input type="hidden" name="action" value="profilefields_edit_refuse" />
					<input type="submit" class="button" name="regsubmit" value="{$lang->profilefields_manager_modcp_edit_popup_button}" />
				</td>
			</tr>
		</table>
        </form>
        </div>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_usercp_menu',
        'template'	=> $db->escape_string('<tbody>
        <tr>
        <td class="tcat tcat_menu tcat_collapse{$collapsedimg[\'profilefields_manager\']}">
		<div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse{$collapsedimg[\'profilefields_manager\']}.png" id="profilefields_manager_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
		<div><span class="smalltext"><strong>{$lang->profilefields_manager_usercp_menu}</strong></span></div>
        </td>
        </tr>
        </tbody>
        <tbody style="{$collapsed[\'profilefields_manager_e\']}" id="profilefields_manager_e">
        {$usercp_pages}
        </tbody>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_usercp_menu_bit',
        'template'	=> $db->escape_string('<tr><td class="trow1 smalltext"><a href="usercp.php?action={$identification}" class="usercp_nav_item usercp_nav_profile">{$linktitle}</a></td></tr>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'profilefieldsmanager_usercp_page',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->profilefields_manager_usercp_page}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<form action="usercp.php" method="post" name="input">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			<table width="100%" border="0" align="center">
				<tr>
					{$usercpnav}
					<td valign="top">
						{$errors}
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<tr>
								<td class="thead" colspan="2"><strong>{$lang->profilefields_manager_usercp_page}</strong></td>
							</tr>
							<tr>
								<td width="100%" class="trow1" valign="top">
									<fieldset class="trow2">
										<legend><strong>{$lang->profile_required}</strong></legend>
										<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}">
											{$requiredfields}
										</table>
									</fieldset>
									{$customfields}
								</td>
							</tr>
						</table>
						<br />
						<div align="center">
                        {$profilefields_manager_hidden_fields}
							<input type="hidden" name="action" value="do_{$pageID}" />
							<input type="submit" class="button" name="regsubmit" value="{$lang->update_profile}" />
						</div>
					</td>
				</tr>
			</table>
		</form>
		<script type="text/javascript" src="{$mybb->asset_url}/jscripts/profilefields_manager.js"></script>
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> $version,
        'dateline'	=> TIME_NOW
    );

    if ($mode == "update") {
        foreach ($templates as $template) {
            $query = $db->simple_select("templates", "tid, template", "title = '".$template['title']."' AND sid = '-2'");
            $existing_template = $db->fetch_array($query);

            if($existing_template) {
                if ($existing_template['template'] !== $template['template']) {
                    $db->update_query("templates", array(
                        'template' => $template['template'],
                        'dateline' => TIME_NOW,
                        'version'	=> $version,
                    ), "tid = '".$existing_template['tid']."'");
                }
            }   
            else {
                $db->insert_query("templates", $template);
            }
        }
    } else {
        foreach ($templates as $template) {
            $check = $db->num_rows($db->simple_select("templates", "title", "title = '".$template['title']."' AND sid = '-2'"));
            if ($check == 0) {
                $db->insert_query("templates", $template);
            }
        }
    }
}

// PLUGINLIBRARY
function profilefields_manager_pluginlibrary() {

    global $PL;

    require_once PLUGINLIBRARY;
    $PL or $PL = new PluginLibrary();
    $PL->edit_core('profilefields_manager', 'usercp.php',
        array(
            array(
                'search' => 'if(!is_member($profilefield[\'editableby\']) || ($profilefield[\'postnum\'] && $profilefield[\'postnum\'] > $mybb->user[\'postnum\']))',
                'before' => '$dependency_style = \'\';
                if((int)$profilefield[\'dependenceFID\'] > 0) {
                $dependency_style = \' style="display: none;"\';
                }',
                'replace' => 'if(!is_member($profilefield[\'editableby\']) || ($profilefield[\'postnum\'] && $profilefield[\'postnum\'] > $mybb->user[\'postnum\']) || (int)$profilefield[\'usercp_page\'] != (int)0)'
                ),
            array(
                'search' => '"profile_fields" => $mybb->get_input(\'profile_fields\', MyBB::INPUT_ARRAY)',
                'replace' => '"profile_fields" => profilefields_manager_prepare_profilefield_updates((int)$mybb->user[\'uid\'], 0, profilefields_manager_merge_profile_fields((int)$mybb->user[\'uid\'], 0, $mybb->get_input(\'profile_fields\', MyBB::INPUT_ARRAY)))'
            ),
            array(
                'search' => 'eval("\$code = \"".$templates->get("usercp_profile_profilefields_textarea")."\";");',
                'before' => 'if ($profilefield[\'allowhtml\'] == 1 || $profilefield[\'allowmycode\'] == 1) {
                    $codebuttons = build_mycode_inserter($field);
					if(function_exists(\'markitup_run_build\')) {
						markitup_run_build($field);
					}; 
                    } else {
					$codebuttons = "";
                    }'
            ),
        ),           
        true
    );
}

// STYLESHEET MASTER
function profilefields_manager_stylesheet() {

    global $db;
    
    $css = array(
		'name' => 'profilefields_manager.css',
		'tid' => 1,
		'attachedto' => '',
		'stylesheet' =>	'.pfm-diff-added {
        background: #d9f2d9;
        color: #1f5f1f;
        padding: 1px 2px;
        border-radius: 3px;
        }

        .pfm-diff-removed {
        background: #f8d7da;
        color: #842029;
        padding: 1px 2px;
        border-radius: 3px;
        text-decoration: line-through;
        }

        .pfm-diff-same {
        background: transparent;
        }',
		'cachefile' => 'profilefields_manager.css',
		'lastmodified' => TIME_NOW
	);

    return $css;
}

// STYLESHEET UPDATE
function profilefields_manager_stylesheet_update() {

    // Update-Stylesheet
    // wird an bestehende Stylesheets immer ganz am ende hinzugefügt
    $update = '';

    // Definiere den  Überprüfung-String (muss spezifisch für die Überprüfung sein)
    $update_string = '';

    return array(
        'stylesheet' => $update,
        'update_string' => $update_string
    );
}

// UPDATE CHECK
function profilefields_manager_is_updated(){

    global $db;

    if ($db->table_exists("usercp_pages")) {
        return true;
    }
    return false;
}
