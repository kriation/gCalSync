<?php
/************************************************************************
* gCalSync-uninstall.php						*
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

// Checking where this is being called from
if( file_exists( dirname( __FILE__ ) . '/SSI.php' ) && !defined( 'SMF' ) )
{
	require_once( dirname( __FILE__ ) . '/SSI.php' );
}
elseif( !defined( 'SMF' ) )
{
	die( 'SMF not defined.' );
}

// gCalSync settings to drop
$settings = array( 'gCal_user', 'gCal_pass', 'gCal_list', 'gCal_calID');

foreach( $settings as $setting )
{
	$result = db_query( "DELETE FROM {$db_prefix}settings 
				WHERE variable='" . $setting . "'", 
				__FILE__, __LINE__ );
	if( !$result )
	{
		fatal_error( "gCalSync: Delete of $setting from settings failed! ");
	}
}

// gCalSync table deletion
$result = db_query( "DROP TABLE {$db_prefix}gCal", __FILE__, __LINE__ );

if( !$result )
{
	fatal_error( 'gCalSync: Dropping of the gCalSync table failed!');
}

?>
