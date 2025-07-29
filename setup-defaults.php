<?php
/**
 * Setup Default Options
 * 
 * Run this file once to set default options
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Set default options
$default_options = [
    'min_role_ask_question' => 'subscriber',
    'min_role_submit_answer' => 'subscriber',
    'min_role_submit_comment' => 'subscriber',
    'enable_pre_question_assistant' => true,
    'enable_image_upload' => true,
    'enable_code_editor' => true,
    'max_attachments' => 5,
    'max_file_size' => 5,
    'leaderboard_limit' => 10,
    'leaderboard_timeframe' => 'all_time',
    'show_avatars' => true,
    'show_ranks' => true,
    'search_results_per_page' => 10,
    'enable_advanced_search' => true,
    'search_highlight' => true
];

$updated = 0;
foreach ($default_options as $option => $default_value) {
    $option_name = 'askro_' . $option;
    if (!get_option($option_name)) {
        update_option($option_name, $default_value);
        $updated++;
        echo "✅ Set {$option_name} = " . (is_bool($default_value) ? ($default_value ? 'true' : 'false') : $default_value) . "\n";
    } else {
        echo "⏭️  {$option_name} already exists\n";
    }
}

echo "\n🎉 Setup complete! {$updated} options were set.\n";
echo "Now try the ask question form again.\n"; 
