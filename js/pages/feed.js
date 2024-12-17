// js/pages/feed.js

document.addEventListener('DOMContentLoaded', function() {
    let page = 1;
    let loading = false;
    let noMorePosts = false;
    const feedMain = document.querySelector('.feed-main');
    const postsPerPage = 10;

    // Format numbers for display
    function formatNumber(num) {
        num = parseInt(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
        return num.toString();
    }

    // Create post element
    function createPostElement(post) {
        const defaultPost = {
            post_id: post.post_id || 0,
            username: post.username || 'Unknown User',
            profile_image_url: post.profile_image_url || 'assets/default-avatar.jpg',
            image_url: post.image_url || 'assets/default-post.jpg',
            title: post.title || '',
            description: post.description || '',
            like_count: post.like_count || 0,
            comment_count: post.comment_count || 0,
            follower_count: post.follower_count || 0,
            is_liked: post.is_liked || false,
            is_bookmarked: post.is_bookmarked || false
        };

        const article = document.createElement('article');
        article.className = 'social-card';
        article.setAttribute('data-post-id', defaultPost.post_id);

        article.innerHTML = `
            <div class="profile-header">
                <img 
                    src="../${defaultPost.profile_image_url}" 
                    alt=""
                    class="profile-image"
                    onclick="window.location.href='../pages/profile.php?username=${defaultPost.username}'"
                    onerror="this.src='../assets/default-avatar.jpg'"
                >
                <div class="profile-info">
                    <div class="profile-name" 
                         onclick="window.location.href='../pages/profile.php?username=${defaultPost.username}'">
                        ${defaultPost.username}
                    </div>
                    <div class="followers">${formatNumber(defaultPost.follower_count)} followers</div>
                </div>
            </div>

            <div class="post-image-container" data-post-trigger>
                <img 
                    src="../${defaultPost.image_url}" 
                    alt="${defaultPost.title}"
                    class="post-image"
                    loading="lazy"
                    onerror="this.src='../assets/default-post.jpg'"
                >
            </div>

            <div class="engagement">
                <button class="engagement-item ${defaultPost.is_liked ? 'liked' : ''}" data-action="like">
                    <i class="${defaultPost.is_liked ? 'fas' : 'far'} fa-heart"></i>
                    <span>${formatNumber(defaultPost.like_count)}</span>
                </button>
                <div class="engagement-item" data-post-trigger>
                    <i class="far fa-comment"></i>
                    <span>${formatNumber(defaultPost.comment_count)}</span>
                </div>
                <button class="engagement-item ${defaultPost.is_bookmarked ? 'bookmarked' : ''}" data-action="bookmark">
                    <i class="${defaultPost.is_bookmarked ? 'fas' : 'far'} fa-bookmark"></i>
                    <span>Save</span>
                </button>
            </div>

            <div class="content">
                ${defaultPost.title ? `<p class="post-title">${defaultPost.title}</p>` : ''}
                ${defaultPost.description ? `<p class="post-description">${defaultPost.description}</p>` : ''}
                ${defaultPost.comment_count > 0 ? `
                    <div class="view-comments" data-post-trigger>
                        View all ${formatNumber(defaultPost.comment_count)} comments
                    </div>
                ` : ''}
            </div>
        `;

        return article;
    }

    // Load more posts
    async function loadMorePosts() {
        if (loading || noMorePosts) return;

        try {
            loading = true;

            const response = await fetch(`../includes/actions/get_feed_posts.php?page=${page}&per_page=${postsPerPage}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load posts');
            }

            if (!data.posts || data.posts.length === 0) {
                noMorePosts = true;
                return;
            }

            // Render posts
            data.posts.forEach(post => {
                const postElement = createPostElement(post);
                feedMain.appendChild(postElement);
            });

            page++;

            if (data.suggestedUsers) {
                updateSuggestionsList(data.suggestedUsers);
            }

        } catch (error) {
            console.error('Error loading posts:', error);
        } finally {
            loading = false;
        }
    }

    // Handle like action
    async function handleLike(likeBtn) {
        const postCard = likeBtn.closest('.social-card');
        const postId = postCard.dataset.postId;

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

                // Update like count in post view if open
                const postView = document.getElementById('postView');
                if (postView.classList.contains('active')) {
                    const postViewLikeBtn = postView.querySelector('[data-action="like"]');
                    if (postViewLikeBtn) {
                        postViewLikeBtn.classList.toggle('liked', data.liked);
                        postViewLikeBtn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                        const likeCount = postView.querySelector('.action-stats');
                        if (likeCount) {
                            likeCount.textContent = `${formatNumber(data.likeCount)} likes`;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }

    // Handle bookmark action
    async function handleBookmark(bookmarkBtn) {
        const postCard = bookmarkBtn.closest('.social-card');
        const postId = postCard.dataset.postId;

        try {
            const response = await fetch('../includes/actions/toggle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                bookmarkBtn.classList.toggle('bookmarked');
                const icon = bookmarkBtn.querySelector('i');
                icon.className = data.bookmarked ? 'fas fa-bookmark' : 'far fa-bookmark';
            }
        } catch (error) {
            console.error('Error toggling bookmark:', error);
        }
    }

    // UI State functions
    function showLoadingSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = '<div class="spinner"></div>';
        feedMain.appendChild(spinner);
    }

    function hideLoadingSpinner() {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) spinner.remove();
    }

    function showErrorState() {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'feed-error';
        errorMessage.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <p>Something went wrong</p>
            <button onclick="location.reload()">Try Again</button>
        `;
        feedMain.appendChild(errorMessage);
    }

    // Event Listeners
    feedMain.addEventListener('click', async function(e) {
        const target = e.target;

        // Like button
        const likeBtn = target.closest('[data-action="like"]');
        if (likeBtn) {
            e.preventDefault();
            await handleLike(likeBtn);
        }

        // Bookmark button
        const bookmarkBtn = target.closest('[data-action="bookmark"]');
        if (bookmarkBtn) {
            e.preventDefault();
            await handleBookmark(bookmarkBtn);
        }

        // Post view trigger
        const postTrigger = target.closest('[data-post-trigger]');
        if (postTrigger) {
            e.preventDefault();
            const postId = postTrigger.closest('.social-card').dataset.postId;
            openPostView(postId);
        }
    });

    // Initialize infinite scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !loading && !noMorePosts) {
                loadMorePosts();
            }
        });
    }, { rootMargin: '100px' });

    // Create and observe sentinel
    const sentinel = document.createElement('div');
    sentinel.className = 'sentinel';
    feedMain.appendChild(sentinel);
    observer.observe(sentinel);

    // Initial load
    loadMorePosts();
});

function updateSuggestionsList(suggestedUsers) {
    const suggestionList = document.querySelector('.suggestion-list');
    if (!suggestionList || !suggestedUsers.length) return;

    suggestionList.innerHTML = suggestedUsers.map(user => `
        <div class="suggestion-item">
            <img 
                src="../${user.profile_image_url || 'assets/default-avatar.jpg'}" 
                alt="${user.username}'s profile" 
                class="suggestion-avatar"
                onerror="this.src='../assets/default-avatar.jpg'"
                onclick="window.location.href='profile.php?username=${user.username}'"
            >
            <div class="suggestion-info" onclick="window.location.href='profile.php?username=${user.username}'">
                <div class="suggestion-name">${user.username}</div>
                ${user.bio ? `<div class="suggestion-bio">${user.bio}</div>` : ''}
            </div>
            <button 
                class="follow-btn ${user.is_following ? 'following' : ''}" 
                data-user-id="${user.user_id}"
            >
                ${user.is_following ? 'Following' : 'Follow'}
            </button>
        </div>
    `).join('');

    // Add event listeners for follow buttons
    document.querySelectorAll('.suggestion-list .follow-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const userId = this.dataset.userId;
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
                    this.classList.toggle('following');
                    this.textContent = this.classList.contains('following') ? 'Following' : 'Follow';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
}