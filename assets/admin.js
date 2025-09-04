jQuery(document).ready(function($) {
    'use strict';
    
    // Dashboard functionality
    if ($('.scs-dashboard').length) {
        initDashboard();
    }
    
    // Analytics functionality
    if ($('.scs-analytics').length) {
        initAnalytics();
    }
    
    // Meta box functionality
    if ($('#scs-scheduler').length) {
        initMetaBox();
    }
    
    function initDashboard() {
        // Quick schedule form
        $('#quick-schedule-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'scs_quick_schedule',
                nonce: scs_ajax.nonce,
                post_title: $('input[name="post_title"]').val(),
                post_content: $('textarea[name="post_content"]').val(),
                schedule_type: $('input[name="schedule_type"]:checked').val(),
                custom_datetime: $('input[name="custom_datetime"]').val()
            };
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            
            $submitBtn.prop('disabled', true).val('Scheduling...');
            
            $.post(scs_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    $form[0].reset();
                    refreshDashboardStats();
                } else {
                    showNotice(response.data || 'An error occurred', 'error');
                }
            }).always(function() {
                $submitBtn.prop('disabled', false).val('Schedule Post');
            });
        });
        
        // Refresh optimal times
        $('#refresh-optimal-times').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('Refreshing...');
            
            $.post(scs_ajax.ajax_url, {
                action: 'scs_update_optimal_times',
                nonce: scs_ajax.nonce
            }, function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    location.reload(); // Refresh to show new optimal times
                } else {
                    showNotice(response.data || 'Failed to update optimal times', 'error');
                }
            }).always(function() {
                $btn.prop('disabled', false).text('Refresh Analysis');
            });
        });
        
        // Auto-refresh dashboard stats every 5 minutes
        setInterval(refreshDashboardStats, 300000);
        
        // Schedule type toggle
        $('input[name="schedule_type"]').on('change', function() {
            var customDatetime = $('input[name="custom_datetime"]');
            if ($(this).val() === 'custom') {
                customDatetime.prop('required', true).show();
            } else {
                customDatetime.prop('required', false).hide();
            }
        });
        
        // Initialize schedule type
        $('input[name="schedule_type"]:checked').trigger('change');
    }
    
    function initAnalytics() {
        // Analytics period filter
        $('#update-analytics').on('click', function(e) {
            e.preventDefault();
            
            var period = $('#analytics-period').val();
            var $btn = $(this);
            
            $btn.prop('disabled', true).text('Updating...');
            
            $.post(scs_ajax.ajax_url, {
                action: 'scs_update_analytics',
                nonce: scs_ajax.nonce,
                period: period
            }, function(response) {
                if (response.success) {
                    updateAnalyticsDisplay(response.data);
                    showNotice('Analytics updated successfully', 'success');
                } else {
                    showNotice('Failed to update analytics', 'error');
                }
            }).always(function() {
                $btn.prop('disabled', false).text('Update');
            });
        });
        
        // Reschedule post buttons
        $(document).on('click', '.scs-reschedule', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to reschedule this post?')) {
                return;
            }
            
            var postId = $(this).data('post-id');
            var $btn = $(this);
            
            $btn.prop('disabled', true).text('Rescheduling...');
            
            $.post(scs_ajax.ajax_url, {
                action: 'scs_reschedule_post',
                nonce: scs_ajax.nonce,
                post_id: postId
            }, function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice(response.data || 'Failed to reschedule post', 'error');
                }
            }).always(function() {
                $btn.prop('disabled', false).text('Reschedule');
            });
        });
        
        // Export functionality
        $('#export-analytics').on('click', function(e) {
            e.preventDefault();
            
            var format = $(this).data('format') || 'csv';
            var $btn = $(this);
            
            $btn.prop('disabled', true).text('Exporting...');
            
            $.post(scs_ajax.ajax_url, {
                action: 'scs_export_data',
                nonce: scs_ajax.nonce,
                format: format
            }, function(response) {
                if (response.success) {
                    downloadFile(response.data.data, response.data.filename, response.data.mime_type);
                    showNotice('Export completed successfully', 'success');
                } else {
                    showNotice('Export failed', 'error');
                }
            }).always(function() {
                $btn.prop('disabled', false).text('Export');
            });
        });
        
        // Initialize charts if Chart.js is available
        if (typeof Chart !== 'undefined') {
            initCharts();
        }
    }
    
    function initMetaBox() {
        // Get optimal time suggestion
        $('#scs-scheduler input, #scs-scheduler select').on('change', function() {
            if ($('input[name="scs_use_optimal"]:checked').length) {
                getOptimalSuggestion();
            }
        });
        
        // Initial load
        if ($('input[name="scs_use_optimal"]:checked').length) {
            getOptimalSuggestion();
        }
    }
    
    function getOptimalSuggestion() {
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_optimal_suggestion',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#optimal-time-suggestion').html(response.data.message);
            } else {
                $('#optimal-time-suggestion').html('Unable to calculate optimal time');
            }
        });
    }
    
    function refreshDashboardStats() {
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_dashboard_stats',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#scheduled-count').text(response.data.scheduled_count);
                $('#published-today').text(response.data.published_today);
                $('#avg-performance').text(response.data.avg_performance + '%');
                
                // Update upcoming posts
                var upcomingHtml = '';
                if (response.data.upcoming_posts.length) {
                    upcomingHtml = '<ul class="scs-upcoming-list">';
                    response.data.upcoming_posts.forEach(function(post) {
                        upcomingHtml += '<li><strong>' + post.title + '</strong><br>';
                        upcomingHtml += '<small>Scheduled for: ' + post.scheduled_time + '</small></li>';
                    });
                    upcomingHtml += '</ul>';
                } else {
                    upcomingHtml = '<p>No upcoming posts scheduled.</p>';
                }
                $('#upcoming-posts-list').html(upcomingHtml);
            }
        });
    }
    
    function updateAnalyticsDisplay(data) {
        // Update analytics table
        if (data.daily_performance) {
            var tableHtml = '';
            // This would need to be expanded based on the actual data structure
            $('#analytics-table-body').html(tableHtml);
        }
        
        // Update charts if available
        if (typeof Chart !== 'undefined') {
            updateCharts(data);
        }
    }
    
    function initCharts() {
        // Performance Chart
        var performanceCtx = $('#performance-chart')[0];
        if (performanceCtx) {
            var performanceChart = new Chart(performanceCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Engagement Score',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performance Over Time'
                        }
                    }
                }
            });
        }
        
        // Engagement Chart (Doughnut)
        var engagementCtx = $('#engagement-chart')[0];
        if (engagementCtx) {
            var engagementChart = new Chart(engagementCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Fair', 'Poor'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: ['#46b450', '#00a0d2', '#ffb900', '#dc3232']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Performance Distribution'
                        }
                    }
                }
            });
        }
    }
    
    function updateCharts(data) {
        // Update chart data based on analytics data
        // This would be implemented based on specific chart instances
    }
    
    function showNotice(message, type) {
        type = type || 'info';
        
        var noticeClass = 'scs-notice notice notice-' + type;
        var noticeHtml = '<div class="' + noticeClass + ' is-dismissible">';
        noticeHtml += '<p>' + message + '</p>';
        noticeHtml += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        noticeHtml += '</div>';
        
        // Remove existing notices
        $('.scs-notice').remove();
        
        // Add new notice
        if ($('.wrap h1').length) {
            $('.wrap h1').after(noticeHtml);
        } else {
            $('.wrap').prepend(noticeHtml);
        }
        
        // Auto-dismiss success notices
        if (type === 'success') {
            setTimeout(function() {
                $('.scs-notice').fadeOut();
            }, 3000);
        }
        
        // Handle dismiss button
        $('.notice-dismiss').on('click', function() {
            $(this).parent().fadeOut();
        });
    }
    
    function downloadFile(data, filename, mimeType) {
        var blob = new Blob([data], {type: mimeType});
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // External click tracking (for frontend)
    if (typeof scs_ajax !== 'undefined') {
        $(document).on('click', 'a[href^="http"]:not([href*="' + window.location.hostname + '"])', function() {
            var postId = $('body').data('post-id') || $('article').first().attr('id');
            if (postId) {
                postId = postId.replace('post-', '');
                $.post(scs_ajax.ajax_url, {
                    action: 'scs_track_click',
                    post_id: postId,
                    link_url: $(this).attr('href')
                });
            }
        });
    }
    
    // Social sharing tracking
    $('.scs-share-button').on('click', function(e) {
        var platform = $(this).data('platform');
        var postId = $(this).data('post-id');
        
        if (postId && platform) {
            $.post(scs_ajax.ajax_url, {
                action: 'scs_track_share',
                post_id: postId,
                platform: platform
            });
        }
    });
    
    // Real-time updates for dashboard
    function startRealTimeUpdates() {
        // Check for new scheduled posts every minute
        setInterval(function() {
            refreshDashboardStats();
        }, 60000);
    }
    
    // Initialize real-time updates if on dashboard
    if ($('.scs-dashboard').length) {
        startRealTimeUpdates();
    }
    
    // Advanced scheduling modal (if needed)
    $('#advanced-schedule-modal').on('show', function() {
        // Initialize advanced scheduling options
        initAdvancedScheduling();
    });
    
    function initAdvancedScheduling() {
        // Time zone selection
        $('#timezone-select').on('change', function() {
            updateSchedulePreview();
        });
        
        // Recurring schedule options
        $('#recurring-schedule').on('change', function() {
            if ($(this).is(':checked')) {
                $('.recurring-options').show();
            } else {
                $('.recurring-options').hide();
            }
        });
        
        // A/B testing for post times
        $('#enable-ab-testing').on('change', function() {
            if ($(this).is(':checked')) {
                $('.ab-testing-options').show();
            } else {
                $('.ab-testing-options').hide();
            }
        });
    }
    
    function updateSchedulePreview() {
        var selectedTime = $('#custom-schedule-time').val();
        var timezone = $('#timezone-select').val();
        
        if (selectedTime && timezone) {
            // Convert and display time in different time zones
            var preview = convertTimeToTimezone(selectedTime, timezone);
            $('#schedule-preview').html(preview);
        }
    }
    
    function convertTimeToTimezone(time, timezone) {
        // This would use a library like moment.js or date-fns for proper timezone conversion
        return 'Preview will be shown in ' + timezone;
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + Shift + S for quick schedule
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 83) {
            e.preventDefault();
            $('#quick-schedule-form input[name="post_title"]').focus();
        }
        
        // Ctrl/Cmd + Shift + A for analytics
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 65) {
            e.preventDefault();
            window.location.href = 'admin.php?page=smart-scheduler-analytics';
        }
    });
    
    // Drag and drop for reordering scheduled posts
    if ($('.scs-upcoming-list').length) {
        $('.scs-upcoming-list').sortable({
            update: function(event, ui) {
                var postOrder = $(this).sortable('toArray', {attribute: 'data-post-id'});
                // Save new order via AJAX
                $.post(scs_ajax.ajax_url, {
                    action: 'scs_reorder_posts',
                    nonce: scs_ajax.nonce,
                    post_order: postOrder
                });
            }
        });
    }
    
    // Bulk actions for scheduled posts
    $('#bulk-action-selector').on('change', function() {
        var action = $(this).val();
        var $applyButton = $('#bulk-apply');
        
        if (action === '') {
            $applyButton.prop('disabled', true);
        } else {
            $applyButton.prop('disabled', false);
        }
    });
    
    $('#bulk-apply').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-selector').val();
        var selectedPosts = [];
        
        $('.post-checkbox:checked').each(function() {
            selectedPosts.push($(this).val());
        });
        
        if (selectedPosts.length === 0) {
            alert('Please select at least one post.');
            return;
        }
        
        if (confirm('Are you sure you want to perform this action on ' + selectedPosts.length + ' post(s)?')) {
            performBulkAction(action, selectedPosts);
        }
    });
    
    function performBulkAction(action, postIds) {
        $.post(scs_ajax.ajax_url, {
            action: 'scs_bulk_action',
            nonce: scs_ajax.nonce,
            bulk_action: action,
            post_ids: postIds
        }, function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                location.reload();
            } else {
                showNotice(response.data || 'Bulk action failed', 'error');
            }
        });
    }
    
    // Smart suggestions based on content analysis
    $('#post-content-analyzer').on('click', function(e) {
        e.preventDefault();
        
        var content = $('#post-content').val() || $('textarea[name="post_content"]').val();
        
        if (!content.trim()) {
            alert('Please enter some content to analyze.');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analyzing...');
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_analyze_content',
            nonce: scs_ajax.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                displayContentSuggestions(response.data);
            } else {
                showNotice('Content analysis failed', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Analyze Content');
        });
    });
    
    function displayContentSuggestions(suggestions) {
        var suggestionsHtml = '<div class="scs-content-suggestions">';
        suggestionsHtml += '<h4>Content Optimization Suggestions:</h4>';
        suggestionsHtml += '<ul>';
        
        suggestions.forEach(function(suggestion) {
            suggestionsHtml += '<li>' + suggestion + '</li>';
        });
        
        suggestionsHtml += '</ul></div>';
        
        $('#content-suggestions').html(suggestionsHtml);
    }
    
    // Performance prediction
    $('#predict-performance').on('click', function(e) {
        e.preventDefault();
        
        var postData = {
            title: $('#post-title').val() || $('input[name="post_title"]').val(),
            content: $('#post-content').val() || $('textarea[name="post_content"]').val(),
            scheduled_time: $('#scheduled-time').val() || $('input[name="scs_scheduled_time"]').val()
        };
        
        if (!postData.title || !postData.content) {
            alert('Please fill in title and content for prediction.');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Predicting...');
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_predict_performance',
            nonce: scs_ajax.nonce,
            post_data: postData
        }, function(response) {
            if (response.success) {
                displayPerformancePrediction(response.data);
            } else {
                showNotice('Performance prediction failed', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Predict Performance');
        });
    });
    
    function displayPerformancePrediction(prediction) {
        var predictionHtml = '<div class="scs-performance-prediction">';
        predictionHtml += '<h4>Performance Prediction:</h4>';
        predictionHtml += '<div class="prediction-score">Expected Score: <strong>' + prediction.score + '/100</strong></div>';
        predictionHtml += '<div class="prediction-details">';
        predictionHtml += '<p>Estimated Views: ' + prediction.estimated_views + '</p>';
        predictionHtml += '<p>Estimated Engagement: ' + prediction.estimated_engagement + '%</p>';
        predictionHtml += '<p>Best Time to Post: ' + prediction.optimal_time + '</p>';
        predictionHtml += '</div></div>';
        
        $('#performance-prediction').html(predictionHtml);
    }
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('.scs-tooltip').tooltip();
    }
    
    // Help system
    $('.scs-help-icon').on('click', function(e) {
        e.preventDefault();
        var helpTopic = $(this).data('help-topic');
        showHelpModal(helpTopic);
    });
    
    function showHelpModal(topic) {
        var helpContent = getHelpContent(topic);
        
        var modalHtml = '<div id="scs-help-modal" class="scs-modal">';
        modalHtml += '<div class="scs-modal-content">';
        modalHtml += '<span class="scs-modal-close">&times;</span>';
        modalHtml += '<h2>Help: ' + topic + '</h2>';
        modalHtml += '<div class="scs-help-content">' + helpContent + '</div>';
        modalHtml += '</div></div>';
        
        $('body').append(modalHtml);
        $('#scs-help-modal').show();
        
        $('.scs-modal-close').on('click', function() {
            $('#scs-help-modal').remove();
        });
    }
    
    function getHelpContent(topic) {
        var helpTopics = {
            'optimal-scheduling': 'The Smart Scheduler uses AI to analyze your past post performance and determine the best times to publish content for maximum engagement.',
            'performance-tracking': 'Track views, clicks, shares, and overall engagement scores to understand what content performs best.',
            'auto-rescheduling': 'Posts that underperform can be automatically rescheduled to better time slots for improved visibility.'
        };
        
        return helpTopics[topic] || 'Help content not available for this topic.';
    }
    
    // Enhanced features JavaScript
    
    // ML & NLP functionality
    $('#analyze-content').on('click', function(e) {
        e.preventDefault();
        
        var content = $('#content-to-analyze').val();
        var title = $('#content-title').val();
        
        if (!content.trim()) {
            alert('Please enter content to analyze.');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analyzing...');
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_analyze_content',
            nonce: scs_ajax.nonce,
            content: content,
            title: title
        }, function(response) {
            if (response.success) {
                displayContentAnalysis(response.data);
            } else {
                showNotice('Content analysis failed', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Analyze Content');
        });
    });
    
    $('#extract-keywords').on('click', function(e) {
        e.preventDefault();
        
        var content = $('#content-to-analyze').val();
        var title = $('#content-title').val();
        
        if (!content.trim()) {
            alert('Please enter content to analyze.');
            return;
        }
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_extract_keywords',
            nonce: scs_ajax.nonce,
            content: content,
            title: title,
            limit: 10
        }, function(response) {
            if (response.success) {
                displayKeywords(response.data);
            }
        });
    });
    
    $('#predict-performance').on('click', function(e) {
        e.preventDefault();
        
        var content = $('#content-to-analyze').val();
        var title = $('#content-title').val();
        
        if (!content.trim() || !title.trim()) {
            alert('Please enter both title and content for prediction.');
            return;
        }
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_ml_predict_performance',
            nonce: scs_ajax.nonce,
            content: content,
            title: title,
            scheduled_time: new Date().toISOString()
        }, function(response) {
            if (response.success) {
                displayPerformancePrediction(response.data);
            }
        });
    });
    
    $('#train-ml-model').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Training Model...');
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_ml_train_model',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#training-results').html('<div class="notice notice-success"><p>Model trained successfully! Training samples: ' + response.data.training_samples + ', Accuracy: ' + response.data.model_accuracy + '%</p></div>');
            } else {
                $('#training-results').html('<div class="notice notice-error"><p>Training failed: ' + response.data + '</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Train Model');
        });
    });
    
    // Social Media functionality
    $('.connect-platform').on('click', function(e) {
        e.preventDefault();
        
        var platform = $(this).data('platform');
        var apiKey = prompt('Enter API Key for ' + platform + ':');
        var apiSecret = prompt('Enter API Secret for ' + platform + ':');
        var accessToken = prompt('Enter Access Token for ' + platform + ':');
        
        if (!apiKey || !apiSecret || !accessToken) {
            alert('All fields are required to connect to ' + platform);
            return;
        }
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_connect_social_platform',
            nonce: scs_ajax.nonce,
            platform: platform,
            api_key: apiKey,
            api_secret: apiSecret,
            access_token: accessToken
        }, function(response) {
            if (response.success) {
                showNotice('Successfully connected to ' + platform, 'success');
                location.reload();
            } else {
                showNotice('Failed to connect to ' + platform + ': ' + response.data, 'error');
            }
        });
    });
    
    $('.disconnect-platform').on('click', function(e) {
        e.preventDefault();
        
        var platform = $(this).data('platform');
        
        if (!confirm('Are you sure you want to disconnect from ' + platform + '?')) {
            return;
        }
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_disconnect_social_platform',
            nonce: scs_ajax.nonce,
            platform: platform
        }, function(response) {
            if (response.success) {
                showNotice('Disconnected from ' + platform, 'success');
                location.reload();
            }
        });
    });
    
    $('#sync-social-data').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Syncing...');
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_sync_social_data',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#social-metrics-display').html('<div class="notice notice-success"><p>Synced ' + response.data.synced_posts + ' posts across ' + response.data.synced_platforms.length + ' platforms</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Sync Social Data');
        });
    });
    
    // A/B Testing functionality
    $('#create-ab-test').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_create_ab_test',
            nonce: scs_ajax.nonce,
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function(response) {
            if (response.success) {
                showNotice('A/B test created successfully', 'success');
                $('#create-ab-test')[0].reset();
                loadABTests();
            } else {
                showNotice('Failed to create A/B test: ' + response.data, 'error');
            }
        });
    });
    
    $('#load-ab-tests').on('click', function(e) {
        e.preventDefault();
        loadABTests();
    });
    
    function loadABTests() {
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_ab_test_list',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayABTests(response.data);
            }
        });
    }
    
    // Seasonal Analysis functionality
    $('#get-seasonal-insights').on('click', function(e) {
        e.preventDefault();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_seasonal_insights',
            nonce: scs_ajax.nonce,
            year: new Date().getFullYear()
        }, function(response) {
            if (response.success) {
                displaySeasonalInsights(response.data);
            }
        });
    });
    
    $('#analyze-trends').on('click', function(e) {
        e.preventDefault();
        
        var years = $('#trend-years').val();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_analyze_seasonal_trends',
            nonce: scs_ajax.nonce,
            years: years
        }, function(response) {
            if (response.success) {
                displaySeasonalTrends(response.data);
            }
        });
    });
    
    $('#get-seasonal-recommendations').on('click', function(e) {
        e.preventDefault();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_seasonal_recommendations',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                displaySeasonalRecommendations(response.data);
            }
        });
    });
    
    // Competitor Analysis functionality
    $('#add-competitor').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_add_competitor',
            nonce: scs_ajax.nonce,
            ...Object.fromEntries(new URLSearchParams(formData))
        }, function(response) {
            if (response.success) {
                showNotice('Competitor added successfully', 'success');
                $('#add-competitor')[0].reset();
            } else {
                showNotice('Failed to add competitor: ' + response.data, 'error');
            }
        });
    });
    
    $('#get-competitor-insights').on('click', function(e) {
        e.preventDefault();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_competitor_insights',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayCompetitorInsights(response.data);
            }
        });
    });
    
    $('#compare-performance').on('click', function(e) {
        e.preventDefault();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_compare_performance',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayPerformanceComparison(response.data);
            }
        });
    });
    
    $('#get-content-gaps').on('click', function(e) {
        e.preventDefault();
        
        $.post(scs_ajax.ajax_url, {
            action: 'scs_get_competitor_content_gaps',
            nonce: scs_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayContentGaps(response.data);
            }
        });
    });
    
    // Display functions for new features
    function displayContentAnalysis(analysis) {
        var html = '<div class="scs-analysis-results">';
        html += '<h3>Content Analysis Results</h3>';
        
        // Readability
        html += '<div class="scs-analysis-section">';
        html += '<h4>Readability</h4>';
        html += '<p>Flesch Score: ' + analysis.readability.flesch_score + ' (' + analysis.readability.flesch_level + ')</p>';
        html += '<p>Grade Level: ' + analysis.readability.fk_grade + '</p>';
        html += '<p>Words: ' + analysis.readability.words + ', Sentences: ' + analysis.readability.sentences + '</p>';
        html += '</div>';
        
        // Sentiment
        html += '<div class="scs-analysis-section">';
        html += '<h4>Sentiment Analysis</h4>';
        html += '<p>Score: ' + analysis.sentiment.score + ' (' + analysis.sentiment.label + ')</p>';
        html += '<p>Positive words: ' + analysis.sentiment.positive_words + ', Negative words: ' + analysis.sentiment.negative_words + '</p>';
        html += '</div>';
        
        // SEO Score
        html += '<div class="scs-analysis-section">';
        html += '<h4>SEO Score</h4>';
        html += '<p>Overall SEO Score: ' + analysis.seo_score + '/100</p>';
        html += '</div>';
        
        // Suggestions
        if (analysis.suggestions && analysis.suggestions.length > 0) {
            html += '<div class="scs-analysis-section">';
            html += '<h4>Suggestions</h4>';
            html += '<ul>';
            analysis.suggestions.forEach(function(suggestion) {
                html += '<li>' + suggestion + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#analysis-results').html(html);
    }
    
    function displayKeywords(keywords) {
        var html = '<div class="scs-keywords-results">';
        html += '<h3>Extracted Keywords</h3>';
        html += '<div class="scs-keywords-list">';
        
        keywords.forEach(function(keyword) {
            html += '<span class="scs-keyword-tag">' + keyword.word + ' (' + keyword.frequency + ')</span> ';
        });
        
        html += '</div></div>';
        
        $('#analysis-results').append(html);
    }
    
    function displayPerformancePrediction(prediction) {
        var html = '<div class="scs-prediction-results">';
        html += '<h3>Performance Prediction</h3>';
        html += '<p><strong>Predicted Score:</strong> ' + prediction.predicted_score + '/100</p>';
        html += '<p><strong>Confidence:</strong> ' + prediction.confidence + '%</p>';
        
        if (prediction.recommendations && prediction.recommendations.length > 0) {
            html += '<h4>Recommendations</h4>';
            html += '<ul>';
            prediction.recommendations.forEach(function(rec) {
                html += '<li>' + rec + '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#analysis-results').append(html);
    }
    
    function displayABTests(tests) {
        var html = '<div class="scs-ab-tests-list">';
        
        if (tests.length === 0) {
            html += '<p>No A/B tests found.</p>';
        } else {
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Test Name</th><th>Type</th><th>Status</th><th>Start Date</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            tests.forEach(function(test) {
                html += '<tr>';
                html += '<td>' + test.test_name + '</td>';
                html += '<td>' + test.test_type + '</td>';
                html += '<td>' + test.status + '</td>';
                html += '<td>' + test.start_date + '</td>';
                html += '<td><button class="button view-test-results" data-test-id="' + test.id + '">View Results</button></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        html += '</div>';
        
        $('#ab-tests-display').html(html);
    }
    
    function displaySeasonalInsights(insights) {
        var html = '<div class="scs-seasonal-insights">';
        html += '<h3>Seasonal Insights for ' + insights.year + '</h3>';
        html += '<p><strong>Current Season:</strong> ' + insights.current_season.name + ' (' + insights.current_season.progress + '% complete)</p>';
        
        if (insights.seasonal_performance && insights.seasonal_performance.best_season) {
            html += '<p><strong>Best Performing Season:</strong> ' + insights.seasonal_performance.best_season + '</p>';
            html += '<p><strong>Worst Performing Season:</strong> ' + insights.seasonal_performance.worst_season + '</p>';
        }
        
        html += '</div>';
        
        $('#seasonal-insights-display').html(html);
    }
    
    function displaySeasonalTrends(trends) {
        var html = '<div class="scs-seasonal-trends">';
        html += '<h3>Multi-Year Seasonal Trends</h3>';
        // Add trend visualization here
        html += '<p>Trend analysis completed for ' + Object.keys(trends.trends_by_year).length + ' years.</p>';
        html += '</div>';
        
        $('#seasonal-trends-display').html(html);
    }
    
    function displaySeasonalRecommendations(recommendations) {
        var html = '<div class="scs-seasonal-recommendations">';
        html += '<h3>Seasonal Recommendations</h3>';
        
        if (recommendations.timing) {
            html += '<h4>Timing Recommendations</h4>';
            html += '<ul>';
            recommendations.timing.forEach(function(rec) {
                html += '<li>' + rec + '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#recommendations-display').html(html);
    }
    
    function displayCompetitorInsights(insights) {
        var html = '<div class="scs-competitor-insights">';
        html += '<h3>Competitor Insights</h3>';
        
        if (insights.industry_benchmarks) {
            html += '<h4>Industry Benchmarks</h4>';
            html += '<ul>';
            html += '<li>Average Posting Frequency: ' + insights.industry_benchmarks.avg_posting_frequency + '</li>';
            html += '<li>Average Engagement Rate: ' + insights.industry_benchmarks.avg_engagement_rate + '</li>';
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#competitor-insights-display').html(html);
    }
    
    function displayPerformanceComparison(comparison) {
        var html = '<div class="scs-performance-comparison">';
        html += '<h3>Performance Comparison</h3>';
        
        if (comparison.competitive_advantages && comparison.competitive_advantages.length > 0) {
            html += '<h4>Your Competitive Advantages</h4>';
            html += '<ul>';
            comparison.competitive_advantages.forEach(function(advantage) {
                html += '<li>' + advantage.metric + ': ' + advantage.advantage + ' better than competitors</li>';
            });
            html += '</ul>';
        }
        
        if (comparison.performance_gaps && comparison.performance_gaps.length > 0) {
            html += '<h4>Areas for Improvement</h4>';
            html += '<ul>';
            comparison.performance_gaps.forEach(function(gap) {
                html += '<li>' + gap.metric + ': ' + gap.gap + ' behind competitors</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#competitor-insights-display').html(html);
    }
    
    function displayContentGaps(gaps) {
        var html = '<div class="scs-content-gaps">';
        html += '<h3>Content Gap Analysis</h3>';
        
        if (gaps.topic_gaps) {
            html += '<h4>Topic Gaps</h4>';
            html += '<ul>';
            gaps.topic_gaps.competitor_topics_missing.forEach(function(topic) {
                html += '<li>' + topic + '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#competitor-insights-display').html(html);
    }
});