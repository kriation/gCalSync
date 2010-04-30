<?php

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
$result = db_query( "DELETE FROM {$db_prefix}settings
					WHERE variable='gCal_user'", __FILE__, __LINE__ );

// It broke - Shit.
if( $result === false )
{
	die( '<b>Really Not Good:</b>
		Delete from settings failed! ');
}

$result = db_query( "DELETE FROM {$db_prefix}settings
					WHERE variable='gCal_pass'", __FILE__, __LINE__ );

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
