<?php

class AP_Stream_Mattermost_API {

	public $stream;
	public $options;

	public function __construct() {
        load_plugin_textdomain( 'ap-stream-to-mattermost', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( ! class_exists( 'WP_Stream\Plugin' ) ) {
			add_action( 'admin_notices', array( $this, 'stream_not_found_notice' ) );
			return false;
		}

		$this->stream = wp_stream_get_instance();
		$this->options = $this->stream->settings->options;

		add_filter( 'wp_stream_settings_option_fields', array( $this, 'options' ) );

		if ( empty( $this->options['mattermost_destination'] ) ) {
			add_action( 'admin_notices', array( $this, 'destination_undefined_notice' ) );
		}
		else {
			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
		}
	}

	public function options( $fields ) {

		$settings = array(
			'title' => esc_html__( 'Mattermost', 'ap-stream-to-mattermost' ),
			'fields' => array(
				array(
					'name'        => 'destination',
					'title'       => esc_html__( 'Webhook URL', 'ap-stream-to-mattermost' ),
					'type'        => 'text',
					'desc'        => esc_html__( 'Find your Incoming Webhook URL in the "Integrations" section of your mattermost settings.' , 'ap-stream-to-mattermost' ),
					'default'     => '',
				),
				array(
					'name'        => 'username',
					'title'       => esc_html__( 'Bot Name', 'ap-stream-to-mattermost' ),
					'type'        => 'text',
					'desc'        => esc_html__( 'This allows you to define the name of the bot that posts your message' , 'ap-stream-to-mattermost' ),
					'default'     => '',
				),
				array(
					'name'        => 'channel',
					'title'       => esc_html__( 'Channel', 'ap-stream-to-mattermost' ),
					'type'        => 'text',
					'desc'        => esc_html__( 'Event the name of the channel you\'d like to post to. This should include the #' , 'ap-stream-to-mattermost' ),
					'default'     => '',
				),
				array(
					'name'        => 'icon_emoji',
					'title'       => esc_html__( 'Icon Emoji', 'ap-stream-to-mattermost' ),
					'type'        => 'text',
					'desc'        => wp_kses_post( 'Use an Emoji as an update icon like :gear: or :eye: or any other you have in your mattermost' ),
					'default'     => '',
				),
				array(
					'name'        => 'message',
					'title'       => esc_html__( 'Message to Mattermost', 'ap-stream-to-mattermost' ),
					'type'        => 'textarea',
					'desc'        => esc_html__( 'Message to Mattermost. You can use this macros: %ip%, %action%, %context%, %connector%, %summary%, %created%, %user_role%, %user_id%, %blog_id%, %site_id%, %object_id%, %site_domain% and %user_login%', 'ap-stream-to-mattermost' ),
					'default'     => '%summary%',
				),
			)
		);

		$fields['mattermost'] = $settings;

		return $fields;

	}

	public function log( $record_id, $record_array ) {

		$record = $record_array;

		$this->send_remote_syslog( $record );
	}

	/**
	 * This sends data to Mattermost
	 */
	public function send_remote_syslog( $message ) {
		$url = $this->options['mattermost_destination'];

		$site_domain = get_site_url( $message[ 'blog_id' ] );
		$user = get_user_by( 'id', $message[ 'user_id' ] );
		$messagesend = strtr( $this->options['mattermost_message'],
							  apply_filters( 'steam_to_mattermost_message_macros',
								  array( '%ip%' => $message[ 'ip' ],
								     '%action%' => $message[ 'action' ],
								     '%context%' => $message[ 'context' ],
								     '%connector%' => $message[ 'connector' ],
								     '%summary%' => $message[ 'summary' ],
								     '%created%' => $message[ 'created' ],
								     '%user_role%' => $message[ 'user_role' ],
								     '%user_id%' => $message[ 'user_id' ],
								     '%blog_id%' => $message[ 'blog_id' ],
								     '%site_id%' => $message[ 'site_id' ],
								     '%object_id%' => $message[ 'object_id' ],
							         '%site_domain%' => $site_domain,
							         '%user_login%' => $user->user_login,
								  ) ) );

        $channel = '';
        $username = '';
        $icon_emoji = '';
        if ( array_key_exists( 'mattermost_channel', $this->options ) ) {
            $channel = $this->options['mattermost_channel'];
        }
        if ( array_key_exists( 'mattermost_username', $this->options ) ) {
            $username = $this->options['mattermost_username'];
        }
        if ( array_key_exists( 'mattermost_icon_emoji', $this->options ) ) {
            $icon_emoji = $this->options['mattermost_icon_emoji'];
        }

		$data = array(
				'channel'      => $channel,
				'username'     => $username,
				'text'         => $messagesend,
				'icon_emoji'   => $icon_emoji,
			);
		$data_string = utf8_encode( json_encode($data));

		wp_remote_post($url, array(
			'sslverify' => apply_filters('steam_to_mattermost_ssl_verify', true ),
			'headers' => array(
				'Content-Type' =>  'application/json',
				'Content-Length' => strlen($data_string)
			),
			'body' => utf8_encode( $data_string)
		));
	}


	public function destination_undefined_notice() {
		$class = 'error';
		$message = __( 'To activate the "Stream to Mattermost" plugin, visit the Mattermost panel in <a href="' . admin_url( 'admin.php?page=wp_stream_settings' ) . '">Stream Settings</a> and set an Incoming Webhook URL.', 'ap-stream-to-matermost' );
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';

	}

	public function stream_not_found_notice() {
		$class = 'error';
		$message = __( 'The "Stream to Mattermost" plugin requires the <a href="https://wordpress.org/plugins/stream/">Stream</a> plugin to be activated before it can log to mattermost.', 'ap-stream-to-mattermost' );
		echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
	}

}
