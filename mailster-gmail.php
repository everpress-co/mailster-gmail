<?php
/**
 * Mailster Gmail delivery integration.
 *
 * @package Mailster_Gmail
 *
 * Plugin Name: Mailster Gmail Integration
 * Requires Plugins: mailster
 * Plugin URI: https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Gmail
 * Description: Uses Gmail to deliver emails for the Mailster Newsletter Plugin for WordPress.
 * Version: 2.0.0
 * Requires PHP: 8.1
 * Author: EverPress
 * Author URI: https://mailster.co
 * Text Domain: mailster-gmail
 * License: GPLv2 or later
 *
 * @since 1.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MAILSTER_GMAIL_VERSION', '2.0.0' );
define( 'MAILSTER_GMAIL_REQUIRED_VERSION', '2.4.11' );
define( 'MAILSTER_GMAIL_FILE', __FILE__ );

require_once __DIR__ . '/classes/gmail.class.php';
new MailsterGmail();
