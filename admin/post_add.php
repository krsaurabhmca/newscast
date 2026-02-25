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

    $status = isset($_POST['publish_post']) ? 'published' : 'draft';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];

    // Image Upload
    $featured_image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $img_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
        $new_img_name = uniqid("post_") . "." . $img_ext;
        $upload_path = "../assets/images/posts/" . $new_img_name;
        if (!is_dir("../assets/images/posts/")) mkdir("../assets/images/posts/", 0777, true);
        if (move_uploaded_file($tmp_name, $upload_path)) $featured_image = $new_img_name;
    }

    if (empty($title) || empty($category_ids)) {
        $_SESSION['flash_msg'] = "Please fill in required fields (Title and Category).";
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
                    if (empty($tag_name)) continue;
                    
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
            redirect('admin/posts.php', 'Post created successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<form action="" method="POST" enctype="multipart/form-data" id="postForm">
    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start;">
        
        <!-- MAIN AREA -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eef2f6;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 700; color: #475569; font-size: 14px; margin-bottom: 8px; display: block;">Title <span style="color:red;">*</span></label>
                    <input type="text" name="title" class="form-control" style="font-size: 18px; font-weight: 700; padding: 12px;" placeholder="Post headline..." required>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 700; color: #475569; font-size: 14px; margin-bottom: 8px; display: block;">Body Content <span style="color:red;">*</span></label>
                    <div id="editor-container" style="background: white;">
                        <div id="editor" style="height: 400px; font-size: 15px;"></div>
                    </div>
                    <input type="hidden" name="content">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #475569; font-size: 13px; margin-bottom: 8px; display: block;">Short Summary / Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="2" style="font-size: 13px;" placeholder="Brief overview for the homepage..."></textarea>
                </div>
            </div>

            <!-- Configuration -->
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h3 style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Advanced Configuration</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #64748b;">URL Slug</label>
                        <input type="text" name="slug" class="form-control" style="font-size: 13px;" placeholder="auto-generated">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #64748b;">Direct Ad Link (e.g. Website URL)</label>
                        <input type="text" name="external_link" class="form-control" style="font-size: 13px;" placeholder="Makes this an AD automatically">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #64748b;">YouTube Video Link</label>
                        <input type="url" name="video_url" class="form-control" style="font-size: 13px;" placeholder="https://youtube.com/...">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #64748b;">Meta Description</label>
                        <input type="text" name="meta_description" class="form-control" style="font-size: 13px;" maxlength="160" placeholder="SEO description">
                    </div>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Actions -->
            <div style="background: #1e293b; padding: 20px; border-radius: 12px; color: white;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-size: 12px; color: #94a3b8; display: block; margin-bottom: 5px;">Publish Schedule</label>
                    <input type="datetime-local" name="published_at" class="form-control" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white; font-size: 13px;">
                    <small style="color: #64748b; font-size: 10px;">Leave blank for instant publish.</small>
                </div>
                <button type="submit" name="publish_post" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 800; border-radius: 8px; font-size: 15px; margin-bottom: 10px;">Publish Post</button>
                <button type="submit" name="save_draft" class="btn" style="width: 100%; padding: 10px; font-weight: 700; border-radius: 8px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">Save Draft</button>
                <div style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_featured" style="width: 16px; height: 16px; accent-color: #3b82f6;"> Featured on Homepage
                    </label>
                </div>
            </div>

            <!-- Categories -->
            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #eef2f6;">
                <h3 style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 12px;">Categories <span style="color:red;">*</span></h3>
                <div style="max-height: 180px; overflow-y: auto; padding: 10px; border: 1px solid #f1f5f9; border-radius: 8px; background: #fafafa;">
                    <?php foreach ($categories as $cat): ?>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 5px 0; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>">
                            <span><?php echo $cat['name']; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tags -->
            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #eef2f6;">
                <h3 style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 12px;">Tags</h3>
                <div style="position: relative;" id="tag-container">
                    <input type="text" name="tags" id="tag-input" class="form-control" placeholder="Tag1, Tag2, Tag3..." style="font-size: 13px;" autocomplete="off">
                    <div id="tag-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 50; display: none; max-height: 200px; overflow-y: auto; margin-top: 5px;"></div>
                </div>
                <p style="font-size: 11px; color: #94a3b8; margin-top: 8px;">Separate tags with commas. Suggestions will appear as you type.</p>
            </div>

            <!-- Image -->
            <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #eef2f6;">
                <h3 style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 12px;">Cover Photo</h3>
                <div id="previewBox" style="width: 100%; height: 150px; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
                    <i data-feather="image" id="imgPlaceholder" style="color: #cbd5e1; width: 32px; height: 32px;"></i>
                    <img id="imgPreview" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                </div>
                <input type="file" name="image" id="imgInput" class="form-control" style="font-size: 12px; margin-top: 10px;" accept="image/*">
            </div>
        </div>
    </div>
</form>

<script>
    window.addEventListener('load', function() {
        if (typeof Quill !== 'undefined') {
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Start writing your story...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });

            const form = document.getElementById('postForm');
            const hiddenContent = document.querySelector('input[name="content"]');

            form.onsubmit = function() {
                const html = quill.root.innerHTML;
                if (html === '<p><br></p>') {
                    alert('Please write some content.');
                    return false;
                }
                hiddenContent.value = html;

                const cats = document.querySelectorAll('input[name="category_ids[]"]:checked');
                if (cats.length === 0) {
                    alert('Select at least one category.');
                    return false;
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

        tagInput.addEventListener('input', async function() {
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
                        item.onclick = function() {
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

        feather.replace();
    });
</script>

<?php include 'includes/footer.php'; ?>
