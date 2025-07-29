<?php
/**
 * Askro Tools Page
 * 
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('ليس لديك صلاحية للوصول لهذه الصفحة', 'askro'));
}
?>

<div class="wrap">
    <h1><?php _e('أدوات Askro', 'askro'); ?></h1>
    
    <div class="askro-tools-container">
        
        <!-- Database Tools Section -->
        <div class="askro-tools-section">
            <h2><?php _e('أدوات قاعدة البيانات', 'askro'); ?></h2>
            
            <div class="askro-tools-grid">
                
                <!-- Fix Answer Links -->
                <div class="askro-tool-card">
                    <h3><?php _e('إصلاح روابط الإجابات', 'askro'); ?></h3>
                    <p><?php _e('ربط الإجابات غير المرتبطة بالأسئلة', 'askro'); ?></p>
                    <button type="button" class="button button-primary" onclick="fixAnswerLinks()">
                        <?php _e('إصلاح الروابط', 'askro'); ?>
                    </button>
                    <div id="fix-links-result" class="askro-result"></div>
                </div>
                
                <!-- Create Test Data -->
                <div class="askro-tool-card">
                    <h3><?php _e('إنشاء بيانات تجريبية', 'askro'); ?></h3>
                    <p><?php _e('إنشاء أسئلة وإجابات تجريبية للاختبار', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="createTestData()">
                        <?php _e('إنشاء بيانات تجريبية', 'askro'); ?>
                    </button>
                    <div id="test-data-result" class="askro-result"></div>
                </div>
                
                <!-- Database Statistics -->
                <div class="askro-tool-card">
                    <h3><?php _e('إحصائيات قاعدة البيانات', 'askro'); ?></h3>
                    <p><?php _e('عرض إحصائيات مفصلة عن البيانات', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="showDatabaseStats()">
                        <?php _e('عرض الإحصائيات', 'askro'); ?>
                    </button>
                    <div id="db-stats-result" class="askro-result"></div>
                </div>
                
                <!-- Clean Orphaned Data -->
                <div class="askro-tool-card">
                    <h3><?php _e('تنظيف البيانات اليتيمة', 'askro'); ?></h3>
                    <p><?php _e('حذف البيانات غير المرتبطة', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="cleanOrphanedData()">
                        <?php _e('تنظيف البيانات', 'askro'); ?>
                    </button>
                    <div id="clean-data-result" class="askro-result"></div>
                </div>
                
            </div>
        </div>
        
        <!-- Cache & Performance Tools -->
        <div class="askro-tools-section">
            <h2><?php _e('أدوات الأداء والتخزين المؤقت', 'askro'); ?></h2>
            
            <div class="askro-tools-grid">
                
                <!-- Clear Cache -->
                <div class="askro-tool-card">
                    <h3><?php _e('مسح التخزين المؤقت', 'askro'); ?></h3>
                    <p><?php _e('مسح جميع البيانات المخزنة مؤقتاً', 'askro'); ?></p>
                    <button type="button" class="button button-primary" onclick="clearCache()">
                        <?php _e('مسح التخزين المؤقت', 'askro'); ?>
                    </button>
                    <div id="cache-result" class="askro-result"></div>
                </div>
                
                <!-- Optimize Database -->
                <div class="askro-tool-card">
                    <h3><?php _e('تحسين قاعدة البيانات', 'askro'); ?></h3>
                    <p><?php _e('تحسين جداول قاعدة البيانات', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="optimizeDatabase()">
                        <?php _e('تحسين قاعدة البيانات', 'askro'); ?>
                    </button>
                    <div id="optimize-result" class="askro-result"></div>
                </div>
                
                <!-- Memory Usage -->
                <div class="askro-tool-card">
                    <h3><?php _e('استخدام الذاكرة', 'askro'); ?></h3>
                    <p><?php _e('عرض معلومات استخدام الذاكرة', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="showMemoryUsage()">
                        <?php _e('عرض استخدام الذاكرة', 'askro'); ?>
                    </button>
                    <div id="memory-result" class="askro-result"></div>
                </div>
                
            </div>
        </div>
        
        <!-- Debug Tools -->
        <div class="askro-tools-section">
            <h2><?php _e('أدوات التصحيح', 'askro'); ?></h2>
            
            <div class="askro-tools-grid">
                
                <!-- Enable Debug Mode -->
                <div class="askro-tool-card">
                    <h3><?php _e('وضع التصحيح', 'askro'); ?></h3>
                    <p><?php _e('تفعيل/إلغاء تفعيل وضع التصحيح', 'askro'); ?></p>
                    <label class="askro-switch">
                        <input type="checkbox" id="debug-mode" <?php checked(get_option('askro_debug_mode', false)); ?>>
                        <span class="askro-slider"></span>
                    </label>
                    <button type="button" class="button button-secondary" onclick="toggleDebugMode()">
                        <?php _e('تطبيق', 'askro'); ?>
                    </button>
                    <div id="debug-result" class="askro-result"></div>
                </div>
                
                <!-- Export Logs -->
                <div class="askro-tool-card">
                    <h3><?php _e('تصدير السجلات', 'askro'); ?></h3>
                    <p><?php _e('تصدير سجلات الأخطاء والأنشطة', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="exportLogs()">
                        <?php _e('تصدير السجلات', 'askro'); ?>
                    </button>
                    <div id="logs-result" class="askro-result"></div>
                </div>
                
                <!-- System Info -->
                <div class="askro-tool-card">
                    <h3><?php _e('معلومات النظام', 'askro'); ?></h3>
                    <p><?php _e('عرض معلومات النظام والبيئة', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="showSystemInfo()">
                        <?php _e('عرض معلومات النظام', 'askro'); ?>
                    </button>
                    <div id="system-result" class="askro-result"></div>
                </div>
                
                <!-- Migrate Settings -->
                <div class="askro-tool-card">
                    <h3><?php _e('ترحيل الإعدادات', 'askro'); ?></h3>
                    <p><?php _e('ترحيل الإعدادات من جدول wp_options إلى الجدول المخصص', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="migrateSettings()">
                        <?php _e('ترحيل الإعدادات', 'askro'); ?>
                    </button>
                    <div id="migrate-settings-result" class="askro-result"></div>
                </div>
                
            </div>
        </div>
        
        <!-- User Management Tools -->
        <div class="askro-tools-section">
            <h2><?php _e('أدوات إدارة المستخدمين', 'askro'); ?></h2>
            
            <div class="askro-tools-grid">
                
                <!-- Reset User Points -->
                <div class="askro-tool-card">
                    <h3><?php _e('إعادة تعيين نقاط المستخدم', 'askro'); ?></h3>
                    <p><?php _e('إعادة تعيين نقاط مستخدم محدد', 'askro'); ?></p>
                    <select id="reset-user-select">
                        <option value=""><?php _e('اختر المستخدم', 'askro'); ?></option>
                        <?php
                        $users = get_users(['orderby' => 'display_name']);
                        foreach ($users as $user) {
                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    <button type="button" class="button button-secondary" onclick="resetUserPoints()">
                        <?php _e('إعادة تعيين النقاط', 'askro'); ?>
                    </button>
                    <div id="reset-points-result" class="askro-result"></div>
                </div>
                
                <!-- Bulk Award Points -->
                <div class="askro-tool-card">
                    <h3><?php _e('منح نقاط جماعي', 'askro'); ?></h3>
                    <p><?php _e('منح نقاط لجميع المستخدمين النشطين', 'askro'); ?></p>
                    <input type="number" id="bulk-points" placeholder="<?php _e('عدد النقاط', 'askro'); ?>" min="1" max="1000">
                    <button type="button" class="button button-secondary" onclick="bulkAwardPoints()">
                        <?php _e('منح النقاط', 'askro'); ?>
                    </button>
                    <div id="bulk-points-result" class="askro-result"></div>
                </div>
                
                <!-- User Activity Report -->
                <div class="askro-tool-card">
                    <h3><?php _e('تقرير نشاط المستخدمين', 'askro'); ?></h3>
                    <p><?php _e('عرض تقرير نشاط المستخدمين', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="generateUserReport()">
                        <?php _e('إنشاء التقرير', 'askro'); ?>
                    </button>
                    <div id="user-report-result" class="askro-result"></div>
                </div>
                
            </div>
        </div>
        
        <!-- Content Management Tools -->
        <div class="askro-tools-section">
            <h2><?php _e('أدوات إدارة المحتوى', 'askro'); ?></h2>
            
            <div class="askro-tools-grid">
                
                <!-- Bulk Approve Content -->
                <div class="askro-tool-card">
                    <h3><?php _e('الموافقة الجماعية على المحتوى', 'askro'); ?></h3>
                    <p><?php _e('الموافقة على جميع المحتويات المعلقة', 'askro'); ?></p>
                    <button type="button" class="button button-primary" onclick="bulkApproveContent()">
                        <?php _e('الموافقة الجماعية', 'askro'); ?>
                    </button>
                    <div id="bulk-approve-result" class="askro-result"></div>
                </div>
                
                <!-- Content Quality Check -->
                <div class="askro-tool-card">
                    <h3><?php _e('فحص جودة المحتوى', 'askro'); ?></h3>
                    <p><?php _e('فحص جودة الأسئلة والإجابات', 'askro'); ?></p>
                    <button type="button" class="button button-secondary" onclick="checkContentQuality()">
                        <?php _e('فحص الجودة', 'askro'); ?>
                    </button>
                    <div id="quality-result" class="askro-result"></div>
                </div>
                
                <!-- Export Content -->
                <div class="askro-tool-card">
                    <h3><?php _e('تصدير المحتوى', 'askro'); ?></h3>
                    <p><?php _e('تصدير الأسئلة والإجابات', 'askro'); ?></p>
                    <select id="export-format">
                        <option value="json">JSON</option>
                        <option value="csv">CSV</option>
                        <option value="xml">XML</option>
                    </select>
                    <button type="button" class="button button-secondary" onclick="exportContent()">
                        <?php _e('تصدير المحتوى', 'askro'); ?>
                    </button>
                    <div id="export-result" class="askro-result"></div>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<style>
.askro-tools-container {
    margin-top: 20px;
}

.askro-tools-section {
    margin-bottom: 40px;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.askro-tools-section h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.askro-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.askro-tool-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #0073aa;
}

.askro-tool-card h3 {
    margin-top: 0;
    color: #23282d;
}

.askro-tool-card p {
    color: #666;
    margin-bottom: 15px;
}

.askro-tool-card button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.askro-tool-card select,
.askro-tool-card input {
    margin-bottom: 10px;
    width: 100%;
    max-width: 200px;
}

.askro-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.askro-result.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.askro-result.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.askro-result.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.askro-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    margin-bottom: 10px;
}

.askro-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.askro-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.askro-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .askro-slider {
    background-color: #0073aa;
}

input:checked + .askro-slider:before {
    transform: translateX(26px);
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Fix Answer Links
    window.fixAnswerLinks = function() {
        const $result = $('#fix-links-result');
        $result.removeClass().addClass('askro-result info').html('جاري إصلاح الروابط...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_fix_answer_links',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم إصلاح ' + response.data.linked_count + ' إجابة بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Create Test Data
    window.createTestData = function() {
        const $result = $('#test-data-result');
        $result.removeClass().addClass('askro-result info').html('جاري إنشاء البيانات التجريبية...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_create_test_data',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم إنشاء البيانات التجريبية بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Show Database Stats
    window.showDatabaseStats = function() {
        const $result = $('#db-stats-result');
        $result.removeClass().addClass('askro-result info').html('جاري جلب الإحصائيات...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_get_db_stats',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    let html = '<h4>إحصائيات قاعدة البيانات:</h4>';
                    html += '<ul>';
                    html += '<li>الأسئلة: ' + stats.questions + '</li>';
                    html += '<li>الإجابات: ' + stats.answers + '</li>';
                    html += '<li>التعليقات: ' + stats.comments + '</li>';
                    html += '<li>التصويتات: ' + stats.votes + '</li>';
                    html += '<li>المستخدمون النشطون: ' + stats.active_users + '</li>';
                    html += '</ul>';
                    
                    $result.removeClass().addClass('askro-result success').html(html).show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Clear Cache
    window.clearCache = function() {
        const $result = $('#cache-result');
        $result.removeClass().addClass('askro-result info').html('جاري مسح التخزين المؤقت...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_clear_cache',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم مسح التخزين المؤقت بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Toggle Debug Mode
    window.toggleDebugMode = function() {
        const debugMode = $('#debug-mode').is(':checked');
        const $result = $('#debug-result');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_toggle_debug',
                debug_mode: debugMode,
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم ' + (debugMode ? 'تفعيل' : 'إلغاء تفعيل') + ' وضع التصحيح بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Reset User Points
    window.resetUserPoints = function() {
        const userId = $('#reset-user-select').val();
        if (!userId) {
            alert('يرجى اختيار مستخدم');
            return;
        }
        
        if (!confirm('هل أنت متأكد من إعادة تعيين نقاط هذا المستخدم؟')) {
            return;
        }
        
        const $result = $('#reset-points-result');
        $result.removeClass().addClass('askro-result info').html('جاري إعادة تعيين النقاط...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_reset_user_points',
                user_id: userId,
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم إعادة تعيين نقاط المستخدم بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Bulk Award Points
    window.bulkAwardPoints = function() {
        const points = $('#bulk-points').val();
        if (!points || points < 1) {
            alert('يرجى إدخال عدد صحيح من النقاط');
            return;
        }
        
        if (!confirm('هل أنت متأكد من منح ' + points + ' نقاط لجميع المستخدمين النشطين؟')) {
            return;
        }
        
        const $result = $('#bulk-points-result');
        $result.removeClass().addClass('askro-result info').html('جاري منح النقاط...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_bulk_award_points',
                points: points,
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم منح النقاط لـ ' + response.data.users_count + ' مستخدم بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Bulk Approve Content
    window.bulkApproveContent = function() {
        if (!confirm('هل أنت متأكد من الموافقة على جميع المحتويات المعلقة؟')) {
            return;
        }
        
        const $result = $('#bulk-approve-result');
        $result.removeClass().addClass('askro-result info').html('جاري الموافقة على المحتوى...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_bulk_approve_content',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم الموافقة على ' + response.data.approved_count + ' محتوى بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Export Content
    window.exportContent = function() {
        const format = $('#export-format').val();
        const $result = $('#export-result');
        $result.removeClass().addClass('askro-result info').html('جاري تصدير المحتوى...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_export_content',
                format: format,
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(response.data.content);
                    link.download = 'askro-content.' + format;
                    link.click();
                    
                    $result.removeClass().addClass('askro-result success')
                        .html('تم تصدير المحتوى بنجاح! تم تحميل الملف تلقائياً.').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    // Additional tool functions (placeholder implementations)
    window.cleanOrphanedData = function() {
        $('#clean-data-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    window.optimizeDatabase = function() {
        $('#optimize-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    window.showMemoryUsage = function() {
        $('#memory-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    // Migrate Settings
    window.migrateSettings = function() {
        if (!confirm('هل أنت متأكد من ترحيل الإعدادات؟ هذا سينسخ الإعدادات من جدول wp_options إلى الجدول المخصص.')) {
            return;
        }
        
        const $result = $('#migrate-settings-result');
        $result.removeClass().addClass('askro-result info').html('جاري ترحيل الإعدادات...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'askro_migrate_settings',
                nonce: '<?php echo wp_create_nonce('askro_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass().addClass('askro-result success')
                        .html('تم ترحيل ' + response.data.migrated_count + ' إعداد بنجاح!').show();
                } else {
                    $result.removeClass().addClass('askro-result error')
                        .html('خطأ: ' + response.data.message).show();
                }
            },
            error: function() {
                $result.removeClass().addClass('askro-result error')
                    .html('حدث خطأ في الاتصال').show();
            }
        });
    };
    
    window.exportLogs = function() {
        $('#logs-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    window.showSystemInfo = function() {
        $('#system-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    window.generateUserReport = function() {
        $('#user-report-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
    window.checkContentQuality = function() {
        $('#quality-result').removeClass().addClass('askro-result info').html('هذه الميزة قيد التطوير...').show();
    };
    
});
</script> 
