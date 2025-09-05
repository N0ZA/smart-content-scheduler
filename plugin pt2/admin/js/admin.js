jQuery(document).ready(function($) {
    // Handle modal display
    $('#scs-new-ab-test').on('click', function(e) {
        e.preventDefault();
        $('#scs-ab-test-modal').show();
    });
    
    // Close modal
    $('.scs-modal-close, .scs-cancel-modal').on('click', function() {
        $('.scs-modal').hide();
    });
    
    // Handle custom duration display
    $('#scs-test-duration').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#scs-custom-duration').show();
        } else {
            $('#scs-custom-duration').hide();
        }
    });
    
    // Handle post selection to load current content
    $('#scs-post-selection').on('change', function() {
        var postId = $(this).val();
        if (!postId) return;
        
        // Show loading state
        $(this).prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_get_post_content',
                post_id: postId,
                nonce: scs_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Populate variant A with current content
                    $('#scs-variant-a-title').val(response.data.title);
                    $('#scs-variant-a-content').val(response.data.content);
                }
                $('#scs-post-selection').prop('disabled', false);
            },
            error: function() {
                alert('Error loading post content.');
                $('#scs-post-selection').prop('disabled', false);
            }
        });
    });
    
    // Handle test type selection
    $('#scs-test-type').on('change', function() {
        var testType = $(this).val();
        
        if (testType === 'title') {
            $('.scs-variant-content-field').hide();
            $('.scs-variant-title-field').show();
        } else if (testType === 'content') {
            $('.scs-variant-content-field').show();
            $('.scs-variant-title-field').hide();
        } else {
            $('.scs-variant-content-field, .scs-variant-title-field').show();
        }
    });
    
    // Add variant button
    $('#scs-add-variant').on('click', function() {
        var variantsContainer = $('.scs-variants-container');
        var variantCount = variantsContainer.children('.scs-variant').length;
        
        if (variantCount >= 5) {
            alert('Maximum of 5 variants allowed.');
            return;
        }
        
        // Create new variant with next letter (C, D, E)
        var nextVariantLetter = String.fromCharCode(65 + variantCount); // 65 = ASCII 'A'
        
        var newVariant = $('<div class="scs-variant" data-variant="' + nextVariantLetter + '"></div>');
        newVariant.append('<h4>Variant ' + nextVariantLetter + '</h4>');
        
        // Add title field
        var titleField = $('<div class="scs-variant-title-field"></div>');
        titleField.append('<label for="scs-variant-' + nextVariantLetter.toLowerCase() + '-title">Title</label>');
        titleField.append('<input type="text" id="scs-variant-' + nextVariantLetter.toLowerCase() + '-title" name="scs-variant-' + nextVariantLetter.toLowerCase() + '-title" class="scs-variant-title">');
        newVariant.append(titleField);
        
        // Add content field
        var contentField = $('<div class="scs-variant-content-field"></div>');
        contentField.append('<label for="scs-variant-' + nextVariantLetter.toLowerCase() + '-content">Content</label>');
        contentField.append('<textarea id="scs-variant-' + nextVariantLetter.toLowerCase() + '-content" name="scs-variant-' + nextVariantLetter.toLowerCase() + '-content" class="scs-variant-content"></textarea>');
        newVariant.append(contentField);
        
        // Add remove button
        var removeButton = $('<button type="button" class="button scs-remove-variant">Remove</button>');
        newVariant.append(removeButton);
        
        // Apply current test type
        var testType = $('#scs-test-type').val();
        if (testType === 'title') {
            contentField.hide();
        } else if (testType === 'content') {
            titleField.hide();
        }
        
        // Add to container
        variantsContainer.append(newVariant);
        
        // Update traffic split
        updateTrafficSplit();
    });
    
    // Remove variant button (delegated event)
    $('.scs-variants-container').on('click', '.scs-remove-variant', function() {
        $(this).closest('.scs-variant').remove();
        
        // Re-label variants
        $('.scs-variants-container .scs-variant').each(function(index) {
            var letter = String.fromCharCode(65 + index);
            $(this).attr('data-variant', letter);
            $(this).find('h4').text(index === 0 ? 'Variant A (Control)' : 'Variant ' + letter);
            
            // Update input IDs and names
            var titleInput = $(this).find('.scs-variant-title');
            var contentInput = $(this).find('.scs-variant-content');
            
            titleInput.attr('id', 'scs-variant-' + letter.toLowerCase() + '-title');
            titleInput.attr('name', 'scs-variant-' + letter.toLowerCase() + '-title');
            
            contentInput.attr('id', 'scs-variant-' + letter.toLowerCase() + '-content');
            contentInput.attr('name', 'scs-variant-' + letter.toLowerCase() + '-content');
        });
        
        // Update traffic split
        updateTrafficSplit();
    });
    
    // Initialize traffic split slider
    if($.fn.slider) {
        $('#scs-traffic-split-slider').slider({
            min: 0,
            max: 100,
            value: 50,
            slide: function(event, ui) {
                $('.scs-variant-a-percent').text(ui.value + '%');
                $('.scs-variant-b-percent').text((100 - ui.value) + '%');
                $('#scs-traffic-split-value').val(ui.value);
            }
        });
    }
    
    // Function to update traffic split UI based on number of variants
    function updateTrafficSplit() {
        var variantCount = $('.scs-variants-container .scs-variant').length;
        var splitPercent = Math.floor(100 / variantCount);
        var remainder = 100 % variantCount;
        
        var labels = $('.scs-traffic-labels');
        labels.empty();
        
        // Reset slider if using jQuery UI
        if($.fn.slider && variantCount > 1) {
            $('#scs-traffic-split-slider').slider('destroy');
            
            // Only show slider for 2 variants
            if(variantCount === 2) {
                $('#scs-traffic-split-slider').slider({
                    min: 10,
                    max: 90,
                    value: 50,
                    slide: function(event, ui) {
                        $('.scs-variant-a-percent').text(ui.value + '%');
                        $('.scs-variant-b-percent').text((100 - ui.value) + '%');
                    }
                });
            }
        }
        
        // Add labels for each variant
        $('.scs-variants-container .scs-variant').each(function(index) {
            var letter = $(this).attr('data-variant');
            var percent = splitPercent + (index === 0 ? remainder : 0);
            labels.append('<span class="scs-variant-' + letter.toLowerCase() + '-percent">' + letter + ': ' + percent + '%</span>');
        });
    }
    
    // Form submission
    $('#scs-ab-test-form').on('submit', function(e) {
        e.preventDefault();
        
        var testName = $('#scs-test-name').val();
        var postId = $('#scs-post-selection').val();
        var testType = $('#scs-test-type').val();
        
        if (!testName || !postId) {
            alert('Please enter a test name and select a post.');
            return;
        }
        
        // Collect variant data
        var variants = {};
        $('.scs-variants-container .scs-variant').each(function() {
            var variant = $(this).attr('data-variant');
            var title = $(this).find('.scs-variant-title').val();
            var content = $(this).find('.scs-variant-content').val();
            
            variants[variant] = {
                title: title,
                content: content
            };
        });
        
        // Get duration
        var duration = $('#scs-test-duration').val();
        if (duration === 'custom') {
            duration = $('#scs-custom-days').val();
        }
        
        // Get conversion goal
        var conversionGoal = $('#scs-conversion-goal').val();
        
        // Get traffic split
        var trafficSplit = {};
        if($('.scs-variants-container .scs-variant').length === 2) {
            var variantASplit = parseInt($('#scs-traffic-split-slider').slider('value'));
            trafficSplit = {
                'A': variantASplit,
                'B': 100 - variantASplit
            };
        } else {
            var splitPercent = Math.floor(100 / $('.scs-variants-container .scs-variant').length);
            var remainder = 100 % $('.scs-variants-container .scs-variant').length;
            
            $('.scs-variants-container .scs-variant').each(function(index) {
                var letter = $(this).attr('data-variant');
                trafficSplit[letter] = splitPercent + (index === 0 ? remainder : 0);
            });
        }
        
        // Submit data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_setup_ab_test',
                test_name: testName,
                post_id: postId,
                test_type: testType,
                variants: variants,
                duration: duration,
                conversion_goal: conversionGoal,
                traffic_split: trafficSplit,
                nonce: scs_data.nonce
            },
            beforeSend: function() {
                $('#scs-ab-test-form button[type="submit"]').prop('disabled', true).text('Creating...');
            },
            success: function(response) {
                if (response.success) {
                    alert('A/B test created successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $('#scs-ab-test-form button[type="submit"]').prop('disabled', false).text('Create A/B Test');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#scs-ab-test-form button[type="submit"]').prop('disabled', false).text('Create A/B Test');
            }
        });
    });
    
    // View test results
    $('.scs-view-test, .scs-view-test-results').on('click', function(e) {
        e.preventDefault();
        var testId = $(this).data('test-id');
        var testName = $(this).data('test-name');
        
        // Load test results
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_get_test_results',
                test_id: testId,
                test_name: testName,
                nonce: scs_data.nonce
            },
            beforeSend: function() {
                $('#scs-test-results-content').html('<p>Loading results...</p>');
                $('#scs-test-results-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    $('#scs-test-results-content').html(response.data.html);
                    
                    // Initialize results charts if needed
                    if (response.data.hasCharts && typeof Chart !== 'undefined') {
                        initResultsCharts(response.data.chartData);
                    }
                    
                    // Show/hide apply winner button
                    if (response.data.hasWinner && response.data.isActive) {
                        $('#scs-apply-winner').show().data('winner', response.data.winner).data('test-id', testId);
                    } else {
                        $('#scs-apply-winner').hide();
                    }
                } else {
                    $('#scs-test-results-content').html('<p>Error loading results: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#scs-test-results-content').html('<p>An error occurred while loading results. Please try again.</p>');
            }
        });
    });
    
    // End test
    $('.scs-end-test').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to end this test? This action cannot be undone.')) {
            return;
        }
        
        var testId = $(this).data('test-id');
        var testName = $(this).data('test-name');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_end_ab_test',
                test_id: testId,
                test_name: testName,
                nonce: scs_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Test ended successfully.');
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Apply winning variant
    $('#scs-apply-winner').on('click', function() {
        if (!confirm('Are you sure you want to apply the winning variant? This will update the post with the content from the winning variant.')) {
            return;
        }
        
        var winner = $(this).data('winner');
        var testId = $(this).data('test-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_apply_ab_winner',
                test_id: testId,
                winner: winner,
                nonce: scs_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Winning variant applied successfully.');
                    window.location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Initialize charts for test results
    function initResultsCharts(chartData) {
        // Conversion Rate Chart
        var convCtx = document.getElementById('scs-conversion-chart').getContext('2d');
        new Chart(convCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Conversion Rate',
                    data: chartData.conversionRates,
                    backgroundColor: chartData.colors,
                    borderColor: chartData.borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Traffic Split Chart
        var trafficCtx = document.getElementById('scs-traffic-chart').getContext('2d');
        new Chart(trafficCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.trafficSplit,
                    backgroundColor: chartData.colors,
                    borderColor: chartData.borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
});