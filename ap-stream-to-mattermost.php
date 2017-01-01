<?php
/**
 * Plugin Name: AP Stream to Mattermost
 * Plugin URI: https://wordpress.org/plugins/ap-stream-to-mattermost/
 * Description: Send Stream logs to rocket.chat.
 * Author: f.staude, stk_jj
 * Version: 0.0.1
 * Author URI: https://staude.net/
 * Text Domain: ap-stream-to-mattermost
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/adminpress/ap-stream-to-mattermost
 * GitHub Branch: master
 */

require_once dirname( __FILE__ ) . '/inc/class-ap-stream-mattermost-api.php';

function register_ap_stream_mattermost() {
	$stream_mattermost = new AP_Stream_Mattermost_API();
}
add_action( 'init', 'register_ap_stream_mattermost' );

