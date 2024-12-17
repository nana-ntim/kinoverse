CREATE DATABASE IF NOT EXISTS kinoverse;
USE kinoverse;

-- Users table (core user information)
CREATE TABLE kinoverse_users (
    user_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image_url VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bioImage VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts table (cinematography shots/scenes)
CREATE TABLE kinoverse_posts (
    post_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    -- Key technical details (simplified)
    camera_details VARCHAR(100),
    lens_details VARCHAR(100),
    lighting_setup TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    INDEX idx_user_posts (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Collections table (for organizing posts)
CREATE TABLE kinoverse_collections (
    collection_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    is_private BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    INDEX idx_user_collections (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Collection_Posts (links posts to collections)
CREATE TABLE kinoverse_collection_posts (
    collection_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id, post_id),
    FOREIGN KEY (collection_id) REFERENCES kinoverse_collections(collection_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES kinoverse_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Follows (social connections)
CREATE TABLE kinoverse_follows (
    follower_id BIGINT UNSIGNED NOT NULL,
    following_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Likes (post engagement)
CREATE TABLE kinoverse_likes (
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES kinoverse_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments
CREATE TABLE kinoverse_comments (
    comment_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES kinoverse_posts(post_id) ON DELETE CASCADE,
    INDEX idx_post_comments (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add to your schema.sql file
CREATE TABLE kinoverse_bookmarks (
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES kinoverse_posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kinoverse_admin_logs (
    log_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_user_id BIGINT UNSIGNED NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES kinoverse_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES kinoverse_users(user_id) ON DELETE SET NULL,
    INDEX idx_admin_logs (admin_id, created_at),
    INDEX idx_target_logs (target_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE kinoverse_users
ADD COLUMN banned_until DATETIME NULL,
ADD COLUMN ban_reason TEXT NULL;