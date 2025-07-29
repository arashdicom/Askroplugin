/**
 * Askro Admin JavaScript
 *
 * @package    Askro
 * @subpackage Assets/JS
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

(function($) {
    'use strict';

    // Admin Askro object
    window.AskroAdmin = {
        
        // Configuration
        config: {
            ajaxUrl: askroAdmin.ajax_url || '',
            nonce: askroAdmin.nonce || '',
            strings: askroAdmin.strings || {}
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.initCharts();
        },

        // Bind events
        bindEvents: function() {
            $(document).ready(this.onDocumentReady.bind(this));
            
            // Tab navigation
            $(document).on('click', '.askro-admin-tab', this.handleTabClick.bind(this));
            
            // Inline editing
            $(document).on('click', '.askro-inline-edit', this.handleInlineEdit.bind(this));
            $(document).on('click', '.askro-inline-save', this.handleInlineSave.bind(this));
            $(document).on('click', '.askro-inline-cancel', this.handleInlineCancel.bind(this));
            
            // Bulk actions
            $(document).on('change', '.askro-select-all', this.handleSelectAll.bind(this));
            $(document).on('click', '.askro-bulk-action', this.handleBulkAction.bind(this));
            
            // System tools
            $(document).on('click', '.askro-system-tool', this.handleSystemTool.bind(this));
            
            // Settings forms
            $(document).on('submit', '.askro-settings-form', this.handleSettingsSubmit.bind(this));
        },

        // Document ready handler
        onDocumentReady: function() {
            this.initTabs();
            this.initDataTables();
            this.initColorPickers();
            this.initTooltips();
            this.loadDashboardData();
        },

        // Initialize components
        initComponents: function() {
            // Initialize sortable tables
            this.initSortableTables();
            
            // Initialize form validation
            this.initFormValidation();
            
            // Initialize media uploader
            this.initMediaUploader();
        },

        // Initialize tabs
        initTabs: function() {
            // Show first tab by default
            $('.askro-admin-tab:first').addClass('active');
            $('.askro-admin-tab-content:first').addClass('active');
            
            // Handle hash navigation
            if (window.location.hash) {
                const tabId = window.location.hash.substring(1);
                this.showTab(tabId);
            }
        },

        // Handle tab click
        handleTabClick: function(e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');
            
            this.showTab(tabId);
            
            // Update URL hash
            window.history.replaceState(null, null, '#' + tabId);
        },

        // Show tab
        showTab: function(tabId) {
            // Hide all tabs and content
            $('.askro-admin-tab').removeClass('active');
            $('.askro-admin-tab-content').removeClass('active');
            
            // Show selected tab and content
            $('[data-tab="' + tabId + '"]').addClass('active');
            $('#' + tabId).addClass('active');
        },

        // Initialize data tables
        initDataTables: function() {
            $('.askro-data-table').each(function() {
                const $table = $(this);
                const options = $table.data('options') || {};
                
                // Add sorting functionality
                $table.find('th[data-sort]').addClass('askro-sortable').on('click', function() {
                    const $th = $(this);
                    const sortBy = $th.data('sort');
                    const currentOrder = $th.data('order') || 'asc';
                    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                    
                    AskroAdmin.sortTable($table, sortBy, newOrder);
                    
                    // Update UI
                    $table.find('th').removeClass('askro-sort-asc askro-sort-desc');
                    $th.addClass('askro-sort-' + newOrder).data('order', newOrder);
                });
            });
        },

        // Sort table
        sortTable: function($table, sortBy, order) {
            const $tbody = $table.find('tbody');
            const $rows = $tbody.find('tr').toArray();
            
            $rows.sort(function(a, b) {
                const aVal = $(a).find('[data-sort-value]').data('sort-value') || $(a).find('td').eq($(a).find('th[data-sort="' + sortBy + '"]').index()).text();
                const bVal = $(b).find('[data-sort-value]').data('sort-value') || $(b).find('td').eq($(b).find('th[data-sort="' + sortBy + '"]').index()).text();
                
                if (order === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            $tbody.empty().append($rows);
        },

        // Handle inline edit
        handleInlineEdit: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('tr');
            
            // Convert cells to inputs
            $row.find('.askro-editable').each(function() {
                const $cell = $(this);
                const value = $cell.text().trim();
                const type = $cell.data('type') || 'text';
                
                let input;
                if (type === 'select') {
                    const options = $cell.data('options') || [];
                    input = '<select class="askro-admin-form-input">';
                    options.forEach(option => {
                        const selected = option.value === value ? 'selected' : '';
                        input += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                    });
                    input += '</select>';
                } else {
                    input = `<input type="${type}" class="askro-admin-form-input" value="${value}">`;
                }
                
                $cell.data('original-value', value).html(input);
            });
            
            // Show save/cancel buttons
            $button.hide();
            $row.find('.askro-inline-actions').show();
        },

        // Handle inline save
        handleInlineSave: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('tr');
            const rowId = $row.data('id');
            const data = {};
            
            // Collect data
            $row.find('.askro-editable').each(function() {
                const $cell = $(this);
                const field = $cell.data('field');
                const value = $cell.find('input, select').val();
                data[field] = value;
            });
            
            // Show loading
            $button.prop('disabled', true).text(this.config.strings.saving);
            
            // Send AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_inline_edit',
                    nonce: this.config.nonce,
                    id: rowId,
                    data: data
                },
                success: function(response) {
                    if (response.success) {
                        // Update cells with new values
                        $row.find('.askro-editable').each(function() {
                            const $cell = $(this);
                            const field = $cell.data('field');
                            $cell.text(data[field]);
                        });
                        
                        this.showNotification(response.data.message, 'success');
                        this.exitInlineEdit($row);
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.save_error, 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text(this.config.strings.save);
                }.bind(this)
            });
        },

        // Handle inline cancel
        handleInlineCancel: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('tr');
            
            // Restore original values
            $row.find('.askro-editable').each(function() {
                const $cell = $(this);
                const originalValue = $cell.data('original-value');
                $cell.text(originalValue);
            });
            
            this.exitInlineEdit($row);
        },

        // Exit inline edit mode
        exitInlineEdit: function($row) {
            $row.find('.askro-inline-edit').show();
            $row.find('.askro-inline-actions').hide();
        },

        // Handle select all
        handleSelectAll: function(e) {
            const $checkbox = $(e.currentTarget);
            const $table = $checkbox.closest('table');
            const isChecked = $checkbox.is(':checked');
            
            $table.find('tbody input[type="checkbox"]').prop('checked', isChecked);
        },

        // Handle bulk action
        handleBulkAction: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const action = $button.data('action');
            const $table = $button.closest('.askro-admin-table-container').find('table');
            const selectedIds = [];
            
            $table.find('tbody input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                this.showNotification(this.config.strings.no_items_selected, 'warning');
                return;
            }
            
            if (!confirm(this.config.strings.confirm_bulk_action)) {
                return;
            }
            
            // Show loading
            $button.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_bulk_action',
                    nonce: this.config.nonce,
                    bulk_action: action,
                    ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        location.reload(); // Refresh the page
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.bulk_action_error, 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        // Handle system tool
        handleSystemTool: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const tool = $button.data('tool');
            
            if (!confirm(this.config.strings.confirm_system_tool)) {
                return;
            }
            
            // Show loading
            $button.prop('disabled', true).addClass('askro-admin-loading');
            
            // Send AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_system_tool',
                    nonce: this.config.nonce,
                    tool: tool
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        
                        // Update display if needed
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.system_tool_error, 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).removeClass('askro-admin-loading');
                }
            });
        },

        // Handle settings submit
        handleSettingsSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('[type="submit"]');
            
            // Show loading
            $submitButton.prop('disabled', true).text(this.config.strings.saving);
            
            // Send AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=askro_save_settings&nonce=' + this.config.nonce,
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification(this.config.strings.save_error, 'error');
                }.bind(this),
                complete: function() {
                    $submitButton.prop('disabled', false).text(this.config.strings.save_settings);
                }
            });
        },

        // Initialize charts
        initCharts: function() {
            if (typeof Chart !== 'undefined') {
                this.initDashboardCharts();
                this.initAnalyticsCharts();
            }
        },

        // Initialize dashboard charts
        initDashboardCharts: function() {
            // Questions over time chart
            const $questionsChart = $('#askro-questions-chart');
            if ($questionsChart.length) {
                const ctx = $questionsChart[0].getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: $questionsChart.data('labels') || [],
                        datasets: [{
                            label: this.config.strings.questions,
                            data: $questionsChart.data('data') || [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
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

            // User activity chart
            const $activityChart = $('#askro-activity-chart');
            if ($activityChart.length) {
                const ctx = $activityChart[0].getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: $activityChart.data('labels') || [],
                        datasets: [{
                            data: $activityChart.data('data') || [],
                            backgroundColor: [
                                '#3b82f6',
                                '#8b5cf6',
                                '#06b6d4',
                                '#22c55e',
                                '#f59e0b'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        },

        // Initialize analytics charts
        initAnalyticsCharts: function() {
            // Load analytics data via AJAX
            console.log('Loading analytics data...');
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_get_analytics_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    console.log('Analytics response:', response);
                    if (response.success) {
                        this.renderAnalyticsCharts(response.data);
                    } else {
                        console.error('Analytics error:', response.data);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Analytics AJAX error:', error);
                }
            });
        },

        // Load analytics data
        loadAnalyticsData: function() {
            console.log('Loading analytics data...');
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_get_analytics_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    console.log('Analytics response:', response);
                    if (response.success) {
                        this.renderAnalyticsCharts(response.data);
                    } else {
                        console.error('Analytics error:', response.data);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Analytics AJAX error:', error);
                }
            });
        },

        // Render analytics charts
        renderAnalyticsCharts: function(data) {
            console.log('Analytics data received:', data);
            
            // Update metrics cards if they exist
            if (data.metrics) {
                this.updateMetricsCards(data.metrics);
            }
            
            // Render charts if data exists
            if (data.charts) {
                this.renderCharts(data.charts);
            }
            
            // Update tables if data exists
            if (data.tables) {
                this.updateTables(data.tables);
            }
        },

        // Update metrics cards
        updateMetricsCards: function(metrics) {
            Object.keys(metrics).forEach(key => {
                const $card = $('[data-metric="' + key + '"]');
                if ($card.length) {
                    $card.find('.metric-value').text(metrics[key].value);
                    $card.find('.metric-change').text(metrics[key].change);
                }
            });
        },

        // Render charts
        renderCharts: function(charts) {
            // Activity chart
            if (charts.activity && window.Chart) {
                this.renderActivityChart(charts.activity);
            }
            
            // User growth chart
            if (charts.user_growth && window.Chart) {
                this.renderUserGrowthChart(charts.user_growth);
            }
        },

        // Render activity chart
        renderActivityChart: function(data) {
            const ctx = document.getElementById('activity-chart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'الأسئلة',
                        data: data.questions || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'الإجابات',
                        data: data.answers || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        },

        // Render user growth chart
        renderUserGrowthChart: function(data) {
            const ctx = document.getElementById('user-growth-chart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'المستخدمون الجدد',
                        data: data.values || [],
                        backgroundColor: '#8b5cf6',
                        borderColor: '#7c3aed',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        },

        // Update tables
        updateTables: function(tables) {
            // Update top contributors table
            if (tables.top_contributors) {
                this.updateTopContributorsTable(tables.top_contributors);
            }
            
            // Update popular questions table
            if (tables.popular_questions) {
                this.updatePopularQuestionsTable(tables.popular_questions);
            }
        },

        // Update top contributors table
        updateTopContributorsTable: function(contributors) {
            const $table = $('#top-contributors-table tbody');
            if (!$table.length) return;
            
            $table.empty();
            contributors.forEach(contributor => {
                $table.append(`
                    <tr>
                        <td>${contributor.display_name}</td>
                        <td>${contributor.total_points}</td>
                        <td>${contributor.questions_count}</td>
                        <td>${contributor.answers_count}</td>
                    </tr>
                `);
            });
        },

        // Update popular questions table
        updatePopularQuestionsTable: function(questions) {
            const $table = $('#popular-questions-table tbody');
            if (!$table.length) return;
            
            $table.empty();
            questions.forEach(question => {
                $table.append(`
                    <tr>
                        <td>${question.post_title}</td>
                        <td>${question.author_name}</td>
                        <td>${question.views}</td>
                        <td>${question.votes}</td>
                        <td>${question.answers}</td>
                    </tr>
                `);
            });
        },

        // Initialize color pickers
        initColorPickers: function() {
            $('.askro-color-picker').each(function() {
                const $input = $(this);
                
                // Create color picker wrapper
                const $wrapper = $('<div class="askro-color-picker-wrapper"></div>');
                const $preview = $('<div class="askro-color-preview"></div>');
                
                $input.wrap($wrapper);
                $input.after($preview);
                
                // Update preview
                const updatePreview = function() {
                    $preview.css('background-color', $input.val());
                };
                
                updatePreview();
                $input.on('change', updatePreview);
            });
        },

        // Initialize tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const text = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    AskroAdmin.showTooltip($element, text);
                });

                $element.on('mouseleave', function() {
                    AskroAdmin.hideTooltip();
                });
            });
        },

        // Show tooltip
        showTooltip: function($element, text) {
            const $tooltip = $('<div class="askro-admin-tooltip">' + text + '</div>');
            $('body').append($tooltip);

            const offset = $element.offset();
            const elementHeight = $element.outerHeight();
            
            $tooltip.css({
                top: offset.top + elementHeight + 5,
                left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
            });

            $tooltip.addClass('askro-fade-in');
        },

        // Hide tooltip
        hideTooltip: function() {
            $('.askro-admin-tooltip').remove();
        },

        // Load dashboard data
        loadDashboardData: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_get_dashboard_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.updateDashboardCards(response.data);
                    }
                }.bind(this)
            });
        },

        // Update dashboard cards
        updateDashboardCards: function(data) {
            Object.keys(data).forEach(key => {
                const $card = $('[data-metric="' + key + '"]');
                if ($card.length) {
                    $card.find('.askro-dashboard-card-value').text(data[key].value);
                    $card.find('.askro-dashboard-card-change').text(data[key].change);
                }
            });
        },

        // Initialize sortable tables
        initSortableTables: function() {
            if (typeof Sortable !== 'undefined') {
                $('.askro-sortable-table tbody').each(function() {
                    new Sortable(this, {
                        handle: '.askro-drag-handle',
                        animation: 150,
                        onEnd: function(evt) {
                            AskroAdmin.updateTableOrder(evt.from);
                        }
                    });
                });
            }
        },

        // Update table order
        updateTableOrder: function($table) {
            const order = [];
            $table.find('tr').each(function() {
                order.push($(this).data('id'));
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'askro_update_order',
                    nonce: this.config.nonce,
                    order: order
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                    }
                }.bind(this)
            });
        },

        // Initialize form validation
        initFormValidation: function() {
            $('.askro-validate-form').on('submit', function(e) {
                const $form = $(this);
                let isValid = true;

                // Clear previous errors
                $form.find('.askro-field-error').remove();
                $form.find('.askro-error').removeClass('askro-error');

                // Validate required fields
                $form.find('[required]').each(function() {
                    const $field = $(this);
                    if (!$field.val().trim()) {
                        AskroAdmin.showFieldError($field, AskroAdmin.config.strings.field_required);
                        isValid = false;
                    }
                });

                // Validate email fields
                $form.find('[type="email"]').each(function() {
                    const $field = $(this);
                    const email = $field.val().trim();
                    if (email && !AskroAdmin.isValidEmail(email)) {
                        AskroAdmin.showFieldError($field, AskroAdmin.config.strings.invalid_email);
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        },

        // Show field error
        showFieldError: function($field, message) {
            $field.addClass('askro-error');
            const $error = $('<div class="askro-field-error">' + message + '</div>');
            $field.after($error);
        },

        // Validate email
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        // Initialize media uploader
        initMediaUploader: function() {
            $('.askro-media-upload').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $input = $button.siblings('input[type="hidden"]');
                const $preview = $button.siblings('.askro-media-preview');
                
                const mediaUploader = wp.media({
                    title: AskroAdmin.config.strings.select_image,
                    button: {
                        text: AskroAdmin.config.strings.use_image
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.html('<img src="' + attachment.url + '" alt="" style="max-width: 150px;">');
                });

                mediaUploader.open();
            });
        },

        // Show notification
        showNotification: function(message, type = 'info', duration = 5000) {
            const $notification = $(`
                <div class="askro-admin-alert askro-admin-alert-${type}">
                    ${message}
                    <button class="askro-notification-close" style="float: right;">&times;</button>
                </div>
            `);

            $('.askro-admin-container').prepend($notification);
            
            setTimeout(() => {
                $notification.addClass('askro-fade-in');
            }, 100);

            setTimeout(() => {
                $notification.remove();
            }, duration);

            $notification.on('click', '.askro-notification-close', function() {
                $notification.remove();
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        AskroAdmin.init();
    });

})(jQuery);

