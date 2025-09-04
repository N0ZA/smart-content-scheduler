/**
 * Smart Content Scheduler Admin JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize the admin UI
    function initializeAdminUI() {
        // Show the AI-powered interface elements
        animateAIElements();
        setupAnalyzeButton();
        setupAISchedule();
        setupAISettings();
    }
    
    // Add animation effects to AI elements
    function animateAIElements() {
        $('.scs-ai-status').addClass('scs-animated');
        
        // Add subtle hover effect to AI buttons
        $('.scs-button').has('.scs-ai-pulse').hover(function() {
            $(this).find('.scs-ai-pulse').css('animation-duration', '0.8s');
        }, function() {
            $(this).find('.scs-ai-pulse').css('animation-duration', '1.5s');
        });
    }
    
    // Set up content analysis button
    function setupAnalyzeButton() {
        $('#scs-analyze-content').on('click', function() {
            const $button = $(this);
            const $aiStatus = $('.scs-ai-status');
            const $aiMessage = $('.scs-ai-message');
            const $resultsSection = $('.scs-analysis-results');
            
            $button.prop('disabled', true);
            $aiMessage.text(scsData.messages.processing);
            
            // Simulate AI processing
            setTimeout(function() {
                $.ajax({
                    url: scsData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'scs_analyze_content',
                        nonce: scsData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $aiMessage.text(scsData.messages.success);
                            
                            // Update results with simulated data (replace with actual data in production)
                            $('#scs-recommended-days').text('Monday, Wednesday, Friday');
                            $('#scs-optimal-time').text('10:00 AM - 11:30 AM');
                            $('#scs-estimated-reach').text('42% higher than average');
                            
                            $resultsSection.slideDown();
                        } else {
                            $aiMessage.text(scsData.messages.error);
                        }
                    },
                    error: function() {
                        $aiMessage.text(scsData.messages.error);
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            }, 2000);
        });
    }
    
    // Set up AI scheduling functionality
    function setupAISchedule() {
        $('#scs-ai-schedule').on('click', function() {
            const $button = $(this);
            const $postSelect = $('#scs-post-selection');
            const postId = $postSelect.val();
            
            if (!postId) {
                alert('Please select a post first.');
                return;
            }
            
            $button.prop('disabled', true);
            
            // Simulate AI processing
            setTimeout(function() {
                // Replace with actual AJAX call to your backend
                const $timeline = $('.scs-schedule-timeline');
                $timeline.html(`
                    <div class="scs-timeline-item">
                        <div class="scs-timeline-date">
                            <span class="scs-day">${getFormattedDate(3)}</span>
                            <span class="scs-time">10:15 AM</span>
                        </div>
                        <div class="scs-timeline-content">
                            <h5>${$postSelect.find('option:selected').text()}</h5>
                            <span class="scs-ai-tag">AI Optimized</span>
                        </div>
                    </div>
                `);
                
                $button.prop('disabled', false);
            }, 1500);
        });
    }
    
    // Set up AI settings functionality
    function setupAISettings() {
        $('#scs-reset-ai-model').on('click', function() {
            if (confirm('Are you sure you want to reset your AI model? This will clear all learned patterns.')) {
                // Simulate reset
                $('.scs-ai-progress-bar').css('width', '0%');
                setTimeout(function() {
                    alert('AI model has been reset successfully.');
                }, 500);
            }
        });
        
        $('#scs-train-ai-model').on('click', function() {
            const $button = $(this);
            $button.prop('disabled', true);
            $button.text('Training in progress...');
            
            // Simulate training
            let progress = 0;
            const interval = setInterval(function() {
                progress += 10;
                $('.scs-ai-progress-bar').css('width', progress + '%');
                
                if (progress >= 100) {
                    clearInterval(interval);
                    $button.prop('disabled', false);
                    $button.html('<span class="scs-ai-pulse"></span>Retrain AI Model');
                    alert('AI model has been successfully trained with your latest content data!');
                }
            }, 500);
        });
    }
    
    // Helper function to get formatted date for demo
    function getFormattedDate(daysAhead) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const date = new Date();
        date.setDate(date.getDate() + daysAhead);
        return days[date.getDay()] + ', ' + (date.getMonth() + 1) + '/' + date.getDate();
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdminUI();
    });
    
})(jQuery);