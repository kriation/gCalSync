<?php

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
 * 			Yes I know... it's in the clear. I'll do something fun with it
 *			eventually.
 *
 * Addition
 * --------
 * Table: smf_gCal
 * Columns:
 * 	ID_EVENT == Corresponds to the event ID from smf_calendar
 *	gCal_ID == This is the Google calendar entry ID returned from Google 
 *				when creating a new calendar entry. This value is also 
 *				used to edit the entry once it has been created.
 *
 * TODO
 * ----
 * > Add functionality to synchronize pre-existing entries with Google 
 *		during the setup process
 * > Add functionality to input Google User/Pass during the setup process
 * > Encrypt Google Password
*/

// Adding the additional parameters to smf_settings
$new_settings = array( 
		'gCal_user' => 'Google User',
		'gCal_pass' => 'Google Password' );

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

// Fix the array into a database compliant form factor
$string = '';
foreach( $new_settings as $x => $y )
{
	// This is so bad, but it works! :)
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
						substr( $string, 0, -1 ), __FILE__, __LINE__ );
}

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Settings insertion failed!' );
}

// As long as things went well up there, let's add the new table! :)
$result = db_query( "CREATE TABLE IF NOT EXISTS {$db_prefix}gCal ( 
					`ID_EVENT` smallint(5) unsigned NOT NULL, 
					`gCal_ID` tinytext NOT NULL,
					PRIMARY KEY (`ID_EVENT`) )",
				   	__FILE__, __LINE__ ); 

// It broke - Shit x 2!
// God knows how/why we got here...
if( $result === false )
{
	die( '<b>Really, Really Not Good:</b>
			Table insertion failed!' );
}
?>
