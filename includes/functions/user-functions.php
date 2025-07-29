<?php
/**
 * User Functions
 *
 * @package    Askro
 * @subpackage Functions
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update user points
 *
 * @param int $user_id User ID
 * @param int $points Points to add (can be negative)
 * @return bool
 * @since 1.0.0
 */
function askro_update_user_points($user_id, $points) {
    if (!$user_id) {
        return false;
    }
    
    $current_points = askro_get_user_points($user_id);
    $new_points = max(0, $current_points + $points);
    
    return update_user_meta($user_id, 'askro_points', $new_points);
}

/**
 * Get user reputation
 *
 * @param int $user_id User ID
 * @return int
 * @since 1.0.0
 */
function askro_get_user_reputation($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    return (int) get_user_meta($user_id, 'askro_reputation', true);
}

/**
 * Update user reputation
 *
 * @param int $user_id User ID
 * @param int $reputation Reputation to add (can be negative)
 * @return bool
 * @since 1.0.0
 */
function askro_update_user_reputation($user_id, $reputation) {
    if (!$user_id) {
        return false;
    }
    
    $current_reputation = askro_get_user_reputation($user_id);
    $new_reputation = max(0, $current_reputation + $reputation);
    
    return update_user_meta($user_id, 'askro_reputation', $new_reputation);
}

/**
 * Get user badge count
 *
 * @param int $user_id User ID
 * @param string $badge_category Badge category (bronze, silver, gold)
 * @return int
 * @since 1.0.0
 */
function askro_get_user_badge_count($user_id = 0, $badge_category = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    $badges = get_user_meta($user_id, 'askro_badges', true);
    if (!is_array($badges)) {
        $badges = [];
    }
    
    if ($badge_category && isset($badges[$badge_category])) {
        return (int) $badges[$badge_category];
    }
    
    return array_sum($badges);
}

/**
 * Check if user can ask questions
 *
 * @param int $user_id User ID
 * @return bool
 * @since 1.0.0
 */
function askro_user_can_ask($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Check user capabilities
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check reputation requirements
    $min_reputation = get_option('askro_min_reputation_ask', 0);
    if ($min_reputation > 0 && askro_get_user_reputation($user_id) < $min_reputation) {
        return false;
    }
    
    return true;
}

/**
 * Check if user can answer questions
 *
 * @param int $user_id User ID
 * @return bool
 * @since 1.0.0
 */
function askro_user_can_answer($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Check user capabilities
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check reputation requirements
    $min_reputation = get_option('askro_min_reputation_answer', 0);
    if ($min_reputation > 0 && askro_get_user_reputation($user_id) < $min_reputation) {
        return false;
    }
    
    return true;
}
