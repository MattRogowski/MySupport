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

function mysupport_do_info()
{
	return array(
		'name' => 'MySupport',
		'description' => 'Add features to your forum to help with giving support. Allows you to mark a thread as solved or technical, assign threads to users, give threads priorities, mark a post as the best answer in a thread, and more to help you run a support forum.',
		'website' => 'http://mattrogowski.co.uk/mybb/plugins/plugin/mysupport',
		'author' => 'MattRogowski',
		'authorsite' => 'http://mattrogowski.co.uk/mybb/',
		'version' => MYSUPPORT_VERSION,
		'compatibility' => '18*',
		'guid' => '3ebe16a9a1edc67ac882782d41742330'
	);
}

function mysupport_do_install()
{
	global $db, $cache, $mysupport_uninstall_confirm_override;

	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$mysupport_uninstall_confirm_override = true;
	mysupport_do_uninstall();

	mysupport_table_columns(1);

	if(!$db->table_exists("mysupport"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "mysupport (
				`mid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`type` VARCHAR(20) NOT NULL ,
				`name` VARCHAR(255) NOT NULL ,
				`description` VARCHAR(500) NOT NULL,
				`extra` VARCHAR(255) NOT NULL
			) ENGINE = MYISAM ;
		");
	}

	$settings_group = array(
		"name" => "mysupport",
		"title" => "MySupport Settings",
		"description" => "Settings for the MySupport plugin.",
		"disporder" => "28",
		"isdefault" => "no"
	);
	$db->insert_query("settinggroups", $settings_group);

	mysupport_import_settings();

	mysupport_do_templates(1);

	mysupport_stylesheet(1);

	// insert some default priorities
	$priorities = array();
	$priorities[] = array(
		"type" => "priority",
		"name" => "Low",
		"description" => "Low priority threads.",
		"extra" => "ADCBE7"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Normal",
		"description" => "Normal priority threads.",
		"extra" => "D6ECA6"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "High",
		"description" => "High priority threads.",
		"extra" => "FFF6BF"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Urgent",
		"description" => "Urgent priority threads.",
		"extra" => "FFE4E1"
	);
	foreach($priorities as $priority)
	{
		$db->insert_query("mysupport", $priority);
	}

	mysupport_insert_task();

	// set some values for the staff groups
	$update = array(
		"canmarksolved" => 1,
		"canmarktechnical" => 1,
		"canseetechnotice" => 1,
		"canassign" => 1,
		"canbeassigned" => 1,
		"cansetpriorities" => 1,
		"canseepriorities" => 1,
		"canmanagesupportdenial" => 1
	);
	$db->update_query("usergroups", $update, "gid IN ('3','4','6')");

	change_admin_permission("config", "mysupport", 1);

	$cache->update_forums();
	$cache->update_usergroups();
	mysupport_cache();
}

function mysupport_do_is_installed()
{
	global $db;

	return $db->table_exists("mysupport");
}

function mysupport_do_uninstall()
{
	global $mybb, $db, $cache, $mysupport_uninstall_confirm_override;

	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when mysupport_uninstall() is called at the start of mysupport_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $mysupport_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			mysupport_table_columns(-1);

			if($db->table_exists("mysupport"))
			{
				$db->drop_table("mysupport");
			}

			$db->delete_query("settinggroups", "name = 'mysupport'");
			$settings = mysupport_setting_names();
			$settings = "'" . implode("','", array_map($db->escape_string, $settings)) . "'";
			// have to use $db->escape_string above instead of around $settings directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("settings", "name IN ({$settings})");

			rebuild_settings();

			mysupport_do_templates(0, false);

			mysupport_stylesheet(-1);

			$cache->update_forums();
			$cache->update_usergroups();
			$db->delete_query("datacache", "title = 'mysupport'");
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;

			$lang->load("config_mysupport");

			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=mysupport&my_post_key={$mybb->post_code}", $lang->mysupport_uninstall_warning);
		}
	}
}

function mysupport_do_activate()
{
	mysupport_template_edits(0);

	mysupport_template_edits(1);

	mysupport_upgrade();
}

function mysupport_do_deactivate()
{
	global $cache;

	mysupport_template_edits(0);

	mysupport_cache("version");
}

// function called upon activation to check if anything needs to be upgraded
// upgrade process is deactivate, upload new files, activate - this function checks for the old version upon re-activation and performs any necessary upgrades
// if settings/templates need to be added/edited/deleted, it'd be taken care of here
// would also deal with any database changes etc
function mysupport_upgrade()
{
	global $mybb, $db, $cache;

	$mysupport_cache = $cache->read("mysupport");
	$old_version = $mysupport_cache['version'];
	// legacy
	if(!$old_version)
	{
		$old_version = $cache->read("mysupport_version");
	}

	// only need to run through this if the version has actually changed
	if(!empty($old_version) && $old_version < MYSUPPORT_VERSION)
	{
		// reimport the settings to add any new ones and refresh the current ones
		mysupport_import_settings();

		// remove the current templates, but only the master versions
		mysupport_do_templates(0, true);
		// re-import the master templates
		mysupport_do_templates(1);

		// add any new table columns that don't already exist
		mysupport_table_columns(1);

		mysupport_stylesheet(2);

		$deleted_settings = array();
		$deleted_templates = array();

		// go through each upgrade process; versions are only listed here if there were changes FROM that version to the next
		// it will go through the ones it needs to and make the changes it needs
		if($old_version <= 0.3)
		{
			// made some mistakes with the original table column additions, 3 of the fields weren't long enough... I do apologise
			$db->modify_column("threads", "statusuid", "INT(10) NOT NULL DEFAULT '0'");
			$db->modify_column("users", "deniedsupportreason", "INT(5) NOT NULL DEFAULT '0'");
			$db->modify_column("users", "deniedsupportuid", "INT(10) NOT NULL DEFAULT '0'");
			// maybe 255 isn't big enough for this after all
			$db->modify_column("mysupport", "description", "VARCHAR(500) NOT NULL");
		}
		if($old_version <= 0.4)
		{
			mysupport_insert_task();
			mysupport_stylesheet(1);
			mysupport_recount_technical_threads();
			$query = $db->simple_select("threads", "DISTINCT assign", "assign != '0'");
			while($user = $db->fetch_field($query, "assign"))
			{
				mysupport_recount_assigned_threads($user);
			}
			// there's just a 'mysupport' cache now with other things in it
			$db->delete_query("datacache", "title = 'mysupport_version'");
			// cache priorities and support denial reasons
			mysupport_cache("priorities");
			mysupport_cache("deniedreasons");
			// we need to update the setting of what to log, to include putting threads on hold, but don't change which actions may have logging disabled
			if($mybb->settings['mysupportmodlog'])
			{
				$mybb->settings['mysupportmodlog'] .= ",";
			}
			$mybb->settings['mysupportmodlog'] .= "12";
			$update = array(
				"value" => $db->escape_string($mybb->settings['mysupportmodlog'])
			);
			$db->update_query("settings", $update, "name = 'mysupportmodlog'");
			rebuild_settings();
		}

		if(!empty($deleted_settings))
		{
			$deleted_settings = "'" . implode("','", array_map($db->escape_string, $deleted_settings)) . "'";
			// have to use $db->escape_string above instead of around $deleted_settings directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("settings", "name IN ({$deleted_settings})");

			mysupport_update_setting_orders();

			rebuild_settings();
		}
		if(!empty($deleted_templates))
		{
			$deleted_templates = "'" . implode("','", array_map($db->escape_string, $deleted_templates)) . "'";
			// have to use $db->escape_string above instead of around $deleted_templates directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("templates", "title IN ({$deleted_templates})");
		}

		// now we can update the cache with the new version
		mysupport_cache("version");
		// rebuild the forums and usergroups caches in case anything's changed
		$cache->update_forums();
		$cache->update_usergroups();
	}
}

function mysupport_table_columns($action = 0)
{
	global $db;

	$mysupport_columns = array(
		"forums" => array(
			"mysupport" => array(
				"size" => 1
			),
			"mysupportmove" => array(
				"size" => 1
			),
			"technicalthreads" => array(
				"size" => 5
			)
		),
		"threads" => array(
			"status" => array(
				"size" => 1
			),
			"statusuid" => array(
				"size" => 10
			),
			"statustime" => array(
				"size" => 10
			),
			"onhold" => array(
				"size" => 1
			),
			"bestanswer" => array(
				"size" => 10
			),
			"assign" => array(
				"size" => 10
			),
			"assignuid" => array(
				"size" => 10
			),
			"priority" => array(
				"size" => 5
			),
			"closedbymysupport" => array(
				"size" => 1
			),
			"issupportthread" => array(
				"size" => 1,
				"default" => 1
			)
		),
		"users" => array(
			"assignedthreads" => array(
				"size" => 500,
				"type" => "varchar"
			),
			"deniedsupport" => array(
				"size" => 1
			),
			"deniedsupportreason" => array(
				"size" => 5
			),
			"deniedsupportuid" => array(
				"size" => 10
			),
			"mysupportdisplayastext" => array(
				"size" => 1
			)
		),
		"usergroups" => array(
			"canmarksolved" => array(
				"size" => 1
			),
			"canmarktechnical" => array(
				"size" => 1
			),
			"canseetechnotice" => array(
				"size" => 1
			),
			"canassign" => array(
				"size" => 1
			),
			"canbeassigned" => array(
				"size" => 1
			),
			"cansetpriorities" => array(
				"size" => 1
			),
			"canseepriorities" => array(
				"size" => 1
			),
			"canmanagesupportdenial" => array(
				"size" => 1
			)
		)
	);

	if($action == 2)
	{
		return $mysupport_columns;
	}

	foreach($mysupport_columns as $table => $columns)
	{
		$last = "";
		foreach($columns as $column => $details)
		{
			// this is called when installing or upgrading
			// if installing, all columns get added, if upgrading, it'll add any new columns
			if($action == 1)
			{
				if(!$db->field_exists($column, $table))
				{
					// most of the columns are INT with a default of 0, so only specify type/default in the array above if it's different, else use int/0
					if(!$details['type'])
					{
						$details['type'] = "int";
					}
					if(!$details['default'])
					{
						$details['default'] = 0;
					}
					$last_sql = "";
					if($last)
					{
						$last_sql = " AFTER `" . $db->escape_string($last) . "`";
					}
					$db->add_column($table, $column, $db->escape_string($details['type']) . " (" . $db->escape_string($details['size']) . ") NOT NULL DEFAULT " . $db->escape_string($details['default']) . $last_sql);
				}
				$last = $column;
			}
			// this is called when uninstalling, to remove all columns
			elseif($action == -1)
			{
				if($db->field_exists($column, $table))
				{
					$db->drop_column($table, $column);
				}
			}
		}
	}
}

function mysupport_insert_task()
{
	global $db, $lang;
	
	$lang->load("mysupport");

	include_once MYBB_ROOT . "inc/functions_task.php";
	$new_task = array(
		"title" => $lang->mysupport,
		"description" => $lang->mysupport_task_description,
		"file" => "mysupport",
		"minute" => 0,
		"hour" => 0,
		"day" => "*",
		"month" => "*",
		"weekday" => "*",
		"enabled" => 1,
		"logging" => 1
	);
	$new_task['nextrun'] = fetch_next_run($new_task);
	$db->insert_query("tasks", $new_task);
}

function mysupport_setting_names()
{
	$settings = mysupport_settings_info();
	$setting_names = array();

	foreach($settings as $setting)
	{
		$setting_names[] = $setting['name'];
	}

	return $setting_names;
}

function mysupport_settings_info()
{
	$settings = array();
	$settings[] = array(
		"name" => "enablemysupport",
		"title" => "Global On/Off setting.",
		"description" => "Turn MySupport on or off here.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportdisplaytype",
		"title" => "How to display the status of a thread?",
		"description" => "'Image' will show a red, green, or blue icon depending on whether a thread is unsolved, solved, or marked as technical. If '[Solved]' is selected, the text '[Solved]' will be displayed before the thread titles (or '[Technical]' if marked as such), while not editing the thread title itself. 'Image' is default as it is intended to be clear but unobtrusive. This setting will be overwridden by a user's personal setting if you've let them change it with the setting below; to force the current setting to all current users, <a href='index.php?module=config-mysupport&amp;action=forcedisplaytype'>click here</a>.",
		"optionscode" => "radio
image=Image
text=Text",
		"value" => "image"
	);
	$settings[] = array(
		"name" => "mysupportdisplaytypeuserchange",
		"title" => "Let users change how threads are displayed?",
		"description" => "Do you want to allow users to change how the status is displayed? If yes, they will have a setting in their User CP Options to choose how the status will be shown, which will override the setting you choose above.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportdisplayto",
		"title" => "Who should the status of a thread be shown to?",
		"description" => "This setting enables you to show the statuses of threads globally, only to people who can mark as solved, or to people who can mark as solved and the author of a thread. This means you can only show people the statuses of their own threads (to save clutter for everybody else) or hide them from view completely so users won't even know the system is in place.",
		"optionscode" => "radio
all=Everybody
canmas=Those who can mark as solved
canmasauthor=Those who can mark as solved and the author of the thread",
		"value" => "all"
	);
	$settings[] = array(
		"name" => "mysupportauthor",
		"title" => "Can the author mark their own threads as solved?",
		"description" => "If this is set to Yes, they will be able to mark their own threads as solved even if their usergroup cannot mark threads as solved.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportclosewhensolved",
		"title" => "Close threads when marked as solved?",
		"description" => "Should the thread be closed when it is marked as solved? If the thread gets marked as not solved, the thread will be reopened, provided it was closed by marking it as solved.",
		"optionscode" => "radio
always=Always
option=Optional
never=Never",
		"value" => "never"
	);
	$settings[] = array(
		"name" => "mysupportmoveredirect",
		"title" => "Move Redirect.",
		"description" => "How long to leave a thread redirect in the original forum for? For this to do anything you must have chosen a forum to move threads to, by going to ACP > Configuration > MySupport > General.",
		"optionscode" => "select
none=No redirect
1=1 Day
2=2 Days
3=3 Days
5=5 Days
10=10 days
28=28 days
forever=Forever",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "mysupportunsolve",
		"title" => "Can a user 'unsolve' a thread?",
		"description" => "If a user marks a thread as solved but then still needs help, can the thread author mark it as not solved? <strong>Note:</strong> if the thread was moved when it was originally marked as solved, this will <strong>not</strong> move it back to it's original forum, therefore it is not recommended to allow this if you choose to move a thread when it is solved.",
		"optionscode" => "yesno",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "mysupportbumpnotice",
		"title" => "Show a 'bump notice' for solved threads?",
		"description" => "If a thread is solved, do you want to show a warning to people to make their own thread rather than bumping the thread they're looking at? The message will be in the textarea on the new reply and quick reply box on the showthread page, so can just be removed should the user still choose to bump the thread. The warning will not be shown to the poster of the thread, or staff.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportonhold",
		"title" => "Enable putting threads 'on hold'",
		"description" => "This will enable you to put a thread 'on hold'. This means the thread is pending a reply from the user, or you're waiting for feedback from the user, etc. It is designed to make it easier to see what is being dealt with and what needs attention. This will change the usual not solved/technical status indicator to a yellow indicator instead. This will change automatically; when a thread is replied to it will be placed on hold, and when the thread's creator replies, it will be taken off hold and will show its old status again. A thread can also be manually put on hold or taken off hold at any time. Furthermore, if a user is the last to post in a thread and the thread is put on hold, if the user edits their post, or replies again, even if it auto-merges, it will take the thread off hold again.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportbestanswer",
		"title" => "Enable ability to highlight the best answer?",
		"description" => "When a thread is solved, can the author choose to highlight the best answer in the thread, i.e. the post that solved the thread for them? Only the thread author can do this, it can be undone, and will highlight the post with the 'mysupport_bestanswer_highlight' class in global.css. If this feature is used when a thread has not yet been marked as solved, choosing to highlight a post will mark it as solved as well, provided they have the ability to.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportbestanswerrep",
		"title" => "Reputation on best answer",
		"description" => "This will give a reputation to the poster of the best answer of the thread, unless the user marks one of their own posts as the best answer. The reputation will be linked with the post. Set to 0 or leave blank to disable. <strong>Note:</strong> For this to work, the 'Allow Multiple Reputation' setting must be enabled in the Reputation settings. Unmarking a post as the best answer will remove the reputation.",
		"optionscode" => "text",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupporttechnical",
		"title" => "Enable the 'Mark as Technical' feature?",
		"description"=> "This will mark a thread as requiring technical attention. This is useful if a thread would be better answered by someone with more knowledge/experience than the standard support team. Configurable below.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupporthidetechnical",
		"title" => "'Hide' technical status if cannot mark as technical?",
		"description" => "Do you want to only show a thread as being technical if the logged in user can mark as technical? Users who cannot mark as technical will see the thread as 'Not Solved'. For example, if a moderator can mark threads as technical and regular users cannot, when a thread is marked technical, moderators will see it as technical but regular users will see it as 'Not Solved'. This can be useful if you want to hide the fact the technical threads feature is in use or that a thread has been marked technical.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupporttechnicalnotice",
		"title" => "Where should the technical threads notice be shown?",
		"description" => "If set to global, it will show in the header on every page. If set to specific, it will only show in the relevant forums; for example, if fid=2 has two technical threads, the notice will only show in that forum.",
		"optionscode" => "radio
off=Nowhere (Disabled)
global=Global
specific=Specific",
		"value" => "global"
	);
	$settings[] = array(
		"name" => "enablemysupportassign",
		"title" => "Enable the ability to assign threads?",
		"description" => "If set to yes, you will be able to assign threads to people. They will have access to a list of threads assigned to them, a header notification message, and there's the ability to send them a PM when they are assigned a new thread. All configurable below.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportassignpm",
		"title" => "PM when assigned thread",
		"description" => "Should users receive a PM when they are assigned a thread? They will not get one if they assign a thread to themselves.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportassignsubscribe",
		"title" => "Subscribe when assigned",
		"description" => "Should a user be automatically subscribed to a thread when it's assigned to them? If the user's options are setup to receive email notifications for subscriptions then they will be subscribed to the thread by email, otherwise they will be subscribed to the thread without email.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportpriorities",
		"title" => "Enable the ability to add a priority to threads?",
		"description" => "If set to yes, you will be able to give threads priorities, which will highlight threads in a specified colour on the forum display.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportnotsupportthread",
		"title" => "Enable the ability to mark threads as not support threads?",
		"description" => "There are times when you may have a thread in a support forum which isn't really classed as a support thread, or something that can't be 'solved' per se. The thread would then behave like a normal thread and would not have any of the MySupport options show up in it.",
		"optionscode" => "radio
0=Disabled
1=Enabled - By default, new threads are support threads
2=Enabled - By default, new threads are not support threads",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "enablemysupportsupportdenial",
		"title" => "Enable support denial?",
		"description" => "If set to yes, you will be able to deny support to selected users, meaning they won't be able to make threads in MySupport forums.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportclosewhendenied",
		"title" => "Close all support threads when denied support?",
		"description" => "This will close all support thread made by a user when you deny them support. If you revoke support denial, all threads that were closed will be reopened, and any threads that were already closed will stay closed.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportmodlog",
		"title" => "Add moderator log entry?",
		"description" => "Do you want to log changes to the status of a thread? These will show in the Moderator CP Moderator Logs list. Separate with a comma. Leave blank for no logging.<br /><strong>Note:</strong> <strong>0</strong> = Mark as Not Solved, <strong>1</strong> = Mark as Solved, <strong>2</strong> = Mark as Technical, <strong>4</strong> = Mark as Not Technical, <strong>5</strong> = Add/change assign, <strong>6</strong> = Remove assign, <strong>7</strong> = Add/change priority, <strong>8</strong> = Remove priority, <strong>9</strong> = Add/change category, <strong>10</strong> = Remove category, <strong>11</strong> = Deny support/revoke support denial, <strong>12</strong> = Put thread on/take thread off hold, <strong>13</strong> = Mark thread as support thread/not support thread. <strong>For a better method of managing this setting, <a href=\"index.php?module=config-mysupport&action=general\">click here</a>.</strong>",
		"optionscode" => "text",
		"value" => "0,1,2,4,5,6,7,8,9,10,11,12,13"
	);
	$settings[] = array(
		"name" => "mysupporthighlightstaffposts",
		"title" => "Highlight staff posts?",
		"description" => "This will highlight posts made by staff, using the 'mysupport_staff_highlight' class in global.css.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportthreadlist",
		"title" => "Enable the list of support threads?",
		"description" => "If this is enabled, users will have an option in their User CP showing them all their threads in any forums where the Mark as Solved feature is enabled, and will include the status of each thread.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportstats",
		"title" => "Small stats section on support/technical lists",
		"description" => "This will show a small stats section at the top of the list of support/technical threads. It will show a simple bar and counts of the amount of solved/unsolved/techncial threads.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportrelativetime",
		"title" => "Display status times with a relative date?",
		"description"=> "If this is enabled, the time of a status will be shown as a relative time, e.g. 'X Months, Y Days ago' or 'X Hours, Y Minutes ago', rather than a specific date.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupporttaskautosolvetime",
		"title" => "Task auto-solve cut-off time",
		"description" => "A task will auto-solve threads that have had no posts and no MySupport actions applied on them for a certain period of time; choose that period of time here.",
		"optionscode" => "select
0=Disabled
604800=1 Week
1209600=2 Weeks
1814400=3 Weeks
2419200=1 Month
4838400=2 Months
7257600=3 Months",
		"value" => "2419200"
	);
	$settings[] = array(
		"name" => "mysupporttaskbackup",
		"title" => "Backup MySupport data automatically",
		"description" => "A task will automatically backup all MySupport data to the backups folder in your admin directory. It must be writeable for this to be possible. No more than 3 backups will be stored, older backups will be automatically deleted.",
		"optionscode" => "select
0=Disabled
86400=Every day
259200=Every 3 days
604800=Every week",
		"value" => "604800"
	);
	$settings[] = array(
		"name" => "mysupportpointssystem",
		"title" => "Points System",
		"description" => "Which points system do you want to integrate with MySupport? MyPS and NewPoints are available. If you have another points system you would like to use, choose 'Other' and fill in the new options that will appear.",
		"optionscode" => "select
myps=MyPS
newpoints=NewPoints
other=Other
none=None (Disabled)",
		"value" => "none"
	);
	$settings[] = array(
		"name" => "mysupportpointssystemname",
		"title" => "Custom Points System name",
		"description"=> "If you want to use a points system that is not supported in MySupport by default, put the name of it here. The name is the same as the name of the file for the plugin in <em>./inc/plugins/</em>. For example, if the plugin file was called <strong>mypoints.php</strong>, you would put <strong>mypoints</strong> into this setting.",
		"optionscode" => "text",
		"value" => ""
	);
	$settings[] = array(
		"name" => "mysupportpointssystemcolumn",
		"title" => "Custom Points System database column",
		"description" => "If you want to use a points system that is not supported in MySupport by default, put the name of the column from the users table which stores the number of points here. if you are unsure what to put here, please contact the author of the points plugin you want to use.",
		"optionscode" => "text",
		"value" => ""
	);
	$settings[] = array(
		"name" => "mysupportbestanswerpoints",
		"title" => "Give points to the author of the best answer?",
		"description" => "How many points do you want to give to the author of the best answer? The same amount of points will be removed should the post be removed as the best answer. Leave blank to give none.",
		"optionscode" => "text",
		"value" => ""
	);

	return $settings;
}

/**
 * Import the settings.
**/
function mysupport_import_settings()
{
	global $mybb, $db;

	$settings = mysupport_settings_info();
	$settings_gid = mysupport_settings_gid();

	foreach($settings as $setting)
	{
		// we're updating an existing setting - this would be called during an upgrade
		if(array_key_exists($setting['name'], $mybb->settings))
		{
			// here we want to update the title, description, and options code in case they've changed, but we don't change the value so it doesn't change what people have set
			$update = array(
				"title" => $db->escape_string($setting['title']),
				"description" => $db->escape_string($setting['description']),
				"optionscode" => $db->escape_string($setting['optionscode'])
			);
			$db->update_query("settings", $update, "name = '" . $db->escape_string($setting['name']) . "'");
		}
		// we're inserting a new setting - either we're installing, or upgrading and a new setting's been added
		else
		{
			$insert = array(
				"name" => $db->escape_string($setting['name']),
				"title" => $db->escape_string($setting['title']),
				"description" => $db->escape_string($setting['description']),
				"optionscode" => $db->escape_string($setting['optionscode']),
				"value" => $db->escape_string($setting['value']),
				"gid" => intval($settings_gid)
			);
			$db->insert_query("settings", $insert);
		}
	}

	mysupport_update_setting_orders();

	rebuild_settings();
}

/**
 * Update the display order of settings if settings
**/
function mysupport_update_setting_orders()
{
	global $db;

	$settings = mysupport_setting_names();

	$i = 1;
	foreach($settings as $setting)
	{
		$update = array(
			"disporder" => $i
		);
		$db->update_query("settings", $update, "name = '" . $db->escape_string($setting) . "'");
		$i++;
	}

	rebuild_settings();
}

/**
 * Import or delete templates.
 * When upgrading MySupport we want to be able to keep edited versions of templates. This function allows us to only delete the master copies when deactivating, whilst deleting everything when uninstalling.
 * Basically so that when you upgrade you'd deactivate, it'd delete the master copies, and reactivating would import the new master copies, so your edits would be saved. Same as normal MyBB templates work except it's not done with an upgrade script.
 * Then the edited copies would sit hidden in the database ready for when you activate again.
 *
 * @param int Importing/deleting - 1/0
 * @param bool If $type == 0, are we fully deleting them (uninstalling) or just removing the master copies (deactivating).
**/
function mysupport_do_templates($type, $master_only = false)
{
	global $db;

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	if($type == 1)
	{
		$template_group = array(
			"prefix" => "mysupport",
			"title" => "<lang:mysupport>"
		);
		$db->insert_query("templategroups", $template_group);

		$templates = array();
		$templates[] = array(
			"title" => "mysupport_form",
			"template" => "<form action=\"showthread.php\" method=\"post\" style=\"display: inline;\">
	<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
	<input type=\"hidden\" name=\"action\" value=\"mysupport\" />
	<input type=\"hidden\" name=\"via_form\" value=\"1\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	{\$status_list}
	{\$assigned_list}
	{\$priorities_list}
	{\$categories_list}
	{\$on_hold}
	{\$is_support_thread}
	{\$gobutton}
</form><br />"
		);
		$templates[] = array(
			"title" => "mysupport_form_ajax",
			"template" => "<div class=\"mysupport_showthread_more_box\">
<form action=\"showthread.php\" method=\"post\" style=\"display: inline;\">
	<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
	<input type=\"hidden\" name=\"action\" value=\"mysupport\" />
	<input type=\"hidden\" name=\"via_form\" value=\"1\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\" style=\"width: 250px;\">
		<tr>
			<td class=\"thead\" align=\"center\">
				<strong>{\$lang->mysupport_additional_options}</strong>
			</td>
		</tr>
		{\$status_list}
		{\$assigned_list}
		{\$priorities_list}
		{\$categories_list}
		{\$on_hold}
		{\$is_support_thread}
		<tr>
			<td class=\"tfoot\" align=\"center\">
				<input type=\"submit\" value=\"{\$lang->update}\" /> <input type=\"button\" value=\"{\$lang->close_options}\" onclick=\"mysupport_close_more_box();\" />
			</td>
		</tr>
	</table>
</form>
</div>
<br />"
		);
		$templates[] = array(
			"title" => "mysupport_tab",
			"template" => "<div class=\"mysupport_tab {\$class}\"><a href=\"{\$url}\"{\$onclick}>{\$text}</a></div>"
		);
		$templates[] = array(
			"title" => "mysupport_bestanswer",
			"template" => " <a href=\"{\$mybb->settings['bburl']}/showthread.php?action=bestanswer&amp;pid={\$post['pid']}&amp;my_post_key={\$mybb->post_code}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/{\$bestanswer_img}.gif\" alt=\"{\$bestanswer_alt}\" title=\"{\$bestanswer_title}\" /> {\$bestanswer_desc}</a>"
		);
		$templates[] = array(
			"title" => "mysupport_status_image",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_{\$status_img}.png\" alt=\"{\$status_title}\" title=\"{\$status_title}\" /> "
		);
		$templates[] = array(
			"title" => "mysupport_status_text",
			"template" => "<span class=\"mysupport_status_{\$status_class}\" title=\"{\$status_title}\">[{\$status_text}]</span> "
		);
		$templates[] = array(
			"title" => "mysupport_notice",
			"template" => "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\">
	<tr>
		<td class=\"trow1\" align=\"right\"><a href=\"{\$mybb->settings['bburl']}/{\$notice_url}\"><span class=\"smalltext\">{\$notice_text}</span></a></td>
	</tr>
</table><br />"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_thread",
			"template" => "<tr{\$priority_class}>
	<td class=\"{\$bgcolor}\" width=\"30%\">
		<div>
			<span><a href=\"{\$thread['threadlink']}\">{\$thread['subject']}</a></span>
			<div class=\"author smalltext\">{\$thread['profilelink']}</div>
		</div>
	</td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$thread['forumlink']} <a href=\"{\$mybb->settings['bburl']}/{\$view_all_forum_link}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_arrow_right.gif\" alt=\"{\$view_all_forum_text}\" title=\"{\$view_all_forum_text}\" /></a></td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$status_time}</td>
	<td class=\"{\$bgcolor}\" width=\"20%\" style=\"white-space: nowrap; text-align: right;\">
		<span class=\"lastpost smalltext\">{\$lastpostdate} {\$lastposttime}<br />
		<a href=\"{\$thread['lastpostlink']}\">{\$lang->thread_list_lastpost}</a>: {\$lastposterlink}</span>
	</td>
</tr>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist",
			"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - {\$thread_list_title}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$navigation}
			<td valign=\"top\">
				{\$stats}
				{\$threads_list}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_list",
			"template" => "{\$mysupport_priority_classes}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\" colspan=\"4\"><strong>{\$thread_list_heading}</strong><div class=\"float_right\">{\$threadlist_filter_form}</div></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"30%\"><strong>{\$lang->thread_list_threadauthor}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$lang->forum}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$status_heading}</strong></td>
		<td class=\"tcat\" width=\"20%\" ><strong>{\$lang->thread_list_lastpost}:</strong></td>
	</tr>
	{\$threads}
	{\$view_all}
</table>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_footer",
			"template" => "<tr>
	<td class=\"tfoot\" colspan=\"4\"><a href=\"{\$mybb->settings['bburl']}/{\$view_all_url}\"><strong>{\$view_all}</strong></a></td>
</tr>"
		);
		$templates[] = array(
			"title" => "mysupport_nav_option",
			"template" => "<tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/{\$nav_link}\" class=\"{\$class1} {\$class2}\">{\$nav_text}</a></td></tr>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_stats",
			"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\"><strong>{\$title_text}</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\" width=\"100%\">{\$overview_text}</td>
	</tr>
	<tr>
		<td class=\"trow2\" width=\"100%\">
			<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
				<tr>
					{\$solved_row}
					{\$notsolved_row}
					{\$technical_row}
				</tr>
			</table>
		</td>
	</tr>
</table><br />"
		);
		$templates[] = array(
			"title" => "mysupport_jumpto_bestanswer",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/{\$jumpto_bestanswer_url}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/{\$bestanswer_image}\" alt=\"{\$lang->jump_to_bestanswer}\" title=\"{\$lang->jump_to_bestanswer}\" /></a>"
		);
		$templates[] = array(
			"title" => "mysupport_assigned",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_assigned.png\" alt=\"{\$lang->assigned}\" title=\"{\$lang->assigned}\" />"
		);
		$templates[] = array(
			"title" => "mysupport_assigned_toyou",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/usercp.php?action=assignedthreads\" target=\"_blank\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_assigned_toyou.png\" alt=\"{\$lang->assigned_toyou}\" title=\"{\$lang->assigned_toyou}\" /></a>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_post",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_post_linked",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$post['uid']}&amp;tid={\$post['tid']}\" title=\"{\$denied_text_desc}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}</a>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support",
		       "template" => "<html>
<head>
<title>{\$lang->support_denial}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$modcp_nav}
			<td valign=\"top\">
				{\$deny_support}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_deny",
			"template" => "<form method=\"post\" action=\"modcp.php\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>
			<td class=\"thead\"><strong>{\$deny_support_to}</strong></td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">{\$lang->deny_support_desc}</td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">
				<label for=\"username\">{\$lang->username}</label> <input type=\"text\" name=\"username\" id=\"username\" value=\"{\$username}\" />
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"hidden\" name=\"action\" value=\"supportdenial\" />
				<input type=\"hidden\" name=\"do\" value=\"do_denysupport\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
				{\$deniedreasons}
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"submit\" value=\"{\$lang->deny_support}\" />
			</td>
		</tr>
	</table>
</form>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_list",
			"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"5\">
			<div class=\"float_right\"><a href=\"modcp.php?action=supportdenial&amp;do=denysupport\">{\$lang->deny_support}</a></div>
			<strong>{\$lang->users_denied_support}</strong>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->username}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"30%\"><strong>{\$lang->support_denial_reason}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->support_denial_user}</strong></td>
		<td class=\"tcat\" colspan=\"2\" align=\"center\" width=\"30%\"><strong>{\$lang->controls}</strong></td>
	</tr>
	{\$denied_users}
</table>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_list_user",
			"template" => "<tr>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denied_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"30%\">{\$support_denial_reason}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denier_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$denieduser['support_denied_uid']}\">{\$lang->edit}</a></td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=do_denysupport&amp;uid={\$denieduser['support_denied_uid']}&amp;deniedsupportreason=-1&amp;my_post_key={\$mybb->post_code}\">{\$lang->revoke}</a></td>
</tr>"
		);
		$templates[] = array(
			"title" => "mysupport_usercp_options",
			"template" => "<fieldset class=\"trow2\">
	<legend><strong>{\$lang->mysupport_options}</strong></legend>
	<table cellspacing=\"0\" cellpadding=\"2\">
		<tr>
			<td valign=\"top\" width=\"1\">
				<input type=\"checkbox\" class=\"checkbox\" name=\"mysupportdisplayastext\" id=\"mysupportdisplayastext\" value=\"1\" {\$mysupportdisplayastextcheck} />
			</td>
			<td>
				<span class=\"smalltext\"><label for=\"mysupportdisplayastext\">{\$lang->mysupport_show_as_text}</label></span>
			</td>
		</tr>
	</table>
</fieldset>
<br />"
		);
		$templates[] = array(
			"title" => "mysupport_inline_thread_moderation",
			"template" => "<optgroup label=\"{\$lang->mysupport}\">
	<option disabled=\"disabled\">{\$lang->markas}</option>
	{\$mysupport_solved}
	{\$mysupport_solved_and_close}
	{\$mysupport_technical}
	{\$mysupport_not_technical}
	{\$mysupport_not_solved}
	<option disabled=\"disabled\">{\$lang->hold_status}</option>
	{\$mysupport_onhold}
	{\$mysupport_offhold}
	<option disabled=\"disabled\">{\$lang->assign_to}</option>
	{\$mysupport_assign}
	<option value=\"mysupport_assign_0\">-- {\$lang->assign_to_nobody}</option>
	<option disabled=\"disabled\">{\$lang->priority}</option>
	{\$mysupport_priorities}
	<option value=\"mysupport_priority_0\">-- {\$lang->priority_none}</option>
	<option disabled=\"disabled\">{\$lang->category}</option>
	{\$mysupport_categories}
	<option value=\"mysupport_category_0\">-- {\$lang->category_none}</option>
</optgroup>"
		);
		$templates[] = array(
			"title" => "mysupport_member_profile",
			"template" => "<br />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td colspan=\"2\" class=\"thead\"><strong>{\$lang->mysupport}</strong></td>
	</tr>
	{\$bestanswers}
	{\$denied_text}
</table>"
		);

		foreach($templates as $template)
		{
			$insert = array(
				"title" => $db->escape_string($template['title']),
				"template" => $db->escape_string($template['template']),
				"sid" => "-2",
				"version" => "1600",
				"status" => "",
				"dateline" => TIME_NOW
			);

			$db->insert_query("templates", $insert);
		}
	}
	else
	{
		$db->delete_query("templategroups", "prefix = 'mysupport'");

		$where_sql = "";
		if($master_only)
		{
			$where_sql = " AND sid = '-2'";
		}

		$templates = mysupport_templates();
		$templates = "'" . implode("','", array_map($db->escape_string, $templates)) . "'";
		// have to use $db->escape_string above instead of around $templates directly because otherwise it escapes the ' around the names, which are important
		$db->delete_query("templates", "title IN ({$templates}){$where_sql}");
	}
}

/**
 * Make the template edits necessary for MySupport to work.
 *
 * @param int Activating/deactivating - 1/0
**/
function mysupport_template_edits($type)
{
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

	if($type == 1)
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$multipage}')."#i", '{$multipage}{$mysupport_options}');
		find_replace_templatesets("showthread", "#".preg_quote('{$footer}')."#i", '{$mysupport_js}{$footer}');
		find_replace_templatesets("postbit", "#".preg_quote('trow1')."#i", 'trow1{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('trow2')."#i", 'trow2{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$altbg}')."#i", '{$altbg}{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("showthread", "#".preg_quote('{$thread[\'threadprefix\']}')."#i", '{$mysupport_status}{$thread[\'threadprefix\']}');
		find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$mysupport_tech_notice}{$mysupport_assign_notice}');
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("forumdisplay_thread_rating", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("forumdisplay_thread_modbit", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{$priority_class}');
		find_replace_templatesets("search_results_threads_inlinecheck", "#".preg_quote('{$bgcolor}')."#i", '{$bgcolor}{priority_class}');
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("modcp_nav", "#".preg_quote('{$lang->mcp_nav_modlogs}</a></td></tr>')."#i", '{$lang->mcp_nav_modlogs}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{$lang->ucp_nav_forum_subscriptions}</a></td></tr>')."#i", '{$lang->ucp_nav_forum_subscriptions}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp", "#".preg_quote('{$latest_warnings}')."#i", '{$latest_warnings}<br />{$threads_list}');
		find_replace_templatesets("member_profile", "#".preg_quote('{$profilefields}')."#i", '{$profilefields}{$mysupport_info}');
		find_replace_templatesets("newreply", "#".preg_quote('{$message}</textarea>')."#i", '{$mysupport_solved_bump_message}{$message}</textarea>');
		find_replace_templatesets("showthread_quickreply", "#".preg_quote('</textarea>')."#i", '{$mysupport_solved_bump_message}</textarea>');
		find_replace_templatesets("newthread", "#".preg_quote('{$multiquote_external}')."#i", '{$multiquote_external}{$mysupport_thread_options}');
	}
	else
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_options}')."#i", '', 0);
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_js}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_status}')."#i", '', 0);
		find_replace_templatesets("header", "#".preg_quote('{$mysupport_tech_notice}{$mysupport_assign_notice}')."#i", '', 0);
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread_rating", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread_modbit", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_inlinecheck", "#".preg_quote('{priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("modcp_nav", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp", "#".preg_quote('<br />{$threads_list}')."#i", '', 0);
		find_replace_templatesets("member_profile", "#".preg_quote('{$mysupport_info}')."#i", '', 0);
		find_replace_templatesets("newreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);
		find_replace_templatesets("showthread_quickreply", "#".preg_quote('{$mysupport_solved_bump_message}')."#i", '', 0);
		find_replace_templatesets("newthread", "#".preg_quote('{$mysupport_thread_options}')."#i", '', 0);
	}
}

function mysupport_stylesheet($action = 0)
{
	global $db;

	$stylesheet = ".mysupport_status_solved {
	color: green;
}

.mysupport_status_notsolved {
	color: red;
}

.mysupport_status_technical {
	color: blue;
}

.mysupport_status_onhold {
	color: yellow;
}

.mysupport_tabs {
	margin: 20px auto;
}

.mysupport_tab {
	text-align: center;
	padding: 5px;
	display: inline;
}

.mysupport_tab_solved {
	background: #D6ECA6;
	border: 2px solid #009900;
	color: #009900;
	font-weight: bold;
}

.mysupport_tab_solved a {
	color: #009900;
}

.mysupport_tab_not_solved {
	background: #FFE4E1;
	border: 2px solid #CD0000;
	color: #CD0000;
	font-weight: bold;
}

.mysupport_tab_not_solved a {
	color: #CD0000;
}

.mysupport_tab_technical {
	background: #ADCBE7;
	border: 2px solid #0F5C8E;
	color: #0F5C8E;
	font-weight: bold;
}

.mysupport_tab_technical a {
	color: #0F5C8E;
}

.mysupport_tab_hold {
	background: #FFF6BF;
	border: 2px solid #FFB90F;
	color: #FFB90F;
	font-weight: bold;
}

.mysupport_tab_hold a {
	color: #FFB90F;
}

.mysupport_tab_best_answer {
	background: #D6ECA6;
	border: 2px solid #8DC93E;
	color: #8DC93E;
	font-weight: bold;
}

.mysupport_tab_best_answer a {
	color: #8DC93E;
}

.mysupport_tab_misc {
	background: #EFEFEF;
	border: 2px solid #555555;
	color: #555555;
	font-weight: bold;
}

.mysupport_tab_misc a {
	color: #555555;
}

.mysupport_showthread_more_box {
	width: 250px;
	position: fixed;
	top: 25%;
	left: 50%;
	margin-left: -125px;
	z-index: 1000;
}

.mysupport_bar_solved {
	background: green;
	height: 10px;
}

.mysupport_bar_notsolved {
	background: red;
	height: 10px;
}

.mysupport_bar_technical {
	background: blue;
	height: 10px;
}

.mysupport_bestanswer_highlight {
	background: #D6ECA6;
}

.mysupport_staff_highlight {
	background: #E6E8FA;
}

.usercp_nav_support_threads {
	background: url(images/usercp/mysupport_support.png) no-repeat left center;
}

.usercp_nav_assigned_threads {
	background: url(images/usercp/mysupport_assigned.png) no-repeat left center;
}

.modcp_nav_tech_threads {
	background: url(images/modcp/mysupport_technical.png) no-repeat left center;
}

.modcp_nav_deny_support {
	background: url(images/mysupport_no_support.gif) no-repeat left center;
}";

	if($action == 1)
	{
		$insert = array(
			"name" => "mysupport.css",
			"tid" => 1,
			"attachedto" => "showthread.php|forumdisplay.php|usercp.php|usercp2.php|modcp.php",
			"stylesheet" => $stylesheet,
			"lastmodified" => TIME_NOW
		);
		$sid = $db->insert_query("themestylesheets", $insert);

		$update = array(
			"cachefile" => "css.php?stylesheet=" . intval($sid)
		);
		$db->update_query("themestylesheets", $update, "sid = '{$sid}'");
	}
	elseif($action == 2)
	{
		$query = $db->simple_select("themestylesheets", "sid", "name = 'mysupport.css' AND tid = '1'");
		$sid = $db->fetch_field($query, "sid");

		$update = array(
			"stylesheet" => $stylesheet,
			"lastmodified" => TIME_NOW
		);
		$db->update_query("themestylesheets", $update, "sid = '" . intval($sid) . "'");
	}
	elseif($action == -1)
	{
		$db->delete_query("themestylesheets", "name = 'mysupport.css'");
	}

	if($action == 1 || $action == -1)
	{
		$query = $db->simple_select("themes", "tid");
		require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
		while($tid = $db->fetch_field($query, "tid"))
		{
			update_theme_stylesheet_list($tid);
		}
	}
}
?>
