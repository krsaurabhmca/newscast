<?php
// api/v1/whatsapp_bot.php
require_once 'init.php';

// Retrieve the incoming message/keyword from various potential request parameters
$incoming = $_REQUEST['message'] ?? $_REQUEST['msg'] ?? $_REQUEST['q'] ?? $_REQUEST['Body'] ?? '';
$incoming = trim($incoming);

// Format can be forced via GET/POST parameter (e.g. format=json, default is text)
$format = $_REQUEST['format'] ?? 'text';

// If no incoming message, default to greeting / menu
if (empty($incoming)) {
    $incoming = 'hello';
}

$response_text = "";
$matched_cmd = "";
$lower_msg = strtolower($incoming);

// Helper function to build post URLs properly
function build_post_url($slug, $external_type, $id) {
    if ($external_type !== 'none') {
        return BASE_URL . "click_tracker.php?post_id=" . $id;
    }
    return BASE_URL . "article/" . $slug;
}

// Command Router
if (in_array($lower_msg, ['hello', 'hi', 'start', 'menu', 'help', 'bot'])) {
    $matched_cmd = 'help';
    $site_name = get_setting('site_name', 'NewsCast');
    $response_text = "👋 *Welcome to " . $site_name . " WhatsApp Bot!* \n\n"
        . "Get the latest news directly on WhatsApp by replying with these keywords:\n\n"
        . "📰 *TODAY* - Get today's top stories\n"
        . "🔥 *LATEST* - Get the last 3 published news stories\n"
        . "📅 *DATE YYYY-MM-DD* - Get news for a specific date (e.g., *DATE " . date('Y-m-d') . "*)\n"
        . "📋 *SUMMARY* - Get a quick summary of the top 10 news stories\n"
        . "🗂️ *CATEGORIES* - List all news categories\n"
        . "📁 *CAT [Category Name]* - Get news from a category (e.g., *CAT Politics*)\n"
        . "🔍 *SEARCH [Keyword]* - Search news by keyword (e.g., *SEARCH Sports*)\n\n"
        . "💡 _Tip: You can also just type any keyword directly (e.g., \"Election\") to search news!_";

} elseif ($lower_msg === 'today') {
    $matched_cmd = 'today';
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p WHERE p.status = 'published' AND p.published_at BETWEEN ? AND ? ORDER BY p.published_at DESC LIMIT 5");
    $stmt->execute([$today_start, $today_end]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        $response_text = "📰 *Today's Top News Stories (" . date('M d, Y') . "):*\n\n";
        $i = 1;
        foreach ($posts as $post) {
            $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
            $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
            $i++;
        }
    } else {
        // Fallback to latest 3
        $response_text = "⚠️ No articles published today yet. Here are our latest news stories:\n\n";
        $stmt = $pdo->query("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p WHERE p.status = 'published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT 3");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 1;
        foreach ($posts as $post) {
            $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
            $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
            $i++;
        }
    }

} elseif ($lower_msg === 'latest' || $lower_msg === 'last 3') {
    $matched_cmd = 'latest';
    $stmt = $pdo->query("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p WHERE p.status = 'published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT 3");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        $response_text = "🔥 *Last 3 Published News Stories:*\n\n";
        $i = 1;
        foreach ($posts as $post) {
            $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
            $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
            $i++;
        }
    } else {
        $response_text = "📭 No published news stories found in the portal.";
    }

} elseif (preg_match('/^date\s+(.+)$/i', $incoming, $matches)) {
    $matched_cmd = 'date';
    $raw_date = trim($matches[1]);
    $timestamp = strtotime($raw_date);
    
    if ($timestamp === false) {
        $response_text = "❌ Invalid date format. Please use *DATE YYYY-MM-DD* (e.g., *DATE " . date('Y-m-d') . "*).";
    } else {
        $formatted_date = date('Y-m-d', $timestamp);
        $start_date = $formatted_date . ' 00:00:00';
        $end_date = $formatted_date . ' 23:59:59';
        
        $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p WHERE p.status = 'published' AND p.published_at BETWEEN ? AND ? ORDER BY p.published_at DESC LIMIT 5");
        $stmt->execute([$start_date, $end_date]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($posts)) {
            $response_text = "📅 *News for " . date('M d, Y', $timestamp) . ":*\n\n";
            $i = 1;
            foreach ($posts as $post) {
                $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
                $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
                $i++;
            }
        } else {
            $response_text = "📭 No news stories found for *" . date('M d, Y', $timestamp) . "*.";
        }
    }

} elseif ($lower_msg === 'summary' || $lower_msg === 'top 10 summary') {
    $matched_cmd = 'summary';
    $stmt = $pdo->query("SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.external_type, p.published_at FROM posts p WHERE p.status = 'published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT 10");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        $response_text = "📋 *Top 10 News Summary:*\n\n";
        $i = 1;
        foreach ($posts as $post) {
            $excerpt = get_post_excerpt($post, 20);
            $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
            $response_text .= "$i. *" . $post['title'] . "*\n_Summary:_ $excerpt\n🔗 Read details: $post_url\n\n";
            $i++;
        }
    } else {
        $response_text = "📭 No news stories found in the portal.";
    }

} elseif ($lower_msg === 'categories' || $lower_msg === 'category') {
    $matched_cmd = 'categories';
    $stmt = $pdo->query("SELECT name FROM categories WHERE status = 'active' ORDER BY name ASC");
    $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($cats)) {
        $response_text = "🗂️ *Available Categories:*\n\n";
        foreach ($cats as $cat) {
            $response_text .= "• *" . $cat . "*\n";
        }
        $response_text .= "\nType *CAT [Category Name]* (e.g., *CAT " . $cats[0] . "*) to see news from that category.";
    } else {
        $response_text = "📭 No active categories found.";
    }

} elseif (preg_match('/^cat\s+(.+)$/i', $incoming, $matches)) {
    $matched_cmd = 'category_filter';
    $cat_name = trim($matches[1]);
    
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE name LIKE ? AND status = 'active' LIMIT 1");
    $stmt->execute(["%$cat_name%"]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt_posts = $pdo->prepare("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p 
                                    JOIN post_categories pc ON p.id = pc.post_id 
                                    WHERE pc.category_id = ? AND p.status = 'published' AND p.published_at <= NOW()
                                    ORDER BY p.published_at DESC LIMIT 5");
        $stmt_posts->execute([$category['id']]);
        $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($posts)) {
            $response_text = "📁 *Top news in " . $category['name'] . ":*\n\n";
            $i = 1;
            foreach ($posts as $post) {
                $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
                $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
                $i++;
            }
        } else {
            $response_text = "📭 No published news stories found in the category *" . $category['name'] . "*.";
        }
    } else {
        $response_text = "❌ Category *" . $cat_name . "* not found. Type *CATEGORIES* to see all valid categories.";
    }

} else {
    // General keyword/phrase search fallback
    $search_query = preg_replace('/^search\s+/i', '', $incoming);
    $matched_cmd = 'search';
    
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.external_type, p.published_at FROM posts p WHERE p.title LIKE ? AND p.status = 'published' AND p.published_at <= NOW() ORDER BY p.published_at DESC LIMIT 5");
    $stmt->execute(["%$search_query%"]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($posts)) {
        $response_text = "🔍 *Search Results for \"" . $search_query . "\":*\n\n";
        $i = 1;
        foreach ($posts as $post) {
            $post_url = build_post_url($post['slug'], $post['external_type'] ?? 'none', $post['id']);
            $response_text .= "$i. *" . $post['title'] . "*\n🔗 Read here: $post_url\n\n";
            $i++;
        }
    } else {
        $response_text = "🤷 No news stories found matching \"" . $search_query . "\".\n\n"
            . "Type *MENU* to see all available commands.";
    }
}

// Output responses based on requested format
if ($format === 'json') {
    header("Content-Type: application/json; charset=UTF-8");
    api_response(true, "Chatbot response generated", [
        "input" => $incoming,
        "command" => $matched_cmd,
        "response" => $response_text
    ]);
} else {
    header("Content-Type: text/plain; charset=UTF-8");
    echo $response_text;
    exit;
}
