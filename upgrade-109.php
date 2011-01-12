<?php
/************************************************************************
* upgrade-109.php							*
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
 * This file is responsible for upgrading the DB associated with SMF to the
 * newest version of gCalSync.
 *
 * To facilitate the feature of calendar selection, I added two variables
 * in the settings table to keep track of the list of calendars available
 * to gCalSync, and another to keep track of the selected calendar. They
 * are the following, respectively:
 * gCal_list 
 * gCal_calID
*/

// Let's do the work!
$new_settings = array (
	'gCal_list' => '',
	'gCal_calID' => ''
	);

// The rest of this is borrowed from the original gCalSync-install.php

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
?>
