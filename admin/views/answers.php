<?php
/**
 * Answers Management Admin Page
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
    <h1 class="wp-heading-inline"><?php _e('إدارة الإجابات', 'askro'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=askro_answer'); ?>" class="page-title-action">
        <?php _e('إضافة إجابة جديدة', 'askro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="askro-stats-row">
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['answers']['total'] ?? 0); ?></h3>
                <p><?php _e('إجمالي الإجابات', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['answers']['best'] ?? 0); ?></h3>
                <p><?php _e('الإجابات المُختارة', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['answers']['pending'] ?? 0); ?></h3>
                <p><?php _e('في انتظار المراجعة', 'askro'); ?></p>
            </div>
        </div>
        
        <div class="askro-stat-card">
            <div class="askro-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="askro-stat-content">
                <h3><?php echo number_format($stats['answers']['today'] ?? 0); ?></h3>
                <p><?php _e('إجابات اليوم', 'askro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="askro-filter-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#all" class="nav-tab nav-tab-active" data-filter="all">
                <?php _e('جميع الإجابات', 'askro'); ?> 
                <span class="count">(<?php echo $stats['answers']['total'] ?? 0; ?>)</span>
            </a>
            <a href="#best" class="nav-tab" data-filter="best">
                <?php _e('الإجابات المُختارة', 'askro'); ?> 
                <span class="count">(<?php echo $stats['answers']['best'] ?? 0; ?>)</span>
            </a>
            <a href="#pending" class="nav-tab" data-filter="pending">
                <?php _e('في الانتظار', 'askro'); ?> 
                <span class="count">(<?php echo $stats['answers']['pending'] ?? 0; ?>)</span>
            </a>
            <a href="#high_voted" class="nav-tab" data-filter="high_voted">
                <?php _e('عالية التصويت', 'askro'); ?> 
                <span class="count">(<?php echo $stats['answers']['high_voted'] ?? 0; ?>)</span>
            </a>
            <a href="#flagged" class="nav-tab" data-filter="flagged">
                <?php _e('مُبلغ عنها', 'askro'); ?> 
                <span class="count">(<?php echo $stats['answers']['flagged'] ?? 0; ?>)</span>
            </a>
        </nav>
    </div>

    <!-- Search & Filters -->
    <div class="askro-table-controls">
        <div class="askro-search-box">
            <input type="search" id="answer-search" placeholder="<?php _e('البحث في الإجابات...', 'askro'); ?>">
            <button type="button" class="button">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>
        
        <div class="askro-filters">
            <select id="question-filter">
                <option value=""><?php _e('جميع الأسئلة', 'askro'); ?></option>
                <?php
                $recent_questions = get_posts([
                    'post_type' => 'askro_question',
                    'posts_per_page' => 50,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                foreach ($recent_questions as $question) {
                    echo '<option value="' . $question->ID . '">' . esc_html($question->post_title) . '</option>';
                }
                ?>
            </select>
            
            <select id="sort-filter">
                <option value="date"><?php _e('الأحدث', 'askro'); ?></option>
                <option value="votes"><?php _e('الأكثر تصويتاً', 'askro'); ?></option>
                <option value="best_first"><?php _e('الإجابات المُختارة أولاً', 'askro'); ?></option>
                <option value="author"><?php _e('بحسب الكاتب', 'askro'); ?></option>
            </select>
        </div>
    </div>

    <!-- Answers Table -->
    <div class="askro-table-container">
        <table class="wp-list-table widefat fixed striped answers" id="answers-table">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th scope="col" class="manage-column column-content">
                        <?php _e('محتوى الإجابة', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-question">
                        <?php _e('السؤال', 'askro'); ?>
                    </th>
                    <th scope="col" class="manage-column column-author">
                        <?php _e('الكاتب', 'askro'); ?>
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
                <?php if (!empty($answers)): ?>
                    <?php foreach ($answers as $answer): ?>
                        <tr data-answer-id="<?php echo $answer['id']; ?>" data-status="<?php echo $answer['status']; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="answer[]" value="<?php echo $answer['id']; ?>">
                            </th>
                            
                            <td class="column-content has-row-actions">
                                <div class="askro-answer-preview">
                                    <div class="askro-answer-excerpt">
                                        <?php echo wp_trim_words(strip_tags($answer['content']), 15, '...'); ?>
                                    </div>
                                    
                                    <?php if ($answer['is_accepted']): ?>
                                        <span class="askro-badge best-answer">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <?php _e('إجابة مُختارة', 'askro'); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($answer['is_flagged']) && $answer['is_flagged']): ?>
                                        <span class="askro-badge flagged">
                                            <span class="dashicons dashicons-flag"></span>
                                            <?php _e('مُبلغ عنها', 'askro'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($answer['id']); ?>">
                                            <?php _e('تحرير', 'askro'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo get_permalink($answer['question_id']); ?>#answer-<?php echo $answer['id']; ?>" target="_blank">
                                            <?php _e('عرض', 'askro'); ?>
                                        </a> |
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="delete-answer" data-id="<?php echo $answer['id']; ?>">
                                            <?php _e('حذف', 'askro'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="column-question">
                                <div class="askro-question-info">
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($answer['question_id']); ?>">
                                            <?php echo esc_html($answer['question']); ?>
                                        </a>
                                    </strong>
                                    <div class="askro-question-meta">
                                        <small><?php printf(__('ID: %d', 'askro'), $answer['question_id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-author">
                                <div class="askro-author-info">
                                    <?php echo get_avatar($answer['author']->ID, 32); ?>
                                    <div class="askro-author-details">
                                        <strong><?php echo $answer['author']->display_name; ?></strong>
                                        <small><?php echo askro_get_user_points($answer['author']->ID); ?> XP</small>
                                        <div class="askro-author-rank">
                                            <?php
                                            $rank = askro_get_user_rank($answer['author']->ID);
                                            echo '<span class="askro-rank-badge">' . $rank['current']['name'] . '</span>';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-stats">
                                <div class="askro-stats-mini">
                                    <span class="askro-stat-item positive">
                                        <span class="dashicons dashicons-thumbs-up"></span>
                                        <?php echo number_format(isset($answer['upvotes']) ? $answer['upvotes'] : 0); ?>
                                    </span>
                                    <span class="askro-stat-item negative">
                                        <span class="dashicons dashicons-thumbs-down"></span>
                                        <?php echo number_format(isset($answer['downvotes']) ? $answer['downvotes'] : 0); ?>
                                    </span>
                                    <span class="askro-stat-item">
                                        <span class="dashicons dashicons-format-chat"></span>
                                        <?php echo number_format(isset($answer['comments_count']) ? $answer['comments_count'] : 0); ?>
                                    </span>
                                    <div class="askro-vote-score <?php echo (isset($answer['vote_score']) && $answer['vote_score'] > 0) ? 'positive' : ((isset($answer['vote_score']) && $answer['vote_score'] < 0) ? 'negative' : 'neutral'); ?>">
                                        <?php printf(__('نقاط: %+d', 'askro'), isset($answer['vote_score']) ? $answer['vote_score'] : 0); ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-status">
                                <?php
                                $status_class = 'pending';
                                $status_text = __('في الانتظار', 'askro');
                                
                                if ($answer['status'] === 'publish') {
                                    if ($answer['is_accepted']) {
                                        $status_class = 'best';
                                        $status_text = __('إجابة مُختارة', 'askro');
                                    } else {
                                        $status_class = 'published';
                                        $status_text = __('منشورة', 'askro');
                                    }
                                } elseif ($answer['status'] === 'draft') {
                                    $status_class = 'draft';
                                    $status_text = __('مسودة', 'askro');
                                }
                                
                                if (isset($answer['is_flagged']) && $answer['is_flagged']) {
                                    $status_class = 'flagged';
                                    $status_text = __('مُبلغ عنها', 'askro');
                                }
                                ?>
                                <span class="askro-status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            
                            <td class="column-date">
                                <?php echo askro_time_ago($answer['date']); ?>
                            </td>
                            
                            <td class="column-actions">
                                <div class="askro-action-buttons">
                                    <?php if ($answer['status'] === 'pending'): ?>
                                        <button type="button" class="button button-small approve-answer" 
                                                data-id="<?php echo $answer['id']; ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('موافقة', 'askro'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (!$answer['is_accepted'] && $answer['status'] === 'publish'): ?>
                                        <button type="button" class="button button-small mark-best" 
                                                data-id="<?php echo $answer['id']; ?>">
                                            <span class="dashicons dashicons-star-empty"></span>
                                            <?php _e('اختيار كأفضل', 'askro'); ?>
                                        </button>
                                    <?php elseif ($answer['is_accepted']): ?>
                                        <button type="button" class="button button-small unmark-best" 
                                                data-id="<?php echo $answer['id']; ?>">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <?php _e('إلغاء الاختيار', 'askro'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($answer['is_flagged']) && $answer['is_flagged']): ?>
                                        <button type="button" class="button button-small unflag-answer" 
                                                data-id="<?php echo $answer['id']; ?>">
                                            <span class="dashicons dashicons-dismiss"></span>
                                            <?php _e('إلغاء التبليغ', 'askro'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="askro-no-data">
                            <div class="askro-empty-state">
                                <span class="dashicons dashicons-format-chat"></span>
                                <h3><?php _e('لا توجد إجابات', 'askro'); ?></h3>
                                <p><?php _e('لم يتم العثور على أي إجابات بالمعايير المحددة.', 'askro'); ?></p>
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
            <option value="mark_best"><?php _e('اختيار كأفضل', 'askro'); ?></option>
            <option value="unmark_best"><?php _e('إلغاء الاختيار', 'askro'); ?></option>
            <option value="unflag"><?php _e('إلغاء التبليغ', 'askro'); ?></option>
            <option value="delete"><?php _e('حذف', 'askro'); ?></option>
        </select>
        <button type="button" class="button" id="apply-bulk-action">
            <?php _e('تطبيق', 'askro'); ?>
        </button>
    </div>

    <!-- Answer Quality Analysis -->
    <div class="askro-quality-panel">
        <h3><?php _e('تحليل جودة الإجابات', 'askro'); ?></h3>
        <div class="askro-quality-stats">
            <div class="askro-quality-item">
                <span class="askro-quality-label"><?php _e('متوسط طول الإجابة:', 'askro'); ?></span>
                <span class="askro-quality-value"><?php echo number_format($quality_stats['avg_length'] ?? 0); ?> <?php _e('كلمة', 'askro'); ?></span>
            </div>
            <div class="askro-quality-item">
                <span class="askro-quality-label"><?php _e('معدل الإجابات المقبولة:', 'askro'); ?></span>
                <span class="askro-quality-value"><?php echo number_format($quality_stats['acceptance_rate'] ?? 0, 1); ?>%</span>
            </div>
            <div class="askro-quality-item">
                <span class="askro-quality-label"><?php _e('متوسط التصويت:', 'askro'); ?></span>
                <span class="askro-quality-value"><?php echo number_format($quality_stats['avg_votes'] ?? 0, 1); ?></span>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="askro-pagination">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(__('%s عنصر', 'askro'), number_format($pagination['total_items'])); ?>
                </span>
                
                <?php if ($pagination['current_page'] > 1): ?>
                    <a class="button" href="?page=askro-answers&paged=1">«</a>
                    <a class="button" href="?page=askro-answers&paged=<?php echo $pagination['current_page'] - 1; ?>">‹</a>
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
                    <a class="button" href="?page=askro-answers&paged=<?php echo $pagination['current_page'] + 1; ?>">›</a>
                    <a class="button" href="?page=askro-answers&paged=<?php echo $pagination['total_pages']; ?>">»</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Answer Details Modal -->
<div id="answer-details-modal" class="askro-modal" style="display: none;">
    <div class="askro-modal-content">
        <div class="askro-modal-header">
            <h3><?php _e('تفاصيل الإجابة', 'askro'); ?></h3>
            <button type="button" class="askro-modal-close">&times;</button>
        </div>
        <div class="askro-modal-body">
            <div id="answer-details-content">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
        <div class="askro-modal-footer">
            <button type="button" class="button" id="close-modal"><?php _e('إغلاق', 'askro'); ?></button>
        </div>
    </div>
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

.askro-answer-preview {
    max-width: 300px;
}

.askro-answer-excerpt {
    margin-bottom: 8px;
    line-height: 1.4;
    color: #555;
}

.askro-question-info strong {
    display: block;
    margin-bottom: 5px;
}

.askro-question-meta {
    color: #666;
    font-size: 11px;
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
    display: block;
    color: #666;
    font-size: 11px;
}

.askro-rank-badge {
    display: inline-block;
    background: #f0f0f1;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 10px;
    color: #646970;
    margin-top: 2px;
}

.askro-stats-mini {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.askro-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #666;
}

.askro-stat-item.positive {
    color: #00a32a;
}

.askro-stat-item.negative {
    color: #d63638;
}

.askro-vote-score {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    margin-top: 4px;
}

.askro-vote-score.positive {
    background: #d1e7dd;
    color: #0f5132;
}

.askro-vote-score.negative {
    background: #f8d7da;
    color: #721c24;
}

.askro-vote-score.neutral {
    background: #e2e3e5;
    color: #41464b;
}

.askro-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.askro-status-badge.best {
    background: #ffd700;
    color: #333;
}

.askro-status-badge.published {
    background: #d4edda;
    color: #155724;
}

.askro-status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.askro-status-badge.draft {
    background: #f8d7da;
    color: #721c24;
}

.askro-status-badge.flagged {
    background: #f8d7da;
    color: #721c24;
}

.askro-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    margin-left: 8px;
}

.askro-badge.best-answer {
    background: #ffd700;
    color: #333;
}

.askro-badge.flagged {
    background: #ff6b6b;
    color: white;
}

.askro-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.askro-bulk-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.askro-quality-panel {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
}

.askro-quality-panel h3 {
    margin: 0 0 15px;
    color: #23282d;
}

.askro-quality-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.askro-quality-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 6px;
}

.askro-quality-label {
    font-size: 14px;
    color: #666;
}

.askro-quality-value {
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
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

/* Modal Styles */
.askro-modal {
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

.askro-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90%;
    display: flex;
    flex-direction: column;
}

.askro-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.askro-modal-header h3 {
    margin: 0;
}

.askro-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}

.askro-modal-body {
    padding: 20px;
    flex: 1;
    overflow-y: auto;
}

.askro-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
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
        filterAnswers(filter);
    });
    
    // Search functionality
    $('#answer-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterAnswersBySearch(searchTerm);
    });
    
    // Bulk actions
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedAnswers = $('input[name="answer[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!action || selectedAnswers.length === 0) {
            alert('<?php _e("يرجى اختيار عملية وتحديد إجابات على الأقل.", "askro"); ?>');
            return;
        }
        
        if (confirm('<?php _e("هل أنت متأكد من تطبيق هذه العملية على العناصر المحددة؟", "askro"); ?>')) {
            applyBulkAction(action, selectedAnswers);
        }
    });
    
    // Individual actions
    $('.approve-answer').on('click', function() {
        const answerId = $(this).data('id');
        approveAnswer(answerId);
    });
    
    $('.mark-best').on('click', function() {
        const answerId = $(this).data('id');
        markAsBest(answerId);
    });
    
    $('.unmark-best').on('click', function() {
        const answerId = $(this).data('id');
        unmarkAsBest(answerId);
    });
    
    $('.unflag-answer').on('click', function() {
        const answerId = $(this).data('id');
        unflagAnswer(answerId);
    });
    
    $('.delete-answer').on('click', function(e) {
        e.preventDefault();
        const answerId = $(this).data('id');
        
        if (confirm('<?php _e("هل أنت متأكد من حذف هذه الإجابة؟ لا يمكن التراجع عن هذا الإجراء.", "askro"); ?>')) {
            deleteAnswer(answerId);
        }
    });
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="answer[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Modal functionality
    $('.askro-modal-close, #close-modal').on('click', function() {
        $('#answer-details-modal').hide();
    });
    
    function filterAnswers(filter) {
        $('#answers-table tbody tr').each(function() {
            const row = $(this);
            let show = true;
            
            switch(filter) {
                case 'best':
                    show = row.find('.askro-badge.best-answer').length > 0;
                    break;
                case 'pending':
                    show = row.find('.askro-status-badge.pending').length > 0;
                    break;
                case 'high_voted':
                    const score = parseInt(row.find('.askro-vote-score').text().match(/-?\d+/));
                    show = score && score >= 5;
                    break;
                case 'flagged':
                    show = row.find('.askro-badge.flagged').length > 0;
                    break;
                default:
                    show = true;
            }
            
            row.toggle(show);
        });
    }
    
    function filterAnswersBySearch(searchTerm) {
        $('#answers-table tbody tr').each(function() {
            const row = $(this);
            const content = row.find('.askro-answer-excerpt').text().toLowerCase();
            const author = row.find('.askro-author-details strong').text().toLowerCase();
            const question = row.find('.askro-question-info strong').text().toLowerCase();
            
            const matches = content.includes(searchTerm) || 
                          author.includes(searchTerm) || 
                          question.includes(searchTerm);
            row.toggle(matches);
        });
    }
    
    function showLoading() {
        $('#askro-loading-overlay').show();
    }
    
    function hideLoading() {
        $('#askro-loading-overlay').hide();
    }
    
    function applyBulkAction(action, answerIds) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_bulk_answers_action',
                bulk_action: action,
                answer_ids: answerIds,
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
    
    function approveAnswer(answerId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_approve_answer',
                answer_id: answerId,
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
    
    function markAsBest(answerId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_mark_best_answer',
                answer_id: answerId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء اختيار الإجابة.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function unmarkAsBest(answerId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_unmark_best_answer',
                answer_id: answerId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء إلغاء اختيار الإجابة.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function unflagAnswer(answerId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_unflag_answer',
                answer_id: answerId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e("حدث خطأ أثناء إلغاء التبليغ.", "askro"); ?>');
                }
            },
            error: function() {
                hideLoading();
                alert('<?php _e("حدث خطأ في الاتصال.", "askro"); ?>');
            }
        });
    }
    
    function deleteAnswer(answerId) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_delete_answer',
                answer_id: answerId,
                nonce: '<?php echo wp_create_nonce("askro_admin_nonce"); ?>'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $(`tr[data-answer-id="${answerId}"]`).fadeOut(300, function() {
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
