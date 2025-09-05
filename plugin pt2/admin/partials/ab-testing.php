<?php
/**
 * A/B Testing template for the plugin admin area
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

// Get active A/B tests
global $wpdb;
$ab_tests_table = $wpdb->prefix . 'scs_ab_tests';

$active_tests = $wpdb->get_results(
    "SELECT t.test_name, t.post_id, COUNT(DISTINCT t.variant) as variants_count, 
            MIN(t.start_time) as start_time, p.post_title
     FROM {$ab_tests_table} t
     JOIN {$wpdb->posts} p ON t.post_id = p.ID
     WHERE t.is_active = 1
     GROUP BY t.test_name, t.post_id, p.post_title
     ORDER BY t.start_time DESC"
);

// Get completed A/B tests
$completed_tests = $wpdb->get_results(
    "SELECT t.test_name, t.post_id, COUNT(DISTINCT t.variant) as variants_count, 
            MIN(t.start_time) as start_time, MAX(t.end_time) as end_time, p.post_title
     FROM {$ab_tests_table} t
     JOIN {$wpdb->posts} p ON t.post_id = p.ID
     WHERE t.is_active = 0 AND t.end_time IS NOT NULL
     GROUP BY t.test_name, t.post_id, p.post_title
     ORDER BY t.end_time DESC"
);
?>

<div class="wrap scs-ab-testing">
    <h1><?php _e('A/B Testing', 'smart-content-scheduler'); ?></h1>
    
    <div class="scs-ab-header">
        <p><?php _e('Create and manage A/B tests to optimize your content performance. Test different headlines, content variations, and calls-to-action to see what resonates best with your audience.', 'smart-content-scheduler'); ?></p>
        
        <div class="scs-ab-actions">
            <a href="#" class="button button-primary" id="scs-new-ab-test"><?php _e('Create New A/B Test', 'smart-content-scheduler'); ?></a>
        </div>
    </div>
    
    <div class="scs-ab-content">
        <h2><?php _e('Active Tests', 'smart-content-scheduler'); ?></h2>
        
        <?php if (empty($active_tests)) : ?>
            <div class="scs-empty-state">
                <p><?php _e('No active A/B tests found.', 'smart-content-scheduler'); ?></p>
                <p><?php _e('Create your first A/B test to start optimizing your content performance.', 'smart-content-scheduler'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Test Name', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Post', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Variants', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Started', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Duration', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Actions', 'smart-content-scheduler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_tests as $test) : ?>
                        <tr>
                            <td><?php echo esc_html($test->test_name); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($test->post_id); ?>">
                                    <?php echo esc_html($test->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($test->variants_count); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($test->start_time)); ?></td>
                            <td>
                                <?php 
                                $start_time = strtotime($test->start_time);
                                $duration = human_time_diff($start_time, time());
                                echo esc_html($duration);
                                ?>
                            </td>
                            <td>
                                <a href="#" class="scs-view-test" data-test-id="<?php echo esc_attr($test->post_id); ?>" data-test-name="<?php echo esc_attr($test->test_name); ?>"><?php _e('View Results', 'smart-content-scheduler'); ?></a> | 
                                <a href="#" class="scs-end-test" data-test-id="<?php echo esc_attr($test->post_id); ?>" data-test-name="<?php echo esc_attr($test->test_name); ?>"><?php _e('End Test', 'smart-content-scheduler'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h2><?php _e('Completed Tests', 'smart-content-scheduler'); ?></h2>
        
        <?php if (empty($completed_tests)) : ?>
            <div class="scs-empty-state">
                <p><?php _e('No completed A/B tests found.', 'smart-content-scheduler'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Test Name', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Post', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Variants', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Start Date', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('End Date', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Actions', 'smart-content-scheduler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_tests as $test) : ?>
                        <tr>
                            <td><?php echo esc_html($test->test_name); ?></td>
                            <td>
                                <a href="<?php echo get_permalink($test->post_id); ?>">
                                    <?php echo esc_html($test->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($test->variants_count); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($test->start_time)); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($test->end_time)); ?></td>
                            <td>
                                <a href="#" class="scs-view-test-results" data-test-id="<?php echo esc_attr($test->post_id); ?>" data-test-name="<?php echo esc_attr($test->test_name); ?>"><?php _e('View Results', 'smart-content-scheduler'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- New A/B Test Modal -->
<div id="scs-ab-test-modal" style="display:none;" class="scs-modal">
    <div class="scs-modal-content">
        <span class="scs-modal-close">&times;</span>
        <h2><?php _e('Create New A/B Test', 'smart-content-scheduler'); ?></h2>
        
        <form id="scs-ab-test-form">
            <div class="scs-form-group">
                <label for="scs-test-name"><?php _e('Test Name', 'smart-content-scheduler'); ?></label>
                <input type="text" id="scs-test-name" name="scs-test-name" required placeholder="<?php _e('e.g., Homepage CTA Test', 'smart-content-scheduler'); ?>">
            </div>
            
            <div class="scs-form-group">
                <label for="scs-post-selection"><?php _e('Select Post', 'smart-content-scheduler'); ?></label>
                <select id="scs-post-selection" name="scs-post-selection" required>
                    <option value=""><?php _e('-- Select Post --', 'smart-content-scheduler'); ?></option>
                    <?php
                    $posts = get_posts([
                        'post_type' => 'post',
                        'post_status' => 'publish',
                        'posts_per_page' => 50,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'meta_query' => [
                            [
                                'key' => '_scs_ab_test_active',
                                'compare' => 'NOT EXISTS',
                            ],
                        ],
                    ]);
                    
                    foreach ($posts as $post) {
                        echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="scs-form-group">
                <label for="scs-test-type"><?php _e('Test Type', 'smart-content-scheduler'); ?></label>
                <select id="scs-test-type" name="scs-test-type" required>
                    <option value="title"><?php _e('Title Variations', 'smart-content-scheduler'); ?></option>
                    <option value="content"><?php _e('Content Variations', 'smart-content-scheduler'); ?></option>
                    <option value="both"><?php _e('Both Title and Content', 'smart-content-scheduler'); ?></option>
                </select>
            </div>
            
            <div class="scs-form-group">
                <label><?php _e('Variants', 'smart-content-scheduler'); ?></label>
                
                <div class="scs-variants-container">
                    <div class="scs-variant" data-variant="A">
                        <h4><?php _e('Variant A (Control)', 'smart-content-scheduler'); ?></h4>
                        
                        <div class="scs-variant-title-field">
                            <label for="scs-variant-a-title"><?php _e('Title', 'smart-content-scheduler'); ?></label>
                            <input type="text" id="scs-variant-a-title" name="scs-variant-a-title" class="scs-variant-title">
                        </div>
                        
                        <div class="scs-variant-content-field">
                            <label for="scs-variant-a-content"><?php _e('Content', 'smart-content-scheduler'); ?></label>
                            <textarea id="scs-variant-a-content" name="scs-variant-a-content" class="scs-variant-content"></textarea>
                        </div>
                    </div>
                    
                    <div class="scs-variant" data-variant="B">
                        <h4><?php _e('Variant B', 'smart-content-scheduler'); ?></h4>
                        
                        <div class="scs-variant-title-field">
                            <label for="scs-variant-b-title"><?php _e('Title', 'smart-content-scheduler'); ?></label>
                            <input type="text" id="scs-variant-b-title" name="scs-variant-b-title" class="scs-variant-title">
                        </div>
                        
                        <div class="scs-variant-content-field">
                            <label for="scs-variant-b-content"><?php _e('Content', 'smart-content-scheduler'); ?></label>
                            <textarea id="scs-variant-b-content" name="scs-variant-b-content" class="scs-variant-content"></textarea>
                        </div>
                    </div>
                </div>
                
                <button type="button" id="scs-add-variant" class="button"><?php _e('Add Another Variant', 'smart-content-scheduler'); ?></button>
            </div>
            
            <div class="scs-form-group">
                <label for="scs-test-duration"><?php _e('Test Duration', 'smart-content-scheduler'); ?></label>
                <select id="scs-test-duration" name="scs-test-duration">
                    <option value="7"><?php _e('7 days', 'smart-content-scheduler'); ?></option>
                    <option value="14"><?php _e('14 days', 'smart-content-scheduler'); ?></option>
                    <option value="30"><?php _e('30 days', 'smart-content-scheduler'); ?></option>
                    <option value="custom"><?php _e('Custom', 'smart-content-scheduler'); ?></option>
                </select>
                <div id="scs-custom-duration" style="display:none; margin-top:10px;">
                    <input type="number" name="scs-custom-days" min="1" max="90" value="7" style="width:80px;"> <?php _e('days', 'smart-content-scheduler'); ?>
                </div>
            </div>
            
            <div class="scs-form-group">
                <label><?php _e('Traffic Split', 'smart-content-scheduler'); ?></label>
                <div class="scs-traffic-split">
                    <div id="scs-traffic-split-slider"></div>
                    <div class="scs-traffic-labels">
                        <span class="scs-variant-a-percent">50%</span>
                        <span class="scs-variant-b-percent">50%</span>
                    </div>
                </div>
            </div>
            
            <div class="scs-form-group">
                <label for="scs-conversion-goal"><?php _e('Conversion Goal', 'smart-content-scheduler'); ?></label>
                <select id="scs-conversion-goal" name="scs-conversion-goal">
                    <option value="pageviews"><?php _e('Page Views', 'smart-content-scheduler'); ?></option>
                    <option value="time_on_page"><?php _e('Time on Page', 'smart-content-scheduler'); ?></option>
                    <option value="scroll_depth"><?php _e('Scroll Depth', 'smart-content-scheduler'); ?></option>
                    <option value="clicks"><?php _e('Link Clicks', 'smart-content-scheduler'); ?></option>
                </select>
            </div>
            
            <div class="scs-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Create A/B Test', 'smart-content-scheduler'); ?></button>
                <button type="button" class="button scs-cancel-modal"><?php _e('Cancel', 'smart-content-scheduler'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- View Test Results Modal -->
<div id="scs-test-results-modal" style="display:none;" class="scs-modal">
    <div class="scs-modal-content scs-modal-large">
        <span class="scs-modal-close">&times;</span>
        <h2><?php _e('A/B Test Results', 'smart-content-scheduler'); ?></h2>
        
        <div id="scs-test-results-content"></div>
        
        <div class="scs-form-actions">
            <button type="button" class="button scs-cancel-modal"><?php _e('Close', 'smart-content-scheduler'); ?></button>
            <button type="button" id="scs-apply-winner" class="button button-primary"><?php _e('Apply Winning Variant', 'smart-content-scheduler'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal functionality for A/B testing
    // Note: Most of the detailed JavaScript functionality is in the separate ab-testing.js file
    // This just handles the basic modal display/hide functionality
    
    // Show the modal when clicking the new test button
    $('#scs-new-ab-test').on('click', function(e) {
        e.preventDefault();
        $('#scs-ab-test-modal').show();
    });
    
    // Close the modal when clicking the X or Cancel button
    $('.scs-modal-close, .scs-cancel-modal').on('click', function() {
        $('.scs-modal').hide();
    });
    
    // Close modal when clicking outside of it
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('scs-modal')) {
            $('.scs-modal').hide();
        }
    });
});
</script>