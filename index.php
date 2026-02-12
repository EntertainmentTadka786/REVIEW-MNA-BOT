<?php
// ============================================
// TELEGRAM MOVIE BOT - ENTERTAINMENT TADKA
// ============================================
// Version: 3.0 (Production Ready)
// Author: EntertainmentTadka
// Last Updated: 12-02-2026
// ============================================

// -------------------- ERROR REPORTING --------------------
// Production me sirf fatal errors dikhao
if (getenv('APP_ENV') === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// -------------------- COMPOSER AUTOLOAD --------------------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Dotenv load karo - agar file exist kare
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
}

// -------------------- CONFIG --------------------
// SECURITY: Sab secrets environment variables se lo
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: (defined('DEV_BOT_TOKEN') ? DEV_BOT_TOKEN : die('❌ BOT_TOKEN missing! .env ya Render dashboard check karo.')));
define('CHANNEL_ID', getenv('CHANNEL_ID') ?: '-1002831038104');
define('GROUP_CHANNEL_ID', getenv('GROUP_CHANNEL_ID') ?: CHANNEL_ID);
define('ADMIN_ID', (int)(getenv('ADMIN_ID') ?: '1080317415'));

// File paths - Environment se lo ya default
define('CSV_FILE', getenv('CSV_FILE_PATH') ?: 'movies.csv');
define('USERS_FILE', getenv('USERS_FILE_PATH') ?: 'users.json');
define('STATS_FILE', getenv('STATS_FILE_PATH') ?: 'bot_stats.json');
define('BACKUP_DIR', getenv('BACKUP_DIR_PATH') ?: 'backups/');

// Performance config
define('CACHE_EXPIRY', (int)(getenv('CACHE_EXPIRY') ?: 300));
define('ITEMS_PER_PAGE', (int)(getenv('ITEMS_PER_PAGE') ?: 5));
define('MESSAGE_DELAY', (int)(getenv('MESSAGE_DELAY') ?: 500000)); // microseconds
define('MOVIE_DELAY', (int)(getenv('MOVIE_DELAY') ?: 300000));

// Channel usernames
define('MAIN_CHANNEL', getenv('MAIN_CHANNEL') ?: '@EntertainmentTadka786');
define('REQUEST_CHANNEL', getenv('REQUEST_CHANNEL') ?: '@EntertainmentTadka7860');
define('THEATER_CHANNEL', getenv('THEATER_CHANNEL') ?: '@threater_print_movies');

// -------------------- MAINTENANCE MODE --------------------
// IMPORTANT: Production me FALSE rakho!
$MAINTENANCE_MODE = false;  // ✅ AB BOT LIVE HAI

// Agar maintenance mode ON hai toh sirf admin allow karo
if ($MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $user_id = $update['message']['from']['id'];
        
        // Sirf admin ko access do maintenance mode me
        if ($user_id == ADMIN_ID) {
            // Admin ko jaane do
        } else {
            $maintenance_msg = "🛠️ <b>Bot Under Maintenance</b>\n\n";
            $maintenance_msg .= "We're temporarily unavailable for updates.\n";
            $maintenance_msg .= "Will be back in few days!\n\n";
            $maintenance_msg .= "Thanks for patience 🙏";
            sendMessage($chat_id, $maintenance_msg, null, 'HTML');
            exit;
        }
    }
}

// -------------------- FILE INITIALIZATION --------------------
function initFiles() {
    // Users.json init
    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([
            'users' => [], 
            'total_requests' => 0, 
            'message_logs' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT));
        @chmod(USERS_FILE, 0666);
    }
    
    // CSV init
    if (!file_exists(CSV_FILE)) {
        file_put_contents(CSV_FILE, "movie_name,message_id,date,video_path\n");
        @chmod(CSV_FILE, 0666);
    }
    
    // Stats init
    if (!file_exists(STATS_FILE)) {
        file_put_contents(STATS_FILE, json_encode([
            'total_movies' => 0, 
            'total_users' => 0, 
            'total_searches' => 0, 
            'total_requests' => 0,
            'last_updated' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT));
        @chmod(STATS_FILE, 0666);
    }
    
    // Backup dir init
    if (!file_exists(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0777, true);
    }
    
    // Error log init
    if (!file_exists('error.log')) {
        @touch('error.log');
        @chmod('error.log', 0666);
    }
}

// Files initialize karo
initFiles();

// -------------------- GLOBAL CACHES --------------------
$movie_messages = [];
$movie_cache = [];
$waiting_users = [];

// -------------------- LOGGING FUNCTION --------------------
function logError($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . " | ERROR: " . $message;
    if (!empty($context)) {
        $log_entry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $log_entry .= "\n";
    
    @file_put_contents('error.log', $log_entry, FILE_APPEND);
    
    // Admin ko critical errors bhejo (optional)
    if (defined('ADMIN_ID') && ADMIN_ID > 0 && strpos($message, 'CRITICAL') !== false) {
        // Silent fail - agar send na ho toh ignore
        @sendMessage(ADMIN_ID, "⚠️ <b>Bot Error</b>\n<code>" . htmlspecialchars($message) . "</code>", null, 'HTML');
    }
}

// -------------------- STATS FUNCTIONS --------------------
function update_stats($field, $increment = 1) {
    if (!file_exists(STATS_FILE)) return false;
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    if (!is_array($stats)) $stats = [];
    
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    return file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function get_stats() {
    if (!file_exists(STATS_FILE)) return [];
    $content = file_get_contents(STATS_FILE);
    return json_decode($content, true) ?: [];
}

// -------------------- CSV LOADING & CACHING --------------------
function load_and_clean_csv($filename = null) {
    global $movie_messages;
    
    $filename = $filename ?: CSV_FILE;
    
    if (!file_exists($filename)) {
        file_put_contents($filename, "movie_name,message_id,date,video_path\n");
        return [];
    }

    $data = [];
    $movie_messages = []; // Reset
    
    $handle = fopen($filename, "r");
    if ($handle === false) {
        logError("Failed to open CSV file", ['file' => $filename]);
        return [];
    }
    
    $header = fgetcsv($handle);
    $valid_rows = 0;
    $invalid_rows = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        // Validate row structure
        if (count($row) < 3 || empty(trim($row[0] ?? ''))) {
            $invalid_rows++;
            continue;
        }
        
        $movie_name = trim($row[0]);
        $message_id_raw = trim($row[1] ?? '');
        $date = trim($row[2] ?? '');
        $video_path = trim($row[3] ?? '');
        
        // Date format validate/fix karo
        if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
            // Try to fix common date issues
            if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $date)) {
                $parts = explode('-', $date);
                $date = $parts[0] . '-' . $parts[1] . '-20' . $parts[2];
            } elseif (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $date)) {
                $parts = explode('-', $date);
                $date = sprintf("%02d-%02d-%04d", $parts[0], $parts[1], $parts[2]);
            } else {
                $date = date('d-m-Y'); // Default to today
            }
        }
        
        $entry = [
            'movie_name' => $movie_name,
            'message_id_raw' => $message_id_raw,
            'date' => $date,
            'video_path' => $video_path,
            'message_id' => is_numeric($message_id_raw) ? (int)$message_id_raw : null
        ];
        
        $data[] = $entry;
        $valid_rows++;
        
        // Cache by movie name (lowercase for case-insensitive search)
        $movie_key = strtolower($movie_name);
        if (!isset($movie_messages[$movie_key])) {
            $movie_messages[$movie_key] = [];
        }
        $movie_messages[$movie_key][] = $entry;
    }
    
    fclose($handle);
    
    // Update stats
    $stats = get_stats();
    $stats['total_movies'] = count($data);
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Agar invalid rows hain toh clean file write karo
    if ($invalid_rows > 0) {
        $handle = fopen($filename, "w");
        fputcsv($handle, ['movie_name', 'message_id', 'date', 'video_path']);
        foreach ($data as $row) {
            fputcsv($handle, [
                $row['movie_name'], 
                $row['message_id_raw'], 
                $row['date'], 
                $row['video_path']
            ]);
        }
        fclose($handle);
        
        logError("CSV Cleanup: Removed $invalid_rows invalid rows");
    }

    return $data;
}

function get_cached_movies() {
    global $movie_cache;
    
    $now = time();
    
    // Cache valid hai kya?
    if (!empty($movie_cache) && isset($movie_cache['timestamp']) && 
        ($now - $movie_cache['timestamp']) < CACHE_EXPIRY) {
        return $movie_cache['data'];
    }
    
    // Cache expire ya empty - reload karo
    $movie_cache = [
        'data' => load_and_clean_csv(),
        'timestamp' => $now
    ];
    
    return $movie_cache['data'];
}

function load_movies_from_csv() {
    return get_cached_movies();
}

// -------------------- TELEGRAM API HELPERS --------------------
function apiRequest($method, $params = [], $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($is_multipart) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data'
        ]);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    }
    
    $result = curl_exec($ch);
    
    if ($result === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        logError("CURL Error: $error", ['errno' => $errno, 'method' => $method]);
        return false;
    }
    
    curl_close($ch);
    return $result;
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    if (empty(trim($text))) return false;
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup, JSON_UNESCAPED_UNICODE);
    }
    
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }
    
    $result = apiRequest('sendMessage', $data);
    
    if ($result === false) {
        logError("sendMessage failed", ['chat_id' => $chat_id]);
        return false;
    }
    
    return json_decode($result, true);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    $result = apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
    
    if ($result === false) {
        logError("copyMessage failed", ['chat_id' => $chat_id, 'message_id' => $message_id]);
        return false;
    }
    
    return $result;
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    $result = apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
    
    if ($result === false) {
        logError("forwardMessage failed", ['chat_id' => $chat_id, 'message_id' => $message_id]);
        return false;
    }
    
    return $result;
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = ['callback_query_id' => $callback_query_id];
    
    if ($text) {
        $data['text'] = $text;
        $data['show_alert'] = $show_alert;
    }
    
    $result = apiRequest('answerCallbackQuery', $data);
    
    if ($result === false) {
        logError("answerCallbackQuery failed", ['callback_id' => $callback_query_id]);
    }
    
    return $result;
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ];
    
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup, JSON_UNESCAPED_UNICODE);
    }
    
    if ($parse_mode) {
        $data['parse_mode'] = $parse_mode;
    }
    
    $result = apiRequest('editMessageText', $data);
    
    if ($result === false) {
        logError("editMessageText failed", ['chat_id' => $chat_id, 'message_id' => $message_id]);
        return false;
    }
    
    return $result;
}

// -------------------- MOVIE DELIVERY --------------------
function deliver_item_to_chat($chat_id, $item) {
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        // Pehle forward try karo - channel name aur views dikhenge
        $result = forwardMessage($chat_id, CHANNEL_ID, $item['message_id']);
        
        if ($result !== false) {
            return true;
        } else {
            // Forward fail - copy as fallback
            $result = copyMessage($chat_id, CHANNEL_ID, $item['message_id']);
            return $result !== false;
        }
    }

    // Agar message_id nahi hai toh simple text bhejo
    $text = "🎬 <b>" . htmlspecialchars($item['movie_name'] ?? 'Unknown') . "</b>\n";
    $text .= "📝 Ref: " . ($item['message_id_raw'] ?? 'N/A') . "\n";
    $text .= "📅 Date: " . ($item['date'] ?? 'N/A') . "\n\n";
    $text .= "📢 Join: " . MAIN_CHANNEL;
    
    sendMessage($chat_id, $text, null, 'HTML');
    return false;
}

// -------------------- PAGINATION HELPERS --------------------
function get_all_movies_list() {
    return get_cached_movies();
}

function paginate_movies(array $all, int $page): array {
    $total = count($all);
    
    if ($total === 0) {
        return [
            'total' => 0,
            'total_pages' => 1, 
            'page' => 1,
            'slice' => []
        ];
    }
    
    $total_pages = (int)ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    
    return [
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'slice' => array_slice($all, $start, ITEMS_PER_PAGE)
    ];
}

function forward_page_movies($chat_id, array $page_movies) {
    $total = count($page_movies);
    if ($total === 0) return 0;
    
    $success_count = 0;
    $i = 1;
    
    foreach ($page_movies as $m) {
        $success = deliver_item_to_chat($chat_id, $m);
        if ($success) $success_count++;
        
        // Har 2 movies ke baad thoda wait karo (rate limit avoid)
        if ($i % 2 === 0) {
            usleep(MOVIE_DELAY);
        }
        
        $i++;
    }
    
    return $success_count;
}

function build_totalupload_keyboard(int $page, int $total_pages): array {
    $kb = ['inline_keyboard' => []];
    
    // Navigation row
    $nav_row = [];
    
    if ($page > 1) {
        $nav_row[] = ['text' => '⬅️ Previous', 'callback_data' => 'tu_prev_' . ($page - 1)];
    }
    
    $nav_row[] = ['text' => "📄 $page/$total_pages", 'callback_data' => 'current_page'];
    
    if ($page < $total_pages) {
        $nav_row[] = ['text' => 'Next ➡️', 'callback_data' => 'tu_next_' . ($page + 1)];
    }
    
    $kb['inline_keyboard'][] = $nav_row;
    
    // Action row
    $action_row = [
        ['text' => '🎬 Send This Page', 'callback_data' => 'tu_view_' . $page],
        ['text' => '🛑 Stop', 'callback_data' => 'tu_stop']
    ];
    
    $kb['inline_keyboard'][] = $action_row;
    
    // Quick jump row (only if many pages)
    if ($total_pages > 5) {
        $jump_row = [];
        
        if ($page > 3) {
            $jump_row[] = ['text' => '⏮️ First', 'callback_data' => 'tu_prev_1'];
        }
        
        if ($page < $total_pages - 2) {
            $jump_row[] = ['text' => 'Last ⏭️', 'callback_data' => 'tu_next_' . $total_pages];
        }
        
        if (!empty($jump_row)) {
            $kb['inline_keyboard'][] = $jump_row;
        }
    }
    
    return $kb;
}

// -------------------- TOTAL UPLOAD CONTROLLER --------------------
function totalupload_controller($chat_id, $page = 1) {
    $all = get_all_movies_list();
    
    if (empty($all)) {
        sendMessage($chat_id, "📭 <b>No Movies Found!</b>\n\nPlease add some movies first.", null, 'HTML');
        return;
    }
    
    $pg = paginate_movies($all, (int)$page);
    
    // Current page ki movies forward karo
    $sent_count = forward_page_movies($chat_id, $pg['slice']);
    
    // Status message
    $title = "🎬 <b>Total Uploads</b>\n\n";
    $title .= "📊 <b>Statistics:</b>\n";
    $title .= "• Total Movies: <b>{$pg['total']}</b>\n";
    $title .= "• Current Page: <b>{$pg['page']}/{$pg['total_pages']}</b>\n";
    $title .= "• Sent: <b>{$sent_count}/" . count($pg['slice']) . "</b> movies\n\n";
    
    // Current page movies list
    $title .= "📋 <b>Movies on This Page:</b>\n";
    $i = 1;
    foreach ($pg['slice'] as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown');
        // Trim agar bohot lamba ho
        if (mb_strlen($movie_name) > 40) {
            $movie_name = mb_substr($movie_name, 0, 37) . '...';
        }
        $title .= "$i. {$movie_name}\n";
        $i++;
    }
    
    $title .= "\n📍 Use buttons to navigate or resend page";
    
    $kb = build_totalupload_keyboard($pg['page'], $pg['total_pages']);
    sendMessage($chat_id, $title, $kb, 'HTML');
    
    update_stats('total_requests', 1);
}

// -------------------- APPEND MOVIE --------------------
function append_movie($movie_name, $message_id_raw, $date = null, $video_path = '') {
    $movie_name = trim($movie_name);
    
    if (empty($movie_name)) {
        return false;
    }
    
    if ($date === null) {
        $date = date('d-m-Y');
    }
    
    // Validate date format
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
        $date = date('d-m-Y');
    }
    
    $entry = [$movie_name, $message_id_raw, $date, $video_path];
    
    $handle = fopen(CSV_FILE, "a");
    if ($handle === false) {
        logError("Cannot open CSV for writing", ['file' => CSV_FILE]);
        return false;
    }
    
    $result = fputcsv($handle, $entry);
    fclose($handle);
    
    if ($result === false) {
        logError("Failed to write to CSV", ['entry' => $entry]);
        return false;
    }
    
    // Global caches update karo
    global $movie_messages, $movie_cache, $waiting_users;
    
    $movie_key = strtolower(trim($movie_name));
    $item = [
        'movie_name' => $movie_name,
        'message_id_raw' => $message_id_raw,
        'date' => $date,
        'video_path' => $video_path,
        'message_id' => is_numeric($message_id_raw) ? (int)$message_id_raw : null
    ];
    
    if (!isset($movie_messages[$movie_key])) {
        $movie_messages[$movie_key] = [];
    }
    $movie_messages[$movie_key][] = $item;
    
    // Clear cache
    $movie_cache = [];
    
    // Waiting users ko notify karo
    foreach ($waiting_users as $query => $users) {
        if (strpos($movie_key, $query) !== false) {
            foreach ($users as $user_data) {
                list($user_chat_id, $user_id) = $user_data;
                deliver_item_to_chat($user_chat_id, $item);
                sendMessage($user_chat_id, "✅ <b>'$query'</b> is now available!\n\n📢 " . MAIN_CHANNEL, null, 'HTML');
            }
            unset($waiting_users[$query]);
        }
    }
    
    update_stats('total_movies', 1);
    return true;
}

// -------------------- SEARCH FUNCTIONS --------------------
function smart_search($query) {
    global $movie_messages;
    
    $query_lower = strtolower(trim($query));
    
    // Agar cache empty hai toh load karo
    if (empty($movie_messages)) {
        load_and_clean_csv();
    }
    
    $results = [];
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        
        // Exact match - highest priority
        if ($movie == $query_lower) {
            $score = 100;
        }
        // Contains match - high priority
        elseif (strpos($movie, $query_lower) !== false) {
            $score = 80;
            
            // Jitna close match, utna high score
            $length_diff = abs(strlen($movie) - strlen($query_lower));
            $score -= min(20, $length_diff);
        }
        // Starts with - good priority
        elseif (strpos($movie, $query_lower) === 0) {
            $score = 70;
        }
        // Fuzzy match - low priority
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 65) {
                $score = (int)$similarity;
            }
        }
        
        if ($score > 0) {
            $results[$movie] = [
                'score' => $score,
                'count' => count($entries)
            ];
        }
    }
    
    // Sort by score descending
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Top 10 results
    return array_slice($results, 0, 10, true);
}

function detect_language($text) {
    $hindi_keywords = ['फिल्म', 'मूवी', 'डाउनलोड', 'हिंदी', 'चाहिए', 'देखो', 'वीडियो'];
    $english_keywords = ['movie', 'film', 'download', 'watch', 'video', 'hd', 'print', 'web'];
    
    $hindi_score = 0;
    $english_score = 0;
    
    foreach ($hindi_keywords as $keyword) {
        if (strpos($text, $keyword) !== false) $hindi_score++;
    }
    
    foreach ($english_keywords as $keyword) {
        if (stripos($text, $keyword) !== false) $english_score++;
    }
    
    return $hindi_score > $english_score ? 'hindi' : 'english';
}

function send_multilingual_response($chat_id, $message_type, $language) {
    $responses = [
        'hindi' => [
            'welcome' => "🎬 कौनसी मूवी चाहिए बॉस?",
            'searching' => "🔍 ढूंढ रहा हूं... ज़रा रुको",
            'found' => "✅ मिल गई! फॉरवर्ड कर रहा हूं...",
            'not_found' => "😔 ये मूवी अभी उपलब्ध नहीं है!\n\n📝 आप रिक्वेस्ट कर सकते हैं: " . REQUEST_CHANNEL . "\n\n🔔 जैसे ही ऐड होगी, मैं ऑटोमेटिक भेज दूंगा!",
            'invalid' => "❌ कृपया सही मूवी का नाम लिखें!\n\nउदाहरण: <b>kgf, pushpa, avengers</b>"
        ],
        'english' => [
            'welcome' => "🎬 Boss, which movie are you looking for?",
            'searching' => "🔍 Searching... Please wait",
            'found' => "✅ Found it! Forwarding the movie...",
            'not_found' => "😔 This movie isn't available yet!\n\n📝 You can request it here: " . REQUEST_CHANNEL . "\n\n🔔 I'll send it automatically once it's added!",
            'invalid' => "❌ Please enter a valid movie name!\n\nExamples: <b>kgf, pushpa, avengers</b>"
        ]
    ];
    
    return sendMessage($chat_id, $responses[$language][$message_type], null, 'HTML');
}

// -------------------- INVALID QUERY FILTER --------------------
function is_valid_movie_query($text) {
    $text = trim($text);
    
    // Commands are always allowed
    if (strpos($text, '/') === 0) {
        return true;
    }
    
    // Empty ya too short
    if (strlen($text) < 2) {
        return false;
    }
    
    // Skip pure numeric (message IDs, etc)
    if (is_numeric($text)) {
        return false;
    }
    
    // Common non-movie phrases - BLOCK KARO
    $invalid_phrases = [
        'good morning', 'good night', 'good afternoon', 'good evening',
        'hello', 'hi ', 'hey ', 'hy ', 'hii', 'heyy',
        'thank', 'thanks', 'thanku', 'thnx', 'welcome',
        'bye', 'tata', 'see you', 'cya',
        'ok', 'okay', 'k', 'okkk', 'yes', 'no', 'maybe',
        'how are you', 'whats up', 'sup', 'hows you',
        'anyone', 'someone', 'everyone', 'all',
        'problem', 'issue', 'help', 'pls help', 'doubt',
        'bro', 'bhai', 'dost', 'friend',
        'group', 'channel', 'link', 'invite',
        'admin', 'owner', 'mod', 'creator',
        'not working', 'not work', 'error', 'bug',
        'send', 'give', 'provide', 'need',
        'movie?', 'movie', 'films?', 'picture?',
        'hindi', 'english', 'tamil', 'telugu'
    ];
    
    $text_lower = strtolower($text);
    
    foreach ($invalid_phrases as $phrase) {
        if (strpos($text_lower, $phrase) !== false) {
            // Agar phrase "movie" hai toh block mat karo
            if ($phrase === 'movie' && $text_lower === 'movie') {
                return true; // "movie" alone is valid
            }
            // Exact match on short phrases
            if (strlen($phrase) <= 5 && $text_lower === $phrase) {
                return false;
            }
            // Long phrase match
            if (strlen($phrase) > 5 && strpos($text_lower, $phrase) !== false) {
                return false;
            }
        }
    }
    
    // Valid movie patterns - ALLOW KARO
    $valid_patterns = [
        '^[a-zA-Z0-9\s\-\.\,\&\+\:\'\"]{2,}$',  // Basic movie name
        '.*(movie|film|series|season|part|episode).*',
        '.*(1080p|720p|480p|hd|hdr|x264|x265|hevc).*',  // Quality tags
        '.*(hindi|english|tamil|telugu|malayalam|kannada).*', // Languages
        '.*\(\d{4}\).*',  // Year in brackets
        '.*\d{4}.*',      // Year anywhere
    ];
    
    foreach ($valid_patterns as $pattern) {
        if (preg_match('/' . $pattern . '/i', $text)) {
            return true;
        }
    }
    
    // By default - allow agar 3+ characters hai
    return strlen($text) >= 3;
}

// -------------------- USER POINTS & TRACKING --------------------
function update_user_points($user_id, $action) {
    $points_map = [
        'search' => 1,
        'found_movie' => 5,
        'daily_login' => 10,
        'request_movie' => 2,
        'share_bot' => 15
    ];
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    if (!is_array($users_data)) {
        $users_data = ['users' => []];
    }
    
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = ['points' => 0];
    }
    
    $users_data['users'][$user_id]['points'] = ($users_data['users'][$user_id]['points'] ?? 0) + ($points_map[$action] ?? 0);
    $users_data['users'][$user_id]['last_activity'] = date('Y-m-d H:i:s');
    $users_data['last_updated'] = date('Y-m-d H:i:s');
    
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// -------------------- ADVANCED SEARCH --------------------
function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages, $waiting_users;
    
    $query = trim($query);
    $query_lower = strtolower($query);
    
    // Minimum length check
    if (strlen($query) < 2) {
        $lang = detect_language($query);
        send_multilingual_response($chat_id, 'invalid', $lang);
        return;
    }
    
    // Invalid query check
    if (!is_valid_movie_query($query)) {
        $lang = detect_language($query);
        send_multilingual_response($chat_id, 'invalid', $lang);
        return;
    }
    
    // Search karo
    $found = smart_search($query_lower);
    
    if (!empty($found)) {
        // Search results bhejo
        $msg = "🔍 <b>Found " . count($found) . " movies</b> for '<i>" . htmlspecialchars($query) . "</i>':\n\n";
        
        $i = 1;
        foreach ($found as $movie => $data) {
            $display_name = htmlspecialchars($movie);
            if (mb_strlen($display_name) > 40) {
                $display_name = mb_substr($display_name, 0, 37) . '...';
            }
            $msg .= "$i. <b>{$display_name}</b> ({$data['count']})\n";
            $i++;
            if ($i > 10) break;
        }
        
        sendMessage($chat_id, $msg, null, 'HTML');
        
        // Inline keyboard banao - top 5 movies
        $keyboard = ['inline_keyboard' => []];
        
        foreach (array_slice(array_keys($found), 0, 5) as $movie) {
            $display_name = htmlspecialchars($movie);
            if (mb_strlen($display_name) > 30) {
                $display_name = mb_substr($display_name, 0, 27) . '...';
            }
            $keyboard['inline_keyboard'][] = [
                ['text' => "🎬 " . $display_name, 'callback_data' => $movie]
            ];
        }
        
        // More results button
        if (count($found) > 5) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🔍 Show All " . count($found) . " Results", 'callback_data' => 'search_' . $query_lower]
            ];
        }
        
        sendMessage($chat_id, "🎯 <b>Top Matches:</b>", $keyboard, 'HTML');
        
        if ($user_id) {
            update_user_points($user_id, 'found_movie');
        }
        
        update_stats('total_searches', 1);
        
    } else {
        // Movie nahi mili
        $lang = detect_language($query);
        send_multilingual_response($chat_id, 'not_found', $lang);
        
        // Waiting list me add karo
        if (!isset($waiting_users[$query_lower])) {
            $waiting_users[$query_lower] = [];
        }
        
        $waiting_users[$query_lower][] = [$chat_id, $user_id ?? $chat_id];
        
        // Limit waiting list size per query (max 50 users)
        if (count($waiting_users[$query_lower]) > 50) {
            array_shift($waiting_users[$query_lower]);
        }
        
        update_user_points($user_id ?? $chat_id, 'request_movie');
        update_stats('total_requests', 1);
    }
}

// -------------------- ADMIN STATS --------------------
function admin_stats($chat_id, $user_id) {
    // Sirf admin access
    if ($user_id != ADMIN_ID) {
        sendMessage($chat_id, "❌ Unauthorized. This command is for admin only.", null, 'HTML');
        return;
    }
    
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    $total_users = count($users_data['users'] ?? []);
    $total_requests = $users_data['total_requests'] ?? 0;
    
    // Calculate active users today
    $today = date('Y-m-d');
    $active_today = 0;
    
    foreach ($users_data['users'] ?? [] as $uid => $udata) {
        if (isset($udata['last_active']) && strpos($udata['last_active'], $today) === 0) {
            $active_today++;
        }
    }
    
    // CSV stats
    $csv_data = get_cached_movies();
    $total_movies_db = count($csv_data);
    
    $msg = "📊 <b>Bot Statistics</b>\n\n";
    $msg .= "🎬 <b>Movies:</b>\n";
    $msg .= "• Total Movies: <b>" . number_format($stats['total_movies'] ?? 0) . "</b>\n";
    $msg .= "• CSV Count: <b>" . number_format($total_movies_db) . "</b>\n\n";
    
    $msg .= "👥 <b>Users:</b>\n";
    $msg .= "• Total Users: <b>" . number_format($total_users) . "</b>\n";
    $msg .= "• Active Today: <b>" . number_format($active_today) . "</b>\n\n";
    
    $msg .= "🔍 <b>Usage:</b>\n";
    $msg .= "• Total Searches: <b>" . number_format($stats['total_searches'] ?? 0) . "</b>\n";
    $msg .= "• Total Requests: <b>" . number_format($total_requests) . "</b>\n\n";
    
    $msg .= "🕒 <b>Last Updated:</b>\n";
    $msg .= "• Stats: " . ($stats['last_updated'] ?? 'N/A') . "\n";
    $msg .= "• Users: " . ($users_data['last_updated'] ?? 'N/A') . "\n\n";
    
    // Recent uploads
    $recent = array_slice($csv_data, -10);
    $msg .= "📈 <b>Recent Uploads (Last 10):</b>\n";
    
    foreach ($recent as $r) {
        $name = htmlspecialchars($r['movie_name'] ?? 'Unknown');
        if (mb_strlen($name) > 30) $name = mb_substr($name, 0, 27) . '...';
        $msg .= "• {$name} <i>({$r['date']})</i>\n";
    }
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

// -------------------- CHECK CSV --------------------
function show_csv_data($chat_id, $show_all = false, $user_id = null) {
    // Sirf admin ko full access
    $is_admin = ($user_id == ADMIN_ID);
    
    if (!file_exists(CSV_FILE)) {
        sendMessage($chat_id, "❌ CSV file not found.");
        return;
    }
    
    $handle = fopen(CSV_FILE, "r");
    if ($handle === false) {
        sendMessage($chat_id, "❌ Error opening CSV file.");
        return;
    }
    
    // Skip header
    fgetcsv($handle);
    
    $movies = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 3 && !empty(trim($row[0] ?? ''))) {
            $movies[] = $row;
        }
    }
    fclose($handle);
    
    if (empty($movies)) {
        sendMessage($chat_id, "📊 CSV file is empty.");
        return;
    }
    
    // Reverse chronological
    $movies = array_reverse($movies);
    
    // Non-admin ko sirf 5 recent dikhao
    $limit = 5;
    if ($is_admin && $show_all) {
        $limit = count($movies);
    } elseif ($is_admin) {
        $limit = 20;
    }
    
    $movies_to_show = array_slice($movies, 0, $limit);
    
    $message = "📊 <b>Movie Database</b>\n\n";
    $message .= "📁 Total Movies: <b>" . count($movies) . "</b>\n";
    
    if (!$is_admin) {
        $message .= "👤 Showing latest 5 entries\n\n";
    } elseif (!$show_all) {
        $message .= "🔍 Showing latest 20 entries\n";
        $message .= "📋 Use <code>/checkcsv all</code> for full list\n\n";
    } else {
        $message .= "📋 Full database listing (" . count($movies) . " entries)\n\n";
    }
    
    $i = 1;
    foreach ($movies_to_show as $movie) {
        $movie_name = htmlspecialchars($movie[0] ?? 'N/A');
        $date = $movie[2] ?? 'N/A';
        
        if (mb_strlen($movie_name) > 50) {
            $movie_name = mb_substr($movie_name, 0, 47) . '...';
        }
        
        $message .= "$i. 🎬 <b>{$movie_name}</b>\n";
        $message .= "   📅 {$date}\n\n";
        
        $i++;
        
        // Telegram message limit
        if (strlen($message) > 3500) {
            sendMessage($chat_id, $message, null, 'HTML');
            $message = "📊 <b>Continuing...</b>\n\n";
        }
    }
    
    $message .= "💾 File: " . basename(CSV_FILE) . "\n";
    $message .= "⏰ Last Updated: " . date('Y-m-d H:i:s', filemtime(CSV_FILE));
    
    sendMessage($chat_id, $message, null, 'HTML');
}

// -------------------- CHECK DATE --------------------
function check_date($chat_id) {
    if (!file_exists(CSV_FILE)) {
        sendMessage($chat_id, "⚠️ No data saved yet.", null, 'HTML');
        return;
    }
    
    $date_counts = [];
    
    $handle = fopen(CSV_FILE, 'r');
    if ($handle === false) {
        sendMessage($chat_id, "❌ Cannot read CSV file.");
        return;
    }
    
    fgetcsv($handle); // Skip header
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 3) {
            $date = $row[2];
            if (!isset($date_counts[$date])) {
                $date_counts[$date] = 0;
            }
            $date_counts[$date]++;
        }
    }
    
    fclose($handle);
    
    // Sort by date (newest first)
    uksort($date_counts, function($a, $b) {
        $a_ts = DateTime::createFromFormat('d-m-Y', $a) ?: new DateTime();
        $b_ts = DateTime::createFromFormat('d-m-Y', $b) ?: new DateTime();
        return $b_ts <=> $a_ts;
    });
    
    $msg = "📅 <b>Movies Upload Record</b>\n\n";
    
    $total_days = 0;
    $total_movies = 0;
    
    foreach ($date_counts as $date => $count) {
        $msg .= "➡️ <b>$date</b>: $count movies\n";
        $total_days++;
        $total_movies += $count;
        
        if (strlen($msg) > 3500) {
            $msg .= "\n... and more";
            break;
        }
    }
    
    $msg .= "\n\n📊 <b>Summary:</b>\n";
    $msg .= "• Total Days: $total_days\n";
    $msg .= "• Total Movies: $total_movies\n";
    $msg .= "• Average: " . round($total_movies / max(1, $total_days), 2) . " per day";
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

// -------------------- BACKUP & DIGEST --------------------
function auto_backup() {
    $backup_files = [CSV_FILE, USERS_FILE, STATS_FILE];
    $backup_dir = BACKUP_DIR . date('Y-m-d');
    
    if (!file_exists($backup_dir)) {
        @mkdir($backup_dir, 0777, true);
    }
    
    foreach ($backup_files as $file) {
        if (file_exists($file)) {
            @copy($file, $backup_dir . '/' . basename($file) . '.bak');
        }
    }
    
    // Keep only last 7 days
    $old_backups = glob(BACKUP_DIR . '*', GLOB_ONLYDIR);
    
    if (count($old_backups) > 7) {
        usort($old_backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $to_delete = array_slice($old_backups, 0, count($old_backups) - 7);
        
        foreach ($to_delete as $dir) {
            $files = glob($dir . '/*');
            foreach ($files as $f) @unlink($f);
            @rmdir($dir);
        }
    }
    
    return true;
}

function send_daily_digest() {
    $yesterday = date('d-m-Y', strtotime('-1 day'));
    $yesterday_movies = [];
    
    $handle = fopen(CSV_FILE, "r");
    if ($handle === false) return;
    
    fgetcsv($handle); // Skip header
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 3 && isset($row[2]) && $row[2] == $yesterday) {
            $yesterday_movies[] = $row[0];
        }
    }
    
    fclose($handle);
    
    if (empty($yesterday_movies)) return;
    
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    $msg = "📅 <b>Daily Movie Digest</b>\n\n";
    $msg .= "📢 <b>" . MAIN_CHANNEL . "</b>\n\n";
    $msg .= "🎬 <b>Yesterday's Uploads (" . $yesterday . "):</b>\n";
    
    $count = 0;
    foreach (array_slice($yesterday_movies, 0, 15) as $movie) {
        $movie_name = htmlspecialchars($movie);
        if (mb_strlen($movie_name) > 40) {
            $movie_name = mb_substr($movie_name, 0, 37) . '...';
        }
        $msg .= "• {$movie_name}\n";
        $count++;
    }
    
    if (count($yesterday_movies) > 15) {
        $msg .= "• ... and " . (count($yesterday_movies) - 15) . " more\n";
    }
    
    $msg .= "\n🔥 <b>Total:</b> " . count($yesterday_movies) . " movies\n";
    $msg .= "\n🔍 <b>Search any movie:</b> @" . getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot';
    
    // Sirf active users ko bhejo (last 7 days active)
    $sent_count = 0;
    foreach ($users_data['users'] ?? [] as $uid => $udata) {
        $last_active = $udata['last_active'] ?? '';
        $last_week = date('Y-m-d', strtotime('-7 days'));
        
        if ($last_active >= $last_week) {
            sendMessage($uid, $msg, null, 'HTML');
            $sent_count++;
            
            // Rate limit avoid
            usleep(50000);
        }
    }
    
    // Admin ko bhejo
    if (defined('ADMIN_ID') && ADMIN_ID > 0) {
        $admin_msg = "📊 <b>Daily Digest Report</b>\n\n";
        $admin_msg .= "📅 Date: $yesterday\n";
        $admin_msg .= "🎬 Movies Added: " . count($yesterday_movies) . "\n";
        $admin_msg .= "👥 Digest Sent: $sent_count users\n";
        
        sendMessage(ADMIN_ID, $admin_msg, null, 'HTML');
    }
}

// -------------------- WEBHOOK SETUP --------------------
function setupWebhook() {
    // Sirf admin ya CLI se allow karo
    if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== getenv('WEBHOOK_TOKEN'))) {
        echo "❌ Unauthorized. Webhook token required.";
        return;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $webhook_url = $protocol . "://" . $host . "/index.php";
    
    $result = apiRequest('setWebhook', ['url' => $webhook_url, 'max_connections' => 40]);
    
    if ($result !== false) {
        $response = json_decode($result, true);
        
        if ($response && $response['ok'] === true) {
            echo "✅ Webhook set successfully!\n";
            echo "URL: " . $webhook_url . "\n";
            
            // Get bot info
            $bot_info = json_decode(apiRequest('getMe'), true);
            if ($bot_info && $bot_info['ok']) {
                echo "🤖 Bot: @" . $bot_info['result']['username'] . "\n";
            }
        } else {
            echo "❌ Webhook failed: " . ($response['description'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ API request failed!\n";
    }
}

// -------------------- MAIN UPDATE HANDLER --------------------
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    // Log incoming update (development only)
    if (getenv('APP_ENV') === 'development') {
        error_log("Update: " . json_encode($update, JSON_PARTIAL_OUTPUT_ON_ERROR));
    }
    
    // Cache load karo
    get_cached_movies();
    
    // -------------------- CHANNEL POST HANDLER --------------------
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        // Sirf configured channel se accept karo
        if ($chat_id == CHANNEL_ID) {
            $text = '';
            
            if (isset($message['caption']) && !empty(trim($message['caption']))) {
                $text = trim($message['caption']);
            } elseif (isset($message['text']) && !empty(trim($message['text']))) {
                $text = trim($message['text']);
            } elseif (isset($message['document']) && isset($message['document']['file_name'])) {
                $text = trim($message['document']['file_name']);
            } else {
                $text = 'Media ' . date('d-m-Y H:i');
            }
            
            if (!empty($text)) {
                append_movie($text, $message_id, date('d-m-Y'), '');
            }
        }
    }
    
    // -------------------- MESSAGE HANDLER --------------------
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? trim($message['text']) : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        // -------------------- USER TRACKING --------------------
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        
        if (!is_array($users_data)) {
            $users_data = ['users' => []];
        }
        
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'last_name' => $message['from']['last_name'] ?? '',
                'username' => $message['from']['username'] ?? '',
                'language_code' => $message['from']['language_code'] ?? '',
                'joined' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s'),
                'points' => 0,
                'total_searches' => 0
            ];
            $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
            update_stats('total_users', 1);
        } else {
            $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
            $users_data['users'][$user_id]['first_name'] = $message['from']['first_name'] ?? $users_data['users'][$user_id]['first_name'];
            $users_data['users'][$user_id]['username'] = $message['from']['username'] ?? $users_data['users'][$user_id]['username'];
        }
        
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // -------------------- GROUP MESSAGE FILTER --------------------
        if ($chat_type !== 'private') {
            // Commands allow karo
            if (strpos($text, '/') !== 0) {
                // Non-command messages - filter karo
                if (!is_valid_movie_query($text)) {
                    // Silent ignore
                    exit;
                }
            }
        }
        
        // -------------------- COMMAND HANDLER --------------------
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = strtolower($parts[0]);
            $param = $parts[1] ?? '';
            
            switch ($command) {
                case '/start':
                    $welcome = "🎬 <b>Welcome to Entertainment Tadka Bot!</b>\n\n";
                    $welcome .= "📢 <b>How to use:</b>\n";
                    $welcome .= "• Simply type any <b>movie name</b>\n";
                    $welcome .= "• Works in <b>English/Hindi</b>\n";
                    $welcome .= "• Partial names also work\n\n";
                    $welcome .= "🔍 <b>Examples:</b>\n";
                    $welcome .= "• <code>kgf</code>\n";
                    $welcome .= "• <code>pushpa</code>\n";
                    $welcome .= "• <code>avengers</code>\n";
                    $welcome .= "• <code>spider man</code>\n\n";
                    $welcome .= "📢 <b>Join Channel:</b> " . MAIN_CHANNEL . "\n";
                    $welcome .= "💬 <b>Request/Help:</b> " . REQUEST_CHANNEL . "\n\n";
                    $welcome .= "✅ <i>Bot is live with " . count(get_cached_movies()) . "+ movies!</i>";
                    
                    sendMessage($chat_id, $welcome, null, 'HTML');
                    update_user_points($user_id, 'daily_login');
                    break;
                    
                case '/help':
                    $help = "🤖 <b>Entertainment Tadka Bot Help</b>\n\n";
                    $help .= "📢 <b>Channel:</b> " . MAIN_CHANNEL . "\n\n";
                    $help .= "📋 <b>Commands:</b>\n";
                    $help .= "• /start - Welcome message\n";
                    $help .= "• /checkdate - Date-wise stats\n";
                    $help .= "• /totalupload - Upload statistics\n";
                    $help .= "• /checkcsv - View recent movies\n";
                    $help .= "• /help - This message\n\n";
                    $help .= "🔍 <b>Just type movie name to search!</b>\n\n";
                    $help .= "💬 <b>Request:</b> " . REQUEST_CHANNEL;
                    
                    sendMessage($chat_id, $help, null, 'HTML');
                    break;
                    
                case '/checkdate':
                    check_date($chat_id);
                    break;
                    
                case '/totalupload':
                case '/totaluploads':
                    totalupload_controller($chat_id, 1);
                    break;
                    
                case '/checkcsv':
                    $show_all = (strtolower($param) === 'all');
                    show_csv_data($chat_id, $show_all, $user_id);
                    break;
                    
                case '/stats':
                    admin_stats($chat_id, $user_id);
                    break;
                    
                case '/backup':
                    if ($user_id == ADMIN_ID) {
                        $result = auto_backup();
                        if ($result) {
                            sendMessage($chat_id, "✅ Backup created successfully!", null, 'HTML');
                        } else {
                            sendMessage($chat_id, "❌ Backup failed!", null, 'HTML');
                        }
                    }
                    break;
                    
                default:
                    // Unknown command - ignore
                    break;
            }
        }
        // -------------------- NON-COMMAND (MOVIE SEARCH) --------------------
        elseif (!empty($text)) {
            $lang = detect_language($text);
            send_multilingual_response($chat_id, 'searching', $lang);
            advanced_search($chat_id, $text, $user_id);
            
            // Update user search count
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            if (isset($users_data['users'][$user_id])) {
                $users_data['users'][$user_id]['total_searches'] = ($users_data['users'][$user_id]['total_searches'] ?? 0) + 1;
                $users_data['users'][$user_id]['last_search'] = date('Y-m-d H:i:s');
                $users_data['users'][$user_id]['last_query'] = $text;
                file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    // -------------------- CALLBACK QUERY HANDLER --------------------
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $message_id = $message['message_id'];
        $data = $query['data'];
        $callback_id = $query['id'];
        
        global $movie_messages;
        
        // Movie selection callback
        $movie_lower = strtolower($data);
        if (isset($movie_messages[$movie_lower])) {
            $entries = $movie_messages[$movie_lower];
            $sent = 0;
            $total = count($entries);
            
            // Typing action bhejo
            apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => 'typing']);
            
            foreach ($entries as $entry) {
                if (deliver_item_to_chat($chat_id, $entry)) {
                    $sent++;
                }
                usleep(MOVIE_DELAY);
            }
            
            $msg = "✅ <b>" . htmlspecialchars($data) . "</b>\n";
            $msg .= "📤 Forwarded: <b>{$sent}/{$total}</b> files\n\n";
            $msg .= "📢 " . MAIN_CHANNEL;
            
            sendMessage($chat_id, $msg, null, 'HTML');
            answerCallbackQuery($callback_id, "🎬 $sent movies sent!", false);
            
            update_user_points($chat_id, 'found_movie');
        }
        // Total Upload Pagination
        elseif (strpos($data, 'tu_prev_') === 0) {
            $page = (int)str_replace('tu_prev_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($callback_id, "📄 Page $page");
        }
        elseif (strpos($data, 'tu_next_') === 0) {
            $page = (int)str_replace('tu_next_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($callback_id, "📄 Page $page");
        }
        elseif (strpos($data, 'tu_view_') === 0) {
            $page = (int)str_replace('tu_view_', '', $data);
            $all = get_all_movies_list();
            $pg = paginate_movies($all, $page);
            $sent = forward_page_movies($chat_id, $pg['slice']);
            answerCallbackQuery($callback_id, "✅ Re-sent $sent movies");
        }
        elseif ($data === 'tu_stop') {
            sendMessage($chat_id, "✅ Pagination stopped.\nType /totalupload to start again.", null, 'HTML');
            answerCallbackQuery($callback_id, "🛑 Stopped");
        }
        elseif ($data === 'current_page') {
            answerCallbackQuery($callback_id, "📍 Current page");
        }
        elseif (strpos($data, 'search_') === 0) {
            $search_query = substr($data, 7);
            advanced_search($chat_id, $search_query, $chat_id);
            answerCallbackQuery($callback_id, "🔍 Showing all results");
        }
        else {
            // Movie not found
            sendMessage($chat_id, "❌ Movie not found: <b>" . htmlspecialchars($data) . "</b>\n\nYou can request it at " . REQUEST_CHANNEL, null, 'HTML');
            answerCallbackQuery($callback_id, "❌ Not available", true);
        }
    }
    
    // -------------------- CRON JOBS (TIME-BASED) --------------------
    $current_hour = date('H');
    $current_minute = date('i');
    
    // Midnight backup (00:00)
    if ($current_hour == '00' && $current_minute == '00') {
        auto_backup();
    }
    
    // Daily digest (08:00)
    if ($current_hour == '08' && $current_minute == '00') {
        send_daily_digest();
    }
}
// -------------------- END OF UPDATE HANDLER --------------------

// -------------------- WEBHOOK SETUP PAGE --------------------
elseif (isset($_GET['setwebhook'])) {
    setupWebhook();
    exit;
}

// -------------------- STATUS PAGE --------------------
else {
    // Simple status page
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $movies = get_cached_movies();
    
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Entertainment Tadka Bot</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #1a1e24; color: #e4e6eb; line-height: 1.6; }
        .container { background: #242a32; border-radius: 16px; padding: 30px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); border: 1px solid #3a4049; }
        h1 { color: #ffd966; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .status-badge { background: #00a884; color: white; padding: 6px 14px; border-radius: 30px; font-size: 14px; font-weight: bold; display: inline-block; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 25px 0; }
        .stat-card { background: #1e242c; padding: 16px; border-radius: 12px; border-left: 4px solid #ffd966; }
        .stat-value { font-size: 28px; font-weight: bold; color: #ffd966; }
        .stat-label { font-size: 14px; color: #a0a8b3; text-transform: uppercase; letter-spacing: 1px; }
        .btn { background: #3a4049; color: white; border: none; padding: 10px 20px; border-radius: 30px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; transition: 0.2s; }
        .btn:hover { background: #4a515c; }
        .btn-primary { background: #ffd966; color: #1a1e24; font-weight: bold; }
        .btn-primary:hover { background: #ffe082; }
        hr { border: none; border-top: 1px solid #3a4049; margin: 25px 0; }
        .footer { margin-top: 30px; font-size: 13px; color: #8e96a0; text-align: center; }
        code { background: #0f1217; padding: 3px 8px; border-radius: 6px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span>🎬 Entertainment Tadka Bot</span>
            <span class="status-badge">✅ LIVE</span>
        </h1>
        
        <p style="font-size: 16px; margin-bottom: 10px;">
            📢 <b>Channel:</b> <?= MAIN_CHANNEL ?> &nbsp;|&nbsp; 
            💬 <b>Request:</b> <?= REQUEST_CHANNEL ?>
        </p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_movies'] ?? count($movies) ?? 0) ?></div>
                <div class="stat-label">🎬 Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format(count($users_data['users'] ?? [])) ?></div>
                <div class="stat-label">👥 Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_searches'] ?? 0) ?></div>
                <div class="stat-label">🔍 Searches</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($users_data['total_requests'] ?? 0) ?></div>
                <div class="stat-label">📝 Requests</div>
            </div>
        </div>
        
        <div style="margin: 25px 0;">
            <a href="?setwebhook=1&token=<?= htmlspecialchars(getenv('WEBHOOK_TOKEN') ?: '') ?>" class="btn btn-primary">🔧 Set Webhook</a>
            <a href="https://t.me/<?= getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot' ?>" class="btn" target="_blank">📱 Open Bot</a>
            <a href="<?= MAIN_CHANNEL ?>" class="btn" target="_blank">📢 Join Channel</a>
        </div>
        
        <hr>
        
        <h3>📊 System Info</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr><td style="padding: 8px 0;"><b>PHP Version:</b></td><td><?= phpversion() ?></td></tr>
            <tr><td style="padding: 8px 0;"><b>Last Updated:</b></td><td><?= $stats['last_updated'] ?? 'N/A' ?></td></tr>
            <tr><td style="padding: 8px 0;"><b>Cache Expiry:</b></td><td><?= CACHE_EXPIRY ?>s</td></tr>
            <tr><td style="padding: 8px 0;"><b>Items Per Page:</b></td><td><?= ITEMS_PER_PAGE ?></td></tr>
            <tr><td style="padding: 8px 0;"><b>CSV File:</b></td><td><?= basename(CSV_FILE) ?> (<?= number_format(filesize(CSV_FILE) ?? 0) ?> bytes)</td></tr>
            <tr><td style="padding: 8px 0;"><b>Maintenance Mode:</b></td><td><span style="color: #00a884; font-weight: bold;">OFF</span></td></tr>
        </table>
        
        <hr>
        
        <h3>📋 Available Commands</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <code>/start</code>
            <code>/checkdate</code>
            <code>/totalupload</code>
            <code>/checkcsv</code>
            <code>/help</code>
            <?php if (isset($_GET['admin']) && $_GET['admin'] == ADMIN_ID): ?>
            <code>/stats</code>
            <code>/backup</code>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 25px;">
            <b>🔍 Just type any movie name to search!</b><br>
            <small style="color: #a0a8b3;">Example: kgf, pushpa, avengers, spider man</small>
        </p>
        
        <div class="footer">
            <p>🤖 Entertainment Tadka Bot | Version 3.0 | © 2026</p>
            <p style="margin-top: 10px; font-size: 12px;">🛡️ All tokens are environment protected | Made with ❤️ for movie lovers</p>
        </div>
    </div>
</body>
</html>
<?php
}

// -------------------- CRON JOB ENDPOINT (Optional) --------------------
if (isset($_GET['cron']) && $_GET['cron'] === getenv('CRON_SECRET')) {
    if (isset($_GET['job']) && $_GET['job'] === 'backup') {
        auto_backup();
        echo "✅ Backup completed at " . date('Y-m-d H:i:s');
    } elseif (isset($_GET['job']) && $_GET['job'] === 'digest') {
        send_daily_digest();
        echo "✅ Daily digest sent at " . date('Y-m-d H:i:s');
    } else {
        echo "❌ Unknown job";
    }
    exit;
}

// -------------------- THE END --------------------
?>
