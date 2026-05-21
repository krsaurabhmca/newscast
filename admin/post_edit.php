<?php
$page_title = "Edit Post";
include 'includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: posts.php");
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: posts.php");
    exit();
}

// Fetch current categories for this post
$current_categories = $pdo->prepare("SELECT category_id FROM post_categories WHERE post_id = ?");
$current_categories->execute([$id]);
$post_category_ids = $current_categories->fetchAll(PDO::FETCH_COLUMN);

// Fetch current tags
$current_tags = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
$current_tags->execute([$id]);
$post_tags_string = implode(', ', $current_tags->fetchAll(PDO::FETCH_COLUMN));

// Handle Update
if (isset($_POST['update_post'])) {
    $title = clean($_POST['title']);
    $slug = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($title);
    $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
    $content = $_POST['content'];
    $excerpt = clean($_POST['excerpt']);
    $meta_description = clean($_POST['meta_description']);
    $video_url = clean($_POST['video_url']);
    $external_link = clean($_POST['external_link']);
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Scheduled Date
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : $post['published_at'];

    // Auto Ad Logic
    $external_type = 'none';
    $external_label = 'none';
    if (!empty($external_link)) {
        $external_label = 'Ad';
        if (filter_var($external_link, FILTER_VALIDATE_URL)) {
            $external_type = 'url';
        }
        elseif (preg_match('/^[0-9+\(\)#\s-]+$/', $external_link)) {
            $external_type = 'call';
        }
        else {
            $external_type = 'url';
        }
    }

    // Image Upload with Auto-compression (reduce 60-70%)
    $featured_image = $post['featured_image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $img_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($img_ext, $allowed)) {
            $new_img_name = uniqid("post_") . "." . $img_ext;
            $upload_path = "../assets/images/posts/" . $new_img_name;

            if (!is_dir("../assets/images/posts/")) {
                mkdir("../assets/images/posts/", 0777, true);
            }

            // Auto compress and resize (60% quality = ~70% reduction)
            if (compress_image($tmp_name, $upload_path, 60)) {
                if ($featured_image && file_exists("../assets/images/posts/" . $featured_image)) {
                    unlink("../assets/images/posts/" . $featured_image);
                }
                $featured_image = $new_img_name;
            }
            else {
                // Fallback if compression fails
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    if ($featured_image && file_exists("../assets/images/posts/" . $featured_image)) {
                        unlink("../assets/images/posts/" . $featured_image);
                    }
                    $featured_image = $new_img_name;
                }
            }
        }
    }

    $content_clean = trim(strip_tags($content, '<img><iframe>'));
    
    $errors = [];
    if (empty($title)) $errors[] = "Title";
    if (empty($content_clean)) $errors[] = "Content";
    if (empty($category_ids)) $errors[] = "at least one Category";

    if (!empty($errors)) {
        $_SESSION['flash_msg'] = "The following fields are required: " . implode(', ', $errors) . ".";
        $_SESSION['flash_type'] = "danger";
    }
    else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, video_url = ?, external_link = ?, external_type = ?, external_label = ?, status = ?, is_featured = ?, meta_description = ?, published_at = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $video_url, $external_link, $external_type, $external_label, $status, $is_featured, $meta_description, $published_at, $id]);

            // Sync categories
            $pdo->prepare("DELETE FROM post_categories WHERE post_id = ?")->execute([$id]);
            $stmt_cat = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cat_id) {
                $stmt_cat->execute([$id, $cat_id]);
            }

            // Sync Tags
            $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
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
                        $stmt_tag_link->execute([$id, $tag_id]);
                    }
                }
            }

            $pdo->commit();
            redirect('admin/posts.php', 'Post updated successfully!');
        }
        catch (PDOException $e) {
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
                header("Location: post_edit.php?id=" . $id);
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
?>

<form action="" method="POST" enctype="multipart/form-data" id="postForm">
<div class="admin-grid">
    
    <!-- MAIN AREA -->
    <div class="admin-main-col">
        <div class="stat-card" style="margin-bottom: 25px;">
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin: 0;">Title <span style="color:var(--danger);">*</span></label>
                    <a href="ai_news.php" style="font-size: 11px; background: #f3e8ff; color: #9333ea; padding: 4px 10px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                        <i data-feather="cpu" style="width: 12px;"></i> Auto-Draft with AI
                    </a>
                </div>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" style="font-size: 1.25rem; font-weight: 700; height: 55px;" required>
            </div>

            <div class="form-group">
                <label>Body Content <span style="color:var(--danger);">*</span></label>
                <div id="editor-container">
                    <div id="editor"></div>
                </div>
                <input type="hidden" name="content" id="quill-content" value="<?php echo htmlspecialchars($post['content']); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label>Short Summary / Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3" placeholder="Briefly describe the article..."><?php echo $post['excerpt']; ?></textarea>
                <p class="field-hint">Appears on listing pages and search results.</p>
            </div>
        </div>

        <!-- Configuration -->
        <div class="stat-card">
            <h3 style="font-size: 15px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i data-feather="settings" style="width: 18px; color: var(--primary);"></i>
                Advanced Configuration
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>URL Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo $post['slug']; ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Direct Ad Link</label>
                    <input type="text" name="external_link" class="form-control" value="<?php echo $post['external_link']; ?>" placeholder="Labels as AD automatically">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>YouTube Video Link</label>
                    <input type="url" name="video_url" class="form-control" value="<?php echo $post['video_url']; ?>" placeholder="https://youtube.com/watch?v=...">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Meta Description</label>
                    <input type="text" name="meta_description" class="form-control" value="<?php echo $post['meta_description']; ?>" maxlength="160">
                </div>
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="admin-sidebar-col" style="display: flex; flex-direction: column; gap: 25px;">
        
        <!-- Actions -->
        <div class="stat-card" style="background: #0f172a; color: white; border: none;">
            <div class="form-group">
                <label style="color: #94a3b8; font-size: 12px;">Article Status</label>
                <select name="status" class="form-control" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white;">
                    <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?> style="color: black;">Published</option>
                    <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?> style="color: black;">Draft</option>
                </select>
            </div>
            <div class="form-group">
                <label style="color: #94a3b8; font-size: 12px;">Publish Schedule</label>
                <input type="datetime-local" name="published_at" value="<?php echo date('Y-m-d\TH:i', strtotime($post['published_at'])); ?>" class="form-control" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white;">
            </div>
            <button type="submit" name="update_post" class="btn btn-primary" style="width: 100%; justify-content: center; height: 50px; font-size: 15px;">
                <i data-feather="save" style="width: 18px;"></i> Save Changes
            </button>
            <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: #cbd5e1;">
                    <input type="checkbox" name="is_featured" <?php echo $post['is_featured'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                    Featured on Homepage
                </label>
            </div>
        </div>

        <!-- Categories -->
        <div class="stat-card">
            <h3 style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i data-feather="layers" style="width: 16px; color: var(--primary);"></i>
                Categories <span style="color:var(--danger);">*</span>
            </h3>
            <div style="max-height: 220px; overflow-y: auto; padding-right: 5px; margin-right: -5px;">
                <?php foreach ($categories as $cat): ?>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; cursor: pointer; font-size: 13px; border-radius: 8px; transition: background .2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $post_category_ids) ? 'checked' : ''; ?> style="accent-color: var(--primary);">
                        <span style="color: var(--text-main); font-weight: 500;"><?php echo $cat['name']; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="stat-card">
            <h3 style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i data-feather="tag" style="width: 16px; color: var(--primary);"></i>
                Keyword Tags
            </h3>
            <div style="position: relative;" id="tag-container">
                <input type="text" name="tags" id="tag-input" class="form-control" value="<?php echo htmlspecialchars($post_tags_string); ?>" placeholder="news, update, world..." autocomplete="off">
                <div id="tag-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 50; display: none; max-height: 200px; overflow-y: auto; margin-top: 8px;"></div>
            </div>
            <p class="field-hint" style="margin-top: 10px;">Separate with commas. Suggestions appear as you type.</p>
        </div>

        <!-- Image -->
        <div class="stat-card">
            <h3 style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i data-feather="image" style="width: 16px; color: var(--primary);"></i>
                Featured Image
            </h3>
            <div id="previewBox" style="width: 100%; height: 180px; background: #f8fafc; border: 2px dashed var(--border); border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; transition: all 0.3s ease;">
                <img id="imgPreview" src="<?php echo get_post_thumbnail($post['featured_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                     <span style="color: white; font-size: 12px; font-weight: 700; background: rgba(0,0,0,0.5); padding: 5px 12px; border-radius: 20px;">Change Image</span>
                </div>
            </div>
            <input type="file" name="image" id="imgInput" class="form-control" style="margin-top: 15px; font-size: 12px;" accept="image/*">
        </div>
    </div>
</div>
</form>

<script>
    window.addEventListener('load', function() {
        if (typeof Quill !== 'undefined') {
            const quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'font': [] }, { 'size': ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'script': 'sub'}, { 'script': 'super' }],
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        [{ 'align': [] }],
                        ['blockquote', 'code-block'],
                        ['link', 'image', 'video'],
                        ['clean']
                    ]
                }
            });

            // Safely inject initial content from PHP
            const initialContent = <?php echo json_encode($post['content']); ?>;
            if (initialContent) {
                quill.clipboard.dangerouslyPasteHTML(initialContent);
            }

            // Tooltips are handled globally in footer.php


            const form = document.getElementById('postForm');
            const hiddenContent = document.getElementById('quill-content');

            form.onsubmit = function() {
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
