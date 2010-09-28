<?php
/************************************************************************
* gCalSync-uninstall.php						*
*************************************************************************
* gCalSync 								*
* Copyright 2009-2010 Armen Kaleshian <armen@kriation.com>		*
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

/* TODO:
 * 1) Backup the smf_gCal table containing the ID links
*/

// Checking where this is being called from
if( file_exists( dirname( __FILE__ ) . '/SSI.php' ) && !defined( 'SMF' ) )
{
	require_once( dirname( __FILE__ ) . '/SSI.php' );
}
elseif( !defined( 'SMF' ) )
{
	die( '<b>No Good:</b> 
	This is not located in the same location as SMF\'s index.php.' );
}

// Drop the SMF Settings
$result = db_query( 
	"DELETE FROM {$db_prefix}settings 
	WHERE variable='gCal_user'", 
	__FILE__, __LINE__ );

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Delete from settings failed! ');
}

$result = db_query( 
	"DELETE FROM {$db_prefix}settings 
	WHERE variable='gCal_pass'", 
	__FILE__, __LINE__ );

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Delete from settings failed! ');
}

$result = db_query( 
	"DELETE FROM {$db_prefix}settings 
	WHERE variable='gCal_list'", 
	__FILE__, __LINE__ );

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Delete from settings failed! ');
}

$result = db_query( 
	"DELETE FROM {$db_prefix}settings 
	WHERE variable='gCal_calID'",
	__FILE__, __LINE__ );

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Delete from settings failed! ');
}

$result = db_query( "DROP TABLE {$db_prefix}gCal", __FILE__, __LINE__ );
// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Drop gCal table has failed! ');
}

?>
