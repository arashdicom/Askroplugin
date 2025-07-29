<?php
/**
 * Askro Standards Helper Class
 * 
 * Provides unified WordPress coding standards compliance
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Standards_Helper Class
 * 
 * Handles WordPress coding standards compliance
 * 
 * @since 1.0.0
 */
class Askro_Standards_Helper {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'askro';
    
    /**
     * Plugin prefix for functions and constants
     */
    const PREFIX = 'askro';
    
    /**
     * Default hook priority
     */
    const DEFAULT_PRIORITY = 10;
    
    /**
     * Add action with proper standards
     * 
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority (optional)
     * @param int $accepted_args Number of accepted arguments (optional)
     */
    public function add_action($hook, $callback, $priority = null, $accepted_args = 1) {
        $priority = $priority ?? self::DEFAULT_PRIORITY;
        add_action($hook, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add filter with proper standards
     * 
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority (optional)
     * @param int $accepted_args Number of accepted arguments (optional)
     */
    public function add_filter($hook, $callback, $priority = null, $accepted_args = 1) {
        $priority = $priority ?? self::DEFAULT_PRIORITY;
        add_filter($hook, $callback, $priority, $accepted_args);
    }
    
    /**
     * Register post type with proper standards
     * 
     * @param string $post_type Post type name
     * @param array $args Post type arguments
     */
    public function register_post_type($post_type, $args = []) {
        $defaults = [
            'labels' => $this->get_post_type_labels($post_type),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'],
            'rewrite' => ['slug' => $post_type],
            'menu_icon' => 'dashicons-format-chat',
            'show_in_rest' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        register_post_type($post_type, $args);
    }
    
    /**
     * Register taxonomy with proper standards
     * 
     * @param string $taxonomy Taxonomy name
     * @param array $post_types Post types to register with
     * @param array $args Taxonomy arguments
     */
    public function register_taxonomy($taxonomy, $post_types, $args = []) {
        $defaults = [
            'labels' => $this->get_taxonomy_labels($taxonomy),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => $taxonomy]
        ];
        
        $args = wp_parse_args($args, $defaults);
        register_taxonomy($taxonomy, $post_types, $args);
    }
    
    /**
     * Get post type labels
     * 
     * @param string $post_type Post type name
     * @return array Labels array
     */
    private function get_post_type_labels($post_type) {
        $singular = ucfirst(str_replace('askro_', '', $post_type));
        $plural = $singular . 's';
        
        return [
            'name' => __($plural, self::TEXT_DOMAIN),
            'singular_name' => __($singular, self::TEXT_DOMAIN),
            'menu_name' => __($plural, self::TEXT_DOMAIN),
            'name_admin_bar' => __($singular, self::TEXT_DOMAIN),
            'add_new' => __('Add New', self::TEXT_DOMAIN),
            'add_new_item' => sprintf(__('Add New %s', self::TEXT_DOMAIN), $singular),
            'new_item' => sprintf(__('New %s', self::TEXT_DOMAIN), $singular),
            'edit_item' => sprintf(__('Edit %s', self::TEXT_DOMAIN), $singular),
            'view_item' => sprintf(__('View %s', self::TEXT_DOMAIN), $singular),
            'all_items' => sprintf(__('All %s', self::TEXT_DOMAIN), $plural),
            'search_items' => sprintf(__('Search %s', self::TEXT_DOMAIN), $plural),
            'parent_item_colon' => sprintf(__('Parent %s:', self::TEXT_DOMAIN), $plural),
            'not_found' => sprintf(__('No %s found.', self::TEXT_DOMAIN), strtolower($plural)),
            'not_found_in_trash' => sprintf(__('No %s found in Trash.', self::TEXT_DOMAIN), strtolower($plural))
        ];
    }
    
    /**
     * Get taxonomy labels
     * 
     * @param string $taxonomy Taxonomy name
     * @return array Labels array
     */
    private function get_taxonomy_labels($taxonomy) {
        $singular = ucfirst(str_replace('askro_', '', $taxonomy));
        $plural = $singular . 's';
        
        return [
            'name' => __($plural, self::TEXT_DOMAIN),
            'singular_name' => __($singular, self::TEXT_DOMAIN),
            'search_items' => sprintf(__('Search %s', self::TEXT_DOMAIN), $plural),
            'all_items' => sprintf(__('All %s', self::TEXT_DOMAIN), $plural),
            'parent_item' => sprintf(__('Parent %s', self::TEXT_DOMAIN), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', self::TEXT_DOMAIN), $singular),
            'edit_item' => sprintf(__('Edit %s', self::TEXT_DOMAIN), $singular),
            'update_item' => sprintf(__('Update %s', self::TEXT_DOMAIN), $singular),
            'add_new_item' => sprintf(__('Add New %s', self::TEXT_DOMAIN), $singular),
            'new_item_name' => sprintf(__('New %s Name', self::TEXT_DOMAIN), $singular),
            'menu_name' => __($plural, self::TEXT_DOMAIN)
        ];
    }
    
    /**
     * Create nonce field with proper standards
     * 
     * @param string $action Nonce action
     * @param string $name Nonce name (optional)
     * @param bool $referer Whether to add referer field (optional)
     * @param bool $echo Whether to echo the field (optional)
     * @return string Nonce field HTML
     */
    public function create_nonce_field($action, $name = '_wpnonce', $referer = true, $echo = true) {
        $field = wp_nonce_field($action, $name, $referer, false);
        
        if ($echo) {
            echo $field;
        }
        
        return $field;
    }
    
    /**
     * Verify nonce with proper standards
     * 
     * @param string $action Nonce action
     * @param string $name Nonce name (optional)
     * @return bool Whether nonce is valid
     */
    public function verify_nonce($action, $name = '_wpnonce') {
        return wp_verify_nonce($_REQUEST[$name] ?? '', $action);
    }
    
    /**
     * Sanitize text field with proper standards
     * 
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public function sanitize_text($text) {
        return sanitize_text_field($text);
    }
    
    /**
     * Sanitize textarea with proper standards
     * 
     * @param string $text Text to sanitize
     * @return string Sanitized text
     */
    public function sanitize_textarea($text) {
        return sanitize_textarea_field($text);
    }
    
    /**
     * Sanitize email with proper standards
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public function sanitize_email($email) {
        return sanitize_email($email);
    }
    
    /**
     * Sanitize URL with proper standards
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public function sanitize_url($url) {
        return esc_url_raw($url);
    }
    
    /**
     * Escape HTML with proper standards
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escape_html($text) {
        return esc_html($text);
    }
    
    /**
     * Escape HTML attributes with proper standards
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escape_attr($text) {
        return esc_attr($text);
    }
    
    /**
     * Escape JavaScript with proper standards
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escape_js($text) {
        return esc_js($text);
    }
    
    /**
     * Get translated text with proper standards
     * 
     * @param string $text Text to translate
     * @return string Translated text
     */
    public function __($text) {
        return __($text, self::TEXT_DOMAIN);
    }
    
    /**
     * Get translated text with context
     * 
     * @param string $text Text to translate
     * @param string $context Context
     * @return string Translated text
     */
    public function _x($text, $context) {
        return _x($text, $context, self::TEXT_DOMAIN);
    }
    
    /**
     * Get translated text with number
     * 
     * @param string $single Single form
     * @param string $plural Plural form
     * @param int $number Number
     * @return string Translated text
     */
    public function _n($single, $plural, $number) {
        return _n($single, $plural, $number, self::TEXT_DOMAIN);
    }
    
    /**
     * Get plugin URL with proper standards
     * 
     * @param string $path Path to append
     * @return string Plugin URL
     */
    public function get_plugin_url($path = '') {
        return plugin_dir_url(ASKRO_PLUGIN_FILE) . $path;
    }
    
    /**
     * Get plugin path with proper standards
     * 
     * @param string $path Path to append
     * @return string Plugin path
     */
    public function get_plugin_path($path = '') {
        return plugin_dir_path(ASKRO_PLUGIN_FILE) . $path;
    }
    
    /**
     * Get plugin basename with proper standards
     * 
     * @return string Plugin basename
     */
    public function get_plugin_basename() {
        return plugin_basename(ASKRO_PLUGIN_FILE);
    }
    
    /**
     * Get plugin version with proper standards
     * 
     * @return string Plugin version
     */
    public function get_plugin_version() {
        return ASKRO_VERSION;
    }
    
    /**
     * Get plugin name with proper standards
     * 
     * @return string Plugin name
     */
    public function get_plugin_name() {
        return ASKRO_PLUGIN_NAME;
    }
    
    /**
     * Get text domain
     * 
     * @return string Text domain
     */
    public function get_text_domain() {
        return self::TEXT_DOMAIN;
    }
    
    /**
     * Get plugin prefix
     * 
     * @return string Plugin prefix
     */
    public function get_prefix() {
        return self::PREFIX;
    }
    
    /**
     * Get default priority
     * 
     * @return int Default priority
     */
    public function get_default_priority() {
        return self::DEFAULT_PRIORITY;
    }
}

// Initialize standards helper
$askro_standards_helper = new Askro_Standards_Helper(); 
