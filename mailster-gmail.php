<?php
/*
Plugin Name: Mailster Gmail Integration
Plugin URI: https://mailster.co/?utm_campaign=wporg&utm_source=Mailster+Gmail+Integration&utm_medium=plugin
Description: Uses Gmail to deliver emails for the Mailster Newsletter Plugin for WordPress.
Version: 1.0
Author: EverPress
Author URI: https://mailster.co
Text Domain: mailster-gmail
License: GPLv2 or later
*/


define( 'MAILSTER_GMAIL_VERSION', '1.0' );
define( 'MAILSTER_GMAIL_REQUIRED_VERSION', '2.4.11' );
define( 'MAILSTER_GMAIL_FILE', __FILE__ );

require_once dirname( __FILE__ ) . '/classes/gmail.class.php';
new MailsterGmail();
