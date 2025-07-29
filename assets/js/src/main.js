/**
 * AskMe Plugin - Main JavaScript
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

    // AskMe namespace
    window.AskMe = window.AskMe || {};

    // Configuration
    AskMe.config = {
ajaxUrl: askroAjax.ajax_url,
        nonce: askroAjax.nonce,
        userId: askroAjax.user_id || 0,
        isLoggedIn: askroAjax.is_logged_in || false,
        strings: {
            loading: 'جاري التحميل...',
            error: 'حدث خطأ، يرجى المحاولة مرة أخرى',
            success: 'تم بنجاح',
            confirm: 'هل أنت متأكد؟',
            voteSuccess: 'تم التصويت بنجاح',
            voteError: 'فشل في التصويت',
            commentSuccess: 'تم إضافة التعليق بنجاح',
            commentError: 'فشل في إضافة التعليق',
            bestAnswerSuccess: 'تم تحديد أفضل إجابة بنجاح',
            bestAnswerError: 'فشل في تحديد أفضل إجابة'
        }
    };

    // Utility functions
    AskMe.utils = {
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="askme-notification askme-notification-${type}">
                    <div class="askme-notification-content">
                        <span class="askme-notification-message">${message}</span>
                        <button class="askme-notification-close">&times;</button>
                    </div>
                </div>
            `);

            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);

            // Close button
            notification.find('.askme-notification-close').on('click', function() {
                notification.fadeOut(() => notification.remove());
            });
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Format number
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Time ago
         */
        timeAgo: function(date) {
            const now = new Date();
            const past = new Date(date);
            const diffInSeconds = Math.floor((now - past) / 1000);

            if (diffInSeconds < 60) {
                return 'الآن';
            }

            const diffInMinutes = Math.floor(diffInSeconds / 60);
            if (diffInMinutes < 60) {
                return `منذ ${diffInMinutes} دقيقة`;
            }

            const diffInHours = Math.floor(diffInMinutes / 60);
            if (diffInHours < 24) {
                return `منذ ${diffInHours} ساعة`;
            }

            const diffInDays = Math.floor(diffInHours / 24);
            if (diffInDays < 7) {
                return `منذ ${diffInDays} يوم`;
            }

            const diffInWeeks = Math.floor(diffInDays / 7);
            if (diffInWeeks < 4) {
                return `منذ ${diffInWeeks} أسبوع`;
            }

            const diffInMonths = Math.floor(diffInDays / 30);
            if (diffInMonths < 12) {
                return `منذ ${diffInMonths} شهر`;
            }

            const diffInYears = Math.floor(diffInDays / 365);
            return `منذ ${diffInYears} سنة`;
        }
    };

    // AJAX handler
    AskMe.ajax = {
        /**
         * Make AJAX request
         */
        request: function(action, data = {}) {
            const requestData = {
                action: action,
                nonce: AskMe.config.nonce,
                ...data
            };

            return $.ajax({
                url: AskMe.config.ajaxUrl,
                type: 'POST',
                data: requestData,
                dataType: 'json'
            });
        },

        /**
         * Handle AJAX response
         */
        handleResponse: function(response, successCallback, errorCallback) {
            if (response.success) {
                if (successCallback) {
                    successCallback(response.data);
                }
                if (response.data.message) {
                    AskMe.utils.showNotification(response.data.message, 'success');
                }
            } else {
                if (errorCallback) {
                    errorCallback(response.data);
                }
                const message = response.data.message || AskMe.config.strings.error;
                AskMe.utils.showNotification(message, 'error');
            }
        }
    };

    // Voting system
    AskMe.voting = {
        /**
         * Initialize voting
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind voting events
         */
        bindEvents: function() {
            $(document).on('click', '.askme-vote-btn', function(e) {
                e.preventDefault();
                AskMe.voting.handleVote($(this));
            });
        },

        /**
         * Handle vote
         */
        handleVote: function($button) {
            if (!AskMe.config.isLoggedIn) {
                AskMe.utils.showNotification('يجب تسجيل الدخول للتصويت', 'warning');
                return;
            }

            const postId = $button.data('post-id');
            const voteType = $button.data('vote-type');
            const $scoreElement = $button.closest('.askme-voting-section').find('.askme-score-value');

            // Visual feedback
            $button.addClass('loading');
            const originalText = $button.html();
            $button.html('<span class="askme-loading-spinner"></span>');

            AskMe.ajax.request('askro_vote', {
                post_id: postId,
                vote_type: voteType
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    // Update score
                    if ($scoreElement.length) {
                        $scoreElement.text(AskMe.utils.formatNumber(data.new_score));
                    }

                    // Update button state
                    $button.toggleClass('active', data.user_vote === voteType);
                    
                    // Update other vote buttons
                    $button.siblings('.askme-vote-btn').removeClass('active');
                    
                    AskMe.utils.showNotification(AskMe.config.strings.voteSuccess, 'success');
                });
            }).fail(function() {
                AskMe.utils.showNotification(AskMe.config.strings.voteError, 'error');
            }).always(function() {
                $button.removeClass('loading').html(originalText);
            });
        }
    };

    // Comments system
    AskMe.comments = {
        /**
         * Initialize comments
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind comment events
         */
        bindEvents: function() {
            // View comments
            $(document).on('click', '.askme-view-comments-btn', function(e) {
                e.preventDefault();
                AskMe.comments.toggleComments($(this));
            });

            // Submit comment
            $(document).on('submit', '.askro-comment-form', function(e) {
                e.preventDefault();
                AskMe.comments.submitComment($(this));
            });
            
            // Also bind to the class used in the HTML
            $(document).on('submit', '.askme-comment-form', function(e) {
                e.preventDefault();
                AskMe.comments.submitComment($(this));
            });

            // Comment reactions
            $(document).on('click', '.askme-comment-reaction', function(e) {
                e.preventDefault();
                AskMe.comments.handleReaction($(this));
            });

            // Edit comment
            $(document).on('click', '.askme-comment-edit', function(e) {
                e.preventDefault();
                AskMe.comments.editComment($(this));
            });

            // Delete comment
            $(document).on('click', '.askme-comment-delete', function(e) {
                e.preventDefault();
                AskMe.comments.deleteComment($(this));
            });
        },

        /**
         * Toggle comments visibility
         */
        toggleComments: function($button) {
            const answerId = $button.data('answer-id');
            const $container = $(`.askme-comments-section[data-answer-id="${answerId}"]`);
            
            if ($container.is(':visible')) {
                $container.slideUp();
                $button.text('عرض التعليقات');
            } else {
                // Load comments via AJAX if not loaded yet
                if ($container.find('.askme-comments-list').children().length === 0) {
                    this.loadComments(answerId, $container);
                }
                $container.slideDown();
                $button.text('إخفاء التعليقات');
            }
        },

        /**
         * Load comments via AJAX
         */
        loadComments: function(answerId, $container) {
            // Validate input
            if (!answerId || !$container.length) {
                console.log('🔥 DEBUG: Invalid input for loadComments:', { answerId, containerExists: $container.length });
                AskMe.utils.showNotification('بيانات غير صحيحة لتحميل التعليقات', 'error');
                return;
            }
            
            const $loadingIndicator = $('<div class="askme-loading">جاري تحميل التعليقات...</div>');
            $container.find('.askme-comments-list').append($loadingIndicator);
            
            // Get nonce from the comment form in this container
            const $commentForm = $container.find('.askme-comment-form');
            const nonce = $commentForm.find('input[name="nonce"]').val();
            
            if (!nonce) {
                console.log('🔥 DEBUG: No nonce found for loadComments');
                $loadingIndicator.remove();
                AskMe.utils.showNotification('خطأ في التحقق من الأمان', 'error');
                return;
            }
            
            AskMe.ajax.request('askro_load_comments', {
                answer_id: answerId,
                nonce: nonce
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    $loadingIndicator.remove();
                    if (data.comments_html) {
                        $container.find('.askme-comments-list').html(data.comments_html);
                    }
                });
            }).fail(function(xhr, status, error) {
                $loadingIndicator.remove();
                console.log('🔥 DEBUG: Failed to load comments:', xhr, status, error);
                
                let errorMessage = 'فشل في تحميل التعليقات';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                $container.find('.askme-comments-list').html(`<div class="askme-error">${errorMessage}</div>`);
                AskMe.utils.showNotification(errorMessage, 'error');
            });
        },

        /**
         * Submit comment
         */
        submitComment: function($form) {
            console.log('🔥 DEBUG: Comment submission started');
            console.log('🔥 DEBUG: Form element:', $form);
            
            // Validate form element
            if (!$form.length) {
                console.log('🔥 DEBUG: Form element not found');
                AskMe.utils.showNotification('عنصر النموذج غير موجود', 'error');
                return;
            }
            
            const postId = $form.data('post-id');
            const parentId = $form.data('parent-id') || 0;
            const content = $form.find('textarea[name="comment_content"]').val().trim();
            const nonce = $form.find('input[name="askro_comment_nonce"]').val();

            console.log('🔥 DEBUG: Post ID:', postId);
            console.log('🔥 DEBUG: Parent ID:', parentId);
            console.log('🔥 DEBUG: Content length:', content.length);
            console.log('🔥 DEBUG: Nonce exists:', !!nonce);

            // Validate post ID
            if (!postId) {
                console.log('🔥 DEBUG: Post ID validation failed');
                AskMe.utils.showNotification('معرف المنشور غير صحيح', 'error');
                return;
            }

            // Validate content
            if (!content) {
                console.log('🔥 DEBUG: Content validation failed');
                AskMe.utils.showNotification('يرجى كتابة تعليق', 'warning');
                return;
            }

            if (content.length < 3) {
                console.log('🔥 DEBUG: Content too short');
                AskMe.utils.showNotification('التعليق يجب أن يحتوي على 3 أحرف على الأقل', 'warning');
                return;
            }

            if (content.length > 1000) {
                console.log('🔥 DEBUG: Content too long');
                AskMe.utils.showNotification('التعليق طويل جداً. الحد الأقصى 1000 حرف', 'warning');
                return;
            }

            // Validate nonce
            if (!nonce) {
                console.log('🔥 DEBUG: Nonce validation failed');
                AskMe.utils.showNotification('خطأ في التحقق من الأمان', 'error');
                return;
            }

            const $submitBtn = $form.find('.askro-submit-comment-btn, .askme-submit-comment-btn');
            const originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text(AskMe.config.strings.loading);
            console.log('🔥 DEBUG: Submit button disabled, sending AJAX request');

            AskMe.ajax.request('askro_add_comment', {
                post_id: postId,
                parent_id: parentId,
                comment_content: content,
                askro_comment_nonce: nonce
            }).then(function(response) {
                console.log('🔥 DEBUG: AJAX response received:', response);
                AskMe.ajax.handleResponse(response, function(data) {
                    console.log('🔥 DEBUG: Success callback data:', data);
                    // Add comment to DOM
                    const $commentsList = $form.closest('.askme-comments-section').find('.askme-comments-list');
                    console.log('🔥 DEBUG: Comments list element:', $commentsList);
                    $commentsList.append(data.comment_html || data.html);
                    
                    // Clear form
                    $form.find('textarea').val('');
                    console.log('🔥 DEBUG: Form cleared');
                    
                    // Update comment count
                    const $viewBtn = $(`.askme-view-comments-btn[data-post-id="${postId}"]`);
                    if ($viewBtn.length) {
                        const currentText = $viewBtn.text();
                        const countMatch = currentText.match(/\((\d+)\)/);
                        if (countMatch) {
                            const newCount = parseInt(countMatch[1]) + 1;
                            $viewBtn.text(currentText.replace(/\(\d+\)/, `(${newCount})`));
                            console.log('🔥 DEBUG: Comment count updated to:', newCount);
                        }
                    }
                });
            }).fail(function(xhr, status, error) {
                console.log('🔥 DEBUG: AJAX request failed:', xhr, status, error);
                
                let errorMessage = AskMe.config.strings.commentError;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                AskMe.utils.showNotification(errorMessage, 'error');
            }).always(function() {
                console.log('🔥 DEBUG: AJAX request completed, re-enabling button');
                $submitBtn.prop('disabled', false).text(originalText);
            });
        },

        /**
         * Handle comment reaction
         */
        handleReaction: function($button) {
            if (!AskMe.config.isLoggedIn) {
                AskMe.utils.showNotification('يجب تسجيل الدخول للتفاعل', 'warning');
                return;
            }

            const commentId = $button.data('comment-id');
            const reaction = $button.data('reaction');
            const $countElement = $button.find('.askme-reaction-count');

            AskMe.ajax.request('askro_comment_reaction', {
                comment_id: commentId,
                reaction: reaction
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    $countElement.text(data.new_count);
                });
            });
        },

        /**
         * Edit comment
         */
        editComment: function($button) {
            const $comment = $button.closest('.askme-comment');
            const $content = $comment.find('.askme-comment-content');
            const currentText = $content.text().trim();

            const $editForm = $(`
                <div class="askme-comment-edit-form">
                    <textarea class="askme-comment-edit-textarea">${currentText}</textarea>
                    <div class="askme-comment-edit-actions">
                        <button class="askme-comment-save-btn">حفظ</button>
                        <button class="askme-comment-cancel-btn">إلغاء</button>
                    </div>
                </div>
            `);

            $content.hide();
            $content.after($editForm);

            // Save button
            $editForm.find('.askme-comment-save-btn').on('click', function() {
                const newText = $editForm.find('textarea').val().trim();
                if (newText) {
                    AskMe.comments.saveComment($comment, newText);
                }
            });

            // Cancel button
            $editForm.find('.askme-comment-cancel-btn').on('click', function() {
                $editForm.remove();
                $content.show();
            });
        },

        /**
         * Save comment
         */
        saveComment: function($comment, newText) {
            const commentId = $comment.data('comment-id');

            AskMe.ajax.request('askro_edit_comment', {
                comment_id: commentId,
                content: newText
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    $comment.find('.askme-comment-content').text(newText).show();
                    $comment.find('.askme-comment-edit-form').remove();
                });
            });
        },

        /**
         * Delete comment
         */
        deleteComment: function($button) {
            if (!confirm(AskMe.config.strings.confirm)) {
                return;
            }

            const $comment = $button.closest('.askme-comment');
            const commentId = $comment.data('comment-id');

            AskMe.ajax.request('askro_delete_comment', {
                comment_id: commentId
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    $comment.fadeOut(() => $comment.remove());
                });
            });
        }
    };

    // Best answer system
    AskMe.bestAnswer = {
        /**
         * Initialize best answer
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind best answer events
         */
        bindEvents: function() {
            $(document).on('click', '.askme-best-answer-btn', function(e) {
                e.preventDefault();
                AskMe.bestAnswer.markBestAnswer($(this));
            });
        },

        /**
         * Mark best answer
         */
        markBestAnswer: function($button) {
            if (!confirm(AskMe.config.strings.confirm)) {
                return;
            }

            const answerId = $button.data('answer-id');
            const $answer = $button.closest('.askme-answer');

            $button.prop('disabled', true).text(AskMe.config.strings.loading);

            AskMe.ajax.request('askro_mark_best_answer', {
                answer_id: answerId
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    // Move answer to best answer section
                    const $bestAnswerSection = $answer.closest('.askme-answers-section').find('.askme-best-answer');
                    if ($bestAnswerSection.length) {
                        $bestAnswerSection.find('.askme-best-answer-content').html($answer.detach());
                    } else {
                        const newBestAnswerSection = $(`
                            <div class="askme-best-answer">
                                <div class="askme-best-answer-header">
                                    <span class="askme-best-answer-badge">👑 أفضل إجابة</span>
                                </div>
                                <div class="askme-best-answer-content"></div>
                            </div>
                        `);
                        newBestAnswerSection.find('.askme-best-answer-content').append($answer.detach());
                        $answer.closest('.askme-answers-section').prepend(newBestAnswerSection);
                    }

                    // Remove best answer button
                    $button.remove();
                    
                    AskMe.utils.showNotification(AskMe.config.strings.bestAnswerSuccess, 'success');
                });
            }).fail(function() {
                AskMe.utils.showNotification(AskMe.config.strings.bestAnswerError, 'error');
            }).always(function() {
                $button.prop('disabled', false).text('أفضل إجابة');
            });
        }
    };

    // Question status
    AskMe.questionStatus = {
        /**
         * Initialize question status
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind status events
         */
        bindEvents: function() {
            $(document).on('change', '.askme-status-select', function() {
                AskMe.questionStatus.updateStatus($(this));
            });
        },

        /**
         * Update question status
         */
        updateStatus: function($select) {
            const questionId = $select.data('question-id');
            const newStatus = $select.val();

            $select.prop('disabled', true);

            AskMe.ajax.request('askro_update_question_status', {
                question_id: questionId,
                status: newStatus
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    // Update status badge
                    const $badge = $select.closest('.askme-question-header').find('.askme-status-badge');
                    if ($badge.length) {
                        $badge.removeClass().addClass(`askme-status-badge askme-status-${newStatus}`).text(data.status_label);
                    }
                });
            }).fail(function() {
                AskMe.utils.showNotification('فشل في تحديث حالة السؤال', 'error');
            }).always(function() {
                $select.prop('disabled', false);
            });
        }
    };

    // Form validation
    AskMe.initFormValidation = function() {
        // Basic form validation for AskMe forms
        $('.askme-form').on('submit', function(e) {
            const $form = $(this);
            let isValid = true;
            
            // Clear previous errors
            $form.find('.askme-form-error').remove();
            $form.find('.askme-input-error').removeClass('askme-input-error');
            
            // Validate required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('askme-input-error');
                    
                    const errorMsg = $field.data('error-message') || 'هذا الحقل مطلوب';
                    $field.after(`<div class="askme-form-error">${errorMsg}</div>`);
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    isValid = false;
                    $field.addClass('askme-input-error');
                    $field.after('<div class="askme-form-error">يرجى إدخال بريد إلكتروني صحيح</div>');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                AskMe.utils.showNotification('يرجى تصحيح الأخطاء في النموذج', 'error');
            }
        });
    };

    // Search functionality
    AskMe.search = {
        /**
         * Initialize search
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind search events
         */
        bindEvents: function() {
            const $searchInput = $('#askro-search-input');
            if ($searchInput.length) {
                $searchInput.on('input', AskMe.utils.debounce(function() {
                    AskMe.search.performSearch($(this).val());
                }, 500));
            }
        },

        /**
         * Perform search
         */
        performSearch: function(query) {
            if (query.length < 3) {
                $('#askro-search-suggestions').empty().hide();
                return;
            }

            AskMe.ajax.request('askro_search_questions', {
                query: query
            }).then(function(response) {
                AskMe.ajax.handleResponse(response, function(data) {
                    if (data.results && data.results.length > 0) {
                        const suggestionsHtml = data.results.map(function(result) {
                            return `
                                <div class="askme-search-suggestion">
                                    <a href="${result.url}" class="askme-suggestion-link">
                                        <div class="askme-suggestion-title">${result.title}</div>
                                        <div class="askme-suggestion-meta">
                                            ${result.status ? `<span class="askme-suggestion-status">${result.status}</span>` : ''}
                                            <span class="askme-suggestion-answers">${result.answers_count} إجابة</span>
                                        </div>
                                    </a>
                                </div>
                            `;
                        }).join('');

                        $('#askro-search-suggestions').html(suggestionsHtml).show();
                    } else {
                        $('#askro-search-suggestions').html('<div class="askme-no-results">لا توجد نتائج</div>').show();
                    }
                });
            });
        }
    };

    // Answer form handling
    AskMe.answerForm = {
        /**
         * Initialize answer form
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind form events
         */
        bindEvents: function() {
            $(document).on('submit', '#askro-answer-form', function(e) {
                e.preventDefault();
                AskMe.answerForm.handleSubmit($(this));
            });
        },

        /**
         * Handle form submission
         */
        handleSubmit: function($form) {
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('جاري الإرسال...');
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'askro_submit_answer');
            
            // Send AJAX request
            $.ajax({
                url: AskMe.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AskMe.utils.showNotification('تم إضافة الإجابة بنجاح!', 'success');
                        // Add answer dynamically without page reload
                        AskMe.answerForm.addAnswerToPage(response.data);
                        // Reset form
                        $form[0].reset();
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('askro-answer-content')) {
                            tinyMCE.get('askro-answer-content').setContent('');
                        }
                    } else {
                        AskMe.utils.showNotification(response.data || 'حدث خطأ في إضافة الإجابة', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Answer submission error:', error);
                    AskMe.utils.showNotification('حدث خطأ في الاتصال', 'error');
                },
                complete: function() {
                    // Reset button state
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Add new answer to the page dynamically
         */
        addAnswerToPage: function(answerData) {
            const $answersContainer = $('.askme-answers-list');
            if (!$answersContainer.length) return;

            // Create answer HTML
            const answerHtml = this.createAnswerHTML(answerData);
            
            // Add to the beginning of answers list (newest first)
            $answersContainer.prepend(answerHtml);
            
            // Update answer count
            const $countElement = $('.askme-answers-count');
            if ($countElement.length) {
                const currentCount = parseInt($countElement.text()) || 0;
                $countElement.text(currentCount + 1);
            }
            
            // Initialize any new interactive elements
            AskMe.voting.init();
            AskMe.comments.init();
        },

        /**
         * Create HTML for a new answer
         */
        createAnswerHTML: function(answerData) {
            const currentUser = AskMe.config.currentUser || {};
            const isCurrentUser = currentUser.ID == answerData.author_id;
            
            return `
                <div class="askme-answer-card" data-answer-id="${answerData.id}">
                    <div class="askme-answer-content">
                        <div class="askme-answer-header">
                            <div class="askme-answer-author">
                                <img src="${answerData.author_avatar}" alt="${answerData.author_name}" class="askme-avatar">
                                <div class="askme-author-info">
                                    <span class="askme-author-name">${answerData.author_name}</span>
                                    <span class="askme-author-rank">${answerData.author_rank}</span>
                                </div>
                            </div>
                            <div class="askme-answer-meta">
                                <span class="askme-answer-date" data-timestamp="${answerData.date}">الآن</span>
                            </div>
                        </div>
                        <div class="askme-answer-body">
                            ${answerData.content}
                        </div>
                        <div class="askme-answer-actions">
                            <div class="askme-voting-buttons" data-post-id="${answerData.id}" data-post-type="answer">
                                <button class="askme-vote-btn askme-vote-up" data-vote-type="useful" title="مفيد">
                                    <span class="askme-vote-icon">✔️</span>
                                    <span class="askme-vote-count">0</span>
                                </button>
                                <button class="askme-vote-btn askme-vote-down" data-vote-type="incorrect" title="غير صحيح">
                                    <span class="askme-vote-icon">❌</span>
                                    <span class="askme-vote-count">0</span>
                                </button>
                            </div>
                            ${isCurrentUser ? '' : '<button class="askme-mark-best-btn" data-answer-id="' + answerData.id + '">تحديد كأفضل إجابة</button>'}
                        </div>
                    </div>
                </div>
            `;
        }
    };

    // Initialize everything when DOM is ready
    $(document).ready(function() {
        AskMe.voting.init();
        AskMe.comments.init();
        AskMe.bestAnswer.init();
        AskMe.questionStatus.init();
        AskMe.search.init();
        AskMe.answerForm.init();

        // Initialize form validation
        AskMe.initFormValidation();

        // Update timestamps
        $('.askme-question-time, .askme-comment-date').each(function() {
            const $element = $(this);
            const timestamp = $element.data('timestamp') || $element.attr('datetime');
            if (timestamp) {
                $element.text(AskMe.utils.timeAgo(timestamp));
            }
        });

        // Format numbers
        $('.askme-stat-value, .askme-score-value').each(function() {
            const $element = $(this);
            const value = parseInt($element.text());
            if (!isNaN(value)) {
                $element.text(AskMe.utils.formatNumber(value));
            }
        });
    });

})(jQuery);

