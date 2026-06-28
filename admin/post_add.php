<?php
$page_title = "Add New Post";
include 'includes/header.php';

// Handle Post Submission
if (isset($_POST['publish_post']) || isset($_POST['save_draft'])) {
    $title = clean($_POST['title']);
    $slug = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($title);
    $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
    $content = $_POST['content'];
    $excerpt = clean($_POST['excerpt']);
    $meta_description = clean($_POST['meta_description']);
    $video_url = clean($_POST['video_url']);
    $external_link = clean($_POST['external_link']);

    // Scheduled Date
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');

    // Auto Ad Logic: If link is provided, it's an AD by default
    $external_type = 'none';
    $external_label = 'none';
    if (!empty($external_link)) {
        $external_label = 'Ad';
        if (filter_var($external_link, FILTER_VALIDATE_URL)) {
            $external_type = 'url';
        } elseif (preg_match('/^[0-9+\(\)#\s-]+$/', $external_link)) {
            $external_type = 'call';
        } else {
            $external_type = 'url';
        }
    }

    if (is_reporter()) {
        $status = 'draft';
    } else {
        $status = isset($_POST['publish_post']) ? 'published' : 'draft';
    }
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];

    // Image Upload with Auto-compression to WEBP (reduce 60-70%)
    $featured_image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $img_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($img_ext, $allowed)) {
            $uploaded_file = upload_and_optimize_image($_FILES['image'], "../assets/images/posts/", "post_", 1200, 80);
            if ($uploaded_file) {
                $featured_image = $uploaded_file;
            }
        }
    } elseif (!empty($_POST['ai_image_url'])) {
        $ai_url = $_POST['ai_image_url'];
        $image_data = @file_get_contents($ai_url);
        if ($image_data) {
            $new_filename = uniqid("post_ai_") . '_' . time() . '.jpg';
            $destination = "../assets/images/posts/" . $new_filename;
            if (file_put_contents($destination, $image_data)) {
                $featured_image = $new_filename;
            }
        }
    } elseif (!empty($_POST['library_image_filename'])) {
        $featured_image = 'media/' . clean($_POST['library_image_filename']);
    }

    $content_clean = trim(strip_tags($content, '<img><iframe>'));

    $errors = [];
    if (empty($title))
        $errors[] = "Title";
    if (empty($content_clean))
        $errors[] = "Content";
    if (empty($category_ids))
        $errors[] = "at least one Category";

    if (!empty($errors)) {
        $_SESSION['flash_msg'] = "The following fields are required: " . implode(', ', $errors) . ".";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, featured_image, video_url, external_link, external_type, external_label, status, is_featured, meta_description, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $slug, $content, $excerpt, $featured_image, $video_url, $external_link, $external_type, $external_label, $status, $is_featured, $meta_description, $published_at]);
            $post_id = $pdo->lastInsertId();

            $stmt_cat = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cat_id) {
                $stmt_cat->execute([$post_id, $cat_id]);
            }

            // Handle Tags
            if (!empty($_POST['tags'])) {
                $tags_input = explode(',', $_POST['tags']);
                $stmt_tag_insert = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
                $stmt_tag_get = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt_tag_link = $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");

                foreach ($tags_input as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name))
                        continue;

                    $tag_slug = create_slug($tag_name);
                    $stmt_tag_insert->execute([$tag_name, $tag_slug]);

                    $stmt_tag_get->execute([$tag_name]);
                    $tag_id = $stmt_tag_get->fetchColumn();

                    if ($tag_id) {
                        $stmt_tag_link->execute([$post_id, $tag_id]);
                    }
                }
            }

            $pdo->commit();
            trigger_auto_share($pdo, $post_id);
            trigger_sitemap_update($pdo);
            redirect('admin/posts.php', 'Post created successfully!');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (Exception $rb_e) {
                    // Connection lost, can't rollback
                }
            }
            $error_msg = $e->getMessage();
            if (strpos($error_msg, 'gone away') !== false) {
                $_SESSION['flash_msg'] = "Error: Database connection lost. This usually happens if the article content (like images) is too large for the server configuration (max_allowed_packet). Please try reducing image sizes.";
                $_SESSION['flash_type'] = "danger";
                header("Location: post_add.php");
                exit();
            }
            $_SESSION['flash_msg'] = "Error: " . $error_msg;
            $_SESSION['flash_type'] = "danger";
        }
    }
}

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Prefill for AI workflow
$prefill_title = isset($_POST['prefill_title']) ? htmlspecialchars($_POST['prefill_title'], ENT_QUOTES) : '';
$prefill_content = isset($_POST['prefill_content']) ? $_POST['prefill_content'] : '';
$prefill_slug = isset($_POST['prefill_slug']) ? htmlspecialchars($_POST['prefill_slug'], ENT_QUOTES) : '';
$prefill_excerpt = isset($_POST['prefill_excerpt']) ? htmlspecialchars($_POST['prefill_excerpt'], ENT_QUOTES) : '';
$prefill_category = isset($_POST['prefill_category']) ? htmlspecialchars($_POST['prefill_category'], ENT_QUOTES) : '';
?>

<form action="" method="POST" enctype="multipart/form-data" id="postForm">
    <div class="admin-grid">

        <!-- MAIN AREA -->
        <div class="admin-main-col">
            <div class="stat-card" style="margin-bottom: 25px;">
                <div class="form-group">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="margin: 0;">Title <span style="color:var(--danger);">*</span></label>
                        <a href="javascript:void(0)" onclick="toggleAIChat()"
                            style="font-size: 11px; background: #f3e8ff; color: #9333ea; padding: 4px 10px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                            <i data-feather="cpu" style="width: 12px;"></i> Auto-Draft with AI
                        </a>
                    </div>
                    <input type="text" name="title" class="form-control" placeholder="Enter article title..."
                        style="font-size: 1.25rem; font-weight: 700; height: 55px;"
                        value="<?php echo $prefill_title; ?>" required>
                </div>

                <div class="form-group">
                    <label>Body Content <span style="color:var(--danger);">*</span></label>
                    <div id="editor-container">
                        <div id="editor" style="height: 400px; font-size: 15px;"><?php echo $prefill_content; ?></div>
                    </div>
                    <input type="hidden" name="content" id="quill-content">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Short Summary / Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="3"
                        placeholder="Briefly describe the article..."><?php echo $prefill_excerpt; ?></textarea>
                    <p class="field-hint">Appears on listing pages and search results.</p>
                </div>
            </div>

            <!-- Configuration -->
            <div class="stat-card">
                <h3
                    style="font-size: 15px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="settings" style="width: 18px; color: var(--primary);"></i>
                    Advanced Configuration
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>URL Slug (Optional)</label>
                        <input type="text" name="slug" class="form-control" placeholder="auto-generated-if-blank"
                            value="<?php echo $prefill_slug; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Direct Ad Link (Optional)</label>
                        <input type="text" name="external_link" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>YouTube Video Link</label>
                        <input type="url" name="video_url" class="form-control"
                            placeholder="https://youtube.com/watch?v=...">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Meta Description</label>
                        <input type="text" name="meta_description" class="form-control" maxlength="160"
                            placeholder="SEO summary...">
                    </div>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="admin-sidebar-col" style="display: flex; flex-direction: column; gap: 25px;">

            <!-- Actions -->
            <div class="stat-card" style="background: #0f172a; color: white; border: none;">
                <div class="form-group">
                    <label style="color: #94a3b8; font-size: 12px;">Publish Schedule</label>
                    <input type="datetime-local" name="published_at" value="<?php echo date('Y-m-d\TH:i'); ?>"
                        class="form-control"
                        style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white;">
                    <p style="color: #64748b; font-size: 10px; margin-top: 5px;">Leave blank for instant publish.</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if (!is_reporter()): ?>
                        <button type="submit" name="publish_post" class="btn btn-primary"
                            style="width: 100%; justify-content: center; height: 50px; font-size: 15px;">
                            <i data-feather="zap" style="width: 18px;"></i> Publish Now
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="save_draft" class="btn"
                        style="width: 100%; justify-content: center; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                        <i data-feather="edit-3" style="width: 16px;"></i> Save as Draft
                    </button>
                </div>
                <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                    <label
                        style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: #cbd5e1;">
                        <input type="checkbox" name="is_featured"
                            style="width: 18px; height: 18px; accent-color: var(--primary);">
                        Featured on Homepage
                    </label>
                </div>
            </div>

            <!-- Categories -->
            <div class="stat-card">
                <h3
                    style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="layers" style="width: 16px; color: var(--primary);"></i>
                    Categories <span style="color:var(--danger);">*</span>
                </h3>
                <div style="max-height: 220px; overflow-y: auto; padding-right: 5px; margin-right: -5px;">
                    <?php foreach ($categories as $cat): ?>
                        <label
                            style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; cursor: pointer; font-size: 13px; border-radius: 8px; transition: background .2s;"
                            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" <?php echo (strtolower(trim($prefill_category)) == strtolower(trim($cat['name']))) ? 'checked' : ''; ?>
                                style="accent-color: var(--primary);">
                            <span style="color: var(--text-main); font-weight: 500;"><?php echo $cat['name']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tags -->
            <div class="stat-card">
                <h3
                    style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="tag" style="width: 16px; color: var(--primary);"></i>
                    Keyword Tags
                </h3>
                <div style="position: relative;" id="tag-container">
                    <input type="text" name="tags" id="tag-input" class="form-control"
                        placeholder="news, update, world..." autocomplete="off">
                    <div id="tag-suggestions"
                        style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 50; display: none; max-height: 200px; overflow-y: auto; margin-top: 8px;">
                    </div>
                </div>
                <p class="field-hint" style="margin-top: 10px;">Separate with commas. Suggestions appear as you type.
                </p>
            </div>

            <!-- Image -->
            <div class="stat-card">
                <h3
                    style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <i data-feather="image" style="width: 16px; color: var(--primary);"></i> Featured Image
                    </span>
                    <div style="display: flex; gap: 5px;">
                        <button type="button" onclick="openMediaPicker('featured')"
                            style="font-size: 11px; background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <i data-feather="folder" style="width: 12px;"></i> Library
                        </button>
                        <button type="button" id="btn-generate-ai"
                            style="font-size: 11px; background: #f3e8ff; color: #9333ea; padding: 4px 10px; border-radius: 6px; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <i data-feather="cpu" style="width: 12px;"></i> AI
                        </button>
                    </div>
                </h3>
                <div id="previewBox"
                    style="width: 100%; height: 180px; background: #f8fafc; border: 2px dashed var(--border); border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; transition: all 0.3s ease;">
                    <style>
                        @keyframes spin {
                            100% {
                                transform: rotate(360deg);
                            }
                        }
                    </style>
                    <div id="imgLoader"
                        style="display: none; flex-direction: column; align-items: center; gap: 10px; color: var(--primary);">
                        <i data-feather="loader"
                            style="width: 24px; height: 24px; animation: spin 1s linear infinite;"></i>
                        <span style="font-size: 12px; font-weight: 600;">Generating...</span>
                    </div>
                    <i data-feather="upload-cloud" id="imgPlaceholder"
                        style="color: #cbd5e1; width: 40px; height: 40px;"></i>
                    <img id="imgPreview" src="" referrerpolicy="no-referrer"
                        style="width: 100%; height: 100%; object-fit: cover; display: none;">
                </div>
                <input type="file" name="image" id="imgInput" class="form-control"
                    style="margin-top: 15px; font-size: 12px;" accept="image/*">
                <p class="field-hint" style="margin-top: 5px;font-size:10px;">You can also paste an image directly using
                    <strong>Ctrl+V</strong>.
                </p>
                <input type="hidden" name="ai_image_url" id="ai_image_url" value="">
                <input type="hidden" name="library_image_filename" id="library_image_filename" value="">
            </div>
        </div>
    </div>
</form>

<script>
    window.addEventListener('load', function () {
        if (typeof Quill !== 'undefined') {
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Start writing your story...',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'font': [] }, { 'size': ['small', false, 'large', 'huge'] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'script': 'sub' }, { 'script': 'super' }],
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'indent': '-1' }, { 'indent': '+1' }],
                            [{ 'align': [] }],
                            ['blockquote', 'code-block'],
                            ['link', 'image', 'video'],
                            ['clean']
                        ],
                        handlers: {
                            image: function() {
                                openMediaPicker('quill');
                            }
                        }
                    }
                }
            });
            window.quill = quill;

            // Tooltips are handled globally in footer.php


            const form = document.getElementById('postForm');
            const hiddenContent = document.getElementById('quill-content');

            let clickedButton = 'save_draft';
            const publishBtn = form.querySelector('button[name="publish_post"]');
            const draftBtn = form.querySelector('button[name="save_draft"]');
            
            if (publishBtn) {
                publishBtn.addEventListener('click', () => { clickedButton = 'publish_post'; });
            }
            if (draftBtn) {
                draftBtn.addEventListener('click', () => { clickedButton = 'save_draft'; });
            }

            form.onsubmit = function () {
                try {
                    const html = quill.root.innerHTML;
                    const text = quill.getText().trim();
                    const hasMedia = quill.root.querySelector('img, iframe') !== null;

                    if (text.length === 0 && !hasMedia) {
                        alert('Article content cannot be empty.');
                        return false;
                    }

                    hiddenContent.value = html;

                    const cats = document.querySelectorAll('input[name="category_ids[]"]:checked');
                    if (cats.length === 0) {
                        alert('Please select at least one category.');
                        return false;
                    }

                    // Dynamically set hidden status parameter for post_edit.php transition compatibility
                    let statusInput = document.getElementById('submit-status');
                    if (!statusInput) {
                        statusInput = document.createElement('input');
                        statusInput.type = 'hidden';
                        statusInput.name = 'status';
                        statusInput.id = 'submit-status';
                        form.appendChild(statusInput);
                    }
                    statusInput.value = (clickedButton === 'publish_post') ? 'published' : 'draft';

                    return true;
                } catch (e) {
                    console.error("Validation error:", e);
                    return true; // Fallback to server-side validation
                }
            };
        }

        document.getElementById('imgInput').onchange = e => {
            const [file] = e.target.files;
            if (file) {
                document.getElementById('imgPreview').src = URL.createObjectURL(file);
                document.getElementById('imgPreview').style.display = 'block';
                document.getElementById('imgPlaceholder').style.display = 'none';
                document.getElementById('previewBox').style.borderStyle = 'solid';
            }
        };

        // Tag Auto-Suggest Logic
        const tagInput = document.getElementById('tag-input');
        const suggestionsBox = document.getElementById('tag-suggestions');

        tagInput.addEventListener('input', async function () {
            const val = this.value;
            const lastTag = val.split(',').pop().trim();

            if (lastTag.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`api_tags.php?q=${lastTag}`);
                const tags = await response.json();

                if (tags.length > 0) {
                    suggestionsBox.innerHTML = tags.map(t => `
                        <div class="suggestion-item" style="padding: 10px 15px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                            ${t}
                        </div>
                    `).join('');
                    suggestionsBox.style.display = 'block';

                    // Handle suggestion click
                    document.querySelectorAll('.suggestion-item').forEach(item => {
                        item.onclick = function () {
                            const currentVal = tagInput.value;
                            const parts = currentVal.split(',');
                            parts.pop(); // Remove the partial tag
                            parts.push(' ' + this.innerText.trim());
                            tagInput.value = parts.join(',').trim() + ', ';
                            suggestionsBox.style.display = 'none';
                            tagInput.focus();
                        };
                    });
                } else {
                    suggestionsBox.style.display = 'none';
                }
            } catch (error) {
                console.error('Error fetching tags:', error);
            }
        });

        // Close suggestions on click outside
        document.addEventListener('click', e => {
            if (!document.getElementById('tag-container').contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        // AI Image Generation Logic
        document.getElementById('btn-generate-ai').addEventListener('click', async function () {
            const title = document.querySelector('input[name="title"]').value;
            const content = typeof quill !== 'undefined' ? quill.getText().trim() : '';

            if (!title && !content) {
                alert('Please enter a title or write some content first so the AI knows what to draw.');
                return;
            }

            const loader = document.getElementById('imgLoader');
            const placeholder = document.getElementById('imgPlaceholder');
            const preview = document.getElementById('imgPreview');
            const imgInput = document.getElementById('imgInput');
            const aiInput = document.getElementById('ai_image_url');

            loader.style.display = 'flex';
            placeholder.style.display = 'none';
            preview.style.display = 'none';

            try {
                let formData = new FormData();
                formData.append('title', title);
                formData.append('content', content);

                const response = await fetch('../api/api_generate_image_prompt.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    const prompt = encodeURIComponent(data.prompt);
                    const randomSeed = Math.floor(Math.random() * 1000000);
                    const imageUrl = `https://image.pollinations.ai/prompt/${prompt}?width=1200&height=800&nologo=true&seed=${randomSeed}`;

                    preview.src = imageUrl;
                    aiInput.value = imageUrl;
                    imgInput.value = ''; // clear file input

                    preview.onload = function () {
                        loader.style.display = 'none';
                        preview.style.display = 'block';
                        document.getElementById('previewBox').style.borderStyle = 'solid';
                    };
                } else {
                    alert('Error: ' + data.message);
                    loader.style.display = 'none';
                    placeholder.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                alert('Failed to generate image.');
                loader.style.display = 'none';
                placeholder.style.display = 'block';
            }
        });

        // Paste image handler for clipboard
        document.addEventListener('paste', function (e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let index in items) {
                const item = items[index];
                if (item.kind === 'file' && item.type.indexOf('image/') !== -1) {
                    const blob = item.getAsFile();
                    const file = new File([blob], "pasted_image.png", { type: blob.type });

                    const imgInput = document.getElementById('imgInput');
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    imgInput.files = dataTransfer.files;

                    const preview = document.getElementById('imgPreview');
                    const placeholder = document.getElementById('imgPlaceholder');
                    const previewBox = document.getElementById('previewBox');

                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                    if (previewBox) previewBox.style.borderStyle = 'solid';

                    const aiInput = document.getElementById('ai_image_url');
                    if (aiInput) aiInput.value = '';
                    break;
                }
            }
        });

        // URL Slug Auto-generation logic
        const titleInput = document.querySelector('input[name="title"]');
        const slugInput = document.querySelector('input[name="slug"]');

        if (titleInput && slugInput) {
            function generateSlug(text) {
                return text
                    .toString()
                    .toLowerCase()
                    .replace(/\s+/g, '-')                     // Replace spaces with -
                    .replace(/[^a-z0-9\-]/g, '')              // Remove non-alphanumeric except -
                    .replace(/\-\-+/g, '-')                   // Replace multiple - with single -
                    .replace(/^-+/, '')                       // Trim - from start
                    .replace(/-+$/, '');                      // Trim - from end
            }

            let isSlugManual = slugInput.value.trim() !== "";

            slugInput.addEventListener('input', function () {
                isSlugManual = true;
                if (slugInput.value.trim() === "") {
                    isSlugManual = false;
                    slugInput.value = generateSlug(titleInput.value);
                }
            });

            titleInput.addEventListener('input', function () {
                if (!isSlugManual) {
                    slugInput.value = generateSlug(titleInput.value);
                }
            });

            if (!isSlugManual && titleInput.value.trim() !== "") {
                slugInput.value = generateSlug(titleInput.value);
            }
        }

        // Auto Save Logic
        let lastTitle = "";
        let lastContent = "";
        let lastExcerpt = "";
        let autoSavePostId = 0;
        
        function getAutoSaveData() {
            const title = document.querySelector('input[name="title"]').value;
            const content = typeof quill !== 'undefined' ? quill.root.innerHTML : '';
            const excerpt = document.querySelector('textarea[name="excerpt"]').value;
            const slug = document.querySelector('input[name="slug"]').value;
            const videoUrl = document.querySelector('input[name="video_url"]').value;
            const externalLink = document.querySelector('input[name="external_link"]').value;
            const metaDescription = document.querySelector('input[name="meta_description"]').value;
            const publishedAt = document.querySelector('input[name="published_at"]').value;
            const isFeatured = document.querySelector('input[name="is_featured"]').checked;
            const tags = document.querySelector('input[name="tags"]').value;
            const aiImageUrl = document.querySelector('input[name="ai_image_url"]').value;
            
            const categoryIds = [];
            document.querySelectorAll('input[name="category_ids[]"]:checked').forEach(cb => {
                categoryIds.push(cb.value);
            });
            
            return {
                post_id: autoSavePostId,
                title: title,
                content: content,
                excerpt: excerpt,
                slug: slug,
                video_url: videoUrl,
                external_link: externalLink,
                meta_description: metaDescription,
                published_at: publishedAt,
                is_featured: isFeatured,
                tags: tags,
                ai_image_url: aiImageUrl,
                category_ids: categoryIds
            };
        }

        async function triggerAutoSave() {
            const data = getAutoSaveData();
            
            // Check if title or content has actually changed and is not empty
            const contentText = typeof quill !== 'undefined' ? quill.getText().trim() : '';
            if (!data.title && !contentText) {
                return; // Nothing to save yet
            }
            
            if (data.title === lastTitle && data.content === lastContent && data.excerpt === lastExcerpt) {
                return; // No new changes
            }
            
            showAutoSaveIndicator("Saving draft...");

            try {
                const response = await fetch('ajax_auto_save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    autoSavePostId = result.post_id;
                    lastTitle = data.title;
                    lastContent = data.content;
                    lastExcerpt = data.excerpt;
                    showAutoSaveIndicator("Draft saved automatically.", true);
                    
                    // If we are on post_add.php and we just saved a new draft, update URL history to post_edit.php?id=NEW_ID
                    if (window.location.pathname.endsWith('post_add.php')) {
                        const newUrl = `post_edit.php?id=${autoSavePostId}`;
                        window.history.replaceState({ id: autoSavePostId }, '', newUrl);
                        
                        const form = document.getElementById('postForm');
                        if (form) {
                            form.action = `post_edit.php?id=${autoSavePostId}`;
                            const publishBtn = form.querySelector('button[name="publish_post"]');
                            if (publishBtn) {
                                publishBtn.name = 'update_post';
                            }
                            const draftBtn = form.querySelector('button[name="save_draft"]');
                            if (draftBtn) {
                                draftBtn.name = 'update_post';
                            }
                        }
                    }
                } else {
                    showAutoSaveIndicator("Auto-save failed: " + result.message, false, true);
                }
            } catch (err) {
                console.error("Auto-save error:", err);
                showAutoSaveIndicator("Auto-save failed (network error)", false, true);
            }
        }

        function showAutoSaveIndicator(text, success = false, error = false) {
            let indicator = document.getElementById('autosave-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'autosave-indicator';
                indicator.style.position = 'fixed';
                indicator.style.bottom = '20px';
                indicator.style.left = '20px';
                indicator.style.padding = '8px 16px';
                indicator.style.borderRadius = '20px';
                indicator.style.fontSize = '12px';
                indicator.style.fontWeight = '700';
                indicator.style.zIndex = '9999';
                indicator.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                indicator.style.transition = 'all 0.3s ease';
                document.body.appendChild(indicator);
            }
            
            indicator.innerText = text;
            indicator.style.display = 'block';
            indicator.style.opacity = '1';
            
            if (error) {
                indicator.style.background = '#fef2f2';
                indicator.style.color = '#ef4444';
                indicator.style.border = '1px solid #fecaca';
            } else if (success) {
                indicator.style.background = '#ecfdf5';
                indicator.style.color = '#10b981';
                indicator.style.border = '1px solid #a7f3d0';
                setTimeout(() => {
                    indicator.style.opacity = '0';
                    setTimeout(() => { indicator.style.display = 'none'; }, 300);
                }, 3000);
            } else {
                indicator.style.background = '#eff6ff';
                indicator.style.color = '#3b82f6';
                indicator.style.border = '1px solid #bfdbfe';
            }
        }

        // Initialize state and trigger interval every 30 seconds
        setTimeout(() => {
            const initialData = getAutoSaveData();
            lastTitle = initialData.title;
            lastContent = initialData.content;
            lastExcerpt = initialData.excerpt;
            
            setInterval(triggerAutoSave, 30000); // 30 seconds
        }, 2000);

        feather.replace();
    });
</script>

<!-- Media Picker Modal -->
<div id="mediaPickerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; width: 90%; max-width: 1000px; height: 80vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i data-feather="image" style="color: var(--primary);"></i> Select Media
            </h3>
            <button onclick="closeMediaPicker()" style="background: none; border: none; cursor: pointer; color: var(--muted);"><i data-feather="x"></i></button>
        </div>
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <input type="text" id="mediaPickerSearch" class="form-control" placeholder="Search by filename..." style="width: 250px; font-size: 13px;">
            <button onclick="document.getElementById('mediaPickerUpload').click()" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 5px;">
                <i data-feather="upload-cloud" style="width: 14px;"></i> Upload
            </button>
            <input type="file" id="mediaPickerUpload" accept="image/*" style="display: none;">
        </div>
        <div id="mediaPickerGrid" style="flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; align-content: start;">
            <!-- Rendered via JS -->
        </div>
        <div id="mediaPickerPagination" style="padding: 15px; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 5px;">
            <!-- Rendered via JS -->
        </div>
    </div>
</div>

<style>
.picker-item {
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: #f1f5f9;
}
.picker-item:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
}
.picker-item.selected {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}
</style>

<script>
let currentPickerTarget = null;
let pickerPage = 1;
let pickerSearch = '';

window.openMediaPicker = function(target) {
    currentPickerTarget = target;
    document.getElementById('mediaPickerModal').style.display = 'flex';
    loadMediaPicker();
};

window.closeMediaPicker = function() {
    document.getElementById('mediaPickerModal').style.display = 'none';
};

async function loadMediaPicker(page = 1, search = '') {
    const grid = document.getElementById('mediaPickerGrid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><i data-feather="loader" style="animation: spin 1s linear infinite;"></i></div>';
    feather.replace();

    try {
        const response = await fetch(`ajax_media_list.php?page=${page}&search=${encodeURIComponent(search)}`);
        const data = await response.json();
        
        if (data.success) {
            renderMediaPickerGrid(data.data);
            renderMediaPickerPagination(data.pagination);
        } else {
            grid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">${data.message}</p>`;
        }
    } catch (e) {
        grid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">Error loading media.</p>`;
    }
}

function renderMediaPickerGrid(items) {
    const grid = document.getElementById('mediaPickerGrid');
    if (items.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--muted);">No media found.</div>`;
        return;
    }

    grid.innerHTML = items.map(item => {
        const url = `../assets/images/media/${item.filename}`;
        return `
            <div class="picker-item" onclick="selectMedia('${url}', '${item.filename}')">
                <div style="aspect-ratio: 1/1; display:flex; align-items:center; justify-content:center;">
                    <img src="${url}" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div style="padding: 8px; font-size: 10px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center;" title="${item.original_name}">
                    ${item.original_name}
                </div>
            </div>
        `;
    }).join('');
}

function renderMediaPickerPagination(pg) {
    const pag = document.getElementById('mediaPickerPagination');
    if (pg.total_pages <= 1) { pag.innerHTML = ''; return; }
    
    let html = '';
    for(let i=1; i<=pg.total_pages; i++) {
        const style = i === pg.current_page ? 'btn-primary' : '';
        html += `<button class="btn btn-sm ${style}" style="${!style?'background:#fff;border:1px solid var(--border);':''}" onclick="changePickerPage(${i})">${i}</button>`;
    }
    pag.innerHTML = html;
}

window.changePickerPage = function(page) {
    pickerPage = page;
    loadMediaPicker(pickerPage, pickerSearch);
}

document.getElementById('mediaPickerSearch').addEventListener('input', function(e) {
    setTimeout(() => {
        pickerSearch = e.target.value;
        pickerPage = 1;
        loadMediaPicker(1, pickerSearch);
    }, 500);
});

// Upload from within picker
document.getElementById('mediaPickerUpload').addEventListener('change', async function() {
    if (this.files.length === 0) return;
    
    const file = this.files[0];
    const formData = new FormData();
    formData.append('media_file', file);
    
    try {
        const response = await fetch('ajax_media_upload.php', { method: 'POST', body: formData });
        const data = await response.json();
        if(data.success) {
            loadMediaPicker(1, pickerSearch);
        } else {
            alert('Upload failed: ' + data.message);
        }
    } catch(e) {
        alert('Network error during upload.');
    }
});

window.selectMedia = function(url, filename) {
    if (currentPickerTarget === 'quill') {
        const range = window.quill ? window.quill.getSelection() : null;
        if (range) {
            window.quill.insertEmbed(range.index, 'image', url);
            window.quill.setSelection(range.index + 1);
        } else {
            window.quill.insertEmbed(window.quill.getLength(), 'image', url);
        }
    } else if (currentPickerTarget === 'featured') {
        document.getElementById('imgPreview').src = url;
        document.getElementById('imgPreview').style.display = 'block';
        const placeholder = document.getElementById('imgPlaceholder');
        if (placeholder) placeholder.style.display = 'none';
        document.getElementById('previewBox').style.borderStyle = 'solid';
        
        // Save the library filename
        document.getElementById('library_image_filename').value = filename;
        // Clear normal file input
        document.getElementById('imgInput').value = '';
    }
    closeMediaPicker();
};

// Expose quill instance to window if not already
window.addEventListener('load', () => {
    if (typeof Quill !== 'undefined' && document.querySelector('#editor')) {
        // Find the quill instance from DOM, Quill doesn't attach it automatically.
        // It was created as `const quill`, but we can get it via standard selection or assign it manually.
        // In the original script block it's `const quill`.
        // To be safe we should assign `window.quill = quill;` inside the original load handler.
        // However, we can't easily modify the exact `const quill` line without a risky regex.
        // We'll rely on the fact that we can get the editor instance from the DOM:
        const editorDOM = document.querySelector('#editor');
        if(editorDOM && editorDOM.__quill) {
            window.quill = editorDOM.__quill;
        } else if (typeof quill !== 'undefined') {
            window.quill = quill;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>