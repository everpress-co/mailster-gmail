<?php
/**
 * Gmail delivery integration for Mailster.
 *
 * @package Mailster_Gmail
 */

defined( 'ABSPATH' ) || exit;

// Mailster campaign objects and Gmail API payloads use camelCase properties.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Registers Gmail as a Mailster delivery method and sends through the Gmail API.
 *
 * @since 1.0
 */
class MailsterGmail {

	/**
	 * Absolute filesystem path to the plugin directory.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * URL to the plugin directory.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Authorized Google API client.
	 *
	 * @since 1.0
	 *
	 * @var object|null
	 */
	private $client;


	/**
	 * Hooks activation, loads translations, and registers the init callback.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {

		$this->plugin_path = plugin_dir_path( MAILSTER_GMAIL_FILE );
		$this->plugin_url  = plugin_dir_url( MAILSTER_GMAIL_FILE );

		register_activation_hook( MAILSTER_GMAIL_FILE, array( &$this, 'activate' ) );
		register_deactivation_hook( MAILSTER_GMAIL_FILE, array( &$this, 'deactivate' ) );

		load_plugin_textdomain( 'mailster-gmail' );

		add_action( 'init', array( &$this, 'init' ), 1 );
	}


	/**
	 * Registers Mailster delivery hooks or shows the missing-Mailster notice.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function init() {

		if ( ! function_exists( 'mailster' ) ) {

			add_action( 'admin_notices', array( &$this, 'notice' ) );

		} else {

			add_filter( 'mailster_delivery_methods', array( &$this, 'delivery_method' ) );
			add_action( 'mailster_deliverymethod_tab_gmail', array( &$this, 'deliverytab' ) );

			add_filter( 'mailster_verify_options', array( &$this, 'verify_options' ) );

			add_action( 'load-newsletter_page_mailster_settings', array( &$this, 'auth_endpoint' ) );

			if ( 'gmail' === mailster_option( 'deliverymethod' ) ) {

				add_action( 'mailster_initsend', array( &$this, 'initsend' ) );
				add_action( 'mailster_presend', array( &$this, 'presend' ) );
				add_action( 'mailster_dosend', array( &$this, 'dosend' ) );
				add_action( 'mailster_check_bounces', array( &$this, 'check_bounces' ) );
				add_action( 'mailster_section_tab_bounce', array( &$this, 'section_tab_bounce' ) );
				add_filter( 'mailster_subscriber_errors', array( $this, 'subscriber_errors' ) );
			}
		}
	}


	/**
	 * Handles the Google OAuth redirect back to the delivery settings screen.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function auth_endpoint() {

		// Google's OAuth redirect does not include a WordPress nonce.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['mailster_gmail'] ) ) {

			$redirect_url = admin_url( 'edit.php?post_type=newsletter&page=mailster_settings#delivery' );
			$oauth_error  = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
			$oauth_code   = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

			if ( '' !== $oauth_error ) {
				switch ( $oauth_error ) {
					case 'access_denied':
						mailster_notice( esc_html__( 'You have to allow access to your app in order to use Gmail as delivery method.', 'mailster-gmail' ), 'error', true );
						break;

					default:
						mailster_notice( esc_html__( 'There was an error while authorizing your app.', 'mailster-gmail' ), 'error', true );
						break;
				}
			} elseif ( '' !== $oauth_code ) {

				$this->get_client( $oauth_code );

			}
			wp_safe_redirect( $redirect_url );
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}


	/**
	 * Returns the OAuth redirect URL, filterable via mailster_gmail_redirect_url.
	 *
	 * @since 1.0
	 *
	 * @return string OAuth redirect URL.
	 */
	public function get_redirect_url() {
		return apply_filters( 'mailster_gmail_redirect_url', admin_url( 'edit.php?post_type=newsletter&page=mailster_settings&mailster_gmail=auth' ) );
	}


	/**
	 * Builds or refreshes the authorized Google API client.
	 *
	 * @since 1.0
	 *
	 * @param string|null $code Optional. OAuth authorization code from Google. Default null.
	 * @return object|null Authorized client, or null when credentials are missing.
	 */
	public function get_client( $code = null ) {

		if ( ! class_exists( '\Mailster\Gmail\Google\Client', false ) ) {
			require_once $this->plugin_path . 'vendor-prefixed/autoload.php';
		}

		$client_id     = mailster_option( 'gmail_client_id' );
		$client_secret = mailster_option( 'gmail_client_secret' );
		$token         = mailster_option( 'gmail_token' );

		if ( ! $this->client && $client_id && $client_secret ) {

			$client = new Mailster\Gmail\Google\Client();
			$client->setClientId( $client_id );
			$client->setClientSecret( $client_secret );
			$client->setRedirectUri( $this->get_redirect_url() );
			$client->setAccessType( 'offline' );
			if ( $token ) {
				$client->setAccessToken( $token );
			}
			$client->setApprovalPrompt( 'force' );

			$client->addScope( 'https://mail.google.com/' );

			// If there is no previous token or it's expired.
			if ( $client->isAccessTokenExpired() ) {

				// Refresh the token if possible, else fetch a new one.
				if ( $client->getRefreshToken() ) {
					$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
				} elseif ( ! is_null( $code ) ) {
					$client->authenticate( $code );
				} else {
					if ( $token ) {
						mailster_notice( esc_html__( 'The Gmail Integration for Mailster is no longer connected. Please check the delivery settings!', 'mailster-gmail' ), 'error', false, 'mailster_gmail_isAccessTokenExpired' );
					}
					return null;
				}

				$token = wp_json_encode( $client->getAccessToken() );
				remove_filter( 'mailster_verify_options', array( &$this, 'verify_options' ) );
				mailster_update_option( 'gmail_token', $token );

			}

			$this->client = $client;
		}

		if ( ! is_null( $code ) ) {
			$this->client->authenticate( $code );
			mailster_update_option( 'gmail_token', wp_json_encode( $client->getAccessToken() ) );
		}

		return $this->client;
	}


	/**
	 * Sets initial send options on the mailster_initsend hook.
	 *
	 * Gmail handles DKIM, so Mailster's DKIM signing is disabled.
	 *
	 * @since 1.0
	 *
	 * @param object $mailobject Mailster mail object.
	 * @return void
	 */
	public function initsend( $mailobject ) {

		// Gmail will handle DKIM integration.
		$mailobject->dkim = false;
	}


	/**
	 * Applies per-message settings on the mailster_presend hook.
	 *
	 * @since 1.0
	 *
	 * @param object $mailobject Mailster mail object.
	 * @return void
	 */
	public function presend( $mailobject ) {

		$mailobject->pre_send();
	}


	/**
	 * Sends the current message through the Gmail API.
	 *
	 * @since 1.0
	 *
	 * @param object $mailobject Mailster mail object.
	 * @return bool|null False when sending fails, null after a successful API call.
	 */
	public function dosend( $mailobject ) {

		$client = $this->get_client();

		if ( ! $client ) {
			$mailobject->set_error(
				sprintf(
					/* translators: %s: setup guide link. */
					esc_html__( 'Please create your Gmail App as explained in our guide %s.', 'mailster-gmail' ),
					'<a href="https://kb.mailster.co/send-your-newsletters-via-gmail/" class="external">' . esc_html__( 'here', 'mailster-gmail' ) . '</a>'
				)
			);
			$mailobject->sent = false;
			return false;
		}

		try {
			$mailobject->mailer->PreSend();

			$service = new Mailster\Gmail\Google\Service\Gmail( $this->get_client() );

			$rawmessage = $mailobject->mailer->getSentMIMEMessage();

			$msg = new Mailster\Gmail\Google\Service\Gmail\Message();
			// Gmail's users.messages.send endpoint expects raw RFC822 in base64url.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$msg->setRaw( rtrim( strtr( base64_encode( $rawmessage ), '+/', '-_' ), '=' ) );

			$response = $service->users_messages->send( 'me', $msg );
			if ( method_exists( $response, 'getId' ) ) {
				$message_id       = $response->getId();
				$mailobject->sent = ! empty( $message_id );
			}
		} catch ( Mailster\Google\Service\Exception $e ) {
			$error_obj = json_decode( $e->getMessage() );
			$code      = isset( $error_obj->error->code ) ? (int) $error_obj->error->code : 0;
			if ( 429 === $code ) {
				$campaign_id = $mailobject->campaignID;
				if ( ! mailster_option( 'pause_campaigns' ) && $campaign_id && defined( 'MAILSTER_DOING_CRON' ) ) {
					$time       = preg_replace( '/(.*?)(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})\.(.*)/', '$2', $error_obj->error->message );
					$campaign   = mailster( 'campaigns' )->get( $campaign_id );
					$timeformat = mailster( 'helper' )->timeformat();
					$timeoffset = mailster( 'helper' )->gmt_offset( true );
					mailster_notice(
						sprintf(
							/* translators: 1: campaign title, 2: resume date. */
							esc_html__( 'Campaign %1$s has been paused and will resume on %2$s.', 'mailster-gmail' ),
							$campaign->post_title,
							// Resume time is shifted by Mailster's GMT offset below.
							// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							date( $timeformat, strtotime( $time ) + $timeoffset )
						),
						'info',
						false,
						'gmail_campaign_paused_' . $campaign_id
					);
					mailster( 'campaigns' )->pause( $campaign_id );
					mailster( 'campaigns' )->resume( $campaign_id, strtotime( $time ) + 10 );
				}
			} elseif ( 401 === $code ) {
				mailster_update_option( 'gmail_token', '' );
			}
			$mailobject->set_error( $error_obj->error->message );
			$mailobject->sent = false;
		} catch ( Exception $e ) {
			$mailobject->set_error( $e->getMessage() );
			$mailobject->sent = false;
		}
	}


	/**
	 * Adds Gmail to Mailster's delivery method list.
	 *
	 * @since 1.0
	 *
	 * @param array $delivery_methods Delivery method labels keyed by slug.
	 * @return array Delivery method labels keyed by slug.
	 */
	public function delivery_method( $delivery_methods ) {
		$delivery_methods['gmail'] = 'Gmail';
		return $delivery_methods;
	}


	/**
	 * Renders the Gmail delivery settings tab.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function deliverytab() {

		$verified = ! is_null( $this->get_client() );

		include $this->plugin_path . '/views/settings.php';
	}


	/**
	 * Copies the authorized Gmail address into the from, reply-to, and bounce options.
	 *
	 * @since 1.0
	 *
	 * @param array $options Mailster options being saved.
	 * @return array Updated Mailster options.
	 */
	public function verify_options( $options ) {

		if ( 'gmail' === $options['deliverymethod'] ) {

			$send_limit = 500;
			if ( false && (int) $options['send_limit'] !== $send_limit ) {
				$options['send_limit']  = $send_limit;
				$options['send_period'] = 24;
				update_option( '_transient__mailster_send_period_timeout', false );
				add_settings_error(
					'mailster_settings',
					uniqid(),
					sprintf(
						/* translators: %d: daily send limit. */
						esc_html__( 'Send limit has been adjusted to %d for Gmail', 'mailster-gmail' ),
						$send_limit
					),
					'updated'
				);
			}

			$client = $this->get_client();
			if ( $client ) {
				try {
					$service             = new Mailster\Gmail\Google\Service\Gmail( $client );
					$response            = $service->users->getProfile( 'me' );
					$options['from']     = $response->emailAddress;
					$options['reply_to'] = $response->emailAddress;
					$options['bounce']   = $response->emailAddress;
				} catch ( Exception $e ) {
					$error_obj = json_decode( $e->getMessage() );
					if ( isset( $error_obj->error->code ) ) {
						$code = $error_obj->error->code;
					} else {
						$code = $error_obj->error;
					}
					if ( 401 === (int) $code ) {
						$options['gmail_token'] = '';
					}
				}
			}
		}

		return $options;
	}


	/**
	 * Checks the Gmail inbox for bounce and unsubscribe messages.
	 *
	 * @since 1.0
	 *
	 * @return bool|null False when a check is skipped, null after a completed scan.
	 */
	public function check_bounces() {

		if ( get_transient( 'mailster_check_bounces_lock' ) ) {
			return false;
		}

		$client = $this->get_client();
		if ( ! $client ) {
			return false;
		}

		$service = new Mailster\Gmail\Google\Service\Gmail( $client );

		$page_token        = null;
		$user_id           = 'me';
		$messages          = array();
		$messages_todelete = array();
		$opt_param         = array(
			'q' => 'in:(inbox OR spam) from:("Mail Delivery Subsystem") OR subject:"Please remove me from the list" after:' . date( 'Y/m/d', time() - DAY_IN_SECONDS ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Gmail search uses this local date string.
		);

		do {
			try {
				if ( $page_token ) {
					$opt_param['pageToken'] = $page_token;
				}
				$messages_response = $service->users_messages->listUsersMessages( $user_id, $opt_param );
				if ( $messages_response->getMessages() ) {
					$messages   = array_merge( $messages, $messages_response->getMessages() );
					$page_token = $messages_response->getNextPageToken();
				}
			} catch ( Exception $e ) {
				// Keep scanning later pages when one Gmail request fails.
				unset( $e );
			}
		} while ( $page_token );

		foreach ( $messages as $id => $message ) {

			$m = $service->users_messages->get( $user_id, $message->id, array( 'format' => 'full' ) );

			$status      = null;
			$action      = null;
			$mailster_id = null;
			$campaign_id = null;
			$hash        = null;
			$info        = $m->getSnippet();

			// Handle a reply that used the client-specific unsubscribe feature.
			if ( false !== strpos( $info, 'X-Mailster:' ) ) {
				preg_match( '#X-Mailster-ID: ([a-f0-9]{32})#i', $info, $mailster_id );
				preg_match( '#X-Mailster: ([a-f0-9]{32})#i', $info, $hash );
				preg_match( '#X-Mailster-Campaign: (\d+)#i', $info, $camp );
				$mailster_id    = isset( $mailster_id[1] ) ? $mailster_id[1] : null;
				$hash           = isset( $hash[1] ) ? $hash[1] : null;
				$campaign_id    = isset( $camp[1] ) ? (int) $camp[1] : null;
				$campaign_index = null;

				// Get the campaign index.
				if ( false !== strpos( (string) $campaign_id, '-' ) ) {
					$campaign_index = absint( strrchr( $campaign_id, '-' ) );
					$campaign_id    = absint( $campaign_id );
				}
				$info   = 'list_unsubscribe';
				$action = 'unsubscribe';
			}

			// Handle regular bounce messages.
			if ( ! $action ) {
				foreach ( $m->payload->parts as $message_part ) {

					foreach ( $message_part->parts as $part ) {
						foreach ( $part->headers as $part_header ) {
							switch ( $part_header->name ) {
								case 'Status':
									$status = $part_header->value;
									break;
								case 'Action':
									$action = $part_header->value;
									break;
								case 'X-Mailster-ID':
									$mailster_id = $part_header->value;
									break;
								case 'X-Mailster-Campaign':
									$campaign_id    = $part_header->value;
									$campaign_index = null;

									// Get the campaign index.
									if ( false !== strpos( (string) $campaign_id, '-' ) ) {
										$campaign_index = absint( strrchr( $campaign_id, '-' ) );
										$campaign_id    = absint( $campaign_id );
									}
									break;
								case 'X-Mailster':
									$hash = $part_header->value;
									break;
							}
						}
					}
				}
			}

			// Handle Gmail special cases.
			if ( ! $action ) {
				foreach ( $m->payload->headers as $message_headers ) {

					if ( 'From' === $message_headers->name && 'Mail Delivery Subsystem <mailer-daemon@googlemail.com>' === $message_headers->value ) {

						// Bounce bodies are base64url payloads from the Gmail API.
						// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
						$raw_status = base64_decode( $m->payload->parts[1]->parts[0]->body->data );

						$attachment_id = $m->payload->parts[2]->body->attachmentId;
						if ( $attachment_id ) {
							$org_email = $service->users_messages_attachments->get( $user_id, $message->id, $attachment_id );
							// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
							$org_message = base64_decode( strtr( $org_email->data, '-_', '+/' ) );

							preg_match( '#Status: ([0-9.]+)#i', $raw_status, $status );
							preg_match( '#Action: ([0-9A-Za-z-]+)#i', $raw_status, $action );
							preg_match( '#X-Mailster-ID: ([a-f0-9]{32})#i', $org_message, $mailster_id );
							preg_match( '#X-Mailster: ([a-f0-9]{32})#i', $org_message, $hash );
							preg_match( '#X-Mailster-Campaign: (\d+)#i', $org_message, $camp );

							$status         = isset( $status[1] ) ? $status[1] : null;
							$action         = isset( $action[1] ) ? $action[1] : null;
							$mailster_id    = isset( $mailster_id[1] ) ? $mailster_id[1] : null;
							$hash           = isset( $hash[1] ) ? $hash[1] : null;
							$campaign_id    = isset( $camp[1] ) ? (int) $camp[1] : null;
							$campaign_index = null;

							// Get the campaign index.
							if ( false !== strpos( (string) $campaign_id, '-' ) ) {
								$campaign_index = absint( strrchr( $campaign_id, '-' ) );
								$campaign_id    = absint( $campaign_id );
							}
						}

						break;

					}
				}
			}

			if ( ! empty( $hash ) && mailster_option( 'ID' ) === $mailster_id ) {

				$subscriber = mailster( 'subscribers' )->get_by_hash( $hash, false );

				if ( $subscriber ) {

					switch ( $action ) {
						case 'success':
							// No action on delayed, because Gmail handles that.
						case 'delayed':
							break;
						case 'unsubscribe':
							// Unsubscribe.
							if ( version_compare( MAILSTER_VERSION, '3.0', '<' ) ) {
								mailster( 'subscribers' )->unsubscribe( $subscriber->ID, $campaign_id, 'list_unsubscribe' );
							} else {
								mailster( 'subscribers' )->unsubscribe( $subscriber->ID, $campaign_id, 'list_unsubscribe', $campaign_index );
							}
							break;
						case 'failed':
							// Hard bounce.
							if ( version_compare( MAILSTER_VERSION, '3.0', '<' ) ) {
								mailster( 'subscribers' )->bounce( $subscriber->ID, $campaign_id, true, $info );
							} else {
								mailster( 'subscribers' )->bounce( $subscriber->ID, $campaign_id, true, $info, $campaign_index );
							}
							break;

						case 'transient':
						default:
							// Soft bounce.
							if ( version_compare( MAILSTER_VERSION, '3.0', '<' ) ) {
								mailster( 'subscribers' )->bounce( $subscriber->ID, $campaign_id, false, $info );
							} else {
								mailster( 'subscribers' )->bounce( $subscriber->ID, $campaign_id, false, $info, $campaign_index );
							}
					}
				}

				$messages_todelete[] = $message->id;

			}
		}

		if ( ! empty( $messages_todelete ) ) {
			try {
				$request = new Mailster\Gmail\Google\Service\Gmail\BatchDeleteMessagesRequest();
				$request->setIds( $messages_todelete );
				$service->users_messages->batchDelete( $user_id, $request );
			} catch ( Exception $e ) {
				// Leave bounce messages in the mailbox when batch delete fails.
				unset( $e );
			}
		}

		set_transient( '_mailster_gmail_last_bounce_check', time() );
	}


	/**
	 * Passes subscriber errors through unchanged.
	 *
	 * @since 1.0
	 *
	 * @param array $errors Subscriber error messages.
	 * @return array Unchanged subscriber error messages.
	 */
	public function subscriber_errors( $errors ) {
		return $errors;
	}


	/**
	 * Displays a note that Gmail handles bouncing.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function section_tab_bounce() {

		?>
		<div class="error inline"><p><strong><?php esc_html_e( 'Bouncing is handled by Gmail so all your settings will be ignored', 'mailster-gmail' ); ?></strong></p></div>
		<?php
	}


	/**
	 * Notice if Mailster is not available.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function notice() {
		?>
	<div id="message" class="error">
		<p>
			<strong><?php esc_html_e( 'Gmail integration for Mailster', 'mailster-gmail' ); ?></strong>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: Mailster plugin link, 2: required Mailster version. */
					__( 'requires the %1$s, at least version %2$s.', 'mailster-gmail' ),
					'<a href="https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=plugin&utm_term=Gmail">' . esc_html__( 'Mailster Newsletter Plugin', 'mailster-gmail' ) . '</a>',
					'<strong>' . esc_html( MAILSTER_GMAIL_REQUIRED_VERSION ) . '</strong>'
				),
				array(
					'a'      => array(
						'href' => true,
					),
					'strong' => array(),
				)
			);
			?>
		</p>
	</div>
		<?php
	}


	/**
	 * Sets default Gmail options and points the user at the delivery settings.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function activate() {

		if ( function_exists( 'mailster' ) ) {

			mailster_notice(
				sprintf(
					/* translators: %s: settings page link. */
					esc_html__( 'Change the delivery method on the %s!', 'mailster-gmail' ),
					'<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=delivery_method#delivery">' . esc_html__( 'Settings Page', 'mailster-gmail' ) . '</a>'
				),
				'',
				360,
				'delivery_method'
			);

			$defaults = array(
				'gmail_client_id'     => '',
				'gmail_client_secret' => '',
				'gmail_token'         => '',
			);

			$mailster_options = mailster_options();

			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $mailster_options[ $key ] ) ) {
					mailster_update_option( $key, $value );
				}
			}
		}
	}


	/**
	 * Switches delivery back to the simple method when this integration is deactivated.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function deactivate() {

		if ( function_exists( 'mailster' ) ) {
			if ( 'gmail' === mailster_option( 'deliverymethod' ) ) {
				mailster_update_option( 'deliverymethod', 'simple' );
				mailster_notice(
					sprintf(
						/* translators: %s: settings page link. */
						esc_html__( 'Change the delivery method on the %s!', 'mailster-gmail' ),
						'<a href="edit.php?post_type=newsletter&page=mailster_settings&mailster_remove_notice=delivery_method#delivery">' . esc_html__( 'Settings Page', 'mailster-gmail' ) . '</a>'
					),
					'',
					360,
					'delivery_method'
				);
			}
		}
	}
}
