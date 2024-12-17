/**
 * Post View System
 * Handles post viewing with slide animations and interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Core elements
    const container = document.getElementById('postView');
    const overlay = document.getElementById('postViewOverlay');

    // Format numbers for display
    function formatNumber(num) {
        num = parseInt(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
        return num.toString();
    }

    // Initialize post interactions
    function initializePostInteractions() {
        // Back button
        const backBtn = container.querySelector('.post-back-btn');
        if (backBtn) {
            backBtn.addEventListener('click', closePost);
        }

        // Comment form
        const commentForm = container.querySelector('#commentForm');
        const commentInput = container.querySelector('#commentInput');
        const submitButton = commentForm?.querySelector('button[type="submit"]');

        if (commentForm && commentInput && submitButton) {
            // Enable/disable submit button based on input
            commentInput.addEventListener('input', function() {
                submitButton.disabled = !this.value.trim();
                // Auto-resize textarea
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Handle comment submission
            commentForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const content = commentInput.value.trim();
                if (!content) return;

                submitButton.disabled = true;
                const postId = container.dataset.postId;

                try {
                    const response = await fetch('../includes/actions/add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&content=${encodeURIComponent(content)}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Add new comment to the top of the list
                        const commentsSection = container.querySelector('.comments-section');
                        commentsSection.insertAdjacentHTML('afterbegin', data.commentHtml);

                        // Reset form
                        commentForm.reset();
                        commentInput.style.height = 'auto';
                        submitButton.disabled = true;

                        // Update comment count in feed
                        const feedPost = document.querySelector(`[data-post-id="${postId}"]`);
                        if (feedPost) {
                            const commentCount = feedPost.querySelector('[data-action="comment"] span');
                            if (commentCount) {
                                const newCount = parseInt(commentCount.textContent) + 1;
                                commentCount.textContent = formatNumber(newCount);
                            }
                        }
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    console.error('Error posting comment:', error);
                    alert('Failed to post comment. Please try again.');
                } finally {
                    submitButton.disabled = false;
                }
            });
        }
        
        // Like button
        const likeBtn = container.querySelector('[data-action="like"]');
        if (likeBtn) {
            likeBtn.addEventListener('click', async function() {
                try {
                    const response = await fetch('../includes/actions/toggle_like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${container.dataset.postId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Update post view like button
                        likeBtn.classList.toggle('liked');
                        const icon = likeBtn.querySelector('i');
                        icon.className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                        
                        // Update like count
                        const likeCount = container.querySelector('.action-stats');
                        if (likeCount) {
                            likeCount.textContent = `${formatNumber(data.likeCount)} likes`;
                        }

                        // Update feed post like button if visible
                        const feedPost = document.querySelector(`[data-post-id="${container.dataset.postId}"]`);
                        if (feedPost) {
                            const feedLikeBtn = feedPost.querySelector('[data-action="like"]');
                            if (feedLikeBtn) {
                                feedLikeBtn.classList.toggle('liked', data.liked);
                                feedLikeBtn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                                feedLikeBtn.querySelector('span').textContent = formatNumber(data.likeCount);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error toggling like:', error);
                }
            });
        }

        // Initialize touch gestures
        initializeTouchGestures();
    }

    // Close post view
    function closePost(e) {
        if (e) e.preventDefault();
        
        overlay.style.display = 'none';
        container.style.display = 'none';
        document.body.style.overflow = '';
        container.innerHTML = '';
    }

    // Open post view
    async function openPost(postId) {
        try {
            console.log('Opening post:', postId);
            document.body.style.overflow = 'hidden';
            
            // Show overlay and container
            overlay.style.display = 'block';
            container.style.display = 'block';
            
            // Show loading state
            container.innerHTML = '<div class="loading-spinner"></div>';
            
            const response = await fetch(`../includes/components/post_view.php?post_id=${postId}`);
            const html = await response.text();
            
            container.innerHTML = html;
            container.dataset.postId = postId;
            
            initializePostInteractions();
            
        } catch (error) {
            console.error('Error loading post:', error);
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load post</p>
                    <button onclick="closePost()">Close</button>
                </div>
            `;
        }
    }

    // Initialize touch gestures
    function initializeTouchGestures() {
        let startX = 0;
        let currentX = 0;

        container.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        container.addEventListener('touchmove', (e) => {
            currentX = e.touches[0].clientX;
            const diff = currentX - startX;
            
            if (diff > 0) {
                container.style.transform = `translateX(${diff}px)`;
                overlay.style.opacity = 1 - (diff / window.innerWidth);
            }
        });

        container.addEventListener('touchend', () => {
            const diff = currentX - startX;
            
            if (diff > window.innerWidth / 3) {
                closePost();
            } else {
                container.style.transform = '';
                overlay.style.opacity = '';
            }
        });
    }

    // Add click listeners to all post triggers
    document.querySelectorAll('[data-post-trigger]').forEach(trigger => {
        console.log('Found post trigger:', trigger); // Debug log
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.closest('.post-item').dataset.postId;
            console.log('Post clicked:', postId); // Debug log
            openPost(postId);
        });
    });

    // Close on overlay click
    overlay.addEventListener('click', closePost);
    
    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && container.style.display === 'block') {
            closePost();
        }
    });

    // Expose necessary functions
    window.openPost = openPost;
    window.closePost = closePost;
});