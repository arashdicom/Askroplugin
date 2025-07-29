<?php
/**
 * User Profile Class
 *
 * @package    Askro
 * @subpackage Core/UserProfile
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
 * Askro User Profile Class
 *
 * Handles user profiles and related functionality
 *
 * @since 1.0.0
 */
class Askro_User_Profile {

    /**
     * Initialize the user profile component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_profile_pages']);
        
        add_action('wp_ajax_askro_follow_user', [$this, 'follow_user']);
        add_action('wp_ajax_askro_update_profile', [$this, 'update_profile']);
        add_action('wp_ajax_askro_upload_avatar', [$this, 'upload_avatar']);
        
        add_action('show_user_profile', [$this, 'add_profile_fields']);
        add_action('edit_user_profile', [$this, 'add_profile_fields']);
        add_action('personal_options_update', [$this, 'save_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_profile_fields']);
    }

    /**
     * Add rewrite rules for profile pages
     *
     * @since 1.0.0
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^askro-user/([^/]+)/?$',
            'index.php?askro_user_profile=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^askro-user/([^/]+)/([^/]+)/?$',
            'index.php?askro_user_profile=$matches[1]&askro_profile_tab=$matches[2]',
            'top'
        );
    }

    /**
     * Add query vars
     *
     * @param array $vars Query vars
     * @return array Modified query vars
     * @since 1.0.0
     */
    public function add_query_vars($vars) {
        $vars[] = 'askro_user_profile';
        $vars[] = 'askro_profile_tab';
        return $vars;
    }

    /**
     * Handle profile page requests
     *
     * @since 1.0.0
     */
    public function handle_profile_pages() {
        $username = get_query_var('askro_user_profile');
        
        if ($username) {
            $this->display_user_profile($username);
            exit;
        }
    }

    /**
     * Display user profile page
     *
     * @param string $username Username
     * @since 1.0.0
     */
    public function display_user_profile($username) {
        global $askro_user_helper;
        $user = $askro_user_helper->get_user_by_username($username);
        
        if (!$user) {
            wp_die(__('المستخدم غير موجود.', 'askro'), 404);
        }

        $current_tab = get_query_var('askro_profile_tab') ?: 'overview';
        $profile_data = $this->get_user_profile_data($user->ID);

        // Load header
        get_header();
        
        echo $this->render_profile_page($user, $profile_data, $current_tab);
        
        // Load footer
        get_footer();
    }

    /**
     * Render profile page
     *
     * @param WP_User $user User object
     * @param array $profile_data Profile data
     * @param string $current_tab Current tab
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_profile_page($user, $profile_data, $current_tab) {
        ob_start();
        ?>
        <div class="askro-profile-container">
            <!-- Profile Header -->
            <div class="askro-profile-header">
                <div class="askro-profile-cover" style="background-image: url('<?php echo esc_url($profile_data['cover_image']); ?>');">
                    <div class="askro-profile-cover-overlay"></div>
                </div>
                
                <div class="askro-profile-info">
                    <div class="askro-profile-avatar">
                        <img src="<?php echo esc_url($profile_data['avatar_url']); ?>" 
                             alt="<?php echo esc_attr($user->display_name); ?>"
                             class="askro-avatar-large">
                        
                        <?php if ($profile_data['is_online']): ?>
                        <div class="askro-online-indicator" title="<?php _e('متصل الآن', 'askro'); ?>"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="askro-profile-details">
                        <h1 class="askro-profile-name"><?php echo esc_html($user->display_name); ?></h1>
                        
                        <?php if ($profile_data['title']): ?>
                        <p class="askro-profile-title"><?php echo esc_html($profile_data['title']); ?></p>
                        <?php endif; ?>
                        
                        <div class="askro-profile-meta">
                            <span class="askro-profile-joined">
                                <?php printf(__('انضم في %s', 'askro'), date_i18n('F Y', strtotime($user->user_registered))); ?>
                            </span>
                            
                            <?php if ($profile_data['location']): ?>
                            <span class="askro-profile-location">
                                📍 <?php echo esc_html($profile_data['location']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <span class="askro-profile-last-seen">
                                <?php printf(__('آخر ظهور: %s', 'askro'), human_time_diff(strtotime($profile_data['last_activity']), current_time('timestamp')) . ' ' . __('مضت', 'askro')); ?>
                            </span>
                        </div>
                        
                        <?php if ($profile_data['bio']): ?>
                        <div class="askro-profile-bio">
                            <?php echo wpautop(esc_html($profile_data['bio'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="askro-profile-actions">
                        <?php if (is_user_logged_in() && get_current_user_id() !== $user->ID): ?>
                        <button type="button" 
                                class="askro-btn-primary askro-follow-btn <?php echo $profile_data['is_following'] ? 'following' : ''; ?>"
                                data-user-id="<?php echo $user->ID; ?>">
                            <span class="follow-text">
                                <?php echo $profile_data['is_following'] ? __('إلغاء المتابعة', 'askro') : __('متابعة', 'askro'); ?>
                            </span>
                        </button>
                        
                        <button type="button" class="askro-btn-outline askro-message-btn" data-user-id="<?php echo $user->ID; ?>">
                            💬 <?php _e('رسالة', 'askro'); ?>
                        </button>
                        <?php endif; ?>
                        
                        <?php if (is_user_logged_in() && get_current_user_id() === $user->ID): ?>
                        <button type="button" class="askro-btn-outline askro-edit-profile-btn">
                            ✏️ <?php _e('تعديل الملف الشخصي', 'askro'); ?>
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="askro-btn-outline askro-share-profile-btn" data-user-id="<?php echo $user->ID; ?>">
                            📤 <?php _e('مشاركة', 'askro'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profile Stats -->
            <div class="askro-profile-stats">
                <div class="askro-stat-item">
                    <span class="askro-stat-number"><?php echo number_format($profile_data['points']); ?></span>
                    <span class="askro-stat-label"><?php _e('نقطة', 'askro'); ?></span>
                </div>
                <div class="askro-stat-item">
                    <span class="askro-stat-number"><?php echo number_format($profile_data['questions_count']); ?></span>
                    <span class="askro-stat-label"><?php _e('سؤال', 'askro'); ?></span>
                </div>
                <div class="askro-stat-item">
                    <span class="askro-stat-number"><?php echo number_format($profile_data['answers_count']); ?></span>
                    <span class="askro-stat-label"><?php _e('إجابة', 'askro'); ?></span>
                </div>
                <div class="askro-stat-item">
                    <span class="askro-stat-number"><?php echo number_format($profile_data['followers_count']); ?></span>
                    <span class="askro-stat-label"><?php _e('متابع', 'askro'); ?></span>
                </div>
                <div class="askro-stat-item">
                    <span class="askro-stat-number"><?php echo number_format($profile_data['following_count']); ?></span>
                    <span class="askro-stat-label"><?php _e('يتابع', 'askro'); ?></span>
                </div>
            </div>

            <!-- Profile Navigation -->
            <div class="askro-profile-nav">
                <nav class="askro-tab-nav">
                    <?php
                    $tabs = [
                        'overview' => __('نظرة عامة', 'askro'),
                        'questions' => __('الأسئلة', 'askro'),
                        'answers' => __('الإجابات', 'askro'),
                        'badges' => __('الشارات', 'askro'),
                        'activity' => __('النشاط', 'askro'),
                        'followers' => __('المتابعون', 'askro'),
                        'following' => __('يتابع', 'askro')
                    ];
                    
                    foreach ($tabs as $tab_key => $tab_label):
                        $active_class = $current_tab === $tab_key ? 'active' : '';
                        $tab_url = home_url("/askro-user/{$user->user_login}/{$tab_key}/");
                    ?>
                    <a href="<?php echo esc_url($tab_url); ?>" 
                       class="askro-tab-link <?php echo $active_class; ?>"
                       data-tab="<?php echo $tab_key; ?>">
                        <?php echo $tab_label; ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Profile Content -->
            <div class="askro-profile-content">
                <div class="askro-profile-main">
                    <?php echo $this->render_profile_tab_content($user, $profile_data, $current_tab); ?>
                </div>
                
                <div class="askro-profile-sidebar">
                    <?php echo $this->render_profile_sidebar($user, $profile_data); ?>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <?php if (is_user_logged_in() && get_current_user_id() === $user->ID): ?>
        <div id="askro-edit-profile-modal" class="askro-modal" style="display: none;">
            <div class="askro-modal-content askro-modal-large">
                <div class="askro-modal-header">
                    <h3><?php _e('تعديل الملف الشخصي', 'askro'); ?></h3>
                    <button type="button" class="askro-modal-close">&times;</button>
                </div>
                <div class="askro-modal-body">
                    <?php echo $this->render_edit_profile_form($user, $profile_data); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Render profile tab content
     *
     * @param WP_User $user User object
     * @param array $profile_data Profile data
     * @param string $tab Current tab
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_profile_tab_content($user, $profile_data, $tab) {
        switch ($tab) {
            case 'overview':
                return $this->render_overview_tab($user, $profile_data);
            case 'questions':
                return $this->render_questions_tab($user);
            case 'answers':
                return $this->render_answers_tab($user);
            case 'badges':
                return $this->render_badges_tab($user, $profile_data);
            case 'activity':
                return $this->render_activity_tab($user);
            case 'followers':
                return $this->render_followers_tab($user);
            case 'following':
                return $this->render_following_tab($user);
            default:
                return $this->render_overview_tab($user, $profile_data);
        }
    }

    /**
     * Render overview tab
     *
     * @param WP_User $user User object
     * @param array $profile_data Profile data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_overview_tab($user, $profile_data) {
        ob_start();
        ?>
        <div class="askro-overview-tab">
            <!-- Recent Questions -->
            <div class="askro-overview-section">
                <h3 class="askro-section-title"><?php _e('الأسئلة الأخيرة', 'askro'); ?></h3>
                <?php
                $recent_questions = get_posts([
                    'post_type' => 'askro_question',
                    'author' => $user->ID,
                    'posts_per_page' => 5,
                    'post_status' => 'publish'
                ]);
                
                if ($recent_questions):
                ?>
                <div class="askro-recent-items">
                    <?php foreach ($recent_questions as $question): ?>
                    <div class="askro-recent-item">
                        <h4><a href="<?php echo get_permalink($question->ID); ?>"><?php echo esc_html($question->post_title); ?></a></h4>
                        <div class="askro-item-meta">
                            <span><?php echo human_time_diff(strtotime($question->post_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></span>
                            <span><?php echo get_post_meta($question->ID, '_askro_views', true) ?: 0; ?> <?php _e('مشاهدة', 'askro'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="askro-no-content"><?php _e('لم يطرح أي أسئلة بعد.', 'askro'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Recent Answers -->
            <div class="askro-overview-section">
                <h3 class="askro-section-title"><?php _e('الإجابات الأخيرة', 'askro'); ?></h3>
                <?php
                $recent_answers = get_posts([
                    'post_type' => 'askro_answer',
                    'author' => $user->ID,
                    'posts_per_page' => 5,
                    'post_status' => 'publish'
                ]);
                
                if ($recent_answers):
                ?>
                <div class="askro-recent-items">
                    <?php foreach ($recent_answers as $answer): 
                        $question_id = get_post_meta($answer->ID, '_askro_question_id', true);
                        $question = get_post($question_id);
                        if (!$question) continue;
                    ?>
                    <div class="askro-recent-item">
                        <h4><a href="<?php echo get_permalink($question_id) . '#answer-' . $answer->ID; ?>"><?php echo esc_html($question->post_title); ?></a></h4>
                        <div class="askro-item-excerpt"><?php echo wp_trim_words(strip_tags($answer->post_content), 20); ?></div>
                        <div class="askro-item-meta">
                            <span><?php echo human_time_diff(strtotime($answer->post_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="askro-no-content"><?php _e('لم يقدم أي إجابات بعد.', 'askro'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Top Badges -->
            <div class="askro-overview-section">
                <h3 class="askro-section-title"><?php _e('الشارات المميزة', 'askro'); ?></h3>
                <?php
                $top_badges = array_slice($profile_data['badges'], 0, 6);
                if ($top_badges):
                ?>
                <div class="askro-badges-grid">
                    <?php foreach ($top_badges as $badge): ?>
                    <div class="askro-badge-item">
                        <div class="askro-badge-icon"><?php echo $badge['icon']; ?></div>
                        <div class="askro-badge-info">
                            <h5><?php echo esc_html($badge['name']); ?></h5>
                            <p><?php echo esc_html($badge['description']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="askro-no-content"><?php _e('لم يحصل على أي شارات بعد.', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render profile sidebar
     *
     * @param WP_User $user User object
     * @param array $profile_data Profile data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_profile_sidebar($user, $profile_data) {
        ob_start();
        ?>
        <div class="askro-profile-sidebar-content">
            <!-- Contact Info -->
            <?php if ($profile_data['website'] || $profile_data['social_links']): ?>
            <div class="askro-sidebar-widget">
                <h4 class="askro-widget-title"><?php _e('معلومات الاتصال', 'askro'); ?></h4>
                <div class="askro-contact-info">
                    <?php if ($profile_data['website']): ?>
                    <a href="<?php echo esc_url($profile_data['website']); ?>" target="_blank" class="askro-contact-link">
                        🌐 <?php _e('الموقع الإلكتروني', 'askro'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php foreach ($profile_data['social_links'] as $platform => $url): ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="askro-contact-link">
                        <?php echo $this->get_social_icon($platform); ?> <?php echo ucfirst($platform); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Skills -->
            <?php if ($profile_data['skills']): ?>
            <div class="askro-sidebar-widget">
                <h4 class="askro-widget-title"><?php _e('المهارات', 'askro'); ?></h4>
                <div class="askro-skills-list">
                    <?php foreach ($profile_data['skills'] as $skill): ?>
                    <span class="askro-skill-tag"><?php echo esc_html($skill); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top Categories -->
            <div class="askro-sidebar-widget">
                <h4 class="askro-widget-title"><?php _e('التصنيفات المفضلة', 'askro'); ?></h4>
                <div class="askro-top-categories">
                    <?php
                    $top_categories = $this->get_user_top_categories($user->ID);
                    foreach ($top_categories as $category):
                    ?>
                    <div class="askro-category-item">
                        <a href="<?php echo get_term_link($category['term']); ?>" class="askro-category-link">
                            <?php echo esc_html($category['term']->name); ?>
                        </a>
                        <span class="askro-category-count"><?php echo $category['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Followers -->
            <div class="askro-sidebar-widget">
                <h4 class="askro-widget-title"><?php _e('المتابعون الجدد', 'askro'); ?></h4>
                <div class="askro-recent-followers">
                    <?php
                    $recent_followers = $this->get_recent_followers($user->ID, 6);
                    foreach ($recent_followers as $follower):
                    ?>
                    <a href="<?php echo home_url("/askro-user/{$follower->user_login}/"); ?>" class="askro-follower-link">
                        <?php echo get_avatar($follower->ID, 32); ?>
                        <span><?php echo esc_html($follower->display_name); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render edit profile form
     *
     * @param WP_User $user User object
     * @param array $profile_data Profile data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_edit_profile_form($user, $profile_data) {
        ob_start();
        ?>
        <form id="askro-edit-profile-form" class="askro-profile-form">
            <?php wp_nonce_field('askro_update_profile', 'askro_profile_nonce'); ?>
            
            <!-- Avatar Upload -->
            <div class="askro-form-group">
                <label class="askro-form-label"><?php _e('الصورة الشخصية', 'askro'); ?></label>
                <div class="askro-avatar-upload">
                    <div class="askro-current-avatar">
                        <img src="<?php echo esc_url($profile_data['avatar_url']); ?>" alt="Avatar" id="current-avatar">
                    </div>
                    <div class="askro-avatar-actions">
                        <button type="button" class="askro-btn-outline" id="upload-avatar-btn">
                            📷 <?php _e('تغيير الصورة', 'askro'); ?>
                        </button>
                        <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
                    </div>
                </div>
            </div>

            <!-- Display Name -->
            <div class="askro-form-group">
                <label for="display_name" class="askro-form-label"><?php _e('الاسم المعروض', 'askro'); ?></label>
                <input type="text" id="display_name" name="display_name" class="askro-input" 
                       value="<?php echo esc_attr($user->display_name); ?>" required>
            </div>

            <!-- Title -->
            <div class="askro-form-group">
                <label for="profile_title" class="askro-form-label"><?php _e('المسمى الوظيفي', 'askro'); ?></label>
                <input type="text" id="profile_title" name="profile_title" class="askro-input" 
                       value="<?php echo esc_attr($profile_data['title']); ?>"
                       placeholder="<?php _e('مطور ويب، مصمم، طالب...', 'askro'); ?>">
            </div>

            <!-- Bio -->
            <div class="askro-form-group">
                <label for="profile_bio" class="askro-form-label"><?php _e('نبذة شخصية', 'askro'); ?></label>
                <textarea id="profile_bio" name="profile_bio" class="askro-textarea" rows="4"
                          placeholder="<?php _e('اكتب نبذة مختصرة عن نفسك...', 'askro'); ?>"><?php echo esc_textarea($profile_data['bio']); ?></textarea>
            </div>

            <!-- Location -->
            <div class="askro-form-group">
                <label for="profile_location" class="askro-form-label"><?php _e('الموقع', 'askro'); ?></label>
                <input type="text" id="profile_location" name="profile_location" class="askro-input" 
                       value="<?php echo esc_attr($profile_data['location']); ?>"
                       placeholder="<?php _e('المدينة، البلد', 'askro'); ?>">
            </div>

            <!-- Website -->
            <div class="askro-form-group">
                <label for="profile_website" class="askro-form-label"><?php _e('الموقع الإلكتروني', 'askro'); ?></label>
                <input type="url" id="profile_website" name="profile_website" class="askro-input" 
                       value="<?php echo esc_attr($profile_data['website']); ?>"
                       placeholder="https://example.com">
            </div>

            <!-- Social Links -->
            <div class="askro-form-group">
                <label class="askro-form-label"><?php _e('الروابط الاجتماعية', 'askro'); ?></label>
                <div class="askro-social-inputs">
                    <?php
                    $social_platforms = ['twitter', 'facebook', 'linkedin', 'github', 'instagram'];
                    foreach ($social_platforms as $platform):
                        $value = $profile_data['social_links'][$platform] ?? '';
                    ?>
                    <div class="askro-social-input">
                        <span class="askro-social-icon"><?php echo $this->get_social_icon($platform); ?></span>
                        <input type="url" name="social_links[<?php echo $platform; ?>]" 
                               class="askro-input" value="<?php echo esc_attr($value); ?>"
                               placeholder="<?php printf(__('رابط %s', 'askro'), ucfirst($platform)); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Skills -->
            <div class="askro-form-group">
                <label for="profile_skills" class="askro-form-label"><?php _e('المهارات', 'askro'); ?></label>
                <input type="text" id="profile_skills" name="profile_skills" class="askro-input askro-tags-input" 
                       value="<?php echo esc_attr(implode(',', $profile_data['skills'])); ?>"
                       placeholder="<?php _e('أضف مهاراتك...', 'askro'); ?>">
                <p class="askro-field-help"><?php _e('اكتب مهارة واضغط Enter لإضافتها', 'askro'); ?></p>
            </div>

            <!-- Privacy Settings -->
            <div class="askro-form-group">
                <label class="askro-form-label"><?php _e('إعدادات الخصوصية', 'askro'); ?></label>
                <div class="askro-privacy-settings">
                    <label class="askro-checkbox-label">
                        <input type="checkbox" name="show_email" value="1" 
                               <?php checked($profile_data['show_email']); ?> class="askro-checkbox">
                        <span class="askro-checkbox-text"><?php _e('إظهار البريد الإلكتروني', 'askro'); ?></span>
                    </label>
                    
                    <label class="askro-checkbox-label">
                        <input type="checkbox" name="allow_messages" value="1" 
                               <?php checked($profile_data['allow_messages']); ?> class="askro-checkbox">
                        <span class="askro-checkbox-text"><?php _e('السماح بالرسائل الخاصة', 'askro'); ?></span>
                    </label>
                    
                    <label class="askro-checkbox-label">
                        <input type="checkbox" name="show_activity" value="1" 
                               <?php checked($profile_data['show_activity']); ?> class="askro-checkbox">
                        <span class="askro-checkbox-text"><?php _e('إظهار النشاط للعامة', 'askro'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="askro-form-actions">
                <button type="button" class="askro-btn-outline askro-modal-close">
                    <?php _e('إلغاء', 'askro'); ?>
                </button>
                <button type="submit" class="askro-btn-primary">
                    <span class="askro-btn-text"><?php _e('حفظ التغييرات', 'askro'); ?></span>
                    <span class="askro-btn-loading" style="display: none;">
                        <div class="askro-spinner"></div>
                        <?php _e('جاري الحفظ...', 'askro'); ?>
                    </span>
                </button>
            </div>
        </form>
        <?php

        return ob_get_clean();
    }

    /**
     * Get user profile data
     *
     * @param int $user_id User ID
     * @return array Profile data
     * @since 1.0.0
     */
    public function get_user_profile_data($user_id) {
        global $askro_user_helper;
        $user = $askro_user_helper->get_user($user_id);
        if (!$user) {
            return [];
        }

        // Get custom profile fields
        $title = get_user_meta($user_id, 'askro_profile_title', true);
        $bio = get_user_meta($user_id, 'askro_profile_bio', true);
        $location = get_user_meta($user_id, 'askro_profile_location', true);
        $website = get_user_meta($user_id, 'askro_profile_website', true);
        $social_links = get_user_meta($user_id, 'askro_social_links', true) ?: [];
        $skills = get_user_meta($user_id, 'askro_profile_skills', true) ?: [];
        $cover_image = get_user_meta($user_id, 'askro_cover_image', true) ?: '';
        
        // Privacy settings
        $show_email = get_user_meta($user_id, 'askro_show_email', true);
        $allow_messages = get_user_meta($user_id, 'askro_allow_messages', true);
        $show_activity = get_user_meta($user_id, 'askro_show_activity', true);

        // Get stats
        $points = askro_get_user_points($user_id);
        $questions_count = $this->get_user_questions_count($user_id);
        $answers_count = $this->get_user_answers_count($user_id);
        $followers_count = $this->get_followers_count($user_id);
        $following_count = $this->get_following_count($user_id);

        // Get badges
        $badges = $this->get_user_badges($user_id);

        // Check if current user is following this user
        $is_following = false;
        if (is_user_logged_in() && get_current_user_id() !== $user_id) {
            $is_following = $this->is_following_user(get_current_user_id(), $user_id);
        }

        // Check online status
        $last_activity = get_user_meta($user_id, 'askro_last_activity', true) ?: $user->user_registered;
        $is_online = (current_time('timestamp') - strtotime($last_activity)) < 300; // 5 minutes

        return [
            'title' => $title,
            'bio' => $bio,
            'location' => $location,
            'website' => $website,
            'social_links' => $social_links,
            'skills' => $skills,
            'cover_image' => $cover_image ?: askro_get_default_cover_image(),
            'avatar_url' => get_avatar_url($user_id, ['size' => 150]),
            'show_email' => $show_email,
            'allow_messages' => $allow_messages,
            'show_activity' => $show_activity,
            'points' => $points,
            'questions_count' => $questions_count,
            'answers_count' => $answers_count,
            'followers_count' => $followers_count,
            'following_count' => $following_count,
            'badges' => $badges,
            'is_following' => $is_following,
            'is_online' => $is_online,
            'last_activity' => $last_activity
        ];
    }

    /**
     * Get user questions count
     *
     * @param int $user_id User ID
     * @return int Questions count
     * @since 1.0.0
     */
    public function get_user_questions_count($user_id) {
        return count_user_posts($user_id, 'askro_question');
    }

    /**
     * Get user answers count
     *
     * @param int $user_id User ID
     * @return int Answers count
     * @since 1.0.0
     */
    public function get_user_answers_count($user_id) {
        return count_user_posts($user_id, 'askro_answer');
    }

    /**
     * Get followers count
     *
     * @param int $user_id User ID
     * @return int Followers count
     * @since 1.0.0
     */
    public function get_followers_count($user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_follows 
             WHERE followed_user_id = %d AND status = 'active'",
            $user_id
        ));
    }

    /**
     * Get following count
     *
     * @param int $user_id User ID
     * @return int Following count
     * @since 1.0.0
     */
    public function get_following_count($user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_follows 
             WHERE follower_user_id = %d AND status = 'active'",
            $user_id
        ));
    }

    /**
     * Check if user is following another user
     *
     * @param int $follower_id Follower user ID
     * @param int $followed_id Followed user ID
     * @return bool
     * @since 1.0.0
     */
    public function is_following_user($follower_id, $followed_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_follows 
             WHERE follower_user_id = %d AND followed_user_id = %d AND status = 'active'",
            $follower_id,
            $followed_id
        )) > 0;
    }

    /**
     * Get user badges
     *
     * @param int $user_id User ID
     * @return array Badges
     * @since 1.0.0
     */
    public function get_user_badges($user_id) {
        global $wpdb;

        $badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, ub.earned_date 
             FROM {$wpdb->prefix}askro_badges b
             INNER JOIN {$wpdb->prefix}askro_user_badges ub ON b.id = ub.badge_id
             WHERE ub.user_id = %d
             ORDER BY ub.earned_date DESC",
            $user_id
        ), ARRAY_A);

        return $badges ?: [];
    }

    /**
     * Get user top categories
     *
     * @param int $user_id User ID
     * @return array Top categories
     * @since 1.0.0
     */
    public function get_user_top_categories($user_id) {
        global $wpdb;

        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT tr.term_taxonomy_id, COUNT(*) as count
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_author = %d 
             AND p.post_type = 'askro_question' 
             AND p.post_status = 'publish'
             AND tt.taxonomy = 'askro_question_category'
             GROUP BY tr.term_taxonomy_id
             ORDER BY count DESC
             LIMIT 5",
            $user_id
        ));

        $result = [];
        foreach ($categories as $category) {
            $term = get_term($category->term_taxonomy_id);
            if ($term && !is_wp_error($term)) {
                $result[] = [
                    'term' => $term,
                    'count' => $category->count
                ];
            }
        }

        return $result;
    }

    /**
     * Get recent followers
     *
     * @param int $user_id User ID
     * @param int $limit Limit
     * @return array Recent followers
     * @since 1.0.0
     */
    public function get_recent_followers($user_id, $limit = 10) {
        global $wpdb;

        $follower_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT follower_user_id FROM {$wpdb->prefix}askro_user_follows 
             WHERE followed_user_id = %d AND status = 'active'
             ORDER BY date_created DESC
             LIMIT %d",
            $user_id,
            $limit
        ));

        $followers = [];
        foreach ($follower_ids as $follower_id) {
            global $askro_user_helper;
        $user = $askro_user_helper->get_user($follower_id);
            if ($user) {
                $followers[] = $user;
            }
        }

        return $followers;
    }

    /**
     * Get social icon
     *
     * @param string $platform Social platform
     * @return string Icon
     * @since 1.0.0
     */
    public function get_social_icon($platform) {
        $icons = [
            'twitter' => '🐦',
            'facebook' => '📘',
            'linkedin' => '💼',
            'github' => '🐙',
            'instagram' => '📷'
        ];

        return $icons[$platform] ?? '🔗';
    }

    /**
     * Follow user via AJAX
     *
     * @since 1.0.0
     */
    public function follow_user() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول للمتابعة.', 'askro')]);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        $follower_id = get_current_user_id();

        if (!$user_id || $user_id === $follower_id) {
            wp_send_json_error(['message' => __('بيانات غير صحيحة.', 'askro')]);
        }

        $is_following = $this->is_following_user($follower_id, $user_id);

        if ($is_following) {
            // Unfollow
            $this->unfollow_user($follower_id, $user_id);
            $message = __('تم إلغاء المتابعة.', 'askro');
            $action = 'follow';
        } else {
            // Follow
            $this->follow_user_action($follower_id, $user_id);
            $message = __('تم بدء المتابعة.', 'askro');
            $action = 'unfollow';
        }

        wp_send_json_success([
            'message' => $message,
            'action' => $action,
            'followers_count' => $this->get_followers_count($user_id)
        ]);
    }

    /**
     * Follow user action
     *
     * @param int $follower_id Follower user ID
     * @param int $followed_id Followed user ID
     * @return bool
     * @since 1.0.0
     */
    public function follow_user_action($follower_id, $followed_id) {
        global $wpdb;

        return $wpdb->insert(
            $wpdb->prefix . 'askro_user_follows',
            [
                'follower_user_id' => $follower_id,
                'followed_user_id' => $followed_id,
                'date_created' => current_time('mysql'),
                'status' => 'active'
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    /**
     * Unfollow user
     *
     * @param int $follower_id Follower user ID
     * @param int $followed_id Followed user ID
     * @return bool
     * @since 1.0.0
     */
    public function unfollow_user($follower_id, $followed_id) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'askro_user_follows',
            [
                'follower_user_id' => $follower_id,
                'followed_user_id' => $followed_id
            ],
            ['%d', '%d']
        );
    }

    /**
     * Update profile via AJAX
     *
     * @since 1.0.0
     */
    public function update_profile() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول.', 'askro')]);
        }

        if (!wp_verify_nonce($_POST['askro_profile_nonce'] ?? '', 'askro_update_profile')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $user_id = get_current_user_id();
        
        // Update user data
        $user_data = [
            'ID' => $user_id,
            'display_name' => sanitize_text_field($_POST['display_name'] ?? '')
        ];

        $result = wp_update_user($user_data);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Update profile meta
        $meta_fields = [
            'askro_profile_title' => sanitize_text_field($_POST['profile_title'] ?? ''),
            'askro_profile_bio' => sanitize_textarea_field($_POST['profile_bio'] ?? ''),
            'askro_profile_location' => sanitize_text_field($_POST['profile_location'] ?? ''),
            'askro_profile_website' => esc_url_raw($_POST['profile_website'] ?? ''),
            'askro_social_links' => array_map('esc_url_raw', $_POST['social_links'] ?? []),
            'askro_profile_skills' => array_map('sanitize_text_field', explode(',', $_POST['profile_skills'] ?? '')),
            'askro_show_email' => !empty($_POST['show_email']),
            'askro_allow_messages' => !empty($_POST['allow_messages']),
            'askro_show_activity' => !empty($_POST['show_activity'])
        ];

        foreach ($meta_fields as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        wp_send_json_success(['message' => __('تم تحديث الملف الشخصي بنجاح!', 'askro')]);
    }

    /**
     * Upload avatar via AJAX
     *
     * @since 1.0.0
     */
    public function upload_avatar() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول.', 'askro')]);
        }

        if (empty($_FILES['avatar'])) {
            wp_send_json_error(['message' => __('لم يتم اختيار ملف.', 'askro')]);
        }

        $file = $_FILES['avatar'];
        
        // Validate file
        if (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            wp_send_json_error(['message' => __('نوع الملف غير مدعوم.', 'askro')]);
        }

        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            wp_send_json_error(['message' => __('حجم الملف كبير جداً.', 'askro')]);
        }

        // Upload file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }

        // Update user avatar
        update_user_meta(get_current_user_id(), 'askro_custom_avatar', $upload['url']);

        wp_send_json_success([
            'message' => __('تم تحديث الصورة الشخصية!', 'askro'),
            'avatar_url' => $upload['url']
        ]);
    }

    /**
     * Add profile fields to user edit page
     *
     * @param WP_User $user User object
     * @since 1.0.0
     */
    public function add_profile_fields($user) {
        $profile_data = $this->get_user_profile_data($user->ID);
        ?>
        <h3><?php _e('معلومات Askro الشخصية', 'askro'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="askro_profile_title"><?php _e('المسمى الوظيفي', 'askro'); ?></label></th>
                <td><input type="text" name="askro_profile_title" id="askro_profile_title" 
                           value="<?php echo esc_attr($profile_data['title']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="askro_profile_bio"><?php _e('نبذة شخصية', 'askro'); ?></label></th>
                <td><textarea name="askro_profile_bio" id="askro_profile_bio" rows="5" cols="30"><?php echo esc_textarea($profile_data['bio']); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="askro_profile_location"><?php _e('الموقع', 'askro'); ?></label></th>
                <td><input type="text" name="askro_profile_location" id="askro_profile_location" 
                           value="<?php echo esc_attr($profile_data['location']); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save profile fields
     *
     * @param int $user_id User ID
     * @since 1.0.0
     */
    public function save_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $fields = ['askro_profile_title', 'askro_profile_bio', 'askro_profile_location'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

