<?php
/**
 * Service Locator Functions
 *
 * @package    Askro
 * @subpackage Functions
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get service from container
 *
 * @param string $service_name Service name
 * @return mixed Service instance
 * @since 1.0.0
 */
function askro_get_service($service_name) {
    $container = Askro_Dependency_Container::get_instance();
    return $container->get($service_name);
}

/**
 * Check if service exists
 *
 * @param string $service_name Service name
 * @return bool
 * @since 1.0.0
 */
function askro_has_service($service_name) {
    $container = Askro_Dependency_Container::get_instance();
    return $container->has($service_name);
}

/**
 * Get display handler
 *
 * @return Askro_Display_Handler_Interface
 * @since 1.0.0
 */
function askro_display() {
    return askro_get_service('display');
}

/**
 * Get admin handler
 *
 * @return Askro_Admin_Handler_Interface
 * @since 1.0.0
 */
function askro_admin() {
    return askro_get_service('admin');
}

/**
 * Get voting handler
 *
 * @return Askro_Voting
 * @since 1.0.0
 */
function askro_voting() {
    return askro_get_service('voting');
}

/**
 * Get comments handler
 *
 * @return Askro_Comments
 * @since 1.0.0
 */
function askro_comments() {
    return askro_get_service('comments');
}

/**
 * Get gamification handler
 *
 * @return Askro_Gamification
 * @since 1.0.0
 */
function askro_gamification() {
    return askro_get_service('gamification');
}

/**
 * Get security handler
 *
 * @return Askro_Security
 * @since 1.0.0
 */
function askro_security() {
    return askro_get_service('security');
}

/**
 * Get API handler
 *
 * @return Askro_API
 * @since 1.0.0
 */
function askro_api() {
    return askro_get_service('api');
}

/**
 * Get API cache handler
 *
 * @return Askro_API_Cache
 * @since 1.0.0
 */
function askro_api_cache() {
    return askro_get_service('api_cache');
}

/**
 * Get API auth handler
 *
 * @return Askro_API_Auth
 * @since 1.0.0
 */
function askro_api_auth() {
    return askro_get_service('api_auth');
}

/**
 * Get AJAX voting handler
 *
 * @return Askro_Ajax_Voting
 * @since 1.0.0
 */
function askro_ajax_voting() {
    return askro_get_service('ajax_voting');
}

/**
 * Get AJAX comments handler
 *
 * @return Askro_Ajax_Comments
 * @since 1.0.0
 */
function askro_ajax_comments() {
    return askro_get_service('ajax_comments');
}

/**
 * Get AJAX search handler
 *
 * @return Askro_Ajax_Search
 * @since 1.0.0
 */
function askro_ajax_search() {
    return askro_get_service('ajax_search');
}

/**
 * Get database handler
 *
 * @return Askro_Database
 * @since 1.0.0
 */
function askro_database() {
    return askro_get_service('database');
}

/**
 * Get assets handler
 *
 * @return Askro_Assets
 * @since 1.0.0
 */
function askro_assets() {
    return askro_get_service('assets');
}

/**
 * Get shortcodes handler
 *
 * @return Askro_Shortcodes
 * @since 1.0.0
 */
function askro_shortcodes() {
    return askro_get_service('shortcodes');
}

/**
 * Get analytics handler
 *
 * @return Askro_Analytics
 * @since 1.0.0
 */
function askro_analytics() {
    return askro_get_service('analytics');
}

/**
 * Get leaderboard handler
 *
 * @return Askro_Leaderboard
 * @since 1.0.0
 */
function askro_leaderboard() {
    return askro_get_service('leaderboard');
}

/**
 * Get notifications handler
 *
 * @return Askro_Notifications
 * @since 1.0.0
 */
function askro_notifications() {
    return askro_get_service('notifications');
}

/**
 * Get post types handler
 *
 * @return Askro_Post_Types
 * @since 1.0.0
 */
function askro_post_types() {
    return askro_get_service('post_types');
}

/**
 * Get taxonomies handler
 *
 * @return Askro_Taxonomies
 * @since 1.0.0
 */
function askro_taxonomies() {
    return askro_get_service('taxonomies');
}

/**
 * Get URL handler
 *
 * @return Askro_URL_Handler
 * @since 1.0.0
 */
function askro_url_handler() {
    return askro_get_service('url_handler');
}

/**
 * Get forms handler
 *
 * @return Askro_Forms
 * @since 1.0.0
 */
function askro_forms() {
    return askro_get_service('forms');
}

/**
 * Create service with dependencies
 *
 * @param string $class_name Class name
 * @param array $dependencies Dependencies
 * @return object Service instance
 * @since 1.0.0
 */
function askro_create_service($class_name, $dependencies = []) {
    $container = Askro_Dependency_Container::get_instance();
    return $container->create($class_name, $dependencies);
}

/**
 * Get service with dependencies
 *
 * @param string $service_name Service name
 * @param array $dependencies Dependencies
 * @return mixed Service instance
 * @since 1.0.0
 */
function askro_get_service_with_dependencies($service_name, $dependencies = []) {
    $container = Askro_Dependency_Container::get_instance();
    return $container->get_with_dependencies($service_name, $dependencies);
}

/**
 * Register a new service
 *
 * @param string $name Service name
 * @param callable $factory Service factory function
 * @param bool $singleton Whether this is a singleton service
 * @since 1.0.0
 */
function askro_register_service($name, $factory, $singleton = true) {
    $container = Askro_Dependency_Container::get_instance();
    $container->register($name, $factory, $singleton);
}

/**
 * Remove a service
 *
 * @param string $name Service name
 * @since 1.0.0
 */
function askro_remove_service($name) {
    $container = Askro_Dependency_Container::get_instance();
    $container->remove($name);
}

/**
 * Get all registered services
 *
 * @return array Service names
 * @since 1.0.0
 */
function askro_get_services() {
    $container = Askro_Dependency_Container::get_instance();
    return $container->get_services();
} 
