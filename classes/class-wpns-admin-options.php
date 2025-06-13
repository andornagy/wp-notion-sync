<?php

namespace WP_Notion_Sync;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class Admin_Options
{
    public function __construct()
    {
        $this->add_hooks();
    }

    /**
     * Add WordPress hooks for the admin options page.
     */
    private function add_hooks()
    {
        // Add the plugin's top-level admin menu page
        add_action('admin_menu', [$this, 'add_plugin_menu_page']);

        // Register plugin settings for saving/loading
        add_action('admin_init', [$this, 'register_plugin_settings']);
    }

    /**
     * Add the top-level menu page for the plugin.
     */
    public function add_plugin_menu_page()
    {
        add_menu_page(
            __('Notion Sync Settings', 'wp-notion-sync'), // Page title
            __('Notion Sync', 'wp-notion-sync'),           // Menu title
            'manage_options',                                // Capability required to access
            'wpns-settings',                                 // Menu slug
            [$this, 'render_options_page'],                // Callback function to render the page content
            'dashicons-cloud',                               // Icon URL or Dashicon class
            80                                               // Position in the menu order
        );
    }

    /**
     * Register plugin settings, sections, and fields.
     */
    public function register_plugin_settings()
    {
        // Register the setting itself. 'wpns_options' will be the name of the option in the wp_options table.
        register_setting(
            'wpns_options_group', // Option group
            'wpns_options',       // Option name (will be an array)
            [$this, 'sanitize_options'] // Sanitize callback
        );

        // Add a settings section
        add_settings_section(
            'wpns_api_settings_section',    // ID of the section
            __('Notion API Settings', 'wp-notion-sync'), // Title of the section
            [$this, 'render_api_settings_section_callback'], // Callback to render section intro
            'wpns-settings'                 // Page slug on which to show the section
        );

        // Add settings fields within the section
        add_settings_field(
            'wpns_notion_api_key',                          // ID of the field
            __('Notion API Key', 'wp-notion-sync'),       // Title of the field
            [$this, 'render_text_field_callback'],       // Callback to render the field HTML
            'wpns-settings',                                // Page slug
            'wpns_api_settings_section',                    // Section ID
            [
                'label_for' => 'wpns_notion_api_key',
                'name'      => 'notion_api_key',
                'type'      => 'password', // Use 'password' for API keys for security
                'description' => __('Your Notion integration token (starts with <code>secret_</code>). Make sure your integration has access to the pages/databases you want to sync.', 'wp-notion-sync'),
            ]
        );

        add_settings_field(
            'wpns_notion_database_id',                      // ID of the field
            __('Notion Database ID', 'wp-notion-sync'),   // Title of the field
            [$this, 'render_text_field_callback'],       // Callback to render the field HTML
            'wpns-settings',                                // Page slug
            'wpns_api_settings_section',                    // Section ID
            [
                'label_for' => 'wpns_notion_database_id',
                'name'      => 'notion_database_id',
                'type'      => 'text',
                'description' => __('The ID of your Notion database. You can find this in the database URL.', 'wp-notion-sync'),
            ]
        );

        // You can add more fields here for other IDs (e.g., specific page IDs)
        // add_settings_field(
        //     'wpns_notion_template_page_id',
        //     __( 'Notion Template Page ID', 'wp-notion-sync' ),
        //     [ $this, 'render_text_field_callback' ],
        //     'wpns-settings',
        //     'wpns_api_settings_section',
        //     [
        //         'label_for' => 'wpns_notion_template_page_id',
        //         'name'      => 'notion_template_page_id',
        //         'type'      => 'text',
        //         'description' => __( 'The ID of a specific Notion page to use as a template.', 'wp-notion-sync' ),
        //     ]
        // );

    }

    /**
     * Renders the description for the API settings section.
     */
    public function render_api_settings_section_callback()
    {
        echo '<p>' . esc_html__('Configure your Notion API credentials and specific IDs here.', 'wp-notion-sync') . '</p>';
    }

    /**
     * Renders a generic text/password input field.
     *
     * @param array $args Array of arguments for the field.
     */
    public function render_text_field_callback($args)
    {
        $options = get_option('wpns_options');
        $value   = isset($options[$args['name']]) ? sanitize_text_field($options[$args['name']]) : '';

        printf(
            '<input type="%1$s" id="%2$s" name="wpns_options[%3$s]" value="%4$s" class="regular-text" />',
            esc_attr($args['type']),
            esc_attr($args['label_for']),
            esc_attr($args['name']),
            esc_attr($value)
        );

        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', wp_kses_post($args['description']));
        }
    }

    /**
     * Renders the main options page.
     */
    public function render_options_page()
    {
        // Check user capabilities
        if (! current_user_can('manage_options')) {
            return;
        }

        // Display success/error messages (WordPress handles this automatically for register_setting)
        settings_errors('wpns_options_group');

?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields for the registered setting "wpns_options_group"
                settings_fields('wpns_options_group');
                // Output setting sections and their fields
                do_settings_sections('wpns-settings');
                // Output save button
                submit_button(__('Save Settings', 'wp-notion-sync'));
                ?>
            </form>
        </div>
<?php
    }

    /**
     * Sanitize the plugin options before saving.
     *
     * @param array $input The raw input array from the form.
     * @return array The sanitized array.
     */
    public function sanitize_options($input)
    {
        $sanitized_input = [];

        if (isset($input['notion_api_key'])) {
            // API keys are sensitive; use sanitize_text_field, but be aware it removes some characters.
            // For true API keys, you might just cast to string or use a custom validation.
            // If the key can contain special characters, raw string storage might be needed,
            // but for security, ensure it's not HTML escaped accidentally.
            $sanitized_input['notion_api_key'] = sanitize_text_field($input['notion_api_key']);
        }

        if (isset($input['notion_database_id'])) {
            // Database IDs are alphanumeric, sanitize_text_field is usually fine.
            $sanitized_input['notion_database_id'] = sanitize_text_field($input['notion_database_id']);
        }

        // Add sanitization for other fields as you add them
        // if ( isset( $input['notion_template_page_id'] ) ) {
        //     $sanitized_input['notion_template_page_id'] = sanitize_text_field( $input['notion_template_page_id'] );
        // }

        Logger::log('Notion Sync options sanitized and saved.');

        return $sanitized_input;
    }
}
