<?php
/**
 * Admin Users View
 * Comprehensive user management interface for the AskRow plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current stats for summary cards
$total_users = count_users()['total_users'];
$active_users = get_users(['meta_key' => 'askro_last_activity', 'meta_compare' => 'EXISTS']);
$active_users_count = count($active_users);
$experts = get_users(['meta_key' => 'askro_user_rank', 'meta_value' => 'expert']);
$experts_count = count($experts);

// Pagination settings
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Handle search and filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$rank_filter = isset($_GET['rank']) ? sanitize_text_field($_GET['rank']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'registered';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Build user query
$user_args = [
    'number' => $per_page,
    'offset' => $offset,
    'orderby' => $orderby,
    'order' => $order,
];

if (!empty($search)) {
    $user_args['search'] = '*' . $search . '*';
    $user_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
}

if (!empty($role_filter)) {
    $user_args['role'] = $role_filter;
}

// Get users
$users = get_users($user_args);

// Get total count for pagination
$count_args = $user_args;
unset($count_args['number'], $count_args['offset']);
$total_users_query = count_users();
$total_count = $total_users_query['total_users'];

// Calculate pagination
$total_pages = ceil($total_count / $per_page);

// Available user ranks
$available_ranks = [
    'novice' => __('Novice', 'askro'),
    'contributor' => __('Contributor', 'askro'),
    'expert' => __('Expert', 'askro'),
    'mentor' => __('Mentor', 'askro'),
    'moderator' => __('Moderator', 'askro')
];

// Available WordPress roles
$wp_roles = wp_roles();
$available_roles = $wp_roles->get_names();
?>

<div class="askro-admin-content" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-base-content"><?php _e('Users Management', 'askro'); ?></h1>
            <p class="text-base-content/70 mt-2"><?php _e('Manage and monitor your community members', 'askro'); ?></p>
        </div>
        
        <div class="flex gap-3">
            <button class="btn btn-outline btn-sm" onclick="exportUsers()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <?php _e('Export', 'askro'); ?>
            </button>
            <button class="btn btn-primary btn-sm" onclick="showInviteModal()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <?php _e('Invite User', 'askro'); ?>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stats-card bg-base-100 rounded-xl p-6 shadow-sm border border-base-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-base-content/60 text-sm font-medium"><?php _e('Total Users', 'askro'); ?></p>
                    <p class="text-2xl font-bold text-base-content mt-1"><?php echo number_format($total_users); ?></p>
                </div>
                <div class="bg-primary/10 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-base-100 rounded-xl p-6 shadow-sm border border-base-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-base-content/60 text-sm font-medium"><?php _e('Active Users', 'askro'); ?></p>
                    <p class="text-2xl font-bold text-base-content mt-1"><?php echo number_format($active_users_count); ?></p>
                </div>
                <div class="bg-success/10 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-base-100 rounded-xl p-6 shadow-sm border border-base-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-base-content/60 text-sm font-medium"><?php _e('Experts', 'askro'); ?></p>
                    <p class="text-2xl font-bold text-base-content mt-1"><?php echo number_format($experts_count); ?></p>
                </div>
                <div class="bg-warning/10 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-base-100 rounded-xl p-6 shadow-sm border border-base-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-base-content/60 text-sm font-medium"><?php _e('New This Month', 'askro'); ?></p>
                    <p class="text-2xl font-bold text-base-content mt-1">
                        <?php 
                        $new_users = get_users([
                            'date_query' => [
                                'after' => '1 month ago'
                            ],
                            'count_total' => true
                        ]);
                        echo number_format(count($new_users));
                        ?>
                    </p>
                </div>
                <div class="bg-info/10 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-base-100 rounded-xl p-6 shadow-sm border border-base-300 mb-6">
        <form method="GET" class="space-y-4">
            <input type="hidden" name="page" value="askro-users" />
            
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <label class="label">
                        <span class="label-text font-medium"><?php _e('Search Users', 'askro'); ?></span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo esc_attr($search); ?>"
                            placeholder="<?php _e('Search by name, email, or username...', 'askro'); ?>"
                            class="input input-bordered w-full pl-10"
                        />
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="w-full lg:w-48">
                    <label class="label">
                        <span class="label-text font-medium"><?php _e('Role', 'askro'); ?></span>
                    </label>
                    <select name="role" class="select select-bordered w-full">
                        <option value=""><?php _e('All Roles', 'askro'); ?></option>
                        <?php foreach ($available_roles as $role_key => $role_name): ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($role_filter, $role_key); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rank Filter -->
                <div class="w-full lg:w-48">
                    <label class="label">
                        <span class="label-text font-medium"><?php _e('Rank', 'askro'); ?></span>
                    </label>
                    <select name="rank" class="select select-bordered w-full">
                        <option value=""><?php _e('All Ranks', 'askro'); ?></option>
                        <?php foreach ($available_ranks as $rank_key => $rank_name): ?>
                            <option value="<?php echo esc_attr($rank_key); ?>" <?php selected($rank_filter, $rank_key); ?>>
                                <?php echo esc_html($rank_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full lg:w-48">
                    <label class="label">
                        <span class="label-text font-medium"><?php _e('Status', 'askro'); ?></span>
                    </label>
                    <select name="status" class="select select-bordered w-full">
                        <option value=""><?php _e('All Status', 'askro'); ?></option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'askro'); ?></option>
                        <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('Inactive', 'askro'); ?></option>
                        <option value="banned" <?php selected($status_filter, 'banned'); ?>><?php _e('Banned', 'askro'); ?></option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <?php _e('Filter', 'askro'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=askro-users'); ?>" class="btn btn-outline">
                    <?php _e('Clear Filters', 'askro'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-base-100 rounded-xl shadow-sm border border-base-300 mb-6">
        <div class="p-4 border-b border-base-300">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="select-all-users" class="checkbox checkbox-primary checkbox-sm" />
                        <span class="text-sm font-medium"><?php _e('Select All', 'askro'); ?></span>
                    </label>
                    <span id="selected-count" class="text-sm text-base-content/60">0 <?php _e('selected', 'askro'); ?></span>
                </div>
                
                <div class="flex gap-2" id="bulk-actions" style="display: none;">
                    <button class="btn btn-outline btn-sm" onclick="bulkAction('edit')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <?php _e('Edit', 'askro'); ?>
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="bulkAction('award_points')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        <?php _e('Award Points', 'askro'); ?>
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="bulkAction('suspend')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18 19l-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1z"></path>
                        </svg>
                        <?php _e('Suspend', 'askro'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="border-b border-base-300">
                        <th class="w-12">
                            <input type="checkbox" class="checkbox checkbox-primary checkbox-sm" />
                        </th>
                        <th class="text-left">
                            <a href="<?php echo add_query_arg(['orderby' => 'display_name', 'order' => ($orderby === 'display_name' && $order === 'ASC') ? 'DESC' : 'ASC']); ?>" class="flex items-center gap-2 hover:text-primary">
                                <?php _e('User', 'askro'); ?>
                                <?php if ($orderby === 'display_name'): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $order === 'ASC' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'; ?>"></path>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="text-left">
                            <a href="<?php echo add_query_arg(['orderby' => 'user_registered', 'order' => ($orderby === 'user_registered' && $order === 'ASC') ? 'DESC' : 'ASC']); ?>" class="flex items-center gap-2 hover:text-primary">
                                <?php _e('Joined', 'askro'); ?>
                                <?php if ($orderby === 'user_registered'): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $order === 'ASC' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'; ?>"></path>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="text-center"><?php _e('Activity', 'askro'); ?></th>
                        <th class="text-center"><?php _e('Points (XP)', 'askro'); ?></th>
                        <th class="text-center"><?php _e('Rank', 'askro'); ?></th>
                        <th class="text-center"><?php _e('Status', 'askro'); ?></th>
                        <th class="text-center"><?php _e('Actions', 'askro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <div class="flex flex-col items-center gap-4">
                                    <svg class="w-16 h-16 text-base-content/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <div class="text-center">
                                        <h3 class="text-lg font-semibold text-base-content"><?php _e('No users found', 'askro'); ?></h3>
                                        <p class="text-base-content/60"><?php _e('Try adjusting your filters or search terms.', 'askro'); ?></p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): 
                            // Get user meta data
                            $user_points = intval(get_user_meta($user->ID, 'askro_user_points', true));
                            $user_rank = get_user_meta($user->ID, 'askro_user_rank', true) ?: 'novice';
                            $question_count = askro_get_user_questions_count($user->ID);
                            $answer_count = askro_get_user_answers_count($user->ID);
                            $last_activity = get_user_meta($user->ID, 'askro_last_activity', true);
                            $badges_count = count(get_user_meta($user->ID, 'askro_user_badges', true) ?: []);
                            
                            // Determine status
                            $is_banned = get_user_meta($user->ID, 'askro_user_banned', true);
                            $is_active = $last_activity && (time() - $last_activity < (30 * DAY_IN_SECONDS));
                            
                            if ($is_banned) {
                                $status = 'banned';
                                $status_class = 'badge-error';
                                $status_text = __('Banned', 'askro');
                            } elseif ($is_active) {
                                $status = 'active';
                                $status_class = 'badge-success';
                                $status_text = __('Active', 'askro');
                            } else {
                                $status = 'inactive';
                                $status_class = 'badge-ghost';
                                $status_text = __('Inactive', 'askro');
                            }
                        ?>
                            <tr class="hover:bg-base-200/50">
                                <td>
                                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm user-checkbox" value="<?php echo $user->ID; ?>" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar">
                                            <div class="w-10 h-10 rounded-full">
                                                <?php echo get_avatar($user->ID, 40, '', '', ['class' => 'rounded-full']); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-base-content">
                                                <?php echo esc_html($user->display_name); ?>
                                            </div>
                                            <div class="text-sm text-base-content/60">
                                                <?php echo esc_html($user->user_email); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm">
                                        <div><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></div>
                                        <?php if ($last_activity): ?>
                                            <div class="text-base-content/60">
                                                <?php printf(__('Last seen: %s', 'askro'), human_time_diff($last_activity)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="text-sm space-y-1">
                                        <div>
                                            <span class="font-medium"><?php echo $question_count; ?></span>
                                            <span class="text-base-content/60"><?php _e('questions', 'askro'); ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php echo $answer_count; ?></span>
                                            <span class="text-base-content/60"><?php _e('answers', 'askro'); ?></span>
                                        </div>
                                        <?php if ($badges_count > 0): ?>
                                            <div>
                                                <span class="font-medium"><?php echo $badges_count; ?></span>
                                                <span class="text-base-content/60"><?php _e('badges', 'askro'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="font-bold text-primary text-lg">
                                        <?php echo number_format($user_points); ?>
                                    </div>
                                    <div class="text-xs text-base-content/60">XP</div>
                                </td>
                                <td class="text-center">
                                    <div class="badge badge-outline badge-sm">
                                        <?php echo esc_html($available_ranks[$user_rank] ?? ucfirst($user_rank)); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="badge <?php echo $status_class; ?> badge-sm">
                                        <?php echo $status_text; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown dropdown-end">
                                        <div tabindex="0" role="button" class="btn btn-ghost btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                            </svg>
                                        </div>
                                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-lg bg-base-100 rounded-box w-52 border border-base-300">
                                            <li>
                                                <a href="<?php echo get_edit_user_link($user->ID); ?>" class="text-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    <?php _e('Edit User', 'askro'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a onclick="viewUserProfile(<?php echo $user->ID; ?>)" class="text-sm cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <?php _e('View Profile', 'askro'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a onclick="awardPoints(<?php echo $user->ID; ?>)" class="text-sm cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                    </svg>
                                                    <?php _e('Award Points', 'askro'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a onclick="sendMessage(<?php echo $user->ID; ?>)" class="text-sm cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <?php _e('Send Message', 'askro'); ?>
                                                </a>
                                            </li>
                                            <div class="divider my-1"></div>
                                            <?php if (!$is_banned): ?>
                                                <li>
                                                    <a onclick="suspendUser(<?php echo $user->ID; ?>)" class="text-sm text-warning cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18 19l-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1-1-1-1 1z"></path>
                                                        </svg>
                                                        <?php _e('Suspend User', 'askro'); ?>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <a onclick="unbanUser(<?php echo $user->ID; ?>)" class="text-sm text-success cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <?php _e('Unban User', 'askro'); ?>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-base-content/60">
                <?php printf(
                    __('Showing %d to %d of %d users', 'askro'),
                    ($current_page - 1) * $per_page + 1,
                    min($current_page * $per_page, $total_count),
                    $total_count
                ); ?>
            </div>
            
            <div class="join">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo add_query_arg('paged', $current_page - 1); ?>" class="join-item btn btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>" 
                       class="join-item btn btn-sm <?php echo $i === $current_page ? 'btn-active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo add_query_arg('paged', $current_page + 1); ?>" class="join-item btn btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- User Profile Modal -->
<div id="user-profile-modal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg"><?php _e('User Profile', 'askro'); ?></h3>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeModal('user-profile-modal')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="user-profile-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Award Points Modal -->
<div id="award-points-modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4"><?php _e('Award Points', 'askro'); ?></h3>
        <form id="award-points-form" onsubmit="submitAwardPoints(event)">
            <input type="hidden" id="award-user-id" name="user_id" />
            <div class="space-y-4">
                <div>
                    <label class="label">
                        <span class="label-text"><?php _e('Points to Award', 'askro'); ?></span>
                    </label>
                    <input type="number" name="points" class="input input-bordered w-full" required min="1" />
                </div>
                <div>
                    <label class="label">
                        <span class="label-text"><?php _e('Reason (Optional)', 'askro'); ?></span>
                    </label>
                    <textarea name="reason" class="textarea textarea-bordered w-full" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" onclick="closeModal('award-points-modal')"><?php _e('Cancel', 'askro'); ?></button>
                <button type="submit" class="btn btn-primary"><?php _e('Award Points', 'askro'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Invite User Modal -->
<div id="invite-user-modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4"><?php _e('Invite New User', 'askro'); ?></h3>
        <form id="invite-user-form" onsubmit="submitInvite(event)">
            <div class="space-y-4">
                <div>
                    <label class="label">
                        <span class="label-text"><?php _e('Email Address', 'askro'); ?></span>
                    </label>
                    <input type="email" name="email" class="input input-bordered w-full" required />
                </div>
                <div>
                    <label class="label">
                        <span class="label-text"><?php _e('Role', 'askro'); ?></span>
                    </label>
                    <select name="role" class="select select-bordered w-full">
                        <?php foreach ($available_roles as $role_key => $role_name): ?>
                            <option value="<?php echo esc_attr($role_key); ?>">
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label">
                        <span class="label-text"><?php _e('Welcome Message (Optional)', 'askro'); ?></span>
                    </label>
                    <textarea name="message" class="textarea textarea-bordered w-full" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" onclick="closeModal('invite-user-modal')"><?php _e('Cancel', 'askro'); ?></button>
                <button type="submit" class="btn btn-primary"><?php _e('Send Invitation', 'askro'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize bulk selection
    initializeBulkSelection();
});

// Bulk selection functionality
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('select-all-users');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectedCount = document.getElementById('selected-count');
    const bulkActions = document.getElementById('bulk-actions');

    selectAllCheckbox?.addEventListener('change', function() {
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkSelection();
    });

    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkSelection);
    });

    function updateBulkSelection() {
        const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        selectedCount.textContent = count + ' <?php _e("selected", "askro"); ?>';
        
        if (count > 0) {
            bulkActions.style.display = 'flex';
        } else {
            bulkActions.style.display = 'none';
        }

        // Update select all checkbox state
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === userCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }
}

// User action functions
function viewUserProfile(userId) {
    showModal('user-profile-modal');
    document.getElementById('user-profile-content').innerHTML = '<div class="loading loading-spinner loading-lg mx-auto"></div>';
    
    // AJAX call to load user profile
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'askro_get_user_profile',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-profile-content').innerHTML = data.data.html;
        } else {
            showNotification('<?php _e("Error loading user profile", "askro"); ?>', 'error');
        }
    })
    .catch(error => {
        showNotification('<?php _e("Error loading user profile", "askro"); ?>', 'error');
    });
}

function awardPoints(userId) {
    document.getElementById('award-user-id').value = userId;
    showModal('award-points-modal');
}

function submitAwardPoints(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'askro_award_points');
    formData.append('nonce', '<?php echo wp_create_nonce("askro_admin_nonce"); ?>');

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('<?php _e("Points awarded successfully", "askro"); ?>', 'success');
            closeModal('award-points-modal');
            location.reload();
        } else {
            showNotification(data.data || '<?php _e("Error awarding points", "askro"); ?>', 'error');
        }
    });
}

function sendMessage(userId) {
    // Implement send message functionality
    showNotification('<?php _e("Message functionality coming soon", "askro"); ?>', 'info');
}

function suspendUser(userId) {
    if (confirm('<?php _e("Are you sure you want to suspend this user?", "askro"); ?>')) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'askro_suspend_user',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('<?php _e("User suspended successfully", "askro"); ?>', 'success');
                location.reload();
            } else {
                showNotification(data.data || '<?php _e("Error suspending user", "askro"); ?>', 'error');
            }
        });
    }
}

function unbanUser(userId) {
    if (confirm('<?php _e("Are you sure you want to unban this user?", "askro"); ?>')) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'askro_unban_user',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('<?php _e("User unbanned successfully", "askro"); ?>', 'success');
                location.reload();
            } else {
                showNotification(data.data || '<?php _e("Error unbanning user", "askro"); ?>', 'error');
            }
        });
    }
}

function bulkAction(action) {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showNotification('<?php _e("Please select at least one user", "askro"); ?>', 'warning');
        return;
    }

    let confirmMessage = '';
    switch(action) {
        case 'suspend':
            confirmMessage = '<?php _e("Are you sure you want to suspend the selected users?", "askro"); ?>';
            break;
        case 'edit':
            // Implement bulk edit modal
            showNotification('<?php _e("Bulk edit functionality coming soon", "askro"); ?>', 'info');
            return;
        case 'award_points':
            // Show bulk award points modal
            showBulkAwardPointsModal(userIds);
            return;
    }

    if (confirmMessage && confirm(confirmMessage)) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'askro_bulk_user_action',
                bulk_action: action,
                user_ids: userIds.join(','),
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.data.message || '<?php _e("Bulk action completed successfully", "askro"); ?>', 'success');
                location.reload();
            } else {
                showNotification(data.data || '<?php _e("Error performing bulk action", "askro"); ?>', 'error');
            }
        });
    }
}

function showInviteModal() {
    showModal('invite-user-modal');
}

function submitInvite(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'askro_invite_user');
    formData.append('nonce', '<?php echo wp_create_nonce("askro_admin_nonce"); ?>');

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('<?php _e("Invitation sent successfully", "askro"); ?>', 'success');
            closeModal('invite-user-modal');
            event.target.reset();
        } else {
            showNotification(data.data || '<?php _e("Error sending invitation", "askro"); ?>', 'error');
        }
    });
}

function exportUsers() {
    window.open(ajaxurl + '?action=askro_export_users&nonce=' + '<?php echo wp_create_nonce("askro_admin_nonce"); ?>', '_blank');
}

// Modal utilities
function showModal(modalId) {
    document.getElementById(modalId).classList.add('modal-open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('modal-open');
}

// Notification utility
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} shadow-lg mb-4 fixed top-4 right-4 z-50 max-w-sm`;
    notification.innerHTML = `
        <div>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>

<style>
.stats-card {
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal {
    display: none;
}

.modal.modal-open {
    display: flex;
}

.loading {
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
