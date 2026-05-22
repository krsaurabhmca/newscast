<?php
$page_title = "System Update";
include 'includes/header.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

$repo_url = 'https://github.com/krsaurabhmca/newscast';
$raw_url = 'https://raw.githubusercontent.com/krsaurabhmca/newscast/main/version.json?v=' . time();
$zip_url = 'https://github.com/krsaurabhmca/newscast/archive/refs/heads/main.zip';

$local_version_file = '../version.json';
$local_info = ['version' => '1.0.0', 'db_version' => 1];
if (file_exists($local_version_file)) {
    $content = file_get_contents($local_version_file);
    $local_info = json_decode($content, true) ?: $local_info;
}

$remote_info = null;
$update_available = false;
$error = '';
$message = '';

// Check for update directly when hitting the page
$ch = curl_init($raw_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Cache-Control: no-cache, no-store, must-revalidate',
    'Pragma: no-cache',
    'Expires: 0',
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && $response) {
    $remote_info = json_decode($response, true);
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
    $error = "Could not connect to GitHub to check for updates. HTTP Code: $http_code";
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

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="background: rgba(99,102,241,0.1); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary);">
            <i data-feather="download-cloud" style="width: 40px; height: 40px;"></i>
        </div>
        <h2 style="font-size: 24px; font-weight: 800; color: #0f172a;">NewsCast System Updater</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i data-feather="alert-circle" style="width:18px;"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Current Local Version</p>
            <h3 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1;">v<?php echo htmlspecialchars($local_info['version']); ?></h3>
            <p style="font-size: 12px; color: #64748b; margin-top: 5px;">DB Schema: <?php echo htmlspecialchars($local_info['db_version']); ?></p>
        </div>

        <div style="background: <?php echo $update_available ? 'rgba(34,197,94,0.05)' : '#f8fafc'; ?>; padding: 20px; border-radius: 12px; border: 1px solid <?php echo $update_available ? 'rgba(34,197,94,0.2)' : '#e2e8f0'; ?>;">
            <p style="font-size: 11px; font-weight: 800; color: <?php echo $update_available ? '#16a34a' : '#94a3b8'; ?>; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Available Cloud Version</p>
            <h3 style="font-size: 28px; font-weight: 800; color: <?php echo $update_available ? '#15803d' : '#1e293b'; ?>; margin: 0; line-height: 1;">
                <?php echo $remote_info ? 'v' . htmlspecialchars($remote_info['version']) : 'Unknown'; ?>
            </h3>
            <?php if (isset($remote_info['changelog'])): ?>
                <p style="font-size: 12px; color: #64748b; margin-top: 5px; font-style: italic;">"<?php echo htmlspecialchars($remote_info['changelog']); ?>"</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($update_available): ?>
        <div style="background: #fffbeb; padding: 15px 20px; border-radius: 12px; border: 1px solid #fde68a; margin-bottom: 30px; display: flex; align-items: flex-start; gap: 12px;">
            <i data-feather="info" style="color: #d97706; width: 22px; flex-shrink: 0;"></i>
            <div>
                <strong style="color: #92400e; font-size: 14px; display: block; margin-bottom: 3px;">Important Notice</strong>
                <p style="color: #b45309; font-size: 13px; margin: 0;">Before updating, please make sure you have backed up any custom changes made directly to the root source code. This updater will safely overwrite core files from the master branch.</p>
            </div>
        </div>

        <form action="" method="POST" style="text-align: center;" onsubmit="return confirm('Are you sure you want to download and install this update?');">
            <button type="submit" name="do_update" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px; font-weight: 800; border-radius: 30px; background: #22c55e; border-color: #22c55e; box-shadow: 0 10px 25px rgba(34,197,94,0.3); display: inline-flex; align-items: center; gap: 10px;">
                <i data-feather="download"></i> Download & Install Update
            </button>
        </form>
    <?php else: ?>
        <div style="text-align: center; color: #16a34a; font-weight: 700; font-size: 16px; background: rgba(34,197,94,0.1); border: 1px dashed rgba(34,197,94,0.3); padding: 20px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i data-feather="check-circle" style="width: 22px;"></i> You are running the latest version!
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="system_update.php" class="btn" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 13px;">
                <i data-feather="refresh-cw" style="width: 14px;"></i> Check Again
            </a>
        </div>
    <?php endif; ?>

    <?php
    $changelog_file = 'changelog.json';
    $changelogs = [];
    if (file_exists($changelog_file)) {
        $changelogs = json_decode(file_get_contents($changelog_file), true) ?: [];
    }
    if (!empty($changelogs)):
    ?>
    <div style="margin-top: 50px;">
        <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">Version History & Changelog</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($changelogs as $index => $log): ?>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: white;">
                <div style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: <?php echo $index === 0 ? '#f8fafc' : 'white'; ?>;" onclick="document.getElementById('changelog-<?php echo $index; ?>').style.display = document.getElementById('changelog-<?php echo $index; ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('svg').style.transform = document.getElementById('changelog-<?php echo $index; ?>').style.display === 'none' ? 'rotate(0deg)' : 'rotate(180deg)';">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span style="font-size: 16px; font-weight: 800; color: #0f172a;">v<?php echo htmlspecialchars($log['version']); ?></span>
                        <span style="font-size: 13px; color: #64748b; font-weight: 600;"><i data-feather="calendar" style="width: 14px; vertical-align: text-bottom;"></i> <?php echo date('M d, Y', strtotime($log['date'])); ?></span>
                    </div>
                    <i data-feather="chevron-down" style="color: #94a3b8; transition: transform 0.3s; transform: <?php echo $index === 0 ? 'rotate(180deg)' : 'rotate(0deg)'; ?>;"></i>
                </div>
                <div id="changelog-<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>; padding: 20px; border-top: 1px solid #e2e8f0;">
                    <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.6;">
                        <?php foreach($log['changes'] as $change): ?>
                            <li style="margin-bottom: 8px;"><?php echo htmlspecialchars($change); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
