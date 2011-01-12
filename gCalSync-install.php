<?php
/************************************************************************
* gCalSync-install.php							*
*************************************************************************
* gCalSync 								*
* Copyright 2009-2011 Armen Kaleshian <armen@kriation.com>		*
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

/*
 * A number of interesting things are done in here to provide the proper
 * environment for gCalSync to work properly.
 * There are a number of database modifications/additions which are listed 
 * below:
 * 
 * Modification
 * ------------
 * Table: smf_settings
 * gUser == This is the user account that will be used to connect to Google.
 * gPass == This is the above user account's password.
 * gCal_list == An array to hold onto the list of calendars available to 
 *		gCalSync
 * gCal_calID == Calendar ID to sync to
 *
 * Addition
 * --------
 * Table: smf_gCal
 * Columns:
 * 	ID_EVENT == Corresponds to the event ID from smf_calendar
 *	gCal_ID == This is the Google calendar entry ID returned from Google
 *	when creating a new calendar entry. This value is also 
 *	used to edit the entry once it has been created.
 *
 * TODO
 * ----
 * > Add functionality to synchronize pre-existing entries with Google 
 *	during the setup process
 * > Add functionality to input Google User/Pass during the setup process
 * > Encrypt Google Password
*/

// Check for PHP version compatibility for gCalSync due to Zend needing v5
if ( substr( phpversion(), 0, 1 ) < 5 )
{
    fatal_error( 'gCalSync: Requires PHP v5 or above.' );
}

// Adding the additional parameters to smf_settings
$new_settings = array( 
		'gCal_user' => '',
		'gCal_pass' => '', 
		'gCal_list' => '',
		'gCal_calID' => ''
		);

// Checking where this is being called from
if( file_exists( dirname( __FILE__ ) . '/SSI.php' ) && !defined( 'SMF' ) )
{
	require_once( dirname( __FILE__ ) . '/SSI.php' );
}
elseif( !defined( 'SMF' ) )
{
	die( 'SMF not defined.' );
}

// Fix the array into a database compliant form factor
$string = '';
foreach( $new_settings as $x => $y )
{
	// This is so bad, but it works! :)
    	// Retract the above statement; this is genius.
	$string .= '(\'' . $x . '\', \'' . $y . '\'),';
}

// Insert the data into the database (cross fingers 'Y')
if( $string != '' )
{
	/* Insert from the above manufactured string, removing the comma
	 * after each set of name,value pairs within the parantheses.
	*/
	$result = db_query( "INSERT IGNORE INTO {$db_prefix}settings
				(variable, value) VALUES" .
				substr( $string, 0, -1 ), 
				__FILE__, __LINE__ );
	if( !$result )
	{
		fatal_error( 'gCalSync: New settings insertion failed!' );
	}
}
else
{
	fatal_error( 'gCalSync: String was empty!!' );
}


// As long as things went well up there, let's add the new table! :)
$result = db_query( "CREATE TABLE IF NOT EXISTS {$db_prefix}gCal ( 
			`ID_EVENT` smallint(5) unsigned NOT NULL, 
			`gCal_ID` tinytext NOT NULL,
			PRIMARY KEY (`ID_EVENT`) )",
		   	__FILE__, __LINE__ ); 

if( !$result )
{
	fatal_error( 'gCalSync: Table creation failed!' );
}
?>
