<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: CBP Change Freeze
 * Plugin URI: https://github.com/ChillibyteUK/cbp-change-freeze
 * Description: Notifies logged-in users when a change freeze is in effect via admin bar color change and dashboard banner.
 * Version: 1.0.0
 * Author: Chillibyte - DS
 * Author URI: https://github.com/ChillibyteUK
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cbp-change-freeze
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class for CBP Change Freeze.
 *
 * Handles change freeze notifications via admin bar styling and dashboard banners.
 */
class CBP_Change_Freeze {

    /**
     * Singleton instance of the class.
     *
     * @var CBP_Change_Freeze|null
     */
    private static $instance = null;

    /**
     * Option name for storing plugin settings.
     *
     * @var string
     */
    private $option_name = 'cbp_change_freeze_settings';

    /**
     * Get the singleton instance of the class.
     *
     * @return CBP_Change_Freeze The singleton instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to initialize the plugin.
     *
     * Sets up WordPress hooks for admin menu, settings, notices, and admin bar modifications.
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'show_dashboard_banner' ) );
        add_action( 'admin_bar_menu', array( $this, 'modify_admin_bar' ), 999 );
        add_action( 'admin_head', array( $this, 'admin_bar_styles' ) );
        add_action( 'wp_head', array( $this, 'admin_bar_styles' ) );
    }

    /**
     * Add admin menu page for plugin settings.
     *
     * Registers a settings page under the WordPress Settings menu.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Change Freeze Settings', 'cbp-change-freeze' ),
            __( 'Change Freeze', 'cbp-change-freeze' ),
            'manage_options',
            'cbp-change-freeze',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * Registers the plugin settings group and sanitization callback.
     */
    public function register_settings() {
        register_setting( 'cbp_change_freeze_group', $this->option_name, array( $this, 'sanitize_settings' ) );
    }

    /**
     * Sanitize plugin settings input.
     *
     * @param array $input The input array from the settings form.
     * @return array The sanitized settings array.
     */
    public function sanitize_settings( $input ) {
        $sanitized            = array();
        $sanitized['enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;
        $sanitized['message'] = wp_kses_post( $input['message'] );
        $sanitized['date']    = sanitize_text_field( $input['date'] );
        return $sanitized;
    }

    /**
     * Render the plugin settings page.
     *
     * Displays the settings form for managing change freeze notifications.
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'cbp_change_freeze_group' );
                do_settings_sections( 'cbp_change_freeze_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cbp_freeze_enabled"><?php esc_html_e( 'Enable Change Freeze', 'cbp-change-freeze' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
								id="cbp_freeze_enabled" 
								name="<?php echo esc_attr( $this->option_name ); ?>[enabled]" 
								value="1" 
								<?php checked( $settings['enabled'], 1 ); ?> />
                            <p class="description"><?php esc_html_e( 'When enabled, displays change freeze notifications to all logged-in users.', 'cbp-change-freeze' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cbp_freeze_message"><?php esc_html_e( 'Message', 'cbp-change-freeze' ); ?></label>
                        </th>
                        <td>
                            <textarea id="cbp_freeze_message" 
								name="<?php echo esc_attr( $this->option_name ); ?>[message]" 
								rows="4" 
								cols="50" 
								class="large-text"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'The message to display. HTML links are allowed.', 'cbp-change-freeze' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cbp_freeze_date"><?php esc_html_e( 'Date (Optional)', 'cbp-change-freeze' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
								id="cbp_freeze_date" 
								name="<?php echo esc_attr( $this->option_name ); ?>[date]" 
								value="<?php echo esc_attr( $settings['date'] ); ?>" 
								class="regular-text" 
								placeholder="e.g., January 25, 2026" />
                            <p class="description"><?php esc_html_e( 'Optional date to display in the notification (purely informational).', 'cbp-change-freeze' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get plugin settings with default values.
     *
     * @return array The plugin settings array with enabled, message, and date keys.
     */
    private function get_settings() {
        $defaults = array(
            'enabled' => 0,
            'message' => '',
            'date'    => '',
        );
        return wp_parse_args( get_option( $this->option_name, array() ), $defaults );
    }

    /**
     * Display change freeze banner on the dashboard.
     *
     * Shows a warning notice on the dashboard when change freeze is enabled.
     */
    public function show_dashboard_banner() {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] || ! is_user_logged_in() ) {
            return;
        }

        $screen = get_current_screen();
        if ( 'dashboard' !== $screen->id ) {
            return;
        }

        $title = __( 'CHANGE FREEZE IN EFFECT', 'cbp-change-freeze' );
        if ( ! empty( $settings['date'] ) ) {
            $title .= ' UNTIL <strong>' . esc_html( $settings['date'] ) . '</strong>';
        }

        // Make links open in new tabs.
        $message = $settings['message'];
        $message = preg_replace( '/<a\s+href=/i', '<a target="_blank" rel="noopener noreferrer" href=', $message );
        ?>
        <div class="notice notice-warning cbp-change-freeze-banner" style="border-left-color: #b22222; background: #fcf0f1;">
            <p style="font-size: 14px; font-weight: 600;">
                <span class="dashicons dashicons-warning" style="color: #b22222; vertical-align: middle;"></span>
                <?php echo wp_kses_post( $title ); ?>
            </p>
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
        <?php
    }

    /**
     * Modify the admin bar to show change freeze notification.
     *
     * Adds a custom node to the admin bar when change freeze is enabled.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WordPress admin bar object.
     */
    public function modify_admin_bar( $wp_admin_bar ) {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] || ! is_user_logged_in() ) {
            return;
        }

        $message = $settings['message'];
        if ( ! empty( $settings['date'] ) ) {
            $message .= ' - ' . esc_html( $settings['date'] );
        }

        $wp_admin_bar->add_node(
			array(
				'id'    => 'cbp-change-freeze-notice',
				'title' => '<span class="ab-icon dashicons dashicons-warning" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'CHANGE FREEZE', 'cbp-change-freeze' ) . '</span>',
				'href'  => admin_url( 'options-general.php?page=cbp-change-freeze' ),
                'meta'  => array(
                    'title' => wp_strip_all_tags( $message ),
                ),
            )
		);
    }

    /**
     * Output custom styles for the admin bar during change freeze.
     *
     * Changes the admin bar background color and adds styling for the change freeze notice.
     */
    public function admin_bar_styles() {
        $settings = $this->get_settings();

        if ( ! $settings['enabled'] || ! is_user_logged_in() || ! is_admin_bar_showing() ) {
            return;
        }
        ?>
        <style type="text/css">
            #wpadminbar {
                background: #b22222 !important;
            }
            #wpadminbar .ab-item,
            #wpadminbar a.ab-item,
            #wpadminbar > #wp-toolbar span.ab-label,
            #wpadminbar > #wp-toolbar span.noticon {
                color: #ffffff !important;
            }
            #wpadminbar .ab-icon:before,
            #wpadminbar .ab-item:before {
                color: #ffffff !important;
            }
            #wpadminbar .ab-top-menu > li.hover > .ab-item,
            #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus,
            #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
            #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
                background: #8b1a1a !important;
                color: #ffffff !important;
            }
            #wpadminbar li#wp-admin-bar-cbp-change-freeze-notice {
                background: #ffffff !important;
            }
            #wpadminbar li#wp-admin-bar-cbp-change-freeze-notice a {
                color: #b22222 !important;
                font-weight: 600 !important;
            }
        </style>
        <?php
    }

    /**
     * Initialize the plugin on plugins_loaded hook.
     *
     * @return CBP_Change_Freeze The plugin instance.
     */
    public static function init() {
        return self::get_instance();
    }
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'CBP_Change_Freeze', 'init' ) );
