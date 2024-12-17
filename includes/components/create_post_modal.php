<!-- Create Post Modal -->
<div class="modal-overlay" id="createPostModal">
    <div class="create-post-modal">
        <!-- Modal Header -->
        <div class="modal-header">
            <h2 class="modal-title">Create New Post</h2>
            <button class="close-modal" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Post Form -->
        <form id="createPostForm" class="create-post-form" action="../includes/actions/create_post.php" method="POST" enctype="multipart/form-data">
            <!-- Image Upload Area -->
            <div class="form-group">
                <div class="image-upload-area" id="imageUploadArea">
                    <input type="file" 
                           id="postImage" 
                           name="post_image" 
                           accept="image/*" 
                           class="hidden-input" 
                           required>
                           <div class="upload-placeholder" id="uploadPlaceholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p class="image-upload-text">Click or drag image to upload</p>
                                <p class="image-upload-hint">Recommended: 1600×1200 or larger, 10MB max</p>
                            </div>
                    <div class="image-preview-container" id="imagePreviewContainer" style="display: none;">
                        <img src="" alt="Preview" id="imagePreview" class="image-preview">
                        <button type="button" class="remove-image" onclick="removeImage()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Post Details -->
            <div class="form-group">
                <label for="postTitle">Title</label>
                <input type="text" 
                       id="postTitle" 
                       name="title" 
                       placeholder="Give your shot a title" 
                       required>
            </div>

            <div class="form-group">
                <label for="postDescription">Description</label>
                <textarea id="postDescription" 
                          name="description" 
                          placeholder="Tell the story behind this shot..."
                          rows="3"></textarea>
            </div>

            <!-- Technical Details Toggle -->
            <button type="button" class="optional-fields-toggle" onclick="toggleTechnicalDetails()">
                <span>Technical Details</span>
                <i class="fas fa-chevron-down"></i>
            </button>

            <!-- Technical Details Fields -->
            <div class="optional-fields" id="technicalDetails">
                <div class="form-group">
                    <label for="cameraDetails">Camera</label>
                    <input type="text" 
                           id="cameraDetails" 
                           name="camera_details" 
                           placeholder="e.g., Sony A7III, ARRI ALEXA Mini">
                </div>

                <div class="form-group">
                    <label for="lensDetails">Lens</label>
                    <input type="text" 
                           id="lensDetails" 
                           name="lens_details" 
                           placeholder="e.g., 24-70mm f/2.8, Cooke S4">
                </div>

                <div class="form-group">
                    <label for="lightingSetup">Lighting Setup</label>
                    <textarea id="lightingSetup" 
                            name="lighting_setup" 
                            placeholder="Describe your lighting setup..."
                            rows="2"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitPost" class="submit-post-btn" disabled>
                <span id="submitText">Post Shot</span>
                <div class="loading-spinner" id="submitSpinner" style="display: none;"></div>
            </button>
        </form>
    </div>
</div>

<!-- Create Post Button -->
<button class="create-post-btn" onclick="openModal()" aria-label="Create new post">
    <i class="fas fa-plus"></i>
</button>