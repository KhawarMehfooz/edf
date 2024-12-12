<?php
/**
 * Plugin Name: Email Domain Filter for WooCommerce
 * Description: A plugin to filter out specified email domains from receiving WooCommerce order notifications.
 * Version: 1.0.0
 * Author: Khawar Mehfooz
 * Author URI: https://khawarmehfooz.com
 * Text Domain: khwr-edf
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Email_Domain_Filter {

    public function __construct() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_inactive_notice' ) );
            return;
        }

        // Load plugin functionality
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'woocommerce_email_recipient_customer_completed_order', array( $this, 'filter_email_domains' ), 10, 2 );
        add_filter( 'woocommerce_email_recipient_new_order', array( $this, 'filter_email_domains' ), 10, 2 );
    }

    /**
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        // Check if WooCommerce is active
        if ( class_exists( 'WooCommerce' ) ) {
            return true;
        }

        // Check active plugins if WooCommerce is in the list
        $active_plugins = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, (array) get_site_option( 'active_sitewide_plugins', array() ) );
        }

        foreach ( $active_plugins as $plugin ) {
            if ( strpos( $plugin, 'woocommerce.php' ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display an admin notice if WooCommerce is inactive.
     */
    public function woocommerce_inactive_notice() {
        ?>
        <div class="error notice">
            <p><?php esc_html_e( 'The Email Domain Filter for WooCommerce plugin requires WooCommerce to be installed and active.', 'khwr-edf' ); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'khwr-edf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Register the admin page.
     */
    public function register_admin_page() {
        add_menu_page(
            __( 'Email Domain Filter', 'khwr-edf' ),
            __( 'Domain Filter', 'khwr-edf' ),
            'manage_options',
            'khwr-edf',
            array( $this, 'admin_page_content' ),
            'dashicons-email',
            26
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'email_domain_filter_settings', 'excluded_email_domains' );
    }

    /**
     * Display the admin page content.
     */
    public function admin_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Email Domain Filter', 'khwr-edf' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'email_domain_filter_settings' );
                do_settings_sections( 'email_domain_filter_settings' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Excluded Domains', 'khwr-edf' ); ?></th>
                        <td>
                            <textarea name="excluded_email_domains" rows="5" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'excluded_email_domains', '' ) ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Enter the email domains to exclude, one per line. For example: example.com', 'khwr-edf' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Filter WooCommerce email recipients.
     *
     * @param string $recipient The email recipient.
     * @param object $order The WooCommerce order object.
     *
     * @return string
     */
    public function filter_email_domains( $recipient, $order ) {
        $excluded_domains = get_option( 'excluded_email_domains', '' );

        if ( empty( $excluded_domains ) ) {
            return $recipient;
        }

        // Convert excluded domains into an array
        $excluded_domains = array_map( 'trim', explode( "\n", $excluded_domains ) );

        // Get the recipient's email domain
        $recipient_domain = substr( strrchr( $recipient, '@' ), 1 );

        // Check if the recipient's domain is in the excluded list
        if ( in_array( $recipient_domain, $excluded_domains, true ) ) {
            return ''; // Prevent the email from being sent
        }

        return $recipient;
    }
}

// Initialize the plugin
new Email_Domain_Filter();
