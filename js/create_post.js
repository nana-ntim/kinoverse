/**
 * Create Post Modal JavaScript
 * Handles modal interactions and form submission
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const createBtn = document.querySelector('.create-post-btn');
    const modal = document.getElementById('createPostModal');
    const closeBtn = document.querySelector('.close-modal');
    const fileInput = document.getElementById('postImage');
    const form = document.getElementById('createPostForm');
    const submitBtn = document.getElementById('submitPost');

    // Open modal when clicking create button
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close modal when clicking close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeModal();
        });
    }

    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Handle file input change
    if (fileInput) {
        fileInput.addEventListener('change', handleImageChange);
    }

    // Handle form submission
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }

    // Close modal function
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        resetForm();
    }

    // Reset form function
    function resetForm() {
        form.reset();
        const previewContainer = document.getElementById('imagePreviewContainer');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        
        if (previewContainer && uploadPlaceholder) {
            previewContainer.style.display = 'none';
            uploadPlaceholder.style.display = 'block';
        }
        
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    }

    // Handle image change
    function handleImageChange(e) {
        const file = e.target.files[0];
        if (!file) return;
    
        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            e.target.value = '';
            return;
        }
    
        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const imagePreview = document.getElementById('imagePreview');
            
            if (previewContainer && uploadPlaceholder && imagePreview) {
                imagePreview.src = event.target.result;
                previewContainer.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        };
        reader.readAsDataURL(file);
    }

    // Handle form submission
    async function handleSubmit(e) {
        e.preventDefault();

        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        // Show loading state
        if (submitBtn && submitText && submitSpinner) {
            submitBtn.disabled = true;
            submitText.style.opacity = '0.7';
            submitSpinner.style.display = 'inline-block';
        }

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to create post');
            }

            // Success - refresh the page
            window.location.reload();

        } catch (error) {
            alert(error.message);
            
            // Reset button state
            if (submitBtn && submitText && submitSpinner) {
                submitBtn.disabled = false;
                submitText.style.opacity = '1';
                submitSpinner.style.display = 'none';
            }
        }
    }

    // Handle technical details toggle
    const technicalToggle = document.querySelector('.optional-fields-toggle');
    if (technicalToggle) {
        technicalToggle.addEventListener('click', function() {
            const details = document.getElementById('technicalDetails');
            if (details) {
                details.classList.toggle('active');
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transform = details.classList.contains('active') ? 'rotate(180deg)' : '';
                }
            }
        });
    }
});