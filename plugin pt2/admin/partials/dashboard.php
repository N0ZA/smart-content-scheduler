<?php
/**
 * Dashboard template for the plugin admin area
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

// Get performance data
global $wpdb;
$performance_table = $wpdb->prefix . 'scs_performance';
$schedules_table = $wpdb->prefix . 'scs_schedules';

// Get top performing content
$top_content = $wpdb->get_results(
    "SELECT p.ID, p.post_title, perf.performance_score, perf.views, perf.social_shares 
     FROM {$wpdb->posts} p
     JOIN {$performance_table} perf ON p.ID = perf.post_id
     WHERE p.post_type = 'post' AND p.post_status = 'publish'
     ORDER BY perf.performance_score DESC
     LIMIT 5"
);

// Get upcoming scheduled content
$upcoming_content = $wpdb->get_results(
    "SELECT p.ID, p.post_title, s.scheduled_time, s.ai_confidence
     FROM {$wpdb->posts} p
     JOIN {$schedules_table} s ON p.ID = s.post_id
     WHERE p.post_status IN ('future', 'draft')
     AND s.scheduled_time > NOW()
     ORDER BY s.scheduled_time ASC
     LIMIT 5"
);

// Get content that was rescheduled
$rescheduled_content = $wpdb->get_results(
    "SELECT p.ID, p.post_title, s.scheduled_time, s.original_time
     FROM {$wpdb->posts} p
     JOIN {$schedules_table} s ON p.ID = s.post_id
     WHERE s.is_rescheduled = 1
     ORDER BY s.scheduled_time DESC
     LIMIT 5"
);
?>

<div class="wrap scs-dashboard">
    <h1><?php _e('Smart Content Scheduler Dashboard', 'smart-content-scheduler'); ?></h1>
    
    <div class="scs-dashboard-header">
        <div class="scs-welcome-panel">
            <h2><?php _e('Welcome to Smart Content Scheduler', 'smart-content-scheduler'); ?></h2>
            <p><?php _e('Use AI-powered scheduling to optimize your content performance and engage your audience at the perfect time.', 'smart-content-scheduler'); ?></p>
            
            <div class="scs-quick-actions">
                <a href="<?php echo admin_url('post-new.php'); ?>" class="button button-primary"><?php _e('New Post', 'smart-content-scheduler'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=smart-content-scheduler-calendar'); ?>" class="button"><?php _e('Content Calendar', 'smart-content-scheduler'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=smart-content-scheduler-analytics'); ?>" class="button"><?php _e('Performance Analytics', 'smart-content-scheduler'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="scs-dashboard-widgets">
        <div class="scs-dashboard-widget">
            <h3><?php _e('Performance Overview', 'smart-content-scheduler'); ?></h3>
            <div class="scs-chart-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
        
        <div class="scs-dashboard-widget">
            <h3><?php _e('Publishing Schedule', 'smart-content-scheduler'); ?></h3>
            <div class="scs-calendar-preview">
                <div id="miniCalendar"></div>
            </div>
        </div>
    </div>
    
    <div class="scs-dashboard-tables">
        <div class="scs-dashboard-table">
            <h3><?php _e('Top Performing Content', 'smart-content-scheduler'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Score', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Views', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Social Shares', 'smart-content-scheduler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_content)) : ?>
                        <tr>
                            <td colspan="4"><?php _e('No data available yet.', 'smart-content-scheduler'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($top_content as $post) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $score_class = '';
                                    if ($post->performance_score >= 0.7) $score_class = 'high';
                                    elseif ($post->performance_score >= 0.4) $score_class = 'medium';
                                    else $score_class = 'low';
                                    ?>
                                    <span class="scs-score <?php echo $score_class; ?>">
                                        <?php echo round($post->performance_score * 100); ?>%
                                    </span>
                                </td>
                                <td><?php echo esc_html($post->views); ?></td>
                                <td><?php echo esc_html($post->social_shares); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="scs-dashboard-table">
            <h3><?php _e('Upcoming Scheduled Content', 'smart-content-scheduler'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Schedule', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('AI Confidence', 'smart-content-scheduler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($upcoming_content)) : ?>
                        <tr>
                            <td colspan="3"><?php _e('No upcoming content scheduled.', 'smart-content-scheduler'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($upcoming_content as $post) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->scheduled_time)); ?>
                                </td>
                                <td>
                                    <?php if ($post->ai_confidence > 0) : ?>
                                        <?php
                                        $confidence_class = '';
                                        if ($post->ai_confidence >= 0.7) $confidence_class = 'high';
                                        elseif ($post->ai_confidence >= 0.4) $confidence_class = 'medium';
                                        else $confidence_class = 'low';
                                        ?>
                                        <span class="scs-confidence <?php echo $confidence_class; ?>">
                                            <?php echo round($post->ai_confidence * 100); ?>%
                                        </span>
                                    <?php else : ?>
                                        <span class="scs-manual"><?php _e('Manual', 'smart-content-scheduler'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="scs-dashboard-tables">
        <div class="scs-dashboard-table">
            <h3><?php _e('Recently Rescheduled Content', 'smart-content-scheduler'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('Original Schedule', 'smart-content-scheduler'); ?></th>
                        <th><?php _e('New Schedule', 'smart-content-scheduler'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rescheduled_content)) : ?>
                        <tr>
                            <td colspan="3"><?php _e('No content has been rescheduled yet.', 'smart-content-scheduler'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($rescheduled_content as $post) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->original_time)); ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->scheduled_time)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="scs-dashboard-table">
            <h3><?php _e('AI Insights', 'smart-content-scheduler'); ?></h3>
            <div class="scs-ai-insights">
                <?php
                // Generate some AI insights based on data
                $insights = [];
                
                // Best day insight
                $best_day = $wpdb->get_var(
                    "SELECT DATE_FORMAT(s.scheduled_time, '%W') as day_name
                     FROM {$schedules_table} s
                     JOIN {$performance_table} p ON s.post_id = p.post_id
                     WHERE p.performance_score > 0.5
                     GROUP BY day_name
                     ORDER BY COUNT(*) DESC
                     LIMIT 1"
                );
                
                if ($best_day) {
                    $insights[] = sprintf(
                        __('Your content tends to perform best when published on %s.', 'smart-content-scheduler'),
                        $best_day
                    );
                }
                
                // Best time insight
                $best_hour = $wpdb->get_var(
                    "SELECT DATE_FORMAT(s.scheduled_time, '%H') as hour
                     FROM {$schedules_table} s
                     JOIN {$performance_table} p ON s.post_id = p.post_id
                     WHERE p.performance_score > 0.5
                     GROUP BY hour
                     ORDER BY COUNT(*) DESC
                     LIMIT 1"
                );
                
                if ($best_hour) {
                    $best_time = date('g:i A', strtotime("2000-01-01 {$best_hour}:00:00"));
                    $insights[] = sprintf(
                        __('Content published around %s typically receives higher engagement.', 'smart-content-scheduler'),
                        $best_time
                    );
                }
                
                // Content length insight
                $optimal_length = $wpdb->get_var(
                    "SELECT AVG(LENGTH(p.post_content)) as avg_length
                     FROM {$wpdb->posts} p
                     JOIN {$performance_table} perf ON p.ID = perf.post_id
                     WHERE perf.performance_score > 0.7
                     AND p.post_type = 'post'"
                );
                
                if ($optimal_length) {
                    $optimal_length = round($optimal_length / 1000);
                    if ($optimal_length > 0) {
                        $insights[] = sprintf(
                            __('Your highest-performing content averages around %d thousand characters in length.', 'smart-content-scheduler'),
                            $optimal_length
                        );
                    }
                }
                
                if (empty($insights)) {
                    $insights[] = __('Not enough data collected yet to generate AI insights. Continue publishing content to receive personalized recommendations.', 'smart-content-scheduler');
                }
                ?>
                
                <ul class="scs-insights-list">
                    <?php foreach ($insights as $insight) : ?>
                        <li><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html($insight); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="scs-insight-actions">
                    <a href="<?php echo admin_url('admin.php?page=smart-content-scheduler-analytics'); ?>" class="button"><?php _e('View Full Analytics', 'smart-content-scheduler'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Performance chart
    var ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Sample data - in real implementation, this would come from AJAX
    var performanceData = {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [
            {
                label: 'Engagement',
                data: [65, 59, 80, 81, 56, 55, 40],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.4
            },
            {
                label: 'Views',
                data: [28, 48, 40, 19, 86, 27, 90],
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4
            }
        ]
    };
    
    var performanceChart = new Chart(ctx, {
        type: 'line',
        data: performanceData,
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Last 7 Days Performance'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Mini calendar initialization using flatpickr
    $('#miniCalendar').flatpickr({
        inline: true,
        enable: [
            <?php
            // Get dates with scheduled posts
            $scheduled_dates = $wpdb->get_col(
                "SELECT DATE(scheduled_time) FROM {$schedules_table} 
                 WHERE scheduled_time > NOW() 
                 GROUP BY DATE(scheduled_time)"
            );
            
            if (!empty($scheduled_dates)) {
                echo '"' . implode('", "', $scheduled_dates) . '"';
            }
            ?>
        ],
        dateFormat: 'Y-m-d',
        onChange: function(selectedDates, dateStr, instance) {
            // In a real implementation, this would show posts scheduled for the selected date
            alert('Posts scheduled for ' + dateStr + ' will be shown here');
        }
    });
});
</script>