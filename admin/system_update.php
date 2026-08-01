<?php
$page_title = "System Update";
include 'includes/header.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

$repo_url = 'https://github.com/krsaurabhmca/newscast';
$api_version_url = 'https://raw.githubusercontent.com/krsaurabhmca/newscast/main/version.json';
$api_changelog_url = 'https://raw.githubusercontent.com/krsaurabhmca/newscast/main/admin/changelog.json';
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

$active_tab = 'update';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['run_diagnosis']) || isset($_POST['clean_system']))) {
    $active_tab = 'diagnosis';
} elseif (isset($_GET['tab']) && $_GET['tab'] === 'diagnosis') {
    $active_tab = 'diagnosis';
}

// Load diagnosis results if they exist
$diagnosis_results = [];
$diagnosis_file = '../diagnosis_results.json';
if (file_exists($diagnosis_file)) {
    $diagnosis_results = json_decode(file_get_contents($diagnosis_file), true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_diagnosis'])) {
    set_time_limit(300);
    $temp_zip = '../diag_temp.zip';
    
    // Download ZIP
    $fp = fopen($temp_zip, 'w+');
    $ch = curl_init($zip_url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if (!$err) {
        $zip = new ZipArchive;
        if ($zip->open($temp_zip) === TRUE) {
            $temp_extract = '../diag_extract_temp';
            if (!is_dir($temp_extract)) mkdir($temp_extract);
            $zip->extractTo($temp_extract);
            $zip->close();
            
            $root_folder = '';
            $dirs = scandir($temp_extract);
            foreach ($dirs as $d) {
                if ($d != '.' && $d != '..' && is_dir($temp_extract . '/' . $d)) {
                    $root_folder = $temp_extract . '/' . $d;
                    break;
                }
            }
            
            if ($root_folder) {
                $extra_scripts = [];
                $modified_core = [];
                $base_dir = realpath('../');
                
                $excluded_dirs = ['uploads', '.gemini', '.git', 'diag_extract_temp', 'update_extract_temp'];
                $excluded_files = ['includes/config.php', 'version.json', 'diagnosis_results.json', 'diag_temp.zip', 'update_temp.zip'];
                
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $file) {
                    $subPath = str_replace('\\', '/', substr($file->getPathname(), strlen($base_dir) + 1));
                    $parts = explode('/', $subPath);
                    if (in_array($parts[0], $excluded_dirs) || in_array($subPath, $excluded_files)) {
                        continue;
                    }
                    
                    if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                        $repo_file = $root_folder . '/' . $subPath;
                        if (!file_exists($repo_file)) {
                            $extra_scripts[] = $subPath;
                        } else {
                            if (md5_file($file->getPathname()) !== md5_file($repo_file)) {
                                $modified_core[] = $subPath;
                            }
                        }
                    }
                }
                
                $diagnosis_results = [
                    'time' => time(),
                    'extra_scripts' => $extra_scripts,
                    'modified_core' => $modified_core
                ];
                file_put_contents($diagnosis_file, json_encode($diagnosis_results));
                
                $_SESSION['flash_msg'] = "Deep scan completed successfully.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Failed to find root folder in diagnosis zip.";
                $_SESSION['flash_type'] = "danger";
            }
            
            if (!function_exists('remove_dir')) {
                function remove_dir($dir) {
                    if (is_dir($dir)) {
                        $objects = scandir($dir);
                        foreach ($objects as $object) {
                            if ($object != "." && $object != "..") {
                                if (is_dir($dir . '/' . $object) && !is_link($dir . "/" . $object)) remove_dir($dir . '/' . $object);
                                else unlink($dir . '/' . $object);
                            }
                        }
                        rmdir($dir);
                    }
                }
            }
            remove_dir($temp_extract);
        } else {
            $_SESSION['flash_msg'] = "Failed to open downloaded diagnosis ZIP.";
            $_SESSION['flash_type'] = "danger";
        }
        if (file_exists($temp_zip)) unlink($temp_zip);
    } else {
        $_SESSION['flash_msg'] = "Failed to download diagnosis ZIP: " . $err;
        $_SESSION['flash_type'] = "danger";
    }
    redirect('admin/system_update.php?tab=diagnosis');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clean_system'])) {
    if (!empty($diagnosis_results)) {
        $base_dir = realpath('../');
        foreach ($diagnosis_results['extra_scripts'] as $script) {
            $file_path = $base_dir . '/' . $script;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        if (!empty($diagnosis_results['modified_core'])) {
            set_time_limit(300);
            $temp_zip = '../diag_temp.zip';
            $fp = fopen($temp_zip, 'w+');
            $ch = curl_init($zip_url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'NewsCast-AutoUpdater');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            
            $zip = new ZipArchive;
            if ($zip->open($temp_zip) === TRUE) {
                $temp_extract = '../diag_extract_temp';
                if (!is_dir($temp_extract)) mkdir($temp_extract);
                $zip->extractTo($temp_extract);
                $zip->close();
                
                $root_folder = '';
                $dirs = scandir($temp_extract);
                foreach ($dirs as $d) {
                    if ($d != '.' && $d != '..' && is_dir($temp_extract . '/' . $d)) {
                        $root_folder = $temp_extract . '/' . $d;
                        break;
                    }
                }
                
                if ($root_folder) {
                    foreach ($diagnosis_results['modified_core'] as $core_file) {
                        $repo_file = $root_folder . '/' . $core_file;
                        $local_file = $base_dir . '/' . $core_file;
                        if (file_exists($repo_file)) {
                            copy($repo_file, $local_file);
                        }
                    }
                }
                if (!function_exists('remove_dir')) {
                    function remove_dir($dir) {
                        if (is_dir($dir)) {
                            $objects = scandir($dir);
                            foreach ($objects as $object) {
                                if ($object != "." && $object != "..") {
                                    if (is_dir($dir . '/' . $object) && !is_link($dir . "/" . $object)) remove_dir($dir . '/' . $object);
                                    else unlink($dir . '/' . $object);
                                }
                            }
                            rmdir($dir);
                        }
                    }
                }
                remove_dir($temp_extract);
            }
            if (file_exists($temp_zip)) unlink($temp_zip);
        }
        
        if (file_exists($diagnosis_file)) {
            unlink($diagnosis_file);
        }
        $diagnosis_results = [];
        
        $_SESSION['flash_msg'] = "System successfully cleaned and core files restored.";
        $_SESSION['flash_type'] = "success";
        redirect('admin/system_update.php?tab=diagnosis');
    }
}

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
    $changelogs = json_decode($cl_response, true) ?: [];
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

/* Tabs Styling */
.tabs-container { display: flex; gap: 10px; justify-content: center; margin-bottom: 25px; }
.tab-btn { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 12px 24px; border-radius: 12px; font-size: 15px; font-weight: 700; color: #475569; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; font-family: 'Inter', system-ui, sans-serif; }
.tab-btn:hover { background: #e2e8f0; color: #0f172a; }
.tab-btn.active { background: #4f46e5; color: white; border-color: #4f46e5; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
.tab-content { display: none; animation: fadeIn 0.4s ease-in-out; }
.tab-content.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

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

.timeline-wrapper { max-height: 350px; overflow-y: auto; padding-right: 15px; }
.timeline-wrapper::-webkit-scrollbar { width: 6px; }
.timeline-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.timeline { position: relative; padding-left: 25px; }
.timeline::before { content: ''; position: absolute; left: 5px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; border-radius: 2px; }
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

    <!-- Tabs Header -->
    <div class="tabs-container">
        <button class="tab-btn <?php echo $active_tab === 'update' ? 'active' : ''; ?>" id="btn-update" onclick="switchTab('update')"><i data-feather="download-cloud"></i> System Update</button>
        <button class="tab-btn <?php echo $active_tab === 'diagnosis' ? 'active' : ''; ?>" id="btn-diagnosis" onclick="switchTab('diagnosis')"><i data-feather="shield"></i> Diagnosis & Cleanup</button>
    </div>

    <!-- Update Tab -->
    <div id="tab-update" class="tab-content <?php echo $active_tab === 'update' ? 'active' : ''; ?>">
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
            <a href="system_update.php" class="btn" style="background: white; border: 1px solid #bbf7d0; color: #15803d; font-weight: 600; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;">
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

    <!-- Changelog Section (Always Shown) -->
    <?php if (!empty($changelogs)): ?>
        <div class="changelog-section" style="margin-top: 25px;">
            <div class="changelog-header">
                <i data-feather="file-text" style="color: #6366f1;"></i>
                <h3>Release Notes</h3>
                <span class="changelog-badge"><?php echo count($changelogs); ?> Releases</span>
            </div>
            
            <div class="timeline-wrapper">
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
        </div>
    <?php endif; ?>
    </div> <!-- End Update Tab Content -->

    <!-- Diagnosis Tab -->
    <div id="tab-diagnosis" class="tab-content <?php echo $active_tab === 'diagnosis' ? 'active' : ''; ?>">
        <!-- System Diagnosis & Cleanup -->
        <div class="diagnosis-container" style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(79, 70, 229, 0.05); border: 1px solid #e0e7ff; margin-bottom: 25px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; right: 0; width: 300px; height: 300px; background: radial-gradient(circle, rgba(79, 70, 229, 0.05) 0%, transparent 70%); border-radius: 50%; transform: translate(30%, -30%); pointer-events: none;"></div>
            
            <div style="display: flex; align-items: flex-start; gap: 25px; position: relative; z-index: 1;">
                <div style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);">
                    <i data-feather="shield" style="width: 32px; height: 32px;"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 800; color: #1e1b4b; letter-spacing: -0.5px;">Malware Scanner & Core Cleanup</h3>
                    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4f46e5; font-weight: 500; line-height: 1.6; max-width: 600px;">
                        Compare your files against the official repository to detect injected scripts or altered core files. <strong style="color: #4338ca;">Your uploads and databases remain untouched.</strong>
                    </p>
                    <form action="" method="POST" id="diagnosisForm">
                        <button type="submit" name="run_diagnosis" id="runDiagBtn" class="btn" style="background: #4f46e5; color: white; border: none; padding: 14px 32px; font-weight: 700; border-radius: 14px; font-size: 15px; display: inline-flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);">
                            <i data-feather="search" style="width: 18px;"></i> <span>Start Deep Scan</span>
                        </button>
                        <div id="diagLoader" style="display: none; align-items: center; gap: 10px; color: #4f46e5; font-weight: 600; font-size: 14px; margin-top: 15px;">
                            <i data-feather="loader" style="animation: spin 1s linear infinite;"></i> <span>Scanning system files... <span id="diagProgress">0%</span></span>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($diagnosis_results)): ?>
                <div style="margin-top: 35px; background: #f8fafc; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i data-feather="file-text" style="color: #6366f1;"></i> Scan Report
                        </h4>
                        <span style="font-size: 12px; font-weight: 600; color: #64748b; background: white; padding: 4px 10px; border-radius: 20px; border: 1px solid #e2e8f0;">
                            Generated: <?php echo date('M j, g:i A', $diagnosis_results['time']); ?>
                        </span>
                    </div>
                    
                    <?php if (empty($diagnosis_results['extra_scripts']) && empty($diagnosis_results['modified_core'])): ?>
                        <div style="background: #ecfdf5; border: 1px dashed #34d399; border-radius: 12px; padding: 20px; text-align: center; color: #065f46;">
                            <div style="width: 48px; height: 48px; background: #d1fae5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                <i data-feather="check" style="color: #10b981;"></i>
                            </div>
                            <h5 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 700;">System is Clean!</h5>
                            <p style="margin: 0; font-size: 13px; opacity: 0.9;">No unauthorized files or core modifications were found.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
                            <?php if (!empty($diagnosis_results['extra_scripts'])): ?>
                                <div style="background: white; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.05);">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                        <div style="width: 36px; height: 36px; background: #fee2e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444;">
                                            <i data-feather="alert-triangle" style="width: 18px;"></i>
                                        </div>
                                        <div>
                                            <h5 style="margin: 0; font-size: 15px; font-weight: 800; color: #991b1b;">Extra Scripts</h5>
                                            <span style="font-size: 12px; color: #dc2626; font-weight: 600;"><?php echo count($diagnosis_results['extra_scripts']); ?> found</span>
                                        </div>
                                    </div>
                                    <ul style="margin: 0; padding: 10px 15px; font-size: 12px; color: #b91c1c; background: #fef2f2; border-radius: 8px; max-height: 120px; overflow-y: auto; list-style-type: square;">
                                        <?php foreach ($diagnosis_results['extra_scripts'] as $script): ?>
                                            <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($script); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($diagnosis_results['modified_core'])): ?>
                                <div style="background: white; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.05);">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                        <div style="width: 36px; height: 36px; background: #fef3c7; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                                            <i data-feather="edit-3" style="width: 18px;"></i>
                                        </div>
                                        <div>
                                            <h5 style="margin: 0; font-size: 15px; font-weight: 800; color: #92400e;">Modified Core</h5>
                                            <span style="font-size: 12px; color: #d97706; font-weight: 600;"><?php echo count($diagnosis_results['modified_core']); ?> altered files</span>
                                        </div>
                                    </div>
                                    <ul style="margin: 0; padding: 10px 15px; font-size: 12px; color: #b45309; background: #fffbeb; border-radius: 8px; max-height: 120px; overflow-y: auto; list-style-type: square;">
                                        <?php foreach ($diagnosis_results['modified_core'] as $script): ?>
                                            <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($script); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <strong style="color: #0f172a; display: block; font-size: 15px;">Ready to resolve these issues?</strong>
                                <span style="color: #64748b; font-size: 13px;">This action will delete the extra scripts and restore the official core files.</span>
                            </div>
                            <form action="" method="POST" onsubmit="return confirm('WARNING: This will permanently delete extra scripts and overwrite modified core files. Make sure you don\'t have unsaved custom code. Proceed?');">
                                <button type="submit" name="clean_system" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; padding: 12px 28px; font-weight: 800; border-radius: 12px; font-size: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); cursor: pointer; transition: 0.3s;">
                                    <i data-feather="trash-2" style="width: 16px;"></i> Fix & Clean System
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <style>
        @keyframes spin { 100% { transform: rotate(360deg); } }
        #runDiagBtn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(79, 70, 229, 0.3); }
        </style>
        <script>
        document.getElementById('diagnosisForm').addEventListener('submit', function() {
            document.getElementById('runDiagBtn').style.display = 'none';
            document.getElementById('diagLoader').style.display = 'flex';
            let progress = 0;
            const progressEl = document.getElementById('diagProgress');
            const interval = setInterval(() => {
                if (progress < 98) {
                    let increment = Math.max(1, Math.floor((98 - progress) / 8));
                    progress += increment;
                    progressEl.innerText = progress + '%';
                }
            }, 400);
        });
        </script>
    </div> <!-- End Diagnosis Tab Content -->
</div> <!-- End .updater-container -->

<script>
function switchTab(tab) {
    document.getElementById('tab-update').classList.remove('active');
    document.getElementById('tab-diagnosis').classList.remove('active');
    document.getElementById('btn-update').classList.remove('active');
    document.getElementById('btn-diagnosis').classList.remove('active');
    
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('btn-' + tab).classList.add('active');
    try { localStorage.setItem('active_updater_tab', tab); } catch(e){}
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if (!isset($_POST['run_diagnosis']) && !isset($_POST['clean_system']) && !isset($_GET['tab'])): ?>
    const savedTab = localStorage.getItem('active_updater_tab');
    if (savedTab && (savedTab === 'update' || savedTab === 'diagnosis')) {
        switchTab(savedTab);
    }
    <?php endif; ?>
});

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
