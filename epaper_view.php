<?php
include 'includes/public_header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM epapers WHERE id = ?");
$stmt->execute([$id]);
$paper = $stmt->fetch();

if (!$paper) {
    redirect('digital-paper', 'E-Paper not found.', 'danger');
}

$pdf_url = BASE_URL . "assets/epapers/" . $paper['file_path'];
?>

<main class="content-container">
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 800; color: #1e293b;"><?php echo $paper['title']; ?></h1>
                <p style="color: #64748b;">Edition: <?php echo format_date($paper['paper_date']); ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>digital-paper" class="btn" style="background: #f1f5f9; color: #475569; font-weight: 600;">&larr; Back to Archive</a>
        </div>

        <!-- PDF Viewer Container -->
        <div style="position: relative; width: 100%; height: 85vh; background: #525659; border-radius: 8px; overflow: hidden; box-shadow: inset 0 0 10px rgba(0,0,0,0.5);">
            <!-- Embedding PDF with standard viewer and #toolbar=0 to discourage downloads -->
            <!-- Note: Modern browsers make it hard to 100% stop downloads, but this hides UI buttons -->
            <iframe src="<?php echo $pdf_url; ?>#toolbar=0&navpanes=0&scrollbar=0" width="100%" height="100%" style="border: none;"></iframe>
            
            <!-- Overlay to prevent some right clicks on the frame (limited effect on iframe content) -->
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 50px; background: transparent; z-index: 10;"></div>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #94a3b8; font-size: 13px;">
            <p><i data-feather="shield" style="width: 14px; vertical-align: middle;"></i> Secure Viewer: Downloading and printing is disabled for this publication.</p>
        </div>
    </div>
</main>

<?php include 'includes/public_footer.php'; ?>
