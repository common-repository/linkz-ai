<?php
/**
 * Plugin Name: Linkz.ai
 * Description: Improve website & blog engagement with link auto-preview popups
 * Author: Linkz.ai
 * Author URI: https://linkz.ai/
 * Version: 1.2.0
 * Text Domain: linkz-ai
 * Requires at least: 4.7
 * Requires PHP: 5.4
 */

defined('ABSPATH') or die();

// Define plugin constants.
define('LINKZ_VERSION', get_file_data(__FILE__, array('Version'), 'plugin')[0]);
define('LINKZ_BASE_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('LINKZ_BASE_DIR', trailingslashit(__DIR__));

/**
 * Main plugin class for Linkz.ai WordPress Plugin.
 *
 * Handles plugin initialization, admin menus, scripts, AJAX handlers, and interactions with the Linkz.ai API.
 */
class LinkzAi
{
    /** @var LinkzAi Singleton instance of the class */
    protected static $instance;

    /**
     * Retrieves the singleton instance of the LinkzAi class.
     *
     * @return LinkzAi Singleton instance of the LinkzAi class.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initializes the plugin by setting up actions and filters.
     */
    public function __construct()
    {
        // Hook into WordPress actions.
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_action('admin_menu', array($this, 'register_pages'));
        add_action('admin_init', array($this, 'check_auth'));
        add_action('admin_init', array($this, 'check_logout'));
        add_action("wp_ajax_linkz", array($this, 'ajax_linkz'));

        // Add action hooks for sign-in and sign-up handlers
        add_action('admin_init', array($this, 'process_signup'));
        add_action('admin_init', array($this, 'process_signin'));
    }

    /**
     * Enqueues admin scripts and styles.
     *
     * @return void
     */
    public function admin_assets()
    {
        wp_enqueue_script('linkz-admin-script', LINKZ_BASE_URL . "assets/js/admin.js", ['jquery'], LINKZ_VERSION);
        wp_enqueue_style('linkz-admin-style', LINKZ_BASE_URL . "assets/css/admin.css", [], LINKZ_VERSION);

        // Localize script to pass variables to JavaScript
        wp_localize_script('linkz-admin-script', 'linkzAi', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajaxNonce' => wp_create_nonce('linkz_ai_ajax_nonce'),
        ));
    }

    /**
     * Enqueues frontend scripts when Linkz.ai is enabled.
     *
     * @return void
     */
    public function assets()
    {
        $enabled = get_option('linkz_enabled', false);
        $userId = get_option('linkz_uid');

        if ($enabled && $userId) {
            wp_enqueue_script('linkz-website-script', "https://js.linkz.ai/?key={$userId}");
        }
    }

    /**
     * Registers the plugin's admin menu page.
     *
     * @return void
     */
    public function register_pages()
    {
        add_menu_page(
            "Linkz.ai",
            "Linkz.ai",
            'manage_options',
            'linkz-ai',
            array($this, 'render_dashboard'),
            LINKZ_BASE_URL . 'assets/img/icon-16x16.png',
            80
        );
    }

    /**
     * Renders the plugin's dashboard page.
     *
     * Determines the appropriate page to display based on user authentication status and passes necessary data to the dashboard renderer.
     *
     * @return void
     */
    public function render_dashboard()
    {
        require_once LINKZ_BASE_DIR . 'classes/dashboard.php';
        $dashboard = LinkzAiDashboard::get_instance();

        $user_data = $this->linkz_user_data();

        $page = $this->page_name($user_data);
        $args = array(
            'global' => array(
                'logout_url' => wp_nonce_url(admin_url('admin.php?page=linkz-ai&linkz-ai-logout=1'), 'linkz_ai_check_logout'),
                'wp_plugin_url' => "https://wordpress.org/plugins/linkz-ai/",
            ),
        );

        if ($user_data) {
            $domain_name = parse_url(site_url(), PHP_URL_HOST);
            $preview_type = "all";

            // Determine if the current domain is set to skip previews.
            foreach ($user_data['user_metadata']['domains'] as $root_domain) {
                if ($root_domain['name'] != $domain_name) {
                    continue;
                }

                foreach ($root_domain['skipDomains'] as $skip_domain) {
                    if ($skip_domain == $domain_name) {
                        $preview_type = "external";
                        break;
                    }
                }
            }

            $args['enable_links'] = array(
                'enabled' => get_option('linkz_enabled', false),
                'settings_url' => "https://dashboard-test.linkz.ai/",
                'domain_name' => $domain_name,
                'preview_type' => $preview_type,
            );
            $args['plan_data'] = array(
                'analytics' => $user_data['plan']['analytics'],
                'analytics_url' => "https://dashboard-test.linkz.ai/analytics",
                'plan_name' => $user_data['plan']['name'],
                'plan_status' => $user_data['plan']['status'],
                'period_start' => $user_data['plan']['currentPeriodStart'],
                'period_end' => $user_data['plan']['currentPeriodEnd'],
                'n_previews_used' => $user_data['usage']['billingPeriodTotal'],
                'n_previews_limit' => $user_data['plan']['quota'],
                'n_domains' => $user_data['plan']['domains'],
                'branding' => $user_data['plan']['branding'],
                'change_plan_url' => "https://dashboard-test.linkz.ai/",
            );
        } else {
            // Build sign-up and sign-in URLs.
            $sign_args = array(
                'email' => wp_get_current_user()->user_email,
                'signup' => 1,
                'clientCallbackUrl' => admin_url('admin.php?page=linkz-ai'),
                'domain' => site_url(),
                'clientId' => "wordpress",
            );
            $sign_up_query = build_query($sign_args);

            unset($sign_args['signup']);

            $sign_in_query = build_query($sign_args);

            // $args['global']['sign_up_url'] = "https://dashboard-test.linkz.ai/?{$sign_up_query}";
            // $args['global']['sign_in_url'] = "https://dashboard-test.linkz.ai/?{$sign_in_query}";

            $args['global']['sign_up_url'] = wp_nonce_url(admin_url('admin.php?page=linkz-ai&linkz-ai-signup=1'), 'linkz_ai_sign_action');
            $args['global']['sign_in_url'] = wp_nonce_url(admin_url('admin.php?page=linkz-ai&linkz-ai-signin=1'), 'linkz_ai_sign_action');

        }

        $dashboard->draw_page($page, $args);
    }

    /**
     * Checks for authentication parameters in the request and logs the user in if present.
     *
     * @return void
     */
    public function check_auth()
    {

        // Verify user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You are not allowed to perform this action.', 'linkz-ai'), 403);
        }

        // Check if required parameters are present
        if (!isset($_REQUEST['lzToken']) || !isset($_REQUEST['lzUserId'])) {
            return;
        }

        // Sanitize input
        $secret = sanitize_text_field($_REQUEST['lzToken']);
        $userId = sanitize_key($_REQUEST['lzUserId']);

        // Update options securely
        update_option('linkz_token', openssl_encrypt($secret, 'aes-128-ctr', AUTH_SALT));
        update_option('linkz_uid', $userId);
        update_option('linkz_enabled', true);

        wp_redirect(admin_url('admin.php?page=linkz-ai'));
        exit;
    }

    public function process_signup()
    {
        if (!isset($_REQUEST['linkz-ai-signup'])) {
            return;
        }

        // Verify user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You are not allowed to perform this action.', 'linkz-ai'), 403);
        }

        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'linkz_ai_sign_action')) {
            wp_die(__('Nonce verification failed.', 'linkz-ai'), 403);
        }

        // Build the external sign-up URL
        $sign_args = array(
            'email' => wp_get_current_user()->user_email,
            'signup' => 1,
            'clientCallbackUrl' => admin_url('admin.php?page=linkz-ai'),
            'domain' => site_url(),
            'clientId' => "wordpress",
        );
        $sign_up_query = build_query($sign_args);
        $sign_up_url = "https://dashboard-test.linkz.ai/?{$sign_up_query}";

        // Redirect to the external sign-up URL
        wp_redirect($sign_up_url);
        exit;
    }

    public function process_signin()
    {
        if (!isset($_REQUEST['linkz-ai-signin'])) {
            return;
        }

        // Verify user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You are not allowed to perform this action.', 'linkz-ai'), 403);
        }

        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'linkz_ai_sign_action')) {
            wp_die(__('Nonce verification failed.', 'linkz-ai'), 403);
        }

        // Build the external sign-in URL
        $sign_args = array(
            'email' => wp_get_current_user()->user_email,
            // 'signup' parameter is not included for sign-in
            'clientCallbackUrl' => admin_url('admin.php?page=linkz-ai'),
            'domain' => site_url(),
            'clientId' => "wordpress",
        );
        $sign_in_query = build_query($sign_args);
        $sign_in_url = "https://dashboard-test.linkz.ai/?{$sign_in_query}";

        // Redirect to the external sign-in URL
        wp_redirect($sign_in_url);
        exit;
    }

    /**
     * Checks for logout parameter in the request and logs the user out if present.
     *
     * @return void
     */
    public function check_logout()
    {

        // Check if the user has the 'manage_options' capability.
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_REQUEST['linkz-ai-logout'])) {
            return;
        }

        // Verify nonce
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'linkz_ai_check_logout')) {
            return;
        }

        // Remove stored options on logout.
        delete_option('linkz_token');
        delete_option('linkz_uid');
        delete_option('linkz_enabled');

        $logout_args = array(
            'redirectUrl' => admin_url('admin.php?page=linkz-ai'),
        );
        $logout = build_query($logout_args);

        wp_redirect("https://dashboard-test.linkz.ai/logout?{$logout}");
        exit;
    }

    /**
     * Determines the page name based on the user authentication status.
     *
     * @param array|false $user_data User data from the API or false if not authenticated.
     * @return string Page name to render.
     */
    public function page_name($user_data)
    {
        if ($user_data) {
            return 'dashboard';
        }

        return 'welcome';
    }

    /**
     * Retrieves user data from the Linkz.ai API.
     *
     * @return array|false User data array or false on failure.
     */
    private function linkz_user_data()
    {
        return $this->linkz_request();
    }

    /**
     * Sets the preview type (all or external) for the current domain.
     *
     * @param string $mode Preview mode ('all' or 'external').
     * @return string|false The preview mode that was set, or false on failure.
     */
    private function set_preview_type($mode)
    {
        $domain_name = parse_url(site_url(), PHP_URL_HOST);
        $userId = get_option('linkz_uid');

        switch ($mode) {
            case "all":
                {
                    // Remove the domain from the skip list.
                    $response = $this->linkz_request("users/{$userId}/domains/{$domain_name}/skip/{$domain_name}", "DELETE");

                    if (!isset($response['error'])) {
                        return 'all';
                    }
                    break;
                }
            case "external":
                {
                    // Add the domain to the skip list.
                    $response = $this->linkz_request("users/{$userId}/domains/{$domain_name}/skip", "POST", ['domain' => site_url()]);

                    if (!isset($response['error'])) {
                        return 'external';
                    }
                    break;
                }
        }

        return false;
    }

    /**
     * Sends a request to the Linkz.ai API.
     *
     * @param string $endpoint API endpoint to request.
     * @param string $method   HTTP method to use ('GET', 'POST', etc.).
     * @param array  $data     Data to send in the request body (for POST requests).
     * @return array|false Response data array or false on failure.
     */
    private function linkz_request($endpoint = "users/me", $method = 'GET', $data = [])
    {
        $userId = get_option('linkz_uid');
        $secret = get_option('linkz_token');

        if (!$userId || !$secret) {
            return false;
        }

        $secret = openssl_decrypt($secret, 'aes-128-ctr', AUTH_SALT);

        if (!$secret) {
            return false;
        }

        $auth_token = base64_encode("{$userId}:{$secret}");
        $request_url = "https://api.linkz.ai/v1/{$endpoint}";

        $args = [
            'method' => $method,
            'httpversion' => '1.1',
            'headers' => ["Authorization" => "Basic {$auth_token}"],
        ];

        if ($method == 'POST' && !empty($data)) {
            $args['headers']['Content-Type'] = "application/json";
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($request_url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Handles AJAX requests for enabling/disabling Linkz.ai and setting preview type.
     *
     * @return void
     */
    public function ajax_linkz()
    {

        if (!current_user_can('manage_options')) {
            wp_send_json(['ok' => false, 'error' => 'Unauthorized']);
            wp_die();
        }

        check_ajax_referer('linkz_ai_ajax_nonce', '_ajax_nonce');

        if (isset($_POST['linkz_enabled'])) {
            $linkz_enabled = sanitize_key($_POST['linkz_enabled']);

            if (!in_array($linkz_enabled, [0, 1, '0', '1', true, false], true)) {
                wp_send_json(['ok' => false]);
                wp_die();
            }

            update_option('linkz_enabled', $linkz_enabled);
            wp_send_json(['ok' => true, 'status' => $linkz_enabled]);
            wp_die();
        }

        if (isset($_POST['preview_type'])) {
            $preview_type = $this->set_preview_type(sanitize_key($_POST['preview_type']));

            if ($preview_type) {
                wp_send_json(['ok' => true, 'preview_type' => $preview_type]);
            } else {
                wp_send_json(['ok' => false]);
            }
            wp_die();
        }

        wp_die();
    }
}

// Initialize the plugin.
LinkzAi::get_instance();
