<?php
/**
 * Notifications Class
 *
 * @package    Askro
 * @subpackage Core/Notifications
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
 * Askro Notifications Class
 *
 * Handles notifications system
 *
 * @since 1.0.0
 */
class Askro_Notifications {

    /**
     * Notification types
     *
     * @var array
     * @since 1.0.0
     */
    public $notification_types = [
        'question_answered' => [
            'title' => 'إجابة جديدة على سؤالك',
            'icon' => '💡',
            'color' => '#10B981'
        ],
        'answer_accepted' => [
            'title' => 'تم قبول إجابتك',
            'icon' => '✅',
            'color' => '#059669'
        ],
        'answer_voted' => [
            'title' => 'تم التصويت على إجابتك',
            'icon' => '👍',
            'color' => '#3B82F6'
        ],
        'question_voted' => [
            'title' => 'تم التصويت على سؤالك',
            'icon' => '👍',
            'color' => '#3B82F6'
        ],
        'comment_added' => [
            'title' => 'تعليق جديد',
            'icon' => '💬',
            'color' => '#F59E0B'
        ],
        'badge_earned' => [
            'title' => 'حصلت على شارة جديدة',
            'icon' => '🏆',
            'color' => '#8B5CF6'
        ],
        'achievement_unlocked' => [
            'title' => 'فتحت إنجاز جديد',
            'icon' => '🎉',
            'color' => '#EC4899'
        ],
        'user_followed' => [
            'title' => 'بدأ شخص في متابعتك',
            'icon' => '👥',
            'color' => '#6366F1'
        ],
        'question_mentioned' => [
            'title' => 'تم ذكرك في سؤال',
            'icon' => '📢',
            'color' => '#EF4444'
        ],
        'answer_mentioned' => [
            'title' => 'تم ذكرك في إجابة',
            'icon' => '📢',
            'color' => '#EF4444'
        ],
        'weekly_digest' => [
            'title' => 'ملخص أسبوعي',
            'icon' => '📊',
            'color' => '#14B8A6'
        ],
        'system_announcement' => [
            'title' => 'إعلان من النظام',
            'icon' => '📣',
            'color' => '#F97316'
        ]
    ];

    /**
     * Initialize the notifications component
     *
     * @since 1.0.0
     */
    public function init() {
        // AJAX handlers
        add_action('wp_ajax_askro_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_askro_mark_notification_read', [$this, 'mark_notification_read']);
        add_action('wp_ajax_askro_mark_all_notifications_read', [$this, 'mark_all_notifications_read']);
        add_action('wp_ajax_askro_delete_notification', [$this, 'delete_notification']);
        add_action('wp_ajax_askro_update_notification_settings', [$this, 'update_notification_settings']);

        // Hooks for creating notifications
        add_action('askro_question_answered', [$this, 'notify_question_answered'], 10, 2);
        add_action('askro_answer_accepted', [$this, 'notify_answer_accepted'], 10, 2);
        add_action('askro_vote_cast', [$this, 'notify_vote_cast'], 10, 3);
        add_action('askro_comment_added', [$this, 'notify_comment_added'], 10, 2);
        add_action('askro_badge_earned', [$this, 'notify_badge_earned'], 10, 2);
        add_action('askro_achievement_unlocked', [$this, 'notify_achievement_unlocked'], 10, 2);
        add_action('askro_user_followed', [$this, 'notify_user_followed'], 10, 2);

        // Email notifications
        add_action('askro_send_email_notification', [$this, 'send_email_notification'], 10, 3);

        // Weekly digest
        add_action('askro_weekly_digest', [$this, 'send_weekly_digest']);
        if (!wp_next_scheduled('askro_weekly_digest')) {
            wp_schedule_event(time(), 'weekly', 'askro_weekly_digest');
        }

        // Add notification bell to admin bar
        add_action('admin_bar_menu', [$this, 'add_notification_bell'], 999);

        // Enqueue notification scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_notification_scripts']);
    }

    /**
     * Render notifications dropdown
     *
     * @param array $args Arguments
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_notifications_dropdown($args = []) {
        if (!is_user_logged_in()) {
            return '';
        }

        $defaults = [
            'show_count' => true,
            'show_settings' => true,
            'limit' => 10
        ];

        $args = wp_parse_args($args, $defaults);
        $user_id = get_current_user_id();
        $unread_count = $this->get_unread_count($user_id);

        ob_start();
        ?>
        <div class="askro-notifications-dropdown" data-user-id="<?php echo $user_id; ?>">
            <!-- Notification Bell -->
            <button type="button" class="askro-notification-bell" id="notification-bell">
                <span class="askro-bell-icon">🔔</span>
                <?php if ($args['show_count'] && $unread_count > 0): ?>
                <span class="askro-notification-count" id="notification-count"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Menu -->
            <div class="askro-notifications-menu" id="notifications-menu" style="display: none;">
                <!-- Header -->
                <div class="askro-notifications-header">
                    <h3 class="askro-notifications-title"><?php _e('الإشعارات', 'askro'); ?></h3>
                    <div class="askro-notifications-actions">
                        <button type="button" class="askro-btn-text askro-mark-all-read" title="<?php _e('تحديد الكل كمقروء', 'askro'); ?>">
                            ✓
                        </button>
                        <?php if ($args['show_settings']): ?>
                        <button type="button" class="askro-btn-text askro-notification-settings" title="<?php _e('إعدادات الإشعارات', 'askro'); ?>">
                            ⚙️
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="askro-notifications-list" id="notifications-list">
                    <div class="askro-loading-placeholder">
                        <div class="askro-spinner"></div>
                        <?php _e('جاري تحميل الإشعارات...', 'askro'); ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="askro-notifications-footer">
                    <a href="<?php echo home_url('/askro-notifications/'); ?>" class="askro-btn-outline askro-btn-sm">
                        <?php _e('عرض جميع الإشعارات', 'askro'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Notification Settings Modal -->
        <?php if ($args['show_settings']): ?>
        <div class="askro-modal" id="notification-settings-modal" style="display: none;">
            <div class="askro-modal-content">
                <div class="askro-modal-header">
                    <h3><?php _e('إعدادات الإشعارات', 'askro'); ?></h3>
                    <button type="button" class="askro-modal-close">&times;</button>
                </div>
                <div class="askro-modal-body">
                    <form id="notification-settings-form">
                        <div class="askro-settings-section">
                            <h4><?php _e('إشعارات الموقع', 'askro'); ?></h4>
                            <?php foreach ($this->notification_types as $type => $config): ?>
                            <div class="askro-setting-item">
                                <label class="askro-checkbox-label">
                                    <input type="checkbox" name="web_notifications[<?php echo $type; ?>]" value="1" 
                                           class="askro-checkbox" checked>
                                    <span class="askro-checkbox-custom"></span>
                                    <span class="askro-setting-text">
                                        <?php echo $config['icon']; ?> <?php echo $config['title']; ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="askro-settings-section">
                            <h4><?php _e('إشعارات البريد الإلكتروني', 'askro'); ?></h4>
                            <?php foreach ($this->notification_types as $type => $config): ?>
                            <div class="askro-setting-item">
                                <label class="askro-checkbox-label">
                                    <input type="checkbox" name="email_notifications[<?php echo $type; ?>]" value="1" 
                                           class="askro-checkbox">
                                    <span class="askro-checkbox-custom"></span>
                                    <span class="askro-setting-text">
                                        <?php echo $config['icon']; ?> <?php echo $config['title']; ?>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="askro-settings-section">
                            <h4><?php _e('إعدادات إضافية', 'askro'); ?></h4>
                            <div class="askro-setting-item">
                                <label class="askro-checkbox-label">
                                    <input type="checkbox" name="digest_frequency" value="weekly" class="askro-checkbox">
                                    <span class="askro-checkbox-custom"></span>
                                    <span class="askro-setting-text">
                                        📊 <?php _e('ملخص أسبوعي بالبريد الإلكتروني', 'askro'); ?>
                                    </span>
                                </label>
                            </div>
                            <div class="askro-setting-item">
                                <label class="askro-checkbox-label">
                                    <input type="checkbox" name="browser_notifications" value="1" class="askro-checkbox">
                                    <span class="askro-checkbox-custom"></span>
                                    <span class="askro-setting-text">
                                        🔔 <?php _e('إشعارات المتصفح', 'askro'); ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="askro-modal-footer">
                    <button type="button" class="askro-btn-outline askro-modal-close">
                        <?php _e('إلغاء', 'askro'); ?>
                    </button>
                    <button type="button" class="askro-btn-primary askro-save-notification-settings">
                        <?php _e('حفظ الإعدادات', 'askro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDropdown = document.querySelector('.askro-notifications-dropdown');
            if (!notificationDropdown) return;

            const bell = document.getElementById('notification-bell');
            const menu = document.getElementById('notifications-menu');
            const list = document.getElementById('notifications-list');
            const countElement = document.getElementById('notification-count');

            let isOpen = false;
            let notifications = [];

            // Toggle dropdown
            bell?.addEventListener('click', function(e) {
                e.stopPropagation();
                if (isOpen) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationDropdown.contains(e.target)) {
                    closeDropdown();
                }
            });

            // Mark all as read
            document.querySelector('.askro-mark-all-read')?.addEventListener('click', function() {
                markAllAsRead();
            });

            // Notification settings
            document.querySelector('.askro-notification-settings')?.addEventListener('click', function() {
                openSettingsModal();
            });

            function openDropdown() {
                menu.style.display = 'block';
                isOpen = true;
                loadNotifications();
            }

            function closeDropdown() {
                menu.style.display = 'none';
                isOpen = false;
            }

            function loadNotifications() {
                const data = {
                    action: 'askro_get_notifications',
                    limit: <?php echo intval($args['limit']); ?>,
                    nonce: askroData.nonce
                };

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        notifications = result.data.notifications;
                        updateNotificationsList(notifications);
                        updateUnreadCount(result.data.unread_count);
                    } else {
                        console.error('Notifications error:', result.data);
                    }
                })
                .catch(error => {
                    console.error('Notifications fetch error:', error);
                });
            }

            function updateNotificationsList(notifications) {
                if (notifications.length === 0) {
                    list.innerHTML = `
                        <div class="askro-no-notifications">
                            <div class="askro-no-notifications-icon">🔔</div>
                            <div class="askro-no-notifications-text"><?php _e('لا توجد إشعارات', 'askro'); ?></div>
                        </div>
                    `;
                    return;
                }

                let html = '';
                notifications.forEach(notification => {
                    const readClass = notification.is_read ? 'read' : 'unread';
                    const timeAgo = formatTimeAgo(notification.date_created);
                    
                    html += `
                        <div class="askro-notification-item ${readClass}" data-id="${notification.id}">
                            <div class="askro-notification-icon" style="color: ${notification.type_config.color}">
                                ${notification.type_config.icon}
                            </div>
                            <div class="askro-notification-content">
                                <div class="askro-notification-title">${notification.title}</div>
                                <div class="askro-notification-message">${notification.message}</div>
                                <div class="askro-notification-time">${timeAgo}</div>
                            </div>
                            <div class="askro-notification-actions">
                                ${!notification.is_read ? `
                                    <button type="button" class="askro-btn-text askro-mark-read" 
                                            data-id="${notification.id}" title="<?php _e('تحديد كمقروء', 'askro'); ?>">
                                        ✓
                                    </button>
                                ` : ''}
                                <button type="button" class="askro-btn-text askro-delete-notification" 
                                        data-id="${notification.id}" title="<?php _e('حذف', 'askro'); ?>">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    `;
                });

                list.innerHTML = html;

                // Add event listeners
                list.querySelectorAll('.askro-mark-read').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        markAsRead(this.dataset.id);
                    });
                });

                list.querySelectorAll('.askro-delete-notification').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        deleteNotification(this.dataset.id);
                    });
                });

                // Click to mark as read and navigate
                list.querySelectorAll('.askro-notification-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const notificationId = this.dataset.id;
                        const notification = notifications.find(n => n.id == notificationId);
                        
                        if (!notification.is_read) {
                            markAsRead(notificationId);
                        }
                        
                        if (notification.action_url) {
                            window.location.href = notification.action_url;
                        }
                    });
                });
            }

            function updateUnreadCount(count) {
                if (countElement) {
                    if (count > 0) {
                        countElement.textContent = count;
                        countElement.style.display = 'block';
                    } else {
                        countElement.style.display = 'none';
                    }
                }
            }

            function markAsRead(notificationId) {
                const data = {
                    action: 'askro_mark_notification_read',
                    notification_id: notificationId,
                    nonce: askroData.nonce
                };

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Update UI
                        const item = list.querySelector(`[data-id="${notificationId}"]`);
                        if (item) {
                            item.classList.remove('unread');
                            item.classList.add('read');
                            const markBtn = item.querySelector('.askro-mark-read');
                            if (markBtn) {
                                markBtn.remove();
                            }
                        }
                        
                        // Update count
                        const currentCount = parseInt(countElement?.textContent || 0);
                        updateUnreadCount(Math.max(0, currentCount - 1));
                    }
                })
                .catch(error => {
                    console.error('Mark read error:', error);
                });
            }

            function markAllAsRead() {
                const data = {
                    action: 'askro_mark_all_notifications_read',
                    nonce: askroData.nonce
                };

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Update UI
                        list.querySelectorAll('.askro-notification-item').forEach(item => {
                            item.classList.remove('unread');
                            item.classList.add('read');
                            const markBtn = item.querySelector('.askro-mark-read');
                            if (markBtn) {
                                markBtn.remove();
                            }
                        });
                        
                        updateUnreadCount(0);
                    }
                })
                .catch(error => {
                    console.error('Mark all read error:', error);
                });
            }

            function deleteNotification(notificationId) {
                const data = {
                    action: 'askro_delete_notification',
                    notification_id: notificationId,
                    nonce: askroData.nonce
                };

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const item = list.querySelector(`[data-id="${notificationId}"]`);
                        if (item) {
                            item.remove();
                        }
                        
                        // Update notifications array
                        notifications = notifications.filter(n => n.id != notificationId);
                        
                        if (notifications.length === 0) {
                            updateNotificationsList([]);
                        }
                    }
                })
                .catch(error => {
                    console.error('Delete notification error:', error);
                });
            }

            function formatTimeAgo(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffInSeconds = Math.floor((now - date) / 1000);

                if (diffInSeconds < 60) {
                    return '<?php _e('الآن', 'askro'); ?>';
                } else if (diffInSeconds < 3600) {
                    const minutes = Math.floor(diffInSeconds / 60);
                    return minutes + ' <?php _e('دقيقة', 'askro'); ?>';
                } else if (diffInSeconds < 86400) {
                    const hours = Math.floor(diffInSeconds / 3600);
                    return hours + ' <?php _e('ساعة', 'askro'); ?>';
                } else {
                    const days = Math.floor(diffInSeconds / 86400);
                    return days + ' <?php _e('يوم', 'askro'); ?>';
                }
            }

            function openSettingsModal() {
                const modal = document.getElementById('notification-settings-modal');
                if (modal) {
                    modal.style.display = 'block';
                    loadNotificationSettings();
                }
            }

            function loadNotificationSettings() {
                // Load current settings and populate form
                // This would fetch user's current notification preferences
            }

            // Modal close handlers
            document.querySelectorAll('.askro-modal-close').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.askro-modal').style.display = 'none';
                });
            });

            // Save settings
            document.querySelector('.askro-save-notification-settings')?.addEventListener('click', function() {
                saveNotificationSettings();
            });

            function saveNotificationSettings() {
                const form = document.getElementById('notification-settings-form');
                const formData = new FormData(form);
                
                const data = {
                    action: 'askro_update_notification_settings',
                    nonce: askroData.nonce
                };

                // Convert FormData to object
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        document.getElementById('notification-settings-modal').style.display = 'none';
                        // Show success message
                    }
                })
                .catch(error => {
                    console.error('Save settings error:', error);
                });
            }

            // Auto-refresh notifications every 30 seconds
            setInterval(function() {
                if (!isOpen) {
                    // Just update the count
                    loadNotifications();
                }
            }, 30000);

            // Request browser notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        });
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Create a notification
     *
     * @param int $user_id User ID to notify
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $data Additional data
     * @return int|false Notification ID or false on failure
     * @since 1.0.0
     */
    public function create_notification($user_id, $type, $title, $message, $data = []) {
        global $wpdb;

        // Check if user wants this type of notification
        if (!$this->user_wants_notification($user_id, $type)) {
            return false;
        }

        $notification_data = [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'action_url' => $data['action_url'] ?? '',
            'is_read' => 0,
            'date_created' => current_time('mysql')
        ];

        $result = $wpdb->insert(
            $wpdb->prefix . 'askro_notifications',
            $notification_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        if ($result) {
            $notification_id = $wpdb->insert_id;

            // Send email notification if enabled
            if ($this->user_wants_email_notification($user_id, $type)) {
                do_action('askro_send_email_notification', $user_id, $type, $notification_data);
            }

            // Send browser notification if supported
            $this->send_browser_notification($user_id, $notification_data);

            return $notification_id;
        }

        return false;
    }

    /**
     * Get notifications via AJAX
     *
     * @since 1.0.0
     */
    public function get_notifications() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول أولاً.', 'askro')]);
        }

        $user_id = get_current_user_id();
        $limit = intval($_POST['limit'] ?? 10);
        $offset = intval($_POST['offset'] ?? 0);

        $notifications = $this->get_user_notifications($user_id, $limit, $offset);
        $unread_count = $this->get_unread_count($user_id);

        // Add type configuration to each notification
        foreach ($notifications as &$notification) {
            $notification['type_config'] = $this->notification_types[$notification['type']] ?? [
                'title' => $notification['type'],
                'icon' => '📢',
                'color' => '#6B7280'
            ];
        }

        wp_send_json_success([
            'notifications' => $notifications,
            'unread_count' => $unread_count,
            'has_more' => count($notifications) === $limit
        ]);
    }

    /**
     * Mark notification as read via AJAX
     *
     * @since 1.0.0
     */
    public function mark_notification_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول أولاً.', 'askro')]);
        }

        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();

        if ($this->mark_as_read($notification_id, $user_id)) {
            wp_send_json_success(['message' => __('تم تحديد الإشعار كمقروء.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في تحديث الإشعار.', 'askro')]);
        }
    }

    /**
     * Mark all notifications as read via AJAX
     *
     * @since 1.0.0
     */
    public function mark_all_notifications_read() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول أولاً.', 'askro')]);
        }

        $user_id = get_current_user_id();

        if ($this->mark_all_as_read($user_id)) {
            wp_send_json_success(['message' => __('تم تحديد جميع الإشعارات كمقروءة.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في تحديث الإشعارات.', 'askro')]);
        }
    }

    /**
     * Delete notification via AJAX
     *
     * @since 1.0.0
     */
    public function delete_notification() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول أولاً.', 'askro')]);
        }

        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();

        if ($this->delete_user_notification($notification_id, $user_id)) {
            wp_send_json_success(['message' => __('تم حذف الإشعار.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في حذف الإشعار.', 'askro')]);
        }
    }

    /**
     * Update notification settings via AJAX
     *
     * @since 1.0.0
     */
    public function update_notification_settings() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول أولاً.', 'askro')]);
        }

        $user_id = get_current_user_id();
        $web_notifications = $_POST['web_notifications'] ?? [];
        $email_notifications = $_POST['email_notifications'] ?? [];
        $digest_frequency = $_POST['digest_frequency'] ?? '';
        $browser_notifications = $_POST['browser_notifications'] ?? '';

        // Save settings
        update_user_meta($user_id, '_askro_web_notifications', $web_notifications);
        update_user_meta($user_id, '_askro_email_notifications', $email_notifications);
        update_user_meta($user_id, '_askro_digest_frequency', $digest_frequency);
        update_user_meta($user_id, '_askro_browser_notifications', $browser_notifications);

        wp_send_json_success(['message' => __('تم حفظ الإعدادات بنجاح.', 'askro')]);
    }

    /**
     * Get user notifications
     *
     * @param int $user_id User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Notifications
     * @since 1.0.0
     */
    public function get_user_notifications($user_id, $limit = 10, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_notifications 
             WHERE user_id = %d 
             ORDER BY date_created DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        ), ARRAY_A);
    }

    /**
     * Get unread notifications count
     *
     * @param int $user_id User ID
     * @return int Unread count
     * @since 1.0.0
     */
    public function get_unread_count($user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_notifications 
             WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    /**
     * Mark notification as read
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID
     * @return bool Success
     * @since 1.0.0
     */
    public function mark_as_read($notification_id, $user_id) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'askro_notifications',
            ['is_read' => 1],
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%d'],
            ['%d', '%d']
        ) !== false;
    }

    /**
     * Mark all notifications as read
     *
     * @param int $user_id User ID
     * @return bool Success
     * @since 1.0.0
     */
    public function mark_all_as_read($user_id) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'askro_notifications',
            ['is_read' => 1],
            ['user_id' => $user_id, 'is_read' => 0],
            ['%d'],
            ['%d', '%d']
        ) !== false;
    }

    /**
     * Delete user notification
     *
     * @param int $notification_id Notification ID
     * @param int $user_id User ID
     * @return bool Success
     * @since 1.0.0
     */
    public function delete_user_notification($notification_id, $user_id) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'askro_notifications',
            ['id' => $notification_id, 'user_id' => $user_id],
            ['%d', '%d']
        ) !== false;
    }

    /**
     * Check if user wants this type of notification
     *
     * @param int $user_id User ID
     * @param string $type Notification type
     * @return bool Wants notification
     * @since 1.0.0
     */
    public function user_wants_notification($user_id, $type) {
        $web_notifications = get_user_meta($user_id, '_askro_web_notifications', true);
        
        // Default to enabled if not set
        if (empty($web_notifications)) {
            return true;
        }

        return isset($web_notifications[$type]) && $web_notifications[$type];
    }

    /**
     * Check if user wants email notification
     *
     * @param int $user_id User ID
     * @param string $type Notification type
     * @return bool Wants email notification
     * @since 1.0.0
     */
    public function user_wants_email_notification($user_id, $type) {
        $email_notifications = get_user_meta($user_id, '_askro_email_notifications', true);
        
        // Default to disabled for email
        if (empty($email_notifications)) {
            return false;
        }

        return isset($email_notifications[$type]) && $email_notifications[$type];
    }

    /**
     * Send browser notification
     *
     * @param int $user_id User ID
     * @param array $notification_data Notification data
     * @since 1.0.0
     */
    public function send_browser_notification($user_id, $notification_data) {
        $browser_notifications = get_user_meta($user_id, '_askro_browser_notifications', true);
        
        if (!$browser_notifications) {
            return;
        }

        // This would use JavaScript to send browser notifications
        // For now, we'll just store the data for the frontend to pick up
        $pending_notifications = get_user_meta($user_id, '_askro_pending_browser_notifications', true) ?: [];
        $pending_notifications[] = $notification_data;
        update_user_meta($user_id, '_askro_pending_browser_notifications', $pending_notifications);
    }

    /**
     * Send email notification
     *
     * @param int $user_id User ID
     * @param string $type Notification type
     * @param array $notification_data Notification data
     * @since 1.0.0
     */
    public function send_email_notification($user_id, $type, $notification_data) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        $subject = sprintf('[%s] %s', get_bloginfo('name'), $notification_data['title']);
        $message = $this->get_email_template($type, $notification_data, $user);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];

        wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Get email template
     *
     * @param string $type Notification type
     * @param array $notification_data Notification data
     * @param WP_User $user User object
     * @return string Email HTML
     * @since 1.0.0
     */
    public function get_email_template($type, $notification_data, $user) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $unsubscribe_url = home_url('/askro-notifications/settings/');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($notification_data['title']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html($site_name); ?></h1>
                </div>
                <div class="content">
                    <h2><?php echo esc_html($notification_data['title']); ?></h2>
                    <p><?php _e('مرحباً', 'askro'); ?> <?php echo esc_html($user->display_name); ?>،</p>
                    <p><?php echo esc_html($notification_data['message']); ?></p>
                    
                    <?php if (!empty($notification_data['action_url'])): ?>
                    <p>
                        <a href="<?php echo esc_url($notification_data['action_url']); ?>" class="button">
                            <?php _e('عرض التفاصيل', 'askro'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="footer">
                    <p>
                        <?php _e('هذا إشعار تلقائي من', 'askro'); ?> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a>
                    </p>
                    <p>
                        <a href="<?php echo esc_url($unsubscribe_url); ?>"><?php _e('إدارة إعدادات الإشعارات', 'askro'); ?></a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php

        return ob_get_clean();
    }

    /**
     * Send weekly digest
     *
     * @since 1.0.0
     */
    public function send_weekly_digest() {
        global $wpdb;

        // Get users who want weekly digest
        $users = $wpdb->get_results(
            "SELECT u.ID, u.user_email, u.display_name 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = '_askro_digest_frequency' 
             AND um.meta_value = 'weekly'"
        );

        foreach ($users as $user) {
            $this->send_user_weekly_digest($user);
        }
    }

    /**
     * Send weekly digest to user
     *
     * @param object $user User object
     * @since 1.0.0
     */
    public function send_user_weekly_digest($user) {
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $today = date('Y-m-d');

        // Get weekly stats
        $stats = [
            'new_questions' => $this->get_questions_count(['start' => $week_ago, 'end' => $today]),
            'new_answers' => $this->get_answers_count(['start' => $week_ago, 'end' => $today]),
            'active_users' => $this->get_active_users_count(['start' => $week_ago, 'end' => $today])
        ];

        // Get popular questions
        $popular_questions = $this->get_popular_questions(['start' => $week_ago, 'end' => $today]);

        $subject = sprintf('[%s] %s', get_bloginfo('name'), __('ملخص أسبوعي', 'askro'));
        $message = $this->get_weekly_digest_template($user, $stats, $popular_questions);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];

        wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Get weekly digest template
     *
     * @param object $user User object
     * @param array $stats Weekly stats
     * @param array $popular_questions Popular questions
     * @return string Email HTML
     * @since 1.0.0
     */
    public function get_weekly_digest_template($user, $stats, $popular_questions) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('ملخص أسبوعي', 'askro'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
                .stats { display: flex; justify-content: space-around; padding: 20px; background: #f0f9ff; }
                .stat { text-align: center; }
                .stat-number { font-size: 24px; font-weight: bold; color: #3B82F6; }
                .content { padding: 20px; }
                .question-item { padding: 10px; border-bottom: 1px solid #eee; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('ملخص أسبوعي', 'askro'); ?></h1>
                    <p><?php echo esc_html($site_name); ?></p>
                </div>
                
                <div class="stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $stats['new_questions']; ?></div>
                        <div><?php _e('أسئلة جديدة', 'askro'); ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $stats['new_answers']; ?></div>
                        <div><?php _e('إجابات جديدة', 'askro'); ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                        <div><?php _e('مستخدمون نشطون', 'askro'); ?></div>
                    </div>
                </div>

                <div class="content">
                    <h2><?php _e('الأسئلة الشائعة هذا الأسبوع', 'askro'); ?></h2>
                    <?php if (!empty($popular_questions)): ?>
                        <?php foreach ($popular_questions as $question): ?>
                        <div class="question-item">
                            <h3><a href="<?php echo esc_url($question['url']); ?>"><?php echo esc_html($question['title']); ?></a></h3>
                            <p><?php echo $question['views']; ?> <?php _e('مشاهدة', 'askro'); ?> • <?php echo $question['answers']; ?> <?php _e('إجابة', 'askro'); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('لا توجد أسئلة شائعة هذا الأسبوع.', 'askro'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="footer">
                    <p>
                        <?php _e('ملخص أسبوعي من', 'askro'); ?> <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a>
                    </p>
                    <p>
                        <a href="<?php echo home_url('/askro-notifications/settings/'); ?>"><?php _e('إدارة إعدادات الإشعارات', 'askro'); ?></a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php

        return ob_get_clean();
    }

    /**
     * Add notification bell to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     * @since 1.0.0
     */
    public function add_notification_bell($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $unread_count = $this->get_unread_count($user_id);

        $wp_admin_bar->add_node([
            'id' => 'askro-notifications',
            'title' => '🔔' . ($unread_count > 0 ? ' <span class="askro-admin-bar-count">' . $unread_count . '</span>' : ''),
            'href' => home_url('/askro-notifications/'),
            'meta' => [
                'title' => __('الإشعارات', 'askro')
            ]
        ]);
    }

    /**
     * Enqueue notification scripts
     *
     * @since 1.0.0
     */
    public function enqueue_notification_scripts() {
        if (!is_user_logged_in()) {
            return;
        }

        // Add notification styles to main CSS
        wp_add_inline_style('askro-main-style', '
            .askro-admin-bar-count {
                background: #dc2626;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 11px;
                margin-left: 5px;
            }
        ');
    }

    // Notification trigger methods

    /**
     * Notify when question is answered
     *
     * @param int $answer_id Answer ID
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function notify_question_answered($answer_id, $question_id) {
        $question = get_post($question_id);
        $answer = get_post($answer_id);
        
        if (!$question || !$answer) {
            return;
        }

        $answerer = get_user_by('ID', $answer->post_author);
        
        $this->create_notification(
            $question->post_author,
            'question_answered',
            __('إجابة جديدة على سؤالك', 'askro'),
            sprintf(__('أجاب %s على سؤالك: %s', 'askro'), $answerer->display_name, $question->post_title),
            [
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'action_url' => get_permalink($question_id) . '#answer-' . $answer_id
            ]
        );
    }

    /**
     * Notify when answer is accepted
     *
     * @param int $answer_id Answer ID
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function notify_answer_accepted($answer_id, $question_id) {
        $answer = get_post($answer_id);
        $question = get_post($question_id);
        
        if (!$answer || !$question) {
            return;
        }

        $this->create_notification(
            $answer->post_author,
            'answer_accepted',
            __('تم قبول إجابتك', 'askro'),
            sprintf(__('تم قبول إجابتك على السؤال: %s', 'askro'), $question->post_title),
            [
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'action_url' => get_permalink($question_id) . '#answer-' . $answer_id
            ]
        );
    }

    /**
     * Notify when vote is cast
     *
     * @param int $vote_id Vote ID
     * @param int $post_id Post ID
     * @param string $vote_type Vote type
     * @since 1.0.0
     */
    public function notify_vote_cast($vote_id, $post_id, $vote_type) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $vote_data = $this->get_vote_data($vote_id);
        if (!$vote_data || $vote_data['vote_value'] <= 0) {
            return; // Only notify for positive votes
        }

        $post_type = $post->post_type === 'askro_question' ? 'question' : 'answer';
        $notification_type = $post_type . '_voted';

        $this->create_notification(
            $post->post_author,
            $notification_type,
            sprintf(__('تم التصويت على %s', 'askro'), $post_type === 'question' ? __('سؤالك', 'askro') : __('إجابتك', 'askro')),
            sprintf(__('حصل %s على تصويت إيجابي: %s', 'askro'), 
                $post_type === 'question' ? __('سؤالك', 'askro') : __('إجابتك', 'askro'), 
                $post->post_title
            ),
            [
                'post_id' => $post_id,
                'vote_type' => $vote_type,
                'action_url' => get_permalink($post_id)
            ]
        );
    }

    /**
     * Notify when comment is added
     *
     * @param int $comment_id Comment ID
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function notify_comment_added($comment_id, $post_id) {
        $post = get_post($post_id);
        $comment_data = $this->get_comment_data($comment_id);
        
        if (!$post || !$comment_data) {
            return;
        }

        $commenter = get_user_by('ID', $comment_data['user_id']);
        
        $this->create_notification(
            $post->post_author,
            'comment_added',
            __('تعليق جديد', 'askro'),
            sprintf(__('علق %s على منشورك: %s', 'askro'), $commenter->display_name, $post->post_title),
            [
                'post_id' => $post_id,
                'comment_id' => $comment_id,
                'action_url' => get_permalink($post_id) . '#comment-' . $comment_id
            ]
        );
    }

    /**
     * Notify when badge is earned
     *
     * @param int $user_id User ID
     * @param string $badge_name Badge name
     * @since 1.0.0
     */
    public function notify_badge_earned($user_id, $badge_name) {
        $this->create_notification(
            $user_id,
            'badge_earned',
            __('حصلت على شارة جديدة', 'askro'),
            sprintf(__('تهانينا! حصلت على شارة "%s"', 'askro'), $badge_name),
            [
                'badge_name' => $badge_name,
                'action_url' => home_url('/askro-user/' . get_userdata($user_id)->user_login . '/badges/')
            ]
        );
    }

    /**
     * Notify when achievement is unlocked
     *
     * @param int $user_id User ID
     * @param string $achievement_name Achievement name
     * @since 1.0.0
     */
    public function notify_achievement_unlocked($user_id, $achievement_name) {
        $this->create_notification(
            $user_id,
            'achievement_unlocked',
            __('فتحت إنجاز جديد', 'askro'),
            sprintf(__('رائع! فتحت إنجاز "%s"', 'askro'), $achievement_name),
            [
                'achievement_name' => $achievement_name,
                'action_url' => home_url('/askro-user/' . get_userdata($user_id)->user_login . '/achievements/')
            ]
        );
    }

    /**
     * Notify when user is followed
     *
     * @param int $follower_id Follower user ID
     * @param int $followed_id Followed user ID
     * @since 1.0.0
     */
    public function notify_user_followed($follower_id, $followed_id) {
        $follower = get_user_by('ID', $follower_id);
        
        $this->create_notification(
            $followed_id,
            'user_followed',
            __('بدأ شخص في متابعتك', 'askro'),
            sprintf(__('بدأ %s في متابعتك', 'askro'), $follower->display_name),
            [
                'follower_id' => $follower_id,
                'action_url' => home_url('/askro-user/' . $follower->user_login . '/')
            ]
        );
    }

    /**
     * Get vote data
     *
     * @param int $vote_id Vote ID
     * @return array|null Vote data
     * @since 1.0.0
     */
    private function get_vote_data($vote_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_votes WHERE id = %d",
            $vote_id
        ), ARRAY_A);
    }

    /**
     * Get comment data
     *
     * @param int $comment_id Comment ID
     * @return array|null Comment data
     * @since 1.0.0
     */
    private function get_comment_data($comment_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_comments WHERE id = %d",
            $comment_id
        ), ARRAY_A);
    }

    /**
     * Get questions count for date range (from Analytics class)
     *
     * @param array $date_range Date range
     * @return int Questions count
     * @since 1.0.0
     */
    private function get_questions_count($date_range) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'askro_question' 
             AND post_status = 'publish'
             AND DATE(post_date) BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
    }

    /**
     * Get answers count for date range (from Analytics class)
     *
     * @param array $date_range Date range
     * @return int Answers count
     * @since 1.0.0
     */
    private function get_answers_count($date_range) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'askro_answer' 
             AND post_status = 'publish'
             AND DATE(post_date) BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
    }

    /**
     * Get active users count for date range (from Analytics class)
     *
     * @param array $date_range Date range
     * @return int Active users count
     * @since 1.0.0
     */
    private function get_active_users_count($date_range) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} 
             WHERE post_type IN ('askro_question', 'askro_answer') 
             AND post_status = 'publish'
             AND DATE(post_date) BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
    }

    /**
     * Get popular questions (from Analytics class)
     *
     * @param array $date_range Date range
     * @return array Popular questions
     * @since 1.0.0
     */
    private function get_popular_questions($date_range) {
        global $wpdb;
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title,
                    COALESCE(pm_views.meta_value, 0) as views,
                    COALESCE(pm_answers.meta_value, 0) as answers
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm_views ON p.ID = pm_views.post_id 
                 AND pm_views.meta_key = '_askro_views'
             LEFT JOIN {$wpdb->postmeta} pm_answers ON p.ID = pm_answers.post_id 
                 AND pm_answers.meta_key = '_askro_answer_count'
             WHERE p.post_type = 'askro_question'
             AND p.post_status = 'publish'
             AND DATE(p.post_date) BETWEEN %s AND %s
             ORDER BY CAST(pm_views.meta_value AS UNSIGNED) DESC
             LIMIT 5",
            $date_range['start'],
            $date_range['end']
        ));
        
        $result = [];
        foreach ($questions as $question) {
            $result[] = [
                'title' => $question->post_title,
                'url' => get_permalink($question->ID),
                'views' => number_format($question->views),
                'answers' => $question->answers
            ];
        }
        
        return $result;
    }
}

