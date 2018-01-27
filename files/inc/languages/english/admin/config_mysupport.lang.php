<?php
/**
 * MySupport 0.4 - Admin Language File
 * � MattRogowski 2010
 * http://mattrogowski.co.uk
 * You may edit for your own personal forum but must not redistribute it in any form without my permission.
**/

$l['mysupport'] = "MySupport";
$l['solved'] = "Solved";
$l['not_solved'] = "Not Solved";
$l['technical'] = "Technical";
$l['thread'] = "Thread";
$l['forum'] = "Forum";
$l['started_by'] = "Started by";
$l['status'] = "Status";

$l['mysupport_uninstall_warning'] = "Are you sure you want to uninstall MySupport? You will permanently lose all thread statuses.";

$l['home'] = "Home";
$l['home_header'] = "General Information";
$l['home_nav'] = "Basic information on MySupport.";
$l['general'] = "General";
$l['general_header'] = "General Configuration";
$l['general_nav'] = "Manage general settings such as which forums to enable MySupport in, and set permissions for usergroups.";
$l['technical_assign'] = "Technical/Assigning Threads";
$l['technical_assign_nav'] = "Manage settings for marking threads as technical/assigning threads.";
$l['technical_header'] = "Technical Threads Configuration";
$l['assign_header'] = "Assigning Threads Configuration";
$l['categories'] = "Categories";
$l['priorities'] = "Priorities";
$l['priorities_current'] = "Current Priorities";
$l['priorities_header'] = "Priority Configuration";
$l['priorities_thread_list_header'] = "Threads with a priority of <em>'{1}'</em>.";
$l['priorities_add'] = "Add a Priority";
$l['priorities_edit'] = "Edit a Priority";
$l['priorities_nav'] = "Manage what priorities you can give to threads.";
$l['support_denial'] = "Support Denial";
$l['support_denial_header'] = "Support Denial Configuration";
$l['support_denial_nav'] = "Manage reasons for denying support and who is denied support.";
$l['support_denial_reason_current'] = "Current Reasons";
$l['support_denial_reason_users'] = "Users denied support";
$l['support_denial_reason_add'] = "Add a reason";
$l['support_denial_reason_edit'] = "Edit a reason";
$l['support_denial_reason_edit_user'] = "Edit a user";
$l['mysupport_settings'] = "Settings";

$l['support_threads'] = "Support Threads Overview";
$l['support_threads_total'] = "<strong>Total Support Threads:</strong> {1}";
$l['support_threads_solved'] = "<strong>Solved:</strong> {1} ({2})";
$l['support_threads_unsolved'] = "<strong>Unsolved:</strong> {1} ({2})";
$l['support_threads_new'] = "<strong>Support Threads today:</strong> {1}";
$l['technical_threads_total'] = "<strong>Technical Threads:</strong> {1}";
$l['technical_threads_new'] = "<strong>Technical Threads today:</strong> {1}";
$l['assigned_threads_total'] = "<strong>Assigned Threads:</strong> {1}";
$l['assigned_threads_new'] = "<strong>Assigned Threads today:</strong> {1}";
$l['mysupport_name'] = "Name";
$l['mysupport_description'] = "Description";
$l['mysupport_view_threads'] = "View Threads";

$l['mysupport_forums'] = "Where to enable MySupport?";
$l['mysupport_move_forum'] = "Where to move threads when solved?";
$l['mysupport_move_forum_desc'] = "<strong>Note:</strong> if a thread is moved when it is marked as solved, 'unsolving' the thread will <strong>not</strong> move the thread back to it's original forum.";
$l['mysupport_canmarksolved'] = "Who can mark threads as solved?";
$l['mysupport_canmarktechnical'] = "Who can mark threads as technical?";
$l['mysupport_canseetechnotice'] = "Who can see the technical threads notice?";
$l['mysupport_canassign'] = "Who can assign threads?";
$l['mysupport_canbeassigned'] = "Who can be assigned threads?";
$l['mysupport_cansetpriorities'] = "Who can set priorities?";
$l['mysupport_canseepriorities'] = "Who can see priorities?";
$l['mysupport_cansetcategories'] = "Who can set categories?";
$l['mysupport_canmanagesupportdenial'] = "Who can manage support denial?";
$l['mysupport_what_to_log'] = "What actions to log?";
$l['mysupport_what_to_log_desc'] = "What MySupport actions should have a moderator entry created? This is an alias of the 'Add moderator log entry' setting in the normal MySupport settings; updating here will update that setting and vice versa.";

$l['can_manage_mysupport'] = "Can manage MySupport";

$l['categories_prefixes_redirect'] = "Use the Thread Prefixes feature to create categories to be used with MySupport.";

$l['mysupport_submit'] = "Save MySupport Settings";
$l['mysupport_add_priority_submit'] = "Add Priority";
$l['mysupport_edit_priority_submit'] = "Edit Priority";
$l['mysupport_add_support_denial_reason_submit'] = "Add Reason";
$l['mysupport_edit_support_denial_reason_submit'] = "Edit Reason";
$l['mysupport_edit_user_submit'] = "Edit user";
$l['success_general'] = "General MySupport settings updated.";
$l['error_general_move_forum'] = "Invalid forum to move threads to. Please select a forum, not a category.";
$l['success_technical'] = "Technical threads settings updated.";
$l['success_assign'] = "Assigning threads settings updated.";
$l['success_priorities'] = "Priority settings updated.";
$l['priority_added'] = "Priority successfully added.";
$l['priority_deleted'] = "Priority successfully deleted.";
$l['priority_delete_confirm'] = "Are you sure you want to delete this priority?";
$l['priority_delete_confirm_count'] = "It is currently being used by {1} threads.";
$l['priority_edited'] = "Priority successfully edited.";
$l['priority_invalid'] = "Invalid priority.";
$l['priority_no_name'] = "Please enter a name for this priority.";
$l['priorities_thread_list_none'] = "There are no threads with a priority of <em>'{1}'</em>.";
$l['priority_style'] = "Style";
$l['priority_style_description'] = "Enter the HEX code of the colour to highlight threads with this priority. This colour will be used to highlight threads on the forum display pages. If a thread has been unapproved, this colour will not override the unapproved colour.";
$l['success_support_denial'] = "Support denial settings updated.";
$l['support_denial_reason_added'] = "Reason successfully added.";
$l['support_denial_reason_deleted'] = "Reason successfully deleted.";
$l['support_denial_reason_edited'] = "Reason successfully edited.";
$l['support_denial_reason_no_name'] = "Please enter a name for this reason.";
$l['support_denial_reason_no_description'] = "Please enter a description for this reason.";
$l['support_denial_reason_invalid'] = "Invalid reason.";
$l['support_denial_reason_delete_confirm'] = "Are you sure you want to delete this reason?";
$l['support_denial_reason_delete_confirm_count'] = "It has been given as a reason to {1} user(s). Deleting this reason will simply not show any reason to this user, it won't allow them to receive support again.";
$l['support_denial_reason_description_description'] = "This is what will be displayed to a user when they are denied support.";

$l['mysupport_display_style_forced'] = "Successfully forced the current status display style to current users.";

$l['mysupport_mod_log_action_0'] = "Mark as Not Solved";
$l['mysupport_mod_log_action_1'] = "Mark as Solved";
$l['mysupport_mod_log_action_2'] = "Mark as Technical";
$l['mysupport_mod_log_action_4'] = "Mark as Not Technical";
$l['mysupport_mod_log_action_5'] = "Add/change assign";
$l['mysupport_mod_log_action_6'] = "Remove assign";
$l['mysupport_mod_log_action_7'] = "Add/change priority";
$l['mysupport_mod_log_action_8'] = "Remove priority";
$l['mysupport_mod_log_action_9'] = "Add/change category";
$l['mysupport_mod_log_action_10'] = "Remove category";
$l['mysupport_mod_log_action_11'] = "Deny support/revoke support";
$l['mysupport_mod_log_action_12'] = "Put thread on/take thread off hold";
$l['mysupport_mod_log_action_13'] = "Mark as support thread/not support thread";
?>
