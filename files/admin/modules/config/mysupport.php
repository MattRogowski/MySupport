<?php
/**
 * MySupport 0.4 - Admin File

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

$page->add_breadcrumb_item($lang->mysupport, "index.php?module=config-mysupport");

if($mybb->input['action'] == "do_general")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-mysupport&action=general");
	}

	// reset the forum settings
	$update = array(
		"mysupport" => 0,
		"mysupportmove" => 0
	);
	$db->update_query("forums", $update);

	// reset the usergroup settings
	$update = array(
		"canmarksolved" => 0
	);
	$db->update_query("usergroups", $update);

	$new_mysupport_forums = "";
	if(empty($mybb->input['mysupport_forums']))
	{
		$mybb->input['mysupport_forums'] = array();
	}
	$new_mysupport_forums = implode(",", array_map("intval", $mybb->input['mysupport_forums']));
	if(!empty($new_mysupport_forums))
	{
		$update = array(
			"mysupport" => 1
		);
		$db->update_query("forums", $update, "fid IN (" . $db->escape_string($new_mysupport_forums) . ")");
	}

	$new_mysupport_move_forum = intval($mybb->input['mysupport_move_forum']);
	if($new_mysupport_move_forum)
	{
		$query = $db->simple_select("forums", "type", "fid = '{$new_mysupport_move_forum}'");
		$type = $db->fetch_field($query, "type");
		if($type == "f")
		{
			$update = array(
				"mysupportmove" => 1
			);
			$db->update_query("forums", $update, "fid = '{$new_mysupport_move_forum}'");
		}
		else
		{
			$invalid_move_forum = true;
		}
	}

	$new_canmarksolved_groups = "";
	if(empty($mybb->input['mysupport_canmarksolved']))
	{
		$mybb->input['mysupport_canmarksolved'] = array();
	}
	$new_canmarksolved_groups = implode(",", array_map("intval", $mybb->input['mysupport_canmarksolved']));
	if(!empty($new_canmarksolved_groups))
	{
		$update = array(
			"canmarksolved" => 1
		);
		$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canmarksolved_groups) . ")");
	}

	$new_mysupportmodlog = "";
	if(empty($mybb->input['mysupportmodlog']))
	{
		$mybb->input['mysupportmodlog'] = array();
	}
	$new_mysupportmodlog = implode(",", array_map("intval", $mybb->input['mysupportmodlog']));
	if($new_mysupportmodlog != $mybb->settings['mysupportmodlog'])
	{
		$update = array(
			"value" => $db->escape_string($new_mysupportmodlog)
		);
		$db->update_query("settings", $update, "name = 'mysupportmodlog'");
		rebuild_settings();
	}

	// rebuild the caches
	$cache->update_forums();
	$cache->update_usergroups();

	if($invalid_move_forum === true)
	{
		flash_message($lang->error_general_move_forum, 'error');
	}
	else
	{
		flash_message($lang->success_general, 'success');
	}
	admin_redirect("index.php?module=config-mysupport&amp;action=general");
}
elseif($mybb->input['action'] == "do_technical")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-mysupport&action=technical_assign");
	}

	// reset the usergroup settings
	$update = array(
		"canmarktechnical" => 0,
		"canseetechnotice" => 0
	);
	$db->update_query("usergroups", $update);

	$new_canmarktechnical_groups = "";
	if(empty($mybb->input['mysupport_canmarktechnical']))
	{
		$mybb->input['mysupport_canmarktechnical'] = array();
	}
	$new_canmarktechnical_groups = implode(",", array_map("intval", $mybb->input['mysupport_canmarktechnical']));
	if(!empty($new_canmarktechnical_groups))
	{
		$update = array(
			"canmarktechnical" => 1
		);
		$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canmarktechnical_groups) . ")");
	}

	$new_canseetechnotice_groups = "";
	if(empty($mybb->input['mysupport_canseetechnotice']))
	{
		$mybb->input['mysupport_canseetechnotice'] = array();
	}
	$new_canseetechnotice_groups = implode(",", array_map("intval", $mybb->input['mysupport_canseetechnotice']));
	if(!empty($new_canseetechnotice_groups))
	{
		$update = array(
			"canseetechnotice" => 1
		);
		$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canseetechnotice_groups) . ")");
	}

	// rebuild the cache
	$cache->update_usergroups();

	flash_message($lang->success_technical, 'success');
	admin_redirect("index.php?module=config-mysupport&amp;action=technical_assign");
}
elseif($mybb->input['action'] == "do_assign")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-mysupport&action=technical_assign");
	}

	// reset the usergroup settings
	$update = array(
		"canassign" => 0,
		"canbeassigned" => 0
	);
	$db->update_query("usergroups", $update);

	$new_canassign_groups = "";
	if(empty($mybb->input['mysupport_canassign']))
	{
		$mybb->input['mysupport_canassign'] = array();
	}
	$new_canassign_groups = implode(",", array_map("intval", $mybb->input['mysupport_canassign']));
	if(!empty($new_canassign_groups))
	{
		$update = array(
			"canassign" => 1
		);
		$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canassign_groups) . ")");
	}

	$new_canbeassigned_groups = "";
	if(empty($mybb->input['mysupport_canbeassigned']))
	{
		$mybb->input['mysupport_canbeassigned'] = array();
	}
	$new_canbeassigned_groups = implode(",", array_map("intval", $mybb->input['mysupport_canbeassigned']));
	if(!empty($new_canbeassigned_groups))
	{
		$update = array(
			"canbeassigned" => 1
		);
		$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canbeassigned_groups) . ")");
	}

	// rebuild the cache
	$cache->update_usergroups();

	flash_message($lang->success_assign, 'success');
	admin_redirect("index.php?module=config-mysupport&amp;action=technical_assign");
}
elseif($mybb->input['action'] == "technical_assign")
{
	$page->add_breadcrumb_item($lang->technical_assign, "index.php?module=config-mysupport&amp;action=technical_assign");

	$page->output_header($lang->mysupport);

	generate_mysupport_tabs("technical_assign");

	$form = new Form("index.php?module=config-mysupport&amp;action=do_technical", "post");
	$form_container = new FormContainer($lang->technical_header);
	$table = new Table;

	$table->construct_header($lang->mysupport);

	$current_canmarktechnical_groups = array();
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		if($group['canmarktechnical'] == 1)
		{
			$current_canmarktechnical_groups[] = $group['gid'];
		}
	}
	$mysupport_canmarktechnical = $form->generate_group_select('mysupport_canmarktechnical[]', $current_canmarktechnical_groups, array('multiple' => true, 'size' => 5));
	$form_container->output_row($lang->mysupport_canmarktechnical, '', $mysupport_canmarktechnical);

	if($mybb->settings['mysupporttechnicalnotice'] != "off")
	{
		$current_canseetechnotice_groups = array();
		$groups = $cache->read("usergroups");
		foreach($groups as $group)
		{
			if($group['canseetechnotice'] == 1)
			{
				$current_canseetechnotice_groups[] = $group['gid'];
			}
		}
		$mysupport_canseetechnotice = $form->generate_group_select('mysupport_canseetechnotice[]', $current_canseetechnotice_groups, array('multiple' => true, 'size' => 5));
		$form_container->output_row($lang->mysupport_canseetechnotice, '', $mysupport_canseetechnotice);
	}

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->mysupport_submit);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
	unset($buttons);
	echo "<br />";
	$form = new Form("index.php?module=config-mysupport&amp;action=do_assign", "post");
	$form_container = new FormContainer($lang->assign_header);
	$table = new Table;

	$table->construct_header($lang->mysupport);

	$current_canassign_groups = array();
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		if($group['canassign'] == 1)
		{
			$current_canassign_groups[] = $group['gid'];
		}
	}
	$mysupport_canassign = $form->generate_group_select('mysupport_canassign[]', $current_canassign_groups, array('multiple' => true, 'size' => 5));
	$form_container->output_row($lang->mysupport_canassign, '', $mysupport_canassign);

	$current_canbeassigned_groups = array();
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		if($group['canbeassigned'] == 1)
		{
			$current_canbeassigned_groups[] = $group['gid'];
		}
	}
	$mysupport_canbeassigned = $form->generate_group_select('mysupport_canbeassigned[]', $current_canbeassigned_groups, array('multiple' => true, 'size' => 5));
	$form_container->output_row($lang->mysupport_canbeassigned, '', $mysupport_canbeassigned);

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->mysupport_submit);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
	$page->output_footer();
}
elseif($mybb->input['action'] == "categories")
{
	flash_message($lang->categories_prefixes_redirect, 'success');
	admin_redirect("index.php?module=config-thread_prefixes");
}
elseif($mybb->input['action'] == "do_priorities")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-mysupport&action=priorities");
	}

	if($mybb->input['do'] == "do_add")
	{
		if(!strlen(trim($mybb->input['name'])))
		{
			flash_message($lang->priority_no_name, 'error');
			admin_redirect("index.php?module=config-mysupport&action=priorities");
		}
		$insert = array(
			"name" => $db->escape_string($mybb->input['name']),
			"description" => $db->escape_string($mybb->input['description']),
			"extra" => $db->escape_string(str_replace("#", "", $mybb->input['style'])),
			"type" => "priority"
		);
		$db->insert_query("mysupport", $insert);

		mysupport_cache("priorities");

		flash_message($lang->priority_added, 'success');
		admin_redirect("index.php?module=config-mysupport&action=priorities");
	}
	elseif($mybb->input['do'] == "do_edit")
	{
		$pid = intval($mybb->input['pid']);
		if(!strlen(trim($mybb->input['name'])))
		{
			flash_message($lang->priority_no_name, 'error');
			admin_redirect("index.php?module=config-mysupport&action=priorities&do=edit&pid={$pid}");
		}
		$update = array(
			"name" => $db->escape_string($mybb->input['name']),
			"description" => $db->escape_string($mybb->input['description']),
			"extra" => $db->escape_string(str_replace("#", "", $mybb->input['style']))
		);
		$db->update_query("mysupport", $update, "mid = '{$pid}'");

		mysupport_cache("priorities");

		flash_message($lang->priority_edited, 'success');
		admin_redirect("index.php?module=config-mysupport&action=priorities");
	}
	elseif($mybb->input['do'] == "do_delete")
	{
		if($mybb->input['no'])
		{
			admin_redirect("index.php?module=config-mysupport&action=priorities");
		}
		else
		{
			$pid = intval($mybb->input['pid']);
			$update = array(
				"priority" => 0
			);
			$db->update_query("threads", $update, "priority = '{$pid}'");
			$db->delete_query("mysupport", "mid = '{$pid}' AND type = 'priority'");

			mysupport_cache("priorities");

			flash_message($lang->priority_deleted, 'success');
			admin_redirect("index.php?module=config-mysupport&action=priorities");
		}
	}
	elseif($mybb->input['do'] == "do_groups")
	{
		// reset the usergroup settings
		$update = array(
			"cansetpriorities" => 0,
			"canseepriorities" => 0
		);
		$db->update_query("usergroups", $update);

		$new_cansetpriorities_groups = "";
		if(empty($mybb->input['mysupport_cansetpriorities']))
		{
			$mybb->input['mysupport_cansetpriorities'] = array();
		}
		$new_cansetpriorities_groups = implode(",", array_map("intval", $mybb->input['mysupport_cansetpriorities']));
		if(!empty($new_cansetpriorities_groups))
		{
			$update = array(
				"cansetpriorities" => 1
			);
			$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_cansetpriorities_groups) . ")");
		}

		$new_canseepriorities_groups = "";
		if(empty($mybb->input['mysupport_canseepriorities']))
		{
			$mybb->input['mysupport_canseepriorities'] = array();
		}
		$new_canseepriorities_groups = implode(",", array_map("intval", $mybb->input['mysupport_canseepriorities']));
		if(!empty($new_canseepriorities_groups))
		{
			$update = array(
				"canseepriorities" => 1
			);
			$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canseepriorities_groups) . ")");
		}

		// rebuild the cache
		$cache->update_usergroups();

		flash_message($lang->success_priorities, 'success');
		admin_redirect("index.php?module=config-mysupport&amp;action=priorities");
	}
}
elseif($mybb->input['action'] == "priorities")
{
	$page->add_breadcrumb_item($lang->priorities, "index.php?module=config-mysupport&amp;action=priorities");

	if($mybb->input['do'] == "edit")
	{
		$page->output_header($lang->mysupport);

		generate_mysupport_tabs("priorities");

		$table = new Table;

		$pid = intval($mybb->input['pid']);
		$query = $db->simple_select("mysupport", "*", "type = 'priority' AND mid = '{$pid}'");
		if($db->num_rows($query) == 0)
		{
			flash_message($lang->priority_invalid, 'error');
			admin_redirect("index.php?module=config-mysupport&action=priorities");
		}
		else
		{
			$priority = $db->fetch_array($query);
		}

		$form = new Form("index.php?module=config-mysupport&amp;action=do_priorities", "post");
		$form_container = new FormContainer($lang->priorities_edit);

		$edit_priority_name = $form->generate_text_box("name", htmlspecialchars_uni($priority['name']));
		$form_container->output_row($lang->mysupport_name . " <em>*</em>", '', $edit_priority_name);

		$edit_priority_description = $form->generate_text_box("description", htmlspecialchars_uni($priority['description']));
		$form_container->output_row($lang->mysupport_description, '', $edit_priority_description);

		$edit_priority_style = $form->generate_text_box("style", htmlspecialchars_uni($priority['extra']));
		$form_container->output_row($lang->priority_style, $lang->priority_style_description, $edit_priority_style);

		echo $form->generate_hidden_field("do", "do_edit");
		echo $form->generate_hidden_field("pid", intval($mybb->input['pid']));

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->mysupport_edit_priority_submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
	elseif($mybb->input['do'] == "delete")
	{
		$pid = intval($mybb->input['pid']);
		$query = $db->simple_select("mysupport", "*", "type = 'priority' AND mid = '{$pid}'");
		if($db->num_rows($query) == 0)
		{
			flash_message($lang->priority_invalid, 'error');
			admin_redirect("index.php?module=config-mysupport&action=priorities");
		}
		$query = $db->simple_select("threads", "COUNT(*) AS priority_count", "priority = '{$pid}'");
		$priority_count = $db->fetch_field($query, "priority_count");
		if($priority_count > 0)
		{
			$priority_delete_confirm_count = " " . $lang->sprintf($lang->priority_delete_confirm_count, $priority_count);
		}
		else
		{
			$priority_delete_confirm_count = "";
		}
		$page->output_confirm_action("index.php?module=config-mysupport&amp;action=do_priorities&amp;do=do_delete&amp;pid={$pid}", $lang->priority_delete_confirm . $priority_delete_confirm_count);
	}
	elseif($mybb->input['do'] == "viewthreads")
	{
		$page->output_header($lang->mysupport);

		generate_mysupport_tabs("priorities");

		$table = new Table;

		$pid = intval($mybb->input['pid']);
		$query = $db->write_query("
			SELECT t.tid, t.subject, t.fid, f.name, t.uid, t.username, t.status
			FROM " . TABLE_PREFIX . "threads t
			LEFT JOIN " . TABLE_PREFIX . "forums f
			ON (t.fid = f.fid)
			WHERE t.priority = '{$pid}'
		");
		$query2 = $db->simple_select("mysupport", "name", "mid = '{$pid}' AND type = 'priority'");
		$priority_name = $db->fetch_field($query2, "name");
		if($db->num_rows($query) > 0)
		{
			$table->construct_header($lang->thread);
			$table->construct_header($lang->forum);
			$table->construct_header($lang->started_by);
			$table->construct_header($lang->status);

			while($thread = $db->fetch_array($query))
			{
				$thread_link = get_thread_link($thread['tid']);
				$forum_link = get_forum_link($thread['fid']);
				$profile_link = build_profile_link($thread['username'], $thread['uid'], "_blank");

				$table->construct_cell("<a href=\"{$mybb->settings['bburl']}/{$thread_link}\" target=\"_blank\">" . htmlspecialchars_uni($thread['subject']) . "</a>", array('width' => '30%'));
				$table->construct_cell("<a href=\"{$mybb->settings['bburl']}/{$forum_link}\" target=\"_blank\">" . htmlspecialchars_uni($thread['name']) . "</a>", array('width' => '30%'));
				$table->construct_cell($profile_link, array('class' => 'align_center', 'width' => '20%'));
				$table->construct_cell(mysupport_get_friendly_status($thread['status']), array('class' => 'align_center', 'width' => '20%'));
				$table->construct_row();
			}
		}
		else
		{
			$table->construct_cell($lang->sprintf($lang->priorities_thread_list_none, $priority_name), array('class' => 'align_center'));
			$table->construct_row();
		}

		$table->output($lang->sprintf($lang->priorities_thread_list_header, $priority_name));
	}
	else
	{
		$page->output_header($lang->mysupport);

		generate_mysupport_tabs("priorities");

		$table = new Table;

		$query = $db->simple_select("mysupport", "*", "type = 'priority'");
		if($db->num_rows($query) > 0)
		{
			$table->construct_header($lang->mysupport_name);
			$table->construct_header($lang->mysupport_description);
			$table->construct_header($lang->controls, array("colspan" => 3, 'class' => 'align_center'));

			while($priority = $db->fetch_array($query))
			{
				if(!empty($priority['extra']))
				{
					$style = "background: #{$priority['extra']}";
				}
				else
				{
					$style = "";
				}
				$table->construct_cell($priority['name'], array('width' => '20%', 'style' => $style));
				$table->construct_cell($priority['description'], array('width' => '30%', 'style' => $style));
				$table->construct_cell("<a href=\"index.php?module=config-mysupport&amp;action=priorities&amp;do=edit&amp;pid={$priority['mid']}\">{$lang->edit}</a>", array('class' => 'align_center', 'width' => '15%'));
				$table->construct_cell("<a href=\"index.php?module=config-mysupport&amp;action=priorities&amp;do=delete&amp;pid={$priority['mid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
				$table->construct_cell("<a href=\"index.php?module=config-mysupport&amp;action=priorities&amp;do=viewthreads&amp;pid={$priority['mid']}\">{$lang->mysupport_view_threads}</a>", array('class' => 'align_center', 'width' => '20%'));
				$table->construct_row();
			}

			$table->output($lang->priorities_current);
		}

		$form = new Form("index.php?module=config-mysupport&amp;action=do_priorities", "post");
		$form_container = new FormContainer($lang->priorities_add);

		$add_priority_name = $form->generate_text_box("name");
		$form_container->output_row($lang->mysupport_name . " <em>*</em>", '', $add_priority_name);

		$add_priority_description = $form->generate_text_box("description");
		$form_container->output_row($lang->mysupport_description, '', $add_priority_description);

		$add_priority_style = $form->generate_text_box("style");
		$form_container->output_row($lang->priority_style, $lang->priority_style_description, $add_priority_style);

		echo $form->generate_hidden_field("do", "do_add");

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->mysupport_add_priority_submit);
		$form->output_submit_wrapper($buttons);
		$form->end();

		echo "\n<br />\n";

		$form = new Form("index.php?module=config-mysupport&amp;action=do_priorities", "post");
		$form_container = new FormContainer($lang->priorities_header);
		$table = new Table;

		$table->construct_header($lang->mysupport);

		$current_cansetpriorities_groups = array();
		$groups = $cache->read("usergroups");
		foreach($groups as $group)
		{
			if($group['cansetpriorities'] == 1)
			{
				$current_cansetpriorities_groups[] = $group['gid'];
			}
		}
		$mysupport_cansetpriorities = $form->generate_group_select('mysupport_cansetpriorities[]', $current_cansetpriorities_groups, array('multiple' => true, 'size' => 5));
		$form_container->output_row($lang->mysupport_cansetpriorities, '', $mysupport_cansetpriorities);

		$current_canseepriorities_groups = array();
		$groups = $cache->read("usergroups");
		foreach($groups as $group)
		{
			if($group['canseepriorities'] == 1)
			{
				$current_canseepriorities_groups[] = $group['gid'];
			}
		}
		$mysupport_canseepriorities = $form->generate_group_select('mysupport_canseepriorities[]', $current_canseepriorities_groups, array('multiple' => true, 'size' => 5));
		$form_container->output_row($lang->mysupport_canseepriorities, '', $mysupport_canseepriorities);

		echo $form->generate_hidden_field("do", "do_groups");

		$form_container->end();

		unset($buttons);
		$buttons[] = $form->generate_submit_button($lang->mysupport_submit);
		$buttons[] = $form->generate_reset_button($lang->reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}

	$page->output_footer();
}
elseif($mybb->input['action'] == "do_support_denial")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=config-mysupport&action=support_denial");
	}

	if($mybb->input['do'] == "do_add")
	{
		if(!strlen(trim($mybb->input['name'])))
		{
			flash_message($lang->support_denial_reason_no_name, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
		if(!strlen(trim($mybb->input['description'])))
		{
			flash_message($lang->support_denial_reason_no_description, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
		$insert = array(
			"name" => $db->escape_string($mybb->input['name']),
			"description" => $db->escape_string($mybb->input['description']),
			"type" => "deniedreason"
		);
		$db->insert_query("mysupport", $insert);

		mysupport_cache("deniedreasons");

		flash_message($lang->support_denial_reason_added, 'success');
		admin_redirect("index.php?module=config-mysupport&action=support_denial");
	}
	elseif($mybb->input['do'] == "do_edit")
	{
		$drid = intval($mybb->input['drid']);
		if(!strlen(trim($mybb->input['name'])))
		{
			flash_message($lang->support_denial_reason_no_name, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial&do=edit&drid={$drid}");
		}
		if(!strlen(trim($mybb->input['description'])))
		{
			flash_message($lang->support_denial_reason_no_description, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial&do=edit&drid={$drid}");
		}
		$update = array(
			"name" => $db->escape_string($mybb->input['name']),
			"description" => $db->escape_string($mybb->input['description'])
		);
		$db->update_query("mysupport", $update, "mid = '{$drid}'");

		mysupport_cache("deniedreasons");

		flash_message($lang->support_denial_reason_edited, 'success');
		admin_redirect("index.php?module=config-mysupport&action=support_denial");
	}
	elseif($mybb->input['do'] == "do_delete")
	{
		if($mybb->input['no'])
		{
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
		else
		{
			$drid = intval($mybb->input['drid']);
			$update = array(
				"deniedsupportreason" => 0
			);
			$db->update_query("users", $update, "deniedsupportreason = '{$drid}'");
			$db->delete_query("mysupport", "mid = '{$drid}'");

			mysupport_cache("deniedreasons");

			flash_message($lang->support_denial_reason_deleted, 'success');
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
	}
	elseif($mybb->input['do'] == "do_groups")
	{
		// reset the usergroup settings
		$update = array(
			"canmanagesupportdenial" => 0
		);
		$db->update_query("usergroups", $update);

		$new_canmanagesupportdenial_groups = "";
		if(empty($mybb->input['mysupport_canmanagesupportdenial']))
		{
			$mybb->input['mysupport_canmanagesupportdenial'] = array();
		}
		$new_canmanagesupportdenial_groups = implode(",", array_map("intval", $mybb->input['mysupport_canmanagesupportdenial']));
		if(!empty($new_canmanagesupportdenial_groups))
		{
			$update = array(
				"canmanagesupportdenial" => 1
			);
			$db->update_query("usergroups", $update, "gid IN (" . $db->escape_string($new_canmanagesupportdenial_groups) . ")");
		}

		// rebuild the cache
		$cache->update_usergroups();

		flash_message($lang->success_support_denial, 'success');
		admin_redirect("index.php?module=config-mysupport&amp;action=support_denial");
	}
}
elseif($mybb->input['action'] == "support_denial")
{
	$page->add_breadcrumb_item($lang->support_denial, "index.php?module=config-mysupport&amp;action=support_denial");

	if($mybb->input['do'] == "edit")
	{
		$page->output_header($lang->mysupport);

		generate_mysupport_tabs("support_denial");

		$table = new Table;

		$drid = intval($mybb->input['drid']);
		$query = $db->simple_select("mysupport", "*", "mid = '{$drid}' AND type = 'deniedreason'");
		if($db->num_rows($query) != 1)
		{
			flash_message($lang->support_denial_reason_invalid, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
		else
		{
			$deniedreason = $db->fetch_array($query);
		}
		$form = new Form("index.php?module=config-mysupport&amp;action=do_support_denial", "post");
		$form_container = new FormContainer($lang->support_denial_reason_edit);

		$edit_support_denial_reason_name = $form->generate_text_box("name", htmlspecialchars_uni($deniedreason['name']));
		$form_container->output_row($lang->mysupport_name . " <em>*</em>", '', $edit_support_denial_reason_name);

		$edit_support_denial_reason_description = $form->generate_text_area("description", htmlspecialchars_uni($deniedreason['description']));
		$form_container->output_row($lang->mysupport_description . " <em>*</em>", $lang->support_denial_reason_description_description, $edit_support_denial_reason_description);

		echo $form->generate_hidden_field("do", "do_edit");
		echo $form->generate_hidden_field("drid", intval($mybb->input['drid']));

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->mysupport_edit_support_denial_reason_submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
	elseif($mybb->input['do'] == "delete")
	{
		$drid = intval($mybb->input['drid']);
		$query = $db->simple_select("mysupport", "*", "mid = '{$drid}' AND type = 'deniedreason'");
		if($db->num_rows($query) != 1)
		{
			flash_message($lang->support_denial_reason_invalid, 'error');
			admin_redirect("index.php?module=config-mysupport&action=support_denial");
		}
		$query = $db->simple_select("users", "COUNT(*) AS support_denial_reason_count", "deniedsupportreason = '{$drid}'");
		$support_denial_reason_count = $db->fetch_field($query, "support_denial_reason_count");
		if($support_denial_reason_count > 0)
		{
			$lang->support_denial_reason_delete_confirm .= " " . $lang->sprintf($lang->support_denial_reason_delete_confirm_count, $support_denial_reason_count);
		}
		$page->output_confirm_action("index.php?module=config-mysupport&amp;action=do_support_denial&amp;do=do_delete&amp;drid={$drid}", $lang->support_denial_reason_delete_confirm);
	}
	else
	{
		$page->output_header($lang->mysupport);

		generate_mysupport_tabs("support_denial");

		$table = new Table;

		$query = $db->simple_select("mysupport", "*", "type = 'deniedreason'");
		if($db->num_rows($query) != 0)
		{
			$table->construct_header($lang->mysupport_name);
			$table->construct_header($lang->mysupport_description);
			$table->construct_header($lang->controls, array("colspan" => 2, 'class' => 'align_center'));

			while($deniedreason = $db->fetch_array($query))
			{
				$table->construct_cell($deniedreason['name'], array('width' => '20%'));
				$table->construct_cell($deniedreason['description'], array('width' => '50%'));
				$table->construct_cell("<a href=\"index.php?module=config-mysupport&amp;action=support_denial&amp;do=edit&amp;drid={$deniedreason['mid']}\">{$lang->edit}</a>", array('class' => 'align_center', 'width' => '10%'));
				$table->construct_cell("<a href=\"index.php?module=config-mysupport&amp;action=support_denial&amp;do=delete&amp;drid={$deniedreason['mid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '10%'));
				$table->construct_row();
			}

			$table->output($lang->support_denial_reason_current);
		}

		$form = new Form("index.php?module=config-mysupport&amp;action=do_support_denial", "post");
		$form_container = new FormContainer($lang->support_denial_reason_add);

		$add_support_denial_reason_name = $form->generate_text_box("name");
		$form_container->output_row($lang->mysupport_name . " <em>*</em>", '', $add_support_denial_reason_name);

		$add_support_denial_reason_description = $form->generate_text_area("description");
		$form_container->output_row($lang->mysupport_description . " <em>*</em>", $lang->support_denial_reason_description_description, $add_support_denial_reason_description);

		echo $form->generate_hidden_field("do", "do_add");

		$form_container->end();

		$buttons[] = $form->generate_submit_button($lang->mysupport_add_support_denial_reason_submit);
		$form->output_submit_wrapper($buttons);
		$form->end();

		echo "<br />";

		$form = new Form("index.php?module=config-mysupport&amp;action=do_support_denial", "post");
		$form_container = new FormContainer($lang->support_denial_header);
		$table = new Table;

		$table->construct_header($lang->mysupport);

		$current_canmanagesupportdenial_groups = array();
		$groups = $cache->read("usergroups");
		foreach($groups as $group)
		{
			if($group['canmanagesupportdenial'] == 1)
			{
				$current_canmanagesupportdenial_groups[] = $group['gid'];
			}
		}
		$mysupport_canmanagesupportdenial = $form->generate_group_select('mysupport_canmanagesupportdenial[]', $current_canmanagesupportdenial_groups, array('multiple' => true, 'size' => 5));
		$form_container->output_row($lang->mysupport_canmanagesupportdenial, '', $mysupport_canmanagesupportdenial);

		echo $form->generate_hidden_field("do", "do_groups");

		$form_container->end();

		unset($buttons);
		$buttons[] = $form->generate_submit_button($lang->mysupport_submit);
		$buttons[] = $form->generate_reset_button($lang->reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}

	$page->output_footer();
}
elseif($mybb->input['action'] == "settings")
{
	$gid = mysupport_settings_gid();
	// redirect to the settings page
	admin_redirect("index.php?module=config-settings&action=change&gid={$gid}");
}
elseif($mybb->input['action'] == "forcedisplaytype")
{
	if($mybb->settings['mysupportdisplaytype'] == "text")
	{
		$update = array(
			"mysupportdisplayastext" => 1
		);
	}
	else
	{
		$update = array(
			"mysupportdisplayastext" => 0
		);
	}
	$db->update_query("users", $update);

	flash_message($lang->mysupport_display_style_forced, "success");
	$gid = mysupport_settings_gid();
	admin_redirect("index.php?module=config-settings&action=change&gid={$gid}");
}
else
{
	$page->output_header($lang->mysupport);

	generate_mysupport_tabs("general");

	$form = new Form("index.php?module=config-mysupport&amp;action=do_general", "post");
	$form_container = new FormContainer($lang->general_header);
	$table = new Table;

	$table->construct_header($lang->mysupport);

	$current_mysupport_forums = array();
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		if($forum['mysupport'] == 1)
		{
			$current_mysupport_forums[] = $forum['fid'];
		}
	}
	$mysupport_forums = $form->generate_forum_select('mysupport_forums[]', $current_mysupport_forums, array('multiple' => true, 'size' => 5));
	$form_container->output_row($lang->mysupport_forums, '', $mysupport_forums);

	$current_mysupport_move_forum = "";
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		if($forum['mysupportmove'] == 1)
		{
			$current_mysupport_move_forum = $forum['fid'];
		}
	}
	$mysupport_move_forum = $form->generate_forum_select('mysupport_move_forum', $current_mysupport_move_forum, array('size' => 5));
	$form_container->output_row($lang->mysupport_move_forum, $lang->mysupport_move_forum_desc, $mysupport_move_forum);

	$current_canmarksolved_groups = array();
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		if($group['canmarksolved'] == 1)
		{
			$current_canmarksolved_groups[] = $group['gid'];
		}
	}
	$mysupport_canmarksolved = $form->generate_group_select('mysupport_canmarksolved[]', $current_canmarksolved_groups, array('multiple' => true, 'size' => 5));
	$form_container->output_row($lang->mysupport_canmarksolved, '', $mysupport_canmarksolved);

	$mysupportmodlog = explode(",", $mybb->settings['mysupportmodlog']);
	$mysupportmodlog_list = array(
		0 => $lang->mysupport_mod_log_action_0,
		1 => $lang->mysupport_mod_log_action_1,
		2 => $lang->mysupport_mod_log_action_2,
		// yes, it skips 3 on purpose
		4 => $lang->mysupport_mod_log_action_4,
		5 => $lang->mysupport_mod_log_action_5,
		6 => $lang->mysupport_mod_log_action_6,
		7 => $lang->mysupport_mod_log_action_7,
		8 => $lang->mysupport_mod_log_action_8,
		9 => $lang->mysupport_mod_log_action_9,
		10 => $lang->mysupport_mod_log_action_10,
		11 => $lang->mysupport_mod_log_action_11,
		12 => $lang->mysupport_mod_log_action_12,
		13 => $lang->mysupport_mod_log_action_13
	);
	$mysupport_modlog = $form->generate_select_box("mysupportmodlog[]", $mysupportmodlog_list, $mysupportmodlog, array("multiple" => true, "size" => 7));
	$form_container->output_row($lang->mysupport_what_to_log, $lang->mysupport_what_to_log_desc, $mysupport_modlog);

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->mysupport_submit);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
	$page->output_footer();
}

/**
 * Output the MySupport tabs; save repeating code for each section
 *
 * @param string The tab to show as the current tab.
**/
function generate_mysupport_tabs($selected)
{
	global $lang, $page;

	$sub_tabs = array();
	$sub_tabs['general'] = array(
		'title' => $lang->general,
		'link' => "index.php?module=config-mysupport&amp;action=general",
		'description' => $lang->general_nav
	);
	$sub_tabs['technical_assign'] = array(
		'title' => $lang->technical_assign,
		'link' => "index.php?module=config-mysupport&amp;action=technical_assign",
		'description' => $lang->technical_assign_nav
	);
	$sub_tabs['categories'] = array(
		'title' => $lang->categories,
		'link' => "index.php?module=config-mysupport&amp;action=categories",
		'description' => $lang->categories_nav
	);
	$sub_tabs['priorities'] = array(
		'title' => $lang->priorities,
		'link' => "index.php?module=config-mysupport&amp;action=priorities",
		'description' => $lang->priorities_nav
	);
	$sub_tabs['support_denial'] = array(
		'title' => $lang->support_denial,
		'link' => "index.php?module=config-mysupport&amp;action=support_denial",
		'description' => $lang->support_denial_nav
	);
	$sub_tabs['settings'] = array(
		'title' => $lang->mysupport_settings,
		'link' => "index.php?module=config-mysupport&amp;action=settings",
		'description' => ""
	);

	$page->output_nav_tabs($sub_tabs, $selected);
}
?>
