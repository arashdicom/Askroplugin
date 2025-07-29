<?php
/**
 * Settings Page
 *
 * @package    Askro
 * @subpackage Admin/Views
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

// Handle form submission
if (isset($_POST['askro_save_settings']) && wp_verify_nonce($_POST['askro_settings_nonce'], 'askro_save_settings')) {
    // Save page assignments
    askro_update_option('archive_page_id', intval($_POST['archive_page_id']));
    askro_update_option('ask_question_page_id', intval($_POST['ask_question_page_id']));
    askro_update_option('user_profile_page_id', intval($_POST['user_profile_page_id']));
    
    // Save general settings
    askro_update_option('min_role_ask_question', sanitize_text_field($_POST['min_role_ask_question']));
    askro_update_option('min_role_submit_answer', sanitize_text_field($_POST['min_role_submit_answer']));
    askro_update_option('min_role_submit_comment', sanitize_text_field($_POST['min_role_submit_comment']));
    
    echo '<div class="notice notice-success"><p>تم حفظ الإعدادات بنجاح!</p></div>';
}

// Handle category management
if (isset($_POST['askro_add_category']) && wp_verify_nonce($_POST['askro_category_nonce'], 'askro_manage_categories')) {
    $category_name = sanitize_text_field($_POST['category_name']);
    $category_description = sanitize_textarea_field($_POST['category_description']);
    $parent_id = intval($_POST['parent_category']);
    
    if (!empty($category_name)) {
        $result = wp_insert_term($category_name, 'askro_question_category', [
            'description' => $category_description,
            'parent' => $parent_id > 0 ? $parent_id : 0
        ]);
        
        if (!is_wp_error($result)) {
            echo '<div class="notice notice-success"><p>تم إضافة التصنيف بنجاح!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>حدث خطأ أثناء إضافة التصنيف: ' . $result->get_error_message() . '</p></div>';
        }
    }
}

// Handle category deletion
if (isset($_POST['askro_delete_category']) && wp_verify_nonce($_POST['askro_category_nonce'], 'askro_manage_categories')) {
    $category_id = intval($_POST['category_id']);
    
    if ($category_id > 0) {
        $result = wp_delete_term($category_id, 'askro_question_category');
        
        if (!is_wp_error($result)) {
            echo '<div class="notice notice-success"><p>تم حذف التصنيف بنجاح!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>حدث خطأ أثناء حذف التصنيف: ' . $result->get_error_message() . '</p></div>';
        }
    }
}

// Get current settings
$archive_page_id = askro_get_option('archive_page_id', 0);
$ask_question_page_id = askro_get_option('ask_question_page_id', 0);
$user_profile_page_id = askro_get_option('user_profile_page_id', 0);
$min_role_ask_question = askro_get_option('min_role_ask_question', 'subscriber');
$min_role_submit_answer = askro_get_option('min_role_submit_answer', 'subscriber');
$min_role_submit_comment = askro_get_option('min_role_submit_comment', 'subscriber');

// Get all pages for dropdowns
$pages = get_pages(['sort_column' => 'post_title']);

// Get WordPress roles
$roles = wp_roles()->get_names();

// Get existing categories
$categories = get_terms([
    'taxonomy' => 'askro_question_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);
?>

<div class="wrap">
    <h1><?php _e('إعدادات Askro', 'askro'); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="#general-settings" class="nav-tab nav-tab-active"><?php _e('الإعدادات العامة', 'askro'); ?></a>
        <a href="#categories-management" class="nav-tab"><?php _e('إدارة التصنيفات', 'askro'); ?></a>
    </nav>
    
    <!-- General Settings Tab -->
    <div id="general-settings" class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('askro_save_settings', 'askro_settings_nonce'); ?>
            
            <div class="askro-settings-container">
                <!-- Page Assignments Section -->
                <div class="askro-settings-section">
                    <h2><?php _e('تعيين الصفحات', 'askro'); ?></h2>
                    <p class="description"><?php _e('قم بتعيين الصفحات التي سيتم استخدامها لعرض ميزات Askro المختلفة.', 'askro'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="archive_page_id"><?php _e('صفحة الأرشيف الرئيسية', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="archive_page_id" id="archive_page_id">
                                    <option value="0"><?php _e('-- اختر صفحة --', 'askro'); ?></option>
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?php echo $page->ID; ?>" <?php selected($archive_page_id, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('الصفحة التي ستعرض قائمة الأسئلة. ضع الشورت كود [askro_archive] في هذه الصفحة.', 'askro'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ask_question_page_id"><?php _e('صفحة طرح السؤال', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="ask_question_page_id" id="ask_question_page_id">
                                    <option value="0"><?php _e('-- اختر صفحة --', 'askro'); ?></option>
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?php echo $page->ID; ?>" <?php selected($ask_question_page_id, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('الصفحة التي ستعرض نموذج طرح السؤال. ضع الشورت كود [askro_ask_question_form] في هذه الصفحة.', 'askro'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="user_profile_page_id"><?php _e('صفحة الملف الشخصي', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="user_profile_page_id" id="user_profile_page_id">
                                    <option value="0"><?php _e('-- اختر صفحة --', 'askro'); ?></option>
                                    <?php foreach ($pages as $page): ?>
                                        <option value="<?php echo $page->ID; ?>" <?php selected($user_profile_page_id, $page->ID); ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('الصفحة التي ستعرض ملفات المستخدمين الشخصية. ضع الشورت كود [askro_user_profile] في هذه الصفحة.', 'askro'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Access Control Section -->
                <div class="askro-settings-section">
                    <h2><?php _e('التحكم في الوصول', 'askro'); ?></h2>
                    <p class="description"><?php _e('حدد الحد الأدنى من الصلاحيات المطلوبة لاستخدام ميزات Askro.', 'askro'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="min_role_ask_question"><?php _e('طرح السؤال', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="min_role_ask_question" id="min_role_ask_question">
                                    <?php foreach ($roles as $role_value => $role_name): ?>
                                        <option value="<?php echo esc_attr($role_value); ?>" <?php selected($min_role_ask_question, $role_value); ?>>
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('الحد الأدنى من الصلاحيات المطلوبة لطرح سؤال جديد.', 'askro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="min_role_submit_answer"><?php _e('تقديم إجابة', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="min_role_submit_answer" id="min_role_submit_answer">
                                    <?php foreach ($roles as $role_value => $role_name): ?>
                                        <option value="<?php echo esc_attr($role_value); ?>" <?php selected($min_role_submit_answer, $role_value); ?>>
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('الحد الأدنى من الصلاحيات المطلوبة لتقديم إجابة على سؤال.', 'askro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="min_role_submit_comment"><?php _e('إضافة تعليق', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="min_role_submit_comment" id="min_role_submit_comment">
                                    <?php foreach ($roles as $role_value => $role_name): ?>
                                        <option value="<?php echo esc_attr($role_value); ?>" <?php selected($min_role_submit_comment, $role_value); ?>>
                                            <?php echo esc_html($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('الحد الأدنى من الصلاحيات المطلوبة لإضافة تعليق على إجابة.', 'askro'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Instructions Section -->
                <div class="askro-settings-section">
                    <h2><?php _e('تعليمات الاستخدام', 'askro'); ?></h2>
                    <div class="askro-instructions">
                        <h3><?php _e('كيفية إعداد الصفحات:', 'askro'); ?></h3>
                        <ol>
                            <li><?php _e('قم بإنشاء صفحة جديدة للأرشيف الرئيسي', 'askro'); ?></li>
                            <li><?php _e('ضع الشورت كود [askro_archive] في محتوى الصفحة', 'askro'); ?></li>
                            <li><?php _e('اختر هذه الصفحة من القائمة أعلاه', 'askro'); ?></li>
                            <li><?php _e('كرر نفس الخطوات لصفحة طرح السؤال والملف الشخصي', 'askro'); ?></li>
                        </ol>
                        
                        <h3><?php _e('الشورت كودز المتاحة:', 'askro'); ?></h3>
                        <ul>
                                                    <li><code>[askro_archive]</code> - <?php _e('عرض قائمة الأسئلة', 'askro'); ?></li>
                        <li><code>[askro_ask_question_form]</code> - <?php _e('نموذج طرح السؤال', 'askro'); ?></li>
                        <li><code>[askro_user_profile]</code> - <?php _e('الملف الشخصي للمستخدم', 'askro'); ?></li>
                        <li><code>[askro_single_question]</code> - <?php _e('عرض سؤال واحد', 'askro'); ?></li>
                        <li><code>[askro_questions_list]</code> - <?php _e('قائمة بسيطة من الأسئلة', 'askro'); ?></li>
                        <li><code>[askro_leaderboard]</code> - <?php _e('قائمة المتصدرين', 'askro'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="askro_save_settings" class="button-primary" value="<?php _e('حفظ الإعدادات', 'askro'); ?>">
            </p>
        </form>
    </div>
    
    <!-- Categories Management Tab -->
    <div id="categories-management" class="tab-content" style="display: none;">
        <div class="askro-settings-container">
            <!-- Add Category Section -->
            <div class="askro-settings-section">
                <h2><?php _e('إضافة تصنيف جديد', 'askro'); ?></h2>
                <p class="description"><?php _e('أضف تصنيفات جديدة لتنظيم الأسئلة.', 'askro'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('askro_manage_categories', 'askro_category_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="category_name"><?php _e('اسم التصنيف', 'askro'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="category_name" id="category_name" class="regular-text" required>
                                <p class="description"><?php _e('اسم التصنيف (مطلوب)', 'askro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="category_description"><?php _e('وصف التصنيف', 'askro'); ?></label>
                            </th>
                            <td>
                                <textarea name="category_description" id="category_description" class="large-text" rows="3"></textarea>
                                <p class="description"><?php _e('وصف مختصر للتصنيف (اختياري)', 'askro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="parent_category"><?php _e('التصنيف الأصل', 'askro'); ?></label>
                            </th>
                            <td>
                                <select name="parent_category" id="parent_category">
                                    <option value="0"><?php _e('-- تصنيف رئيسي --', 'askro'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->term_id; ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('اختر تصنيفاً أصلاً إذا كنت تريد إنشاء تصنيف فرعي', 'askro'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="askro_add_category" class="button-primary" value="<?php _e('إضافة التصنيف', 'askro'); ?>">
                    </p>
                </form>
            </div>
            
            <!-- Manage Categories Section -->
            <div class="askro-settings-section">
                <h2><?php _e('إدارة التصنيفات الحالية', 'askro'); ?></h2>
                <p class="description"><?php _e('عرض وتحرير وحذف التصنيفات الموجودة.', 'askro'); ?></p>
                
                <?php if (!empty($categories)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('اسم التصنيف', 'askro'); ?></th>
                                <th><?php _e('الوصف', 'askro'); ?></th>
                                <th><?php _e('عدد الأسئلة', 'askro'); ?></th>
                                <th><?php _e('الإجراءات', 'askro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($category->name); ?></strong>
                                        <?php if ($category->parent > 0): ?>
                                            <br><small><?php _e('تصنيف فرعي', 'askro'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($category->description); ?></td>
                                    <td><?php echo $category->count; ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('edit-tags.php?action=edit&taxonomy=askro_question_category&tag_ID=' . $category->term_id); ?>" class="button button-small">
                                            <?php _e('تحرير', 'askro'); ?>
                                        </a>
                                        <form method="post" action="" style="display: inline;">
                                            <?php wp_nonce_field('askro_manage_categories', 'askro_category_nonce'); ?>
                                            <input type="hidden" name="category_id" value="<?php echo $category->term_id; ?>">
                                            <input type="submit" name="askro_delete_category" class="button button-small button-link-delete" 
                                                   value="<?php _e('حذف', 'askro'); ?>" 
                                                   onclick="return confirm('<?php _e('هل أنت متأكد من حذف هذا التصنيف؟', 'askro'); ?>')">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="notice notice-info">
                        <p><?php _e('لا توجد تصنيفات حالياً. قم بإضافة تصنيف جديد من الأعلى.', 'askro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.askro-settings-container {
    max-width: 800px;
}

.askro-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.askro-settings-section h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.askro-instructions {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px;
}

.askro-instructions h3 {
    margin-top: 0;
    color: #23282d;
}

.askro-instructions ol,
.askro-instructions ul {
    margin-left: 20px;
}

.askro-instructions code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

.tab-content {
    margin-top: 20px;
}

.nav-tab-wrapper {
    margin-bottom: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Hide all tab content
        $('.tab-content').hide();
        
        // Show selected tab content
        var target = $(this).attr('href');
        $(target).show();
    });
});
</script> 
