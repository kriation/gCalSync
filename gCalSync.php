<?php
/************************************************************************
* gCalSync.php															*
*************************************************************************
* gCalSync																*
* Copyright 2009-2015 Armen Kaleshian <armen@kriation.com>				*
* License: GNU GPL (v3 or later). See LICENSE.txt for details.			*
*																		*
* An enhancement for SMF to synchronize forum calendar entries with a	*
* Google Calendar.														*
* *********************************************************************	*
* This program is free software: you can redistribute it and/or modify	*
* it under the terms of the GNU General Public License as published by	*
* the Free Software Foundation, either version 3 of the License, or		*
* (at your option) any later version.									*
*																		*
* This program is distributed in the hope that it will be useful,		*
* but WITHOUT ANY WARRANTY; without even the implied warranty of		*
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the			*
* GNU General Public License for more details.							*
*																		*
* You should have received a copy of the GNU General Public License		*
* along with this program.  If not, see <http://www.gnu.org/licenses/>.	*
************************************************************************/

if ( !defined( 'SMF' ) )
die( 'Hacking attempt...' );

function gcalsync_init( $gcal_sec )
{
	// Constants that most likely will never change
	defined( 'ACCESS_TYPE' ) ? true :
		define( 'ACCESS_TYPE', 'offline' );
	defined( 'APPLICATION_NAME' ) ? true :
		define( 'APPLICATION_NAME', 'gCalSync' );
	defined( 'SCOPES' ) ? true :
		define( 'SCOPES', Google_Service_Calendar::CALENDAR );

	$gClient = new Google_Client();
	$gClient->setAccessType( ACCESS_TYPE );
	$gClient->setApplicationName( APPLICATION_NAME );
	$gClient->setScopes( SCOPES );

	$gClient->setAuthConfig( $gcal_sec );

	return $gClient;
}

function gcalsync_getAuthUrl( $gClient = NULL )
{
	// Get the Google Authentication URL and return it to the caller
	if ( !empty( $gClient ) )
	{
		$authUrl = $gClient->createAuthUrl();
	}
	else
		$authUrl = NULL;

	return $authUrl;
}

function gcalsync_auth( $gClient = NULL, $authCode = NULL )
{
	if ( !empty( $gClient ) && !empty( $authCode ) )
	{
		$accessToken = NULL;
		// Authenticate against Google API
		$accessToken = $gClient->authenticate( $authCode );

		if( empty( $accessToken ) )
		{
			die(
				log_error( 'gCalSync: Authentication to Google failed.' ) );
		}
	}
	else
	{
		die(
			log_error(
				'gCalSync: Client object and/or authCode are null.' ) );
	}

	return $accessToken;
}

function gcalsync_refresh( &$gClient, $accessToken = NULL )
{
	if ( !empty( $gClient ) )
	{
		if ( !empty( $accessToken ) )
		{
			$gClient->setAccessToken( $accessToken );
			if ( $gClient->isAccessTokenExpired() )
			{
				$gClient->refreshToken( $gClient->getRefreshToken() );
				$accessToken = $gClient->getAccessToken();
			}
		}
		else
		{
			die(
				log_error(
					'gCalSync: Access Token is empty!' ) );
		}
	}
	else
	{
		die(
			log_error(
				'gCalSync: Google Client object is empty!' ) );
	}

	return $accessToken;
}

function gcalsync_getCals( $gClient = NULL )
{
	if ( !empty( $gClient ) )
	{
		$gCalService = new Google_Service_Calendar( $gClient );
		$gCalList = $gCalService->calendarList->listCalendarList();
		$gCalArray = array();
		foreach( $gCalList->getItems() as $gCalListEntry )
		{
			if( empty( $gCalListEntry->getSummaryOverride() ) )
			{
				$gCalArray[ $gCalListEntry->getId() ] =
				$gCalListEntry->getSummary();
			}
			else
			{
				$gCalArray[ $gCalListEntry->getId() ] =
				$gCalListEntry->getSummaryOverride();
			}
		}
	}
	else
	{
		die(
			log_error( 'gCalSync: Google Client object is empty!' ) );
	}

	return $gCalArray;
}

function gcalsync_insert( $gClient = NULL, $gCalID, $eventOptions,
	$boardurl )
{
	global $smcFunc;

	if ( !empty( $gClient ) )
	{
		$gCalService = new Google_Service_Calendar( $gClient );

		// Create link to SMF board for description field
		$topicLink = $eventOptions['topic'] !== 0 ?
			$boardurl . '/index.php?topic=' .
				$eventOptions['topic'] . '.0' :
			$boardurl .'/index.php?action=calendar;year=' .
				date( 'Y', strtotime( $eventOptions['start_date'] ) ) .
				';month=' .
				date( 'n', strtotime( $eventOptions['start_date'] ) );

		// End date for Google is not inclusive; adjusting...
		if ( $eventOptions['span'] > 0 )
		{
			sscanf( $eventOptions['end_date'], '%d-%d-%d',
				$year, $month, $day );
			$eventOptions['end_date'] = strftime( '%Y-%m-%d',
				mktime( 0, 0, 0, $month, $day, $year ) +
				$eventOptions['span'] + 86400 );
		}

		// Prepare the payload
		$event = new Google_Service_Calendar_Event(
			array(
				'summary' => (
					isset( $eventOptions['title'] ) ?
						$eventOptions['title'] : NULL ),
				'description' => $topicLink,
				'start' => array( 'date' => (
					isset( $eventOptions['start_date'] ) ?
						$eventOptions['start_date'] : NULL ) ),
				'end' => array( 'date' => (
					isset( $eventOptions['end_date'] ) ?
						$eventOptions['end_date'] : NULL ) )
			)
		);

		$event = $gCalService->events->insert( $gCalID, $event );

		// Add association to gcalsync table
		$smcFunc['db_insert']('',
			'{db_prefix}gcalsync',
			array(
				'id_event' => 'int',
				'id_google_entry' => 'string-50'
			),
			array(
				$eventOptions['id'],
				$event->getId()
			),
			array( 'id_event' )
		);

	}
}

function gcalsync_update( $gClient = NULL, $gCalID, $gEventID,
	$eventOptions, $boardurl )
{
	global $smcFunc;

	if ( !empty( $gClient ) )
	{
		$gCalService = new Google_Service_Calendar( $gClient );

		// Build a description (as in gcalsync_insert)
		$topicLink = isset( $eventOptions['topic'] ) ?
			$boardurl . '/index.php?topic=' .
				$eventOptions['topic'] . '.0' :
			$boardurl .'/index.php?action=calendar;year=' .
				date( 'Y', strtotime( $eventOptions['start_date'] ) ) .
				';month=' .
				date( 'n', strtotime( $eventOptions['start_date'] ) );

		// End date for Google is not inclusive; adjusting...
		if ( $eventOptions['span'] > 0 )
		{
			sscanf( $eventOptions['end_date'], '%d-%d-%d',
				$year, $month, $day );
			$eventOptions['end_date'] = strftime( '%Y-%m-%d',
				mktime( 0, 0, 0, $month, $day, $year ) +
				$eventOptions['span'] + 86400 );
		}

		// Get event object from Google Service
		$event = $gCalService->events->get( $gCalID, $gEventID );

		// Modify event object with new eventOptions
		isset( $eventOptions['title'] ) ?
			$event->setSummary( $eventOptions[ 'title' ] ) : false;
		$event->setDescription( $topicLink );

		$eventStart = $event->getStart();
		isset( $eventOptions['start_date'] ) ?
			$eventStart->setDate( $eventOptions[ 'start_date' ] ) : false;
		$event->setStart( $eventStart );

		$eventEnd = $event->getEnd();
		isset( $eventOptions['end_date'] ) ?
			$eventEnd->setDate( $eventOptions[ 'end_date' ] ) : false;
		$event->setEnd( $eventEnd );

		$gCalService->events->update( $gCalID, $gEventID, $event );
	}
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
?>
