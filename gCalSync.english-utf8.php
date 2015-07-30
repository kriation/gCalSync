<?php
/************************************************************************
* gCalSync.english-utf8.php						                        *
*************************************************************************
* gCalSync                                                              *
* Copyright 2009-2015 Armen Kaleshian <armen@kriation.com>              *
* License: GNU GPL (v3 or later). See LICENSE.txt for details.          *
*                                                                       *
* An enhancement for SMF to synchronize forum calendar entries with a   *
* Google Calendar.                                                      *
* ********************************************************************* *
* This program is free software: you can redistribute it and/or modify  *
* it under the terms of the GNU General Public License as published by  *
* the Free Software Foundation, either version 3 of the License, or     *
* (at your option) any later version.                                   *
*                                                                       *
* This program is distributed in the hope that it will be useful,       *
* but WITHOUT ANY WARRANTY; without even the implied warranty of        *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
* GNU General Public License for more details.                          *
*                                                                       *
* You should have received a copy of the GNU General Public License     *
* along with this program.  If not, see <http://www.gnu.org/licenses/>. *
************************************************************************/

global $settings, $scripturl;

$txt['gcal_sec_include'] = 'Use included Google application project?';
$txt['gcal_sec'] = 'Google Secret from Developer\'s Console';
$txt['gcal_auth'] = 'Google Authentication Code';
$txt['gcal_calid'] = 'Google Calendar for gCalSync\'s use';
$txt['desc_oauth_url'] = 'The link below is dynamically generated based on the JSON string inputted in the above box. By clicking, you\'ll kick off the Google oAuth process. Please authenticate with the Google account associated with the calendar that you would like to sync to. Once you have completed authentication, you\'ll be presented with an authentication code that you are required to paste in the Google Authentication Code text box above to complete the association process.';
$txt['msg_gcal_admin'] = 'This page is used to configure gCalSync.';
$txt['msg_google_calendar'] = 'Please choose a Google Calendar from the dropdown list for gCalSync to use, and click save.';
$txt['msg_google_success'] = 'Association with Google complete!';
$txt['title_gcal_admin'] = 'gCalSync Administration';

?>
