<?php
/************************************************************************
* gCalSync.php								*
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

/* Protecting from bad people */
if( !defined( 'SMF' ) )
{
	die( 'Hacking attempt...' );
}

/* gCalSync_init( Google User, Google Password )
 * 	Rope in Zend to talk to Google
 *	Load some entries from modSettings
 *	Instantiate a connection to Google
 *	Instantiate a Google Calendar object from Zend
 *	Return Google Calendar object
*/
function gCalSync_Init( $user, $pass )
{
	/* Without Zend, this does not work */
    	$zendLoc = getcwd() . '/Sources';
	set_include_path( get_include_path() . PATH_SEPARATOR . $zendLoc );
	
	// require_once will throw E_COMPILE_ERROR halting the script
	require_once 'Zend/Loader.php';
	Zend_Loader::loadClass('Zend_Gdata');
	Zend_Loader::loadClass('Zend_Gdata_AuthSub');
	Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
	Zend_Loader::loadClass('Zend_Gdata_HttpClient');
	Zend_Loader::loadClass('Zend_Gdata_Calendar');

	if( !($user && $pass ) )
	{
	    fatal_lang_error('gCalE7');
	}

	/* Attempt to establish a connection to Google */
	try {
		$gClient = Zend_Gdata_ClientLogin::getHttpClient(
				$user, $pass,
				Zend_Gdata_Calendar::AUTH_SERVICE_NAME );
	}
	catch( Zend_Gdata_App_HttpException $e )
	{
		fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
	}

	if( !$gClient )
	{
		fatal_lang_error('gCalE1');
	}

	/* Attempt to grab a Google Calendar object */
	$gCal = new Zend_Gdata_Calendar( $gClient );

	if( !$gCal )
	{
		fatal_lang_error('gCalE2');
	}

	/* Return the Google Calendar object for further usage */
	return $gCal;
}

/* gCalSync_Insert( SMF Database Prefix, SMF Board URL,
			Google Calendar Object,
   			Event Title,
			Month,
			Day,
			Year,
			Number of Days )
 * Connects to Google, and inserts a calendar entry based on the values
 * passed to it from within calendarInsertEvent located in the smf
 * Calendar.php
 * After the entry is completed, inserts an entry into the smf_gCal 
 * mapping table to associate an smf eventID with a gCal ID returned
 * from the gCal insert operation
*/
function gCalSync_Insert( $db_prefix, $boardurl, $gCal, $title, $month, 
			$day, $year, $span )
{
	if( !$gCal )
	{
		fatal_lang_error('gCalE3');
	}

	/* Assuming that the Google Calendar object we were passed is still
	 * valid, let's start working through the magic */
	$event = $gCal->newEventEntry();

	/* Changing the SMF title to be in a non-HTML 
	 * Special Character form */
	$cleanTitle = str_replace( "\\", "", un_htmlspecialchars( $title ));
 
	/* Building out the topic link for the Google Calendar entry
	 * This step is painful, and is a multi-step process until I can
	 * figure out a a better way.
	 * 1) Run a select statement on smf_calendar to return the latest 
	 *	ID_EVENT based on the last insert
	 * 2) Run another select statement to find out if there's an 
	 *	ID_TOPIC associated with the above returned ID_EVENT
	 * 3) Finish up by building the topic link and adding it to 
	 *	the Google Calendar entry description
	*/

	/* Step 1 */
	$result = db_query( 
		"SELECT MAX(ID_EVENT) from {$db_prefix}calendar", 
		__FILE__, __LINE__ );
	
	if( !$result )
	{
		fatal_lang_error('gCalE8');
	}


	/* We 'assume' that there's only going to be one row returned */
	while( $row = mysql_fetch_assoc( $result ) )
	{
		$eventID = $row['MAX(ID_EVENT)'];
	}
	mysql_free_result( $result );

	/* Step 2 */
	$result = db_query( 
		"SELECT ID_TOPIC from {$db_prefix}calendar 
		WHERE ID_EVENT=$eventID", 
		__FILE__, __LINE__ );


	if( !$result )
	{
		fatal_lang_error('gCalE8');
	}

	/* Again... assuming that there's only one row returned */
	while( $row = mysql_fetch_assoc( $result ) )
	{
		$topicID = $row['ID_TOPIC'];
	}
	mysql_free_result( $result );

	/* Step 3 */
	if( $topicID != 0 )
	{
		$topicLink = $boardurl .'/index.php?topic='.$topicID.'.0';
	}
	else
	{
		$topicLink = $boardurl 
		    .'/index.php?action=calendar;year='
		    . $year 
		    . ';month='
		    . $month;
	}


        /* Grab the Google Calendar URI */
        $result = db_query(
                "SELECT value from {$db_prefix}settings
                WHERE variable='gCal_calID'",
                __FILE__, __LINE__ );
	if( !$result )
	{
		fatal_lang_error('gCalE8');
	}

        while( $row = mysql_fetch_assoc( $result ) ) 
        {
            $gCal_calID = $row['value'];
        }
        mysql_free_result( $result );
	

	/* /me sighs - Let's build the start and end dates... again.
	 * This is directly 'borrowed' from the SMF code within Calendar.php
	 * from line 457.
	 */

	// If $span == 0, event lasts 1 day, if it's >0, add 1
	if( $span > 0 )
		$span++;

	$startDate = strftime( 
			'%Y-%m-%d', 
			mktime(0, 0, 0, $month, $day, $year) );
	$endDate = strftime( 
			'%Y-%m-%d', 
			mktime(0, 0, 0, $month, $day, $year) 
				+ $span * 86400 );

	/* TODO:
	 * It would be nice if we could force specific times to be included
	 * in the SMF calendar entries, and then use them here to narrow
	 * the scope of impact on the Google Calendar
	*/

	/* Now that we have all the values we need for the Google Calendar
	 * entry, let's build it.
	*/
	$event->title = $gCal->newTitle( $cleanTitle );
	$event->content = $gCal->newContent( $topicLink );
	
	$when = $gCal->newWhen();
	$when->startTime = $startDate;
	$when->endTime = $endDate;
	$event->when = array( $when );

	/* 3,2,1 - Insert */
	try
	{
		$event = $gCal->insertEvent( $event, $gCal_calID );
	}
	catch( Zend_Gdata_App_HttpException $e )
	{
		fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
	}	

	
	/* As long as the above worked properly, we can move forward
	 * and add the associated Google event ID to the mapping table
	 * as part of this modification.
	*/
	
	/* Store the Google Event ID for safe keeping */
	$gCal_eventID = $event->id->text;

	/* Use it. */
	if( $gCal_eventID )
	{
		$result = db_query( 
		"INSERT INTO {$db_prefix}gCal (ID_EVENT,gCal_ID) 
		VALUES( $eventID, '$gCal_eventID' )",
	       	__FILE__, __LINE__ );

		if( !$result )
		{
			fatal_lang_error('gCalE8');
		}
	}

	/* I think we're done. :) */
}
/* gCalSync_Remove( SMF Database Prefix, Google Calendar Object, Event ID )
 * Before we do anything, we need to get the Google Event URL from 
 * the smf DB
 * Once we have it, we fetch the entry from Google
 * As long as it's valid, we can delete the entry!
 *
*/
function gCalSync_Remove( $db_prefix, $gCal, $eventID )
{
	if( !$gCal )
	{
		fatal_lang_error('gCalE4');
	}
	
	/* Retrieve the Google event URL from the smf DB */
	$result = db_query( 
		"SELECT gCal_ID from {$db_prefix}gCal 
		WHERE ID_EVENT=$eventID",
	       	__FILE__, __LINE__ );
	
	if( !$result )
	{
		fatal_lang_error('gCalE8');
	}

	$numRows = mysql_num_rows( $result );
	if( $numRows == 1 )
	{
		$row = mysql_fetch_assoc( $result );
		$gCalID = $row['gCal_ID'];
		
		// Connect to Google and retrieve the event's edit URL 
		try
		{
			$gEvent = 
				$gCal->getCalendarEventEntry( $gCalID );
		}
		catch( Zend_Gdata_App_InvalidArgumentException $e )
		{
			fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
		}
		catch( Zend_Gdata_App_HttpException $e )
		{
			fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
		}

		/* If we're successful... pull the trigger... */
		try
		{
			$gEvent->delete();
		}
		catch( Zend_Gdata_App_InvalidArgumentException $e )
		{
			fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
		}
		catch( Zend_Gdata_App_HttpException $e )
		{
			fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
		}	

		$result = db_query( 
			"DELETE FROM {$db_prefix}gCal 
			WHERE ID_EVENT=$eventID", __FILE__, __LINE__ );

		if( !$result )
		{
			fatal_lang_error('gCalE8');
		}
	}
	elseif( $numRows == 0 )
	{
		fatal_lang_error('gCalE8');
	}
	elseif( $numRows > 1 )
	{
		fatal_lang_error('gCalE5');
	}
	elseif( $numRows == FALSE )
	{
		fatal_lang_error('gCalE6');
	}


	/* That was easy... :) */
}
/* gCalSync_Update( SMF Database Prefix, Google Calendar Object,
   			Event ID
   			Event Title,
			Month,
			Day,
			Year,
			Number of Days )
 * Again... retrieve the Google Event URL from the smf DB
 * Once we have it, fetch the entry from Google... again.
 * Take the new values (regardless of what they are) and build a new event
 * object
 * Once it's filled, save the event.
*/
function gCalSync_Update( $db_prefix, $gCal, $eventID, $title, 
				$month, $day, $year, $span )
{
	if( !$gCal )
	{
	    	fatal_lang_error('gCal7');
	}
	
	/* Retrieve the Google event URL from the smf DB */
	$result = db_query( 
		"SELECT gCal_ID from {$db_prefix}gCal 
		WHERE ID_EVENT=$eventID", __FILE__, __LINE__ );
	
	if( !$result )
	{
		fatal_lang_error('gCalE8');
	}

	$numRows = mysql_num_rows( $result );
	if( $numRows == 1 )
	{
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$gCalID = $row['gCal_ID'];
			/* Connect to Google and retrieve the event's edit URL */
			try
			{
				$event = $gCal->getCalendarEventEntry( $gCalID );
			}
			catch( Zend_Gdata_App_InvalidArgumentException $e )
			{
				fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
			}
			catch( Zend_Gdata_App_HttpException $e )
			{
				fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
			}
	
			// If $span == 0, event lasts 1 day, if it's >0, add 1
			if( $span > 0 )
				$span++;
		
			/* Fix title */
                        $cleanTitle = str_replace( "\\", "",
                                un_htmlspecialchars( $title ));
	
			/* Build the update object */
			$event->title = $gCal->newTitle( $cleanTitle );
			$when = $gCal->newWhen();
			$startDate = strftime( 
				'%Y-%m-%d', 
				mktime(0, 0, 0, $month, $day, $year) );
			$endDate = strftime( 
				'%Y-%m-%d', 
				mktime(0, 0, 0, $month, $day, $year) 
					+ $span * 86400 );
			$when->startTime = $startDate;
			$when->endTime = $endDate;
			$event->when = array( $when );
		
			/* If the above was put together properly, 
			 * this should work... 
			 */
			try 
			{
				$event->save();
			}
			catch( Zend_Gdata_App_InvalidArgumentException $e )
			{
				fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
			}
			catch( Zend_Gdata_App_HttpException $e )
			{
				fatal_error( "gCalSync: Error from Google <br><br> $e->getMessage()" );
			}

		}
	}
	elseif( $numRows == 0 )
	{
		fatal_lang_error('gCalE8');
	}
	elseif( $numRows > 1 )
	{
		fatal_lang_error('gCalE5');
	}
	elseif( $numRows == FALSE )
	{
		fatal_lang_error('gCalE6');
	}
	
	mysql_free_result( $result );

}
?>
