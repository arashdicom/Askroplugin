<?php
/**
 * Taxonomies Class
 *
 * @package    Askro
 * @subpackage Classes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro Taxonomies Class
 *
 * @since 1.0.0
 */
class Askro_Taxonomies {
    
    /**
     * Initialize the class
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('init', [$this, 'register_taxonomies'], 0);
    }
    
    /**
     * Register custom taxonomies
     *
     * @since 1.0.0
     */
    public function register_taxonomies() {
        $this->register_question_categories();
        $this->register_question_tags();
    }
    
    /**
     * Register question categories taxonomy
     *
     * @since 1.0.0
     */
    public function register_question_categories() {
        $labels = [
            'name'                       => _x('Question Categories', 'Taxonomy General Name', 'askro'),
            'singular_name'              => _x('Question Category', 'Taxonomy Singular Name', 'askro'),
            'menu_name'                  => __('Categories', 'askro'),
            'all_items'                  => __('All Categories', 'askro'),
            'parent_item'                => __('Parent Category', 'askro'),
            'parent_item_colon'          => __('Parent Category:', 'askro'),
            'new_item_name'              => __('New Category Name', 'askro'),
            'add_new_item'               => __('Add New Category', 'askro'),
            'edit_item'                  => __('Edit Category', 'askro'),
            'update_item'                => __('Update Category', 'askro'),
            'view_item'                  => __('View Category', 'askro'),
            'separate_items_with_commas' => __('Separate categories with commas', 'askro'),
            'add_or_remove_items'        => __('Add or remove categories', 'askro'),
            'choose_from_most_used'      => __('Choose from the most used', 'askro'),
            'popular_items'              => __('Popular Categories', 'askro'),
            'search_items'               => __('Search Categories', 'askro'),
            'not_found'                  => __('Not Found', 'askro'),
            'no_terms'                   => __('No categories', 'askro'),
            'items_list'                 => __('Categories list', 'askro'),
            'items_list_navigation'      => __('Categories list navigation', 'askro'),
        ];
        
        $args = [
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rest_base'                  => 'question-categories',
            'rewrite'                    => [
                'slug'         => 'question-category',
                'hierarchical' => true,
                'with_front'   => false,
            ],
            'capabilities'               => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];
        
        register_taxonomy('askro_question_category', ['askro_question'], $args);
    }
    
    /**
     * Register question tags taxonomy
     *
     * @since 1.0.0
     */
    public function register_question_tags() {
        $labels = [
            'name'                       => _x('Question Tags', 'Taxonomy General Name', 'askro'),
            'singular_name'              => _x('Question Tag', 'Taxonomy Singular Name', 'askro'),
            'menu_name'                  => __('Tags', 'askro'),
            'all_items'                  => __('All Tags', 'askro'),
            'parent_item'                => __('Parent Tag', 'askro'),
            'parent_item_colon'          => __('Parent Tag:', 'askro'),
            'new_item_name'              => __('New Tag Name', 'askro'),
            'add_new_item'               => __('Add New Tag', 'askro'),
            'edit_item'                  => __('Edit Tag', 'askro'),
            'update_item'                => __('Update Tag', 'askro'),
            'view_item'                  => __('View Tag', 'askro'),
            'separate_items_with_commas' => __('Separate tags with commas', 'askro'),
            'add_or_remove_items'        => __('Add or remove tags', 'askro'),
            'choose_from_most_used'      => __('Choose from the most used', 'askro'),
            'popular_items'              => __('Popular Tags', 'askro'),
            'search_items'               => __('Search Tags', 'askro'),
            'not_found'                  => __('Not Found', 'askro'),
            'no_terms'                   => __('No tags', 'askro'),
            'items_list'                 => __('Tags list', 'askro'),
            'items_list_navigation'      => __('Tags list navigation', 'askro'),
        ];
        
        $args = [
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rest_base'                  => 'question-tags',
            'rewrite'                    => [
                'slug'       => 'question-tag',
                'with_front' => false,
            ],
            'capabilities'               => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];
        
        register_taxonomy('askro_question_tag', ['askro_question'], $args);
    }
    
    /**
     * Get question categories
     *
     * @param array $args Query arguments
     * @return array Categories
     * @since 1.0.0
     */
    public function get_question_categories($args = []) {
        $defaults = [
            'taxonomy' => 'askro_question_category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return get_terms($args);
    }
    
    /**
     * Get question tags
     *
     * @param array $args Query arguments
     * @return array Tags
     * @since 1.0.0
     */
    public function get_question_tags($args = []) {
        $defaults = [
            'taxonomy' => 'askro_question_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return get_terms($args);
    }
    
    /**
     * Get popular tags
     *
     * @param int $limit Number of tags to return
     * @return array Popular tags
     * @since 1.0.0
     */
    public function get_popular_tags($limit = 10) {
        return get_terms([
            'taxonomy' => 'askro_question_tag',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => $limit,
            'hide_empty' => true,
        ]);
    }
    
    /**
     * Get category hierarchy for display
     *
     * @param int $parent_id Parent category ID
     * @return array Category hierarchy
     * @since 1.0.0
     */
    public function get_category_hierarchy($parent_id = 0) {
        $categories = get_terms([
            'taxonomy' => 'askro_question_category',
            'parent' => $parent_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        $hierarchy = [];
        
        foreach ($categories as $category) {
            $category_data = [
                'term' => $category,
                'children' => $this->get_category_hierarchy($category->term_id),
            ];
            
            $hierarchy[] = $category_data;
        }
        
        return $hierarchy;
    }
}
