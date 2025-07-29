<?php
/**
 * Questions Management Admin Page
 *
 * @package    Askro
 * @subpackage Admin/Views
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap askro-admin">
    <h1 class="wp-heading-inline"><?php _e('إدارة الأسئلة', 'askro'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=askro_question'); ?>" class="page-title-action">
        <?php _e('إضافة سؤال جديد', 'askro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="askro-stats-row">
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['questions']['total'] ?? 0); ?></h3>
                <p><?php _e('إجمالي الأسئلة', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['questions']['solved'] ?? 0); ?></h3>
                <p><?php _e('الأسئلة المحلولة', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['questions']['pending'] ?? 0); ?></h3>
                <p><?php _e('في انتظار المراجعة', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['questions']['today'] ?? 0); ?></h3>
                <p><?php _e('أسئلة اليوم', 'askro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="askro-filter-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#all" class="nav-tab nav-tab-active" data-filter="all">
                <?php _e('جميع الأسئلة', 'askro'); ?> 
                <span class="count">(<?php echo $stats['questions']['total'] ?? 0; ?>)</span>
            </a>
            <a href="#solved" class="nav-tab" data-filter="solved">
                <?php _e('محلولة', 'askro'); ?> 
                <span class="count">(<?php echo $stats['questions']['solved'] ?? 0; ?>)</span>
            </a>
            <a href="#unsolved" class="nav-tab" data-filter="unsolved">
                <?php _e('غير محلولة', 'askro'); ?> 
                <span class="count">(<?php echo $stats['questions']['unsolved'] ?? 0; ?>)</span>
            </a>
            <a href="#pending" class="nav-tab" data-filter="pending">
                <?php _e('في الانتظار', 'askro'); ?> 
                <span class="count">(<?php echo $stats['questions']['pending'] ?? 0; ?>)</span>
            </a>
            <a href="#featured" class="nav-tab" data-filter="featured">
                <?php _e('مميزة', 'askro'); ?> 
                <span class="count">(<?php echo $stats['questions']['featured'] ?? 0; ?>)</span>
            </a>
        </nav>
    </div>

    <!-- Search & Filters -->
    <div class="askro-table-controls">
        <div class="askro-search-box">
            <input type="search" id="question-search" placeholder="<?php _e('البحث في الأسئلة...', 'askro'); ?>">
            <button type="button" class="button">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>
        
        <div class="askro-filters">
            <select id="category-filter">
                <option value=""><?php _e('جميع الفئات', 'askro'); ?></option>
                <?php
                $categories = get_terms([
                    'taxonomy' => 'askro_question_category',
                    'hide_empty' => false
                ]);
                foreach ($categories as $category) {
                    echo '<option value="' . $category->slug . '">' . $category->name . '</option>';
                }
                ?>
            </select>
            
            <select id="sort-filter">
                <option value="date"><?php _e('الأحدث', 'askro'); ?></option>
                <option value="votes"><?php _e('الأكثر تصويتاً', 'askro'); ?></option>
                <option value="views"><?php _e('الأكثر مشاهدة', 'askro'); ?></option>
                <option value="answers"><?php _e('الأكثر إجابة', 'askro'); ?></option>
            </select>
        </div>
    </div>

    <!-- Questions Table -->
    <div class="askro-table-container">
        <table class="wp-list-table widefat fixed striped questions" id="questions-table">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th scope="col" class="manage-column column-title">
                        <?php _e('السؤال', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-author">
                        <?php _e('الكاتب', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-category">
                        <?php _e('الفئة', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-stats">
                        <?php _e('الإحصائيات', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('الحالة', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('التاريخ', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('الإجراءات', 'askro'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <tr data-question-id="<?php echo $question['id']; ?>" data-status="<?php echo $question['status']; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="question[]" value="<?php echo $question['id']; ?>">
                            </th>
                            
                            <td class="column-title has-row-actions">
                                <strong>
                                    <a href="<?php echo get_edit_post_link($question['id']); ?>">
                                        <?php echo esc_html($question['title']); ?>
                                    </a>
                                </strong>
                                
                                <?php if (isset($question['is_featured']) && $question['is_featured']): ?>
                                    <span class="askro-badge featured">
                                        <span class="dashicons dashicons-star-filled"></span>
                                        <?php _e('مميز', 'askro'); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (isset($question['is_closed']) && $question['is_closed']): ?>
                                    <span class="askro-badge closed">
                                        <span class="dashicons dashicons-lock"></span>
                                        <?php _e('مغلق', 'askro'); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($question['id']); ?>">
                                            <?php _e('تحرير', 'askro'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo get_permalink($question['id']); ?>" target="_blank">
                                            <?php _e('عرض', 'askro'); ?>
                                        </a> |
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="delete-question" data-id="<?php echo $question['id']; ?>">
                                            <?php _e('حذف', 'askro'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="column-author">
                                <div class="askro-author-info">
                                    <?php echo get_avatar($question['author']->ID, 32); ?>
                                    <div class="askro-author-details">
                                        <strong><?php echo $question['author']->display_name; ?></strong>
                                        <small><?php echo askro_get_user_points($question['author']->ID); ?> XP</small>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-category">
                                <?php 
                                $categories = wp_get_post_terms($question['id'], 'askro_question_category');
                                if (!empty($categories)) {
                                    foreach ($categories as $cat) {
                                        echo '<span class="askro-category-tag">' . $cat->name . '</span>';
                                    }
                                } else {
                                    echo '<span class="askro-no-category">—</span>';
                                }
                                ?>
                            </td>
                            
                            <td class="column-stats">
                                <div class="askro-stats-mini">
                                    <span class="askro-stat-item">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php echo number_format($question['views_count']); ?>
                                    </span>
                                    <span class="askro-stat-item">
                                        <span class="dashicons dashicons-format-chat"></span>
                                        <?php echo number_format($question['answers_count']); ?>
                                    </span>
                                    <span class="askro-stat-item">
                                        <span class="dashicons dashicons-thumbs-up"></span>
                                        <?php echo number_format($question['votes_count']); ?>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="column-status">
                                <?php
                                $status_class = 'pending';
                                $status_text = __('في الانتظار', 'askro');
                                
                                if ($question['status'] === 'publish') {
                                    $status_class = (isset($question['is_solved']) && $question['is_solved']) ? 'solved' : 'open';
                                    $status_text = (isset($question['is_solved']) && $question['is_solved']) ? __('محلول', 'askro') : __('مفتوح', 'askro');
                                } elseif ($question['status'] === 'draft') {
                                    $status_class = 'draft';
                                    $status_text = __('مسودة', 'askro');
                                }
                                ?>
                                <span class="askro-status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            
                            <td class="column-date">
                                <?php echo askro_time_ago($question['date']); ?>
                            </td>
                            
                            <td class="column-actions">
                                <div class="askro-action-buttons">
                                    <?php if ($question['status'] === 'pending'): ?>
                                        <button type="button" class="button button-small approve-question" 
                                                data-id="<?php echo $question['id']; ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('موافقة', 'askro'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="button button-small toggle-featured" 
                                            data-id="<?php echo $question['id']; ?>"
                                            data-featured="<?php echo (isset($question['is_featured']) && $question['is_featured']) ? '1' : '0'; ?>">
                                        <span class="dashicons dashicons-star-<?php echo (isset($question['is_featured']) && $question['is_featured']) ? 'filled' : 'empty'; ?>"></span>
                                        <?php echo (isset($question['is_featured']) && $question['is_featured']) ? __('إلغاء التمييز', 'askro') : __('تمييز', 'askro'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="askro-no-data">
                            <div class="askro-empty-state">
                                <span class="dashicons dashicons-format-chat"></span>
                                <h3><?php _e('لا توجد أسئلة', 'askro'); ?></h3>
                                <p><?php _e('لم يتم العثور على أي أسئلة بالمعايير المحددة.', 'askro'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bulk Actions -->
    <div class="askro-bulk-actions">
        <select id="bulk-action-selector">
            <option value=""><?php _e('العمليات المجمعة', 'askro'); ?></option>
            <option value="approve"><?php _e('موافقة', 'askro'); ?></option>
            <option value="feature"><?php _e('تمييز', 'askro'); ?></option>
            <option value="unfeature"><?php _e('إلغاء التمييز', 'askro'); ?></option>
            <option value="close"><?php _e('إغلاق', 'askro'); ?></option>
            <option value="delete"><?php _e('حذف', 'askro'); ?></option>
        </select>
        <button type="button" class="button" id="apply-bulk-action">
            <?php _e('تطبيق', 'askro'); ?>
        </button>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="askro-pagination">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(__('%s عنصر', 'askro'), number_format($pagination['total_items'])); ?>
                </span>
                
                <?php if ($pagination['current_page'] > 1): ?>
                    <a class="button" href="?page=askro-questions&paged=1">«</a>
                    <a class="button" href="?page=askro-questions&paged=<?php echo $pagination['current_page'] - 1; ?>">‹</a>
                <?php endif; ?>
                
                <span class="paging-input">
                    <label for="current-page-selector"><?php _e('الصفحة', 'askro'); ?></label>
                    <input class="current-page" id="current-page-selector" type="text" 
                           name="paged" value="<?php echo $pagination['current_page']; ?>" size="2">
                    <span class="tablenav-paging-text"> من 
                        <span class="total-pages"><?php echo $pagination['total_pages']; ?></span>
                    </span>
                </span>
                
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a class="button" href="?page=askro-questions&paged=<?php echo $pagination['current_page'] + 1; ?>">›</a>
                    <a class="button" href="?page=askro-questions&paged=<?php echo $pagination['total_pages']; ?>">»</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Loading Overlay -->
<div id="askro-loading-overlay" class="askro-loading-overlay" style="display: none;">
    <div class="askro-loading-spinner">
        <div class="spinner"></div>
        <p><?php _e('جاري المعالجة...', 'askro'); ?></p>
    </div>
</div>

<style>
.askro-admin {
    margin: 0;
    padding: 20px;
}

.askro-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0 30px;
}

.askro-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.askro-stat-icon {
    width: 50px;
    height: 50px;
    background: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.askro-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #23282d;
}

.askro-stat-content p {
    margin: 5px 0 0;
    color: #666;
    font-size: 14px;
}

.askro-filter-tabs {
    margin: 20px 0;
}

.askro-table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    gap: 20px;
}

.askro-search-box {
    display: flex;
    gap: 5px;
}

.askro-search-box input {
    width: 300px;
}

.askro-filters {
    display: flex;
    gap: 10px;
}

.askro-author-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.askro-author-details strong {
    display: block;
    color: #23282d;
}

.askro-author-details small {
    color: #666;
}

.askro-category-tag {
    display: inline-block;
    background: #f1f1f1;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-right: 5px;
}

.askro-stats-mini {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.askro-stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #666;
}

.askro-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.askro-status-badge.solved {
    background: #d4edda;
    color: #155724;
}

.askro-status-badge.open {
    background: #cce5ff;
    color: #004085;
}

.askro-status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.askro-status-badge.draft {
    background: #f8d7da;
    color: #721c24;
}

.askro-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    margin-left: 8px;
}

.askro-badge.featured {
    background: #ffc107;
    color: #212529;
}

.askro-badge.closed {
    background: #6c757d;
    color: white;
}

.askro-action-buttons {
    display: flex;
    gap: 5px;
}

.askro-bulk-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.askro-pagination {
    margin: 20px 0;
    text-align: center;
}

.askro-no-data {
    text-align: center;
    padding: 60px 20px;
}

.askro-empty-state {
    color: #666;
}

.askro-empty-state .dashicons {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.askro-empty-state h3 {
    margin: 10px 0;
    color: #555;
}

.askro-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
}

.askro-loading-spinner {
    background: white;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        const filter = $(this).data('filter');
        filterQuestions(filter);
    });
    
    // Search functionality
    $('#question-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterQuestionsBySearch(searchTerm);
    });
    
    // Bulk actions
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedQuestions = $('input[name="question[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!action || selectedQuestions.length === 0) {
            alert('<?php _e("يرجى اختيار عملية وتحديد أسئلة على الأقل.", "askro"); ?>');
            return;
        }
        
        if (confirm('<?php _e("هل أنت متأكد من تطبيق هذه العملية على العناصر المحددة؟", "askro"); ?>')) {
            applyBulkAction(action, selectedQuestions);
        }
    });
    
    // Individual actions
    $('.approve-question').on('click', function() {
        const questionId = $(this).data('id');
        approveQuestion(questionId);
    });
    
    $('.toggle-featured').on('click', function() {
        const questionId = $(this).data('id');
        const isFeatured = $(this).data('featured') === '1';
        toggleFeatured(questionId, !isFeatured);
    });
    
    $('.delete-question').on('click', function(e) {
        e.preventDefault();
        const questionId = $(this).data('id');
        
        if (confirm('<?php _e("هل أنت متأكد من حذف هذا السؤال؟ لا يمكن التراجع عن هذا الإجراء.", "askro"); ?>')) {
            deleteQuestion(questionId);
        }
    });
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="question[]"]').prop('checked', $(this).prop('checked'));
    });
    
    function filterQuestions(filter) {
        $('#questions-table tbody tr').each(function() {
            const row = $(this);
            const status = row.data('status');
            let show = true;
            
            switch(filter) {
                case 'solved':
                    show = row.find('.askro-status-badge.solved').length > 0;
                    break;
                case 'unsolved':
                    show = row.find('.askro-status-badge.open').length > 0;
                    break;
                case 'pending':
                    show = row.find('.askro-status-badge.pending').length > 0;
                    break;
                case 'featured':
                    show = row.find('.askro-badge.featured').length > 0;
                    break;
                default:
                    show = true;
            }
            
            row.toggle(show);
        });
    }
    
    function filterQuestionsBySearch(searchTerm) {
        $('#questions-table tbody tr').each(function() {
            const row = $(this);
            const title = row.find('.column-title strong a').text().toLowerCase();
            const author = row.find('.askro-author-details strong').text().toLowerCase();
            
            const matches = title.includes(searchTerm) || author.includes(searchTerm);
            row.toggle(matches);
        });
    }
    
    function showLoading() {
        $('#askro-loading-overlay').show();
    }
    
    function hideLoading() {
        $('#askro-loading-overlay').hide();
    }
    
    function applyBulkAction(action, questionIds) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_bulk_questions_action',
                bulk_action: action,
                question_ids: questionIds,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء تطبيق العملية.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function approveQuestion(questionId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_approve_question',
                question_id: questionId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء الموافقة.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function toggleFeatured(questionId, featured) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_toggle_featured_question',
                question_id: questionId,
                featured: featured ? 1 : 0,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء تحديث الحالة.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function deleteQuestion(questionId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_delete_question',
                question_id: questionId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $(`tr[data-question-id="${questionId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء الحذف.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
});
</script>
