// View post details
function viewPost(postId) {
    window.location.href = `view_post.php?id=${postId}`;
}

// Delete post
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('../../includes/actions/admin_post_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&post_id=${postId}`
        });

        const data = await response.json();

        if (data.success) {
            // Remove the post card from the UI
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            if (postCard) {
                postCard.remove();
            } else {
                location.reload();
            }
        } else {
            alert(data.message || 'Failed to delete post');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while deleting the post');
    }
}

// Export report
function exportReport() {
    const filters = new URLSearchParams(window.location.search);
    const downloadUrl = `../../includes/actions/export_posts.php?${filters.toString()}`;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = 'posts_report.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}