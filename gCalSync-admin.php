<?php
/************************************************************************
* gCalSync-admin.php													*
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

function add_gCalSync_menu( &$admin_areas )
{
	// As far as I'm concerned, this is voodoo.
	$admin_areas['config']['areas']['modsettings']['subsections']['gcalsyncadmin'] = array( 'gCalSync Administration' );
}

function add_gCalSync_admin( &$subActions )
{
	$subActions['gcalsyncadmin'] = 'gCalSync_admin';
}

function gCalSync_admin()
{
	global $context, $modSettings, $txt, $sc, $scripturl, $sourcedir;

	loadLanguage( 'gCalSync' );

	$context['settings_title'] = $txt['title_gcal_admin'];
	$context['settings_message'] = $txt['msg_gcal_admin'];
	$context['post_url'] = $scripturl .
		'?action=admin;area=modsettings;sa=gcalsyncadmin;save';

	$config_vars = array();
	empty( $modSettings['gcal_sec'] ) ?
		$config_vars[] = array( 'check', 'gcal_sec_include' ) : false;
	empty( $modSettings['gcal_sec'] ) ?
		$config_vars[] = array( 'password', 'gcal_sec' ) : false;
	( !empty( $modSettings['gcal_sec'] ) &&
		empty( $modSettings['gcal_auth'] ) ) ?
			$config_vars[] = array( 'password', 'gcal_auth' ) : false;

	if ( !empty( $modSettings['gcal_sec'] ) &&
		!empty( $modSettings['gcal_auth'] ) )
	{
		if( empty( $modSettings['gcal_calid'] ) )
			{
			$gCalFrame = '<div class="alert">' .
				$txt['msg_google_calendar'] .  '</div>';
			$context['settings_message'] = $gCalFrame;
		}
		else
		{
			$successFrame = '<div class="success">' .
				$txt['msg_google_success'] .  '</div>';
			$context['settings_message'] = $successFrame;
		}

		$gClient = gcalsync_init( $modSettings['gcal_sec'] );
		$accessToken = gcalsync_refresh( $gClient,
			$modSettings['gcal_auth'] );
		( !empty( $accessToken ) &&
			( $accessToken !== $modSettings['gcal_auth'] ) ) ?
				updateSettings( array( 'gcal_auth' => $accessToken ),
					$update = true ) : false );
		$gCalArray = gcalsync_getCals( $gClient );
		( !empty( $modSettings['gcal_sec'] ) &&
			!empty( $modSettings['gcal_auth'] ) ) ?
				$config_vars[] = array( 'select', 'gcal_calid',
					$gCalArray ) : false );
	}

	prepareDBSettingContext( $config_vars );

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
	{
		if ( !empty( $_POST['gcal_sec_include'] ) )
		{
			if ( $_POST['gcal_sec_include'] === '1' )
			{
				$gcal_sec = file_get_contents(
					$sourcedir . '/gCalSync.json' );
				$_POST['gcal_sec']['0'] = $gcal_sec;
				$_POST['gcal_sec']['1'] = $gcal_sec;
			}
		}
		if ( !empty( $_POST['gcal_auth']['0'] ) &&
			!empty( $_POST['gcal_auth']['1'] ) &&
			!empty( $modSettings['gcal_sec'] ) )
		{
			// Complete the authentication
			$gcal_sec = $modSettings['gcal_sec'];
			// Only if user input matches
			$gcal_authCode =
				( $_POST['gcal_auth']['0'] === $_POST['gcal_auth']['1'] ) ?
					$_POST['gcal_auth']['1'] : NULL;
			$gClient = gcalsync_init( $gcal_sec );
			$accessToken = gcalsync_auth( $gClient, $gcal_authCode );

			// Put the access token in the database
			$_POST['gcal_auth']['0'] = $accessToken;
			$_POST['gcal_auth']['1'] = $accessToken;
		}
	}

	if ( !empty( $modSettings['gcal_sec'] ) &&
		empty( $modSettings['gcal_auth'] ) )
	{
		// Kick off Google oAuth Process
		$gcal_sec = $modSettings['gcal_sec'];
		$gClient = gcalsync_init( $gcal_sec );
		$authURL = gcalsync_getAuthUrl( $gClient );

		// Make the oAuth URL Pretty
		$authURL = '<a target="_blank " href="' . $authURL .
			'">Google oAuth URL</a>';
		$authFrame = '<div class="windowbg2"><div class="content"><p>' .
			$txt['desc_oauth_url'] . '</p>' .  $authURL . '</div></div>';
		$context['settings_insert_below'] = $authFrame;
	}
	if ( isset( $_GET['save'] ) )
	{
		saveDBSettings( $config_vars );
		redirectexit( 'action=admin;area=modsettings;sa=gcalsyncadmin;' .
			$context['session_var'] . '=' . $context['session_id'] );
	}
}
