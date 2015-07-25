<?php
/************************************************************************
* gCalSync-uninstall.php						*
*************************************************************************
* gCalSync 								*
* Copyright 2009-2015 Armen Kaleshian <armen@kriation.com>		*
* License: GNU GPL (v3 or later). See LICENSE.txt for details.		*
*									*
* An enhancement for SMF to synchronize forum calendar entries with a	*
* Google Calendar.							*
* ********************************************************************* *
* This program is free software: you can redistribute it and/or modify	*
* it under the terms of the GNU General Public License as published by	*
* the Free Software Foundation, either version 3 of the License, or	*
* (at your option) any later version.					*
*									*
* This program is distributed in the hope that it will be useful,	*
* but WITHOUT ANY WARRANTY; without even the implied warranty of	*
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the		*
* GNU General Public License for more details.				*
*									*
* You should have received a copy of the GNU General Public License	*
* along with this program.  If not, see <http://www.gnu.org/licenses/>.	*
************************************************************************/

if ( !defined( 'SMF' ) )
    die( 'Hacking attempt...' );

// List of gCalSync parameters to remove from settings
$gcalsync_settings = array( 
    'gcal_sec', 
    'gcal_auth', 
    'gcal_list', 
    'gcal_calid');

foreach ( $gcalsync_settings as $setting )
{
    // Remove the gCalSync settings from the settings table
    $smcFunc['db_query']('', ' 
	DELETE FROM {db_prefix}settings
	WHERE variable = {string:gcalsync_setting}',
	array( 'gcalsync_setting' => $setting )
    );
}

// Without this definition, we don't have access to db_drop_table
db_extend('packages');

// Drop gCalSync table
$smcFunc['db_drop_table']( 'gcalsync' );

// Database is cleansed. Remove hooks
remove_integration_function(   'integrate_pre_include',
				'$sourcedir/gCalSync.php' );
remove_integration_function(   'integrate_admin_include',
				'$sourcedir/gCalSync-admin.php');
remove_integration_function(   'integrate_admin_areas',
				'add_gCalSync_menu' );
remove_integration_function(   'integrate_modify_modifications',
				'add_gCalSync_admin' );
?>
