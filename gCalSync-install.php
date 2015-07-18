<?php
/************************************************************************
* gCalSync-install.php							*
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

// List of gCalSync parameters to add to settings
$gcalsync_settings = array( 
    'gcal_sec' => '',
    'gcal_auth' => '', 
    'gcal_list' => '',
    'gcal_calid' => '');

foreach ( $gcalsync_settings as $x => $y )
{
    // Insert the new settings for gCalSync to the settings table
    $smcFunc['db_insert'](
	'ignore',
	'{db_prefix}settings',
	array( 'variable' => 'string', 'value' => 'string' ),
	array( $x, $y ),
	array( 'variable' )
    );
}

// Without this definition, we don't have acces to db_create_table
db_extend('packages');

// Create new table for gCalSync
$smcFunc['db_create_table']( 
    'gcalsync',
    array(
	array(
	    'name' => 'id_event',
	    'type' => 'smallint',
	    'null' => 'false' ),
	array(
	    'name' => 'id_google_entry',
	    'type' => 'varchar',
	    'null' => 'false',
	    'size' => 40 )
    ),
    array(
	array(
	    'name' => 'gcalsync',
	    'columns' => array( 'id_event' ),
	    'type' => 'primary')
    )
)
?>
