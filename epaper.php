<?php
include 'includes/public_header.php';

// Filter by date if provided
$filter_date = isset($_GET['date']) ? $_GET['date'] : null;

if ($filter_date) {
    $stmt = $pdo->prepare("SELECT * FROM epapers WHERE paper_date = ? ORDER BY created_at DESC");
    $stmt->execute([$filter_date]);
    $epapers = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM epapers ORDER BY paper_date DESC");
    $epapers = $stmt->fetchAll();
}

// Get dates for history
$stmt = $pdo->query("SELECT DISTINCT paper_date FROM epapers ORDER BY paper_date DESC LIMIT 30");
$available_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="content-container">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px;">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; color: #1e293b; margin-bottom: 5px;">Digital E-Paper</h1>
                <p style="color: #64748b; font-size: 16px;">Read the daily newspaper in its original form</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <label style="font-weight: 600; color: #1e293b;">Select Date:</label>
                <form action="" method="GET" style="display: flex; gap: 5px;">
                    <input type="date" name="date" class="form-control" value="<?php echo $filter_date ?: date('Y-m-d'); ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                    <button type="submit" class="btn btn-primary" style="background: var(--primary); border: none; padding: 10px 20px;">Filter</button>
                </form>
            </div>
        </div>

        <?php if (empty($epapers)): ?>
            <div style="text-align: center; padding: 80px 20px; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0;">
                <i data-feather="file-text" style="width: 60px; height: 60px; color: #cbd5e1; margin-bottom: 20px;"></i>
                <h2 style="color: #64748b;">No e-paper available for this date.</h2>
                <p style="color: #94a3b8; margin-top: 10px;">Please select another date from the archive below.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
                <?php foreach ($epapers as $paper): ?>
                    <div class="epaper-card" style="transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
                        <a href="<?php echo BASE_URL; ?>digital-paper/view/<?php echo $paper['id']; ?>" style="text-decoration: none; color: inherit;">
                            <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 1px solid #eee; background: #fff;">
                                <img src="assets/epapers/<?php echo $paper['thumbnail'] ?: 'default_thumb.png'; ?>" 
                                     style="width: 100%; height: 380px; object-fit: cover;" 
                                     onerror="this.src='assets/images/default-post.jpg'">
                                <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 25px; background: linear-gradient(transparent, rgba(0,0,0,0.9)); color: white;">
                                    <span style="background: #ff3c00; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; display: inline-block;">
                                        <?php echo format_date($paper['paper_date']); ?>
                                    </span>
                                    <h3 style="font-size: 18px; margin: 0; line-height: 1.4; color: #fff;"><?php echo $paper['title']; ?></h3>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 15px; font-size: 13px; font-weight: 600; color: #fff; opacity: 0.9;">
                                        <i data-feather="eye" style="width: 16px;"></i> Click to Read Full Paper
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Archive Section -->
        <?php if (!empty($available_dates)): ?>
            <div style="margin-top: 60px; border-top: 1px solid #eee; padding-top: 40px;">
                <h3 style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 25px;">Recent Archives</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <?php foreach ($available_dates as $date): ?>
                            <a href="?date=<?php echo $date; ?>" 
                           style="padding: 10px 20px; background: <?php echo $filter_date === $date ? 'var(--primary)' : '#f1f5f9'; ?>; 
                                  color: <?php echo $filter_date === $date ? 'white' : '#475569'; ?>; 
                                  text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; transition: 0.2s;">
                            <?php echo format_date($date); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/public_footer.php'; ?>
