<?php
/**
 * MySupport 0.4 - Task File

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

function task_mysupport($task)
{
	global $mybb, $db, $lang;

	$lang->load("mysupport");

	$task_log = $lang->task_mysupport_ran;

	// if this is empty or 0 it'll effect all threads
	if($mybb->settings['mysupporttaskautosolvetime'] > 0)
	{
		$cut = TIME_NOW - intval($mybb->settings['mysupporttaskautosolvetime']);
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));

		// are there any MySupport forums?
		if(!empty($mysupport_forums))
		{
			// select all the unsolved threads in MySupport forums where the last post was before the cut-off time, and either the status time is before the cut-off time, or the status of the thread has never been changed
			// this means it's not been posted in, and no MySupport actions have taken place on it, within the cut-off time
			$query = $db->simple_select("threads", "tid", "status != '1' AND fid IN (" . $db->escape_string($mysupport_forums) . ") AND lastpost < '" . intval($cut) . "' AND (statustime < '" . intval($cut) . "' OR statustime = '0')");
			$tids = array();
			while($thread = $db->fetch_array($query))
			{
				$tids[] = $thread['tid'];
			}

			$threads_solved = false;
			// if there are any threads to mark as solved
			if(!empty($tids))
			{
				mysupport_change_status($tids, 1, true);
				$threads_solved = true;
			}
		}

		if($threads_solved)
		{
			$task_log .= $lang->sprintf($lang->task_mysupport_autosolve_count, count($tids));
		}
	}

	if($mybb->settings['mysupporttaskbackup'] > 0)
	{
		$timecut = TIME_NOW - $mybb->settings['mysupporttaskbackup'];
		$query = $db->simple_select("mysupport", "*", "type = 'backup' AND extra > '" . intval($timecut) . "'");
		// no backups have been made within the cut off time
		if($db->num_rows($query) == 0)
		{
			if(!defined('MYBB_ADMIN_DIR'))
			{
				if(!isset($config['admin_dir']))
				{
					$config['admin_dir'] = "admin";
				}

				define('MYBB_ADMIN_DIR', MYBB_ROOT.$config['admin_dir'] . "/");
			}

			if(is_writable(MYBB_ADMIN_DIR . "backups"))
			{
				$name = substr(md5($mybb->user['uid'] . TIME_NOW), 0, 10) . random_str(54);
				$file = MYBB_ADMIN_DIR . "backups/mysupport_backup_" . $name . ".sql";

				$f = @fopen($file, "w");
				@fwrite($f, "<?php\n");
				@fwrite($f, "/**\n * Backup of MySupport data\n * Generated: " . date("dS F Y \a\\t H:i", TIME_NOW) . "\n * Only to be imported via the MySupport backup importer.\n**/\n\n");

				require_once MYBB_ROOT . "inc/plugins/mysupport/mysupport.php";

				$mysupport_columns = mysupport_table_columns(2);

				foreach($mysupport_columns as $table => $columns)
				{
					switch($table)
					{
						case "forums":
							$id_field = "fid";
							break;
						case "threads":
							$id_field = "tid";
							break;
						case "users":
							$id_field = "uid";
							break;
						case "usergroups":
							$id_field = "gid";
							break;
					}

					$columns = implode(", ", array_map($db->escape_string, array_keys($columns)));
					$query = $db->simple_select($table, $id_field . "," . $columns);
					$columns = explode(", ", $columns);
					while($r = $db->fetch_array($query))
					{
						$set = "";
						foreach($columns as $column)
						{
							if(!empty($set))
							{
								$set .= ", ";
							}
							$set .= "`" . $column . "` = '" . $r[$column] . "'";
						}
						$q = "\$queries[] = \"UPDATE " . TABLE_PREFIX . $table . " SET " . $set . " WHERE `" . $id_field . "` = '" . $r[$id_field] . "'\";\n";
						@fwrite($f, $q);
					}
				}
				$query = $db->simple_select("mysupport");
				while($r = $db->fetch_array($query))
				{
					$keys = array();
					$vals = array();
					foreach($r as $key => $val)
					{
						$keys[] = "`" . $key . "`";
						$vals[] = "'" . $val . "'";
					}
					$q = "\$queries[] = \"INSERT INTO " . TABLE_PREFIX . "mysupport (" . implode(",", $keys) . ") VALUES (" . implode(",", $vals) . ")\";\n";
					@fwrite($f, $q);
				}

				@fwrite($f, "?>");
				@fclose($f);

				$insert = array(
					"type" => "backup",
					"name" => $db->escape_string($name),
					"extra" => TIME_NOW
				);
				$db->insert_query("mysupport", $insert);

				// get the latest 3 backups
				$query = $db->simple_select("mysupport", "mid", "type = 'backup'", array("order_by" => "extra", "order_dir" => "DESC", "limit" => 3));
				$backups = array(0);
				while($backup = $db->fetch_field($query, "mid"))
				{
					$backups[] = $backup;
				}
				$backups = implode(",", array_map("intval", $backups));

				// select all the backups that aren't the last 3
				$query = $db->simple_select("mysupport", "mid, name", "type = 'backup' AND mid NOT IN (" . $db->escape_string($backups) . ")");
				while($backup = $db->fetch_array($query))
				{
					if(file_exists(MYBB_ADMIN_DIR . "backups/mysupport_backup_" . $backup['name'] . ".sql"))
					{
						@unlink(MYBB_ADMIN_DIR . "backups/mysupport_backup_" . $backup['name'] . ".sql");
					}
					$db->delete_query("mysupport", "mid = '" . intval($backup['mid']) . "'");
				}

				$task_log .= " " . $lang->task_mysupport_backup_ran;
			}
		}
	}

	if(!empty($task_log))
	{
		add_task_log($task, $task_log);
	}

	/*
	SELECT `t`.`tid`, `t`.`subject`, `t`.`fid`, `f`.`name`, `t`.`status`, `t`.`statusuid`, `u1`.`username` AS `statusuid_username`, `t`.`statustime`, `t`.`bestanswer`, `t`.`assign`, `u2`.`username` AS `assign_username`, `t`.`assignuid`, `u3`.`username` AS `assignuid_username`, `t`.`priority`, `m`.`name` AS `priority_name`, `t`.`prefix`, `tp`.`prefix` AS `prefix_name`
	FROM `mybb_threads` `t`
	LEFT JOIN `mybb_forums` `f` ON `t`.`fid` = `f`.`fid`
	LEFT JOIN `mybb_threadprefixes` `tp` ON `t`.`prefix` = `tp`.`pid`
	LEFT JOIN `mybb_users` `u1` ON `t`.`statusuid` = `u1`.`uid`
	LEFT JOIN `mybb_users` `u2` ON `t`.`assign` = `u2`.`uid`
	LEFT JOIN `mybb_users` `u3` ON `t`.`assignuid` = `u3`.`uid`
	LEFT JOIN `mybb_mysupport` `m` ON `t`.`priority` = `m`.`mid`
	WHERE CONCAT(',', f.parentlist, ',') LIKE '%,1,%'
	AND `t`.`closed` NOT LIKE 'moved|%'
	ORDER BY `t`.`tid` ASC;
	*/
}
?>
