<?php

/**
 * Plugin Name:       User Online
 * Plugin URI:        github.com/jonathanitz/user-online
 * Description:       Plugin that allows developers to easily show if users are online
 * Version:           0.0.1
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Jonathan Itzen
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       user-online
 * Domain Path:       /languages
*/

if ( ! function_exists( 'write_log' ) ) {
    // To use this function, add these to your wp-config.php
    // define('WP_DEBUG', true);
    // define('WP_DEBUG_DISPLAY', false);
    // define('WP_DEBUG_LOG', true);
    // set_time_limit(1800);

    function write_log( $log ) {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}

class LiveUser {
    // Directory of user online plugin
    protected $plugin_directory;

    // Meta key used to determine if user is online
    protected static $meta_key = 'user_online';

    function __construct() {
        $this->plugin_directory = plugins_url( '/user-online' );

        // Later functionality
        // add_action( 'wp_enqueue_scripts', [ $this, 'user_online_javascript' ] );

        add_filter( 'heartbeat_received', [ $this, 'user_online_heartbeat' ], 10, 2 );
        add_filter( 'heartbeat_nopriv_received', [ $this, 'user_online_heartbeat' ], 10, 2 );

        add_action( 'template_redirect', [ $this, 'update_user_status' ] );
    }

    public function user_online_javascript() {
        wp_register_script( 'user_online_javascript', $this->plugin_directory . '/main.js', [ 'jquery', 'heartbeat' ], false, false );
        wp_enqueue_script( 'user_online_javascript' );
    }

    public static function is_user_online( int $user_id = 0 ) {

        // Check if the current user is the user we're checking to be online
        $is_users_id = false;
        if( $user_id === get_current_user_id() ) $is_users_id = true;

        // Get the last time they were online
        $is_user_online = get_user_meta( $user_id, self::$meta_key, true );

        $now = new DateTime( 'now' );

        // If this is not the current user's ID and the user is online return
        // the online status of the user
        if( ! $is_users_id && $is_user_online ) {
            $last_signed_in = new DateTime( $is_user_online );

            // Add a 1 minute cushion
            $last_signed_in->modify( '+1 minutes' );

            // If this is inverted, the user is still online
            if( $last_signed_in->diff( $now )->invert ) return $is_user_online;

            return false;

        } elseif( ! $is_users_id && ! $is_user_online ) return false;

        // Format date for database
        $now = $now->format( 'Y-m-d H:i:s' );

        // Update the users last online date
        update_user_meta( $user_id, self::$meta_key, $now );

        // Return true cause we know the current user is online
        return true;
    }

    public function update_user_status() {
        if( $user_id = get_current_user_id() ) self::is_user_online( $user_id );
    }

    /**
     * Receive Heartbeat data and respond.
     *
     * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
     *
     * @param array $response Heartbeat response data to pass back to front end.
     * @param array $data     Data received from the front end (unslashed).
     *
     * @return array
     */
    public function user_online_heartbeat( array $response, array $data ) {
        // If we didn't receive our data, don't send any back.
        if ( empty( $data['myplugin_customfield'] ) ) {
            return $response;
        }

        // Calculate our data and pass it back. For this example, we'll hash it.
        $received_data = $data['myplugin_customfield'];

        $response['myplugin_customfield_hashed'] = sha1( $received_data );
        return $response;
    }
}

function live_user() {
    return new LiveUser;
}

live_user();
