# Smart Content Scheduler Pro

## Overview

Smart Content Scheduler Pro is a powerful WordPress plugin that uses AI-powered analytics to optimize your content publishing schedule. The plugin automatically determines the best times to publish your content based on historical performance data, tracks engagement metrics, and can automatically reschedule underperforming posts.

## Features

- **AI-Powered Optimal Scheduling**: Automatically calculates the best times to publish content
- **Performance Tracking**: Track views, clicks, shares, and engagement scores
- **Auto-Rescheduling**: Automatically reschedule underperforming posts
- **Analytics Dashboard**: Comprehensive analytics with charts and performance insights
- **Quick Scheduling**: Fast content scheduling with optimal time suggestions
- **Bulk Actions**: Manage multiple scheduled posts at once
- **Export Analytics**: Export performance data in CSV or JSON format
- **Real-time Updates**: Dashboard updates in real-time

## Installation Instructions

### 1. Plugin Structure
Create the following folder structure in your WordPress `wp-content/plugins/` directory:

```
smart-content-scheduler/
├── smart-content-scheduler.php (main plugin file)
├── includes/
│   ├── admin-menu.php
│   ├── scheduler.php
│   ├── analytics.php
│   └── ajax-handlers.php
├── assets/
│   ├── admin.css
│   └── admin.js
├── languages/ (for translations)
└── README.md
```

### 2. File Setup

1. **Main Plugin File**: Copy the main plugin code into `smart-content-scheduler.php`
2. **Include Files**: Place all the include files in the `includes/` directory
3. **Assets**: Place CSS and JS files in the `assets/` directory
4. **Create Languages Directory**: For future translation support

### 3. Database Setup

The plugin will automatically create the required database table when activated:

```sql
CREATE TABLE wp_scs_analytics (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    scheduled_time datetime NOT NULL,
    published_time datetime DEFAULT NULL,
    views int(11) DEFAULT 0,
    clicks int(11) DEFAULT 0,
    shares int(11) DEFAULT 0,
    engagement_score float DEFAULT 0,
    performance_rating varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY post_id (post_id)
);
```

### 4. WordPress Installation

1. **Upload Plugin**: Upload the entire `smart-content-scheduler` folder to `/wp-content/plugins/`
2. **Activate Plugin**: Go to WordPress Admin → Plugins → Activate "Smart Content Scheduler Pro"
3. **Set Permissions**: Ensure the plugin directory has proper write permissions

### 5. Configuration

After activation:

1. **Access Dashboard**: Go to WordPress Admin → Smart Scheduler
2. **Configure Settings**: Navigate to Smart Scheduler → Settings
3. **Set Optimal Times**: Configure default optimal posting times for each day
4. **Enable Features**: Enable auto-rescheduling and set performance thresholds

## Testing Instructions

### 1. Basic Functionality Testing

#### Test Quick Scheduling:
1. Go to Smart Scheduler dashboard
2. Fill in the "Quick Schedule" form
3. Select "Use AI Optimal Time"
4. Click "Schedule Post"
5. Verify post is scheduled with suggested optimal time

#### Test Custom Scheduling:
1. Select "Custom Date/Time" option
2. Choose a future date/time
3. Schedule the post
4. Verify post appears in "Upcoming Posts"

### 2. Analytics Testing

#### Test Performance Tracking:
1. Publish a test post
2. Visit the post on frontend to generate views
3. Check Smart Scheduler → Analytics
4. Verify view count increases

#### Test Performance Rating:
1. Create posts with different engagement levels
2. Wait 24 hours (or modify the check interval for testing)
3. Check that posts receive performance ratings (excellent, good, fair, poor)

### 3. Auto-Rescheduling Testing

1. **Enable Auto-Reschedule**: Go to Settings and enable auto-rescheduling
2. **Set Low Threshold**: Set performance threshold to a high value (e.g., 80) for testing
3. **Create Test Post**: Schedule and publish a post
4. **Wait for Analysis**: After 24 hours, the system should identify it as underperforming
5. **Check for Rescheduled Post**: Look for automatically created rescheduled version

### 4. Meta Box Testing

1. **Edit/Create Post**: Go to Posts → Add New or edit existing
2. **Smart Scheduler Meta Box**: Verify meta box appears on the right side
3. **Optimal Time Suggestion**: Check "Use AI Optimal Time" and verify suggestion appears
4. **Custom Scheduling**: Test custom date/time selection

### 5. Dashboard Testing

1. **Stats Display**: Verify dashboard shows correct counts for scheduled posts, published today, etc.
2. **Upcoming Posts**: Check that upcoming posts list updates correctly
3. **Optimal Times Display**: Verify optimal times grid shows configured times
4. **Real-time Updates**: Leave dashboard open and verify stats update automatically

## Development Testing Environment

### Setting Up Test Environment

1. **Local WordPress Installation**: Use XAMPP, WAMP, or Local by Flywheel
2. **Test Data**: Create multiple test posts with various content types
3. **Cron Jobs**: Ensure WordPress cron is working (install WP Crontrol plugin for testing)
4. **Debug Mode**: Enable WordPress debug mode in wp-config.php:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Performance Testing

1. **Load Testing**: Create 100+ scheduled posts and verify performance
2. **Database Queries**: Use Query Monitor plugin to check for slow queries
3. **Memory Usage**: Monitor memory usage during bulk operations

### Browser Testing

Test the admin interface in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Troubleshooting

### Common Issues

1. **Database Table Not Created**:
   - Deactivate and reactivate the plugin
   - Check WordPress debug logs
   - Verify database user has CREATE TABLE permissions

2. **AJAX Requests Failing**:
   - Check browser console for JavaScript errors
   - Verify nonce validation
   - Ensure jQuery is loaded

3. **Cron Jobs Not Running**:
   - Install WP Crontrol plugin to verify cron events
   - Check if `wp_schedule_event` is working
   - Verify server cron configuration

4. **Permission Issues**:
   - Check user capabilities
   - Verify current_user_can() checks
   - Test with different user roles

### Debug Mode

To enable detailed logging, add this to wp-config.php:

```php
define('SCS_DEBUG', true);
```

## Plugin Customization

### Adding Custom Performance Metrics

Extend the `calculate_performance_score` method in the `SCS_Scheduler` class:

```php
private function calculate_performance_score($post_id) {
    // Add your custom metrics here
    $custom_metric = get_post_meta($post_id, '_custom_metric', true);
    // Include in scoring calculation
}
```

### Custom Optimal Time Algorithms

Modify the `calculate_optimal_time` method to implement your own algorithms:

```php
public function calculate_optimal_time($post_id = null) {
    // Your custom algorithm here
    return $optimal_time;
}
```

## CodeCanyon Submission Checklist

- [ ] All code properly commented
- [ ] No hardcoded URLs or paths
- [ ] Proper WordPress coding standards
- [ ] Security measures implemented (nonces, sanitization, validation)
- [ ] Translation ready (text domain used throughout)
- [ ] No PHP errors or warnings
- [ ] Tested on multiple WordPress versions (5.0+)
- [ ] Tested on multiple PHP versions (7.4+)
- [ ] Database queries optimized
- [ ] Proper uninstall functionality
- [ ] Documentation complete
- [ ] Screenshots for CodeCanyon listing prepared

## Support

For support and feature requests, contact the developer or visit the plugin support forum.

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- AI-powered scheduling
- Performance tracking
- Auto-rescheduling feature
- Analytics dashboard
- Export functionality