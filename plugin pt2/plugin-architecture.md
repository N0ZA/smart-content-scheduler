# Smart Content Scheduler & Performance Tracker - Architecture

## Core Plugin Files

```
smart-content-scheduler/
├── smart-content-scheduler.php          # Main plugin file
├── readme.txt                           # Plugin information
├── uninstall.php                        # Clean uninstallation
├── includes/                            # Core functionality
│   ├── class-smart-content-scheduler.php
│   ├── class-activator.php
│   ├── class-deactivator.php
│   └── class-i18n.php                   # Internationalization
├── admin/                               # Admin-facing functionality
│   ├── class-admin.php                  # Admin dashboard
│   ├── js/                              # JavaScript files
│   └── css/                             # Admin stylesheets
├── public/                              # Public-facing functionality
│   ├── class-public.php
│   ├── js/                              # Public JavaScript
│   └── css/                             # Public stylesheets
├── ml/                                  # Machine learning components
│   ├── class-ml-scheduler.php           # ML scheduling engine
│   ├── class-nlp-analyzer.php           # NLP functionality
│   └── models/                          # Pre-trained models
├── api/                                 # API integrations
│   ├── class-social-api.php             # Social media APIs wrapper
│   ├── class-competitor-api.php         # Competitor analysis 
│   └── class-data-collector.php         # Data collection utilities
└── tests/                               # Testing suite
```

## Database Tables

```sql
-- Content schedule table
CREATE TABLE {$table_prefix}_scs_schedules (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned NOT NULL,
  scheduled_time datetime NOT NULL,
  is_rescheduled tinyint(1) DEFAULT 0,
  original_time datetime,
  ai_confidence float DEFAULT 0,
  schedule_reason varchar(255),
  PRIMARY KEY (id),
  KEY post_id (post_id)
);

-- Performance metrics table
CREATE TABLE {$table_prefix}_scs_performance (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned NOT NULL,
  views int(11) DEFAULT 0,
  engagement float DEFAULT 0,
  social_shares int(11) DEFAULT 0,
  avg_time_on_page float DEFAULT 0,
  performance_score float DEFAULT 0,
  last_updated datetime,
  PRIMARY KEY (id),
  KEY post_id (post_id)
);

-- A/B testing table
CREATE TABLE {$table_prefix}_scs_ab_tests (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  test_name varchar(255) NOT NULL,
  post_id bigint(20) unsigned NOT NULL,
  variant varchar(50) NOT NULL,
  start_time datetime,
  end_time datetime,
  is_active tinyint(1) DEFAULT 1,
  PRIMARY KEY (id),
  KEY post_id (post_id)
);

-- ML data collection table
CREATE TABLE {$table_prefix}_scs_ml_data (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned NOT NULL,
  publish_time datetime,
  day_of_week tinyint(1),
  hour_of_day tinyint(2),
  content_length int(11),
  content_type varchar(50),
  category varchar(100),
  tags text,
  engagement_score float,
  PRIMARY KEY (id),
  KEY post_id (post_id)
);
```