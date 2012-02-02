<?php
/**
 * MySupport 0.4

 * Copyright 2010 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

define("MYSUPPORT_VERSION", "0.5");

$plugins->add_hook("admin_config_action_handler", "mysupport_admin_config_action_handler");
$plugins->add_hook("admin_config_menu", "mysupport_admin_config_menu");
$plugins->add_hook("admin_config_permissions", "mysupport_admin_config_permissions");
$plugins->add_hook("admin_config_plugins_activate_commit", "mysupport_settings_redirect");
$plugins->add_hook("admin_page_output_footer", "mysupport_settings_footer");
$plugins->add_hook("build_forumbits_forum", "mysupport_forum_overview");
$plugins->add_hook("build_friendly_wol_location_end", "mysupport_build_wol");
$plugins->add_hook("datahandler_post_validate_post", "mysupport_datahandler_post_validate_post");
$plugins->add_hook("editpost_start", "mysupport_thread_info");
$plugins->add_hook("fetch_wol_activity_end", "mysupport_friendly_wol");
$plugins->add_hook("forumdisplay_start", "mysupport_forumdisplay_searchresults");
$plugins->add_hook("forumdisplay_thread", "mysupport_threadlist_thread");
$plugins->add_hook("global_start", "mysupport_notices");
$plugins->add_hook("member_profile_end", "mysupport_profile");
$plugins->add_hook("modcp_start", "mysupport_modcp_support_denial");
$plugins->add_hook("modcp_start", "mysupport_navoption", -10);
$plugins->add_hook("modcp_start", "mysupport_thread_list");
$plugins->add_hook("moderation_start", "mysupport_do_inline_thread_moderation");
$plugins->add_hook("newreply_start", "mysupport_bump_thread_notice");
$plugins->add_hook("newreply_start", "mysupport_thread_info");
$plugins->add_hook("newthread_do_newthread_end", "mysupport_set_is_support_thread");
$plugins->add_hook("newthread_start", "mysupport_newthread");
$plugins->add_hook("newthread_start", "mysupport_thread_info");
$plugins->add_hook("postbit", "mysupport_postbit");
$plugins->add_hook("search_results_start", "mysupport_forumdisplay_searchresults");
$plugins->add_hook("search_results_thread", "mysupport_threadlist_thread");
$plugins->add_hook("showthread_start", "mysupport_bump_thread_notice");
$plugins->add_hook("showthread_start", "mysupport_showthread");
$plugins->add_hook("usercp_menu_built", "mysupport_navoption", -10);
$plugins->add_hook("usercp_start", "mysupport_thread_list");
$plugins->add_hook("usercp_start", "mysupport_usercp_options");

global $templatelist;
if(isset($templatelist))
{
	$templatelist .= ',';
}
$mysupport_templates = mysupport_templates();
$mysupport_templates = implode(",", $mysupport_templates);
$templatelist .= $mysupport_templates;

/**
 * These are just here for when I'm debugging or updating templates, to run some code at runtime which is usually called in the upgrade function
**/
if(!defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	/*if(!defined("MYBB_ADMIN_DIR"))
	{
		define("MYBB_ADMIN_DIR", MYBB_ROOT."/admin/");
	}*/
	//mysupport_do_templates(0, true);
	//mysupport_do_templates(1);
	//mysupport_template_edits(0);
	//mysupport_template_edits(1);
	//mysupport_import_settings();
	//mysupport_table_columns(1);
	//mysupport_stylesheet(2);
	//mysupport_cache();
}

function mysupport_info()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_info();
}

function mysupport_install()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_install();
}

function mysupport_is_installed()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_is_installed();
}

function mysupport_uninstall()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_uninstall();
}

function mysupport_activate()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_activate();
}

function mysupport_deactivate()
{
	require_once MYBB_ROOT."inc/plugins/mysupport/mysupport.php";
	
	return mysupport_do_deactivate();
}

function mysupport_cache($what = "")
{
	global $db, $cache;
	
	$old_cache = $cache->read("mysupport");
	$new_cache = array();
	
	if($what == "version" || !$what)
	{
		$new_cache['version'] = MYSUPPORT_VERSION;
	}
	else
	{
		$new_cache['version'] = $old_cache['version'];
	}
	
	if($what == "priorities" || !$what)
	{
		$query = $db->simple_select("mysupport", "mid, name, description, extra", "type = 'priority'");
		$new_cache['priorities'] = array();
		while($priority = $db->fetch_array($query))
		{
			$new_cache['priorities'][$priority['mid']] = $priority;
		}
	}
	else
	{
		$new_cache['priorities'] = $old_cache['priorities'];
	}
	
	if($what == "deniedreasons" || !$what)
	{
		$query = $db->simple_select("mysupport", "mid, name, description", "type = 'deniedreason'");
		$new_cache['deniedreasons'] = array();
		while($deniedreason = $db->fetch_array($query))
		{
			$new_cache['deniedreasons'][$deniedreason['mid']] = $deniedreason;
		}
	}
	else
	{
		$new_cache['deniedreasons'] = $old_cache['deniedreasons'];
	}
	
	$cache->update("mysupport", $new_cache);
}

// can't put this in the external functions file as it's needed for the templates list above
function mysupport_templates()
{
	return array(
		'mysupport_assigned',
		'mysupport_assigned_toyou',
		'mysupport_bestanswer',
		'mysupport_deny_support',
		'mysupport_deny_support_deny',
		'mysupport_deny_support_list',
		'mysupport_deny_support_post',
		'mysupport_deny_support_post_linked',
		'mysupport_form',
		'mysupport_form_ajax',
		'mysupport_jumpto_bestanswer',
		'mysupport_nav_option',
		'mysupport_notice',
		'mysupport_status_image',
		'mysupport_status_text',
		'mysupport_tab',
		'mysupport_threadlist',
		'mysupport_threadlist_footer',
		'mysupport_threadlist_list',
		'mysupport_threadlist_stats',
		'mysupport_threadlist_thread',
		'mysupport_deny_support_list_user',
		'mysupport_usercp_options',
		'mysupport_inline_thread_moderation',
		'mysupport_member_profile'
	);
}

// get the gid of the MySupport settings group
function mysupport_settings_gid()
{
	global $db;
	
	$query = $db->simple_select("settinggroups", "gid", "name = 'mysupport'", array("limit" => 1));
	$gid = $db->fetch_field($query, "gid");
	
	return intval($gid);
}

// redirect to the settings page after activating
function mysupport_settings_redirect()
{
	global $mybb, $db, $lang, $installed;
	
	if($installed === true && $mybb->input['plugin'] == "mysupport")
	{
		$lang->load("mysupport");
		
		$gid = mysupport_settings_gid();
		
		flash_message($lang->mysupport_activated, 'success');
		admin_redirect("index.php?module=config-settings&action=change&gid={$gid}");
	}
}

// show the form in the thread to change the status of the thread
function mysupport_showthread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $templates, $theme, $thread, $forum, $mysupport_status, $mysupport_options, $mysupport_js, $support_denial_reasons, $mod_log_action, $redirect;
	
	$lang->load("mysupport");

	$tid = intval($thread['tid']);
	$fid = intval($thread['fid']);
	
	if(mysupport_forum($forum['fid']) && $mybb->input['action'] != "mysupport" && $mybb->input['action'] != "bestanswer")
	{
		// load the denied reasons so we can display them to staff if necessary
		if($mybb->settings['enablemysupportsupportdenial'] == 1 && mysupport_usergroup("canmanagesupportdenial"))
		{
			$support_denial_reasons = array();
			$mysupport_cache = $cache->read("mysupport");
			if(!empty($mysupport_cache['deniedreasons']))
			{
				foreach($mysupport_cache['deniedreasons'] as $deniedreasons)
				{
					$support_denial_reasons[$deniedreason['mid']] = htmlspecialchars_uni($deniedreason['name']);
				}
			}
		}
		
		if($thread['issupportthread'] == 1)
		{
			$mysupport_options = "";
			$count = 0;
			$mysupport_solved = $mysupport_solved_and_close = $mysupport_technical = $mysupport_not_solved = $on_hold = $assigned_list = $priorities_list = $categories_list = $is_support_thread = "";
			// if it's not already solved
			if($thread['status'] != 1)
			{
				// can they mark as solved??
				if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
				{
					// closing when solved is either optional, or off
					if($mybb->settings['mysupportclosewhensolved'] != "always")
					{
						if($mybb->input['mysupport_full'])
						{
							$mysupport_solved = "<option value=\"1\">".$lang->solved."</option>";
						}
						else
						{
							$text = $lang->sprintf($lang->markas_link, $lang->solved);
							$class = "mysupport_tab_solved";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
							eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
						}
						++$count;
					}
					
					// is the ability to close turned on??
					if($mybb->settings['mysupportclosewhensolved'] != "never" && $thread['closed'] != 1)
					{
						// if the close setting isn't never, this option would show regardless of whether it's set to always or optional
						if($mybb->input['mysupport_full'])
						{
							$mysupport_solved_and_close = "<option value=\"3\">".$lang->solved_close."</option>";
						}
						else
						{
							$text = $lang->sprintf($lang->markas_link, $lang->solved_close);
							$class = "mysupport_tab_solved";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=3&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
							eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
						}
						++$count;
					}
				}
				
				// is the technical threads feature on??
				if($mybb->settings['enablemysupporttechnical'] == 1)
				{
					// can they mark as techincal??
					if(mysupport_usergroup("canmarktechnical"))
					{
						if($thread['status'] != 2)
						{
							// if it's not marked as technical, give an option to mark it as such
							if($mybb->input['mysupport_full'])
							{
								$mysupport_technical = "<option value=\"2\">".$lang->technical."</option>";
							}
							else
							{
								$text = $lang->sprintf($lang->markas_link, $lang->technical);
								$class = "mysupport_tab_technical";
								$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=2&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
								eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
							}
						}
						else
						{
							// if it's already marked as technical, have an option to put it back to normal
							if($mybb->input['mysupport_full'])
							{
								$mysupport_technical = "<option value=\"4\">".$lang->not_technical."</option>";
							}
							else
							{
								$text = $lang->sprintf($lang->markas_link, $lang->not_technical);
								$class = "mysupport_tab_technical";
								$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=4&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
								eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
							}
						}
						++$count;
					}
				}
			}
			// if it's solved, all you can do is mark it as not solved
			else
			{
				// are they allowed to mark it as not solved if it's been marked solved already??
				if($mybb->settings['mysupportunsolve'] == 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid'])))
				{
					if($mybb->input['mysupport_full'])
					{
						$mysupport_not_solved = "<option value=\"0\">".$lang->not_solved."</option>";
					}
					else
					{
						$text = $lang->sprintf($lang->markas_link, $lang->not_solved);
						$class = "mysupport_tab_not_solved";
						$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;status=0&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
					}
					++$count;
				}
			}
			
			$status_list = "";
			// if the current count is more than 0 there's some status options to show
			if($count > 0)
			{
				$current_status = mysupport_get_friendly_status($thread['status']);
				$status_list .= "<label for=\"status\">".$lang->markas."</label> <select name=\"status\">\n";
				// show the current status but have the value as -1 so it's treated as not submitting a status
				// doing this because the assigning and priority menus show their current values, so do it here too for consistency
				$status_list .= "<option value=\"-1\">".htmlspecialchars_uni($current_status)."</option>\n";
				// also show a blank option with a value of -1
				$status_list .= "<option value=\"-1\"></option>\n";
				$status_list .= $mysupport_not_solved."\n";
				$status_list .= $mysupport_solved."\n";
				$status_list .= $mysupport_solved_and_close."\n";
				$status_list .= $mysupport_technical."\n";
				$status_list .= "</select>\n";
				if($mybb->input['ajax'])
				{
					$status_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$status_list."\n</td>\n</tr>";
				}
			}
			
			if($mybb->settings['enablemysupportbestanswer'] == 1)
			{
				// this doesn't need to show when viewing the 'full' form, as only staff will be seeing that
				if($thread['bestanswer'] != 0 && !$mybb->input['mysupport_full'])
				{
					$post = intval($thread['bestanswer']);
					$text = $lang->jump_to_bestanswer_tab;
					$class = "mysupport_tab_best_answer";
					$url = $mybb->settings['bburl']."/".get_post_link($post, $tid)."#pid".$post;
					eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
				}
			}
			
			if($thread['status'] != 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid'])))
			{
				if($mybb->settings['enablemysupportonhold'] == 1)
				{
					if($mybb->input['mysupport_full'])
					{
						$checked = "";
						if($thread['onhold'] == 1)
						{
							$checked = " checked=\"checked\"";
						}
						$on_hold = "<label for=\"onhold\">".$lang->onhold_form."</label> <input type=\"checkbox\" name=\"onhold\" id=\"onhold\" value=\"1\"{$checked} />";
						if($mybb->input['ajax'])
						{
							$on_hold = "<tr>\n<td class=\"trow1\" align=\"center\">".$on_hold."\n</td>\n</tr>";
						}
					}
					else
					{
						if($thread['onhold'] == 1)
						{
							$text = $lang->hold_off;
							$class = "mysupport_tab_hold";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;onhold=0&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						}
						else
						{
							$text = $lang->hold_on;
							$class = "mysupport_tab_hold";
							$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;action=mysupport&amp;onhold=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
						}
						eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
					}
				}
			}
			
			// do we need to show the link to show the additional options??
			// for assigning users, and setting priorities and categories, check the permission; if we're requesting the information, show it, if not, set this to true
			$show_more_link = false;
			// check if assigning threads is enabled and make sure you can assign threads to people
			// also check if the thread is currently not solved, or if it's solved but you can unsolve it; if any of those are true, you may want to assign it
			if($mybb->settings['enablemysupportassign'] == 1 && mysupport_usergroup("canassign") && ($thread['status'] != 1 || ($thread['status'] == 1 && $mybb->settings['mysupportunsolve'] == 1)))
			{
				if($mybb->input['mysupport_full'])
				{
					$assign_users = mysupport_get_assign_users();
					
					// only continue if there's one or more users that can be assigned threads
					if(!empty($assign_users))
					{
						$assigned_list .= "<label for=\"assign\">".$lang->assign_to."</label> <select name=\"assign\">\n";
						$assigned_list .= "<option value=\"0\"></option>\n";
						
						foreach($assign_users as $assign_userid => $assign_username)
						{
							$selected = "";
							if($thread['assign'] == $assign_userid)
							{
								$selected = " selected=\"selected\"";
							}
							$assigned_list .= "<option value=\"".intval($assign_userid)."\"{$selected}>".htmlspecialchars_uni($assign_username)."</option>\n";
							++$count;
						}
						if($thread['assign'] != 0)
						{
							$assigned_list .= "<option value=\"-1\">".$lang->assign_to_nobody."</option>\n";
						}
						
						$assigned_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$assigned_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$assigned_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}
			
			// are priorities enabled and can this user set priorities??
			if($mybb->settings['enablemysupportpriorities'] == 1 && mysupport_usergroup("cansetpriorities"))
			{
				if($mybb->input['mysupport_full'])
				{
					$mysupport_cache = $cache->read("mysupport");
					if(!empty($mysupport_cache['priorities']))
					{
						$priorities_list .= "<label for=\"priority\">".$lang->priority."</label> <select name=\"priority\">\n";
						$priorities_list .= "<option value=\"0\"></option>\n";
					
						foreach($mysupport_cache['priorities'] as $priority)
						{
							$option_style = "";
							if(!empty($priority['extra']))
							{
								$option_style = " style=\"background: #".htmlspecialchars_uni($priority['extra'])."\"";
							}
							$selected = "";
							if($thread['priority'] == $priority['mid'])
							{
								$selected = " selected=\"selected\"";
							}
							$priorities_list .= "<option value=\"".intval($priority['mid'])."\"{$option_style}{$selected}>".htmlspecialchars_uni($priority['name'])."</option>\n";
							++$count;
						}
						if($thread['priority'] != 0)
						{
							$priorities_list .= "<option value=\"-1\">".$lang->priority_none."</option>\n";
						}
						$priorities_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$priorities_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$priorities_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}
			
			if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
			{
				if($mybb->input['mysupport_full'])
				{
					$categories = mysupport_get_categories($forum);
					if(!empty($categories))
					{
						$categories_list .= "<label for=\"category\">".$lang->category."</label> <select name=\"category\">\n";
						$categories_list .= "<option value=\"0\"></option>\n";
						
						foreach($categories as $category_id => $category)
						{
							$selected = "";
							if($thread['prefix'] == $category_id)
							{
								$selected = " selected=\"selected\"";
							}
							$categories_list .= "<option value=\"".intval($category_id)."\"{$selected}>".htmlspecialchars_uni($category)."</option>\n";
							++$count;
						}
						if($thread['prefix'] != 0)
						{
							$categories_list .= "<option value=\"-1\">".$lang->category_none."</option>\n";
						}
						$categories_list .= "</select>\n";
						if($mybb->input['ajax'])
						{
							$categories_list = "<tr>\n<td class=\"trow1\" align=\"center\">".$categories_list."\n</td>\n</tr>";
						}
					}
				}
				$show_more_link = true;
			}
			
			if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']) && $mybb->settings['enablemysupportnotsupportthread'] != 0)
			{
				if($mybb->input['mysupport_full'])
				{
					$checked = "";
					if($thread['issupportthread'] == 1)
					{
						$checked = " checked=\"checked\"";
					}
					$is_support_thread .= "<label for=\"issupportthread\">".$lang->issupportthread."</label>\n";
					$is_support_thread .= "<input type=\"checkbox\" name=\"issupportthread\" id=\"issupportthread\"{$checked} value=\"1\" />\n";
					if($mybb->input['ajax'])
					{
						$is_support_thread = "<tr>\n<td class=\"trow1\" align=\"center\">".$is_support_thread."\n</td>\n</tr>";
					}
				}
				$show_more_link = true;
			}
			
			if($show_more_link)
			{
				$mysupport_js = "<div id=\"mysupport_showthread_more_box\" style=\"display: none;\"></div>
<script type=\"text/javascript\">
function mysupport_more_link()
{
	var url = '{$mybb->settings['bburl']}/showthread.php?tid={$tid}&mysupport_full=1';
	
	new Ajax.Request(url+'&ajax=1', {
		method: 'get',
		onSuccess: function(data) {
			if(data.responseText != '')
			{
				$('mysupport_showthread_more_box').style.display = '';
				$('mysupport_showthread_more_box').innerHTML = data.responseText;
			}
			else
			{
				window.location = url;
			}
		}
	});
}

function mysupport_close_more_box()
{
	$('mysupport_showthread_more_box').style.display = 'none';
	$('mysupport_showthread_more_box').innerHTML = '';
}
</script>";
				
				$text = $lang->mysupport_tab_more;
				$class = "mysupport_tab_misc";
				$url = $mybb->settings['bburl']."/showthread.php?tid={$tid}&amp;mysupport_full=1";
				$onclick = " onclick=\"mysupport_more_link(); return false\"";
				eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
			}
		}
		else
		{
			$text = $lang->issupportthread_mark_as_support_thread;
			$class = "mysupport_tab_misc";
			$url = $mybb->settings['bburl']."/showthread.php?action=mysupport&amp;issupportthread=1&amp;tid={$tid}&amp;my_post_key={$mybb->post_code}";
			eval("\$mysupport_options .= \"".$templates->get('mysupport_tab')."\";");
		}
		
		if($mybb->input['mysupport_full'])
		{
			// are there actually any options to show for this user??
			if($count > 0)
			{
				if($mybb->input['ajax'] == 1)
				{
					eval("\$mysupport_options = \"".$templates->get('mysupport_form_ajax')."\";");
					// this is an AJAX request, echo and exit, GO GO GO
					echo $mysupport_options;
					exit;
				}
				else
				{
					eval("\$mysupport_options = \"".$templates->get('mysupport_form')."\";");
				}
			}
		}
		else
		{
			$mysupport_options = "<br /><div class=\"mysupport_tabs\">{$mysupport_options}</div>";
		}
		
		if($thread['issupportthread'] == 1)
		{
			$mysupport_status = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);
		}
	}
	
	if($mybb->input['action'] == "mysupport")
	{
		verify_post_check($mybb->input['my_post_key']);
		$status = $db->escape_string($mybb->input['status']);
		$assign = $db->escape_string($mybb->input['assign']);
		$priority = $db->escape_string($mybb->input['priority']);
		$category = $db->escape_string($mybb->input['category']);
		$onhold = $db->escape_string($mybb->input['onhold']);
		$issupportthread = $db->escape_string($mybb->input['issupportthread']);
		$tid = intval($thread['tid']);
		$fid = intval($thread['fid']);
		$old_status = intval($thread['status']);
		$old_assign = intval($thread['assign']);
		$old_priority = intval($thread['priority']);
		$old_category = intval($thread['prefix']);
		$old_onhold = intval($thread['onhold']);
		$old_issupportthread = intval($thread['issupportthread']);
		
		// we need to make sure they haven't edited the form to try to perform an action they're not allowed to do
		// we check everything in the entire form, if any part of it is wrong, it won't do anything
		if(!mysupport_forum($fid))
		{
			mysupport_error($lang->error_not_mysupport_forum);
			exit;
		}
		// are they trying to assign the same status it already has??
		if($status == $old_status && !isset($mybb->input['onhold']) && !isset($mybb->input['issupportthread']))
		{
			$duplicate_status = mysupport_get_friendly_status($status);
			mysupport_error($lang->sprintf($lang->error_same_status, $duplicate_status));
			exit;
		}
		elseif($status == 0)
		{
			// either the ability to unsolve is turned off,
			// they don't have permission to mark as not solved via group permissions, or they're not allowed to mark it as not solved even though they authored it
			if($mybb->settings['mysupportunsolve'] != 1 || (!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid'])))
			{
				mysupport_error($lang->no_permission_mark_notsolved);
				exit;
			}
			
			$valid_action = true;
		}
		elseif($status == 1)
		{
			// either they're not in a group that can mark as solved
			// or they're not allowed to mark it as solved even though they authored it
			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_mark_solved);
				exit;
			}
			
			$valid_action = true;
		}
		elseif($status == 2)
		{
			if($mybb->settings['enablemysupporttechnical'] != 1)
			{
				mysupport_error($lang->technical_not_enabled);
				exit;
			}
			
			// they don't have the ability to mark threads as technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				mysupport_error($lang->no_permission_mark_technical);
				exit;
			}
			
			$valid_action = true;
		}
		elseif($status == 3)
		{
			// either closing of threads is turned off altogether
			// or it's on, but they're not in a group that can't mark as solved
			if($thread['closed'] == 1 || $mybb->settings['mysupportclosewhensolved'] == "never" || ($mybb->settings['mysupportclosewhensolved'] != "never" && (!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))))
			{
				mysupport_error($lang->no_permission_mark_solved_close);
				exit;
			}
			
			$valid_action = true;
		}
		elseif($status == 4)
		{
			// they don't have the ability to mark threads as not technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				mysupport_error($lang->no_permission_mark_nottechnical);
				exit;
			}
			
			$valid_action = true;
		}
		// check if the thread is being put on/taken off hold
		// check here is a bit weird as it'll be 1/-1 if coming from the tab link, and 1 or nothing if coming from the checkbox in the form
		// if it's coming from the form, check if it's being put on hold and wasn't on hold before (put on hold), or the box wasn't checked and it was on hold before (taken off hold)
		// or, if it's coming from the link, check if it's being put on hold and wasn't on hold before (put on hold), or it's being taken off hold and was on hold before
		if(($mybb->input['via_form'] == 1 && (($onhold == 1 && $old_onhold == 0) || (!$onhold && $old_onhold == 1))) || (!$mybb->input['via_form'] && (($onhold == 1 && $old_onhold == 0) || ($onhold == -1 && $old_onhold == 1))))
		{
			if($mybb->settings['enablemysupportonhold'] != 1)
			{
				mysupport_error($lang->onhold_not_enabled);
				exit;
			}
			
			if($thread['status'] == 1)
			{
				mysupport_error($lang->onhold_solved);
				exit;
			}
			
			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_thread_hold);
				exit;
			}
			
			// we don't need to perform the big check above again, as if we're in here we know it's being changed
			// it'll either be 1, 0 or -1, if it's anything other than 1, we're taking it off hold
			if($onhold != 1)
			{
				$ohold = 0;
			}
			
			$valid_action = true;
		}
		if(($mybb->input['via_form'] == 1 && (($issupportthread == 1 && $old_issupportthread == 0) || (!$issupportthread && $old_issupportthread == 1))))
		{
			if($mybb->settings['enablemysupportnotsupportthread'] != 1)
			{
				mysupport_error($lang->issupportthread_not_enabled);
				exit;
			}
			
			if(!mysupport_usergroup("canmarksolved") && !($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
			{
				mysupport_error($lang->no_permission_issupportthread);
			}
		}
		// trying to assign a thread to someone
		if($assign != 0)
		{
			if($mybb->settings['enablemysupportassign'] != 1)
			{
				mysupport_error($lang->assign_not_enabled);
				exit;
			}
			// trying to assign a solved thread
			// this is needed to see if we're trying to assign a currently solved thread whilst at the same time changing the status of it
			// the option to assign will still be there if it's solved as you may want to unsolve it and assign it again, but we can't assign it if it's staying solved, we have to be unsolving it
			if($thread['status'] == 1 && $status != 0)
			{
				mysupport_error($lang->assign_solved);
				exit;
			}
			
			if(!mysupport_usergroup("canassign"))
			{
				mysupport_error($lang->assign_no_perms);
				exit;
			}
			
			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users) && $assign != "-1")
			{
				mysupport_error($lang->assign_invalid);
				exit;
			}
			
			$valid_action = true;
		}
		// setting a priority
		if($priority != 0)
		{
			if($mybb->settings['enablemysupportpriorities'] != 1)
			{
				mysupport_error($lang->priority_not_enabled);
				exit;
			}
			
			if(!mysupport_usergroup("cansetpriorities"))
			{
				mysupport_error($lang->priority_no_perms);
				exit;
			}
			
			if($thread['status'] == 1 && $status != 0)
			{
				mysupport_error($lang->priority_solved);
				exit;
			}
			
			$mysupport_cache = $cache->read("mysupport");
			$mids = array();
			if(!empty($mysupport_cache['priorities']))
			{
				foreach($mysupport_cache['priorities'] as $priority_info)
				{
					$mids[] = intval($priority_info['mid']);
				}
			}
			if(!in_array($priority, $mids) && $priority != "-1")
			{
				mysupport_error($lang->priority_invalid);
				exit;
			}
			
			$valid_action = true;
		}
		// setting a category
		if($category != 0)
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				mysupport_error($lang->category_invalid);
				exit;
			}
			
			$valid_action = true;
		}
		// it didn't hit an error with any of the above, it's a valid action
		if($valid_action !== false)
		{
			// if you're choosing the same status or choosing none
			// and assigning the same user or assigning none (as in the empty option, not choosing 'Nobody' to remove an assignment)
			// and setting the same priority or setting none (as in the empty option, not choosing 'None' to remove a priority)
			// and setting the same hold status, and setting the same issupportthread status
			// then you're not actually doing anything, because you're either choosing the same stuff, or choosing nothing at all
			if(($status == $old_status || $status == "-1") && ($assign == $old_assign || $assign == 0) && ($priority == $old_priority || $priority == 0) && ($category == $old_category || $category == 0) && ($onhold == $old_onhold) && ($issupportthread == $old_issupportthread))
			{
				mysupport_error($lang->error_no_action);
				exit;
			}
			
			$mod_log_action = "";
			$redirect = "";
			
			if($issupportthread != $old_issupportthread)
			{
				mysupport_change_issupportthread($thread, $issupportthread);
			}
			else
			{
				// change the status and move/close
				if($status != $old_status && $status != "-1")
				{
					mysupport_change_status($thread, $status);
				}
				
				if($onhold != $old_onhold)
				{
					mysupport_change_hold($thread, $onhold);
				}
				
				// we need to see if the same user has been submitted so it doesn't run this for no reason
				// we also need to check if it's being marked as solved, if it is we don't need to do anything with assignments, it'll just be ignored
				if($assign != $old_assign && ($assign != 0 && $status != 1 && $status != 3))
				{
					mysupport_change_assign($thread, $assign);
				}
				
				// we need to see if the same priority has been submitted so it doesn't run this for no reason
				// we also need to check if it's being marked as solved, if it is we don't need to do anything with priorities, it'll just be ignored
				if($priority != $old_priority && ($priority != 0 && $status != 1))
				{
					mysupport_change_priority($thread, $priority);
				}
				
				// we need to see if the same category has been submitted so it doesn't run this for no reason
				if($category != $old_category && ($category != 0 && $status != 1))
				{
					mysupport_change_category($thread, $category);
				}
			}
			
			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			// where should they go to afterwards??
			$thread_url = get_thread_link($tid);
			redirect($thread_url, $redirect);
		}
	}
	elseif($mybb->input['action'] == "bestanswer")
	{
		verify_post_check($mybb->input['my_post_key']);
		if($mybb->settings['enablemysupportbestanswer'] != 1)
		{
			mysupport_error($lang->bestanswer_not_enabled);
			exit;
		}
		
		$pid = intval($mybb->input['pid']);
		// we only have a pid so we need to get the tid, fid, uid, and mysupport information of the thread it belongs to
		$query = $db->query("
			SELECT t.fid, t.tid, t.uid AS author_uid, p.uid AS bestanswer_uid, t.status, t.bestanswer
			FROM ".TABLE_PREFIX."threads t
			INNER JOIN ".TABLE_PREFIX."forums f
			INNER JOIN ".TABLE_PREFIX."posts p
			ON (t.tid = p.tid AND t.fid = f.fid AND p.pid = '".$pid."')
		");
		$post_info = $db->fetch_array($query);
		
		// is this post in a thread that isn't within an allowed forum??
		if(!mysupport_forum($post_info['fid']))
		{
			mysupport_error($lang->bestanswer_invalid_forum);
			exit;
		}
		// did this user author this thread??
		elseif($mybb->user['uid'] != $post_info['author_uid'])
		{
			mysupport_error($lang->bestanswer_not_author);
			exit;
		}
		// is this post already the best answer??
		elseif($pid == $post_info['bestanswer'])
		{
			// this will mark it as the best answer
			$status_update = array(
				"bestanswer" => 0
			);
			// update the bestanswer column for this thread with 0
			$db->update_query("threads", $status_update, "tid = '".intval($post_info['tid'])."'");
			
			// are we removing points for this??
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupportbestanswerpoints']) && $mybb->settings['mysupportbestanswerpoints'] != 0)
				{
					mysupport_update_points($mybb->settings['mysupportbestanswerpoints'], $post_info['bestanswer_uid'], true);
				}
			}
			
			$redirect = "";
			mysupport_redirect_message($lang->unbestanswer_redirect);
			
			// where should they go to afterwards??
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
		// mark it as the best answer
		else
		{
			$status_update = array(
				"bestanswer" => intval($pid)
			);
			// update the bestanswer column for this thread with the pid of the best answer
			$db->update_query("threads", $status_update, "tid = '".intval($post_info['tid'])."'");
			
			// are we adding points for this??
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupportbestanswerpoints']) && $mybb->settings['mysupportbestanswerpoints'] != 0)
				{
					mysupport_update_points($mybb->settings['mysupportbestanswerpoints'], $post_info['bestanswer_uid']);
				}
			}
			
			// if this thread isn't solved yet, do that too whilst we're here
			// if they're marking a post as the best answer, it must have solved the thread, so save them marking it as solved manually
			if($post_info['status'] != 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $post_info['author_uid'] == $mybb->user['uid'])))
			{
				$mod_log_action = "";
				$redirect = "";
				
				// change the status
				mysupport_change_status($post_info, 1);
				
				if(!empty($mod_log_action))
				{
					$mod_log_data = array(
						"fid" => intval($post_info['fid']),
						"tid" => intval($post_info['tid'])
					);
					log_moderator_action($mod_log_data, $mod_log_action);
				}
				mysupport_redirect_message($lang->bestanswer_redirect);
			}
			else
			{
				$redirect = "";
				mysupport_redirect_message($lang->bestanswer_redirect);
			}
			
			// where should they go to afterwards??
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
	}
}

// generate CSS classes for the priorities and select the categories, and load inline thread moderation
function mysupport_forumdisplay_searchresults()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $foruminfo, $priorities, $mysupport_priority_classes;
	
	// basically it's much easier (and neater) to generate makeshift classes for priorities for highlighting threads than adding inline styles
	$mysupport_cache = $cache->read("mysupport");
	if(!empty($mysupport_cache['priorities']))
	{
		// build an array of all the priorities
		$priorities = array();
		// start the CSS classes
		$mysupport_priority_classes = "";
		$mysupport_priority_classes .= "\n<style type=\"text/css\">\n";
		foreach($mysupport_cache['priorities'] as $priority)
		{
			// add the name to the array, then we can get the relevant name for each priority when looping through the threads
			$priorities[$priority['mid']] = strtolower(htmlspecialchars_uni($priority['name']));
			// add the CSS class
			if(!empty($priority['extra']))
			{
				$mysupport_priority_classes .= ".mysupport_priority_".strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priority['name'])))." {\n";
				$mysupport_priority_classes .= "\tbackground: #".htmlspecialchars_uni($priority['extra']).";\n";
				$mysupport_priority_classes .= "}\n";
			}
		}
		$mysupport_priority_classes .= "</style>\n";
	}
	
	$mysupport_forums = mysupport_forums();
	// if we're viewing a forum which has MySupport enabled, or we're viewing search results and there's at least 1 MySupport forum, show the MySupport options in the inline moderation menu
	if((THIS_SCRIPT == "forumdisplay.php" && mysupport_forum($mybb->input['fid'])) || (THIS_SCRIPT == "search.php" && !empty($mysupport_forums)))
	{
		mysupport_inline_thread_moderation();
	}
}

// show the status of a thread for each thread on the forum display or a list of search results
function mysupport_threadlist_thread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $lang, $templates, $theme, $foruminfo, $thread, $is_mysupport_forum, $mysupport_status, $mysupport_assigned, $mysupport_bestanswer, $priorities, $priority_class, $inline_mod_checkbox;
	
	// need to reset these outside of the the check for if it's a MySupport forum, otherwise they don't get unset in search results where the forum of the next thread may not be a MySupport forum
	$mysupport_status = "";
	$priority_class = "";
	$mysupport_assigned = "";
	$mysupport_bestanswer = "";
	
	if($thread['issupportthread'] != 1 || strpos($thread['closed'], "moved") !== false)
	{
		return;
	}
	
	// this function is called for the thread list on the forum display and the list of threads for search results, however the source of the fid is different
	// if this is the forum display, get it from the info on the forum we're in
	if(THIS_SCRIPT == "forumdisplay.php")
	{
		$fid = $foruminfo['fid'];
	}
	// if this is a list of search results, get it from the array of info about the thread we're looking at
	// this means that out of all the results, only threads in MySupport forums will show this information
	elseif(THIS_SCRIPT == "search.php")
	{
		$fid = $thread['fid'];
	}
	
	if(mysupport_forum($fid))
	{
		if($thread['priority'] != 0 && $thread['visible'] == 1)
		{
			$priority_class = " mysupport_priority_".htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']]));
		}
		if(THIS_SCRIPT == "search.php")
		{
			$inline_mod_checkbox = str_replace("{priority_class}", $priority_class, $inline_mod_checkbox);
		}
		
		// the only thing we might want to do with sticky threads is to give them a priority, to highlight them; they're not going to have a status or be assigned to anybody
		// after we've done the priority, we can exit
		if($thread['sticky'] == 1)
		{
			return;
		}
		
		$mysupport_status = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);
		
		if($thread['assign'] != 0)
		{
			if($thread['assign'] == $mybb->user['uid'])
			{
				eval("\$mysupport_assigned = \"".$templates->get('mysupport_assigned_toyou')."\";");
			}
			else
			{
				eval("\$mysupport_assigned = \"".$templates->get('mysupport_assigned')."\";");
			}
		}
		
		if($mybb->settings['enablemysupportbestanswer'] == 1)
		{
			if($thread['bestanswer'] != 0)
			{
				$post = intval($thread['bestanswer']);
				$jumpto_bestanswer_url = get_post_link($post, $tid)."#pid".$post;
				$bestanswer_image = "mysupport_bestanswer.gif";
				eval("\$mysupport_bestanswer = \"".$templates->get('mysupport_jumpto_bestanswer')."\";");
			}
		}
	}
	else
	{
		$inline_mod_checkbox = str_replace("{priority_class}", "", $inline_mod_checkbox);
	}
}

// loads the dropdown menu for inline thread moderation
function mysupport_inline_thread_moderation()
{
	global $mybb, $db, $cache, $lang, $templates, $foruminfo, $mysupport_inline_thread_moderation;
	
	$lang->load("mysupport");
	
	$mysupport_solved = $mysupport_not_solved = $mysupport_solved_and_close = $mysupport_technical = $mysupport_not_technical = "";
	if(mysupport_usergroup("canmarksolved"))
	{
		$mysupport_solved = "<option value=\"mysupport_status_1\">-- ".$lang->solved."</option>";
		$mysupport_not_solved = "<option value=\"mysupport_status_0\">-- ".$lang->not_solved."</option>";
		if($mybb->settings['mysupportclosewhensolved'] != "never")
		{
			$mysupport_solved_and_close = "<option value=\"mysupport_status_3\">-- ".$lang->solved_close."</option>";
		}
	}
	if($mybb->settings['enablemysupporttechnical'] == 1)
	{
		if(mysupport_usergroup("canmarktechnical"))
		{
			$mysupport_technical = "<option value=\"mysupport_status_2\">-- ".$lang->technical."</option>";
			$mysupport_not_technical = "<option value=\"mysupport_status_4\">-- ".$lang->not_technical."</option>";
		}
	}
	
	$mysupport_onhold = $mysupport_offhold = "";
	if($mybb->settings['enablemysupportonhold'] == 1)
	{
		if(mysupport_usergroup("canmarksolved"))
		{
			$mysupport_onhold = "<option value=\"mysupport_onhold_1\">-- ".$lang->hold_status_onhold."</option>";
			$mysupport_offhold = "<option value=\"mysupport_onhold_0\">-- ".$lang->hold_status_offhold."</option>";
		}
	}
	
	if($mybb->settings['enablemysupportassign'] == 1)
	{
		$mysupport_assign = "";
		$assign_users = mysupport_get_assign_users();
		// only continue if there's one or more users that can be assigned threads
		if(!empty($assign_users))
		{
			foreach($assign_users as $assign_userid => $assign_username)
			{
				$mysupport_assign .= "<option value=\"mysupport_assign_".intval($assign_userid)."\">-- ".htmlspecialchars_uni($assign_username)."</option>\n";
			}
		}
	}
	
	if($mybb->settings['enablemysupportpriorities'] == 1)
	{
		$mysupport_cache = $cache->read("mysupport");
		$mysupport_priorities = "";
		// only continue if there's any priorities
		if(!empty($mysupport_cache['priorities']))
		{
			foreach($mysupport_cache['priorities'] as $priority)
			{
				$mysupport_priorities .= "<option value=\"mysupport_priority_".intval($priority['mid'])."\">-- ".htmlspecialchars_uni($priority['name'])."</option>\n";
			}
		}
	}
	
	$mysupport_categories = "";
	$categories_users = mysupport_get_categories($foruminfo['fid']);
	// only continue if there's any priorities
	if(!empty($categories_users))
	{
		foreach($categories_users as $category_id => $category_name)
		{
			$mysupport_categories .= "<option value=\"mysupport_priority_".intval($category_id)."\">-- ".htmlspecialchars_uni($category_name)."</option>\n";
		}
	}
	
	eval("\$mysupport_inline_thread_moderation = \"".$templates->get('mysupport_inline_thread_moderation')."\";");
}

// perform inline thread moderation on multiple threads
function mysupport_do_inline_thread_moderation()
{
	global $mybb;
	
	// we're hooking into the start of moderation.php, so if we're not submitting a MySupport action, exit now
	if(strpos($mybb->input['action'], "mysupport") === false)
	{
		return false;
	}
	
	verify_post_check($mybb->input['my_post_key']);
	
	global $db, $cache, $lang, $mod_log_action, $redirect;
	
	$lang->load("mysupport");
	
	$fid = intval($mybb->input['fid']);
	if(!is_moderator($fid, 'canmanagethreads'))
	{
		error_no_permission();
	}
	if($mybb->input['inlinetype'] == "search")
	{
		$type = "search";
		$id = $mybb->input['searchid'];
		$redirect_url = "search.php?action=results&sid=".rawurlencode($id);
	}
	else
	{
		$type = "forum";
		$id = $fid;
		$redirect_url = get_forum_link($fid);
	}
	$threads = getids($id, $type);
	if(count($threads) < 1)
	{
		mysupport_error($lang->error_inline_nothreadsselected);
		exit;
	}
	clearinline($id, $type);
	
	$tids = implode(",", array_map("intval", $threads));
	$mysupport_threads = array();
	// in a list of search results, you could see threads that aren't from a MySupport forum, but the MySupport options will always show in the inline moderation options regardless of this
	// this is a way of determining which of the selected threads from a list of search results are in a MySupport forum
	// this isn't necessary for inline moderation via the forum display, as the options only show in MySupport forums to begin with
	if($type == "search")
	{
		// list of MySupport forums
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
		// query all the threads that are in the list of TIDs and where the FID is also in the list of MySupport forums and where the thread is set to be a support thread
		// this will knock out the non-MySupport threads
		$query = $db->simple_select("threads", "tid", "fid IN (".$db->escape_string($mysupport_forums).") AND tid IN (".$db->escape_string($tids).") AND issupportthread = '1'");
		while($tid = $db->fetch_field($query, "tid"))
		{
			$mysupport_threads[] = intval($tid);
		}
		$threads = $mysupport_threads;
		// if the new list of threads is empty, no MySupport threads have been selected
		if(count($threads) < 1)
		{
			mysupport_error($lang->no_mysupport_threads_selected);
			exit;
		}
	}
	// make sure we only have threads that are set to be support threads
	elseif($type == "forum")
	{
		$query = $db->simple_select("threads", "tid", "tid IN (".$db->escape_string($tids).") AND issupportthread = '1'");
		while($tid = $db->fetch_field($query, "tid"))
		{
			$mysupport_threads[] = intval($tid);
		}
		$threads = $mysupport_threads;
		// if the new list of threads is empty, no MySupport threads have been selected
		if(count($threads) < 1)
		{
			mysupport_error($lang->no_mysupport_threads_selected);
			exit;
		}
	}
	
	$mod_log_action = "";
	$redirect = "";
	
	if(strpos($mybb->input['action'], "status") !== false)
	{
		$status = str_replace("mysupport_status_", "", $mybb->input['action']);
		if($status == 2 || $status == 4)
		{
			$perm = "canmarktechnical";
		}
		else
		{
			$perm = "canmarksolved";
		}
		// they don't have permission to perform this action, so go through the different statuses and show an error for the right one
		if(!mysupport_usergroup($perm))
		{
			switch($status)
			{
				case 1:
					mysupport_error($lang->no_permission_mark_solved_multi);
					break;
				case 2:
					mysupport_error($lang->no_permission_mark_technical_multi);
					break;
				case 3:
					mysupport_error($lang->no_permission_mark_solved_close_multi);
					break;
				case 4:
					mysupport_error($lang->no_permission_mark_nottechnical_multi);
					break;
				default:
					mysupport_error($lang->no_permission_mark_notsolved_multi);
			}
		}
		
		mysupport_change_status($threads, $status, true);
	}
	if(strpos($mybb->input['action'], "onhold") !== false)
	{
		$hold = str_replace("mysupport_onhold_", "", $mybb->input['action']);
		
		if(!mysupport_usergroup("canmarksolved"))
		{
			mysupport_error($lang->no_permission_thread_hold_multi);
			exit;
		}
		
		mysupport_change_hold($threads, $hold, true);
	}
	elseif(strpos($mybb->input['action'], "assign") !== false)
	{
		if(!mysupport_usergroup("canassign"))
		{
			mysupport_error($lang->assign_no_perms);
			exit;
		}
		$assign = str_replace("mysupport_assign_", "", $mybb->input['action']);
		if($assign == 0)
		{
			// in the function to change the assigned user, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$assign = -1;
		}
		else
		{
			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users))
			{
				mysupport_error($lang->assign_invalid);
				exit;
			}
		}
		
		mysupport_change_assign($threads, $assign, true);
	}
	elseif(strpos($mybb->input['action'], "priority") !== false)
	{
		if(!mysupport_usergroup("cansetpriorities"))
		{
			mysupport_error($lang->priority_no_perms);
			exit;
		}
		$priority = str_replace("mysupport_priority_", "", $mybb->input['action']);
		if($priority == 0)
		{
			// in the function to change the priority, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$priority = -1;
		}
		else
		{
			$mysupport_cache = $cache->read("mysupport");
			$mids = array();
			if(!empty($mysupport_cache['priorities']))
			{
				foreach($mysupport_cache['priorities'] as $priority_info)
				{
					$mids[] = intval($priority_info['mid']);
				}
			}
			if(!in_array($priority, $mids))
			{
				mysupport_error($lang->priority_invalid);
				exit;
			}
		}
		
		mysupport_change_priority($threads, $priority, true);
	}
	elseif(strpos($mybb->input['action'], "category") !== false)
	{
		$category = str_replace("mysupport_category_", "", $mybb->input['action']);
		if($category == 0)
		{
			// in the function to change the category, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$category = -1;
		}
		else
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				mysupport_error($lang->category_invalid);
				exit;
			}
		}
		
		mysupport_change_category($threads, $category, true);
	}
	$mod_log_data = array(
		"fid" => intval($fid)
	);
	log_moderator_action($mod_log_data, $mod_log_action);
	redirect($redirect_url, $redirect);
}

// check if a user is denied support when they're trying to make a new thread
function mysupport_newthread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $forum;
	
	// this is a MySupport forum and this user has been denied support
	if(mysupport_forum($forum['fid']) && $mybb->user['deniedsupport'] == 1)
	{
		// start the standard error message to show
		$deniedsupport_message = $lang->deniedsupport;
		// if a reason has been set for this user
		if($mybb->user['deniedsupportreason'] != 0)
		{
			$query = $db->simple_select("mysupport", "name, description", "mid = '".intval($mybb->user['deniedsupportreason'])."'");
			$deniedsupportreason = $db->fetch_array($query);
			$deniedsupport_message .= "<br /><br />".$lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($deniedsupportreason['name']));
			if($deniedsupportreason['description'] != "")
			{
				$deniedsupport_message .= "<br />".$lang->sprintf($lang->deniedsupport_reason_extra, htmlspecialchars_uni($deniedsupportreason['description']));
			}
		}
		mysupport_error($deniedsupport_message);
		exit;
	}
}

function mysupport_forum_overview($fid)
{
	
}

function mysupport_do_thread_info()
{
	
}

function mysupport_thread_info()
{
	
}

function mysupport_set_is_support_thread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $thread_info;
	
	if(mysupport_forum($thread_info['fid']))
	{
		if($mybb->settings['enablemysupportnotsupportthread'] == 2)
		{
			$update = array(
				"issupportthread" => 0
			);
			$db->update_query("threads", $update, "tid = '".intval($thread_info['tid'])."'");
		}
	}
}

function mysupport_datahandler_post_validate_post($data)
{
	global $db, $posthandler;
	
	if($mybb->settings['enablemysupport'] != 1 || count($posthandler->get_errors()) > 0)
	{
		return;
	}
	// if we're editing a post, see if it's the last post in the thread and was written by the thread poster
	if($posthandler->method == "update")
	{
		$post = get_post($posthandler->data['pid']);
		$thread_tid = $post['tid'];
		$thread_uid = $post['uid'];
		
		$query = $db->simple_select("posts", "pid", "tid = '".intval($thread_tid)."'", array("order_by" => "dateline", "order_dir" => "DESC", "limit" => 1));
		$pid = $db->fetch_field($query, "pid");
		$posthandler->data['uid'] = $posthandler->data['edit_uid'];
	}
	else
	{
		$thread = get_thread($posthandler->data['tid']);
		$thread_tid = $posthandler->data['tid'];
		$thread_uid = $thread['uid'];
	}
	
	// the user submitting this data is the author of the thread
	// and they're either making a new reply
	// or they're editing the last post in the thread, which is theirs
	// take the thread off hold, as they've made an update
	if($posthandler->data['uid'] == $thread_uid && ($posthandler->method == "insert" || ($posthandler->method == "update" && $posthandler->data['pid'] == $pid)))
	{
		$update = array(
			"onhold" => 0
		);
	}
	else
	{
		$update = array(
			"onhold" => 1
		);
	}
	
	$db->update_query("threads", $update, "tid = '".intval($thread_tid)."'");
}

// show a message if someone is going to bump a thread that is solved and isn't their thread
function mysupport_bump_thread_notice()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $lang, $thread, $forum, $mysupport_solved_bump_message;
	
	if(mysupport_forum($forum['fid']))
	{
		if($mybb->settings['mysupportbumpnotice'] == 1)
		{
			if($thread['status'] == 1 && $thread['uid'] != $mybb->user['uid'] && !(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid'])))
			{
				$mysupport_solved_bump_message = $lang->mysupport_solved_bump_message."\n\n";
			}
		}
	}
}

// highlight the best answer from the thread and show the status of the thread in each post
function mysupport_postbit(&$post)
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $thread, $forum, $support_denial_reasons;
	
	$lang->load("mysupport");
	
	if(mysupport_forum($forum['fid']))
	{
		$post['mysupport_bestanswer'] = "";
		$post['mysupport_bestanswer_highlight'] = "";
		$post['mysupport_staff_highlight'] = "";
		if($post['visible'] == 1)
		{
			if($mybb->settings['enablemysupportbestanswer'] == 1)
			{
				if($thread['bestanswer'] == $post['pid'])
				{
					$post['mysupport_bestanswer_highlight'] = " mysupport_bestanswer_highlight";
				}
				
				if($mybb->user['uid'] == $thread['uid'])
				{
					if($thread['bestanswer'] == $post['pid'])
					{
						$bestanswer_img = "mysupport_bestanswer";
						$bestanswer_alt = $lang->unbestanswer_img_alt;
						$bestanswer_title = $lang->unbestanswer_img_title;
						$bestanswer_desc = $lang->unbestanswer_img_alt;
					}
					else
					{
						$bestanswer_img = "mysupport_unbestanswer";
						$bestanswer_alt = $lang->bestanswer_img_alt;
						$bestanswer_title = $lang->bestanswer_img_title;
						$bestanswer_desc = $lang->bestanswer_img_alt;
					}
					
					eval("\$post['mysupport_bestanswer'] = \"".$templates->get('mysupport_bestanswer')."\";");
				}
			}
			
			// we only want to do this if it's not been highlighted as the best answer; that takes priority over this
			if(empty($post['mysupport_bestanswer_highlight']))
			{
				if($mybb->settings['mysupporthighlightstaffposts'] == 1)
				{
					$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
					// various checks to see if they should be considered staff or not
					if(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid']))
					{
						$post['mysupport_staff_highlight'] = " mysupport_staff_highlight";
					}
				}
			}
		}
		
		if($mybb->settings['enablemysupportsupportdenial'] == 1)
		{
			$post['mysupport_deny_support_post'] = "";
			$denied_text = $denied_text_desc = "";
			
			if($post['deniedsupport'] == 1)
			{
				$denied_text = $lang->denied_support;
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$denied_text_desc = $lang->sprintf($lang->revoke_from, htmlspecialchars_uni($post['username']));
					if(array_key_exists($post['deniedsupportreason'], $support_denial_reasons))
					{
						$denied_text .= ": ".htmlspecialchars_uni($support_denial_reasons[$post['deniedsupportreason']]);
					}
					$denied_text .= " ".$lang->denied_support_click_to_edit_revoke;
					eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post_linked')."\";");
				}
				else
				{
					$denied_text_desc = $lang->denied_support;
					eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post')."\";");
				}
			}
			else
			{
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
					// various checks to see if they should be considered staff or not - if they are, don't show this for this user
					if(!(mysupport_usergroup("canmarksolved", $post_groups) || is_moderator($forum['fid'], "", $post['uid'])))
					{
						$denied_text_desc = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($post['username']));
						eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post_linked')."\";");
					}
				}
			}
		}
		
		if($thread['issupportthread'] == 1)
		{
			$post['mysupport_status'] = mysupport_get_display_status($thread['status'], $thread['onhold'], $thread['statustime'], $thread['uid']);
		}
	}
}

// show MySupport information on a user's profile
function mysupport_profile()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $templates, $theme, $memprofile, $mysupport_info;
	
	$lang->load("mysupport");
	
	$something_to_show = false;
	
	if($mybb->settings['enablemysupportbestanswer'] == 1)
	{
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
		$query = $db->write_query("
			SELECT COUNT(*) AS bestanswers
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."posts p
			ON (t.bestanswer = p.pid)
			WHERE t.fid IN (".$db->escape_string($mysupport_forums).")
			AND p.uid = '".intval($memprofile['uid'])."'
		");
		$bestanswers = $db->fetch_field($query, "bestanswers");
		$bestanswers = "<tr><td class=\"trow1\" width=\"50%\"><strong>".$lang->best_answers_given."</strong></td><td class=\"trow1\" width=\"50%\">".$bestanswers."</td></tr>";
		$something_to_show = true;
	}
	
	if($mybb->settings['enablemysupportsupportdenial'] == 1)
	{
		if($memprofile['deniedsupport'] == 1)
		{
			$denied_text = $lang->denied_support_profile;
			if(mysupport_usergroup("canmanagesupportdenial"))
			{
				$mysupport_cache = $cache->read("mysupport");
				if(array_key_exists($memprofile['deniedsupportreason'], $mysupport_cache['deniedreasons']))
				{
					$deniedsupportreason = $mysupport_cache['deniedreasons'][$memprofile['deniedsupportreason']]['name'];
					$denied_text .= " ".$lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($deniedsupportreason));
				}
				$denied_text = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=supportdenial&do=denysupport&uid=".$memprofile['uid']."\">".$denied_text."</a>";
			}
			$denied_text = "<tr><td colspan=\"2\" class=\"trow2\">".$denied_text."</td></tr>";
			$something_to_show = true;
		}
	}
	
	if($something_to_show)
	{
		eval("\$mysupport_info = \"".$templates->get('mysupport_member_profile')."\";");
	}
}

// show a notice for technical and/or assigned threads
function mysupport_notices()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $forum, $thread, $mysupport_tech_notice, $mysupport_assign_notice;
	
	$lang->load("mysupport");
	
	// this function does both the technical threads alert and the assigned threads alert
	// both similar enough to keep in one function but different enough to be separated into two chunks
	
	// some code that's used in both, work out now
	
	// check for THIS_SCRIPT so it doesn't execute if we're viewing the technical threads list in the MCP or support threads in the UCP with an FID
	if(($mybb->input['fid'] || $mybb->input['tid']) && THIS_SCRIPT != "modcp.php" && THIS_SCRIPT != "usercp.php")
	{
		if($mybb->input['fid'])
		{
			$fid = intval($mybb->input['fid']);
		}
		else
		{
			$tid = intval($mybb->input['tid']);
			$thread_info = get_thread($tid);
			$fid = $thread_info['fid'];
		}
	}
	else
	{
		$fid = "";
	}
	
	// the technical threads notice
	$mysupport_tech_notice = "";
	// is it enabled??
	if($mybb->settings['enablemysupporttechnical'] == 1 && $mybb->settings['mysupporttechnicalnotice'] != "off")
	{
		// this user is in an allowed usergroup??
		if(mysupport_usergroup("canseetechnotice"))
		{
			// the notice is showing on all pages
			if($mybb->settings['mysupporttechnicalnotice'] == "global")
			{
				// count for the entire forum
				$technical_count_global = mysupport_get_count("technical");
			}
			
			// if the notice is enabled, it'll at least show in the forums containing technical threads
			if(!empty($fid))
			{
				// count for the forum we're in now
				$technical_count_forum = mysupport_get_count("technical", $fid);
			}
			
			$notice_url = "modcp.php?action=technicalthreads";
			
			if($technical_count_forum > 0)
			{
				$notice_url .= "&amp;fid=".$fid;
			}
			
			// now to show the notice itself
			// it's showing globally
			if($mybb->settings['mysupporttechnicalnotice'] == "global")
			{
				if($technical_count_global == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				// we're in a forum/thread, and the count for this forum, generated above, is more than 0, show the global count and forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_global_forum, intval($technical_count_global), $threads_text, intval($technical_count_forum));
				}
				// either there's no forum/thread, or there is but there's no tech threads in this forum, just show the global count
				else
				{
					$notice_text = $lang->sprintf($lang->technical_global, intval($technical_count_global), $threads_text);
				}
				
				if($technical_count_global > 0)
				{
					eval("\$mysupport_tech_notice = \"".$templates->get('mysupport_notice')."\";");
				}
			}
			// it's only showing in the relevant forums, if necessary
			elseif($mybb->settings['mysupporttechnicalnotice'] == "specific")
			{
				if($technical_count_forum == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				// we're inside a forum/thread and the count for this forum, generated above, is more than 0, show the forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_forum, intval($technical_count_forum), $threads_text);
					eval("\$mysupport_tech_notice = \"".$templates->get('mysupport_notice')."\";");
				}
			}
		}
	}
	
	if($mybb->settings['enablemysupportassign'] == 1)
	{
		// this user is in an allowed usergroup??
		if(mysupport_usergroup("canbeassigned"))
		{
			$assigned = mysupport_get_count("assigned");
			if($assigned > 0)
			{
				if($assigned == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				$notice_url = "usercp.php?action=assignedthreads";
				
				if(!empty($fid))
				{
					$assigned_forum = mysupport_get_count("assigned", $fid);
				}
				if($assigned_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->assign_forum, intval($assigned), $threads_text, intval($assigned_forum));
					$notice_url .= "&amp;fid=".$fid;
				}
				else
				{
					$notice_text = $lang->sprintf($lang->assign_global, intval($assigned), $threads_text);
				}
				
				eval("\$mysupport_assign_notice = \"".$templates->get('mysupport_notice')."\";");
			}
		}
	}
}

// show a list of threads requiring technical attention, assigned threads, or support threads
function mysupport_thread_list()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $forum, $headerinclude, $header, $footer, $usercpnav, $modcp_nav, $threads_list, $priorities, $mysupport_priority_classes;
	
	$lang->load("mysupport");
	
	// checks if we're in the Mod CP, technical threads are enabled, and we're viewing the technical threads list...
	// ... or we're in the User CP, the ability to view a list of support threads is enabled, and we're viewing that list
	if((THIS_SCRIPT == "modcp.php" && $mybb->settings['enablemysupporttechnical'] == 1 && $mybb->input['action'] == "technicalthreads") || (THIS_SCRIPT == "usercp.php" && (($mybb->settings['mysupportthreadlist'] == 1 && ($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])) || ($mybb->settings['enablemysupportassign'] == 1 && $mybb->input['action'] == "assignedthreads"))))
	{
		// add to navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			add_breadcrumb($lang->nav_modcp, "modcp.php");
			add_breadcrumb($lang->thread_list_title_tech, "modcp.php?action=technicalthreads");
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			add_breadcrumb($lang->nav_usercp, "usercp.php");
			if($mybb->input['action'] == "assignedthreads")
			{
				add_breadcrumb($lang->thread_list_title_assign, "usercp.php?action=assignedthreads");
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				add_breadcrumb($lang->thread_list_title_solved, "usercp.php?action=supportthreads");
			}
		}
		
		// load the priorities and generate the CSS classes
		mysupport_forumdisplay_searchresults();
		
		// if we have a forum in the URL, we're only dealing with threads in that forum
		// set some stuff for this forum that will be used in various places in this function
		if($mybb->input['fid'])
		{
			$forum_info = get_forum(intval($mybb->input['fid']));
			$list_where_sql = " AND t.fid = ".intval($mybb->input['fid']);
			$stats_where_sql = " AND fid = ".intval($mybb->input['fid']);
			// if we're viewing threads from a specific forum, add that to the nav too
			if(THIS_SCRIPT == "modcp.php")
			{
				add_breadcrumb($lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name'])), "modcp.php?action=technicalthreads&fid={$fid}");
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
				elseif($mybb->input['action'] == "supportthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
			}
		}
		else
		{
			$list_where_sql = "";
			$stats_where_sql = "";
		}
		
		// what forums is this allowed in??
		$mysupport_forums = mysupport_forums();
		$mysupport_forums = implode(",", array_map("intval", $mysupport_forums));
		// if this string isn't empty, generate a variable to go in the query
		if(!empty($mysupport_forums))
		{
			$list_in_sql = " AND t.fid IN (".$db->escape_string($mysupport_forums).")";
			$stats_in_sql = " AND fid IN (".$db->escape_string($mysupport_forums).")";
		}
		else
		{
			$list_in_sql = " AND t.fid IN (0)";
			$stats_in_sql = " AND fid IN (0)";
		}
		
		if($mybb->settings['mysupportstats'] == 1)
		{
			// only want to do this if we're viewing the list of support threads or technical threads
			if((THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "supportthreads") || (THIS_SCRIPT == "modcp.php" && $mybb->input['action'] == "technicalthreads"))
			{
				// show a small stats section
				if(THIS_SCRIPT == "modcp.php")
				{
					$query = $db->simple_select("threads", "status", "1=1{$stats_in_sql}{$stats_where_sql}");
					// 1=1 here because both of these variables could start with AND, so if there's nothing before that, there'll be an SQL error
				}
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$query = $db->simple_select("threads", "status", "uid = '{$mybb->user['uid']}'{$stats_in_sql}{$stats_where_sql}");
				}
				if($db->num_rows($query) > 0)
				{
					$total_count = $solved_count = $notsolved_count = $technical_count = 0;
					while($threads = $db->fetch_array($query))
					{
						switch($threads['status'])
						{
							case 2:
								// we have a technical thread, count it
								++$technical_count;
								break;
							case 1:
								// we have a solved thread, count it
								++$solved_count;
								break;
								// we have an unsolved thread, count it
							default:
								++$notsolved_count;
						}
						// count the total
						++$total_count;
					}
					// if the total count is 0, set all the percentages to 0
					// otherwise we'd get 'division by zero' errors as it would try to divide by zero, and dividing by zero would cause the universe to implode
					if($total_count == 0)
					{
						$solved_percentage = $notsolved_percentage = $technical_percentage = 0;
					}
					// work out the percentages, so we know how big to make each bar
					else
					{
						$solved_percentage = round(($solved_count / $total_count) * 100);
						if($solved_percentage > 0)
						{
							$solved_row = "<td class=\"mysupport_bar_solved\" width=\"{$solved_percentage}%\"></td>";
						}
						
						$notsolved_percentage = round(($notsolved_count / $total_count) * 100);
						if($notsolved_percentage > 0)
						{
							$notsolved_row = "<td class=\"mysupport_bar_notsolved\" width=\"{$notsolved_percentage}%\"></td>";
						}
						
						$technical_percentage = round(($technical_count / $total_count) * 100);
						if($technical_percentage > 0)
						{
							$technical_row = "<td class=\"mysupport_bar_technical\" width=\"{$technical_percentage}%\"></td>";
						}
					}
					
					// get the title for the stats table
					if(THIS_SCRIPT == "modcp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_tech;
						}
					}
					elseif(THIS_SCRIPT == "usercp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_solved;
						}
					}
					
					// fill out the counts of the statuses of threads
					$overview_text = $lang->sprintf($lang->thread_list_stats_overview, $total_count, $solved_count, $notsolved_count, $technical_count);
					
					if(THIS_SCRIPT == "usercp.php")
					{
						$query = $db->simple_select("threads", "COUNT(*) AS newthreads", "lastpost > '".intval($mybb->user['lastvisit'])."' OR statustime > '".intval($mybb->user['lastvisit'])."'");
						$newthreads = $db->fetch_field($query, "newthreads");
						// there's 'new' support threads (reply or action since last visit) so show a link to give a list of just those
						if($newthreads != 0)
						{
							$newthreads_text = $lang->sprintf($lang->thread_list_newthreads, intval($newthreads));
							$newthreads = "<tr><td class=\"trow1\" align=\"center\"><a href=\"{$mybb->settings['bburl']}/usercp.php?action=supportthreads&amp;do=new\">{$newthreads_text}</a></td></tr>";
						}
						else
						{
							$newthreads = "";
						}
					}
					
					eval("\$stats = \"".$templates->get('mysupport_threadlist_stats')."\";");
				}
			}
		}
		
		// now get the relevant threads
		// the query for if we're in the Mod CP, getting all technical threads
		if(THIS_SCRIPT == "modcp.php")
		{
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.priority, f.name
				FROM ".TABLE_PREFIX."threads t
				INNER JOIN ".TABLE_PREFIX."forums f
				ON(t.fid = f.fid AND t.status = '2'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
			");
		}
		// the query for if we're in the User CP, getting all support threads
		elseif(THIS_SCRIPT == "usercp.php")
		{
			$list_limit_sql = "";
			if($mybb->input['action'] == "assignedthreads")
			{
				// viewing assigned threads
				$column = "t.assign";
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				// viewing support threads
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				if($mybb->input['do'] == "new")
				{
					$list_where_sql .= " AND (t.lastpost > '".intval($mybb->user['lastvisit'])."' OR t.statustime > '".intval($mybb->user['lastvisit'])."')";
				}
			}
			else
			{
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				$list_limit_sql = "LIMIT 0, 5";
			}
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.assignuid, t.priority, f.name
				FROM ".TABLE_PREFIX."threads t
				INNER JOIN ".TABLE_PREFIX."forums f
				ON(t.fid = f.fid AND {$column} = '{$mybb->user['uid']}'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
				{$list_limit_sql}
			");
		}
		
		// sort out multipage
		if(!$mybb->settings['postsperpage'])
		{
			$mybb->settings['postperpage'] = 20;
		}
		$perpage = $mybb->settings['postsperpage'];
		if(intval($mybb->input['page']) > 0)
		{
			$page = intval($mybb->input['page']);
			$start = ($page-1) * $perpage;
			$pages = $threadcount / $perpage;
			$pages = ceil($pages);
			if($page > $pages || $page <= 0)
			{
				$start = 0;
				$page = 1;
			}
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		$end = $start + $perpage;
		$lower = $start + 1;
		$upper = $end;
		if($upper > $threadcount)
		{
			$upper = $threadcount;
		}
		
		$threads = "";
		if($db->num_rows($query) == 0)
		{
			$threads = "<tr><td class=\"trow1\" colspan=\"4\" align=\"center\">{$lang->thread_list_no_results}</td></tr>";
		}
		else
		{
			while($thread = $db->fetch_array($query))
			{
				$bgcolor = alt_trow();
				$priority_class = "";
				if($thread['priority'] != 0)
				{
					$priority_class = " class=\"mysupport_priority_".strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']])))."\"";
				}
				
				$thread['subject'] = htmlspecialchars_uni($thread['subject']);
				$thread['threadlink'] = get_thread_link($thread['tid']);
				$thread['forumlink'] = "<a href=\"".get_forum_link($thread['fid'])."\">".htmlspecialchars_uni($thread['name'])."</a>";
				$thread['profilelink'] = build_profile_link(htmlspecialchars_uni($thread['username']), intval($thread['uid']));
				
				$status_time_date = my_date($mybb->settings['dateformat'], intval($thread['statustime']));
				$status_time_time = my_date($mybb->settings['timeformat'], intval($thread['statustime']));
				// if we're in the Mod CP we only need the date and time it was marked technical, don't need the status on every line
				if(THIS_SCRIPT == "modcp.php")
				{
					if($mybb->settings['mysupportrelativetime'] == 1)
					{
						$status_time = mysupport_relative_time($thread['statustime']);
					}
					else
					{
						$status_time = $status_time_date." ".$status_time_time;
					}
					// we're viewing technical threads, show who marked it as technical
					$status_uid = intval($thread['statusuid']);
					$status_user = get_user($status_uid);
					$status_username = $status_user['username'];
					$status_user_link = build_profile_link(htmlspecialchars_uni($status_username), intval($status_uid));
					$status_time .= ", ".$lang->sprintf($lang->mysupport_by, $status_user_link);
					
					$view_all_forum_text = $lang->sprintf($lang->thread_list_link_tech, htmlspecialchars_uni($thread['name']));
					$view_all_forum_link = "modcp.php?action=technicalthreads&amp;fid=".intval($thread['fid']);
				}
				// if we're in the User CP we want to get the status...
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$status = mysupport_get_friendly_status(intval($thread['status']));
					switch($thread['status'])
					{
						case 2:
							$class = "technical";
							break;
						case 1:
							$class = "solved";
							break;
						default:
							$class = "notsolved";
					}
					$status = "<span class=\"mysupport_status_{$class}\">".htmlspecialchars_uni($status)."</span>";
					// ... but we only want to show the time if the status is something other than Not Solved...
					if($thread['status'] != 0)
					{
						if($mybb->settings['mysupportrelativetime'] == 1)
						{
							$status_time = $status." - ".mysupport_relative_time($thread['statustime']);
						}
						else
						{
							$status_time = $status." - ".$status_time_date." ".$status_time_time;
						}
					}
					// ... otherwise, if it is not solved, just show that
					else
					{
						$status_time = $status;
					}
					//if(!($mybb->input['action'] == "supportthreads" && $thread['status'] == 0))
					// we wouldn't want to do this if a thread was unsolved
					if((($mybb->input['action'] == "supportthreads" || !$mybb->input['action']) && $thread['status'] != 0) || $mybb->input['action'] == "assignedthreads")
					{
						if($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])
						{
							// we're viewing support threads, show who marked it as solved or technical
							$status_uid = intval($thread['statusuid']);
							$by_lang = "mysupport_by";
						}
						else
						{
							// we're viewing assigned threads, show who assigned this thread to you
							$status_uid = intval($thread['assignuid']);
							$by_lang = "mysupport_assigned_by";
						}
						if($status_uid)
						{
							$status_user = get_user($status_uid);
							$status_user_link = build_profile_link(htmlspecialchars_uni($status_user['username']), intval($status_uid));
							$status_time .= ", ".$lang->sprintf($lang->$by_lang, $status_user_link);
						}
					}
					
					if($mybb->input['action'] == "assignedthreads")
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_assign, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=assignedthreads&amp;fid=".intval($thread['fid']);
					}
					else
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_solved, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=supportthreads&amp;fid=".intval($thread['fid']);
					}
				}
				
				$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");
				$lastpostdate = my_date($mybb->settings['dateformat'], intval($thread['lastpost']));
				$lastposttime = my_date($mybb->settings['timeformat'], intval($thread['lastpost']));
				$lastposterlink = build_profile_link(htmlspecialchars_uni($thread['lastposter']), intval($thread['lastposteruid']));
				
				eval("\$threads .= \"".$templates->get("mysupport_threadlist_thread")."\";");
			}
		}
		
		// if we have a forum in the URL, add a table footer with a link to all the threads
		if($mybb->input['fid'] || (THIS_SCRIPT == "usercp.php" && !$mybb->input['action']))
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
				$view_all = $lang->thread_list_view_all_tech;
				$view_all_url = "modcp.php?action=technicalthreads";
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name']));
					$view_all = $lang->thread_list_view_all_assign;
					$view_all_url = "usercp.php?action=assignedthreads";
				}
				else
				{
					if($mybb->input['action'] == "supportthreads")
					{
						$thread_list_heading = $lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
					}
					elseif(!$mybb->input['action'])
					{
						$thread_list_heading = $lang->thread_list_heading_solved_latest;
					}
					$view_all = $lang->thread_list_view_all_solved;
					$view_all_url = "usercp.php?action=supportthreads";
				}
			}
			eval("\$view_all = \"".$templates->get("mysupport_threadlist_footer")."\";");
		}
		// if there's no forum in the URL, just get the standard table heading
		else
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->thread_list_heading_tech;
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->thread_list_heading_assign;
				}
				else
				{
					if($mybb->input['do'] == "new")
					{
						$thread_list_heading = $lang->thread_list_heading_solved_new;
					}
					else
					{
						$thread_list_heading = $lang->thread_list_heading_solved;
					}
				}
			}
		}
		
		//get the page title, heading for the status of the thread column, and the relevant sidebar navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			$thread_list_title = $lang->thread_list_title_tech;
			$status_heading = $lang->thread_list_time_tech;
			$navigation = "$modcp_nav";
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			if($mybb->input['action'] == "assignedthreads")
			{
				$thread_list_title = $lang->thread_list_title_assign;
				$status_heading = $lang->thread_list_time_solved;
			}
			else
			{
				$thread_list_title = $lang->thread_list_title_solved;
				$status_heading = $lang->thread_list_time_assign;
			}
			$navigation = "$usercpnav";
		}
		
		$threadlist_filter_form = "";
		$threadlist_filter_form .= "<form action=\"".THIS_SCRIPT."?action=".$mybb->input['action']."\" method=\"get\">";
		$threadlist_filter_form .= $lang->filter_by;
		$threadlist_filter_form .= "<option value=\"0\">".$lang->status."</option>";
		$threadlist_filter_form .= "<option value=\"-1\">".$lang->not_solved."</option>";
		$threadlist_filter_form .= "<option value=\"1\">".$lang->solved."</option>";
		//if
		$threadlist_filter_form .= "</form>";
		
		eval("\$threads_list = \"".$templates->get("mysupport_threadlist_list")."\";");
		// we only want to output the page if we've got an action; i.e. we're not viewing the list on the User CP home page
		if($mybb->input['action'])
		{
			eval("\$threads_page = \"".$templates->get("mysupport_threadlist")."\";");
			output_page($threads_page);
		}
	}
}

function mysupport_modcp_support_denial()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $headerinclude, $header, $footer, $modcp_nav, $mod_log_action, $redirect;
	
	$lang->load("mysupport");
	
	if($mybb->input['action'] == "supportdenial")
	{
		if(!mysupport_usergroup("canmanagesupportdenial"))
		{
			error_no_permission();
		}
		
		add_breadcrumb($lang->nav_modcp, "modcp.php");
		add_breadcrumb($lang->support_denial, "modcp.php?action=supportdenial");
		
		if($mybb->input['do'] == "do_denysupport")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			if($mybb->settings['enablemysupportsupportdenial'] != 1)
			{
				mysupport_error($lang->support_denial_not_enabled);
				exit;
			}
			
			// get username from UID
			// this is if we're revoking via the list of denied users, we specify a UID here
			if($mybb->input['uid'])
			{
				$uid = intval($mybb->input['uid']);
				$user = get_user($uid);
				$username = $user['username'];
			}
			// get UID from username
			// this is if we're denying support via the form, where we give a username
			elseif($mybb->input['username'])
			{
				$username = $db->escape_string($mybb->input['username']);
				$query = $db->simple_select("users", "uid", "username = '{$username}'");
				$uid = $db->fetch_field($query, "uid");
			}
			if(!$uid || !$username)
			{
				mysupport_error($lang->support_denial_reason_invalid_user);
				exit;
			}
			
			if(isset($mybb->input['deniedsupportreason']))
			{
				$deniedsupportreason = intval($mybb->input['deniedsupportreason']);
			}
			else
			{
				$deniedsupportreason = 0;
			}
			
			if($mybb->input['tid'] != 0)
			{
				$tid = intval($mybb->input['tid']);
				$thread_info = get_thread($tid);
				$fid = $thread_info['fid'];
				
				$redirect_url = get_thread_link($tid);
			}
			else
			{
				$redirect_url = "modcp.php?action=supportdenial";
			}
			
			$mod_log_action = "";
			$redirect = "";
			
			$mysupport_cache = $cache->read("mysupport");
			// -1 is if we're revoking and 0 is no reason, so those are exempt
			if(!array_key_exists($deniedsupportreason, $mysupport_cache['deniedreasons']) && $deniedsupportreason != -1 && $deniedsupportreason != 0)
			{
				mysupport_error($lang->support_denial_reason_invalid_reason);
				exit;
			}
			elseif($deniedsupportreason == -1)
			{
				$update = array(
					"deniedsupport" => 0,
					"deniedsupportreason" => 0,
					"deniedsupportuid" => 0
				);
				$db->update_query("users", $update, "uid = '".intval($uid)."'");
				
				$update = array(
					"closed" => 0,
					"closedbymysupport" => 0
				);
				$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
				$db->update_query("threads", $update, "uid = '".intval($uid)."' AND fid IN (".$db->escape_string($mysupport_forums).") AND closed = '1' AND closedbymysupport = '2'");
				
				mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_revoke_mod_log, $username));
				mysupport_redirect_message($lang->sprintf($lang->deny_support_revoke_success, htmlspecialchars_uni($username)));
			}
			else
			{
				$update = array(
					"deniedsupport" => 1,
					"deniedsupportreason" => intval($deniedsupportreason),
					"deniedsupportuid" => intval($mybb->user['uid'])
				);
				$db->update_query("users", $update, "uid = '".intval($uid)."'");
				
				if($mybb->settings['mysupportclosewhendenied'] == 1)
				{
					$update = array(
						"closed" => 1,
						"closedbymysupport" => 2
					);
					$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
					
					$db->update_query("threads", $update, "uid = '".intval($uid)."' AND fid IN (".$db->escape_string($mysupport_forums).") AND closed = '0'");
				}
				
				if($deniedsupportreason != 0)
				{
					$deniedsupportreason = $db->fetch_field($query, "name");
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log_reason, $username, $deniedsupportreason));
				}
				else
				{
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log, $username));
				}
				mysupport_redirect_message($lang->sprintf($lang->deny_support_success, htmlspecialchars_uni($username)));
			}
			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			redirect($redirect_url, $redirect);
		}
		elseif($mybb->input['do'] == "denysupport")
		{
			if($mybb->settings['enablemysupportsupportdenial'] != 1)
			{
				mysupport_error($lang->support_denial_not_enabled);
				exit;
			}
			
			$uid = intval($mybb->input['uid']);
			$tid = intval($mybb->input['tid']);
			
			$user = get_user($uid);
			$username = $user['username'];
			$user_link = build_profile_link(htmlspecialchars_uni($username), intval($uid), "blank");
			
			if($mybb->input['uid'])
			{
				$deny_support_to = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($username));
			}
			else
			{
				$deny_support_to = $lang->deny_support_to_user;
			}
			
			add_breadcrumb($deny_support_to);
			
			$deniedreasons = "";
			$deniedreasons .= "<label for=\"deniedsupportreason\">{$lang->reason}:</label> <select name=\"deniedsupportreason\" id=\"deniedsupportreason\">\n";
			// if they've not been denied support yet or no reason was given, show an empty option that will be selected
			if($user['deniedsupport'] == 0 || $user['deniedsupportreason'] == 0)
			{
				$deniedreasons .= "<option value=\"0\"></option>\n";
			}
			
			$mysupport_cache = $cache->read("mysupport");
			if(!empty($mysupport_cache['deniedreasons']))
			{
				// if there's one or more reasons set, show them in a dropdown
				foreach($mysupport_cache['deniedreasons'] as $deniedreasons)
				{
					$selected = "";
					// if a reason has been given, we'd be editing it, so this would select the current one
					if($user['deniedsupport'] == 1 && $user['deniedsupportreason'] == $deniedreason['mid'])
					{
						$selected = " selected=\"selected\"";
					}
					$deniedreasons .= "<option value=\"".intval($deniedreason['mid'])."\"{$selected}>".htmlspecialchars_uni($deniedreason['name'])."</option>\n";
				}
			}
			$deniedreasons .= "<option value=\"0\">{$lang->support_denial_reasons_none}</option>\n";
			// if they've been denied support, give an option to revoke it
			if($user['deniedsupport'] == 1)
			{
				$deniedreasons .= "<option value=\"0\">-----</option>\n";
				$deniedreasons .= "<option value=\"-1\">{$lang->revoke}</option>\n";
			}
			$deniedreasons .= "</select>\n";
			
			eval("\$deny_support = \"".$templates->get('mysupport_deny_support_deny')."\";");
			eval("\$deny_support_page = \"".$templates->get('mysupport_deny_support')."\";");
			output_page($deny_support_page);
		}
		else
		{
			$query = $db->write_query("
				SELECT u1.username AS support_denied_username, u1.uid AS support_denied_uid, u2.username AS support_denier_username, u2.uid AS support_denier_uid, m.name AS support_denied_reason
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN ".TABLE_PREFIX."mysupport m ON (u.deniedsupportreason = m.mid)
				LEFT JOIN ".TABLE_PREFIX."users u1 ON (u1.uid = u.uid)
				LEFT JOIN ".TABLE_PREFIX."users u2 ON (u2.uid = u.deniedsupportuid)
				WHERE u.deniedsupport = '1'
				ORDER BY u1.username ASC
			");
			
			if($db->num_rows($query) > 0)
			{
				while($denieduser = $db->fetch_array($query))
				{
					$bgcolor = alt_trow();
					
					$support_denied_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denied_username']), intval($denieduser['support_denied_uid']));
					$support_denier_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denier_username']), intval($denieduser['support_denier_uid']));
					if(empty($denieduser['support_denied_reason']))
					{
						$support_denial_reason = $lang->support_denial_no_reason;
					}
					else
					{
						$support_denial_reason = $denieduser['support_denied_reason'];
					}
					eval("\$denied_users .= \"".$templates->get('mysupport_deny_support_list_user')."\";");
				}
			}
			else
			{
				$denied_users = "<tr><td class=\"trow1\" align=\"center\" colspan=\"5\">{$lang->support_denial_no_users}</td></tr>";
			}
			
			eval("\$deny_support = \"".$templates->get('mysupport_deny_support_list')."\";");
			eval("\$deny_support_page = \"".$templates->get('mysupport_deny_support')."\";");
			output_page($deny_support_page);
		}
	}
}

function mysupport_usercp_options()
{
	global $mybb, $db, $lang, $templates, $mysupport_usercp_options;
	
	if($mybb->settings['mysupportdisplaytypeuserchange'] == 1)
	{
		if($mybb->input['action'] == "do_options")
		{
			$update = array(
				"mysupportdisplayastext" => intval($mybb->input['mysupportdisplayastext'])
			);
			
			$db->update_query("users", $update, "uid = '".intval($mybb->user['uid'])."'");
		}
		elseif($mybb->input['action'] == "options")
		{
			$lang->load("mysupport");
			
			if($mybb->settings['enablemysupport'] == 1)
			{
				$mysupportdisplayastextcheck = "";
				if($mybb->user['mysupportdisplayastext'] == 1)
				{
					$mysupportdisplayastextcheck = " checked=\"checked\"";
				}
			}
			
			eval("\$mysupport_usercp_options = \"".$templates->get('mysupport_usercp_options')."\";");
		}
	}
}

function mysupport_navoption()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		global $usercpnav, $modcp_nav;
		
		// if MySupport is turned off, we need to replace these with nothing otherwise they'll show up in the menu
		$modcp_nav = str_replace("{mysupport_nav_option}", "", $modcp_nav);
		$usercpnav = str_replace("{mysupport_nav_option}", "", $usercpnav);
		
		return;
	}
	
	global $lang, $templates, $usercpnav, $modcp_nav, $mysupport_nav_option;
	
	$lang->load("mysupport");
	
	if(THIS_SCRIPT == "modcp.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the technical threads feature enabled??
		if($mybb->settings['enablemysupporttechnical'] == 1)
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_tech_threads";
			$nav_link = "modcp.php?action=technicalthreads";
			$nav_text = $lang->thread_list_title_tech;
			// we need to eval this template now to generate the nav row with the correct details in it
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		// is support denial enabled??
		if($mybb->settings['enablemysupportsupportdenial'] == 1)
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_deny_support";
			$nav_link = "modcp.php?action=supportdenial";
			$nav_text = $lang->support_denial;
			// we need to eval this template now to generate the nav row with the correct details in it
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		
		if($something_to_show)
		{
			// do a str_replace on the nav to display it; need to do a string replace as the hook we're using here is after $modcp_nav has been eval'd
			$modcp_nav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $modcp_nav);
		}
		else
		{
			// if the technical threads or support denial feature isn't enabled, replace the code in the template with nothing
			$modcp_nav = str_replace("{mysupport_nav_option}", "", $modcp_nav);
		}
	}
	// need to check for private.php too so it shows in the PM system - the usercp_menu_built hook is run after $mysupport_nav_option has been made so this will work for both
	elseif(THIS_SCRIPT == "usercp.php" || THIS_SCRIPT == "usercp2.php" || THIS_SCRIPT == "private.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the list of support threads enabled??
		if($mybb->settings['mysupportthreadlist'] == 1)
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_support_threads";
			$nav_link = "usercp.php?action=supportthreads";
			$nav_text = $lang->thread_list_title_solved;
			// add to the code for the option
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		// is assigning threads enabled??
		if($mybb->settings['enablemysupportassign'] == 1 && mysupport_usergroup("canbeassigned"))
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_assigned_threads";
			$nav_link = "usercp.php?action=assignedthreads";
			$nav_text = $lang->thread_list_title_assign;
			// add to the code for the option
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		
		if($something_to_show)
		{
			// if we added either or both of the nav options above, do a str_replace on the nav to display it
			// need to do a string replace as the hook we're using here is after $usercpnav has been eval'd
			$usercpnav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $usercpnav);
		}
		else
		{
			// if we didn't add either of the nav options above, replace the code in the template with nothing
			$usercpnav = str_replace("{mysupport_nav_option}", "", $usercpnav);
		}
	}
}

function mysupport_friendly_wol(&$user_activity)
{
	global $user;
	
	if(my_strpos($user['location'], "modcp.php?action=technicalthreads") !== false)
	{
		$user_activity['activity'] = "modcp_techthreads";
	}
	elseif(my_strpos($user['location'], "usercp.php?action=supportthreads") !== false)
	{
		$user_activity['activity'] = "usercp_supportthreads";
	}
	elseif(my_strpos($user['location'], "modcp.php?action=supportdenial") !== false)
	{
		if(my_strpos($user['location'], "do=denysupport") !== false || my_strpos($user['location'], "do=do_denysupport") !== false)
		{
			$user_activity['activity'] = "modcp_supportdenial_deny";
		}
		else
		{
			$user_activity['activity'] = "modcp_supportdenial";
		}
	}
}

function mysupport_build_wol(&$plugin_array)
{
	global $lang;
	
	if($plugin_array['user_activity']['activity'] == "modcp_techthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_technical;
	}
	elseif($plugin_array['user_activity']['activity'] == "usercp_supportthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial_deny")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial_deny;
	}
}

function mysupport_settings_footer()
{
	global $mybb, $db;
	// we're viewing the form to change settings but not submitting it
	if($mybb->input["action"] == "change" && $mybb->request_method != "post")
	{
		$gid = mysupport_settings_gid();
		// if the settings group we're editing is the same as the gid for the MySupport group, or there's no gid (viewing all settings), echo the peekers
		if($mybb->input["gid"] == $gid || !$mybb->input['gid'])
		{
			echo '<script type="text/javascript">
	Event.observe(window, "load", function() {
	loadMySupportPeekers();
});
function loadMySupportPeekers()
{
	new Peeker($$(".setting_enablemysupporttechnical"), $("row_setting_mysupporthidetechnical"), /1/, true);
	new Peeker($$(".setting_enablemysupporttechnical"), $("row_setting_mysupporttechnicalnotice"), /1/, true);
	new Peeker($$(".setting_enablemysupportassign"), $("row_setting_mysupportassignpm"), /1/, true);
	new Peeker($$(".setting_enablemysupportassign"), $("row_setting_mysupportassignsubscribe"), /1/, true);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportpointssystemname"), /other/, false);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportpointssystemcolumn"), /other/, false);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportbestanswerpoints"), /[^none]/, false);
}
</script>';
		}
	}
}

function mysupport_admin_config_menu($sub_menu)
{
	global $lang;
	
	$lang->load("config_mysupport");
	
	$sub_menu[] = array("id" => "mysupport", "title" => $lang->mysupport, "link" => "index.php?module=config-mysupport");
	
	return $sub_menu;
}

function mysupport_admin_config_action_handler($actions)
{
	$actions['mysupport'] = array(
		"active" => "mysupport",
		"file" => "mysupport.php"
	);
	
	return $actions;
}

function mysupport_admin_config_permissions($admin_permissions)
{
	global $lang;
	
	$lang->load("config_mysupport");
	
	$admin_permissions['mysupport'] = $lang->can_manage_mysupport;
	
	return $admin_permissions;
}

// general functions

/**
 * Check is MySupport is enabled in this forum.
 *
 * @param int The FID of the thread.
 * @param bool Whether or not this is a MySupport forum.
**/
function mysupport_forum($fid)
{
	global $cache;
	
	$fid = intval($fid);
	$forum_info = get_forum($fid);
	
	// the parent list includes the ID of the forum itself so this will quickly check the forum and all it's parents
	// only slight issue is that the ID of this forum would be at the end of this list, so it'd check the parents first, but if it returns true, it returns true, doesn't really matter
	$forum_ids = explode(",", $forum_info['parentlist']);
	
	// load the forums cache
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		// if this forum is in the parent list
		if(in_array($forum['fid'], $forum_ids))
		{
			// if this is a MySupport forum, return true
			if($forum['mysupport'] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Generates a list of all forums that have MySupport enabled.
 *
 * @param array Array of forums that have MySupport enabled.
**/
function mysupport_forums()
{
	global $cache;
	
	$forums = $cache->read("forums");
	$mysupport_forums = array();
	
	foreach($forums as $forum)
	{
		// if this forum/category has MySupport enabled, add it to the array
		if($forum['mysupport'] == 1)
		{
			if(!in_array($forum['fid'], $mysupport_forums))
			{
				$mysupport_forums[] = $forum['fid'];
			}
		}
		// if this forum/category hasn't got MySupport enabled...
		else
		{
			// ... go through the parent list...
			$parentlist = explode(",", $forum['parentlist']);
			foreach($parentlist as $parent)
			{
				// ... if this parent has MySupport enabled...
				if($forums[$parent]['mysupport'] == 1)
				{
					// ... add the original forum we're looking at to the list
					if(!in_array($forum['fid'], $mysupport_forums))
					{
						$mysupport_forums[] = $forum['fid'];
						continue;
					}
					// this is for if we enable MySupport for a whole category; this will pick up all the forums inside that category and add them to the array
				}
			}
		}
	}
	
	return $mysupport_forums;
}

/**
 * Check the usergroups for MySupport permissions.
 *
 * @param string What permission we're checking.
 * @param int Usergroup of the user we're checking.
**/
function mysupport_usergroup($perm, $usergroups = array())
{
	global $mybb, $cache;
	
	// does this key even exist?? Check here if it does
	if(!array_key_exists($perm, $mybb->usergroup))
	{
		return false;
	}
	
	// if no usergroups are specified, we're checking our own usergroups
	if(empty($usergroups))
	{
		$usergroups = array_merge(array($mybb->user['usergroup']), explode(",", $mybb->user['additionalgroups']));
	}
	
	// load the usergroups cache
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		// if this user is in this group
		if(in_array($group['gid'], $usergroups))
		{
			// if this group can perform this action, return true
			if($group[$perm] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Shows an error with a header indicating it's been created by MySupport, to save having to put a header into every call of error()
 *
 * @param string The error to show.
**/
function mysupport_error($error)
{
	global $lang;
	
	$lang->load("mysupport");
	
	error($error, $lang->mysupport_error);
	exit;
}

/**
 * Change the status of a thread.
 *
 * @param array Information about the thread.
 * @param int The new status.
 * @param bool If this is changing the status of multiple threads.
**/
function mysupport_change_status($thread_info, $status = 0, $multiple = false)
{
	global $mybb, $db, $lang, $cache;
	
	$status = intval($status);
	if($status == 3)
	{
		// if it's 3, we're solving and closing, but we'll just check for regular solving in the list of things to log
		// saves needing to have a 3, for the solving and closing option, in the setting of what to log
		// then below it'll check if 1 is in the list of things to log; 1 is normal solving, so if that's in the list, it'll log this too
		$log_status = 1;
	}
	else
	{
		$log_status = $status;
	}
	
	if($multiple)
	{
		$tid = -1;
		$old_status = -1;
	}
	else
	{
		$tid = intval($thread_info['tid']);
		$old_status = intval($thread_info['status']);
	}
	
	$move_fid = "";
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		if(!empty($forum['mysupportmove']) && $forum['mysupportmove'] != 0)
		{
			$move_fid = intval($forum['fid']);
			break;
		}
	}
	// are we marking it as solved and is it being moved??
	if(!empty($move_fid) && ($status == 1 || $status == 3))
	{
		if($mybb->settings['mysupportmoveredirect'] == "none")
		{
			$move_type = "move";
			$redirect_time = 0;
		}
		else
		{
			$move_type = "redirect";
			if($mybb->settings['mysupportmoveredirect'] == "forever")
			{
				$redirect_time = 0;
			}
			else
			{
				$redirect_time = intval($mybb->settings['mysupportmoveredirect']);
			}
		}
		if($multiple)
		{
			$move_tids = $thread_info;
		}
		else
		{
			$move_tids = array($thread_info['tid']);
		}
		require_once MYBB_ROOT."inc/class_moderation.php";
		$moderation = new Moderation;
		// the reason it loops through using move_thread is because move_threads doesn't give the option for a redirect
		// if it's not a multiple thread it will just loop through once as there'd only be one value in the array
		foreach($move_tids as $move_tid)
		{
			$moderation->move_thread($move_tid, $move_fid, $move_type, $redirect_time);
		}
	}
	
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	// we need to build an array of users who have been assigned threads before the assignment is removed
	if($status == 1 || $status == 3)
	{
		$query = $db->simple_select("threads", "DISTINCT assign", $where_sql." AND assign != '0'");
		$assign_users = array();
		while($user = $db->fetch_field($query, "assign"))
		{
			$assign_users[] = $user;
		}
	}
	
	if($status == 3 || ($status == 1 && $mybb->settings['mysupportclosewhensolved'] == "always"))
	{
		// the bit after || here is for if we're marking as solved via marking a post as the best answer, it will close if it's set to always close
		// the incoming status would be 1 but we need to close it if necessary
		$status_update = array(
			"closed" => 1,
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0,
			"closedbymysupport" => 1,
			"onhold" => 0
		);
	}
	elseif($status == 0)
	{
		// if we're marking it as unsolved, a post may have been marked as the best answer when it was originally solved, best remove it, as well as rest everything else
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0,
			"bestanswer" => 0
		);
	}
	elseif($status == 4)
	{
		/** if it's 4, it's because it was marked as being not technical after being marked technical
		 ** basically put back to the original status of not solved (0)
		 ** however it needs to be 4 so we can differentiate between this action (technical => not technical), and a user marking it as not solved
		 ** because both of these options eventually set it back to 0
		 ** so the mod log entry will say the correct action as the status was 4 and it used that
		 ** now that the log has been inserted we can set it to 0 again for the thread update query so it's marked as unsolved **/
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0
		);
	}
	elseif($status == 2)
	{
		$status_update = array(
			"status" => 2,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW
		);
	}
	// if not, it's being marked as solved
	else
	{
		$status_update = array(
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0,
			"onhold" => 0
		);
	}
	
	$db->update_query("threads", $status_update, $where_sql);
	
	// if the thread is being marked as technical, being marked as something else after being marked technical, or we're changing the status of multiple threads, recount the number of technical threads
	if($status == 2 || $old_status == 2 || $multiple)
	{
		mysupport_recount_technical_threads();
	}
	// if the thread is being marked as solved, recount the number of assigned threads for any users who were assigned threads that are now being marked as solved
	if($status == 1 || $status == 3)
	{
		foreach($assign_users as $user)
		{
			mysupport_recount_assigned_threads($user);
		}
	}
	if($status == 0)
	{
		// if we're marking a thread(s) as unsolved, re-open any threads that were closed when they were marked as solved, but not any that were closed by denying support
		$update = array(
			"closed" => 0,
			"closedbymysupport" => 0
		);
		$db->update_query("threads", $update, $where_sql." AND closed = '1' AND closedbymysupport = '1'");
	}
	
	// get the friendly version of the status for the redirect message and mod log
	$friendly_old_status = "'".mysupport_get_friendly_status($old_status)."'";
	$friendly_new_status = "'".mysupport_get_friendly_status($status)."'";
	
	if($multiple)
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log_multi, count($thread_info), $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success_multi, count($thread_info), htmlspecialchars_uni($friendly_new_status)));
	}
	else
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log, $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success, htmlspecialchars_uni($friendly_old_status), htmlspecialchars_uni($friendly_new_status)));
	}
}

/**
 * Change the hold status of a thread.
 *
 * @param array Information about the thread.
 * @param int The new hold status.
 * @param bool If this is changing the hold status of multiple threads.
**/
function mysupport_change_hold($thread_info, $onhold = 0, $multiple = false)
{
	global $db, $cache, $lang;
	
	$tid = intval($thread_info['tid']);
	$onhold = intval($onhold);
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	if($onhold == 0)
	{
		$update = array(
			"onhold" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(12, $lang->sprintf($lang->hold_off_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->hold_off_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(12, $lang->hold_off_success);
			mysupport_redirect_message($lang->hold_off_success);
		}
	}
	else
	{
		$update = array(
			"onhold" => 1
		);
		if($multiple)
		{
			// when changing the hold status via the form in a thread, you can't you can't change the hold status if the thread's solved
			// here, it's not as easy to check for that; instead, only change the hold status if the thread isn't solved
			$where_sql .= " AND status != '1'";
		}
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(12, $lang->sprintf($lang->hold_on_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->hold_on_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(12, $lang->hold_on_success);
			mysupport_redirect_message($lang->hold_on_success);
		}
	}
}

/**
 * Change who a thread is assigned to.
 *
 * @param array Information about the thread.
 * @param int The UID of who we're assigning it to now.
 * @param bool If this is changing the assigned user of multiple threads.
**/
function mysupport_change_assign($thread_info, $assign, $multiple = false)
{
	global $mybb, $db, $lang;
	
	if($multiple)
	{
		$fid = -1;
		$tid = -1;
		$old_assign = -1;
	}
	else
	{
		$fid = intval($thread_info['fid']);
		$tid = intval($thread_info['tid']);
		$old_assign = intval($thread_info['assign']);
	}
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	// because we can assign a thread to somebody if it's already assigned to somebody else, we need to get a list of all the users who have been assigned the threads we're dealing with, so we can recount the number of assigned threads for all these users after the assignment has been chnaged
	$query = $db->simple_select("threads", "DISTINCT assign", $where_sql." AND assign != '0'");
	$assign_users = array(
		$assign => $assign
	);
	while($user = $db->fetch_field($query, "assign"))
	{
		$assign_users[$user] = $user;
	}
	
	// if we're unassigning it
	if($assign == "-1")
	{
		$update = array(
			"assign" => 0,
			"assignuid" => 0
		);
		// remove the assignment on the thread
		$db->update_query("threads", $update, $where_sql);
		
		// get information on who it was assigned to
		$user = get_user($old_assign);
		
		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success, $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success, htmlspecialchars_uni($user['username'])));
		}
	}
	// if we're assigning it or changing the assignment
	else
	{
		$update = array(
			"assign" => intval($assign),
			"assignuid" => intval($mybb->user['uid'])
		);
		if($multiple)
		{
			// when assigning via the form in a thread, you can't assign a thread if it's solved
			// here, it's not as easy to check for that; instead, only assign a thread if it isn't solved
			$where_sql .= " AND status != '1'";
		}
		// assign the thread
		$db->update_query("threads", $update, $where_sql);
		
		$user = get_user($assign);
		$username = $db->escape_string($user['username']);
		
		if($mybb->settings['mysupportassignpm'] == 1)
		{
			// send the PM
			mysupport_send_assign_pm($assign, $fid, $tid);
		}
		
		if($mybb->settings['mysupportassignsubscribe'] == 1)
		{
			if($multiple)
			{
				$tids = $thread_info;
			}
			else
			{
				$tids = array($thread_info['tid']);
			}
			foreach($tids as $tid)
			{
				$query = $db->simple_select("threadsubscriptions", "*", "uid = '{$assign}' AND tid = '{$tid}'");
				// only do this if they're not already subscribed
				if($db->num_rows($query) == 0)
				{
					if($user['subscriptionmethod'] == 2)
					{
						$subscription_method = 2;
					}
					// this is if their subscription method is 1 OR 0
					// done like this because this setting forces a subscription, but we'll only subscribe them via email if the user wants it
					else
					{
						$subscription_method = 1;
					}
					require_once MYBB_ROOT."inc/functions_user.php";
					add_subscribed_thread($tid, $subscription_method, $assign);
				}
			}
		}
		
		if($multiple)
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success_multi, count($thread_info), $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success_multi, count($thread_info), htmlspecialchars_uni($user['username'])));
		}
		else
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success, $username));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success, htmlspecialchars_uni($username)));
		}
	}
	
	foreach($assign_users as $user)
	{
		mysupport_recount_assigned_threads($user);
	}
}

/**
 * Change the priority of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new priority.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_priority($thread_info, $priority, $multiple = false)
{
	global $db, $cache, $lang;
	
	$tid = intval($thread_info['tid']);
	$priority = $db->escape_string($priority);
	
	$mysupport_cache = $cache->read("mysupport");
	$priorities = array();
	if(!empty($mysupport_cache['priorities']))
	{
		foreach($mysupport_cache['priorities'] as $priority_info)
		{
			$priorities[$priority_info['mid']] = $priority_info['name'];
		}
	}
	
	$new_priority = $priorities[$priority];
	$old_priority = $priorities[$thread_info['priority']];
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	if($priority == "-1")
	{
		$update = array(
			"priority" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success, $old_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success, htmlspecialchars_uni($old_priority)));
		}
	}
	else
	{
		$update = array(
			"priority" => intval($priority)
		);
		if($multiple)
		{
			// when setting a priority via the form in a thread, you can't give a thread a priority if it's solved
			// here, it's not as easy to check for that; instead, only set the priority if the thread isn't solved
			$where_sql .= " AND status != '1'";
		}
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
		}
		else
		{
			if($thread['priority'] == 0)
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_to, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to, htmlspecialchars_uni($new_priority)));
			}
			else
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_fromto, $old_priority, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_fromto, htmlspecialchars_uni($old_priority), htmlspecialchars_uni($new_priority)));
			}
		}
	}
}

/**
 * Change the category of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new category.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_category($thread_info, $category, $multiple = false)
{
	global $db, $lang;
	
	$tid = intval($thread_info['tid']);
	$category = $db->escape_string($category);
	
	$query = $db->simple_select("threadprefixes", "pid, prefix");
	$categories = array();
	while($category_info = $db->fetch_array($query))
	{
		$categories[$category_info['pid']] = htmlspecialchars_uni($category_info['prefix']);
	}
	
	$new_category = $categories[$category];
	$old_category = $categories[$thread_info['prefix']];
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	if($category == "-1")
	{
		$update = array(
			"prefix" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success, $old_category));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success, htmlspecialchars_uni($old_category)));
		}
	}
	else
	{
		$update = array(
			"prefix" => $category
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to_multi, count($thread_info), $new_category));
			mysupport_redirect_message($lang->sprintf($lang->category_change_success_to_multi, count($thread_info), htmlspecialchars_uni($new_category)));
		}
		else
		{
			if($thread['prefix'] == 0)
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_to, htmlspecialchars_uni($new_category)));
			}
			else
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_fromto, $old_category, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_fromto, htmlspecialchars_uni($old_category), htmlspecialchars_uni($new_category)));
			}
		}
	}
}

/**
 * Change whether or not a thread is a support thread
 *
 * @param array Information about the thread.
 * @param int If this thread is a support thread or not (1/0)
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_issupportthread($thread_info, $issupportthread, $multiple = false)
{
	global $db, $lang;
	
	$tid = intval($thread_info['tid']);
	$issupportthread = intval($issupportthread);
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (".$db->escape_string($tids).")";
	}
	else
	{
		$where_sql = "tid = '".intval($tid)."'";
	}
	
	if($issupportthread == 1)
	{
		$update = array(
			"issupportthread" => 1
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(13, $lang->sprintf($lang->issupportthread_1_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->issupportthread_1_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(13, $lang->issupportthread_1);
			mysupport_redirect_message($lang->issupportthread_1);
		}
	}
	else
	{
		$update = array(
			"issupportthread" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(13, $lang->sprintf($lang->issupportthread_0_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->issupportthread_0_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(13, $lang->issupportthread_0);
			mysupport_redirect_message($lang->issupportthread_0);
		}
	}
}

/**
 * Add to the moderator log message.
 *
 * @param int The ID of the log action.
 * @param string The message to add.
**/
function mysupport_mod_log_action($id, $message)
{
	global $mybb, $mod_log_action;
	
	$id = intval($id);
	$mysupportmodlog = explode(",", $mybb->settings['mysupportmodlog']);
	// if this action shouldn't be logged, return false
	if(!in_array($id, $mysupportmodlog))
	{
		return false;
	}
	// if the message isn't empty, add a space
	if(!empty($mod_log_action))
	{
		$mod_log_action .= " ";
	}
	$mod_log_action .= $message;
}

/**
 * Add to the redirect message.
 *
 * @param string The message to add.
**/
function mysupport_redirect_message($message)
{
	global $redirect;
	
	// if the message isn't empty, add a new line
	if(!empty($redirect))
	{
		$redirect .= "<br /><br />";
	}
	$redirect .= $message;
}

/**
 * Send a PM about a new assignment
 *
 * @param int The UID of who we're assigning it to now.
 * @param int The FID the thread is in.
 * @param int The TID of the thread.
**/
function mysupport_send_assign_pm($uid, $fid, $tid)
{
	global $mybb, $db, $lang;
	
	if($uid == $mybb->user['uid'])
	{
		//return;
	}
	
	$uid = intval($uid);
	$fid = intval($fid);
	$tid = intval($tid);
	
	$user_info = get_user($uid);
	$username = $user_info['username'];
	
	$forum_url = $mybb->settings['bburl']."/".get_forum_link($fid);
	$forum_info = get_forum($fid);
	$forum_name = $forum_info['name'];
	
	$thread_url = $mybb->settings['bburl']."/".get_thread_link($tid);
	$thread_info = get_thread($tid);
	$thread_name = $thread_info['subject'];
	
	$recipients_to = array($uid);
	$recipients_bcc = array();
	
	$assigned_by_user_url = $mybb->settings['bburl']."/".get_profile_link($mybb->user['uid']);
	$assigned_by = $lang->sprintf($lang->assigned_by, $assigned_by_user_url, htmlspecialchars_uni($mybb->user['username']));
	
	$message = $lang->sprintf($lang->assign_pm_message, htmlspecialchars_uni($username), $forum_url, htmlspecialchars_uni($forum_name), $thread_url, htmlspecialchars_uni($thread_name), $assigned_by, $mybb->settings['bburl']);
	
	$pm = array(
		"subject" => $lang->assign_pm_subject,
		"message" => $message,
		"icon" => -1,
		"fromid" => 0,
		"toid" => $recipients_to,
		"bccid" => $recipients_bcc,
		"do" => '',
		"pmid" => '',
		"saveasdraft" => 0,
		"options" => array(
			"signature" => 1,
			"disablesmilies" => 0,
			"savecopy" => 0,
			"readreceipt" => 0
		)
	);
	
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();
	
	$pmhandler->admin_override = 1;
	$pmhandler->set_data($pm);
	
	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();
	}
}

/**
 * Get the relative time of when a thread was solved.
 *
 * @param int Timestamp of when the thread was solved.
 * @return string Relative time of when the thread was solved.
**/
function mysupport_relative_time($statustime)
{
	global $lang;
	
	$lang->load("mysupport");
	
	$time = TIME_NOW - $statustime;
	
	if($time <= 60)
	{
		return $lang->mysupport_just_now;
	}
	else
	{
		$options = array();
		if($time >= 864000)
		{
			$options['hours'] = false;
			$options['minutes'] = false;
			$options['seconds'] = false;
		}
		return nice_time($time)." ".$lang->mysupport_ago;
	}
}

/**
 * Get the count of technical or assigned threads.
 *
 * @param int The FID we're in.
 * @return int The number of technical or assigned threads in this forum.
**/
function mysupport_get_count($type, $fid = 0)
{
	global $mybb, $db, $cache;
	
	$fid = intval($fid);
	$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
	
	$count = 0;
	$forums = $cache->read("forums");
	if($type == "technical")
	{
		// there's no FID given so this is loading the total number of technical threads
		if($fid == 0)
		{
			foreach($forums as $forum => $info)
			{
				$count += $info['technicalthreads'];
			}
		}
		// we have an FID, so count the number of technical threads in this specific forum and all it's parents
		else
		{
			$forums_list = array();
			foreach($forums as $forum => $info)
			{
				$parentlist = $info['parentlist'];
				if(strpos(",".$parentlist.",", ",".$fid.",") !== false)
				{
					$forums_list[] = $forum;
				}
			}
			foreach($forums_list as $forum)
			{
				$count += $forums[$forum]['technicalthreads'];
			}
		}
	}
	elseif($type == "assigned")
	{
		$assigned = unserialize($mybb->user['assignedthreads']);
		if(!is_array($assigned))
		{
			return 0;
		}
		// there's no FID given so this is loading the total number of assigned threads
		if($fid == 0)
		{
			foreach($assigned as $fid => $threads)
			{
				$count += $threads;
			}
		}
		// we have an FID, so count the number of assigned threads in this specific forum
		else
		{
			$forums_list = array();
			foreach($forums as $forum => $info)
			{
				$parentlist = $info['parentlist'];
				if(strpos(",".$parentlist.",", ",".$fid.",") !== false)
				{
					$forums_list[] = $forum;
				}
			}
			foreach($forums_list as $forum)
			{
				$count += $assigned[$forum];
			}
		}
	}
	
	return $count;
}

/**
 * Recount how many technical threads there are in each forum.
 *
**/
function mysupport_recount_technical_threads()
{
	global $db, $cache;
	
	$update = array(
		"technicalthreads" => 0
	);
	$db->update_query("forums", $update);
	
	$query = $db->simple_select("threads", "fid", "status = '2'");
	$techthreads = array();
	while($fid = $db->fetch_field($query, "fid"))
	{
		if(!$techthreads[$fid])
		{
			$techthreads[$fid] = 0;
		}
		$techthreads[$fid]++;
	}
	
	foreach($techthreads as $forum => $count)
	{
		$update = array(
			"technicalthreads" => intval($count)
		);
		$db->update_query("forums", $update, "fid = '".intval($forum)."'");
	}
	
	$cache->update_forums();
}

/**
 * Recount how many threads a user has been assigned.
**/
function mysupport_recount_assigned_threads($uid)
{
	global $db, $cache;
	
	$uid = intval($uid);
	
	$query = $db->simple_select("threads", "fid", "assign = '{$uid}' AND status != '1'");
	$assigned = array();
	while($fid = $db->fetch_field($query, "fid"))
	{
		if(!$assigned[$fid])
		{
			$assigned[$fid] = 0;
		}
		$assigned[$fid]++;
	}
	$assigned = serialize($assigned);
	
	$update = array(
		"assignedthreads" => $db->escape_string($assigned)
	);
	$db->update_query("users", $update, "uid = '{$uid}'");
}

/**
 * Check if a points system is enabled for points system integration.
 *
 * @return bool Whether or not your chosen points system is enabled.
**/
function mysupport_points_system_enabled()
{
	global $mybb, $cache;
	
	$plugins = $cache->read("plugins");
	
	if($mybb->settings['mysupportpointssystem'] != "none")
	{
		if($mybb->settings['mysupportpointssystem'] == "other")
		{
			$mybb->settings['mysupportpointssystem'] = $mybb->settings['mysupportpointssystemname'];
		}
		return in_array($mybb->settings['mysupportpointssystem'], $plugins['active']);
	}
	return false;
}

/**
 * Update points for certain MySupport actions.
 *
 * @param int The number of points to add/remove.
 * @param int The UID of the user we're adding/removing points to/from.
 * @param bool Is this removing points?? Defaults to false as we'd be adding them most of the time.
**/
function mysupport_update_points($points, $uid, $removing = false)
{
	global $mybb, $db;
	
	$points = intval($points);
	$uid = intval($uid);
	
	switch($mybb->settings['mysupportpointssystem'])
	{
		case "myps":
			$column = "myps";
			break;
		case "newpoints":
			$column = "newpoints";
			break;
		case "other":
			$column = $db->escape_string($mybb->settings['mysupportpointssystemcolumn']);
			break;
		default:
			$column = "";
	}
	
	// if it somehow had to resort to the default option above or 'other' was selected but no custom column name was specified, don't run the query because it's going to create an SQL error, no column to update
	if(!empty($column))
	{
		if($removing)
		{
			$operator = "-";
		}
		else
		{
			$operator = "+";
		}
		
		$query = $db->write_query("UPDATE ".TABLE_PREFIX."users SET {$column} = {$column} {$operator} '{$points}' WHERE uid = '{$uid}'");
	}
}

/**
 * Build an array of who can be assigned threads. Used to build the dropdown menus, and also check a valid user has been chosen.
 *
 * @return array Array of available categories.
**/
function mysupport_get_assign_users()
{
	global $db, $cache;
	
	// who can be assigned threads??
	$groups = $cache->read("usergroups");
	$assign_groups = array();
	foreach($groups as $group)
	{
		if($group['canbeassigned'] == 1)
		{
			$assign_groups[] = intval($group['gid']);
		}
	}
	
	// only continue if there's one or more groups that can be assigned threads
	if(!empty($assign_groups))
	{
		$assigngroups = "";
		$assigngroups = implode(",", array_map("intval", $assign_groups));
		$assign_concat_sql = "";
		foreach($assign_groups as $assign_group)
		{
			if(!empty($assign_concat_sql))
			{
				$assign_concat_sql .= " OR ";
			}
			$assign_concat_sql .= "CONCAT(',',additionalgroups,',') LIKE '%,{$assign_group},%'";
		}
		
		$query = $db->simple_select("users", "uid, username", "usergroup IN (".$db->escape_string($assigngroups).") OR displaygroup IN (".$db->escape_string($assigngroups).") OR {$assign_concat_sql}");
		$assign_users = array();
		while($assigned = $db->fetch_array($query))
		{
			$assign_users[$assigned['uid']] = $assigned['username'];
		}
	}
	return $assign_users;
}

/**
 * Build an array of available categories (thread prefixes). Used to build the dropdown menus, and also check a valid category has been chosen.
 *
 * @param array Info on the forum.
 * @return array Array of available categories.
**/
function mysupport_get_categories($forum)
{
	global $mybb, $db;
	
	$forums_concat_sql = $groups_concat_sql = "";
	
	$parent_list = explode(",", $forum['parentlist']);
	foreach($parent_list as $parent)
	{
		if(!empty($forums_concat_sql))
		{
			$forums_concat_sql .= " OR ";
		}
		$forums_concat_sql .= "CONCAT(',',forums,',') LIKE '%,".intval($parent).",%'";
	}
	$forums_concat_sql = "(".$forums_concat_sql." OR forums = '-1')";
	
	$usergroup_list = $mybb->user['usergroup'];
	if(!empty($mybb->user['additionalgroups']))
	{
		$usergroup_list .= ",".$mybb->user['additionalgroups'];
	}
	$usergroup_list = explode(",", $usergroup_list);
	foreach($usergroup_list as $usergroup)
	{
		if(!empty($groups_concat_sql))
		{
			$groups_concat_sql .= " OR ";
		}
		$groups_concat_sql .= "CONCAT(',',groups,',') LIKE '%,".intval($usergroup).",%'";
	}
	$groups_concat_sql = "(".$groups_concat_sql." OR groups = '-1')";
	
	$query = $db->simple_select("threadprefixes", "pid, prefix", "{$forums_concat_sql} AND {$groups_concat_sql}");
	$categories = array();
	while($category = $db->fetch_array($query))
	{
		$categories[$category['pid']] = $category['prefix'];
	}
	return $categories;
}

/**
 * Show the status of a thread.
 *
 * @param int The status of the thread.
 * @param int The time the thread was solved.
 * @param int The TID of the thread.
**/
function mysupport_get_display_status($status, $onhold = 0, $statustime = 0, $thread_author = 0)
{
	global $mybb, $lang, $templates, $theme, $mysupport_status;
	
	$thread_author = intval($thread_author);
	
	// if this user is logged in, we want to override the global setting for display with their own setting
	if($mybb->user['uid'] != 0 && $mybb->settings['mysupportdisplaytypeuserchange'] == 1)
	{
		if($mybb->user['mysupportdisplayastext'] == 1)
		{
			$mybb->settings['mysupportdisplaytype'] = "text";
		}
		else
		{
			$mybb->settings['mysupportdisplaytype'] = "image";
		}
	}
	
	// big check to see if either the status is to be show to everybody, only to people who can mark as solved, or to people who can mark as solved or who authored the thread
	if($mybb->settings['mysupportdisplayto'] == "all" || ($mybb->settings['mysupportdisplayto'] == "canmas" && mysupport_usergroup("canmarksolved")) || ($mybb->settings['mysupportdisplayto'] == "canmasauthor" && (mysupport_usergroup("canmarksolved") || $mybb->user['uid'] == $thread_author)))
	{
		if($mybb->settings['mysupportrelativetime'] == 1)
		{
			$date_time = mysupport_relative_time($statustime);
			$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time_technical));
		}
		else
		{
			$date = my_date(intval($mybb->settings['dateformat']), intval($statustime));
			$time = my_date(intval($mybb->settings['timeformat']), intval($statustime));
			$date_time = $date." ".$time;
		}
		
		if($mybb->settings['mysupportdisplaytype'] == "text")
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I?? sorry about that
			if($status == 2 && !($mybb->settings['mysupporthidetechnical'] == 1 && !mysupport_usergroup("canmarktechnical")))
			{
				$status_class = "technical";
				$status_text = $lang->technical;
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time));
			}
			elseif($status == 1)
			{
				$status_class = "solved";
				$status_text = $lang->solved;
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time));
			}
			else
			{
				$status_class = "notsolved";
				$status_text = $status_title = $lang->not_solved;
			}
			
			if($onhold == 1)
			{
				$status_class = "onhold";
				$status_text = $lang->onhold;
				$status_title = $lang->onhold." - ".$status_title;
			}
			
			eval("\$mysupport_status = \"".$templates->get('mysupport_status_text')."\";");
		}
		else
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I?? sorry about that
			if($status == 2 && !($mybb->settings['mysupporthidetechnical'] == 1 && !mysupport_usergroup("canmarktechnical")))
			{
				$status_img = "technical";
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time));
			}
			elseif($status == 1)
			{
				$status_img = "solved";
				$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time));
			}
			else
			{
				$status_img = "notsolved";
				$status_title = $lang->not_solved;
			}
			
			if($onhold == 1)
			{
				$status_img = "onhold";
				$status_title = $lang->onhold." - ".$status_title;
			}
			
			eval("\$mysupport_status = \"".$templates->get('mysupport_status_image')."\";");
		}
		
		return $mysupport_status;
	}
}

/**
 * Get the text version of the status of a thread.
 *
 * @param int The status of the thread.
 * @param string The text version of the status of the thread.
**/
function mysupport_get_friendly_status($status = 0)
{
	global $lang;
	
	$lang->load("mysupport");
	
	$status = intval($status);
	switch($status)
	{
		// has it been marked as not techincal??
		case 4:
			$friendlystatus = $lang->not_technical;
			break;
		// is it a technical thread??
		case 2:
			$friendlystatus = $lang->technical;
			break;
		// no, is it a solved thread??
		case 3:
		case 1:
			$friendlystatus = $lang->solved;
			break;
		// must be not solved then
		default:
			$friendlystatus = $lang->not_solved;
	}
	
	return $friendlystatus;
}
?>