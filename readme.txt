Name: MySupport
Description: Add features to your forum to help with giving support. Allows you to mark a thread as solved or technical, assign threads to users, give threads priorities, mark a post as the best answer in a thread, and more to help you run a support forum.
Website: http://mattrogowski.co.uk
Author: MattRogowski
Authorsite: http://mattrogowski.co.uk
Version: 0.4
Compatibility: 1.6.x
Files: 4 (plus 13 images)
Templates added: 22
Template changes: 21
Settings added: 26
Database changes: 1 new table, 21 new columns to 4 default tables.

To Install:
Upload ./inc/plugins/mysupport.php to ./inc/plugins/
Upload ./admin/modules/config/mysupport.php to ./admin/modules/config/
Upload ./inc/languages/english/mysupport.lang.php to ./inc/languages/english/
Upload ./inc/languages/english/admin/config_mysupport.lang.php to ./inc/languages/english/admin/
Open ./files/mysupport_css_additions.css and add the code inside to the bottom of global.css for your themes, by going to ACP > Templates & Style > **choose theme** > global.css > Edit Stylesheet: Advanced Mode > scroll down and add the CSS to the bottom.
Go to ACP > Templates & Style > Templates > **expand template set** > User Control Panel Templates > usercp_options > find:
	<br />
	<fieldset class="trow2">
	<legend><strong>{$lang->other_options}</strong></legend>
change to:
	<br />
	{$mysupport_usercp_options}
	<fieldset class="trow2">
	<legend><strong>{$lang->other_options}</strong></legend>
Go to ACP > Plugins > Install and Activate
Go to ACP > Configuration > MySupport Settings > Configure Settings.
Go to ACP > Configuration > MySupport (left menu) > setup where MySupport can be used and who can use it.

Information:
This plugin will add multiple support features to your forum.

* Choose which forums to enable MySupport in.
* Mark threads as solved.
* Mark threads as technical.
 ** Alert of technical threads in header.
 ** List of technical threads in Mod CP.
 ** Option to hide a technical status from people who can't mark as technical; display technical threads as simply not solved to regular users.
* Display the status of threads as either an image, or as text. Configurable on a per-user basis as well as a global basis.
* Assign threads.
 ** Alert of assigned threads in header.
 ** List of assigned threads in User CP.
 ** Icon on forum display to see what threads have been assigned and what threads are assigned to you.
 ** PM/subscribe to thread when assigned.
* Give threads priorities.
 ** Highlighted on forum display in colour representing priority.
* Mark the best answer in the thread.
 ** Only thread author able to do this.
 ** Highlights the best answer and includes quick access link to jump straight to best answer, both at the top of the thread and on the forum display.
* List of users' support threads in User CP.
* Highlight staff responses.
* Deny users support.
 ** Configurable reasons.
 ** Unable to make threads in MySupport forum.
* Configure a points system to receive points for MySupport actions.
 ** Receive points for having a post marked as the best answer.

Change Log:
01/09/10 - v0.1 -> Initial beta release.
03/09/10 - v0.1 -> v0.2 -> Setting added to stop users choosing how thread statuses are displayed, and also added the ability to force a display style on current users. Fixed a bug where support/assigned/technical threads lists would show incorrect/irrelevant threads. To upgrade, deactivate MySupport, reupload ./inc/plugins/mysupport.php, ./admin/modules/config/mysupport.php, and ./inc/languages/english/admin/config_mysupport.lang.php, activate MySupport.
08/09/10 - v0.2 -> v0.3 -> Few code improvements. Added text to best answer/support denial links in posts. Implemented inline thread moderation; add/change status/assigned user/priority/category of multiple threads via the forum display/search results. Fixed bug where mod log actions would be double escaped, and where MySupport information shown in search results wouldn't be unset properly, causing problems if the forum of the next thread in the list wasn't a MySupport forum. To upgrade, deactivate MySupport, reupload ./inc/plugins/mysupport.php and ./inc/languages/english/mysupport.lang.php, activate MySupport.
28/09/10 - v0.3 -> v0.4 -> Tweaked display of best answer and support denial links in posts. Doesn't show status/assign info for sticky threads. Bugs fixed: Some table columns were too small; breadcrumb/table header would be incomplete on support denial page if no UID was given; inline thread moderation options would show even if MySupport wasn't enabled in that forum, would show in search results if no forums had MySupport enabled, and didn't check if selected threads were in MySupport forums when selected via inline thread moderation on search results; priorities with a space in the name wouldn't highlight the thread on the forum display; best answer/staff highlighting would override the unapproved post shade; support denial link would show in the posts of staff; list of support threads would load unapproved threads; wouldn't be able to deny support with a reason if you had more than one reason specified; list of threads with a particular priority in the ACP wouldn't load the correct threads. Missed htmlspecialchars_uni() in an ACP table and the priority classes. To upgrade, deactivate MySupport, reupload ./inc/plugins/mysupport.php, ./admin/modules/config/mysupport.php, ./inc/languages/english/mysupport.lang.php, and ./inc/languages/english/admin/config_mysupport.lang.php, activate MySupport.

Copyright 2010 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.