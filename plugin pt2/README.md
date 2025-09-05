# Smart Content Scheduler & Performance Tracker

A WordPress plugin that leverages AI and machine learning to optimize content scheduling, track performance metrics, and automatically reschedule underperforming content.

## Features

- **AI-Powered Scheduling Optimization**
  - Machine learning to determine optimal posting times
  - Seasonal pattern recognition for timing adjustments
  - User behavior analysis for maximum engagement

- **Advanced Performance Analytics**
  - Real-time performance tracking
  - Natural language processing for content quality assessment
  - Competitor analysis integration

- **Automatic Content Rescheduling**
  - Performance threshold triggers
  - A/B testing automation
  - Smart reschedule algorithms

- **Social Media Integration**
  - Cross-platform performance tracking
  - API integration with major social networks
  - Unified analytics dashboard

## Installation

1. Upload the `smart-content-scheduler` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Smart Content' in your admin menu to configure the plugin

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- PHP extensions: mysqli, curl, json, mbstring
- Composer (for installing PHP-ML dependency)

## Setup Instructions

### First-Time Setup

1. After activating the plugin, navigate to "Smart Content > Settings"
2. Configure the general settings including:
   - Default AI confidence threshold
   - Auto-rescheduling options
   - Performance thresholds

3. For enhanced NLP capabilities (optional):
   - Go to the "API Integration" tab
   - Enter your NLP API endpoint and key

4. For social media integration (optional):
   - Navigate to the "Social Media" tab
   - Configure credentials for Facebook, Twitter, LinkedIn, etc.

### Installing Dependencies

The plugin requires PHP-ML for machine learning capabilities. Install using Composer:

```bash
cd wp-content/plugins/smart-content-scheduler
composer require php-ai/php-ml
```

## Using the Plugin

### Scheduling Content

1. When creating or editing a post, you'll see a "Smart Content Scheduler" meta box
2. Check "Use AI-powered scheduling"
3. Click "Suggest optimal times" to get AI-recommended publishing times
4. Select one of the suggested times or set a manual time

### Content Analysis

1. In the post editor, click "Analyze Content" in the Smart Content Scheduler meta box
2. Review the readability, SEO, and engagement scores
3. Implement the suggested improvements to enhance your content quality

### A/B Testing

1. Navigate to "Smart Content > A/B Testing"
2. Click "Create New A/B Test"
3. Select a post and configure test variables (title variants, content variants)
4. Define test duration and conversion goals
5. Review results and apply the winning variant when the test completes

### Performance Analytics

1. Go to "Smart Content > Analytics"
2. View performance metrics across all your content
3. Filter by date range, category, or author
4. Identify trends and patterns to improve future content strategy

## Troubleshooting

If you encounter issues:

1. Check the "Advanced > Troubleshooting" section in settings
2. Verify database tables are created correctly
3. Ensure scheduled events are running
4. If tables are missing, use the "Repair Database Tables" button

## Frequently Asked Questions

### How does the AI determine optimal posting times?
The AI analyzes your historical content performance, audience engagement patterns, and seasonal trends to suggest the most effective times for maximum engagement.

### Can I manually override the AI scheduling?
Yes, you can always choose to set your own publishing schedule or select from the AI-suggested times.

### How long does it take for the plugin to generate useful insights?
The plugin needs data to learn from. Expect initial basic recommendations after 5-10 posts, with more accurate predictions after 20+ posts with performance data.

### Does this work with custom post types?
Yes, the plugin supports all post types by default, although the primary optimization is focused on standard posts.

## License

GPL-2.0+ License - http://www.gnu.org/licenses/gpl-2.0.txt