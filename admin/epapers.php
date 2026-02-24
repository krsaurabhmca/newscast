<?php
$page_title = "Manage E-Paper";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Handle Add E-Paper
if (isset($_POST['add_epaper'])) {
    $title = clean($_POST['title']);
    $paper_date = $_POST['paper_date'];
    
    // File Upload
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $file_name = time() . '_' . $_FILES['pdf_file']['name'];
        $tmp_name = $_FILES['pdf_file']['tmp_name'];
        $upload_dir = "../assets/epapers/";
        
        if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
            $thumbnail = null;
            // Handle Thumbnail if provided
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
                $thumbnail = 'thumb_' . time() . '_' . $_FILES['thumbnail']['name'];
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $thumbnail);
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO epapers (title, paper_date, file_path, thumbnail) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $paper_date, $file_name, $thumbnail]);
                redirect('admin/epapers.php', 'E-Paper added successfully!');
            } catch (PDOException $e) {
                $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        }
    } else {
        $_SESSION['flash_msg'] = "Please upload a PDF file.";
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path, thumbnail FROM epapers WHERE id = ?");
    $stmt->execute([$id]);
    $paper = $stmt->fetch();
    
    if ($paper) {
        @unlink("../assets/epapers/" . $paper['file_path']);
        if ($paper['thumbnail']) @unlink("../assets/epapers/" . $paper['thumbnail']);
        
        $stmt = $pdo->prepare("DELETE FROM epapers WHERE id = ?");
        $stmt->execute([$id]);
        redirect('admin/epapers.php', 'E-Paper deleted successfully!');
    }
}

$epapers = $pdo->query("SELECT * FROM epapers ORDER BY paper_date DESC")->fetchAll();

include 'includes/header.php';
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
    <!-- Add form -->
    <div style="background: white; padding: 25px; border-radius: 12px; height: fit-content; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">Upload New E-Paper</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Daily News - Morning Edition" required>
            </div>
            <div class="form-group">
                <label>Paper Date</label>
                <input type="date" name="paper_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>PDF File</label>
                <input type="file" name="pdf_file" class="form-control" accept="application/pdf" required>
            </div>
            <div class="form-group">
                <label>Thumbnail Image (Optional)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
            </div>
            <button type="submit" name="add_epaper" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Upload E-Paper
            </button>
        </form>
    </div>

    <!-- List -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">E-Paper Archive</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($epapers as $paper): ?>
                <tr>
                    <td>
                        <img src="../assets/epapers/<?php echo $paper['thumbnail'] ?: 'default_thumb.png'; ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" onerror="this.src='../assets/images/default-post.jpg'">
                    </td>
                    <td><strong><?php echo $paper['title']; ?></strong></td>
                    <td><?php echo format_date($paper['paper_date']); ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="../assets/epapers/<?php echo $paper['file_path']; ?>" target="_blank" class="btn btn-success" style="padding: 5px 10px; font-size: 11px;">View PDF</a>
                            <a href="?delete=<?php echo $paper['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 11px;" onclick="return confirm('Delete this e-paper?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
