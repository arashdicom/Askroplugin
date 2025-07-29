<?php
/**
 * Analytics Class
 *
 * @package    Askro
 * @subpackage Core/Analytics
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
 * Askro Analytics Class
 *
 * Handles analytics, statistics, and reporting
 *
 * @since 1.0.0
 */
class Askro_Analytics {

    /**
     * Initialize the analytics component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_askro_get_analytics_data', [$this, 'get_analytics_data']);
        add_action('wp_ajax_askro_export_analytics', [$this, 'export_analytics']);
        
        // Track user activities
        add_action('askro_question_posted', [$this, 'track_question_activity']);
        add_action('askro_answer_posted', [$this, 'track_answer_activity']);
        add_action('askro_vote_cast', [$this, 'track_vote_activity']);
        add_action('askro_comment_posted', [$this, 'track_comment_activity']);
        
        // Daily analytics cron
        add_action('askro_daily_analytics', [$this, 'generate_daily_analytics']);
        if (!wp_next_scheduled('askro_daily_analytics')) {
            wp_schedule_event(time(), 'daily', 'askro_daily_analytics');
        }
    }

    /**
     * Render analytics dashboard
     *
     * @param array $args Arguments
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_analytics_dashboard($args = []) {
        $defaults = [
            'period' => '30_days',
            'show_charts' => true,
            'show_tables' => true,
            'show_export' => true
        ];

        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <div class="askro-analytics-dashboard" data-period="<?php echo esc_attr($args['period']); ?>">
            <!-- Analytics Header -->
            <div class="askro-analytics-header">
                <h2 class="askro-analytics-title"><?php _e('لوحة التحليلات', 'askro'); ?></h2>
                
                <div class="askro-analytics-controls">
                    <div class="askro-period-selector">
                        <label for="analytics-period"><?php _e('الفترة الزمنية:', 'askro'); ?></label>
                        <select id="analytics-period" class="askro-select">
                            <option value="7_days" <?php selected($args['period'], '7_days'); ?>><?php _e('آخر 7 أيام', 'askro'); ?></option>
                            <option value="30_days" <?php selected($args['period'], '30_days'); ?>><?php _e('آخر 30 يوم', 'askro'); ?></option>
                            <option value="90_days" <?php selected($args['period'], '90_days'); ?>><?php _e('آخر 90 يوم', 'askro'); ?></option>
                            <option value="1_year" <?php selected($args['period'], '1_year'); ?>><?php _e('آخر سنة', 'askro'); ?></option>
                            <option value="all_time" <?php selected($args['period'], 'all_time'); ?>><?php _e('كل الأوقات', 'askro'); ?></option>
                        </select>
                    </div>
                    
                    <?php if ($args['show_export']): ?>
                    <button type="button" class="askro-btn-outline askro-export-analytics">
                        📊 <?php _e('تصدير التقرير', 'askro'); ?>
                    </button>
                    <?php endif; ?>
                    
                    <button type="button" class="askro-btn-primary askro-refresh-analytics">
                        🔄 <?php _e('تحديث', 'askro'); ?>
                    </button>
                </div>
            </div>

            <!-- Key Metrics Cards -->
            <div class="askro-metrics-grid" id="metrics-grid">
                <div class="askro-loading-placeholder">
                    <div class="askro-spinner"></div>
                    <?php _e('جاري تحميل المقاييس...', 'askro'); ?>
                </div>
            </div>

            <?php if ($args['show_charts']): ?>
            <!-- Charts Section -->
            <div class="askro-charts-section">
                <div class="askro-charts-grid">
                    <!-- Activity Chart -->
                    <div class="askro-chart-container">
                        <div class="askro-chart-header">
                            <h3><?php _e('نشاط المجتمع', 'askro'); ?></h3>
                            <div class="askro-chart-legend" id="activity-legend"></div>
                        </div>
                        <div class="askro-chart-content">
                            <canvas id="activity-chart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- User Growth Chart -->
                    <div class="askro-chart-container">
                        <div class="askro-chart-header">
                            <h3><?php _e('نمو المستخدمين', 'askro'); ?></h3>
                        </div>
                        <div class="askro-chart-content">
                            <canvas id="user-growth-chart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="askro-chart-container">
                        <div class="askro-chart-header">
                            <h3><?php _e('توزيع التصنيفات', 'askro'); ?></h3>
                        </div>
                        <div class="askro-chart-content">
                            <canvas id="category-chart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Engagement Metrics -->
                    <div class="askro-chart-container">
                        <div class="askro-chart-header">
                            <h3><?php _e('مقاييس التفاعل', 'askro'); ?></h3>
                        </div>
                        <div class="askro-chart-content">
                            <canvas id="engagement-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($args['show_tables']): ?>
            <!-- Data Tables Section -->
            <div class="askro-tables-section">
                <div class="askro-tables-grid">
                    <!-- Top Contributors -->
                    <div class="askro-table-container">
                        <div class="askro-table-header">
                            <h3><?php _e('أفضل المساهمين', 'askro'); ?></h3>
                        </div>
                        <div class="askro-table-content" id="top-contributors-table">
                            <div class="askro-loading-placeholder">
                                <div class="askro-spinner"></div>
                                <?php _e('جاري التحميل...', 'askro'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Questions -->
                    <div class="askro-table-container">
                        <div class="askro-table-header">
                            <h3><?php _e('الأسئلة الشائعة', 'askro'); ?></h3>
                        </div>
                        <div class="askro-table-content" id="popular-questions-table">
                            <div class="askro-loading-placeholder">
                                <div class="askro-spinner"></div>
                                <?php _e('جاري التحميل...', 'askro'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="askro-table-container">
                        <div class="askro-table-header">
                            <h3><?php _e('النشاط الأخير', 'askro'); ?></h3>
                        </div>
                        <div class="askro-table-content" id="recent-activity-table">
                            <div class="askro-loading-placeholder">
                                <div class="askro-spinner"></div>
                                <?php _e('جاري التحميل...', 'askro'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="askro-table-container">
                        <div class="askro-table-header">
                            <h3><?php _e('مقاييس الأداء', 'askro'); ?></h3>
                        </div>
                        <div class="askro-table-content" id="performance-metrics-table">
                            <div class="askro-loading-placeholder">
                                <div class="askro-spinner"></div>
                                <?php _e('جاري التحميل...', 'askro'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Insights Section -->
            <div class="askro-insights-section">
                <div class="askro-insights-header">
                    <h3><?php _e('رؤى وتوصيات', 'askro'); ?></h3>
                </div>
                <div class="askro-insights-content" id="insights-content">
                    <div class="askro-loading-placeholder">
                        <div class="askro-spinner"></div>
                        <?php _e('جاري تحليل البيانات...', 'askro'); ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dashboard = document.querySelector('.askro-analytics-dashboard');
            if (!dashboard) return;

            let charts = {};
            
            // Load initial analytics
            loadAnalytics();

            // Period change handler
            document.getElementById('analytics-period')?.addEventListener('change', function() {
                loadAnalytics();
            });

            // Refresh button
            document.querySelector('.askro-refresh-analytics')?.addEventListener('click', function() {
                loadAnalytics();
            });

            // Export button
            document.querySelector('.askro-export-analytics')?.addEventListener('click', function() {
                exportAnalytics();
            });

            function loadAnalytics() {
                const period = document.getElementById('analytics-period')?.value || '<?php echo esc_js($args['period']); ?>';

                const data = {
                    action: 'askro_get_analytics_data',
                    period: period,
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
                        updateDashboard(result.data);
                    } else {
                        console.error('Analytics error:', result.data);
                    }
                })
                .catch(error => {
                    console.error('Analytics fetch error:', error);
                });
            }

            function updateDashboard(data) {
                // Update metrics cards
                updateMetricsGrid(data.metrics);
                
                // Update charts
                <?php if ($args['show_charts']): ?>
                updateCharts(data.charts);
                <?php endif; ?>
                
                // Update tables
                <?php if ($args['show_tables']): ?>
                updateTables(data.tables);
                <?php endif; ?>
                
                // Update insights
                updateInsights(data.insights);
            }

            function updateMetricsGrid(metrics) {
                const grid = document.getElementById('metrics-grid');
                if (!grid || !metrics) return;

                let html = '';
                Object.entries(metrics).forEach(([key, metric]) => {
                    const changeClass = metric.change >= 0 ? 'positive' : 'negative';
                    const changeIcon = metric.change >= 0 ? '📈' : '📉';
                    
                    html += `
                        <div class="askro-metric-card">
                            <div class="askro-metric-icon">${metric.icon}</div>
                            <div class="askro-metric-content">
                                <div class="askro-metric-value">${metric.value}</div>
                                <div class="askro-metric-label">${metric.label}</div>
                                <div class="askro-metric-change ${changeClass}">
                                    ${changeIcon} ${Math.abs(metric.change)}%
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                grid.innerHTML = html;
            }

            <?php if ($args['show_charts']): ?>
            function updateCharts(chartData) {
                if (!chartData) return;

                // Activity Chart
                if (chartData.activity) {
                    updateActivityChart(chartData.activity);
                }

                // User Growth Chart
                if (chartData.user_growth) {
                    updateUserGrowthChart(chartData.user_growth);
                }

                // Category Chart
                if (chartData.categories) {
                    updateCategoryChart(chartData.categories);
                }

                // Engagement Chart
                if (chartData.engagement) {
                    updateEngagementChart(chartData.engagement);
                }
            }

            function updateActivityChart(data) {
                const ctx = document.getElementById('activity-chart');
                if (!ctx) return;

                if (charts.activity) {
                    charts.activity.destroy();
                }

                charts.activity = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: '<?php _e('الأسئلة', 'askro'); ?>',
                                data: data.questions,
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: '<?php _e('الإجابات', 'askro'); ?>',
                                data: data.answers,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: '<?php _e('التعليقات', 'askro'); ?>',
                                data: data.comments,
                                borderColor: '#F59E0B',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            function updateUserGrowthChart(data) {
                const ctx = document.getElementById('user-growth-chart');
                if (!ctx) return;

                if (charts.userGrowth) {
                    charts.userGrowth.destroy();
                }

                charts.userGrowth = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '<?php _e('مستخدمون جدد', 'askro'); ?>',
                            data: data.new_users,
                            backgroundColor: '#8B5CF6',
                            borderColor: '#7C3AED',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            function updateCategoryChart(data) {
                const ctx = document.getElementById('category-chart');
                if (!ctx) return;

                if (charts.categories) {
                    charts.categories.destroy();
                }

                charts.categories = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                                '#EC4899', '#14B8A6', '#F97316', '#84CC16', '#6366F1'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            function updateEngagementChart(data) {
                const ctx = document.getElementById('engagement-chart');
                if (!ctx) return;

                if (charts.engagement) {
                    charts.engagement.destroy();
                }

                charts.engagement = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '<?php _e('مستوى التفاعل', 'askro'); ?>',
                            data: data.values,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.2)',
                            pointBackgroundColor: '#EF4444'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            <?php if ($args['show_tables']): ?>
            function updateTables(tableData) {
                if (!tableData) return;

                // Top Contributors
                if (tableData.top_contributors) {
                    updateTopContributorsTable(tableData.top_contributors);
                }

                // Popular Questions
                if (tableData.popular_questions) {
                    updatePopularQuestionsTable(tableData.popular_questions);
                }

                // Recent Activity
                if (tableData.recent_activity) {
                    updateRecentActivityTable(tableData.recent_activity);
                }

                // Performance Metrics
                if (tableData.performance_metrics) {
                    updatePerformanceMetricsTable(tableData.performance_metrics);
                }
            }

            function updateTopContributorsTable(data) {
                const container = document.getElementById('top-contributors-table');
                if (!container) return;

                let html = '<div class="askro-data-table">';
                html += '<table class="askro-table">';
                html += '<thead><tr><th><?php _e('المستخدم', 'askro'); ?></th><th><?php _e('النقاط', 'askro'); ?></th><th><?php _e('المساهمات', 'askro'); ?></th></tr></thead>';
                html += '<tbody>';

                data.forEach(user => {
                    html += `
                        <tr>
                            <td>
                                <div class="askro-user-cell">
                                    <img src="${user.avatar}" alt="${user.name}" class="askro-user-avatar">
                                    <span class="askro-user-name">${user.name}</span>
                                </div>
                            </td>
                            <td><span class="askro-points">${user.points}</span></td>
                            <td><span class="askro-contributions">${user.contributions}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            }

            function updatePopularQuestionsTable(data) {
                const container = document.getElementById('popular-questions-table');
                if (!container) return;

                let html = '<div class="askro-data-table">';
                html += '<table class="askro-table">';
                html += '<thead><tr><th><?php _e('السؤال', 'askro'); ?></th><th><?php _e('المشاهدات', 'askro'); ?></th><th><?php _e('الإجابات', 'askro'); ?></th></tr></thead>';
                html += '<tbody>';

                data.forEach(question => {
                    html += `
                        <tr>
                            <td>
                                <a href="${question.url}" class="askro-question-link">
                                    ${question.title}
                                </a>
                            </td>
                            <td><span class="askro-views">${question.views}</span></td>
                            <td><span class="askro-answers">${question.answers}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            }

            function updateRecentActivityTable(data) {
                const container = document.getElementById('recent-activity-table');
                if (!container) return;

                let html = '<div class="askro-activity-list">';

                data.forEach(activity => {
                    html += `
                        <div class="askro-activity-item">
                            <div class="askro-activity-icon">${activity.icon}</div>
                            <div class="askro-activity-content">
                                <div class="askro-activity-text">${activity.text}</div>
                                <div class="askro-activity-time">${activity.time}</div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
            }

            function updatePerformanceMetricsTable(data) {
                const container = document.getElementById('performance-metrics-table');
                if (!container) return;

                let html = '<div class="askro-metrics-list">';

                data.forEach(metric => {
                    const statusClass = metric.status === 'good' ? 'success' : metric.status === 'warning' ? 'warning' : 'danger';
                    
                    html += `
                        <div class="askro-metric-row">
                            <div class="askro-metric-name">${metric.name}</div>
                            <div class="askro-metric-value">${metric.value}</div>
                            <div class="askro-metric-status askro-status-${statusClass}">${metric.status_text}</div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
            }
            <?php endif; ?>

            function updateInsights(insights) {
                const container = document.getElementById('insights-content');
                if (!container || !insights) return;

                let html = '<div class="askro-insights-list">';

                insights.forEach(insight => {
                    const typeClass = insight.type === 'positive' ? 'success' : insight.type === 'warning' ? 'warning' : 'info';
                    
                    html += `
                        <div class="askro-insight-item askro-insight-${typeClass}">
                            <div class="askro-insight-icon">${insight.icon}</div>
                            <div class="askro-insight-content">
                                <h4 class="askro-insight-title">${insight.title}</h4>
                                <p class="askro-insight-description">${insight.description}</p>
                                ${insight.action ? `<div class="askro-insight-action">${insight.action}</div>` : ''}
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
            }

            function exportAnalytics() {
                const period = document.getElementById('analytics-period')?.value || '<?php echo esc_js($args['period']); ?>';

                const data = {
                    action: 'askro_export_analytics',
                    period: period,
                    format: 'pdf', // or 'csv', 'excel'
                    nonce: askroData.nonce
                };

                // Create form and submit for download
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = askroData.ajax_url;
                form.style.display = 'none';

                Object.entries(data).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        });
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Get analytics data via AJAX
     *
     * @since 1.0.0
     */
    public function get_analytics_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_admin_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بالوصول.', 'askro')]);
        }

        $period = sanitize_text_field($_POST['period'] ?? '30_days');
        
        $analytics_data = [
            'metrics' => $this->get_key_metrics($period),
            'charts' => $this->get_chart_data($period),
            'tables' => $this->get_table_data($period),
            'insights' => $this->generate_insights($period)
        ];

        wp_send_json_success($analytics_data);
    }

    /**
     * Get key metrics
     *
     * @param string $period Time period
     * @return array Key metrics
     * @since 1.0.0
     */
    public function get_key_metrics($period) {
        $date_range = $this->get_date_range($period);
        $previous_range = $this->get_previous_date_range($period);

        return [
            'total_questions' => [
                'icon' => '❓',
                'label' => __('إجمالي الأسئلة', 'askro'),
                'value' => number_format($this->get_questions_count($date_range)),
                'change' => $this->calculate_change(
                    $this->get_questions_count($date_range),
                    $this->get_questions_count($previous_range)
                )
            ],
            'total_answers' => [
                'icon' => '💡',
                'label' => __('إجمالي الإجابات', 'askro'),
                'value' => number_format($this->get_answers_count($date_range)),
                'change' => $this->calculate_change(
                    $this->get_answers_count($date_range),
                    $this->get_answers_count($previous_range)
                )
            ],
            'active_users' => [
                'icon' => '👥',
                'label' => __('المستخدمون النشطون', 'askro'),
                'value' => number_format($this->get_active_users_count($date_range)),
                'change' => $this->calculate_change(
                    $this->get_active_users_count($date_range),
                    $this->get_active_users_count($previous_range)
                )
            ],
            'total_votes' => [
                'icon' => '👍',
                'label' => __('إجمالي التصويتات', 'askro'),
                'value' => number_format($this->get_votes_count($date_range)),
                'change' => $this->calculate_change(
                    $this->get_votes_count($date_range),
                    $this->get_votes_count($previous_range)
                )
            ],
            'engagement_rate' => [
                'icon' => '📊',
                'label' => __('معدل التفاعل', 'askro'),
                'value' => $this->get_engagement_rate($date_range) . '%',
                'change' => $this->calculate_change(
                    $this->get_engagement_rate($date_range),
                    $this->get_engagement_rate($previous_range)
                )
            ],
            'answer_rate' => [
                'icon' => '✅',
                'label' => __('معدل الإجابة', 'askro'),
                'value' => $this->get_answer_rate($date_range) . '%',
                'change' => $this->calculate_change(
                    $this->get_answer_rate($date_range),
                    $this->get_answer_rate($previous_range)
                )
            ]
        ];
    }

    /**
     * Get chart data
     *
     * @param string $period Time period
     * @return array Chart data
     * @since 1.0.0
     */
    public function get_chart_data($period) {
        $date_range = $this->get_date_range($period);
        
        return [
            'activity' => $this->get_activity_chart_data($date_range),
            'user_growth' => $this->get_user_growth_chart_data($date_range),
            'categories' => $this->get_category_distribution_data($date_range),
            'engagement' => $this->get_engagement_chart_data($date_range)
        ];
    }

    /**
     * Get table data
     *
     * @param string $period Time period
     * @return array Table data
     * @since 1.0.0
     */
    public function get_table_data($period) {
        $date_range = $this->get_date_range($period);
        
        return [
            'top_contributors' => $this->get_top_contributors($date_range),
            'popular_questions' => $this->get_popular_questions($date_range),
            'recent_activity' => $this->get_recent_activity($date_range),
            'performance_metrics' => $this->get_performance_metrics($date_range)
        ];
    }

    /**
     * Generate insights
     *
     * @param string $period Time period
     * @return array Insights
     * @since 1.0.0
     */
    public function generate_insights($period) {
        $insights = [];
        $date_range = $this->get_date_range($period);
        
        // Analyze question trends
        $questions_trend = $this->analyze_questions_trend($date_range);
        if ($questions_trend['insight']) {
            $insights[] = $questions_trend['insight'];
        }
        
        // Analyze user engagement
        $engagement_insight = $this->analyze_engagement($date_range);
        if ($engagement_insight['insight']) {
            $insights[] = $engagement_insight['insight'];
        }
        
        // Analyze answer quality
        $quality_insight = $this->analyze_answer_quality($date_range);
        if ($quality_insight['insight']) {
            $insights[] = $quality_insight['insight'];
        }
        
        // Analyze community health
        $health_insight = $this->analyze_community_health($date_range);
        if ($health_insight['insight']) {
            $insights[] = $health_insight['insight'];
        }
        
        return $insights;
    }

    /**
     * Get date range for period
     *
     * @param string $period Period string
     * @return array Date range
     * @since 1.0.0
     */
    public function get_date_range($period) {
        $end_date = current_time('Y-m-d');
        
        switch ($period) {
            case '7_days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30_days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90_days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
            case '1_year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all_time':
                $start_date = '2020-01-01'; // Arbitrary early date
                break;
            default:
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
        }
        
        return ['start' => $start_date, 'end' => $end_date];
    }

    /**
     * Get previous date range for comparison
     *
     * @param string $period Period string
     * @return array Previous date range
     * @since 1.0.0
     */
    public function get_previous_date_range($period) {
        $current_range = $this->get_date_range($period);
        $days_diff = (strtotime($current_range['end']) - strtotime($current_range['start'])) / (60 * 60 * 24);
        
        $end_date = date('Y-m-d', strtotime($current_range['start'] . ' -1 day'));
        $start_date = date('Y-m-d', strtotime($end_date . ' -' . $days_diff . ' days'));
        
        return ['start' => $start_date, 'end' => $end_date];
    }

    /**
     * Calculate percentage change
     *
     * @param float $current Current value
     * @param float $previous Previous value
     * @return float Percentage change
     * @since 1.0.0
     */
    public function calculate_change($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get questions count for date range
     *
     * @param array $date_range Date range
     * @return int Questions count
     * @since 1.0.0
     */
    public function get_questions_count($date_range) {
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
     * Get answers count for date range
     *
     * @param array $date_range Date range
     * @return int Answers count
     * @since 1.0.0
     */
    public function get_answers_count($date_range) {
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
     * Get active users count for date range
     *
     * @param array $date_range Date range
     * @return int Active users count
     * @since 1.0.0
     */
    public function get_active_users_count($date_range) {
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
     * Get votes count for date range
     *
     * @param array $date_range Date range
     * @return int Votes count
     * @since 1.0.0
     */
    public function get_votes_count($date_range) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_votes 
             WHERE DATE(voted_at) BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
    }

    /**
     * Get engagement rate for date range
     *
     * @param array $date_range Date range
     * @return float Engagement rate
     * @since 1.0.0
     */
    public function get_engagement_rate($date_range) {
        $questions_count = $this->get_questions_count($date_range);
        $answers_count = $this->get_answers_count($date_range);
        $votes_count = $this->get_votes_count($date_range);
        
        $total_content = $questions_count + $answers_count;
        $total_engagement = $votes_count;
        
        if ($total_content == 0) {
            return 0;
        }
        
        return round(($total_engagement / $total_content) * 100, 1);
    }

    /**
     * Get answer rate for date range
     *
     * @param array $date_range Date range
     * @return float Answer rate
     * @since 1.0.0
     */
    public function get_answer_rate($date_range) {
        $questions_count = $this->get_questions_count($date_range);
        
        if ($questions_count == 0) {
            return 0;
        }
        
        global $wpdb;
        
        $answered_questions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT q.ID) FROM {$wpdb->posts} q
             INNER JOIN {$wpdb->posts} a ON q.ID = (
                 SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id = a.ID AND meta_key = '_askro_question_id'
             )
             WHERE q.post_type = 'askro_question' 
             AND q.post_status = 'publish'
             AND a.post_type = 'askro_answer'
             AND a.post_status = 'publish'
             AND DATE(q.post_date) BETWEEN %s AND %s",
            $date_range['start'],
            $date_range['end']
        ));
        
        return round(($answered_questions / $questions_count) * 100, 1);
    }

    /**
     * Get activity chart data
     *
     * @param array $date_range Date range
     * @return array Activity chart data
     * @since 1.0.0
     */
    public function get_activity_chart_data($date_range) {
        global $wpdb;
        
        $days = [];
        $questions = [];
        $answers = [];
        $comments = [];
        
        $start = new DateTime($date_range['start']);
        $end = new DateTime($date_range['end']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->add($interval));
        
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $days[] = $date->format('M j');
            
            // Questions
            $questions[] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'askro_question' 
                 AND post_status = 'publish'
                 AND DATE(post_date) = %s",
                $day
            ));
            
            // Answers
            $answers[] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'askro_answer' 
                 AND post_status = 'publish'
                 AND DATE(post_date) = %s",
                $day
            ));
            
            // Comments
            $comments[] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments 
                 WHERE status = 'approved'
                 AND DATE(created_at) = %s",
                $day
            ));
        }
        
        return [
            'labels' => $days,
            'questions' => $questions,
            'answers' => $answers,
            'comments' => $comments
        ];
    }

    /**
     * Get user growth chart data
     *
     * @param array $date_range Date range
     * @return array User growth chart data
     * @since 1.0.0
     */
    public function get_user_growth_chart_data($date_range) {
        global $wpdb;
        
        $days = [];
        $new_users = [];
        
        $start = new DateTime($date_range['start']);
        $end = new DateTime($date_range['end']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->add($interval));
        
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $days[] = $date->format('M j');
            
            $new_users[] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} 
                 WHERE DATE(user_registered) = %s",
                $day
            ));
        }
        
        return [
            'labels' => $days,
            'new_users' => $new_users
        ];
    }

    /**
     * Get category distribution data
     *
     * @param array $date_range Date range
     * @return array Category distribution data
     * @since 1.0.0
     */
    public function get_category_distribution_data($date_range) {
        global $wpdb;
        
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT t.name, COUNT(p.ID) as count
             FROM {$wpdb->terms} t
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             WHERE tt.taxonomy = 'askro_question_category'
             AND p.post_type = 'askro_question'
             AND p.post_status = 'publish'
             AND DATE(p.post_date) BETWEEN %s AND %s
             GROUP BY t.term_id
             ORDER BY count DESC
             LIMIT 10",
            $date_range['start'],
            $date_range['end']
        ));
        
        $labels = [];
        $values = [];
        
        foreach ($categories as $category) {
            $labels[] = $category->name;
            $values[] = $category->count;
        }
        
        return [
            'labels' => $labels,
            'values' => $values
        ];
    }

    /**
     * Get engagement chart data
     *
     * @param array $date_range Date range
     * @return array Engagement chart data
     * @since 1.0.0
     */
    public function get_engagement_chart_data($date_range) {
        // This would calculate various engagement metrics
        // For now, returning sample data
        return [
            'labels' => [
                __('الأسئلة', 'askro'),
                __('الإجابات', 'askro'),
                __('التصويتات', 'askro'),
                __('التعليقات', 'askro'),
                __('المشاركات', 'askro')
            ],
            'values' => [85, 92, 78, 65, 88] // Sample engagement percentages
        ];
    }

    /**
     * Get top contributors
     *
     * @param array $date_range Date range
     * @return array Top contributors
     * @since 1.0.0
     */
    public function get_top_contributors($date_range) {
        global $wpdb;
        
        $contributors = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, 
                    COUNT(p.ID) as contributions,
                    COALESCE(SUM(pt.points), 0) as points
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                 AND p.post_type IN ('askro_question', 'askro_answer')
                 AND p.post_status = 'publish'
                 AND DATE(p.post_date) BETWEEN %s AND %s
             LEFT JOIN {$wpdb->prefix}askro_points_log pt ON u.ID = pt.user_id
             GROUP BY u.ID
             HAVING contributions > 0
             ORDER BY contributions DESC, points DESC
             LIMIT 10",
            $date_range['start'],
            $date_range['end']
        ));
        
        $result = [];
        foreach ($contributors as $contributor) {
            $result[] = [
                'name' => $contributor->display_name,
                'avatar' => get_avatar_url($contributor->ID, ['size' => 32]),
                'points' => number_format($contributor->points),
                'contributions' => $contributor->contributions
            ];
        }
        
        return $result;
    }

    /**
     * Get popular questions
     *
     * @param array $date_range Date range
     * @return array Popular questions
     * @since 1.0.0
     */
    public function get_popular_questions($date_range) {
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
             LIMIT 10",
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

    /**
     * Get recent activity
     *
     * @param array $date_range Date range
     * @return array Recent activity
     * @since 1.0.0
     */
    public function get_recent_activity($date_range) {
        global $wpdb;
        
        // Get recent questions and answers
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_type, p.post_title, p.post_date, u.display_name
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->users} u ON p.post_author = u.ID
             WHERE p.post_type IN ('askro_question', 'askro_answer')
             AND p.post_status = 'publish'
             AND DATE(p.post_date) BETWEEN %s AND %s
             ORDER BY p.post_date DESC
             LIMIT 20",
            $date_range['start'],
            $date_range['end']
        ));
        
        $result = [];
        foreach ($activities as $activity) {
            $icon = $activity->post_type === 'askro_question' ? '❓' : '💡';
            $action = $activity->post_type === 'askro_question' ? 
                __('طرح سؤال', 'askro') : __('قدم إجابة', 'askro');
            
            $result[] = [
                'icon' => $icon,
                'text' => sprintf('%s %s: %s', $activity->display_name, $action, $activity->post_title),
                'time' => human_time_diff(strtotime($activity->post_date), current_time('timestamp')) . ' ' . __('مضت', 'askro')
            ];
        }
        
        return $result;
    }

    /**
     * Get performance metrics
     *
     * @param array $date_range Date range
     * @return array Performance metrics
     * @since 1.0.0
     */
    public function get_performance_metrics($date_range) {
        $answer_rate = $this->get_answer_rate($date_range);
        $engagement_rate = $this->get_engagement_rate($date_range);
        $active_users = $this->get_active_users_count($date_range);
        
        return [
            [
                'name' => __('معدل الإجابة', 'askro'),
                'value' => $answer_rate . '%',
                'status' => $answer_rate >= 70 ? 'good' : ($answer_rate >= 50 ? 'warning' : 'poor'),
                'status_text' => $answer_rate >= 70 ? __('ممتاز', 'askro') : ($answer_rate >= 50 ? __('جيد', 'askro') : __('يحتاج تحسين', 'askro'))
            ],
            [
                'name' => __('معدل التفاعل', 'askro'),
                'value' => $engagement_rate . '%',
                'status' => $engagement_rate >= 80 ? 'good' : ($engagement_rate >= 60 ? 'warning' : 'poor'),
                'status_text' => $engagement_rate >= 80 ? __('ممتاز', 'askro') : ($engagement_rate >= 60 ? __('جيد', 'askro') : __('يحتاج تحسين', 'askro'))
            ],
            [
                'name' => __('المستخدمون النشطون', 'askro'),
                'value' => number_format($active_users),
                'status' => $active_users >= 50 ? 'good' : ($active_users >= 20 ? 'warning' : 'poor'),
                'status_text' => $active_users >= 50 ? __('ممتاز', 'askro') : ($active_users >= 20 ? __('جيد', 'askro') : __('يحتاج تحسين', 'askro'))
            ]
        ];
    }

    /**
     * Analyze questions trend
     *
     * @param array $date_range Date range
     * @return array Analysis result
     * @since 1.0.0
     */
    public function analyze_questions_trend($date_range) {
        $current_count = $this->get_questions_count($date_range);
        $previous_count = $this->get_questions_count($this->get_previous_date_range('30_days'));
        
        $change = $this->calculate_change($current_count, $previous_count);
        
        if ($change > 20) {
            return [
                'insight' => [
                    'type' => 'positive',
                    'icon' => '📈',
                    'title' => __('نمو ممتاز في الأسئلة', 'askro'),
                    'description' => sprintf(__('زادت الأسئلة بنسبة %s%% مقارنة بالفترة السابقة. هذا مؤشر إيجابي على نمو المجتمع.', 'askro'), $change),
                    'action' => __('استمر في تشجيع المستخدمين على طرح الأسئلة', 'askro')
                ]
            ];
        } elseif ($change < -10) {
            return [
                'insight' => [
                    'type' => 'warning',
                    'icon' => '📉',
                    'title' => __('انخفاض في الأسئلة', 'askro'),
                    'description' => sprintf(__('انخفضت الأسئلة بنسبة %s%% مقارنة بالفترة السابقة.', 'askro'), abs($change)),
                    'action' => __('فكر في حملات تشجيعية لزيادة المشاركة', 'askro')
                ]
            ];
        }
        
        return ['insight' => null];
    }

    /**
     * Analyze engagement
     *
     * @param array $date_range Date range
     * @return array Analysis result
     * @since 1.0.0
     */
    public function analyze_engagement($date_range) {
        $engagement_rate = $this->get_engagement_rate($date_range);
        
        if ($engagement_rate >= 80) {
            return [
                'insight' => [
                    'type' => 'positive',
                    'icon' => '🎉',
                    'title' => __('تفاعل ممتاز', 'askro'),
                    'description' => sprintf(__('معدل التفاعل %s%% وهو ممتاز. المجتمع نشط ومتفاعل.', 'askro'), $engagement_rate),
                    'action' => __('حافظ على هذا المستوى الرائع', 'askro')
                ]
            ];
        } elseif ($engagement_rate < 50) {
            return [
                'insight' => [
                    'type' => 'warning',
                    'icon' => '⚠️',
                    'title' => __('تفاعل منخفض', 'askro'),
                    'description' => sprintf(__('معدل التفاعل %s%% وهو أقل من المطلوب.', 'askro'), $engagement_rate),
                    'action' => __('فكر في استراتيجيات لزيادة التفاعل مثل المسابقات والتحديات', 'askro')
                ]
            ];
        }
        
        return ['insight' => null];
    }

    /**
     * Analyze answer quality
     *
     * @param array $date_range Date range
     * @return array Analysis result
     * @since 1.0.0
     */
    public function analyze_answer_quality($date_range) {
        $answer_rate = $this->get_answer_rate($date_range);
        
        if ($answer_rate >= 80) {
            return [
                'insight' => [
                    'type' => 'positive',
                    'icon' => '✅',
                    'title' => __('معدل إجابة ممتاز', 'askro'),
                    'description' => sprintf(__('معدل الإجابة %s%% وهو ممتاز. معظم الأسئلة تحصل على إجابات.', 'askro'), $answer_rate),
                    'action' => __('شجع على تقديم إجابات عالية الجودة', 'askro')
                ]
            ];
        } elseif ($answer_rate < 60) {
            return [
                'insight' => [
                    'type' => 'warning',
                    'icon' => '❓',
                    'title' => __('معدل إجابة منخفض', 'askro'),
                    'description' => sprintf(__('معدل الإجابة %s%% وهو أقل من المطلوب. العديد من الأسئلة بدون إجابات.', 'askro'), $answer_rate),
                    'action' => __('شجع الخبراء على الإجابة وفكر في نظام مكافآت', 'askro')
                ]
            ];
        }
        
        return ['insight' => null];
    }

    /**
     * Analyze community health
     *
     * @param array $date_range Date range
     * @return array Analysis result
     * @since 1.0.0
     */
    public function analyze_community_health($date_range) {
        $active_users = $this->get_active_users_count($date_range);
        $total_content = $this->get_questions_count($date_range) + $this->get_answers_count($date_range);
        
        $content_per_user = $active_users > 0 ? $total_content / $active_users : 0;
        
        if ($content_per_user >= 3) {
            return [
                'insight' => [
                    'type' => 'positive',
                    'icon' => '🌟',
                    'title' => __('مجتمع صحي ونشط', 'askro'),
                    'description' => sprintf(__('كل مستخدم نشط ينتج %.1f محتوى في المتوسط. هذا مؤشر على مجتمع صحي.', 'askro'), $content_per_user),
                    'action' => __('استمر في دعم المجتمع وتشجيع المشاركة', 'askro')
                ]
            ];
        } elseif ($content_per_user < 1.5) {
            return [
                'insight' => [
                    'type' => 'info',
                    'icon' => '💡',
                    'title' => __('فرصة لزيادة المشاركة', 'askro'),
                    'description' => sprintf(__('كل مستخدم نشط ينتج %.1f محتوى في المتوسط. يمكن تحسين هذا.', 'askro'), $content_per_user),
                    'action' => __('فكر في برامج تحفيزية لزيادة مشاركة المستخدمين', 'askro')
                ]
            ];
        }
        
        return ['insight' => null];
    }

    /**
     * Track question activity
     *
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function track_question_activity($question_id) {
        $this->record_activity('question_posted', $question_id);
    }

    /**
     * Track answer activity
     *
     * @param int $answer_id Answer ID
     * @since 1.0.0
     */
    public function track_answer_activity($answer_id) {
        $this->record_activity('answer_posted', $answer_id);
    }

    /**
     * Track vote activity
     *
     * @param int $vote_id Vote ID
     * @since 1.0.0
     */
    public function track_vote_activity($vote_id) {
        $this->record_activity('vote_cast', $vote_id);
    }

    /**
     * Track comment activity
     *
     * @param int $comment_id Comment ID
     * @since 1.0.0
     */
    public function track_comment_activity($comment_id) {
        $this->record_activity('comment_posted', $comment_id);
    }

    /**
     * Record activity in analytics table
     *
     * @param string $activity_type Activity type
     * @param int $object_id Object ID
     * @since 1.0.0
     */
    public function record_activity($activity_type, $object_id) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'askro_analytics',
            [
                'activity_type' => $activity_type,
                'object_id' => $object_id,
                'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
                'date_created' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Generate daily analytics
     *
     * @since 1.0.0
     */
    public function generate_daily_analytics() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Calculate daily metrics
        $metrics = [
            'date' => $yesterday,
            'questions_count' => $this->get_questions_count(['start' => $yesterday, 'end' => $yesterday]),
            'answers_count' => $this->get_answers_count(['start' => $yesterday, 'end' => $yesterday]),
            'votes_count' => $this->get_votes_count(['start' => $yesterday, 'end' => $yesterday]),
            'active_users_count' => $this->get_active_users_count(['start' => $yesterday, 'end' => $yesterday]),
            'engagement_rate' => $this->get_engagement_rate(['start' => $yesterday, 'end' => $yesterday]),
            'answer_rate' => $this->get_answer_rate(['start' => $yesterday, 'end' => $yesterday])
        ];
        
        // Store daily metrics in analytics table instead of non-existent daily_analytics table
        $wpdb->insert(
            $wpdb->prefix . 'askro_analytics',
            [
                'event_type' => 'daily_summary',
                'user_id' => 0,
                'object_type' => 'daily_metrics',
                'object_id' => 0,
                'data' => json_encode($metrics),
                'ip_hash' => '',
                'user_agent_hash' => '',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s']
        );
        
        return $metrics;
    }

    /**
     * Export analytics via AJAX
     *
     * @since 1.0.0
     */
    public function export_analytics() {
        if (!current_user_can('manage_options')) {
            wp_die(__('غير مصرح لك بالوصول.', 'askro'));
        }

        $period = sanitize_text_field($_POST['period'] ?? '30_days');
        $format = sanitize_text_field($_POST['format'] ?? 'pdf');
        
        $analytics_data = [
            'metrics' => $this->get_key_metrics($period),
            'tables' => $this->get_table_data($period),
            'insights' => $this->generate_insights($period)
        ];
        
        switch ($format) {
            case 'csv':
                $this->export_to_csv($analytics_data, $period);
                break;
            case 'excel':
                $this->export_to_excel($analytics_data, $period);
                break;
            default: // pdf
                $this->export_to_pdf($analytics_data, $period);
                break;
        }
    }

    /**
     * Export to PDF
     *
     * @param array $data Analytics data
     * @param string $period Period
     * @since 1.0.0
     */
    public function export_to_pdf($data, $period) {
        // This would generate a PDF report
        // For now, just output a simple text file
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="askro-analytics-' . $period . '.pdf"');
        
        echo "Askro Analytics Report - " . $period . "\n\n";
        echo "Generated on: " . current_time('Y-m-d H:i:s') . "\n\n";
        
        foreach ($data['metrics'] as $key => $metric) {
            echo $metric['label'] . ": " . $metric['value'] . " (" . $metric['change'] . "%)\n";
        }
        
        exit;
    }

    /**
     * Export to CSV
     *
     * @param array $data Analytics data
     * @param string $period Period
     * @since 1.0.0
     */
    public function export_to_csv($data, $period) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="askro-analytics-' . $period . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Metric', 'Value', 'Change']);
        
        // Data
        foreach ($data['metrics'] as $key => $metric) {
            fputcsv($output, [$metric['label'], $metric['value'], $metric['change'] . '%']);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export to Excel
     *
     * @param array $data Analytics data
     * @param string $period Period
     * @since 1.0.0
     */
    public function export_to_excel($data, $period) {
        // This would generate an Excel file
        // For now, just use CSV format
        $this->export_to_csv($data, $period);
    }
}

