<?php
require_once "../config/config_session.inc.php";
require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Get suggested users
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.profile_image_url, u.bio,
               COUNT(p.post_id) as post_count
        FROM kinoverse_users u
        LEFT JOIN kinoverse_posts p ON u.user_id = p.user_id
        WHERE u.user_id != ? 
        AND u.user_id NOT IN (
            SELECT following_id 
            FROM kinoverse_follows 
            WHERE follower_id = ?
        )
        GROUP BY u.user_id
        ORDER BY post_count DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $suggestions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching suggestions: " . $e->getMessage());
    $suggestions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed | Kinoverse</title>
    
    <link rel="stylesheet" href="../styles/components/navbar.css">
    <link rel="stylesheet" href="../styles/pages/feed.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/components/post_view.css">
</head>
<body>
    <?php include '../includes/components/navbar.php'; ?>

    <main class="feed-container">
        <!-- Main Feed Section -->
        <div class="feed-main" id="feedMain">
        </div>

        <!-- Suggestions Sidebar -->
        <aside class="suggestions-section">
            <h3 class="suggestions-header">Suggested for you</h3>
            <div class="suggestion-list">
                <?php foreach ($suggestions as $user): ?>
                    <div class="suggestion-item">
                        <img 
                            src="../<?php echo !empty($user['profile_image_url']) ? 
                                htmlspecialchars($user['profile_image_url']) : 
                                'assets/default-avatar.jpg'; ?>" 
                            alt="<?php echo htmlspecialchars($user['username']); ?>'s profile" 
                            class="suggestion-avatar"
                            onerror="this.src='../assets/default-avatar.jpg'"
                        >
                        <div class="suggestion-info">
                            <div class="suggestion-name">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                            <?php if (!empty($user['bio'])): ?>
                                <div class="suggestion-bio">
                                    <?php echo htmlspecialchars($user['bio']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button 
                            class="follow-btn <?php echo isset($user['is_following']) && $user['is_following'] ? 'following' : ''; ?>" 
                            data-user-id="<?php echo $user['user_id']; ?>"
                        >
                            <?php echo isset($user['is_following']) && $user['is_following'] ? 'Following' : 'Follow'; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </main>

    <!-- Post View Container -->
    <div class="post-view-overlay" id="postViewOverlay"></div>
    <div class="post-view-container" id="postView"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let page = 1;
        let loading = false;
        let noMorePosts = false;
        const feedMain = document.getElementById('feedMain');

        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
            }
            return num.toString();
        }

        // Load posts function
        async function loadPosts() {
            if (loading || noMorePosts) return;
            
            loading = true;
            const loadingSpinner = document.createElement('div');
            loadingSpinner.className = 'loading-spinner';
            loadingSpinner.innerHTML = '<div class="spinner"></div>';
            feedMain.appendChild(loadingSpinner);

            try {
                const response = await fetch(`../includes/actions/get_feed_posts.php?page=${page}`);
                const posts = await response.json();

                loadingSpinner.remove();

                if (!Array.isArray(posts) || posts.length === 0) {
                    if (page === 1) {
                        feedMain.innerHTML = `
                            <div class="feed-empty">
                                <i class="far fa-images"></i>
                                <p>No posts yet</p>
                                <small>Follow some creators to see their posts here</small>
                            </div>
                        `;
                    }
                    noMorePosts = true;
                    return;
                }

                posts.forEach(post => {
                    const postElement = `
                        <article class="social-card" data-post-id="${post.post_id}">
                            <div class="profile-header">
                                <a href="profile.php?username=${post.username}">
                                    <img 
                                        src="../${post.profile_image_url || 'assets/default-avatar.jpg'}" 
                                        alt="" 
                                        class="profile-image"
                                        onerror="this.src='../assets/default-avatar.jpg'"
                                    >
                                </a>
                                <div class="profile-info">
                                    <div class="profile-name">
                                        <span onclick="window.location.href='profile.php?username=${post.username}'">${post.username}</span>
                                        <small>${formatNumber(post.follower_count)} followers</small>
                                    </div>
                                </div>
                            </div>

                            <div class="post-image-container" data-post-trigger>
                                <img 
                                    src="../${post.image_url}" 
                                    alt="${post.title}" 
                                    class="post-image"
                                    onerror="this.src='../assets/default-post.jpg'"
                                >
                            </div>

                            <div class="engagement">
                                <button class="engagement-item ${post.is_liked ? 'liked' : ''}" data-action="like">
                                    <i class="${post.is_liked ? 'fas' : 'far'} fa-heart"></i>
                                    <span>${formatNumber(post.like_count)}</span>
                                </button>
                                <a href="../includes/components/post_view.php?post_id=${post.post_id}">
                                <button class="engagement-item" data-post-trigger>
                                        <i class="far fa-comment"></i>
                                        <span>${formatNumber(post.comment_count)}</span>
                                </button>
                                </a>
                                <button class="engagement-item" data-action="bookmark">
                                    <i class="far fa-bookmark"></i>
                                    Bookmark
                                </button>
                            </div>

                            <div class="content">
                                <p>${post.title}</p>
                                ${post.description ? `<p>${post.description}</p>` : ''}
                                ${post.comment_count > 0 ? `
                                    <div class="view-comments" data-post-trigger>
                                        View comments...
                                    </div>
                                ` : ''}
                            </div>
                        </article>
                    `;
                    feedMain.insertAdjacentHTML('beforeend', postElement);
                });

                page++;
            } catch (error) {
                console.error('Error loading posts:', error);
                loadingSpinner.remove();
            } finally {
                loading = false;
            }
        }

        // Initialize infinite scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !loading && !noMorePosts) {
                    loadPosts();
                }
            });
        }, { rootMargin: '100px' });

        // Create a sentinel element for infinite scroll
        const sentinel = document.createElement('div');
        sentinel.className = 'sentinel';
        feedMain.appendChild(sentinel);
        observer.observe(sentinel);

        // Initial load
        loadPosts();

        // Handle interactions
        feedMain.addEventListener('click', async (e) => {
            const target = e.target;

            // Like interaction
            if (target.closest('[data-action="like"]')) {
                const likeBtn = target.closest('[data-action="like"]');
                const postId = likeBtn.closest('.social-card').dataset.postId;

                try {
                    const response = await fetch('../includes/actions/toggle_like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        likeBtn.classList.toggle('liked');
                        const icon = likeBtn.querySelector('i');
                        const count = likeBtn.querySelector('span');
                        
                        icon.className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                        count.textContent = formatNumber(data.likeCount);
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }

            // Post view trigger
            if (target.closest('[data-post-trigger]')) {
                const postId = target.closest('.social-card').dataset.postId;
                // Your existing post view opening logic
            }

            // Bookmark interaction
            if (target.closest('[data-action="bookmark"]')) {
                const bookmarkBtn = target.closest('[data-action="bookmark"]');
                bookmarkBtn.querySelector('i').className = 
                    bookmarkBtn.querySelector('i').className === 'far fa-bookmark' ? 
                    'fas fa-bookmark' : 'far fa-bookmark';
            }
        });
    });

        document.addEventListener('DOMContentLoaded', function() {
        const suggestionsSection = document.querySelector('.suggestions-section');

        // Handle follows
        suggestionsSection.addEventListener('click', async (e) => {
            // Follow button clicks
            if (e.target.classList.contains('follow-btn')) {
                const followBtn = e.target;
                const userId = followBtn.dataset.userId;

                try {
                    const response = await fetch('../includes/actions/toggle_follow.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${userId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        followBtn.classList.toggle('following');
                        followBtn.textContent = followBtn.classList.contains('following') ? 'Following' : 'Follow';
                        
                        // If they're now following, you might want to reload the feed
                        if (followBtn.classList.contains('following')) {
                            // Optional: reload the feed to show new posts
                            // location.reload();
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }

            // Profile navigation for avatar click
            if (e.target.classList.contains('suggestion-avatar')) {
                const username = e.target.closest('.suggestion-item').querySelector('.suggestion-name').textContent.trim();
                window.location.href = `profile.php?username=${username}`;
            }

            // Profile navigation for name/bio click
            if (e.target.closest('.suggestion-info')) {
                const username = e.target.closest('.suggestion-item').querySelector('.suggestion-name').textContent.trim();
                window.location.href = `profile.php?username=${username}`;
            }
        });
    });
    </script>

    <script src="../js/pages/feed.js"></script>
</body>
</html>