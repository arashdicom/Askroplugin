/**
 * AskMe Shortcodes JavaScript
 * Handles all interactive components for the new shortcodes
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        AskMeShortcodes.init();
    });

    // Global function to create test answer
    window.createTestAnswer = function(questionId) {
        console.log('Creating test answer for question:', questionId);
        
        $.ajax({
            url: window.askroAjax?.ajax_url || '',
            type: 'POST',
            data: {
                action: 'askro_create_test_answer',
                question_id: questionId,
                nonce: window.askroAjax?.nonce || ''
            },
            success: function(response) {
                console.log('Test answer response:', response);
                if (response.success) {
                    alert('تم إنشاء إجابة تجريبية بنجاح! قم بتحديث الصفحة لرؤيتها.');
                    location.reload();
                } else {
                    alert('خطأ في إنشاء الإجابة التجريبية: ' + response.data.message);
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال');
            }
        });
    };

    // Global function to fix answer links
    window.fixAnswerLinks = function() {
        console.log('Fixing answer links...');
        
        $.ajax({
            url: window.askroAjax?.ajax_url || '',
            type: 'POST',
            data: {
                action: 'askro_fix_answer_links',
                nonce: window.askroAjax?.nonce || ''
            },
            success: function(response) {
                console.log('Fix links response:', response);
                if (response.success) {
                    alert('تم إصلاح روابط الإجابات بنجاح! تم ربط ' + response.data.linked_count + ' إجابة.');
                    location.reload();
                } else {
                    alert('خطأ في إصلاح الروابط: ' + response.data.message);
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال');
            }
        });
    };

    const AskMeShortcodes = {
        init: function() {
            console.log('AskMeShortcodes initializing...');
            console.log('askroAjax data:', window.askroAjax);
            
            this.initChartJS();
            this.initSwiper();
            this.initCropper();
            this.initTagify();
            this.initImageUpload();
            this.initAnimations();
            this.initArchive(); // NEWLY ADDED
            this.initMultiDimensionalVoting(); // NEWLY ADDED
            this.initNestedComments(); // NEWLY ADDED
            this.initAdvancedSearch(); // NEWLY ADDED
            this.initAdvancedFiltering(); // NEWLY ADDED
            this.initSingleQuestionSidebar(); // NEWLY ADDED
            this.initSingleQuestionPage(); // NEWLY ADDED
        },

        /**
         * Initialize Chart.js for user profile and leaderboard
         */
        initChartJS: function() {
            if (typeof Chart === 'undefined') return;

            // User Profile XP Progress Chart
            const xpChartCanvas = document.getElementById('askme-xp-chart');
            if (xpChartCanvas) {
                const ctx = xpChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['XP المكتسبة', 'XP المتبقية'],
                        datasets: [{
                            data: [window.askroAjax?.currentXP || 0, window.askroAjax?.remainingXP || 100],
                            backgroundColor: [
                                '#667eea',
                                '#e2e8f0'
                            ],
                            borderWidth: 0
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
                        cutout: '70%'
                    }
                });
            }

            // User Activity Chart
            const activityChartCanvas = document.getElementById('askme-activity-chart');
            if (activityChartCanvas) {
                const ctx = activityChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: window.askroAjax?.activityLabels || ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                        datasets: [{
                            label: 'النشاط',
                            data: window.askroAjax?.activityData || [12, 19, 3, 5, 2, 3],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Leaderboard Chart
            const leaderboardChartCanvas = document.getElementById('askme-leaderboard-chart');
            if (leaderboardChartCanvas) {
                const ctx = leaderboardChartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: window.askmeData?.leaderboardLabels || ['المستخدم 1', 'المستخدم 2', 'المستخدم 3'],
                        datasets: [{
                            label: 'النقاط',
                            data: window.askmeData?.leaderboardData || [1500, 1200, 900],
                            backgroundColor: '#667eea',
                            borderRadius: 8
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
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        },

        /**
         * Initialize Swiper for image galleries and sliders
         */
        initSwiper: function() {
            if (typeof Swiper === 'undefined') return;

            // Question Images Swiper
            const questionImagesSwiper = document.querySelector('.askme-question-images');
            if (questionImagesSwiper) {
                new Swiper(questionImagesSwiper, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    breakpoints: {
                        768: {
                            slidesPerView: 2,
                        },
                        1024: {
                            slidesPerView: 3,
                        }
                    }
                });
            }

            // User Profile Gallery Swiper
            const profileGallerySwiper = document.querySelector('.askme-profile-gallery');
            if (profileGallerySwiper) {
                new Swiper(profileGallerySwiper, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: true,
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    }
                });
            }
        },

        /**
         * Initialize Cropper.js for image editing
         */
        initCropper: function() {
            if (typeof Cropper === 'undefined') return;

            // Image upload with cropper
            const imageInput = document.getElementById('askme-image-upload');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            AskMeShortcodes.showCropperModal(e.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        },

        /**
         * Show cropper modal for image editing
         */
        showCropperModal: function(imageSrc) {
            const modal = `
                <div id="askme-cropper-modal" class="askme-modal">
                    <div class="askme-modal-content">
                        <div class="askme-modal-header">
                            <h3>تحرير الصورة</h3>
                            <button class="askme-modal-close">&times;</button>
                        </div>
                        <div class="askme-modal-body">
                            <div class="askme-cropper-container">
                                <img id="askme-cropper-image" src="${imageSrc}" alt="صورة للتحرير">
                            </div>
                        </div>
                        <div class="askme-modal-footer">
                            <button class="askme-btn askme-btn-secondary" id="askme-crop-cancel">إلغاء</button>
                            <button class="askme-btn askme-btn-primary" id="askme-crop-save">حفظ</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modal);
            
            const cropperImage = document.getElementById('askme-cropper-image');
            const cropper = new Cropper(cropperImage, {
                aspectRatio: 16 / 9,
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });

            // Handle modal close
            $('#askme-cropper-modal .askme-modal-close, #askme-crop-cancel').on('click', function() {
                cropper.destroy();
                $('#askme-cropper-modal').remove();
            });

            // Handle crop save
            $('#askme-crop-save').on('click', function() {
                const canvas = cropper.getCroppedCanvas();
                const croppedImage = canvas.toDataURL('image/jpeg');
                
                // Create a new file input with the cropped image
                AskMeShortcodes.createCroppedFile(croppedImage);
                
                cropper.destroy();
                $('#askme-cropper-modal').remove();
            });
        },

        /**
         * Create file from cropped image data
         */
        createCroppedFile: function(dataUrl) {
            // Convert data URL to blob
            fetch(dataUrl)
                .then(res => res.blob())
                .then(blob => {
                    const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
                    
                    // Create a new FileList-like object
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    
                    // Update the file input
                    const imageInput = document.getElementById('askme-image-upload');
                    if (imageInput) {
                        imageInput.files = dataTransfer.files;
                        
                        // Show preview
                        AskMeShortcodes.showImagePreview(dataUrl);
                    }
                });
        },

        /**
         * Show image preview after cropping
         */
        showImagePreview: function(imageSrc) {
            const previewContainer = document.getElementById('askme-image-preview');
            if (previewContainer) {
                previewContainer.innerHTML = `
                    <div class="askme-image-preview-item">
                        <img src="${imageSrc}" alt="معاينة الصورة">
                        <button class="askme-remove-image" type="button">&times;</button>
                    </div>
                `;
                previewContainer.style.display = 'block';
            }
        },

        /**
         * Initialize Tagify for tag inputs
         */
        initTagify: function() {
            if (typeof Tagify === 'undefined') return;

            // Question tags input
            const tagsInput = document.querySelector('input[name="question_tags"]');
            if (tagsInput) {
                new Tagify(tagsInput, {
                    whitelist: window.askmeData?.availableTags || [],
                    maxTags: 10,
                    dropdown: {
                        maxItems: 20,
                        classname: "tags-look",
                        enabled: 0,
                        closeOnSelect: false
                    }
                });
            }

            // Category select - Keep as regular select, don't convert to Tagify
            const categorySelect = document.querySelector('select[name="question_category"]');
            if (categorySelect) {
                // Add some basic styling and functionality without Tagify
                categorySelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        // Add visual feedback
                        this.classList.add('askme-category-selected');
                    } else {
                        this.classList.remove('askme-category-selected');
                    }
                });
            }
        },

        /**
         * Initialize image upload functionality
         */
        initImageUpload: function() {
            // Drag and drop functionality
            const uploadArea = document.querySelector('.askme-upload-area');
            if (uploadArea) {
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('askme-drag-over');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('askme-drag-over');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('askme-drag-over');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        AskMeShortcodes.handleFileUpload(files);
                    }
                });
            }

            // File input change
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        AskMeShortcodes.handleFileUpload(e.target.files);
                    }
                });
            }
        },

        /**
         * Handle file upload
         */
        handleFileUpload: function(files) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    // Handle image files with cropper
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        AskMeShortcodes.showCropperModal(e.target.result);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Handle other files
                    AskMeShortcodes.uploadFile(file);
                }
            });
        },

        /**
         * Upload file to server
         */
        uploadFile: function(file) {
            const formData = new FormData();
            formData.append('action', 'askro_upload_file');
            formData.append('file', file);
            formData.append('nonce', window.askmeData?.nonce || '');

            $.ajax({
                url: window.askmeData?.ajax_url || '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AskMeShortcodes.addFilePreview(response.data.url, file.name);
                    } else {
                        console.error('Upload failed:', response.data);
                    }
                },
                error: function() {
                    console.error('Upload failed');
                }
            });
        },

        /**
         * Add file preview
         */
        addFilePreview: function(fileUrl, fileName) {
            const previewContainer = document.getElementById('askme-file-preview');
            if (previewContainer) {
                const previewItem = document.createElement('div');
                previewItem.className = 'askme-file-preview-item';
                previewItem.innerHTML = `
                    <span class="askme-file-name">${fileName}</span>
                    <button class="askme-remove-file" type="button">&times;</button>
                    <input type="hidden" name="uploaded_files[]" value="${fileUrl}">
                `;
                previewContainer.appendChild(previewItem);
            }
        },

        /**
         * Initialize animations
         */
        initAnimations: function() {
            if (typeof anime === 'undefined') return;

            // Animate progress bars
            const progressBars = document.querySelectorAll('.askme-progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                
                anime({
                    targets: bar,
                    width: width,
                    duration: 1500,
                    easing: 'easeOutQuart',
                    delay: 300
                });
            });

            // Animate cards on scroll
            const cards = document.querySelectorAll('.askme-card');
            cards.forEach((card, index) => {
                anime({
                    targets: card,
                    translateY: [50, 0],
                    opacity: [0, 1],
                    duration: 800,
                    easing: 'easeOutQuart',
                    delay: index * 100
                });
            });

            // Animate stats counters
            const counters = document.querySelectorAll('.askme-stat-number');
            counters.forEach(counter => {
                const finalValue = parseInt(counter.textContent);
                counter.textContent = '0';
                
                anime({
                    targets: counter,
                    textContent: [0, finalValue],
                    duration: 2000,
                    easing: 'easeOutQuart',
                    round: 1
                });
            });
        },

        /**
         * Initialize archive page functionality
         */
        initArchive: function() {
            this.initSortTabs();
            this.initFilterModal();
            this.initSearch();
            this.initQuestionCards();
        },

        /**
         * Initialize sort tabs
         */
        initSortTabs: function() {
            $('.askme-tab').on('click', function() {
                const sort = $(this).data('sort');
                
                // Update active tab
                $('.askme-tab').removeClass('active');
                $(this).addClass('active');
                
                // Show loading state
                $('.askme-questions-list').addClass('loading');
                
                // AJAX request to get sorted questions
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_sort_questions',
                        sort_by: sort,
                        sort_order: 'DESC',
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.askme-questions-list').html(response.data.html);
                            $('.askme-questions-list').removeClass('loading');
                        }
                    },
                    error: function() {
                        $('.askme-questions-list').removeClass('loading');
                        alert('حدث خطأ أثناء الفرز. يرجى المحاولة مرة أخرى.');
                    }
                });
            });
        },

        /**
         * Initialize filter modal functionality
         */
        initFilterModal: function() {
            // Open filter modal
            $('#askme-filter-toggle').on('click', function() {
                $('#askme-filter-modal').addClass('active');
            });

            // Close filter modal
            $('.askme-modal-close').on('click', function() {
                $('#askme-filter-modal').removeClass('active');
            });

            // Close modal when clicking outside
            $('#askme-filter-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                }
            });

            // Apply filters
            $('#askme-filter-apply').on('click', function() {
                // Collect all filter values
                const filters = {
                    category: $('select[name="category"]').val() || '',
                    status: $('select[name="status"]').val() || '',
                    tags: $('input[name="tags"]').val() || '',
                    date_range: {
                        start: $('input[name="date_start"]').val() || '',
                        end: $('input[name="date_end"]').val() || ''
                    },
                    vote_range: {
                        min: $('input[name="vote_min"]').val() || '',
                        max: $('input[name="vote_max"]').val() || ''
                    },
                    answer_range: {
                        min: $('input[name="answer_min"]').val() || '',
                        max: $('input[name="answer_max"]').val() || ''
                    },
                    authors: $('select[name="authors"]').val() || [],
                    solved_only: $('input[name="solved_only"]').is(':checked') ? '1' : '',
                    unanswered_only: $('input[name="unanswered_only"]').is(':checked') ? '1' : '',
                    has_attachments: $('input[name="has_attachments"]').is(':checked') ? '1' : ''
                };

                // Show loading state
                $('.askme-questions-list').addClass('loading');
                
                // AJAX request to get filtered questions
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_filter_questions',
                        ...filters,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.askme-questions-list').html(response.data.html);
                            $('.askme-questions-list').removeClass('loading');
                            $('#askme-filter-modal').removeClass('active');
                            
                            // Show results count
                            if (response.data.count !== undefined) {
                                $('.askme-results-count').text(`تم العثور على ${response.data.count} سؤال`);
                            }
                        } else {
                            $('.askme-questions-list').removeClass('loading');
                            alert('حدث خطأ أثناء الفلترة. يرجى المحاولة مرة أخرى.');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('.askme-questions-list').removeClass('loading');
                        console.error('Filter error:', error);
                        alert('حدث خطأ أثناء الفلترة. يرجى المحاولة مرة أخرى.');
                    }
                });
            });

            // Reset filters
            $('#askme-filter-reset').on('click', function() {
                // Reset all form fields
                $('#askme-filter-modal select').val('');
                $('#askme-filter-modal input[type="text"]').val('');
                $('#askme-filter-modal input[type="checkbox"]').prop('checked', false);
                $('#askme-filter-modal input[type="date"]').val('');
                $('#askme-filter-modal input[type="number"]').val('');
            });
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            let searchTimeout;
            
            $('.askme-search-input').on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                
                if (query.length >= 3) {
                    searchTimeout = setTimeout(function() {
                        // Show loading state
                        $('.askme-questions-list').addClass('loading');
                        
                        // AJAX request to search questions
                        $.ajax({
                            url: window.askroAjax?.ajax_url || '',
                            type: 'POST',
                            data: {
                                action: 'askro_search_questions',
                                query: query,
                                nonce: window.askroAjax?.nonce || ''
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.askme-questions-list').html(response.data.html);
                                    $('.askme-questions-list').removeClass('loading');
                                }
                            },
                            error: function() {
                                $('.askme-questions-list').removeClass('loading');
                                alert('حدث خطأ أثناء البحث. يرجى المحاولة مرة أخرى.');
                            }
                        });
                    }, 500);
                } else if (query.length === 0) {
                    // Reset to original questions when search is cleared
                    location.reload();
                }
            });

            // Search button click
            $('.askme-search-btn').on('click', function() {
                const query = $('.askme-search-input').val();
                if (query.length >= 3) {
                    $('.askme-search-input').trigger('input');
                }
            });
        },

        /**
         * Initialize question cards interactions
         */
        initQuestionCards: function() {
            // Hover effects
            $('.askme-question-card').on('mouseenter', function() {
                $(this).addClass('hover');
            }).on('mouseleave', function() {
                $(this).removeClass('hover');
            });

            // Click to expand excerpt
            $('.askme-question-excerpt').on('click', function() {
                $(this).toggleClass('expanded');
            });
        },

        /**
         * Initialize Multi-Dimensional Voting System
         */
        initMultiDimensionalVoting: function() {
            console.log('Initializing multi-dimensional voting...');
            console.log('Found vote buttons:', $('.askme-vote-btn').length);
            
            // Vote buttons for different vote types
            $('.askme-vote-btn').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const postId = $btn.data('post-id');
                const voteType = $btn.data('vote-type');
                const voteValue = $btn.data('vote-value');
                
                if (!window.askroAjax?.current_user) {
                    alert('يجب تسجيل الدخول للتصويت');
                    return;
                }
                
                // Add loading state
                $btn.addClass('loading').prop('disabled', true);
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_cast_vote',
                        post_id: postId,
                        vote_type: voteType,
                        vote_value: voteValue,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                                            // Update vote count
                const $voteCount = $btn.closest('.askme-voting-section').find('.askme-score-value');
                $voteCount.text(response.data.new_count);
                            
                            // Update button state
                            $btn.removeClass('loading').addClass('voted');
                            
                            // Show success message
                            if (response.data.message) {
                                AskMeShortcodes.showNotification(response.data.message, 'success');
                            }
                            
                            // Update total score
                            const $totalScore = $btn.closest('.askme-answer-card').find('.askme-total-score');
                            if ($totalScore.length && response.data.total_score !== undefined) {
                                $totalScore.text(response.data.total_score);
                            }
                        } else {
                            $btn.removeClass('loading');
                            AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ أثناء التصويت', 'error');
                        }
                    },
                    error: function() {
                        $btn.removeClass('loading');
                        AskMeShortcodes.showNotification('حدث خطأ في الاتصال', 'error');
                    }
                });
            });

            // Vote type tooltips - Custom implementation since Bootstrap tooltip is not available
            $('.askme-vote-btn').each(function() {
                const $btn = $(this);
                const tooltipText = $btn.data('tooltip') || $btn.attr('title') || '';
                
                if (tooltipText) {
                    $btn.removeAttr('title'); // Remove default title to prevent double tooltips
                    
                    $btn.on('mouseenter', function() {
                        const $tooltip = $(`
                            <div class="askme-custom-tooltip">
                                ${tooltipText}
                            </div>
                        `);
                        
                        $('body').append($tooltip);
                        
                        const btnOffset = $btn.offset();
                        const btnHeight = $btn.outerHeight();
                        
                        $tooltip.css({
                            position: 'absolute',
                            top: btnOffset.top - $tooltip.outerHeight() - 10,
                            left: btnOffset.left + ($btn.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                            zIndex: 9999
                        });
                        
                        $tooltip.fadeIn(200);
                        $btn.data('tooltip-element', $tooltip);
                    });
                    
                    $btn.on('mouseleave', function() {
                        const $tooltip = $btn.data('tooltip-element');
                        if ($tooltip) {
                            $tooltip.fadeOut(200, function() {
                                $(this).remove();
                            });
                            $btn.removeData('tooltip-element');
                        }
                    });
                }
            });
        },

        /**
         * Initialize Nested Comments System
         */
        initNestedComments: function() {
            console.log('Initializing nested comments...');
            console.log('Found comment buttons:', $('.askme-toggle-comments').length);
            
            // Toggle comments visibility
            $('.askme-toggle-comments').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const $commentsSection = $btn.closest('.askme-answer-card').find('.askme-comments-section');
                const postId = $btn.data('post-id');
                
                if ($commentsSection.hasClass('loaded')) {
                    $commentsSection.slideToggle();
                    $btn.toggleClass('active');
                } else {
                    // Load comments via AJAX
                    $btn.addClass('loading');
                    
                    $.ajax({
                        url: window.askroAjax?.ajax_url || '',
                        type: 'POST',
                        data: {
                            action: 'askro_load_comments',
                            post_id: postId,
                            nonce: window.askroAjax?.nonce || ''
                        },
                        success: function(response) {
                            if (response.success) {
                                $commentsSection.html(response.data.html).addClass('loaded').slideDown();
                                $btn.removeClass('loading').addClass('active');
                                
                                // Initialize comment interactions
                                AskMeShortcodes.initCommentInteractions();
                            }
                        },
                        error: function() {
                            $btn.removeClass('loading');
                            AskMeShortcodes.showNotification('حدث خطأ في تحميل التعليقات', 'error');
                        }
                    });
                }
            });

            // Submit new comment
            $(document).on('submit', '.askme-comment-form', function(e) {
                e.preventDefault();
                
                console.log('Comment form submitted');
                
                const $form = $(this);
                const $submitBtn = $form.find('.askme-submit-comment-btn');
                const postId = $form.data('post-id');
                const parentId = $form.data('parent-id') || 0;
                const commentText = $form.find('textarea[name="comment_content"]').val();
                
                console.log('Comment data:', { postId, parentId, commentText });
                
                if (!commentText.trim()) {
                    console.log('Comment text is empty');
                    return;
                }
                
                $submitBtn.addClass('loading').prop('disabled', true);
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_submit_comment',
                        post_id: postId,
                        parent_id: parentId,
                        comment_text: commentText,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add new comment to the list
                            const $commentsList = $form.closest('.askme-comments-section').find('.askme-comments-list');
                            $commentsList.append(response.data.html);
                            
                            // Clear form
                            $form.find('textarea[name="comment_content"]').val('');
                            
                            // Update comment count
                            const $commentCount = $form.closest('.askme-answer').find('.askme-comment-count');
                            const currentCount = parseInt($commentCount.text()) || 0;
                            $commentCount.text(currentCount + 1);
                            
                            AskMeShortcodes.showNotification('تم إضافة التعليق بنجاح', 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ في إضافة التعليق', 'error');
                        }
                    },
                    error: function() {
                        AskMeShortcodes.showNotification('حدث خطأ في الاتصال', 'error');
                    },
                    complete: function() {
                        $submitBtn.removeClass('loading').prop('disabled', false);
                    }
                });
            });

            // Reply to comment
            $(document).on('click', '.askme-reply-comment', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const $comment = $btn.closest('.askme-comment-item');
                const commentId = $comment.data('comment-id');
                const authorName = $comment.find('.askme-comment-author').text();
                
                // Create reply form
                const replyForm = `
                    <div class="askme-comment-reply-form">
                        <form class="askme-comment-form" data-post-id="${$comment.data('post-id')}" data-parent-id="${commentId}">
                            <div class="askme-form-group">
                                <textarea class="askme-comment-text" placeholder="رد على ${authorName}..." required></textarea>
                            </div>
                            <div class="askme-form-actions">
                                <button type="submit" class="askme-submit-comment">إرسال الرد</button>
                                <button type="button" class="askme-cancel-reply">إلغاء</button>
                            </div>
                        </form>
                    </div>
                `;
                
                // Remove any existing reply forms
                $('.askme-comment-reply-form').remove();
                
                // Add reply form after the comment
                $comment.after(replyForm);
                $comment.find('.askme-comment-reply-form textarea').focus();
            });

            // Cancel reply
            $(document).on('click', '.askme-cancel-reply', function() {
                $(this).closest('.askme-comment-reply-form').remove();
            });

            // Micro-reactions on comments
            $(document).on('click', '.askme-micro-reaction', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const commentId = $btn.data('comment-id');
                const reactionType = $btn.data('reaction-type');
                
                if (!window.askmeData?.user_id) {
                    alert('يجب تسجيل الدخول لإضافة رد الفعل');
                    return;
                }
                
                $btn.toggleClass('active');
                
                $.ajax({
                    url: window.askmeData?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_add_reaction',
                        comment_id: commentId,
                        reaction_type: reactionType,
                        nonce: window.askmeData?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update reaction count
                            const $count = $btn.find('.askme-reaction-count');
                            $count.text(response.data.count);
                        }
                    }
                });
            });
        },

        /**
         * Initialize Advanced Search System
         */
        initAdvancedSearch: function() {
            let searchTimeout;
            let searchHistory = JSON.parse(localStorage.getItem('askro_search_history') || '[]');
            
            // Initialize search suggestions
            this.initSearchSuggestions();
            
            // Live search with debounce
            $('.askme-advanced-search-input').on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        AskMeShortcodes.performAdvancedSearch(query);
                    }, 300);
                } else {
                    $('.askme-search-suggestions').hide();
                    $('.askme-search-results').hide();
                }
            });

            // Search button click
            $('.askme-advanced-search-btn').on('click', function() {
                const query = $('.askme-advanced-search-input').val();
                if (query.trim()) {
                    AskMeShortcodes.performAdvancedSearch(query);
                }
            });

            // Search filters
            $('.askme-search-filter').on('change', function() {
                const query = $('.askme-advanced-search-input').val();
                if (query.trim()) {
                    AskMeShortcodes.performAdvancedSearch(query);
                }
            });

            // Search history
            this.initSearchHistory();
        },

        /**
         * Perform advanced search
         */
        performAdvancedSearch: function(query) {
            const filters = {
                category: $('.askme-search-filter[name="category"]').val(),
                status: $('.askme-search-filter[name="status"]').val(),
                date_range: $('.askme-search-filter[name="date_range"]').val(),
                author: $('.askme-search-filter[name="author"]').val(),
                tags: $('.askme-search-filter[name="tags"]').val()
            };

            // Show loading state
            $('.askme-search-results').addClass('loading').show();
            
            $.ajax({
                url: window.askmeData?.ajax_url || '',
                type: 'POST',
                data: {
                    action: 'askro_advanced_search',
                    query: query,
                    filters: filters,
                    nonce: window.askmeData?.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        $('.askme-search-results').html(response.data.html).removeClass('loading');
                        
                        // Add to search history
                        AskMeShortcodes.addToSearchHistory(query);
                        
                        // Show search statistics
                        if (response.data.stats) {
                            $('.askme-search-stats').html(response.data.stats).show();
                        }
                    } else {
                        $('.askme-search-results').html('<p class="askme-no-results">لا توجد نتائج</p>').removeClass('loading');
                    }
                },
                error: function() {
                    $('.askme-search-results').html('<p class="askme-error">حدث خطأ في البحث</p>').removeClass('loading');
                }
            });
        },

        /**
         * Initialize search suggestions
         */
        initSearchSuggestions: function() {
            $('.askme-advanced-search-input').on('focus', function() {
                const query = $(this).val();
                if (query.length >= 2) {
                    AskMeShortcodes.loadSearchSuggestions(query);
                }
            });

            // Handle suggestion selection
            $(document).on('click', '.askme-search-suggestion', function() {
                const suggestion = $(this).text();
                $('.askme-advanced-search-input').val(suggestion);
                $('.askme-search-suggestions').hide();
                AskMeShortcodes.performAdvancedSearch(suggestion);
            });
        },

        /**
         * Load search suggestions
         */
        loadSearchSuggestions: function(query) {
            $.ajax({
                url: window.askmeData?.ajax_url || '',
                type: 'POST',
                data: {
                    action: 'askro_get_search_suggestions',
                    query: query,
                    nonce: window.askmeData?.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        $('.askme-search-suggestions').html(response.data.suggestions).show();
                    }
                }
            });
        },

        /**
         * Initialize search history
         */
        initSearchHistory: function() {
            const history = JSON.parse(localStorage.getItem('askro_search_history') || '[]');
            
            if (history.length > 0) {
                const historyHtml = history.map(item => 
                    `<div class="askme-search-history-item" data-query="${item}">${item}</div>`
                ).join('');
                
                $('.askme-search-history').html(historyHtml).show();
            }

            // Handle history item click
            $(document).on('click', '.askme-search-history-item', function() {
                const query = $(this).data('query');
                $('.askme-advanced-search-input').val(query);
                AskMeShortcodes.performAdvancedSearch(query);
            });
        },

        /**
         * Add query to search history
         */
        addToSearchHistory: function(query) {
            let history = JSON.parse(localStorage.getItem('askro_search_history') || '[]');
            
            // Remove if exists and add to front
            history = history.filter(item => item !== query);
            history.unshift(query);
            
            // Keep only last 10 items
            history = history.slice(0, 10);
            
            localStorage.setItem('askro_search_history', JSON.stringify(history));
        },

        /**
         * Initialize Advanced Filtering System
         */
        initAdvancedFiltering: function() {
            // Filter modal toggle
            $('.askme-advanced-filter-btn').on('click', function() {
                $('#askme-advanced-filter-modal').addClass('active');
            });

            // Close modal
            $('.askme-modal-close, .askme-modal-overlay').on('click', function() {
                $('#askme-advanced-filter-modal').removeClass('active');
            });

            // Apply filters
            $('.askme-apply-filters').on('click', function() {
                const filters = AskMeShortcodes.collectAdvancedFilters();
                AskMeShortcodes.applyAdvancedFilters(filters);
            });

            // Reset filters
            $('.askme-reset-filters').on('click', function() {
                $('.askme-advanced-filter input, .askme-advanced-filter select').val('');
                $('.askme-advanced-filter input[type="checkbox"]').prop('checked', false);
                $('.askme-advanced-filter input[type="radio"]').prop('checked', false);
            });

            // Filter presets
            $('.askme-filter-preset').on('click', function() {
                const preset = $(this).data('preset');
                AskMeShortcodes.loadFilterPreset(preset);
            });

            // Dynamic filter updates
            $('.askme-advanced-filter select, .askme-advanced-filter input[type="checkbox"]').on('change', function() {
                AskMeShortcodes.updateFilterCounts();
            });

            // Date range picker
            this.initDateRangePicker();
        },

        /**
         * Collect advanced filters
         */
        collectAdvancedFilters: function() {
            const filters = {
                categories: $('.askme-advanced-filter select[name="categories"]').val() || [],
                tags: $('.askme-advanced-filter select[name="tags"]').val() || [],
                status: $('.askme-advanced-filter select[name="status"]').val() || [],
                date_range: {
                    start: $('.askme-advanced-filter input[name="date_start"]').val(),
                    end: $('.askme-advanced-filter input[name="date_end"]').val()
                },
                vote_range: {
                    min: $('.askme-advanced-filter input[name="min_votes"]').val(),
                    max: $('.askme-advanced-filter input[name="max_votes"]').val()
                },
                answer_range: {
                    min: $('.askme-advanced-filter input[name="min_answers"]').val(),
                    max: $('.askme-advanced-filter input[name="max_answers"]').val()
                },
                authors: $('.askme-advanced-filter select[name="authors"]').val() || [],
                solved_only: $('.askme-advanced-filter input[name="solved_only"]').is(':checked'),
                unanswered_only: $('.askme-advanced-filter input[name="unanswered_only"]').is(':checked'),
                has_attachments: $('.askme-advanced-filter input[name="has_attachments"]').is(':checked'),
                sort_by: $('.askme-advanced-filter select[name="sort_by"]').val(),
                sort_order: $('.askme-advanced-filter select[name="sort_order"]').val()
            };

            return filters;
        },

        /**
         * Apply advanced filters
         */
        applyAdvancedFilters: function(filters) {
            // Show loading state
            $('.askme-questions-list').addClass('loading');
            
            $.ajax({
                url: window.askmeData?.ajax_url || '',
                type: 'POST',
                data: {
                    action: 'askro_apply_advanced_filters',
                    filters: filters,
                    nonce: window.askmeData?.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        $('.askme-questions-list').html(response.data.html);
                        
                        // Update URL with filters
                        AskMeShortcodes.updateURLWithFilters(filters);
                        
                        // Show filter summary
                        AskMeShortcodes.showFilterSummary(filters);
                        
                        // Close modal
                        $('#askme-advanced-filter-modal').removeClass('active');
                        
                        AskMeShortcodes.showNotification('تم تطبيق الفلاتر بنجاح', 'success');
                    } else {
                        AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ في تطبيق الفلاتر', 'error');
                    }
                },
                error: function() {
                    AskMeShortcodes.showNotification('حدث خطأ في الاتصال', 'error');
                },
                complete: function() {
                    $('.askme-questions-list').removeClass('loading');
                }
            });
        },

        /**
         * Load filter preset
         */
        loadFilterPreset: function(preset) {
            const presets = {
                'recent': {
                    date_range: { start: '7 days ago', end: 'today' },
                    sort_by: 'date',
                    sort_order: 'desc'
                },
                'popular': {
                    sort_by: 'votes',
                    sort_order: 'desc'
                },
                'unanswered': {
                    unanswered_only: true,
                    sort_by: 'date',
                    sort_order: 'desc'
                },
                'solved': {
                    solved_only: true,
                    sort_by: 'date',
                    sort_order: 'desc'
                }
            };

            if (presets[preset]) {
                const presetData = presets[preset];
                
                // Apply preset values
                Object.keys(presetData).forEach(key => {
                    if (key === 'date_range') {
                        $('input[name="date_start"]').val(presetData[key].start);
                        $('input[name="date_end"]').val(presetData[key].end);
                    } else if (typeof presetData[key] === 'boolean') {
                        $(`input[name="${key}"]`).prop('checked', presetData[key]);
                    } else {
                        $(`select[name="${key}"]`).val(presetData[key]);
                    }
                });
            }
        },

        /**
         * Update filter counts
         */
        updateFilterCounts: function() {
            const activeFilters = $('.askme-advanced-filter select:not([name="sort_by"]):not([name="sort_order"])').filter(function() {
                return $(this).val() && $(this).val().length > 0;
            }).length;
            
            const activeCheckboxes = $('.askme-advanced-filter input[type="checkbox"]:checked').length;
            
            const totalActive = activeFilters + activeCheckboxes;
            
            if (totalActive > 0) {
                $('.askme-filter-count').text(totalActive).show();
            } else {
                $('.askme-filter-count').hide();
            }
        },

        /**
         * Initialize date range picker
         */
        initDateRangePicker: function() {
            // Simple date range picker
            $('.askme-date-range').on('change', function() {
                const range = $(this).val();
                const today = new Date();
                let startDate = new Date();
                
                switch(range) {
                    case 'today':
                        startDate = today;
                        break;
                    case 'week':
                        startDate.setDate(today.getDate() - 7);
                        break;
                    case 'month':
                        startDate.setMonth(today.getMonth() - 1);
                        break;
                    case 'year':
                        startDate.setFullYear(today.getFullYear() - 1);
                        break;
                }
                
                $('input[name="date_start"]').val(startDate.toISOString().split('T')[0]);
                $('input[name="date_end"]').val(today.toISOString().split('T')[0]);
            });
        },

        /**
         * Update URL with filters
         */
        updateURLWithFilters: function(filters) {
            const params = new URLSearchParams(window.location.search);
            
            // Clear existing filter params
            ['categories', 'tags', 'status', 'date_start', 'date_end', 'min_votes', 'max_votes', 'min_answers', 'max_answers', 'authors', 'solved_only', 'unanswered_only', 'has_attachments', 'sort_by', 'sort_order'].forEach(param => {
                params.delete(param);
            });
            
            // Add new filter params
            Object.keys(filters).forEach(key => {
                if (filters[key] && filters[key].length > 0) {
                    if (Array.isArray(filters[key])) {
                        filters[key].forEach(value => {
                            params.append(key, value);
                        });
                    } else if (typeof filters[key] === 'object') {
                        Object.keys(filters[key]).forEach(subKey => {
                            if (filters[key][subKey]) {
                                params.append(`${key}_${subKey}`, filters[key][subKey]);
                            }
                        });
                    } else {
                        params.append(key, filters[key]);
                    }
                }
            });
            
            // Update URL without page reload
            const newURL = window.location.pathname + '?' + params.toString();
            window.history.pushState({}, '', newURL);
        },

        /**
         * Show filter summary
         */
        showFilterSummary: function(filters) {
            const activeFilters = [];
            
            if (filters.categories && filters.categories.length > 0) {
                activeFilters.push(`التصنيفات: ${filters.categories.join(', ')}`);
            }
            if (filters.tags && filters.tags.length > 0) {
                activeFilters.push(`العلامات: ${filters.tags.join(', ')}`);
            }
            if (filters.status && filters.status.length > 0) {
                activeFilters.push(`الحالة: ${filters.status.join(', ')}`);
            }
            if (filters.solved_only) {
                activeFilters.push('المحلولة فقط');
            }
            if (filters.unanswered_only) {
                activeFilters.push('غير المجاب عنها');
            }
            
            if (activeFilters.length > 0) {
                const summaryHtml = `
                    <div class="askme-filter-summary">
                        <span class="askme-filter-summary-title">الفلاتر النشطة:</span>
                        <span class="askme-filter-summary-items">${activeFilters.join(' | ')}</span>
                        <button class="askme-clear-filters">مسح الفلاتر</button>
                    </div>
                `;
                
                $('.askme-filter-summary-container').html(summaryHtml).show();
            } else {
                $('.askme-filter-summary-container').hide();
            }
        },

        /**
         * Initialize comment interactions
         */
        initCommentInteractions: function() {
            // Edit comment
            $('.askme-edit-comment').on('click', function() {
                const $comment = $(this).closest('.askme-comment-item');
                const commentId = $comment.data('comment-id');
                const currentText = $comment.find('.askme-comment-text').text();
                
                const editForm = `
                    <div class="askme-comment-edit-form">
                        <textarea class="askme-comment-edit-text">${currentText}</textarea>
                        <div class="askme-form-actions">
                            <button class="askme-save-edit" data-comment-id="${commentId}">حفظ</button>
                            <button class="askme-cancel-edit">إلغاء</button>
                        </div>
                    </div>
                `;
                
                $comment.find('.askme-comment-content').hide();
                $comment.find('.askme-comment-actions').hide();
                $comment.append(editForm);
            });

            // Save edited comment
            $(document).on('click', '.askme-save-edit', function() {
                const $btn = $(this);
                const commentId = $btn.data('comment-id');
                const newText = $btn.closest('.askme-comment-edit-form').find('.askme-comment-edit-text').val();
                
                $.ajax({
                    url: window.askmeData?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_edit_comment',
                        comment_id: commentId,
                        comment_text: newText,
                        nonce: window.askmeData?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            const $comment = $btn.closest('.askme-comment-item');
                            $comment.find('.askme-comment-text').text(newText);
                            $comment.find('.askme-comment-content').show();
                            $comment.find('.askme-comment-actions').show();
                            $comment.find('.askme-comment-edit-form').remove();
                            
                            AskMeShortcodes.showNotification('تم تحديث التعليق بنجاح', 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ في تحديث التعليق', 'error');
                        }
                    }
                });
            });

            // Cancel edit
            $(document).on('click', '.askme-cancel-edit', function() {
                const $comment = $(this).closest('.askme-comment-item');
                $comment.find('.askme-comment-content').show();
                $comment.find('.askme-comment-actions').show();
                $comment.find('.askme-comment-edit-form').remove();
            });

            // Delete comment
            $('.askme-delete-comment').on('click', function() {
                if (confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
                    const $btn = $(this);
                    const commentId = $btn.data('comment-id');
                    
                    $.ajax({
                        url: window.askroAjax?.ajax_url || '',
                        type: 'POST',
                        data: {
                            action: 'askro_delete_comment',
                            comment_id: commentId,
                            nonce: window.askroAjax?.nonce || ''
                        },
                        success: function(response) {
                            if (response.success) {
                                $btn.closest('.askme-comment-item').fadeOut();
                                AskMeShortcodes.showNotification('تم حذف التعليق بنجاح', 'success');
                            } else {
                                AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ في حذف التعليق', 'error');
                            }
                        }
                    });
                }
            });
        },

        /**
         * Initialize single question sidebar functionality
         */
        initSingleQuestionSidebar: function() {
            // Copy link functionality
            $('.askme-copy-link').on('click', function() {
                const url = $(this).data('url');
                navigator.clipboard.writeText(url).then(function() {
                    AskMeShortcodes.showNotification('تم نسخ الرابط بنجاح', 'success');
                }).catch(function() {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    AskMeShortcodes.showNotification('تم نسخ الرابط بنجاح', 'success');
                });
            });

            // Report question functionality
            $('.askme-report-btn').on('click', function() {
                const questionId = $(this).data('question-id');
                AskMeShortcodes.showReportModal(questionId);
            });
        },

        /**
         * Show report modal
         */
        showReportModal: function(questionId) {
            const modal = `
                <div class="askme-modal" id="askme-report-modal">
                    <div class="askme-modal-content">
                        <div class="askme-modal-header">
                            <h3>الإبلاغ عن مشكلة</h3>
                            <button class="askme-modal-close">&times;</button>
                        </div>
                        <div class="askme-modal-body">
                            <form class="askme-report-form">
                                <div class="askme-form-group">
                                    <label>نوع المشكلة</label>
                                    <select name="report_type" required>
                                        <option value="">اختر نوع المشكلة</option>
                                        <option value="spam">محتوى غير مرغوب</option>
                                        <option value="inappropriate">محتوى غير لائق</option>
                                        <option value="duplicate">سؤال مكرر</option>
                                        <option value="offensive">محتوى مسيء</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                                <div class="askme-form-group">
                                    <label>تفاصيل المشكلة</label>
                                    <textarea name="report_description" rows="4" placeholder="اشرح المشكلة بالتفصيل..." required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="askme-modal-footer">
                            <button class="askme-modal-cancel">إلغاء</button>
                            <button class="askme-modal-submit">إرسال البلاغ</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            $('#askme-report-modal').fadeIn();
            
            // Close modal
            $('.askme-modal-close, .askme-modal-cancel').on('click', function() {
                $('#askme-report-modal').fadeOut(function() {
                    $(this).remove();
                });
            });
            
            // Submit report
            $('.askme-modal-submit').on('click', function() {
                const $form = $('.askme-report-form');
                const formData = {
                    action: 'askro_report_question',
                    question_id: questionId,
                    report_type: $form.find('select[name="report_type"]').val(),
                    report_description: $form.find('textarea[name="report_description"]').val(),
                    nonce: window.askmeData?.nonce || ''
                };
                
                if (!formData.report_type || !formData.report_description) {
                    AskMeShortcodes.showNotification('يرجى ملء جميع الحقول المطلوبة', 'error');
                    return;
                }
                
                $.ajax({
                    url: window.askmeData?.ajax_url || '',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            AskMeShortcodes.showNotification('تم إرسال البلاغ بنجاح', 'success');
                            $('#askme-report-modal').fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            AskMeShortcodes.showNotification(response.data.message || 'حدث خطأ في إرسال البلاغ', 'error');
                        }
                    }
                });
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const notification = `
                <div class="askme-notification askme-notification-${type}">
                    <span class="askme-notification-message">${message}</span>
                    <button class="askme-notification-close">&times;</button>
                </div>
            `;
            
            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                $('.askme-notification').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $(document).on('click', '.askme-notification-close', function() {
                $(this).closest('.askme-notification').fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Initialize single question page functionality
         */
        initSingleQuestionPage: function() {
            // Initialize voting system
            this.initVotingSystem();
            
            // Initialize comments system
            this.initCommentsSystem();
            
            // Initialize status selector
            this.initStatusSelector();
            
            // Initialize micro reactions
            this.initMicroReactions();
            
            // Initialize action buttons
            this.initActionButtons();
        },

        /**
         * Initialize voting system for answers
         */
        initVotingSystem: function() {
            $('.askme-vote-btn').on('click', function() {
                const $btn = $(this);
                const answerId = $btn.data('answer-id');
                const voteType = $btn.data('vote-type');
                
                if ($btn.hasClass('loading')) {
                    return;
                }
                
                $btn.addClass('loading');
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_cast_vote',
                        post_id: answerId,
                        vote_type: voteType,
                        vote_value: $btn.data('vote-value') || 1,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update vote count
                            $btn.find('.askme-vote-count').text(response.data.vote_count);
                            
                            // Update total score
                            $btn.closest('.askme-answer').find('.askme-total-score').text(response.data.total_score);
                            
                            // Update button state
                            $btn.toggleClass('voted', response.data.user_voted);
                            
                            // Show notification
                            AskMeShortcodes.showNotification(response.data.message, 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        AskMeShortcodes.showNotification('حدث خطأ أثناء التصويت', 'error');
                    },
                    complete: function() {
                        $btn.removeClass('loading');
                    }
                });
            });
        },

        /**
         * Initialize comments system
         */
        initCommentsSystem: function() {
            // Toggle comments visibility
            $('.askme-toggle-comments').on('click', function() {
                const $btn = $(this);
                const $commentsList = $btn.siblings('.askme-comments-list');
                const answerId = $btn.data('answer-id');
                
                if ($btn.hasClass('loading')) {
                    return;
                }
                
                if ($commentsList.is(':visible')) {
                    $commentsList.slideUp();
                    $btn.removeClass('active');
                } else {
                    $btn.addClass('loading active');
                    
                    // Load comments if not loaded yet
                    if ($commentsList.children().length === 0) {
                        $.ajax({
                            url: window.askroAjax?.ajax_url || '',
                            type: 'POST',
                            data: {
                                action: 'askro_load_comments',
                                answer_id: answerId,
                                nonce: window.askroAjax?.nonce || ''
                            },
                            success: function(response) {
                                if (response.success) {
                                    $commentsList.html(response.data.html);
                                    $commentsList.slideDown();
                                    
                                    // Initialize comment actions
                                    AskMeShortcodes.initCommentActions();
                                } else {
                                    AskMeShortcodes.showNotification(response.data.message, 'error');
                                }
                            },
                            error: function() {
                                AskMeShortcodes.showNotification('حدث خطأ أثناء تحميل التعليقات', 'error');
                            },
                            complete: function() {
                                $btn.removeClass('loading');
                            }
                        });
                    } else {
                        $commentsList.slideDown();
                        $btn.removeClass('loading');
                    }
                }
            });
            
            // Submit comment
            $(document).on('submit', '.askme-comment-form', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('.askme-submit-comment');
                const $textarea = $form.find('.askme-comment-text');
                const answerId = $form.data('answer-id');
                const commentText = $textarea.val().trim();
                
                if (!commentText) {
                    AskMeShortcodes.showNotification('يرجى كتابة تعليق', 'error');
                    return;
                }
                
                if ($submitBtn.hasClass('loading')) {
                    return;
                }
                
                $submitBtn.addClass('loading');
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_submit_comment',
                        answer_id: answerId,
                        comment_text: commentText,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add new comment to list
                            const $commentsList = $form.closest('.askme-comments-list');
                            $commentsList.append(response.data.html);
                            
                            // Clear form
                            $textarea.val('');
                            
                            // Initialize comment actions for new comment
                            AskMeShortcodes.initCommentActions();
                            
                            AskMeShortcodes.showNotification('تم إضافة التعليق بنجاح', 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        AskMeShortcodes.showNotification('حدث خطأ أثناء إضافة التعليق', 'error');
                    },
                    complete: function() {
                        $submitBtn.removeClass('loading');
                    }
                });
            });
            
            // Cancel reply
            $(document).on('click', '.askme-cancel-reply', function() {
                const $form = $(this).closest('.askme-comment-reply-form');
                $form.slideUp();
            });
        },

        /**
         * Initialize comment actions
         */
        initCommentActions: function() {
            // Reply to comment
            $('.askme-reply-comment').on('click', function() {
                const $comment = $(this).closest('.askme-comment-item');
                const $replyForm = $comment.find('.askme-comment-reply-form');
                
                if ($replyForm.is(':visible')) {
                    $replyForm.slideUp();
                } else {
                    $replyForm.slideDown();
                    $replyForm.find('.askme-comment-text').focus();
                }
            });
            
            // Delete comment
            $('.askme-delete-comment').on('click', function() {
                const $comment = $(this).closest('.askme-comment-item');
                const commentId = $comment.data('comment-id');
                
                if (confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
                    $.ajax({
                        url: window.askroAjax?.ajax_url || '',
                        type: 'POST',
                        data: {
                            action: 'askro_delete_comment',
                            comment_id: commentId,
                            nonce: window.askroAjax?.nonce || ''
                        },
                        success: function(response) {
                            if (response.success) {
                                $comment.fadeOut();
                                AskMeShortcodes.showNotification('تم حذف التعليق بنجاح', 'success');
                            } else {
                                AskMeShortcodes.showNotification(response.data.message, 'error');
                            }
                        },
                        error: function() {
                            AskMeShortcodes.showNotification('حدث خطأ أثناء حذف التعليق', 'error');
                        }
                    });
                }
            });
        },

        /**
         * Initialize status selector
         */
        initStatusSelector: function() {
            $('.askme-status-select').on('change', function() {
                const $select = $(this);
                const questionId = $select.data('question-id');
                const newStatus = $select.val();
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_update_question_status',
                        question_id: questionId,
                        status: newStatus,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            AskMeShortcodes.showNotification('تم تحديث حالة السؤال بنجاح', 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        AskMeShortcodes.showNotification('حدث خطأ أثناء تحديث حالة السؤال', 'error');
                    }
                });
            });
        },

        /**
         * Initialize micro reactions
         */
        initMicroReactions: function() {
            $('.askme-micro-reaction').on('click', function() {
                const $reaction = $(this);
                const commentId = $reaction.data('comment-id');
                const reactionType = $reaction.data('reaction-type');
                
                $.ajax({
                    url: window.askroAjax?.ajax_url || '',
                    type: 'POST',
                    data: {
                        action: 'askro_comment_reaction',
                        comment_id: commentId,
                        reaction_type: reactionType,
                        nonce: window.askroAjax?.nonce || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update reaction count
                            $reaction.find('.askme-reaction-count').text(response.data.count);
                            
                            // Toggle active state
                            $reaction.toggleClass('active', response.data.user_reacted);
                            
                            AskMeShortcodes.showNotification(response.data.message, 'success');
                        } else {
                            AskMeShortcodes.showNotification(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        AskMeShortcodes.showNotification('حدث خطأ أثناء إضافة التفاعل', 'error');
                    }
                });
            });
        },

        /**
         * Initialize action buttons
         */
        initActionButtons: function() {
            // Share button
            $('.askme-share-btn').on('click', function() {
                const url = $(this).data('url');
                const title = $(this).data('title');
                
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    });
                } else {
                    // Fallback: copy to clipboard
                    navigator.clipboard.writeText(url).then(function() {
                        AskMeShortcodes.showNotification('تم نسخ الرابط بنجاح', 'success');
                    }).catch(function() {
                        // Fallback for older browsers
                        const textArea = document.createElement('textarea');
                        textArea.value = url;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        AskMeShortcodes.showNotification('تم نسخ الرابط بنجاح', 'success');
                    });
                }
            });
            
            // Report button
            $('.askme-report-btn').on('click', function() {
                const $btn = $(this);
                const answerId = $btn.data('answer-id');
                const reason = prompt('يرجى كتابة سبب الإبلاغ:');
                
                if (reason && reason.trim()) {
                    $.ajax({
                        url: window.askroAjax?.ajax_url || '',
                        type: 'POST',
                        data: {
                            action: 'askro_report_question',
                            answer_id: answerId,
                            reason: reason.trim(),
                            nonce: window.askroAjax?.nonce || ''
                        },
                        success: function(response) {
                            if (response.success) {
                                AskMeShortcodes.showNotification('تم إرسال البلاغ بنجاح', 'success');
                            } else {
                                AskMeShortcodes.showNotification(response.data.message, 'error');
                            }
                        },
                        error: function() {
                            AskMeShortcodes.showNotification('حدث خطأ أثناء إرسال البلاغ', 'error');
                        }
                    });
                }
            });
            
            // Mark as best answer
            $('.askme-mark-best').on('click', function() {
                const $btn = $(this);
                const answerId = $btn.data('answer-id');
                
                if (confirm('هل أنت متأكد من تحديد هذا الجواب كأفضل إجابة؟')) {
                    $.ajax({
                        url: window.askroAjax?.ajax_url || '',
                        type: 'POST',
                        data: {
                            action: 'askro_mark_best_answer',
                            answer_id: answerId,
                            nonce: window.askroAjax?.nonce || ''
                        },
                        success: function(response) {
                            if (response.success) {
                                // Reload page to show best answer
                                location.reload();
                            } else {
                                AskMeShortcodes.showNotification(response.data.message, 'error');
                            }
                        },
                        error: function() {
                            AskMeShortcodes.showNotification('حدث خطأ أثناء تحديد أفضل إجابة', 'error');
                        }
                    });
                }
            });
        }
    };

})(jQuery); 
