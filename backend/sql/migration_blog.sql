-- Blog posts with SEO and Open Graph fields
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS blog_posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(191) NOT NULL,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NULL,
  content_html LONGTEXT NOT NULL,
  status ENUM('draft', 'published', 'scheduled') NOT NULL DEFAULT 'draft',
  featured_image_path VARCHAR(512) NULL,
  author_name VARCHAR(128) NULL,
  tags JSON NULL,
  reading_time_min SMALLINT UNSIGNED NULL,
  published_at DATETIME NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(512) NULL,
  meta_keywords VARCHAR(512) NULL,
  canonical_url VARCHAR(512) NULL,
  robots_index TINYINT(1) NOT NULL DEFAULT 1,
  og_title VARCHAR(255) NULL,
  og_description VARCHAR(512) NULL,
  og_image_path VARCHAR(512) NULL,
  og_type VARCHAR(32) NOT NULL DEFAULT 'article',
  twitter_card VARCHAR(32) NOT NULL DEFAULT 'summary_large_image',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_posts_slug (slug),
  KEY idx_blog_posts_status_published (status, published_at),
  KEY idx_blog_posts_featured (is_featured, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
