<?php
/**
 * Template Functions
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
 * Get template part for questions
 *
 * @param string $slug Template slug
 * @param string $name Template name
 * @since 1.0.0
 */
function askro_get_template_part($slug, $name = '') {
    $template = '';
    
    if ($name) {
        $template = locate_template([
            "askro/{$slug}-{$name}.php",
            "askro/{$slug}.php"
        ]);
    } else {
        $template = locate_template(["askro/{$slug}.php"]);
    }
    
    if (!$template) {
        $template = ASKRO_TEMPLATES_DIR . $slug . '.php';
        if ($name && file_exists(ASKRO_TEMPLATES_DIR . "{$slug}-{$name}.php")) {
            $template = ASKRO_TEMPLATES_DIR . "{$slug}-{$name}.php";
        }
    }
    
    if (file_exists($template)) {
        include $template;
    }
}

/**
 * Load template with data
 *
 * @param string $template_name Template name
 * @param array $args Template arguments
 * @return string
 * @since 1.0.0
 */
function askro_load_template($template_name, $args = []) {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $template_path = ASKRO_TEMPLATES_DIR . $template_name;
    
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    return '';
}

/**
 * Get question form template
 *
 * @param array $args Form arguments
 * @since 1.0.0
 */
function askro_question_form($args = []) {
    $defaults = [
        'show_title' => true,
        'show_content' => true,
        'show_tags' => true,
        'show_category' => true,
        'redirect_to' => '',
    ];
    
    $args = wp_parse_args($args, $defaults);
    askro_get_template_part('question-form', '', $args);
}

/**
 * Display questions list
 *
 * @param array $args Query arguments
 * @since 1.0.0
 */
function askro_questions_list($args = []) {
    $defaults = [
        'posts_per_page' => 10,
        'post_status' => 'publish',
        'meta_key' => '',
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    
    $args = wp_parse_args($args, $defaults);
    askro_get_template_part('questions-list', '', $args);
}
