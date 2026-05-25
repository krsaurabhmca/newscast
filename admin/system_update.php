<?php
$page_title = "System Update";
include 'includes/header.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

$repo_url = 'https://github.com/krsaurabhmca/newscast';
$api_version_url = 'https://api.github.com/repos/krsaurabhmca/newscast/contents/version.json';
$api_changelog_url = 'https://api.github.com/repos/krsaurabhmca/newscast/contents/admin/changelog.json';
$zip_url = 'https://github.com/krsaurabhmca/newscast/archive/refs/heads/main.zip';

$local_version_file = '../version.json';
$local_info = ['version' => '1.0.0', 'db_version' => 1];
if (file_exists($local_version_file)) {
    $content = file_get_contents($local_version_file);
    $local_info = json_decode($content, true) ?: $local_info;
}

// Ensure local db_version matches actual DB setting
try {
    $stmt_db = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
    $actual_db_version = $stmt_db->fetchColumn();
    if ($actual_db_version !== false) {
        $local_info['db_version'] = (int)$actual_db_version;
    } else {
        $local_info['db_version'] = 1;
    }
} catch (Exception $e) {}

// Check DB Integrity
$schema_issues = [];

try {
    $stmt1 = $pdo->query("SHOW TABLES LIKE 'wp_sources'");
    if (!$stmt1 || count($stmt1->fetchAll()) == 0) $schema_issues[] = "Missing table: wp_sources";
} catch (Exception $e) { $schema_issues[] = "Error checking wp_sources table"; }

try {
    $stmt2 = $pdo->query("SHOW COLUMNS FROM posts LIKE 'source_url'");
    if (!$stmt2 || count($stmt2->fetchAll()) == 0) $schema_issues[] = "Missing column: source_url in posts table";
} catch (Exception $e) { $schema_issues[] = "Error checking source_url in posts"; }

try {
    $stmt3 = $pdo->query("SHOW COLUMNS FROM categories LIKE 'show_on_homepage'");
    if (!$stmt3 || count($stmt3->fetchAll()) == 0) $schema_issues[] = "Missing column: show_on_homepage in categories table";
} catch (Exception $e) { $schema_issues[] = "Error checking show_on_homepage in categories"; }

// Handle DB Repair
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repair_db'])) {
    if (file_exists('../includes/run_migrations.php')) {
        $force_migrations = true;
        include '../includes/run_migrations.php';
        $_SESSION['flash_msg'] = "Database schema has been successfully verified and repaired.";
        $_SESSION['flash_type'] = "success";
        redirect('admin/system_update.php');
    }
}

$remote_info = null;
$update_available = false;
$error = '';
$message = '';

// Check for update directly when hitting the page via GitHub API to bypass CDN cache
$ch = curl_init($api_version_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && $response) {
    $api_data = json_decode($response, true);
    if (isset($api_data['content'])) {
        $decoded_json = base64_decode($api_data['content']);
        $remote_info = json_decode($decoded_json, true);
        if ($remote_info) {
            if (version_compare($remote_info['version'], $local_info['version'], '>')) {
                $update_available = true;
            } elseif ($remote_info['db_version'] > $local_info['db_version']) {
                $update_available = true; // DB update only
            }
        } else {
            $error = "Could not parse version.json from GitHub.";
        }
    } else {
        $error = "Invalid API response from GitHub.";
    }
} else {
    $error = "Could not connect to GitHub API to check for updates. HTTP Code: $http_code";
}

// Fetch Remote Changelog via API
$ch_cl = curl_init($api_changelog_url);
curl_setopt($ch_cl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_cl, CURLOPT_TIMEOUT, 10);
curl_setopt($ch_cl, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
curl_setopt($ch_cl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch_cl, CURLOPT_SSL_VERIFYHOST, false);
$cl_response = curl_exec($ch_cl);
$cl_code = curl_getinfo($ch_cl, CURLINFO_HTTP_CODE);
curl_close($ch_cl);

$changelogs = [];
if ($cl_code == 200 && $cl_response) {
    $api_cl_data = json_decode($cl_response, true);
    if (isset($api_cl_data['content'])) {
        $changelogs = json_decode(base64_decode($api_cl_data['content']), true) ?: [];
    }
}
if (empty($changelogs) && file_exists('changelog.json')) {
    $changelogs = json_decode(file_get_contents('changelog.json'), true) ?: [];
}

// Perform Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_update'])) {
    set_time_limit(300); // 5 minutes max
    $temp_zip = '../update_temp.zip';
    
    // 1. Download ZIP
    $fp = fopen($temp_zip, 'w+');
    $ch = curl_init($zip_url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Increased from 60 to 300
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if ($err) {
        $error = "Failed to download update: " . $err;
    } else {
        // 2. Extract ZIP
        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === TRUE) {
            $temp_extract = '../update_extract_temp';
            if (!is_dir($temp_extract)) mkdir($temp_extract);
            
            $zip->extractTo($temp_extract);
            $zip->close();
            
            // Move files using recurse_copy
            $root_folder = '';
            $dirs = scandir($temp_extract);
            foreach ($dirs as $d) {
                if ($d != '.' && $d != '..' && is_dir($temp_extract . '/' . $d)) {
                    $root_folder = $temp_extract . '/' . $d;
                    break;
                }
            }
            
            if ($root_folder) {
                // Function to recursively copy files
                if (!function_exists('recurse_copy')) {
                    function recurse_copy($src, $dst) { 
                        $dir = opendir($src); 
                        @mkdir($dst, 0777, true); 
                        while(false !== ( $file = readdir($dir)) ) { 
                            if (( $file != '.' ) && ( $file != '..' )) { 
                                if ( is_dir($src . '/' . $file) ) { 
                                    recurse_copy($src . '/' . $file, $dst . '/' . $file); 
                                } 
                                else { 
                                    copy($src . '/' . $file, $dst . '/' . $file); 
                                } 
                            } 
                        } 
                        closedir($dir); 
                    }
                }
                
                // Copy all files from unzipped newscast-main to current root directory '../'
                recurse_copy($root_folder, '../');
                
                // 3. Database Updates
                if (file_exists('../includes/run_migrations.php')) {
                    global $pdo; // ensure $pdo is available for migrations
                    include '../includes/run_migrations.php';
                }
                
                $_SESSION['flash_msg'] = "System successfully updated to version " . htmlspecialchars($remote_info['version']) . "!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Extraction failed: Could not find root folder in ZIP.";
                $_SESSION['flash_type'] = "danger";
            }
            
            // Cleanup Temp Files
            if (!function_exists('remove_dir')) {
                function remove_dir($dir) {
                    if (is_dir($dir)) {
                        $objects = scandir($dir);
                        foreach ($objects as $object) {
                            if ($object != "." && $object != "..") {
                                if (is_dir($dir . '/' . $object) && !is_link($dir . "/" . $object))
                                    remove_dir($dir . '/' . $object);
                                else
                                    unlink($dir . '/' . $object);
                            }
                        }
                        rmdir($dir);
                    }
                }
            }
            remove_dir($temp_extract);
            unlink($temp_zip);
            
            redirect('admin/system_update.php');
        } else {
            $error = "Failed to open downloaded ZIP file.";
            unlink($temp_zip);
        }
    }
}
?>

<style>
/* Premium Modern UI for System Updater */
.updater-container { max-width: 900px; margin: 0 auto; font-family: 'Inter', system-ui, sans-serif; }
.header-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.5); border-radius: 24px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04); margin-bottom: 30px; text-align: center; position: relative; overflow: hidden; }
.header-glass::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 60%); z-index: -1; }
.icon-box { width: 80px; height: 80px; border-radius: 24px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.icon-box:hover { transform: translateY(-5px) scale(1.05); }
.title { font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 10px 0; letter-spacing: -1px; }
.subtitle { font-size: 15px; color: #64748b; margin: 0; font-weight: 500; }

.version-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
.version-card { background: white; border-radius: 20px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; position: relative; overflow: hidden; transition: 0.3s; }
.version-card:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.05); border-color: #e2e8f0; }
.card-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; display: inline-flex; align-items: center; gap: 6px; }
.local-label { color: #64748b; }
.remote-label { color: #8b5cf6; }
.version-number { font-size: 42px; font-weight: 900; margin: 0 0 5px 0; line-height: 1; letter-spacing: -1px; }
.local-version { color: #1e293b; }
.remote-version { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.db-schema { font-size: 13px; color: #94a3b8; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; padding: 4px 10px; border-radius: 20px; margin-top: auto; align-self: flex-start; }

.update-action-box { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 20px; padding: 30px; color: white; text-align: center; margin-bottom: 30px; box-shadow: 0 20px 30px -10px rgba(16, 185, 129, 0.4); position: relative; overflow: hidden; }
.update-action-box::after { content: ''; position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="20"/></svg>') no-repeat center right -50px; opacity: 0.5; pointer-events: none; }
.btn-update { background: white; color: #059669; border: none; padding: 15px 40px; font-size: 16px; font-weight: 800; border-radius: 30px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.btn-update:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 15px 25px rgba(0,0,0,0.15); }

.up-to-date-box { background: rgba(34, 197, 94, 0.05); border: 1px dashed rgba(34, 197, 94, 0.3); border-radius: 20px; padding: 30px; text-align: center; color: #15803d; margin-bottom: 30px; }
.up-to-date-icon { width: 60px; height: 60px; background: rgba(34, 197, 94, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }

.integrity-box { background: white; border-radius: 20px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 30px; display: flex; align-items: flex-start; gap: 20px; }
.integrity-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.integrity-good { background: #dcfce7; color: #16a34a; }
.integrity-bad { background: #fee2e2; color: #dc2626; }
.integrity-content h4 { margin: 0 0 5px 0; font-size: 18px; font-weight: 800; }
.integrity-content p { margin: 0; font-size: 14px; color: #64748b; line-height: 1.5; }

.changelog-section { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
.changelog-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
.changelog-header h3 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }
.changelog-badge { background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }

.timeline { position: relative; padding-left: 30px; }
.timeline::before { content: ''; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; border-radius: 2px; }
.timeline-item { position: relative; margin-bottom: 30px; }
.timeline-item:last-child { margin-bottom: 0; }
.timeline-dot { position: absolute; left: -30px; width: 16px; height: 16px; border-radius: 50%; background: white; border: 4px solid #6366f1; top: 4px; box-shadow: 0 0 0 4px white; }
.timeline-item:first-child .timeline-dot { border-color: #10b981; }
.timeline-version { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; cursor: pointer; user-select: none; }
.timeline-version-name { font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
.timeline-date { font-size: 13px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 5px; }
.timeline-changes { background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #f1f5f9; transition: all 0.3s ease; }
.timeline-changes ul { margin: 0; padding-left: 15px; color: #475569; font-size: 14px; line-height: 1.7; list-style-type: none; }
.timeline-changes li { position: relative; padding-left: 15px; margin-bottom: 8px; }
.timeline-changes li::before { content: ''; position: absolute; left: 0; top: 10px; width: 5px; height: 5px; background: #94a3b8; border-radius: 50%; }
.timeline-changes li:last-child { margin-bottom: 0; }

.badge-new { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
</style>

<div class="updater-container">
    <div class="header-glass">
        <div class="icon-box">
            <i data-feather="cloud-lightning" style="width: 40px; height: 40px;"></i>
        </div>
        <h2 class="title">System Update Center</h2>
        <p class="subtitle">Keep your NewsCast platform secure, stable, and up to date.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius: 16px; font-weight: 600;">
            <i data-feather="alert-circle" style="width:18px;"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="version-grid">
        <div class="version-card">
            <span class="card-label local-label"><i data-feather="hard-drive" style="width: 14px;"></i> Local Installation</span>
            <h3 class="version-number local-version">v<?php echo htmlspecialchars($local_info['version']); ?></h3>
            <span class="db-schema"><i data-feather="database" style="width: 12px;"></i> DB Schema: <?php echo htmlspecialchars($local_info['db_version']); ?></span>
        </div>

        <div class="version-card">
            <span class="card-label remote-label"><i data-feather="globe" style="width: 14px;"></i> Cloud Master</span>
            <h3 class="version-number remote-version">
                <?php echo $remote_info ? 'v' . htmlspecialchars($remote_info['version']) : 'Unknown'; ?>
            </h3>
            <span class="db-schema"><i data-feather="database" style="width: 12px;"></i> Target DB Schema: <?php echo $remote_info ? htmlspecialchars($remote_info['db_version']) : 'Unknown'; ?></span>
        </div>
    </div>

    <?php if ($update_available): ?>
        <div class="update-action-box">
            <h3 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 800;">Update Available!</h3>
            <p style="margin: 0 0 25px 0; font-size: 15px; opacity: 0.9;">A newer version of NewsCast is ready to be installed. This will safely upgrade your core files and database.</p>
            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to download and install this update? Ensure you have backups of any custom code modifications.');">
                <button type="submit" name="do_update" class="btn-update">
                    <i data-feather="download-cloud"></i> Download & Install v<?php echo htmlspecialchars($remote_info['version']); ?>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="up-to-date-box">
            <div class="up-to-date-icon">
                <i data-feather="check" style="width: 32px; height: 32px;"></i>
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 800;">System is Up to Date</h3>
            <p style="margin: 0 0 20px 0; font-size: 14px; opacity: 0.8;">You are running the latest version of NewsCast.</p>
            <a href="system_update.php" class="btn" style="background: white; border: 1px solid #bbf7d0; color: #15803d; font-weight: 600; border-radius: 12px;">
                <i data-feather="refresh-cw" style="width: 14px;"></i> Check for Updates
            </a>
        </div>
    <?php endif; ?>

    <!-- Database Integrity -->
    <?php if (!empty($schema_issues)): ?>
        <div class="integrity-box" style="border-color: #fca5a5;">
            <div class="integrity-icon integrity-bad"><i data-feather="alert-triangle"></i></div>
            <div class="integrity-content">
                <h4 style="color: #991b1b;">Database Integrity Issues Detected</h4>
                <p style="color: #b91c1c; margin-bottom: 15px;">Your current database structure does not perfectly match the required schema for this version. The following missing elements were detected:</p>
                <ul style="color: #dc2626; font-size: 13px; font-weight: 600; background: rgba(220, 38, 38, 0.05); padding: 12px 12px 12px 30px; border-radius: 12px; margin-bottom: 20px;">
                    <?php foreach($schema_issues as $iss): ?>
                        <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($iss); ?></li>
                    <?php endforeach; ?>
                </ul>
                <form action="" method="POST" onsubmit="return confirm('This will forcefully apply missing database structures. Proceed?');">
                    <button type="submit" name="repair_db" class="btn" style="background: #ef4444; color: white; border: none; padding: 10px 24px; font-weight: 700; border-radius: 10px; font-size: 14px;">
                        <i data-feather="tool" style="width: 16px;"></i> Verify & Repair Database Now
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="integrity-box">
            <div class="integrity-icon integrity-good"><i data-feather="database"></i></div>
            <div class="integrity-content">
                <h4 style="color: #166534;">Database Integrity Verified</h4>
                <p>All critical tables and columns perfectly match the latest required schema. Your system database is healthy.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Changelog Section -->
    <?php if (!empty($changelogs)): ?>
        <div class="changelog-section">
            <div class="changelog-header">
                <i data-feather="file-text" style="color: #6366f1;"></i>
                <h3>Release Notes</h3>
                <span class="changelog-badge"><?php echo count($changelogs); ?> Releases</span>
            </div>
            
            <div class="timeline">
                <?php foreach ($changelogs as $index => $log): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-version" onclick="toggleChangelog(<?php echo $index; ?>)">
                        <div class="timeline-version-name">
                            v<?php echo htmlspecialchars($log['version']); ?>
                            <?php if ($index === 0): ?>
                                <span class="badge-new">Latest</span>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-date">
                            <i data-feather="calendar" style="width: 12px;"></i>
                            <?php echo date('F j, Y', strtotime($log['date'])); ?>
                            <i data-feather="chevron-down" id="chevron-<?php echo $index; ?>" style="width: 16px; margin-left: 10px; transition: transform 0.3s; transform: <?php echo $index === 0 ? 'rotate(180deg)' : 'rotate(0deg)'; ?>;"></i>
                        </div>
                    </div>
                    <div class="timeline-changes" id="changelog-<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                        <ul>
                            <?php foreach($log['changes'] as $change): ?>
                                <li><?php echo htmlspecialchars($change); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleChangelog(index) {
    const el = document.getElementById('changelog-' + index);
    const chevron = document.getElementById('chevron-' + index);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        el.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
