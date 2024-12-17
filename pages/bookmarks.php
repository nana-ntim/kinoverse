<?php
// pages/bookmarks.php
require_once "../config/config_session.inc.php";
require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks | Kinoverse</title>
    <link rel="stylesheet" href="../styles/components/navbar.css">
    <link rel="stylesheet" href="../styles/pages/feed.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/components/navbar.php'; ?>

    <main class="feed-container" id="bookmarksContainer">
        <!-- Header -->
        <div class="bookmarks-header">
            <h1 class="bookmarks-title">Bookmarks</h1>
            <p class="bookmarks-subtitle">Save posts to view them later</p>
        </div>

        <!-- Bookmarked posts will be loaded here -->
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
    </main>

    <!-- Post View Container -->
    <div id="postView"></div>

    <!-- Use the same post template as feed -->
    <template id="postTemplate">
        <article class="post-card">
            <header class="post-header">
                <img src="" alt="" class="user-avatar" data-profile>
                <div class="user-info">
                    <a href="#" class="username" data-profile></a>
                </div>
            </header>

            <div class="post-image-container" data-post-trigger>
                <img src="" alt="" class="post-image">
            </div>

            <div class="post-engagement">
                <button class="engagement-action" data-action="like">
                    <i class="far fa-heart"></i>
                    <span class="like-count">0</span>
                </button>
                <button class="engagement-action" data-action="comment">
                    <i class="far fa-comment"></i>
                    <span class="comment-count">0</span>
                </button>
                <button class="engagement-action bookmarked" data-action="bookmark">
                    <i class="fas fa-bookmark"></i>
                </button>
            </div>

            <div class="post-content">
                <h2 class="post-title"></h2>
                <p class="post-description"></p>
            </div>
        </article>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let page = 1;
            let loading = false;
            let noMorePosts = false;
            const container = document.getElementById('bookmarksContainer');
            const template = document.getElementById('postTemplate');
            const loadingSpinner = document.querySelector('.loading-spinner');

            // Load bookmarked posts
            async function loadBookmarks() {
                if (loading || noMorePosts) return;
                loading = true;

                try {
                    const response = await fetch(`../includes/actions/get_bookmarks.php?page=${page}`);
                    const data = await response.json();

                    if (data.success) {
                        if (data.posts.length === 0) {
                            noMorePosts = true;
                            if (page === 1) {
                                showEmptyState();
                            }
                            return;
                        }

                        data.posts.forEach(post => {
                            const postElement = createPostElement(post);
                            container.appendChild(postElement);
                        });

                        page++;
                    }
                } catch (error) {
                    console.error('Error loading bookmarks:', error);
                } finally {
                    loading = false;
                    loadingSpinner.style.display = 'none';
                }
            }

            // Show empty state
            function showEmptyState() {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <i class="far fa-bookmark"></i>
                    <h2 class="empty-message">No bookmarks yet</h2>
                    <p class="empty-submessage">Save posts to view them here later</p>
                `;
                container.appendChild(emptyState);
            }

            // Initial load
            loadBookmarks();

            // Event delegation for interactions
            container.addEventListener('click', async (e) => {
                const target = e.target;
                
                // Bookmark action
                if (target.closest('[data-action="bookmark"]')) {
                    const button = target.closest('[data-action="bookmark"]');
                    const post = button.closest('.post-card');
                    const postId = post.dataset.postId;
                    
                    try {
                        const response = await fetch('../includes/actions/toggle_bookmark.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `post_id=${postId}`
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && !data.bookmarked) {
                            // Remove post from bookmarks view with animation
                            post.style.opacity = '0';
                            post.style.transform = 'scale(0.9)';
                            setTimeout(() => post.remove(), 300);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });
        });
    </script>
</body>
</html>