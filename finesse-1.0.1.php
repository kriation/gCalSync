<?php
/************************************************************************
* finesse-1.0.1.php							*
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

/* 
 * The reason this file exists is because I did a major refactoring within
 * gCalSync.xml for v1.0.2. Basically, I removed the requirement for the
 * whitespace=loose condition. In testing the upgrade from 1.0.1 to 1.0.2,
 * the process added all of the changes to the files again due to more
 * stringent whitespace requirements, which really broke the runtime 
 * functionality of the modification.
 *
 * This file (I'm hoping) will temporarily replace the original copies 
 * (which were backed up during the original modification install) over the
 * live ones, and then re-run through the new changes. Once this is done, 
 * subsequent upgrades shouldn't need this kind of 'surgery'.
*/

/* Crossing fingers _  X | |
 *		    \\(     )
*/

// Involve SMF 
if( file_exists( dirname( __FILE__ ) . '/SSI.php' ) && !defined( 'SMF' ) ) 
{
        require_once( dirname( __FILE__ ) . '/SSI.php' );
}
elseif( !defined( 'SMF' ) ) 
{
        die( '<b>No Good:</b> 
        This is not located in the same location as SMF\'s index.php.' );
}

/* In version 1.0.1, gCalSync.xml 'touched' the following files:
 * within $sourcedir:
 *	ManageCalendar.php
 *	Calendar.php
 *	Post.php
 *	RemoveTopic.php
 * within $themedir:
 *	ManageCalendar.template.php
*/

// Do backups exist for the above?
$fileList = array( "$sourcedir/ManageCalendar.php", 
		"$sourcedir/Calendar.php", "$sourcedir/Post.php", 
		"$sourcedir/RemoveTopic.php", 
		"$themedir/ManageCalendar.template.php" );

foreach( $fileList as $fileTest )
{
	if( is_file( $fileTest . '~' ) != TRUE )
	{
		die( "Upgrade failed because $fileTest was not found." );
	}

	if( preg_match( '/gCalSync/', 
		file_get_contents( $fileTest . '~' ) ) != 0 )
	{
		die( "Upgrade failed because $fileTest was tainted." );
	}
}

// Now that we know we have good backups, we can start...
foreach( $fileList as $fileTest )
{
	if( copy( $fileTest, 
		$fileTest . '.gCalSync.upgrade.orig' ) != TRUE )
	{
		die( 
		"Upgrade failed because $fileTest could not be copied.");
	}

	if( copy( $fileTest . '~', $fileTest ) != TRUE )	
	{
		die(
		"Upgrade failed because $fileTest could not be copied.");
	}
}

// Looks good... clean-up the mess.
foreach( $fileList as $fileTest )
{
	if( unlink( $fileTest . '.gCalSync.upgrade.orig' ) != TRUE )
	{
		error_log( '$fileTest' 
			. '.gCalSync.upgrade.orig could not be deleted!' );
		// We left a mess...
	}
}
