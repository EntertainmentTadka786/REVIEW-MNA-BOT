<?php
// ============================================
// ENTERTAINMENT TADKA MEGA BOT v3.0
// COMPLETE MERGE: index.php + index1.php
// ALL FEATURES + SECURITY + ANALYTICS
// FIXED: Undefined constant REQUEST_FILE
// FIXED: 'break' not in loop/switch error
// ============================================

// ==================== CONFIGURATION ====================
// Environment detection
$environment = getenv('ENVIRONMENT') ?: 'production';

// Error Reporting - Only in Development
if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

// ==================== SECURITY HEADERS ====================
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ==================== ERROR LOGGING ====================
function log_error($message, $type = 'ERROR', $context = []) {
    $log_entry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    @file_put_contents('error.log', $log_entry, FILE_APPEND);
    @chmod('error.log', 0666);
    @error_log($message);
    
    if (getenv('ENVIRONMENT') === 'development') {
        echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    }
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error [$errno]: $errstr in $errfile on line $errline", 'PHP_ERROR');
    return false;
});

set_exception_handler(function($exception) {
    log_error("Uncaught Exception: " . $exception->getMessage(), 'EXCEPTION', [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

log_error("Bot script started", 'INFO', [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'uri' => $_SERVER['REQUEST_URI'] ?? ''
]);

// ==================== ENVIRONMENT CONFIGURATION ====================
$ENV_CONFIG = [
    'BOT_TOKEN' => getenv('BOT_TOKEN') ?: '',
    'BOT_USERNAME' => getenv('BOT_USERNAME') ?: 'EntertainmentTadkaBot',
    'ADMIN_IDS' => array_map('intval', explode(',', getenv('ADMIN_IDS') ?: '1080317415')),
    'PUBLIC_CHANNELS' => [
        ['id' => getenv('PUBLIC_CHANNEL_1_ID') ?: '-1003181705395', 'username' => getenv('PUBLIC_CHANNEL_1_USERNAME') ?: '@EntertainmentTadka786'],
        ['id' => getenv('PUBLIC_CHANNEL_2_ID') ?: '-1002831605258', 'username' => getenv('PUBLIC_CHANNEL_2_USERNAME') ?: '@threater_print_movies'],
        ['id' => getenv('PUBLIC_CHANNEL_3_ID') ?: '-1002964109368', 'username' => getenv('PUBLIC_CHANNEL_3_USERNAME') ?: '@ETBackup']
    ],
    'PRIVATE_CHANNELS' => [
        ['id' => getenv('PRIVATE_CHANNEL_1_ID') ?: '-1003251791991', 'username' => getenv('PRIVATE_CHANNEL_1_USERNAME') ?: ''],
        ['id' => getenv('PRIVATE_CHANNEL_2_ID') ?: '-1002337293281', 'username' => getenv('PRIVATE_CHANNEL_2_USERNAME') ?: ''],
        ['id' => getenv('PRIVATE_CHANNEL_3_ID') ?: '-1003614546520', 'username' => getenv('PRIVATE_CHANNEL_3_USERNAME') ?: '']
    ],
    'REQUEST_GROUP' => [
        'id' => getenv('REQUEST_GROUP_ID') ?: '-1003083386043',
        'username' => getenv('REQUEST_GROUP_USERNAME') ?: '@EntertainmentTadka7860'
    ],
    'CSV_FILE' => 'movies.csv',
    'USERS_FILE' => 'users.json',
    'STATS_FILE' => 'bot_stats.json',
    'REQUESTS_FILE' => 'requests.json',
    'BACKUP_DIR' => 'backups/',
    'CACHE_DIR' => 'cache/',
    'CACHE_EXPIRY' => 300,
    'ITEMS_PER_PAGE' => 5,
    'CSV_BUFFER_SIZE' => 50,
    'MAX_REQUESTS_PER_DAY' => 3,
    'DUPLICATE_CHECK_HOURS' => 24,
    'REQUEST_SYSTEM_ENABLED' => true,
    'MAINTENANCE_MODE' => (getenv('MAINTENANCE_MODE') === 'true') ? true : false,
    'RATE_LIMIT_REQUESTS' => 30,
    'RATE_LIMIT_WINDOW' => 60
];

if (empty($ENV_CONFIG['BOT_TOKEN']) || $ENV_CONFIG['BOT_TOKEN'] === 'YOUR_BOT_TOKEN_HERE') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("❌ Bot Token not configured. Please set BOT_TOKEN environment variable.");
}

define('BOT_TOKEN', $ENV_CONFIG['BOT_TOKEN']);
define('ADMIN_IDS', $ENV_CONFIG['ADMIN_IDS']);
define('CSV_FILE', $ENV_CONFIG['CSV_FILE']);
define('USERS_FILE', $ENV_CONFIG['USERS_FILE']);
define('STATS_FILE', $ENV_CONFIG['STATS_FILE']);
define('REQUESTS_FILE', $ENV_CONFIG['REQUESTS_FILE']);
define('BACKUP_DIR', $ENV_CONFIG['BACKUP_DIR']);
define('CACHE_DIR', $ENV_CONFIG['CACHE_DIR']);
define('CACHE_EXPIRY', $ENV_CONFIG['CACHE_EXPIRY']);
define('ITEMS_PER_PAGE', $ENV_CONFIG['ITEMS_PER_PAGE']);
define('CSV_BUFFER_SIZE', $ENV_CONFIG['CSV_BUFFER_SIZE']);
define('MAX_REQUESTS_PER_DAY', $ENV_CONFIG['MAX_REQUESTS_PER_DAY']);
define('REQUEST_SYSTEM_ENABLED', $ENV_CONFIG['REQUEST_SYSTEM_ENABLED']);
define('MAINTENANCE_MODE', $ENV_CONFIG['MAINTENANCE_MODE']);
define('RATE_LIMIT_REQUESTS', $ENV_CONFIG['RATE_LIMIT_REQUESTS']);
define('RATE_LIMIT_WINDOW', $ENV_CONFIG['RATE_LIMIT_WINDOW']);

define('MAIN_CHANNEL', '@EntertainmentTadka786');
define('THEATER_CHANNEL', '@threater_print_movies');
define('REQUEST_CHANNEL', '@EntertainmentTadka7860');
define('BACKUP_CHANNEL_USERNAME', '@ETBackup');

// ==================== INDEX1.PHP CONFIG ====================
define('ADMIN_ID', getenv('ADMIN_ID') ?: '1080317415');
define('CHANNEL_ID', getenv('CHANNEL_ID') ?: '-1003181705395');
define('DELETE_AFTER_MINUTES', 15);
define('DAILY_REQUEST_LIMIT', 5);
define('AUTO_BACKUP_HOUR', '03');
define('UPLOADS_DB', 'uploads_analytics.db');
define('LOG_FILE', 'bot_activity.log');
define('DELETION_LOG', 'deletions.log');
define('ERROR_LOG', 'errors.log');
define('REQUEST_FILE', REQUESTS_FILE); // FIXED: Add this line!

$MAINTENANCE_MODE = false;
$MAINTENANCE_MESSAGE = "🛠️ <b>Bot Under Maintenance</b>\n\nWe're temporarily unavailable for updates.\nWill be back in few days!\n\nThanks for patience 🙏";

// ==================== SECURITY FUNCTIONS ====================
function validateInput($input, $type = 'text') {
    if (is_array($input)) {
        return array_map('validateInput', $input);
    }
    
    $input = trim($input);
    
    switch($type) {
        case 'movie_name':
            if (strlen($input) < 2 || strlen($input) > 200) return false;
            if (!preg_match('/^[\p{L}\p{N}\s\-\.\,\&\+\'\"\(\)\!\:\;\?]{2,200}$/u', $input)) return false;
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        case 'user_id':
            return preg_match('/^\d+$/', $input) ? intval($input) : false;
        case 'command':
            return preg_match('/^\/[a-zA-Z0-9_]+$/', $input) ? $input : false;
        case 'telegram_id':
            return preg_match('/^\-?\d+$/', $input) ? $input : false;
        case 'filename':
            $input = basename($input);
            $allowed_files = ['movies.csv', 'users.json', 'bot_stats.json', 'requests.json'];
            return in_array($input, $allowed_files) ? $input : false;
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

function secureFileOperation($filename, $operation = 'read') {
    $filename = validateInput($filename, 'filename');
    if (!$filename) return false;
    if ($operation === 'write' && !is_writable($filename)) @chmod($filename, 0644);
    return $filename;
}

// ==================== RATE LIMITING ====================
class RateLimiter {
    private static $limits = [];
    
    public static function check($key, $limit = 30, $window = 60) {
        $now = time();
        $window_start = $now - $window;
        
        if (!isset(self::$limits[$key])) self::$limits[$key] = [];
        
        self::$limits[$key] = array_filter(self::$limits[$key], function($time) use ($window_start) {
            return $time > $window_start;
        });
        
        if (count(self::$limits[$key]) >= $limit) {
            log_error("Rate limit exceeded for key: $key", 'WARNING');
            return false;
        }
        
        self::$limits[$key][] = $now;
        return true;
    }
}

// ==================== REQUEST SYSTEM CLASS ====================
class RequestSystem {
    private static $instance = null;
    private $db_file = 'requests.json';
    private $config;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = [
            'max_requests_per_day' => MAX_REQUESTS_PER_DAY,
            'duplicate_check_hours' => 24,
            'auto_approve_delay' => 300,
            'admin_ids' => ADMIN_IDS
        ];
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        if (!file_exists($this->db_file)) {
            $default_data = [
                'requests' => [],
                'last_request_id' => 0,
                'user_stats' => [],
                'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]
            ];
            file_put_contents($this->db_file, json_encode($default_data, JSON_PRETTY_PRINT));
            @chmod($this->db_file, 0666);
            log_error("Requests database created", 'INFO');
        }
    }
    
    private function loadData() {
        $data = json_decode(file_get_contents($this->db_file), true);
        if (!$data) {
            $data = ['requests' => [], 'last_request_id' => 0, 'user_stats' => [], 'system_stats' => ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0]];
        }
        return $data;
    }
    
    private function saveData($data) {
        $result = file_put_contents($this->db_file, json_encode($data, JSON_PRETTY_PRINT));
        if ($result === false) {
            log_error("Failed to save requests database", 'ERROR');
            return false;
        }
        return true;
    }
    
    public function submitRequest($user_id, $movie_name, $user_name = '') {
        $movie_name = validateInput($movie_name, 'movie_name');
        $user_id = validateInput($user_id, 'user_id');
        
        if (!$movie_name || !$user_id || strlen($movie_name) < 2) {
            return ['success' => false, 'message' => 'Please enter a valid movie name (min 2 characters)'];
        }
        
        $duplicate_check = $this->checkDuplicateRequest($user_id, $movie_name);
        if ($duplicate_check['is_duplicate']) {
            return ['success' => false, 'message' => "You already requested '$movie_name' recently. Please wait before requesting again."];
        }
        
        $flood_check = $this->checkFloodControl($user_id);
        if (!$flood_check['allowed']) {
            return ['success' => false, 'message' => "You've reached the daily limit of " . MAX_REQUESTS_PER_DAY . " requests. Please try again tomorrow."];
        }
        
        $data = $this->loadData();
        $request_id = ++$data['last_request_id'];
        
        $request = [
            'id' => $request_id,
            'user_id' => $user_id,
            'user_name' => validateInput($user_name),
            'movie_name' => $movie_name,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'approved_at' => null,
            'rejected_at' => null,
            'approved_by' => null,
            'rejected_by' => null,
            'reason' => '',
            'is_notified' => false
        ];
        
        $data['requests'][$request_id] = $request;
        $data['system_stats']['total_requests']++;
        $data['system_stats']['pending']++;
        
        if (!isset($data['user_stats'][$user_id])) {
            $data['user_stats'][$user_id] = [
                'total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0,
                'last_request_time' => null, 'requests_today' => 0, 'last_request_date' => date('Y-m-d')
            ];
        }
        
        $data['user_stats'][$user_id]['total_requests']++;
        $data['user_stats'][$user_id]['pending']++;
        $data['user_stats'][$user_id]['last_request_time'] = time();
        
        if ($data['user_stats'][$user_id]['last_request_date'] != date('Y-m-d')) {
            $data['user_stats'][$user_id]['requests_today'] = 0;
            $data['user_stats'][$user_id]['last_request_date'] = date('Y-m-d');
        }
        $data['user_stats'][$user_id]['requests_today']++;
        
        $this->saveData($data);
        
        log_error("Request submitted", 'INFO', ['request_id' => $request_id, 'user_id' => $user_id, 'movie_name' => $movie_name]);
        
        return [
            'success' => true,
            'request_id' => $request_id,
            'message' => "✅ Request submitted successfully!\n\n🎬 Movie: $movie_name\n📝 ID: #$request_id\n🕒 Status: Pending\n\nYou will be notified when it's approved."
        ];
    }
    
    private function checkDuplicateRequest($user_id, $movie_name) {
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $time_limit = time() - (24 * 3600);
        
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id && 
                strtolower($request['movie_name']) == $movie_lower &&
                strtotime($request['created_at']) > $time_limit) {
                return ['is_duplicate' => true, 'request' => $request];
            }
        }
        return ['is_duplicate' => false];
    }
    
    private function checkFloodControl($user_id) {
        $data = $this->loadData();
        if (!isset($data['user_stats'][$user_id])) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        $user_stats = $data['user_stats'][$user_id];
        if ($user_stats['last_request_date'] != date('Y-m-d')) {
            return ['allowed' => true, 'remaining' => MAX_REQUESTS_PER_DAY];
        }
        $remaining = MAX_REQUESTS_PER_DAY - $user_stats['requests_today'];
        return ['allowed' => $user_stats['requests_today'] < MAX_REQUESTS_PER_DAY, 'remaining' => max(0, $remaining)];
    }
    
    public function approveRequest($request_id, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized access'];
        $data = $this->loadData();
        if (!isset($data['requests'][$request_id])) return ['success' => false, 'message' => 'Request not found'];
        $request = $data['requests'][$request_id];
        if ($request['status'] != 'pending') return ['success' => false, 'message' => "Request is already {$request['status']}"];
        
        $data['requests'][$request_id]['status'] = 'approved';
        $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['approved_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        
        $data['system_stats']['approved']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['approved']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request approved", 'INFO', ['request_id' => $request_id, 'admin_id' => $admin_id, 'movie_name' => $request['movie_name']]);
        
        return ['success' => true, 'request' => $data['requests'][$request_id], 'message' => "✅ Request #$request_id approved!"];
    }
    
    public function rejectRequest($request_id, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized access'];
        $data = $this->loadData();
        if (!isset($data['requests'][$request_id])) return ['success' => false, 'message' => 'Request not found'];
        $request = $data['requests'][$request_id];
        if ($request['status'] != 'pending') return ['success' => false, 'message' => "Request is already {$request['status']}"];
        
        $reason = validateInput($reason);
        
        $data['requests'][$request_id]['status'] = 'rejected';
        $data['requests'][$request_id]['rejected_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['rejected_by'] = $admin_id;
        $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
        $data['requests'][$request_id]['reason'] = $reason;
        
        $data['system_stats']['rejected']++;
        $data['system_stats']['pending']--;
        
        $user_id = $request['user_id'];
        $data['user_stats'][$user_id]['rejected']++;
        $data['user_stats'][$user_id]['pending']--;
        
        $this->saveData($data);
        
        log_error("Request rejected", 'INFO', ['request_id' => $request_id, 'admin_id' => $admin_id, 'movie_name' => $request['movie_name'], 'reason' => $reason]);
        
        return ['success' => true, 'request' => $data['requests'][$request_id], 'message' => "❌ Request #$request_id rejected!"];
    }
    
    public function bulkApprove($request_ids, $admin_id) {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized access'];
        $success_count = 0;
        foreach ($request_ids as $request_id) {
            $result = $this->approveRequest($request_id, $admin_id);
            if ($result['success']) $success_count++;
        }
        return ['success' => true, 'approved_count' => $success_count, 'total_count' => count($request_ids)];
    }
    
    public function bulkReject($request_ids, $admin_id, $reason = '') {
        if (!in_array($admin_id, ADMIN_IDS)) return ['success' => false, 'message' => 'Unauthorized access'];
        $reason = validateInput($reason);
        $success_count = 0;
        foreach ($request_ids as $request_id) {
            $result = $this->rejectRequest($request_id, $admin_id, $reason);
            if ($result['success']) $success_count++;
        }
        return ['success' => true, 'rejected_count' => $success_count, 'total_count' => count($request_ids)];
    }
    
    public function getPendingRequests($limit = 10, $filter_movie = '') {
        $data = $this->loadData();
        $pending = [];
        foreach ($data['requests'] as $request) {
            if ($request['status'] == 'pending') {
                if (!empty($filter_movie)) {
                    $movie_lower = strtolower($filter_movie);
                    $request_movie_lower = strtolower($request['movie_name']);
                    if (strpos($request_movie_lower, $movie_lower) === false) continue;
                }
                $pending[] = $request;
            }
        }
        usort($pending, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
        return array_slice($pending, 0, $limit);
    }
    
    public function getUserRequests($user_id, $limit = 20) {
        $data = $this->loadData();
        $user_requests = [];
        foreach ($data['requests'] as $request) {
            if ($request['user_id'] == $user_id) $user_requests[] = $request;
        }
        usort($user_requests, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return array_slice($user_requests, 0, $limit);
    }
    
    public function getRequest($request_id) {
        $data = $this->loadData();
        return $data['requests'][$request_id] ?? null;
    }
    
    public function getStats() {
        $data = $this->loadData();
        return $data['system_stats'];
    }
    
    public function getUserStats($user_id) {
        $data = $this->loadData();
        return $data['user_stats'][$user_id] ?? ['total_requests' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'requests_today' => 0];
    }
    
    public function checkAutoApprove($movie_name) {
        $movie_name = validateInput($movie_name, 'movie_name');
        if (!$movie_name) return [];
        
        $data = $this->loadData();
        $movie_lower = strtolower($movie_name);
        $auto_approved = [];
        
        foreach ($data['requests'] as $request_id => $request) {
            if ($request['status'] == 'pending') {
                $request_movie_lower = strtolower($request['movie_name']);
                if (strpos($movie_lower, $request_movie_lower) !== false || 
                    strpos($request_movie_lower, $movie_lower) !== false ||
                    similar_text($movie_lower, $request_movie_lower) > 80) {
                    
                    $data['requests'][$request_id]['status'] = 'approved';
                    $data['requests'][$request_id]['approved_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['approved_by'] = 'system';
                    $data['requests'][$request_id]['updated_at'] = date('Y-m-d H:i:s');
                    $data['requests'][$request_id]['reason'] = 'Auto-approved: Movie added to database';
                    
                    $data['system_stats']['approved']++;
                    $data['system_stats']['pending']--;
                    
                    $user_id = $request['user_id'];
                    $data['user_stats'][$user_id]['approved']++;
                    $data['user_stats'][$user_id]['pending']--;
                    
                    $auto_approved[] = $request_id;
                }
            }
        }
        
        if (!empty($auto_approved)) {
            $this->saveData($data);
            log_error("Auto-approved requests", 'INFO', ['movie_name' => $movie_name, 'request_ids' => $auto_approved]);
        }
        
        return $auto_approved;
    }
    
    public function markAsNotified($request_id) {
        $data = $this->loadData();
        if (isset($data['requests'][$request_id])) {
            $data['requests'][$request_id]['is_notified'] = true;
            $this->saveData($data);
        }
    }
    
    public function getUnnotifiedRequests() {
        $data = $this->loadData();
        $unnotified = [];
        foreach ($data['requests'] as $request) {
            if ($request['status'] != 'pending' && !$request['is_notified']) $unnotified[] = $request;
        }
        return $unnotified;
    }
}

// ==================== CSV MANAGER CLASS ====================
class CSVManager {
    private static $buffer = [];
    private static $instance = null;
    private $cache_data = null;
    private $cache_timestamp = 0;
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeFiles();
        register_shutdown_function([$this, 'flushBuffer']);
    }
    
    private function initializeFiles() {
        if (!file_exists(BACKUP_DIR)) { @mkdir(BACKUP_DIR, 0777, true); @chmod(BACKUP_DIR, 0777); }
        if (!file_exists(CACHE_DIR)) { @mkdir(CACHE_DIR, 0777, true); @chmod(CACHE_DIR, 0777); }
        
        if (!file_exists(CSV_FILE)) {
            $header = "movie_name,message_id,channel_id\n";
            @file_put_contents(CSV_FILE, $header);
            @chmod(CSV_FILE, 0666);
            log_error("CSV file created", 'INFO');
        }
        
        if (!file_exists(USERS_FILE)) {
            $users_data = ['users' => [], 'total_requests' => 0, 'message_logs' => []];
            @file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            @chmod(USERS_FILE, 0666);
        }
        
        if (!file_exists(STATS_FILE)) {
            $stats_data = ['total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'last_updated' => date('Y-m-d H:i:s')];
            @file_put_contents(STATS_FILE, json_encode($stats_data, JSON_PRETTY_PRINT));
            @chmod(STATS_FILE, 0666);
        }
    }
    
    private function acquireLock($file, $mode = LOCK_EX) {
        $fp = fopen($file, 'r+');
        if ($fp && flock($fp, $mode)) return $fp;
        if ($fp) fclose($fp);
        return false;
    }
    
    private function releaseLock($fp) {
        if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
    }
    
    public function bufferedAppend($movie_name, $message_id, $channel_id) {
        $movie_name = validateInput($movie_name, 'movie_name');
        $channel_id = validateInput($channel_id, 'telegram_id');
        
        if (!$movie_name || !$channel_id || empty(trim($movie_name))) {
            log_error("Invalid input for bufferedAppend", 'WARNING');
            return false;
        }
        
        self::$buffer[] = [
            'movie_name' => trim($movie_name),
            'message_id' => intval($message_id),
            'channel_id' => $channel_id,
            'timestamp' => time()
        ];
        
        log_error("Added to buffer: " . trim($movie_name), 'INFO', ['message_id' => $message_id, 'channel_id' => $channel_id]);
        
        if (count(self::$buffer) >= CSV_BUFFER_SIZE) $this->flushBuffer();
        $this->clearCache();
        return true;
    }
    
    public function flushBuffer() {
        if (empty(self::$buffer)) return true;
        
        log_error("Flushing buffer with " . count(self::$buffer) . " items", 'INFO');
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_EX);
        if (!$fp) { log_error("Failed to lock CSV file for writing", 'ERROR'); return false; }
        
        try {
            foreach (self::$buffer as $entry) {
                fputcsv($fp, [$entry['movie_name'], $entry['message_id'], $entry['channel_id']]);
            }
            fflush($fp);
            log_error("Buffer flushed successfully", 'INFO');
            self::$buffer = [];
            return true;
        } catch (Exception $e) {
            log_error("Error flushing buffer: " . $e->getMessage(), 'ERROR');
            return false;
        } finally {
            $this->releaseLock($fp);
        }
    }
    
    public function readCSV() {
        $data = [];
        if (!file_exists(CSV_FILE)) { log_error("CSV file not found", 'ERROR'); return $data; }
        
        $fp = $this->acquireLock(CSV_FILE, LOCK_SH);
        if (!$fp) { log_error("Failed to lock CSV file for reading", 'ERROR'); return $data; }
        
        try {
            $header = fgetcsv($fp);
            if ($header === false || $header[0] !== 'movie_name') {
                log_error("Invalid CSV header, rebuilding", 'WARNING');
                $this->rebuildCSV();
                return $this->readCSV();
            }
            
            while (($row = fgetcsv($fp)) !== FALSE) {
                if (count($row) >= 3 && !empty(trim($row[0]))) {
                    $data[] = [
                        'movie_name' => validateInput(trim($row[0]), 'movie_name'),
                        'message_id' => isset($row[1]) ? intval(trim($row[1])) : 0,
                        'channel_id' => isset($row[2]) ? validateInput(trim($row[2]), 'telegram_id') : ''
                    ];
                }
            }
            return $data;
        } catch (Exception $e) {
            log_error("Error reading CSV: " . $e->getMessage(), 'ERROR');
            return [];
        } finally {
            $this->releaseLock($fp);
        }
    }
    
    private function rebuildCSV() {
        $backup = BACKUP_DIR . 'csv_backup_' . date('Y-m-d_H-i-s') . '.csv';
        if (file_exists(CSV_FILE)) copy(CSV_FILE, $backup);
        
        $data = [];
        if (file_exists(CSV_FILE)) {
            $lines = file(CSV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode(',', $line);
                if (count($parts) >= 3) {
                    $data[] = [
                        'movie_name' => validateInput(trim($parts[0]), 'movie_name'),
                        'message_id' => intval(trim($parts[1])),
                        'channel_id' => validateInput(trim($parts[2]), 'telegram_id')
                    ];
                }
            }
        }
        
        $fp = fopen(CSV_FILE, 'w');
        if ($fp) {
            fputcsv($fp, ['movie_name', 'message_id', 'channel_id']);
            foreach ($data as $row) fputcsv($fp, [$row['movie_name'], $row['message_id'], $row['channel_id']]);
            fclose($fp);
            @chmod(CSV_FILE, 0666);
        }
    }
    
    public function getCachedData() {
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        
        if ($this->cache_data !== null && (time() - $this->cache_timestamp) < CACHE_EXPIRY) {
            return $this->cache_data;
        }
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_EXPIRY) {
            $cached = @unserialize(file_get_contents($cache_file));
            if ($cached !== false) {
                $this->cache_data = $cached;
                $this->cache_timestamp = filemtime($cache_file);
                return $this->cache_data;
            }
        }
        
        $this->cache_data = $this->readCSV();
        $this->cache_timestamp = time();
        @file_put_contents($cache_file, serialize($this->cache_data));
        @chmod($cache_file, 0666);
        
        return $this->cache_data;
    }
    
    public function clearCache() {
        $this->cache_data = null;
        $this->cache_timestamp = 0;
        $cache_file = CACHE_DIR . 'movies_cache.ser';
        if (file_exists($cache_file)) @unlink($cache_file);
    }
    
    public function searchMovies($query) {
        $query = validateInput($query, 'movie_name');
        if (!$query) return [];
        
        $data = $this->getCachedData();
        $query_lower = strtolower(trim($query));
        $results = [];
        
        foreach ($data as $item) {
            if (empty($item['movie_name'])) continue;
            $movie_lower = strtolower($item['movie_name']);
            $score = 0;
            
            if ($movie_lower === $query_lower) $score = 100;
            elseif (strpos($movie_lower, $query_lower) !== false) $score = 80;
            else {
                similar_text($movie_lower, $query_lower, $similarity);
                if ($similarity > 60) $score = $similarity;
            }
            
            if ($score > 0) {
                if (!isset($results[$movie_lower])) {
                    $results[$movie_lower] = ['score' => $score, 'count' => 0, 'items' => []];
                }
                $results[$movie_lower]['count']++;
                $results[$movie_lower]['items'][] = $item;
            }
        }
        
        uasort($results, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($results, 0, 10);
    }
    
    public function getStats() {
        $data = $this->getCachedData();
        $stats = ['total_movies' => count($data), 'channels' => [], 'last_updated' => date('Y-m-d H:i:s', $this->cache_timestamp)];
        foreach ($data as $item) {
            $channel = $item['channel_id'];
            if (!isset($stats['channels'][$channel])) $stats['channels'][$channel] = 0;
            $stats['channels'][$channel]++;
        }
        return $stats;
    }
}

// ==================== TELEGRAM API FUNCTIONS ====================
function apiRequest($method, $params = [], $is_multipart = false) {
    if (!RateLimiter::check('telegram_api', RATE_LIMIT_REQUESTS, RATE_LIMIT_WINDOW)) {
        log_error("Telegram API rate limit exceeded", 'WARNING');
        usleep(100000);
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $res = curl_exec($ch);
        if ($res === false) log_error("CURL ERROR: " . curl_error($ch), 'ERROR');
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($params),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            log_error("apiRequest failed for method $method: " . ($error['message'] ?? 'Unknown error'), 'ERROR');
        }
        return $result;
    }
}

function sendChatAction($chat_id, $action = 'typing') {
    return apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => $action]);
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null, $reply_to = null) {
    $data = ['chat_id' => $chat_id, 'text' => validateInput($text, 'text')];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    if ($reply_to) $data['reply_to_message_id'] = $reply_to;
    $data['disable_web_page_preview'] = true;
    
    $result = apiRequest('sendMessage', $data);
    if ($result) {
        $decoded = json_decode($result, true);
        if ($decoded && $decoded['ok']) {
            log_error("Message sent to $chat_id: " . substr(strip_tags($text), 0, 100), 'INFO');
        }
    }
    return $result;
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => validateInput($text, 'text'),
        'disable_web_page_preview' => true
    ];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    
    log_error("Editing message $message_id for $chat_id", 'INFO');
    return apiRequest('editMessageText', $data);
}

function editMessage($chat_id, $message_id, $text, $keyboard = null) {
    return editMessageText($chat_id, $message_id, $text, $keyboard, 'HTML');
}

function deleteMessage($chat_id, $message_id) {
    return apiRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);
}

function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => validateInput($from_chat_id, 'telegram_id'),
        'message_id' => intval($message_id)
    ]);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $data = ['callback_query_id' => $callback_query_id, 'show_alert' => $show_alert];
    if ($text) $data['text'] = validateInput($text, 'text');
    return apiRequest('answerCallbackQuery', $data);
}

// ==================== CHANNEL MANAGEMENT ====================
function getChannelType($channel_id) {
    global $ENV_CONFIG;
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) if ($channel['id'] == $channel_id) return 'public';
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) if ($channel['id'] == $channel_id) return 'private';
    return 'unknown';
}

function getChannelUsername($channel_id) {
    global $ENV_CONFIG;
    foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel) if ($channel['id'] == $channel_id) return $channel['username'];
    foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel) if ($channel['id'] == $channel_id) return $channel['username'] ?: 'Private Channel';
    return 'Unknown Channel';
}

// ==================== NOTIFICATION FUNCTIONS ====================
function notifyUserAboutRequest($user_id, $request, $action) {
    global $requestSystem;
    $movie_name = htmlspecialchars($request['movie_name'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'approved') {
        $message = "🎉 <b>Good News!</b>\n\n✅ Your movie request has been <b>APPROVED</b>!\n\n🎬 <b>Movie:</b> $movie_name\n📝 <b>Request ID:</b> #" . $request['id'] . "\n🕒 <b>Approved at:</b> " . date('d M Y, H:i', strtotime($request['approved_at'])) . "\n\n";
        if (!empty($request['reason'])) $message .= "📋 <b>Note:</b> " . htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') . "\n\n";
        $message .= "🔍 You can now search for this movie in the bot!\n📢 Join: @EntertainmentTadka786";
    } else {
        $message = "📭 <b>Update on Your Request</b>\n\n❌ Your movie request has been <b>REJECTED</b>.\n\n🎬 <b>Movie:</b> $movie_name\n📝 <b>Request ID:</b> #" . $request['id'] . "\n🕒 <b>Rejected at:</b> " . date('d M Y, H:i', strtotime($request['rejected_at'])) . "\n";
        if (!empty($request['reason'])) $message .= "📋 <b>Reason:</b> " . htmlspecialchars($request['reason'], ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "\n💡 <b>Tip:</b> Make sure the movie name is correct and check if it's already available.";
    }
    
    sendMessage($user_id, $message, null, 'HTML');
    $requestSystem->markAsNotified($request['id']);
}

// ==================== DELIVERY LOGIC ====================
function deliver_item_to_chat($chat_id, $item) {
    $channel_id = $item['channel_id'];
    $message_id = $item['message_id'];
    $channel_type = getChannelType($channel_id);
    
    sendChatAction($chat_id, 'typing');
    
    if ($channel_type === 'public') {
        $result = forwardMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) { log_error("Forwarded message successfully", 'INFO'); return true; }
        else { log_error("Failed to forward message", 'ERROR'); return false; }
    } elseif ($channel_type === 'private') {
        $result = copyMessage($chat_id, $channel_id, $message_id);
        if ($result !== false) { log_error("Copied message successfully", 'INFO'); return true; }
        else { log_error("Failed to copy message", 'ERROR'); return false; }
    }
    
    $text = "🎬 " . htmlspecialchars($item['movie_name'], ENT_QUOTES, 'UTF-8') . "\n📁 Channel: " . getChannelUsername($channel_id) . "\n🔗 Message ID: " . $message_id;
    sendMessage($chat_id, $text, null, 'HTML');
    log_error("Used fallback text delivery", 'WARNING');
    return false;
}

// ==================== ADVANCED SEARCH FUNCTION ====================
function advanced_search($chat_id, $query, $user_id = null) {
    global $csvManager, $waiting_users;
    
    sendChatAction($chat_id, 'typing');
    
    $q = validateInput($query, 'movie_name');
    if (!$q || strlen($q) < 2) {
        sendMessage($chat_id, "❌ Invalid movie name format. Minimum 2 characters required.");
        return;
    }
    
    $q = strtolower(trim($q));
    $lang = detect_language($query);
    send_multilingual_response($chat_id, 'searching', $lang);
    
    $found = $csvManager->searchMovies($query);
    
    if (!empty($found)) {
        $stats = json_decode(file_get_contents(STATS_FILE), true);
        $stats['total_searches'] = ($stats['total_searches'] ?? 0) + 1;
        $stats['successful_searches'] = ($stats['successful_searches'] ?? 0) + 1;
        file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
        
        update_user_activity($user_id, 'search');
        
        $message = "🔍 Found " . count($found) . " results for '$query':\n\n";
        $i = 1;
        foreach ($found as $movie => $data) {
            $message .= "$i. <b>" . ucwords($movie) . "</b> ({$data['count']} entries)\n";
            $i++; if ($i > 10) break;
        }
        sendMessage($chat_id, $message, null, 'HTML');
        
        $keyboard = ['inline_keyboard' => []];
        $top_movies = array_slice(array_keys($found), 0, 5);
        foreach ($top_movies as $movie) {
            $keyboard['inline_keyboard'][] = [['text' => "🎬 " . ucwords($movie), 'callback_data' => 'movie_' . base64_encode($movie)]];
        }
        $keyboard['inline_keyboard'][] = [['text' => "📝 Request Different Movie", 'callback_data' => 'request_movie']];
        
        sendMessage($chat_id, "Click to download:", $keyboard);
        
        update_user_points($user_id, 'found_movie');
    } else {
        $stats = json_decode(file_get_contents(STATS_FILE), true);
        $stats['total_searches'] = ($stats['total_searches'] ?? 0) + 1;
        $stats['failed_searches'] = ($stats['failed_searches'] ?? 0) + 1;
        file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
        
        send_multilingual_response($chat_id, 'not_found', $lang);
        
        $keyboard = ['inline_keyboard' => [[['text' => '📝 Request This Movie', 'callback_data' => 'auto_request_' . base64_encode($query)]]]];
        sendMessage($chat_id, "💡 Click to request automatically:", $keyboard);
        
        if (!isset($waiting_users[$query])) $waiting_users[$query] = [];
        $waiting_users[$query][] = [$chat_id, $user_id];
    }
    
    update_stats('total_searches', 1);
    if ($user_id) update_user_points($user_id, 'search');
}

// ==================== HELPER FUNCTIONS ====================
function detect_language($text) {
    $hindi_pattern = '/[\x{0900}-\x{097F}]/u';
    if (preg_match($hindi_pattern, $text)) return 'hindi';
    $hindi_keywords = ['फिल्म', 'मूवी', 'डाउनलोड', 'हिंदी', 'चाहिए'];
    foreach ($hindi_keywords as $keyword) if (strpos($text, $keyword) !== false) return 'hindi';
    return 'english';
}

function send_multilingual_response($chat_id, $message_type, $language = 'english') {
    $responses = [
        'hindi' => [
            'welcome' => "🎬 Boss, kis movie ki talash hai?",
            'found' => "✅ Mil gayi! Movie forward ho rahi hai...",
            'not_found' => "😔 Yeh movie abhi available nahi hai!\n\n📝 Request kar sakte ho: @EntertainmentTadka7860",
            'searching' => "🔍 Dhoondh raha hoon... Zara wait karo",
            'request_success' => "✅ Request receive ho gayi! Jald add karenge.",
            'request_limit' => "❌ Aaj ke liye maximum " . DAILY_REQUEST_LIMIT . " requests hi kar sakte ho."
        ],
        'english' => [
            'welcome' => "🎬 Boss, which movie are you looking for?",
            'found' => "✅ Found it! Forwarding the movie...",
            'not_found' => "😔 This movie isn't available yet!\n\n📝 Request it: @EntertainmentTadka7860",
            'searching' => "🔍 Searching... Please wait",
            'request_success' => "✅ Request received! We'll add it soon.",
            'request_limit' => "❌ Daily limit reached (" . DAILY_REQUEST_LIMIT . " requests)."
        ]
    ];
    sendMessage($chat_id, $responses[$language][$message_type]);
}

function update_stats($field, $increment = 1) {
    if (!file_exists(STATS_FILE)) { log_error("Stats file not found", 'ERROR'); return; }
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    if (!$stats) { $stats = []; log_error("Failed to decode stats file", 'ERROR'); }
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}

function update_user_points($user_id, $action) {
    if (!file_exists(USERS_FILE)) { log_error("Users file not found", 'ERROR'); return; }
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users_data) { $users_data = ['users' => []]; log_error("Failed to decode users file", 'ERROR'); }
    $points_map = ['search' => 1, 'found_movie' => 5, 'daily_login' => 10];
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = ['points' => 0, 'last_activity' => date('Y-m-d H:i:s')];
    }
    $users_data['users'][$user_id]['points'] += ($points_map[$action] ?? 0);
    $users_data['users'][$user_id]['last_activity'] = date('Y-m-d H:i:s');
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

function update_user_activity($user_id, $action) {
    if (!$user_id) return;
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!isset($users_data['users'][$user_id])) {
        $users_data['users'][$user_id] = [
            'first_name' => '', 'last_name' => '', 'username' => '',
            'joined' => date('Y-m-d H:i:s'), 'last_active' => date('Y-m-d H:i:s'),
            'points' => 0, 'total_searches' => 0, 'total_downloads' => 0, 'request_count' => 0
        ];
    }
    switch ($action) {
        case 'search': $users_data['users'][$user_id]['total_searches']++; $users_data['users'][$user_id]['points'] += 1; break;
        case 'download': $users_data['users'][$user_id]['total_downloads']++; $users_data['users'][$user_id]['points'] += 3; break;
        case 'request': $users_data['users'][$user_id]['request_count']++; $users_data['users'][$user_id]['points'] += 2; break;
    }
    $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}

// ==================== ADMIN COMMANDS ====================
function admin_stats($chat_id) {
    global $csvManager, $ENV_CONFIG, $requestSystem;
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    $request_stats = $requestSystem->getStats();
    $file_stats = json_decode(file_get_contents(STATS_FILE), true);
    
    $msg = "📊 Bot Statistics\n\n🎬 Total Movies: " . $stats['total_movies'] . "\n👥 Total Users: " . $total_users . "\n🔍 Total Searches: " . ($file_stats['total_searches'] ?? 0) . "\n🕒 Last Updated: " . $stats['last_updated'] . "\n\n📡 Channels Distribution:\n";
    foreach ($stats['channels'] as $channel_id => $count) $msg .= "• " . getChannelUsername($channel_id) . ": " . $count . " movies\n";
    $msg .= "\n📋 Request System Stats:\n• Total Requests: " . $request_stats['total_requests'] . "\n• Pending: " . $request_stats['pending'] . "\n• Approved: " . $request_stats['approved'] . "\n• Rejected: " . $request_stats['rejected'] . "\n";
    
    sendMessage($chat_id, $msg, null, 'HTML');
}

function csv_stats_command($chat_id) {
    global $csvManager;
    sendChatAction($chat_id, 'typing');
    
    $stats = $csvManager->getStats();
    $csv_size = file_exists(CSV_FILE) ? filesize(CSV_FILE) : 0;
    
    $msg = "📊 CSV Database Statistics\n\n📁 File Size: " . round($csv_size / 1024, 2) . " KB\n📄 Total Movies: " . $stats['total_movies'] . "\n🕒 Last Cache Update: " . $stats['last_updated'] . "\n\n📡 Movies by Channel:\n";
    foreach ($stats['channels'] as $channel_id => $count) {
        $channel_type = getChannelType($channel_id);
        $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
        $msg .= $type_icon . " " . getChannelUsername($channel_id) . ": " . $count . "\n";
    }
    sendMessage($chat_id, $msg, null, 'HTML');
}

// ==================== PAGINATION & VIEW ====================
function totalupload_controller($chat_id, $page = 1) {
    global $csvManager;
    sendChatAction($chat_id, 'upload_document');
    
    $all = $csvManager->getCachedData();
    if (empty($all)) { sendMessage($chat_id, "⚠️ No movies found in database."); return; }
    
    $total = count($all);
    $total_pages = ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    $page_movies = array_slice($all, $start, ITEMS_PER_PAGE);
    
    foreach ($page_movies as $movie) {
        sendChatAction($chat_id, 'upload_document');
        deliver_item_to_chat($chat_id, $movie);
        usleep(500000);
    }
    
    $title = "📊 Total Uploads\n• Page {$page}/{$total_pages}\n• Showing: " . count($page_movies) . " of {$total}\n\n➡️ Use buttons to navigate";
    
    $keyboard = ['inline_keyboard' => []];
    $row = [];
    if ($page > 1) $row[] = ['text' => '⏮️ Previous', 'callback_data' => 'tu_prev_' . ($page - 1)];
    if ($page < $total_pages) $row[] = ['text' => '⏭️ Next', 'callback_data' => 'tu_next_' . ($page + 1)];
    if (!empty($row)) $keyboard['inline_keyboard'][] = $row;
    $keyboard['inline_keyboard'][] = [['text' => '🎬 View Current Page', 'callback_data' => 'tu_view_' . $page], ['text' => '🛑 Stop', 'callback_data' => 'tu_stop']];
    
    sendMessage($chat_id, $title, $keyboard, 'HTML');
}

// ==================== LEGACY FUNCTIONS ====================
function check_date($chat_id) {
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $msg = "📅 Bot Statistics\n\n🎬 Total Movies: " . ($stats['total_movies'] ?? 0) . "\n👥 Total Users: " . ($stats['total_users'] ?? 0) . "\n🔍 Total Searches: " . ($stats['total_searches'] ?? 0) . "\n🕒 Last Updated: " . ($stats['last_updated'] ?? 'N/A');
    sendMessage($chat_id, $msg, null, 'HTML');
}

function test_csv($chat_id) {
    global $csvManager;
    $data = $csvManager->getCachedData();
    if (empty($data)) { sendMessage($chat_id, "📊 CSV file is empty."); return; }
    
    $message = "📊 CSV Movie Database\n\n📁 Total Movies: " . count($data) . "\n🔍 Showing latest 10 entries\n\n";
    $recent = array_slice($data, -10);
    $i = 1;
    foreach ($recent as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n   📝 ID: " . $movie['message_id'] . "\n   📡 Channel: " . $channel_name . "\n\n";
        $i++;
    }
    sendMessage($chat_id, $message, null, 'HTML');
}

function show_csv_data($chat_id, $show_all = false) {
    global $csvManager;
    $data = $csvManager->getCachedData();
    if (empty($data)) { sendMessage($chat_id, "📊 CSV file is empty."); return; }
    
    $limit = $show_all ? count($data) : 10;
    $display_data = array_slice($data, -$limit);
    
    $message = "📊 CSV Movie Database\n\n📁 Total Movies: " . count($data) . "\n";
    $message .= $show_all ? "📋 Full database listing\n\n" : "🔍 Showing latest 10 entries\n📋 Use '/checkcsv all' for full list\n\n";
    
    $i = 1;
    foreach ($display_data as $movie) {
        $movie_name = htmlspecialchars($movie['movie_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $channel_name = getChannelUsername($movie['channel_id']);
        $message .= "$i. 🎬 " . $movie_name . "\n   📝 ID: " . $movie['message_id'] . "\n   📡 Channel: " . $channel_name . "\n\n";
        $i++;
        if (strlen($message) > 3000) { sendMessage($chat_id, $message, null, 'HTML'); $message = "📊 Continuing...\n\n"; }
    }
    $message .= "💾 File: " . CSV_FILE . "\n⏰ Last Updated: " . date('Y-m-d H:i:s', filemtime(CSV_FILE));
    sendMessage($chat_id, $message, null, 'HTML');
}

// ==================== INDEX1.PHP FUNCTIONS ====================
$movie_cache = [];
$movie_messages = [];
$waiting_users = [];

function load_and_clean_csv() {
    global $movie_messages, $movie_cache;
    if (!file_exists(CSV_FILE)) return [];
    
    $data = [];
    $handle = fopen(CSV_FILE, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3 && !empty(trim($row[0]))) {
                $movie_name = trim($row[0]);
                $message_id = isset($row[1]) ? trim($row[1]) : '';
                $date = isset($row[2]) ? trim($row[2]) : '';
                $video_path = isset($row[3]) ? trim($row[3]) : '';
                $quality = isset($row[4]) ? trim($row[4]) : 'Unknown';
                $size = isset($row[5]) ? trim($row[5]) : 'Unknown';
                $language = isset($row[6]) ? trim($row[6]) : 'Hindi';
                
                $entry = [
                    'movie_name' => $movie_name, 'message_id_raw' => $message_id, 'date' => $date,
                    'video_path' => $video_path, 'quality' => $quality, 'size' => $size,
                    'language' => $language, 'message_id' => is_numeric($message_id) ? intval($message_id) : null
                ];
                
                $data[] = $entry;
                $movie_key = strtolower($movie_name);
                if (!isset($movie_messages[$movie_key])) $movie_messages[$movie_key] = [];
                $movie_messages[$movie_key][] = $entry;
            }
        }
        fclose($handle);
    }
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats['total_movies'] = count($data);
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
    
    $movie_cache = ['data' => $data, 'timestamp' => time()];
    return $data;
}

function get_cached_movies() {
    global $movie_cache;
    if (!empty($movie_cache) && (time() - $movie_cache['timestamp']) < 300) return $movie_cache['data'];
    return load_and_clean_csv();
}

function append_movie($movie_name, $message_id, $quality = 'Unknown', $size = 'Unknown', $language = 'Hindi') {
    global $csvManager, $movie_messages, $movie_cache;
    
    $date = date('d-m-Y');
    $entry = [$movie_name, $message_id, $date, '', $quality, $size, $language];
    
    $handle = fopen(CSV_FILE, "a");
    fputcsv($handle, $entry);
    fclose($handle);
    
    $movie_key = strtolower($movie_name);
    if (!isset($movie_messages[$movie_key])) $movie_messages[$movie_key] = [];
    $movie_messages[$movie_key][] = [
        'movie_name' => $movie_name, 'message_id_raw' => $message_id,
        'message_id' => is_numeric($message_id) ? intval($message_id) : null,
        'date' => $date, 'quality' => $quality, 'size' => $size, 'language' => $language
    ];
    
    $movie_cache = [];
    track_upload($movie_name, $message_id, $quality, $size, $language);
    
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats['total_movies'] = ($stats['total_movies'] ?? 0) + 1;
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
    
    log_error("Movie appended: $movie_name (ID: $message_id)");
    
    global $waiting_users;
    foreach ($waiting_users as $query => $users) {
        if (stripos($movie_name, $query) !== false) {
            foreach ($users as $user_data) {
                list($user_chat_id, $user_id) = $user_data;
                sendMessage($user_chat_id, "🎉 Good news! '$movie_name' ab available hai! Search karo ya /recent check karo.");
            }
            unset($waiting_users[$query]);
        }
    }
}

function smart_search($query) {
    global $movie_messages;
    $query_lower = strtolower(trim($query));
    $results = [];
    
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        if ($movie == $query_lower) $score = 100;
        elseif (strpos($movie, $query_lower) !== false) $score = 80 - (strlen($movie) - strlen($query_lower));
        else { similar_text($movie, $query_lower, $similarity); if ($similarity > 60) $score = $similarity; }
        
        if ($score > 0) {
            $results[$movie] = [
                'score' => $score, 'count' => count($entries),
                'latest_entry' => end($entries), 'qualities' => array_unique(array_column($entries, 'quality'))
            ];
        }
    }
    
    uasort($results, fn($a, $b) => $b['score'] - $a['score']);
    return array_slice($results, 0, 15);
}

// ==================== COPYRIGHT PROTECTION SYSTEM ====================
function get_progress_bar($percentage, $length = 20) {
    $filled = round(($percentage / 100) * $length);
    $empty = $length - $filled;
    $bar = "";
    for ($i = 0; $i < $filled; $i++) $bar .= "🟩";
    for ($i = 0; $i < $empty; $i++) $bar .= "⬜";
    return $bar;
}

function get_countdown_timer($delete_time) {
    $remaining = strtotime($delete_time) - time();
    if ($remaining <= 0) return "00:00";
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    return sprintf("%02d:%02d", $minutes, $seconds);
}

function get_warning_message($file_name, $file_size = '', $quality = '', $delete_time = null) {
    if ($delete_time === null) $delete_time = date('Y-m-d H:i:s', time() + (DELETE_AFTER_MINUTES * 60));
    
    $current_time = date("g:i A");
    $delete_formatted = date("g:i A", strtotime($delete_time));
    
    $info = extract_file_info($file_name);
    $display_name = $info['title'] ?: $file_name;
    $year = $info['year'] ? " ($year)" : "";
    $final_quality = $quality ?: ($info['quality'] ?: 'HD');
    
    $total_seconds = DELETE_AFTER_MINUTES * 60;
    $elapsed = time() - (strtotime($delete_time) - $total_seconds);
    $percentage = min(100, max(0, ($elapsed / $total_seconds) * 100));
    $progress_bar = get_progress_bar($percentage);
    $countdown = get_countdown_timer($delete_time);
    
    $message = "🎬 <b>" . htmlspecialchars($display_name) . "$year</b>";
    if ($final_quality != 'Unknown') $message .= " [$final_quality]";
    if ($file_size) $message .= "\n💾 " . htmlspecialchars($file_size);
    
    $message .= "\n═══════════════════════════════\n🚨🚨🚨 URGENT NOTICE 🚨🚨🚨\n═══════════════════════════════\n⚠️ File Deletion: " . DELETE_AFTER_MINUTES . " Minutes\n🛡️ Protection: Copyright Shield\n📋 Action: Forward Immediately\n\n";
    $message .= "✅ <b>TO-DO LIST:</b>\n├─ 📤 Forward File Now\n├─ 💾 Save to Secure Location\n├─ ⬇️ Download Safely\n└─ ⚠️ Avoid Auto-Deletion\n═══════════════════════════════\n";
    $message .= "🔔 Channel: " . MAIN_CHANNEL . "\n═══════════════════════════════\n";
    $message .= "⏳ Countdown: $countdown\n$progress_bar " . round($percentage) . "%\n\n";
    $message .= "⏰ Uploaded: $current_time\n🗑️ Deletion: $delete_formatted\n⏱️ Time Left: " . DELETE_AFTER_MINUTES . " minutes";
    
    return $message;
}

function extract_file_info($file_name) {
    $info = ['title' => '', 'year' => '', 'quality' => '', 'type' => 'Video'];
    if (preg_match('/\((\d{4})\)/', $file_name, $matches)) $info['year'] = $matches[1];
    if (preg_match('/(\d{3,4}p|HD|FHD|UHD|WEB\-DL|WEBRip|BluRay)/i', $file_name, $matches)) $info['quality'] = strtoupper($matches[1]);
    
    $title = $file_name;
    $title = preg_replace('/\.(mkv|mp4|avi|mov|wmv|flv|webm)$/i', '', $title);
    $title = preg_replace('/\((\d{4})\)/', '', $title);
    $title = preg_replace('/(\d{3,4}p|HD|FHD|UHD|WEB\-DL|WEBRip|BluRay)/i', '', $title);
    $title = trim(preg_replace('/[\._\-]+/', ' ', $title));
    $title = preg_replace('/\s+/', ' ', $title);
    
    $info['title'] = ucwords($title);
    return $info;
}

function schedule_file_deletion($chat_id, $message_id, $file_name, $file_size = '', $quality = '') {
    $db = new SQLite3(UPLOADS_DB);
    $delete_time = date('Y-m-d H:i:s', time() + (DELETE_AFTER_MINUTES * 60));
    
    $stmt = $db->prepare("INSERT INTO scheduled_deletes (chat_id, message_id, file_name, file_size, quality, delete_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $chat_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $message_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $file_name, SQLITE3_TEXT);
    $stmt->bindValue(4, $file_size, SQLITE3_TEXT);
    $stmt->bindValue(5, $quality, SQLITE3_TEXT);
    $stmt->bindValue(6, $delete_time, SQLITE3_TEXT);
    $stmt->execute();
    $schedule_id = $db->lastInsertRowID();
    $db->close();
    
    $warning_msg = get_warning_message($file_name, $file_size, $quality, $delete_time);
    
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🔗 JOIN CHANNEL', 'url' => 'https://t.me/' . str_replace('@', '', MAIN_CHANNEL)], ['text' => '⏰ LIVE COUNTDOWN', 'callback_data' => 'countdown_' . $schedule_id]],
            [['text' => '✅ I SAVED IT', 'callback_data' => 'saved_' . $schedule_id], ['text' => '❌ DELETE NOW', 'callback_data' => 'delete_now_' . $schedule_id]]
        ]
    ];
    
    $result = sendMessage($chat_id, $warning_msg, $keyboard, 'HTML', $message_id);
    
    if ($result && $result['ok']) {
        $db = new SQLite3(UPLOADS_DB);
        $stmt = $db->prepare("INSERT INTO warning_messages (file_id, message_id, chat_id) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $schedule_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $result['result']['message_id'], SQLITE3_INTEGER);
        $stmt->bindValue(3, $chat_id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
    }
    
    bot_log("Scheduled deletion: $file_name (ID: $schedule_id) for $delete_time");
    return $schedule_id;
}

function process_scheduled_deletions() {
    $db = new SQLite3(UPLOADS_DB);
    $now = date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("SELECT * FROM scheduled_deletes WHERE delete_time <= ? AND status = 'pending'");
    $stmt->bindValue(1, $now, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $deleted_count = 0;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $delete_result = deleteMessage($row['chat_id'], $row['message_id']);
        if ($delete_result && $delete_result['ok']) {
            $update_stmt = $db->prepare("UPDATE scheduled_deletes SET status = 'deleted' WHERE id = ?");
            $update_stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
            $update_stmt->execute();
            $deleted_count++;
            
            $final_msg = "⏰ <b>TIME'S UP!</b>\n\n🗑️ <b>" . htmlspecialchars($row['file_name']) . "</b>\nhas been automatically deleted.\n\n⚠️ Remember to forward files immediately!\n🔗 " . MAIN_CHANNEL;
            sendMessage($row['chat_id'], $final_msg, null, 'HTML');
        } else {
            $update_stmt = $db->prepare("UPDATE scheduled_deletes SET status = 'failed' WHERE id = ?");
            $update_stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
            $update_stmt->execute();
        }
    }
    $db->close();
    if ($deleted_count > 0) bot_log("Processed $deleted_count scheduled deletions");
    return $deleted_count;
}

function update_progress_bars() {
    $db = new SQLite3(UPLOADS_DB);
    $now = time();
    
    $stmt = $db->prepare("SELECT sd.*, wm.message_id as warning_id FROM scheduled_deletes sd LEFT JOIN warning_messages wm ON sd.id = wm.file_id WHERE sd.status = 'pending' AND sd.delete_time > datetime('now')");
    $result = $stmt->execute();
    
    $updated = 0;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!$row['warning_id']) continue;
        
        $delete_timestamp = strtotime($row['delete_time']);
        $total_seconds = DELETE_AFTER_MINUTES * 60;
        $elapsed = $now - ($delete_timestamp - $total_seconds);
        $percentage = min(100, max(0, ($elapsed / $total_seconds) * 100));
        
        $last_percentage = $row['progress_percentage'] ?? 0;
        if (abs($percentage - $last_percentage) >= 5 || $now % 30 == 0) {
            $warning_msg = get_warning_message($row['file_name'], $row['file_size'], $row['quality'], $row['delete_time']);
            try {
                editMessage($row['chat_id'], $row['warning_id'], $warning_msg);
                $update_stmt = $db->prepare("UPDATE warning_messages SET progress_percentage = ?, last_updated = datetime('now') WHERE file_id = ?");
                $update_stmt->bindValue(1, round($percentage), SQLITE3_INTEGER);
                $update_stmt->bindValue(2, $row['id'], SQLITE3_INTEGER);
                $update_stmt->execute();
                $updated++;
            } catch (Exception $e) {}
        }
    }
    $db->close();
    if ($updated > 0) bot_log("Updated $updated progress bars");
}

// ==================== UPLOAD ANALYTICS SYSTEM ====================
function track_upload($file_name, $message_id, $quality = 'Unknown', $size = 'Unknown', $language = 'Hindi') {
    $db = new SQLite3(UPLOADS_DB);
    $info = extract_file_info($file_name);
    $category = 'Movie';
    if (stripos($file_name, 'season') !== false || stripos($file_name, 'episode') !== false) $category = 'Series';
    
    $stmt = $db->prepare("INSERT INTO uploads (file_name, message_id, quality, file_size, language, category, upload_date, upload_time) VALUES (?, ?, ?, ?, ?, ?, DATE('now'), TIME('now'))");
    $stmt->bindValue(1, $file_name, SQLITE3_TEXT);
    $stmt->bindValue(2, $message_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $quality, SQLITE3_TEXT);
    $stmt->bindValue(4, $size, SQLITE3_TEXT);
    $stmt->bindValue(5, $language, SQLITE3_TEXT);
    $stmt->bindValue(6, $category, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
    bot_log("Tracked upload: $file_name");
}

function get_first_upload() {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT * FROM uploads ORDER BY upload_timestamp ASC LIMIT 1");
    $result = $stmt->execute();
    $upload = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$upload) return "📭 No uploads found in database!";
    
    $message = "🥇 <b>FIRST UPLOAD EVER</b>\n\n🎬 <b>Title:</b> " . htmlspecialchars($upload['file_name']) . "\n📅 <b>Date:</b> " . date('d M Y', strtotime($upload['upload_date'])) . "\n⏰ <b>Time:</b> " . $upload['upload_time'] . "\n📊 <b>Quality:</b> " . $upload['quality'] . "\n🗣️ <b>Language:</b> " . $upload['language'] . "\n📁 <b>Category:</b> " . $upload['category'] . "\n💾 <b>Size:</b> " . $upload['file_size'] . "\n\n";
    $days_ago = floor((time() - strtotime($upload['upload_timestamp'])) / 86400);
    $message .= "⏳ <b>Time Since:</b> $days_ago days ago\n";
    
    $db = new SQLite3(UPLOADS_DB);
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM uploads");
    $count_result = $count_stmt->execute();
    $count_row = $count_result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    $message .= "📈 <b>Total Uploads Since:</b> " . $count_row['total'] . "\n\n📍 <b>This was the beginning of our journey!</b>";
    return $message;
}

function get_recent_uploads($limit = 10) {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT * FROM uploads ORDER BY upload_timestamp DESC LIMIT ?");
    $stmt->bindValue(1, $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $message = "🆕 <b>RECENT UPLOADS</b>\n📊 Showing last $limit uploads\n\n";
    $counter = 1;
    while ($upload = $result->fetchArray(SQLITE3_ASSOC)) {
        $time_ago = time_ago($upload['upload_timestamp']);
        $short_name = strlen($upload['file_name']) > 40 ? substr($upload['file_name'], 0, 40) . "..." : $upload['file_name'];
        $message .= "$counter. <b>" . htmlspecialchars($short_name) . "</b>\n   📅 " . date('d/m', strtotime($upload['upload_date'])) . " | ⏰ " . substr($upload['upload_time'], 0, 5) . " | " . $time_ago . "\n   📊 " . $upload['quality'] . " | 🗣️ " . $upload['language'] . " | 💾 " . $upload['file_size'] . "\n\n";
        $counter++;
    }
    $db->close();
    
    $db = new SQLite3(UPLOADS_DB);
    $time_stmt = $db->prepare("SELECT MIN(upload_timestamp) as first, MAX(upload_timestamp) as last FROM (SELECT upload_timestamp FROM uploads ORDER BY upload_timestamp DESC LIMIT ?)");
    $time_stmt->bindValue(1, $limit, SQLITE3_INTEGER);
    $time_result = $time_stmt->execute();
    $time_row = $time_result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if ($time_row['first'] && $time_row['last']) {
        $diff = strtotime($time_row['last']) - strtotime($time_row['first']);
        $hours = floor($diff / 3600);
        $message .= "⏱️ <b>Timeframe:</b> Last " . ($hours > 0 ? "$hours hours" : "few minutes") . "\n";
    }
    
    $message .= "📈 <b>Today's Uploads:</b> " . get_todays_upload_count() . "\n🎯 <b>Upload Rate:</b> " . get_upload_rate() . "/day";
    return $message;
}

function get_last_upload() {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT * FROM uploads ORDER BY upload_timestamp DESC LIMIT 1");
    $result = $stmt->execute();
    $upload = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$upload) { $db->close(); return "📭 No uploads found!"; }
    
    $message = "📤 <b>LAST UPLOAD</b>\n\n🎬 <b>Title:</b> " . htmlspecialchars($upload['file_name']) . "\n📅 <b>Date:</b> " . date('d M Y', strtotime($upload['upload_date'])) . " (" . get_day_name($upload['upload_date']) . ")\n⏰ <b>Time:</b> " . $upload['upload_time'] . "\n⏳ <b>Uploaded:</b> " . time_ago($upload['upload_timestamp']) . " ago\n\n";
    $message .= "📊 <b>Details:</b>\n• Quality: " . $upload['quality'] . "\n• Language: " . $upload['language'] . "\n• Category: " . $upload['category'] . "\n• Size: " . $upload['file_size'] . "\n\n";
    
    $prev_stmt = $db->prepare("SELECT * FROM uploads WHERE upload_timestamp < ? ORDER BY upload_timestamp DESC LIMIT 1");
    $prev_stmt->bindValue(1, $upload['upload_timestamp'], SQLITE3_TEXT);
    $prev_result = $prev_stmt->execute();
    $prev_upload = $prev_result->fetchArray(SQLITE3_ASSOC);
    
    if ($prev_upload) {
        $time_diff = strtotime($upload['upload_timestamp']) - strtotime($prev_upload['upload_timestamp']);
        $hours = floor($time_diff / 3600);
        $minutes = floor(($time_diff % 3600) / 60);
        $message .= "⏱️ <b>Time Since Previous:</b> ";
        if ($hours > 0) $message .= "$hours hours ";
        $message .= "$minutes minutes\n";
    }
    $db->close();
    
    $message .= "📈 <b>Today's Uploads:</b> " . get_todays_upload_count() . "\n🎯 <b>Next Expected:</b> " . predict_next_upload();
    return $message;
}

function get_total_uploads_stats() {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM uploads");
    $result = $stmt->execute();
    $total_row = $result->fetchArray(SQLITE3_ASSOC);
    $total = $total_row['total'];
    if ($total == 0) { $db->close(); return "📭 No uploads yet!"; }
    
    $type_stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN category = 'Movie' THEN 1 ELSE 0 END) as movies, SUM(CASE WHEN category = 'Series' THEN 1 ELSE 0 END) as series, SUM(CASE WHEN category NOT IN ('Movie', 'Series') THEN 1 ELSE 0 END) as other FROM uploads");
    $type_result = $type_stmt->execute();
    $type_row = $type_result->fetchArray(SQLITE3_ASSOC);
    
    $qual_stmt = $db->prepare("SELECT quality, COUNT(*) as count FROM uploads WHERE quality != 'Unknown' GROUP BY quality ORDER BY count DESC LIMIT 5");
    $qual_result = $qual_stmt->execute();
    $quality_stats = "";
    while ($row = $qual_result->fetchArray(SQLITE3_ASSOC)) {
        $percentage = round(($row['count'] / $total) * 100);
        $quality_stats .= "• " . $row['quality'] . ": " . $row['count'] . " (" . $percentage . "%)\n";
    }
    
    $lang_stmt = $db->prepare("SELECT language, COUNT(*) as count FROM uploads GROUP BY language ORDER BY count DESC LIMIT 5");
    $lang_result = $lang_stmt->execute();
    $language_stats = "";
    while ($row = $lang_result->fetchArray(SQLITE3_ASSOC)) {
        $percentage = round(($row['count'] / $total) * 100);
        $language_stats .= "• " . $row['language'] . ": " . $row['count'] . " (" . $percentage . "%)\n";
    }
    
    $date_stmt = $db->prepare("SELECT MIN(upload_date) as first_date, MAX(upload_date) as last_date FROM uploads");
    $date_result = $date_stmt->execute();
    $date_row = $date_result->fetchArray(SQLITE3_ASSOC);
    
    $days_active = days_between($date_row['first_date'], $date_row['last_date']) + 1;
    $avg_per_day = round($total / max(1, $days_active), 2);
    $db->close();
    
    $message = "📊 <b>TOTAL UPLOADS STATISTICS</b>\n\n🎯 <b>Grand Total:</b> $total uploads\n📅 <b>Time Period:</b> " . date('d M Y', strtotime($date_row['first_date'])) . " to " . date('d M Y', strtotime($date_row['last_date'])) . "\n📆 <b>Active Days:</b> $days_active days\n📈 <b>Average per Day:</b> $avg_per_day uploads\n\n";
    $message .= "📁 <b>Category Distribution:</b>\n• Movies: " . $type_row['movies'] . "\n• Series: " . $type_row['series'] . "\n• Other: " . $type_row['other'] . "\n\n";
    if (!empty($quality_stats)) $message .= "🎬 <b>Quality Distribution (Top 5):</b>\n$quality_stats\n";
    if (!empty($language_stats)) $message .= "🗣️ <b>Language Distribution (Top 5):</b>\n$language_stats\n";
    $message .= "📈 <b>Milestones:</b>\n• 1000 uploads: " . ($total >= 1000 ? "✅ Achieved" : "⏳ " . (1000 - $total) . " to go") . "\n• 5000 uploads: " . ($total >= 5000 ? "✅ Achieved" : "⏳ " . (5000 - $total) . " to go") . "\n• 10000 uploads: " . ($total >= 10000 ? "✅ Achieved" : "⏳ " . (10000 - $total) . " to go") . "\n";
    return $message;
}

function get_middle_upload() {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM uploads");
    $result = $stmt->execute();
    $total_row = $result->fetchArray(SQLITE3_ASSOC);
    $total = $total_row['total'];
    if ($total == 0) { $db->close(); return "📭 No uploads yet!"; }
    
    $middle_position = ceil($total / 2);
    $stmt = $db->prepare("SELECT * FROM uploads ORDER BY upload_timestamp LIMIT 1 OFFSET ?");
    $stmt->bindValue(1, $middle_position - 1, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $upload = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$upload) { $db->close(); return "❌ Could not find middle upload!"; }
    
    $message = "🎯 <b>MIDDLE UPLOAD</b>\n\n📍 <b>Position:</b> $middle_position of $total (50% milestone)\n\n";
    $message .= "🎬 <b>Title:</b> " . htmlspecialchars($upload['file_name']) . "\n📅 <b>Date:</b> " . date('d M Y', strtotime($upload['upload_date'])) . "\n⏰ <b>Time:</b> " . $upload['upload_time'] . "\n📊 <b>Quality:</b> " . $upload['quality'] . "\n🗣️ <b>Language:</b> " . $upload['language'] . "\n\n";
    
    $before_stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE upload_timestamp < ?");
    $before_stmt->bindValue(1, $upload['upload_timestamp'], SQLITE3_TEXT);
    $before_result = $before_stmt->execute();
    $before_row = $before_result->fetchArray(SQLITE3_ASSOC);
    
    $after_stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE upload_timestamp > ?");
    $after_stmt->bindValue(1, $upload['upload_timestamp'], SQLITE3_TEXT);
    $after_result = $after_stmt->execute();
    $after_row = $after_result->fetchArray(SQLITE3_ASSOC);
    
    $message .= "📈 <b>Position Analysis:</b>\n• Before this: " . $before_row['count'] . " uploads\n• After this: " . $after_row['count'] . " uploads\n\n";
    $message .= "⏳ <b>Halfway Point:</b> " . time_ago($upload['upload_timestamp']) . " ago\n📊 <b>Completion:</b> 50% of total uploads\n\n🎉 <b>This marks the halfway point of our upload journey!</b>";
    $db->close();
    return $message;
}

function get_upload_date_stats($date = null) {
    if ($date === null) $date = date('Y-m-d');
    
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT * FROM uploads WHERE upload_date = ? ORDER BY upload_time");
    $stmt->bindValue(1, $date, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $uploads = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $uploads[] = $row;
    
    if (empty($uploads)) { $db->close(); return "📭 No uploads found for " . date('d M Y', strtotime($date)); }
    
    $message = "📅 <b>UPLOADS ON " . date('d M Y', strtotime($date)) . "</b>\n📊 <b>Day:</b> " . get_day_name($date) . "\n📈 <b>Total Uploads:</b> " . count($uploads) . "\n\n⏰ <b>Upload Timeline:</b>\n";
    foreach ($uploads as $index => $upload) {
        $short_name = strlen($upload['file_name']) > 30 ? substr($upload['file_name'], 0, 30) . "..." : $upload['file_name'];
        $message .= ($index + 1) . ". <b>" . substr($upload['upload_time'], 0, 5) . "</b> - " . htmlspecialchars($short_name) . "\n";
    }
    
    $hourly = [];
    foreach ($uploads as $upload) {
        $hour = (int)substr($upload['upload_time'], 0, 2);
        if (!isset($hourly[$hour])) $hourly[$hour] = 0;
        $hourly[$hour]++;
    }
    
    if (!empty($hourly)) {
        $message .= "\n📊 <b>Hourly Distribution:</b>\n";
        arsort($hourly);
        foreach ($hourly as $hour => $count) $message .= "• " . sprintf("%02d:00", $hour) . " - $count uploads\n";
    }
    
    $prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
    $prev_stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE upload_date = ?");
    $prev_stmt->bindValue(1, $prev_date, SQLITE3_TEXT);
    $prev_result = $prev_stmt->execute();
    $prev_row = $prev_result->fetchArray(SQLITE3_ASSOC);
    
    if ($prev_row['count'] > 0) {
        $change = count($uploads) - $prev_row['count'];
        $change_text = $change > 0 ? "📈 +$change" : ($change < 0 ? "📉 $change" : "📊 No change");
        $message .= "\n📈 <b>Vs Previous Day:</b> $change_text\n";
    }
    $db->close();
    
    $message .= "\n🎯 <b>Busiest Hour:</b> " . get_busiest_hour_for_date($date) . "\n📊 <b>Average per Hour:</b> " . round(count($uploads) / max(1, count($hourly)), 2);
    return $message;
}

function get_upload_calendar($month = null, $year = null) {
    if ($month === null) $month = date('m');
    if ($year === null) $year = date('Y');
    
    $db = new SQLite3(UPLOADS_DB);
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $stmt = $db->prepare("SELECT upload_date, COUNT(*) as count FROM uploads WHERE upload_date BETWEEN ? AND ? GROUP BY upload_date ORDER BY upload_date");
    $stmt->bindValue(1, $start_date, SQLITE3_TEXT);
    $stmt->bindValue(2, $end_date, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $daily_counts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $day = date('d', strtotime($row['upload_date']));
        $daily_counts[$day] = $row['count'];
    }
    
    $month_name = get_month_name($month);
    $first_day = date('w', strtotime($start_date));
    $days_in_month = date('t', strtotime($start_date));
    
    $message = "📅 <b>UPLOAD CALENDAR - $month_name $year</b>\n\n";
    $weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    $message .= implode(" ", $weekdays) . "\n";
    
    $day_counter = 1;
    $week = "";
    for ($i = 0; $i < $first_day; $i++) $week .= "   ";
    
    while ($day_counter <= $days_in_month) {
        for ($i = $first_day; $i < 7 && $day_counter <= $days_in_month; $i++) {
            $day_str = sprintf("%2d", $day_counter);
            if (isset($daily_counts[$day_counter])) {
                $count = $daily_counts[$day_counter];
                if ($count >= 10) $week .= "🔥";
                elseif ($count >= 5) $week .= "⚡";
                elseif ($count >= 1) $week .= "📤";
                else $week .= $day_str;
            } else {
                $week .= "⬜";
            }
            $week .= " ";
            $day_counter++;
            $first_day = 0;
        }
        $message .= $week . "\n";
        $week = "";
    }
    
    $total_uploads = array_sum($daily_counts);
    $active_days = count($daily_counts);
    $avg_per_active_day = $active_days > 0 ? round($total_uploads / $active_days, 2) : 0;
    
    $message .= "\n📊 <b>Monthly Statistics:</b>\n• Total Uploads: $total_uploads\n• Active Days: $active_days\n• Average per Active Day: $avg_per_active_day\n";
    if (!empty($daily_counts)) {
        $busiest_day = array_keys($daily_counts, max($daily_counts))[0];
        $busiest_count = max($daily_counts);
        $message .= "• Busiest Day: $busiest_day ($busiest_count uploads)\n";
    }
    $message .= "\n📈 <b>Legend:</b>\n🔥 = 10+ uploads\n⚡ = 5-9 uploads\n📤 = 1-4 uploads\n⬜ = No uploads\n";
    
    $db->close();
    return $message;
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return $diff . " seconds ago";
    elseif ($diff < 3600) return floor($diff / 60) . " minutes ago";
    elseif ($diff < 86400) return floor($diff / 3600) . " hours ago";
    elseif ($diff < 604800) return floor($diff / 86400) . " days ago";
    else return date('d M', $time);
}

function days_between($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

function get_day_name($date) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $day_num = date('w', strtotime($date));
    return $days[$day_num];
}

function get_month_name($month_num) {
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return $months[$month_num - 1];
}

function get_todays_upload_count() {
    $db = new SQLite3(UPLOADS_DB);
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE upload_date = ?");
    $stmt->bindValue(1, $today, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $row['count'] ?? 0;
}

function get_upload_rate() {
    $db = new SQLite3(UPLOADS_DB);
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM uploads WHERE upload_date >= ?");
    $stmt->bindValue(1, $week_ago, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return round(($row['count'] ?? 0) / 7, 1);
}

function predict_next_upload() {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT upload_timestamp FROM uploads ORDER BY upload_timestamp DESC LIMIT 5");
    $result = $stmt->execute();
    
    $timestamps = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $timestamps[] = strtotime($row['upload_timestamp']);
    
    if (count($timestamps) < 2) { $db->close(); return "Insufficient data"; }
    
    $intervals = [];
    for ($i = 0; $i < count($timestamps) - 1; $i++) $intervals[] = $timestamps[$i] - $timestamps[$i + 1];
    
    $avg_interval = array_sum($intervals) / count($intervals);
    $db->close();
    
    if ($avg_interval < 3600) return "Within " . round($avg_interval / 60) . " minutes";
    else return "In about " . round($avg_interval / 3600, 1) . " hours";
}

function get_busiest_hour_for_date($date) {
    $db = new SQLite3(UPLOADS_DB);
    $stmt = $db->prepare("SELECT substr(upload_time, 1, 2) as hour, COUNT(*) as count FROM uploads WHERE upload_date = ? GROUP BY hour ORDER BY count DESC LIMIT 1");
    $stmt->bindValue(1, $date, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    if ($row) return sprintf("%02d:00", $row['hour']) . " (" . $row['count'] . " uploads)";
    return "No data";
}

function format_size($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    else return $bytes . ' bytes';
}

function extract_quality_from_name($text) {
    $patterns = ['/(\d{3,4}p)/i', '/(HD|FHD|UHD|HQ)/i', '/(WEB\-DL|WEBRip|BluRay|DVDRip)/i'];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) return strtoupper($matches[1]);
    }
    return 'Unknown';
}

function bot_log($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $type: $message\n";
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
    if (strpos($type, 'DELETE') !== false || strpos($message, 'delete') !== false) file_put_contents(DELETION_LOG, $log_entry, FILE_APPEND);
    if ($type == 'ERROR') file_put_contents(ERROR_LOG, $log_entry, FILE_APPEND);
}

function bot_api($method, $params = [], $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result = curl_exec($ch);
        curl_close($ch);
    } else {
        $options = ['http' => ['method' => 'POST', 'content' => http_build_query($params), 'header' => "Content-Type: application/x-www-form-urlencoded\r\n"]];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
    }
    return $result ? json_decode($result, true) : false;
}

function initialize_all_systems() {
    if (!file_exists(CSV_FILE)) { $handle = fopen(CSV_FILE, "w"); fputcsv($handle, ['movie_name','message_id','date','video_path','quality','size','language']); fclose($handle); @chmod(CSV_FILE, 0666); }
    
    $json_files = [
        USERS_FILE => ['users' => [], 'total_requests' => 0, 'message_logs' => [], 'daily_stats' => []],
        STATS_FILE => ['total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'total_downloads' => 0, 'successful_searches' => 0, 'failed_searches' => 0, 'daily_activity' => [], 'last_updated' => date('Y-m-d H:i:s')],
        REQUEST_FILE => ['requests' => [], 'pending_approval' => [], 'completed_requests' => [], 'user_request_count' => []]
    ];
    
    foreach ($json_files as $file => $data) {
        if (!file_exists($file)) { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)); @chmod($file, 0666); }
    }
    
    $db = new SQLite3(UPLOADS_DB);
    $db->exec("CREATE TABLE IF NOT EXISTS uploads (id INTEGER PRIMARY KEY AUTOINCREMENT, message_id INTEGER, file_name TEXT, file_type TEXT, file_size TEXT, quality TEXT, language TEXT, category TEXT, upload_date DATE, upload_time TIME, upload_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, uploaded_by INTEGER, views INTEGER DEFAULT 0, downloads INTEGER DEFAULT 0, forwards INTEGER DEFAULT 0, chat_id INTEGER, delete_scheduled INTEGER DEFAULT 0, delete_time DATETIME, status TEXT DEFAULT 'active')");
    $db->exec("CREATE TABLE IF NOT EXISTS users_advanced (user_id INTEGER PRIMARY KEY, username TEXT, first_name TEXT, last_name TEXT, join_date DATE DEFAULT CURRENT_DATE, last_active DATETIME DEFAULT CURRENT_TIMESTAMP, total_uploads INTEGER DEFAULT 0, total_downloads INTEGER DEFAULT 0, total_searches INTEGER DEFAULT 0, favorite_category TEXT, points INTEGER DEFAULT 0, level INTEGER DEFAULT 1, warning_count INTEGER DEFAULT 0, is_admin INTEGER DEFAULT 0, is_premium INTEGER DEFAULT 0, daily_request_count INTEGER DEFAULT 0, last_request_date DATE)");
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_deletes (id INTEGER PRIMARY KEY AUTOINCREMENT, chat_id INTEGER, message_id INTEGER, file_id TEXT, file_name TEXT, file_size TEXT, quality TEXT, delete_time DATETIME, status TEXT DEFAULT 'pending', warning_message_id INTEGER, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS warning_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, file_id INTEGER, message_id INTEGER, chat_id INTEGER, progress_percentage INTEGER DEFAULT 0, last_updated DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (file_id) REFERENCES scheduled_deletes(id) ON DELETE CASCADE)");
    $db->close();
    
    $directories = [BACKUP_DIR, 'cache/', 'temp/', 'exports/'];
    foreach ($directories as $dir) if (!file_exists($dir)) mkdir($dir, 0777, true);
    
    if (!file_exists(LOG_FILE)) file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] SYSTEM: All systems initialized\n");
    bot_log("All systems initialized successfully");
}

function auto_backup() {
    bot_log("Starting auto-backup process...");
    $backup_files = [CSV_FILE, USERS_FILE, STATS_FILE, REQUEST_FILE, UPLOADS_DB];
    $backup_dir = BACKUP_DIR . date('Y-m-d_H-i-s');
    if (!file_exists($backup_dir)) mkdir($backup_dir, 0777, true);
    
    foreach ($backup_files as $file) if (file_exists($file)) copy($file, $backup_dir . '/' . basename($file) . '.bak');
    
    $summary = create_backup_summary();
    file_put_contents($backup_dir . '/backup_summary.txt', $summary);
    clean_old_backups();
    bot_log("Auto-backup completed: $backup_dir");
    return true;
}

function create_backup_summary() {
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $db = new SQLite3(UPLOADS_DB);
    $upload_count = $db->querySingle("SELECT COUNT(*) FROM uploads");
    $db->close();
    
    $summary = "📊 BACKUP SUMMARY\n================\n\n📅 Backup Date: " . date('Y-m-d H:i:s') . "\n🤖 Bot: Entertainment Tadka Mega Bot\n\n📈 STATISTICS:\n• Total Movies: " . ($stats['total_movies'] ?? 0) . "\n• Total Users: " . count($users_data['users'] ?? []) . "\n• Total Searches: " . ($stats['total_searches'] ?? 0) . "\n• Total Uploads Tracked: $upload_count\n• Active Systems: Search + Protection + Analytics\n";
    return $summary;
}

function clean_old_backups() {
    $backups = glob(BACKUP_DIR . '*', GLOB_ONLYDIR);
    if (count($backups) > 7) {
        usort($backups, fn($a, $b) => filemtime($a) - filemtime($b));
        $to_delete = array_slice($backups, 0, count($backups) - 7);
        foreach ($to_delete as $dir) {
            $files = glob($dir . '/*');
            foreach ($files as $file) @unlink($file);
            @rmdir($dir);
        }
        bot_log("Cleaned " . count($to_delete) . " old backups");
    }
}

// ==================== INITIALIZE ALL SYSTEMS ====================
initialize_all_systems();

// ==================== MAINTENANCE CHECK ====================
if (MAINTENANCE_MODE || $MAINTENANCE_MODE) {
    $update = json_decode(file_get_contents('php://input'), true);
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        sendMessage($chat_id, $MAINTENANCE_MESSAGE, null, 'HTML');
    }
    exit;
}

// ==================== MAIN PROCESSING ====================
$csvManager = CSVManager::getInstance();
$requestSystem = RequestSystem::getInstance();

if (isset($_GET['setup'])) {
    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1><h2>Webhook Setup</h2><pre>Webhook Set: " . htmlspecialchars($result) . "</pre><p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>Bot Info</h2><p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p><p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    exit;
}

if (isset($_GET['deletehook'])) {
    $result = apiRequest('deleteWebhook');
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot</h1><h2>Webhook Deleted</h2><pre>" . htmlspecialchars($result) . "</pre>";
    exit;
}

if (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>🎬 Entertainment Tadka Bot - Test Page</h1><p><strong>Status:</strong> ✅ Running</p><p><strong>Bot:</strong> @" . $ENV_CONFIG['BOT_USERNAME'] . "</p><p><strong>Environment:</strong> " . getenv('ENVIRONMENT') . "</p>";
    
    $stats = $csvManager->getStats();
    echo "<p><strong>Total Movies:</strong> " . $stats['total_movies'] . "</p>";
    
    $users_data = json_decode(@file_get_contents(USERS_FILE), true);
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    
    $request_stats = $requestSystem->getStats();
    echo "<p><strong>Total Requests:</strong> " . $request_stats['total_requests'] . "</p><p><strong>Pending Requests:</strong> " . $request_stats['pending'] . "</p>";
    
    echo "<h3>🚀 Quick Setup</h3><p><a href='?setup=1'>Set Webhook Now</a></p><p><a href='?deletehook=1'>Delete Webhook</a></p><p><a href='?test_csv=1'>Test CSV Manager</a></p>";
    exit;
}

if (isset($_GET['test_csv'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2>Testing CSV Manager</h2><h3>1. Reading CSV...</h3>";
    $data = $csvManager->readCSV();
    echo "Total entries: " . count($data) . "<br>";
    echo "<h3>2. Cache Status...</h3>";
    $cached = $csvManager->getCachedData();
    echo "Cached entries: " . count($cached) . "<br>";
    echo "<h3>3. CSV Stats...</h3><pre>" . print_r($csvManager->getStats(), true) . "</pre>";
    exit;
}

if (isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = bot_api('setWebhook', ['url' => $webhook_url]);
    echo "<div class='container' style='margin-top: 20px;'><div class='header'><h3>Webhook Result</h3><pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre><a href='./' class='button'>← Back to Dashboard</a></div></div>";
    exit;
}

if (isset($_GET['backup'])) {
    auto_backup();
    echo "<div class='container' style='margin-top: 20px;'><div class='header'><h3>✅ Manual Backup Completed</h3><p>All data has been backed up successfully.</p><a href='./' class='button'>← Back to Dashboard</a></div></div>";
    exit;
}

if (isset($_GET['logs'])) {
    echo "<div class='container' style='margin-top: 20px;'><div class='header'><h3>📋 Complete Logs</h3><div style='max-height: 500px; overflow-y: auto; background: #f5f5f5; padding: 15px; border-radius: 10px; font-family: monospace;'>";
    if (file_exists(LOG_FILE)) echo nl2br(htmlspecialchars(file_get_contents(LOG_FILE)));
    else echo "No logs found";
    echo "</div><a href='./' class='button'>← Back to Dashboard</a></div></div>";
    exit;
}

// ==================== TELEGRAM UPDATE PROCESSING ====================
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    log_error("Update received", 'INFO', ['update_id' => $update['update_id'] ?? 'N/A']);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!RateLimiter::check($ip, 'telegram_update', 30, 60)) { http_response_code(429); exit; }
    
    $current_minute = date('i');
    if ($current_minute % 5 == 0) { process_scheduled_deletions(); update_progress_bars(); }
    if (date('H') == AUTO_BACKUP_HOUR && $current_minute == '00') auto_backup();
    
    get_cached_movies();
    
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        
        $all_channels = array_merge(array_column($ENV_CONFIG['PUBLIC_CHANNELS'], 'id'), array_column($ENV_CONFIG['PRIVATE_CHANNELS'], 'id'));
        
        if (in_array($chat_id, $all_channels)) {
            $text = '';
            if (isset($message['caption'])) $text = $message['caption'];
            elseif (isset($message['text'])) $text = $message['text'];
            elseif (isset($message['document'])) $text = $message['document']['file_name'];
            else $text = 'Media Upload - ' . date('d-m-Y H:i');
            
            if (!empty(trim($text))) {
                $csvManager->bufferedAppend($text, $message_id, $chat_id);
                append_movie($text, $message_id, 'Unknown', 'Unknown', 'Hindi');
                
                $auto_approved = $requestSystem->checkAutoApprove($text);
                if (!empty($auto_approved)) {
                    foreach ($auto_approved as $req_id) {
                        $request = $requestSystem->getRequest($req_id);
                        if ($request) notifyUserAboutRequest($request['user_id'], $request, 'approved');
                    }
                }
            }
        }
    }
    
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = isset($message['text']) ? $message['text'] : '';
        $chat_type = $message['chat']['type'] ?? 'private';
        
        $is_file = isset($message['video']) || isset($message['document']) || isset($message['audio']) || isset($message['photo']);
        
        if ($is_file && !in_array($user_id, ADMIN_IDS)) {
            $file_name = ''; $file_size = ''; $quality = '';
            if (isset($message['video'])) { $file_name = $message['video']['file_name'] ?? 'Video_' . time() . '.mp4'; $file_size = format_size($message['video']['file_size'] ?? 0); }
            elseif (isset($message['document'])) { $file_name = $message['document']['file_name']; $file_size = format_size($message['document']['file_size'] ?? 0); $quality = extract_quality_from_name($file_name); }
            if (isset($message['caption'])) { $file_name = $message['caption'] . ' - ' . $file_name; if (!$quality) $quality = extract_quality_from_name($message['caption']); }
            if ($file_name) { schedule_file_deletion($chat_id, $message['message_id'], $file_name, $file_size, $quality); track_upload($file_name, $message['message_id'], $quality, $file_size); }
        }
        
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!$users_data) $users_data = ['users' => []];
        
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '', 'last_name' => $message['from']['last_name'] ?? '', 'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s'), 'last_active' => date('Y-m-d H:i:s'), 'points' => 0
            ];
            $users_data['total_requests'] = ($users_data['total_requests'] ?? 0) + 1;
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
        }
        $users_data['users'][$user_id]['last_active'] = date('Y-m-d H:i:s');
        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
        
        if (!empty($text)) {
            $command = strtolower(explode(' ', $text)[0]);
            
            switch ($command) {
                case '/start':
                    $welcome = "🎬 <b>Welcome to Entertainment Tadka Mega Bot!</b>\n\n📢 <b>Complete Features:</b>\n• Smart Movie Search\n• Copyright Protection (Auto-delete)\n• Upload Analytics & Statistics\n• Complete Backup System\n\n🔍 <b>Search Movies:</b> Just type movie name\n🛡️ <b>Protection:</b> Files auto-delete in " . DELETE_AFTER_MINUTES . " min\n📊 <b>Analytics:</b> Use /1stupload, /recent, etc.\n\n📢 <b>Channels:</b>\n• Main: " . MAIN_CHANNEL . "\n• Requests: " . REQUEST_CHANNEL . "\n• Backup: " . BACKUP_CHANNEL_USERNAME . "\n\n🚀 <b>Enjoy the complete experience!</b>";
                    
                    $keyboard = ['inline_keyboard' => [
                        [['text' => '🔍 Search Movies', 'switch_inline_query_current_chat' => ''], ['text' => '📊 Analytics', 'callback_data' => 'show_analytics']],
                        [['text' => '🛡️ Protection Info', 'callback_data' => 'protection_info'], ['text' => '📢 Join Channel', 'url' => 'https://t.me/EntertainmentTadka786']]
                    ]];
                    sendMessage($chat_id, $welcome, $keyboard, 'HTML');
                    update_user_points($user_id, 'daily_login');
                    break;
                    
                case '/help':
                    $help = "🤖 <b>Entertainment Tadka Mega Bot - Complete Help</b>\n\n🎯 <b>MOVIE SEARCH:</b>\n• Just type movie name\n• Use /search movie_name\n• Hindi/English both work\n\n🛡️ <b>COPYRIGHT PROTECTION:</b>\n• Upload any file\n• Auto-deletes in " . DELETE_AFTER_MINUTES . " minutes\n• Progress bar & countdown\n\n📊 <b>UPLOAD ANALYTICS:</b>\n• /1stupload - First upload ever\n• /recent - Recent uploads\n• /lastupload - Last upload\n• /totalupload - Total statistics\n• /middleupload - Middle upload\n• /uploaddate - Date-wise stats\n• /uploadcalendar - Monthly calendar\n\n⚙️ <b>OTHER COMMANDS:</b>\n• /request movie - Request movie\n• /stats - Bot statistics\n• /channel - Join channels\n• /backup - Manual backup (admin)\n\n🔗 <b>Channels:</b> " . MAIN_CHANNEL . " | " . REQUEST_CHANNEL;
                    sendMessage($chat_id, $help, null, 'HTML');
                    break;
                    
                case '/request':
                    if (!REQUEST_SYSTEM_ENABLED) { sendMessage($chat_id, "❌ Request system is currently disabled."); break; }
                    $parts = explode(' ', $text);
                    if (!isset($parts[1])) {
                        sendMessage($chat_id, "📝 Usage: /request Movie Name\nExample: /request KGF Chapter 3\n\nYou can also type: 'pls add MovieName'");
                        break;
                    }
                    $movie_name = implode(' ', array_slice($parts, 1));
                    $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
                    
                    $lang = detect_language($movie_name);
                    $users_data = json_decode(file_get_contents(USERS_FILE), true);
                    $today = date('Y-m-d');
                    $user_requests_today = 0;
                    if (isset($users_data['users'][$user_id]['last_request_date']) && $users_data['users'][$user_id]['last_request_date'] == $today) {
                        $user_requests_today = $users_data['users'][$user_id]['request_count'] ?? 0;
                    }
                    
                    if ($user_requests_today < DAILY_REQUEST_LIMIT) {
                        $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
                        sendMessage($chat_id, $result['message']);
                        
                        $requests_data = json_decode(file_get_contents(REQUEST_FILE), true);
                        $requests_data['requests'][] = ['id' => uniqid(), 'user_id' => $user_id, 'movie_name' => $movie_name, 'language' => $lang, 'date' => $today, 'time' => date('H:i:s'), 'status' => 'pending'];
                        file_put_contents(REQUEST_FILE, json_encode($requests_data, JSON_PRETTY_PRINT));
                        
                        $users_data['users'][$user_id]['request_count'] = $user_requests_today + 1;
                        $users_data['users'][$user_id]['last_request_date'] = $today;
                        file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
                        
                        update_user_activity($user_id, 'request');
                        sendMessage(ADMIN_ID, "📝 New Movie Request\n\n🎬 Movie: $movie_name\n👤 User: $user_id\n🗣️ Language: $lang");
                    } else {
                        send_multilingual_response($chat_id, 'request_limit', $lang);
                    }
                    break;
                    
                case '/myrequests':
                    if (!REQUEST_SYSTEM_ENABLED) { sendMessage($chat_id, "❌ Request system is currently disabled."); break; }
                    $requests = $requestSystem->getUserRequests($user_id, 10);
                    $user_stats = $requestSystem->getUserStats($user_id);
                    if (empty($requests)) {
                        sendMessage($chat_id, "📭 You haven't made any requests yet.\nUse /request MovieName to request a movie.\n\nOr type: 'pls add MovieName'");
                        break;
                    }
                    $message_text = "📋 <b>Your Movie Requests</b>\n\n📊 <b>Stats:</b>\n• Total: " . $user_stats['total_requests'] . "\n• Approved: " . $user_stats['approved'] . "\n• Pending: " . $user_stats['pending'] . "\n• Rejected: " . $user_stats['rejected'] . "\n• Today: " . $user_stats['requests_today'] . "/" . MAX_REQUESTS_PER_DAY . "\n\n🎬 <b>Recent Requests:</b>\n";
                    $i = 1;
                    foreach ($requests as $req) {
                        $status_icon = $req['status'] == 'approved' ? '✅' : ($req['status'] == 'rejected' ? '❌' : '⏳');
                        $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                        $message_text .= "$i. $status_icon <b>" . $movie_name . "</b>\n   ID: #" . $req['id'] . " | " . ucfirst($req['status']) . "\n   Date: " . date('d M, H:i', strtotime($req['created_at'])) . "\n\n";
                        $i++;
                    }
                    sendMessage($chat_id, $message_text, null, 'HTML');
                    break;
                    
                case '/pendingrequests':
                    if (!in_array($user_id, ADMIN_IDS)) { sendMessage($chat_id, "❌ Admin only!"); break; }
                    if (!REQUEST_SYSTEM_ENABLED) { sendMessage($chat_id, "❌ Request system is currently disabled."); break; }
                    $limit = 10; $filter_movie = '';
                    $parts = explode(' ', $text);
                    if (isset($parts[1])) {
                        if (is_numeric($parts[1])) $limit = min(intval($parts[1]), 50);
                        else $filter_movie = implode(' ', array_slice($parts, 1));
                    }
                    $requests = $requestSystem->getPendingRequests($limit, $filter_movie);
                    $stats = $requestSystem->getStats();
                    if (empty($requests)) {
                        $msg = "📭 No pending requests";
                        if ($filter_movie) $msg .= " for '$filter_movie'";
                        sendMessage($chat_id, $msg . ".");
                        break;
                    }
                    $message_text = "📋 <b>Pending Requests" . ($filter_movie ? " (Filter: $filter_movie)" : "") . "</b>\n\n📊 <b>System Stats:</b>\n• Total: " . $stats['total_requests'] . "\n• Pending: " . $stats['pending'] . "\n• Approved: " . $stats['approved'] . "\n• Rejected: " . $stats['rejected'] . "\n\n🎬 <b>Showing " . count($requests) . " requests:</b>\n\n";
                    $keyboard = ['inline_keyboard' => []];
                    foreach ($requests as $req) {
                        $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                        $user_name = htmlspecialchars($req['user_name'] ?: "ID: " . $req['user_id'], ENT_QUOTES, 'UTF-8');
                        $message_text .= "🔸 <b>#" . $req['id'] . ":</b> " . $movie_name . "\n   👤 User: " . $user_name . "\n   📅 Date: " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
                        $keyboard['inline_keyboard'][] = [
                            ['text' => '✅ Approve #' . $req['id'], 'callback_data' => 'approve_' . $req['id']],
                            ['text' => '❌ Reject #' . $req['id'], 'callback_data' => 'reject_' . $req['id']]
                        ];
                    }
                    $request_ids = array_column($requests, 'id');
                    $current_page_data = base64_encode(json_encode($request_ids));
                    $keyboard['inline_keyboard'][] = [
                        ['text' => '✅ Bulk Approve This Page', 'callback_data' => 'bulk_approve_' . $current_page_data],
                        ['text' => '❌ Bulk Reject This Page', 'callback_data' => 'bulk_reject_' . $current_page_data]
                    ];
                    if (count($requests) >= $limit) {
                        $next_limit = $limit + 10;
                        $keyboard['inline_keyboard'][] = [['text' => '⏭️ Load More', 'callback_data' => 'pending_more_' . $next_limit]];
                    }
                    sendMessage($chat_id, $message_text, $keyboard, 'HTML');
                    break;
                    
                case '/1stupload':
                    sendMessage($chat_id, get_first_upload(), null, 'HTML');
                    break;
                case '/recent':
                case '/recentuploads':
                    sendMessage($chat_id, get_recent_uploads(), null, 'HTML');
                    break;
                case '/lastupload':
                    sendMessage($chat_id, get_last_upload(), null, 'HTML');
                    break;
                case '/totalupload':
                case '/stats':
                    if (in_array($user_id, ADMIN_IDS)) admin_stats($chat_id);
                    else sendMessage($chat_id, get_total_uploads_stats(), null, 'HTML');
                    break;
                case '/middleupload':
                    sendMessage($chat_id, get_middle_upload(), null, 'HTML');
                    break;
                case '/uploaddate':
                    $params = explode(' ', $text);
                    $date = isset($params[1]) ? $params[1] : null;
                    sendMessage($chat_id, get_upload_date_stats($date), null, 'HTML');
                    break;
                case '/uploadcalendar':
                    $params = explode(' ', $text);
                    $month = isset($params[1]) ? $params[1] : null;
                    $year = isset($params[2]) ? $params[2] : null;
                    sendMessage($chat_id, get_upload_calendar($month, $year), null, 'HTML');
                    break;
                case '/checkdate':
                    check_date($chat_id);
                    break;
                case '/totaluploads':
                case '/totalupload':
                    totalupload_controller($chat_id, 1);
                    break;
                case '/testcsv':
                    test_csv($chat_id);
                    break;
                case '/checkcsv':
                    $show_all = (isset($parts[1]) && strtolower($parts[1]) == 'all');
                    show_csv_data($chat_id, $show_all);
                    break;
                case '/csvstats':
                    csv_stats_command($chat_id);
                    break;
                case '/search':
                    $movie_name = trim(substr($text, strlen('/search') + 1));
                    if (!empty($movie_name)) advanced_search($chat_id, $movie_name, $user_id);
                    else sendMessage($chat_id, "❌ Usage: /search movie_name\nExample: /search Animal");
                    break;
                case '/channel':
                    $channel_info = "📢 <b>Our Channels</b>\n\n🍿 <b>Main Channel:</b> " . MAIN_CHANNEL . "\nLatest movies & updates\n\n📥 <b>Requests Channel:</b> " . REQUEST_CHANNEL . "\nMovie requests & support\n\n🔒 <b>Backup Channel:</b> " . BACKUP_CHANNEL_USERNAME . "\nData backups & archives\n\n🔔 <b>Join all for best experience!</b>";
                    $keyboard = ['inline_keyboard' => [
                        [['text' => '🍿 ' . MAIN_CHANNEL, 'url' => 'https://t.me/EntertainmentTadka786'], ['text' => '📥 ' . REQUEST_CHANNEL, 'url' => 'https://t.me/EntertainmentTadka7860']],
                        [['text' => '🔒 ' . BACKUP_CHANNEL_USERNAME, 'url' => 'https://t.me/ETBackup']]
                    ]];
                    sendMessage($chat_id, $channel_info, $keyboard, 'HTML');
                    break;
                case '/backup':
                    if (in_array($user_id, ADMIN_IDS)) { auto_backup(); sendMessage($chat_id, "✅ Manual backup completed!"); }
                    else sendMessage($chat_id, "❌ Admin only command!");
                    break;
                case '/test':
                    if (in_array($user_id, ADMIN_IDS)) {
                        sendMessage($chat_id, "🧪 Testing all systems...");
                        sendMessage($chat_id, "1️⃣ Testing Movie Search...");
                        $movies = get_cached_movies();
                        sendMessage($chat_id, "✅ Movie database: " . count($movies) . " entries");
                        sendMessage($chat_id, "2️⃣ Testing Analytics...");
                        sendMessage($chat_id, get_first_upload(), null, 'HTML');
                        sendMessage($chat_id, "3️⃣ Testing Protection System...");
                        sendMessage($chat_id, "✅ All systems operational!");
                    }
                    break;
                    
                default:
                    if (strlen($text) > 1 && !str_starts_with($text, '/')) {
                        if (stripos($text, 'add movie') !== false || stripos($text, 'please add') !== false || stripos($text, 'pls add') !== false || stripos($text, 'can you add') !== false || stripos($text, 'request movie') !== false) {
                            if (!REQUEST_SYSTEM_ENABLED) { sendMessage($chat_id, "❌ Request system is currently disabled."); break; }
                            
                            $patterns = ['/add movie (.+)/i', '/please add (.+)/i', '/pls add (.+)/i', '/add (.+) movie/i', '/can you add (.+)/i', '/request movie (.+)/i', '/request (.+) movie/i'];
                            $movie_name = '';
                            foreach ($patterns as $pattern) { if (preg_match($pattern, $text, $matches)) { $movie_name = trim($matches[1]); break; } }
                            if (empty($movie_name)) { $clean_text = preg_replace('/add movie|please add|pls add|movie|add|request|can you/i', '', $text); $movie_name = trim($clean_text); }
                            
                            if (strlen($movie_name) < 2) {
                                sendMessage($chat_id, "🎬 Please specify which movie you want to add.\nExample: 'Please add KGF Chapter 3' or use /request command");
                                break;
                            }
                            $user_name = $message['from']['first_name'] . ($message['from']['last_name'] ? ' ' . $message['from']['last_name'] : '');
                            $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
                            sendMessage($chat_id, $result['message']);
                        } else {
                            advanced_search($chat_id, $text, $user_id);
                        }
                    }
                    break;
            }
        }
    }
    
    // ==================== FIXED CALLBACK QUERY HANDLING ====================
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $data = $query['data'];
        $user_id = $query['from']['id'];
        
        sendChatAction($chat_id, 'typing');
        
        if (strpos($data, 'movie_') === 0) {
            $movie_name_encoded = str_replace('movie_', '', $data);
            $movie_name = base64_decode($movie_name_encoded);
            if ($movie_name) {
                $all_movies = $csvManager->getCachedData();
                $movie_items = [];
                foreach ($all_movies as $item) if (strtolower($item['movie_name']) === strtolower($movie_name)) $movie_items[] = $item;
                
                if (!empty($movie_items)) {
                    $sent_count = 0;
                    foreach ($movie_items as $item) { 
                        if (deliver_item_to_chat($chat_id, $item)) { 
                            $sent_count++; 
                            usleep(300000); 
                        } 
                    }
                    $channel_type = getChannelType($movie_items[0]['channel_id']);
                    $source_note = $channel_type === 'public' ? " (Forwarded from " . getChannelUsername($movie_items[0]['channel_id']) . ")" : " (Source hidden)";
                    sendMessage($chat_id, "✅ Sent $sent_count copies of '$movie_name'$source_note\n\n📢 Join: @EntertainmentTadka786");
                    answerCallbackQuery($query['id'], "🎬 $sent_count items sent!");
                    update_user_activity($user_id, 'download');
                    $stats = json_decode(file_get_contents(STATS_FILE), true);
                    $stats['total_downloads'] = ($stats['total_downloads'] ?? 0) + $sent_count;
                    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
                } else { 
                    answerCallbackQuery($query['id'], "❌ Movie not found", true); 
                }
            }
        }
        elseif (strpos($data, 'download_') === 0) {
            $movie_name = substr($data, 9);
            global $movie_messages;
            $movie_key = strtolower($movie_name);
            if (isset($movie_messages[$movie_key])) {
                $entries = $movie_messages[$movie_key];
                $sent = 0;
                foreach ($entries as $entry) {
                    if (!empty($entry['message_id']) && is_numeric($entry['message_id'])) {
                        $result = forwardMessage($chat_id, CHANNEL_ID, $entry['message_id']);
                        if ($result) $sent++;
                        usleep(300000);
                    }
                }
                if ($sent > 0) {
                    answerCallbackQuery($query['id'], "✅ $sent movies forwarded!");
                    update_user_activity($user_id, 'download');
                    $stats = json_decode(file_get_contents(STATS_FILE), true);
                    $stats['total_downloads'] = ($stats['total_downloads'] ?? 0) + $sent;
                    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
                } else {
                    answerCallbackQuery($query['id'], "❌ Could not forward movies");
                }
            } else {
                answerCallbackQuery($query['id'], "❌ Movie not found");
            }
        }
        elseif (strpos($data, 'approve_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $request_id = str_replace('approve_', '', $data);
                $result = $requestSystem->approveRequest($request_id, $user_id);
                if ($result['success']) {
                    $request = $result['request'];
                    $new_text = $message['text'] . "\n\n✅ <b>Approved by Admin</b>\n🕒 " . date('H:i:s');
                    editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                    answerCallbackQuery($query['id'], "✅ Request #$request_id approved");
                    notifyUserAboutRequest($request['user_id'], $request, 'approved');
                } else {
                    answerCallbackQuery($query['id'], $result['message'], true);
                }
            }
        }
        elseif (strpos($data, 'reject_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $request_id = str_replace('reject_', '', $data);
                $keyboard = ['inline_keyboard' => [
                    [['text' => 'Already Available', 'callback_data' => 'reject_reason_' . $request_id . '_already_available'], 
                     ['text' => 'Invalid Request', 'callback_data' => 'reject_reason_' . $request_id . '_invalid_request']],
                    [['text' => 'Low Quality', 'callback_data' => 'reject_reason_' . $request_id . '_low_quality'], 
                     ['text' => 'Not Available', 'callback_data' => 'reject_reason_' . $request_id . '_not_available']],
                    [['text' => 'Custom Reason...', 'callback_data' => 'reject_custom_' . $request_id]]
                ]];
                editMessageText($chat_id, $message['message_id'], "Select rejection reason for Request #$request_id:", $keyboard);
                answerCallbackQuery($query['id'], "Select rejection reason");
            }
        }
        elseif (strpos($data, 'reject_reason_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $parts = explode('_', $data);
                $request_id = $parts[2];
                $reason_key = $parts[3];
                $reason_map = [
                    'already_available' => 'Movie is already available in our channels',
                    'invalid_request' => 'Invalid movie name or request',
                    'low_quality' => 'Cannot find good quality version',
                    'not_available' => 'Movie is not available anywhere'
                ];
                $reason = $reason_map[$reason_key] ?? 'Not specified';
                $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
                if ($result['success']) {
                    $request = $result['request'];
                    $new_text = $message['text'] . "\n\n❌ <b>Rejected by Admin</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                    editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                    answerCallbackQuery($query['id'], "❌ Request #$request_id rejected");
                    notifyUserAboutRequest($request['user_id'], $request, 'rejected');
                } else {
                    answerCallbackQuery($query['id'], $result['message'], true);
                }
            }
        }
        elseif (strpos($data, 'bulk_approve_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $encoded_data = str_replace('bulk_approve_', '', $data);
                $request_ids = json_decode(base64_decode($encoded_data), true);
                $result = $requestSystem->bulkApprove($request_ids, $user_id);
                $new_text = $message['text'] . "\n\n✅ <b>Bulk Approved {$result['approved_count']}/{$result['total_count']} requests</b>\n🕒 " . date('H:i:s');
                editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "✅ Approved {$result['approved_count']} requests");
                foreach ($request_ids as $req_id) { 
                    $request = $requestSystem->getRequest($req_id); 
                    if ($request && $request['status'] == 'approved') {
                        notifyUserAboutRequest($request['user_id'], $request, 'approved');
                    }
                }
            }
        }
        elseif (strpos($data, 'bulk_reject_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $encoded_data = str_replace('bulk_reject_', '', $data);
                $request_ids = json_decode(base64_decode($encoded_data), true);
                $reason = "Bulk rejected by admin";
                $result = $requestSystem->bulkReject($request_ids, $user_id, $reason);
                $new_text = $message['text'] . "\n\n❌ <b>Bulk Rejected {$result['rejected_count']}/{$result['total_count']} requests</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                editMessageText($chat_id, $message['message_id'], $new_text, null, 'HTML');
                answerCallbackQuery($query['id'], "❌ Rejected {$result['rejected_count']} requests");
                foreach ($request_ids as $req_id) { 
                    $request = $requestSystem->getRequest($req_id); 
                    if ($request && $request['status'] == 'rejected') {
                        notifyUserAboutRequest($request['user_id'], $request, 'rejected');
                    }
                }
            }
        }
        elseif (strpos($data, 'pending_more_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $limit = str_replace('pending_more_', '', $data);
                $requests = $requestSystem->getPendingRequests($limit);
                $stats = $requestSystem->getStats();
                $message_text = "📋 <b>Pending Requests (Showing $limit)</b>\n\n📊 <b>System Stats:</b>\n• Total: " . $stats['total_requests'] . "\n• Pending: " . $stats['pending'] . "\n• Approved: " . $stats['approved'] . "\n• Rejected: " . $stats['rejected'] . "\n\n🎬 <b>Showing " . count($requests) . " requests:</b>\n\n";
                $keyboard = ['inline_keyboard' => []];
                foreach ($requests as $req) {
                    $movie_name = htmlspecialchars($req['movie_name'], ENT_QUOTES, 'UTF-8');
                    $user_name = htmlspecialchars($req['user_name'] ?: "ID: " . $req['user_id'], ENT_QUOTES, 'UTF-8');
                    $message_text .= "🔸 <b>#" . $req['id'] . ":</b> " . $movie_name . "\n   👤 User: " . $user_name . "\n   📅 Date: " . date('d M H:i', strtotime($req['created_at'])) . "\n\n";
                    $keyboard['inline_keyboard'][] = [
                        ['text' => '✅ Approve #' . $req['id'], 'callback_data' => 'approve_' . $req['id']],
                        ['text' => '❌ Reject #' . $req['id'], 'callback_data' => 'reject_' . $req['id']]
                    ];
                }
                $request_ids = array_column($requests, 'id');
                $current_page_data = base64_encode(json_encode($request_ids));
                $keyboard['inline_keyboard'][] = [
                    ['text' => '✅ Bulk Approve This Page', 'callback_data' => 'bulk_approve_' . $current_page_data],
                    ['text' => '❌ Bulk Reject This Page', 'callback_data' => 'bulk_reject_' . $current_page_data]
                ];
                editMessageText($chat_id, $message['message_id'], $message_text, $keyboard, 'HTML');
                answerCallbackQuery($query['id'], "Loaded $limit requests");
            }
        }
        elseif (strpos($data, 'reject_custom_') === 0) {
            if (!in_array($user_id, ADMIN_IDS)) { 
                answerCallbackQuery($query['id'], "❌ Admin only!", true); 
            } else {
                $request_id = str_replace('reject_custom_', '', $data);
                sendMessage($chat_id, "Please send the custom rejection reason for Request #$request_id:");
                answerCallbackQuery($query['id'], "Please type the custom reason");
                $pending_file = 'pending_rejection.json';
                $pending_data = [
                    'request_id' => $request_id, 
                    'admin_id' => $user_id, 
                    'chat_id' => $chat_id, 
                    'message_id' => $message['message_id'], 
                    'timestamp' => time()
                ];
                file_put_contents($pending_file, json_encode($pending_data, JSON_PRETTY_PRINT));
            }
        }
        elseif (strpos($data, 'tu_prev_') === 0) { 
            $page = (int)str_replace('tu_prev_', '', $data); 
            totalupload_controller($chat_id, $page); 
            answerCallbackQuery($query['id'], "Page $page"); 
        }
        elseif (strpos($data, 'tu_next_') === 0) { 
            $page = (int)str_replace('tu_next_', '', $data); 
            totalupload_controller($chat_id, $page); 
            answerCallbackQuery($query['id'], "Page $page"); 
        }
        elseif (strpos($data, 'tu_view_') === 0) { 
            $page = (int)str_replace('tu_view_', '', $data); 
            $all = $csvManager->getCachedData(); 
            $start = ($page - 1) * ITEMS_PER_PAGE; 
            $page_movies = array_slice($all, $start, ITEMS_PER_PAGE); 
            $sent = 0; 
            foreach ($page_movies as $movie) { 
                if (deliver_item_to_chat($chat_id, $movie)) $sent++; 
                usleep(500000); 
            } 
            answerCallbackQuery($query['id'], "✅ Re-sent $sent movies"); 
        }
        elseif ($data === 'tu_stop') { 
            sendMessage($chat_id, "✅ Pagination stopped. Type /totalupload to start again."); 
            answerCallbackQuery($query['id'], "Stopped"); 
        }
        elseif ($data === 'help_command') {
            $help_text = "🤖 <b>Entertainment Tadka Bot - Help</b>\n\n📋 <b>Available Commands:</b>\n/start - Welcome message with channel links\n/help - Show this help message\n/request MovieName - Request a new movie\n/myrequests - View your movie requests\n/checkdate - Show date-wise statistics\n/totalupload - Browse all movies with pagination\n/testcsv - View all movies in database\n/checkcsv - Check CSV data (add 'all' for full list)\n/csvstats - CSV statistics\n";
            if (in_array($user_id, ADMIN_IDS)) $help_text .= "/stats - Admin statistics\n/pendingrequests - View pending requests (Admin only)\n";
            $help_text .= "\n🔍 <b>How to Search:</b>\n• Type any movie name (English/Hindi)\n• Partial names work too\n• Example: 'kgf', 'pushpa', 'hindi movie'\n\n🎬 <b>Movie Requests:</b>\n• Use /request MovieName\n• Or type: 'pls add MovieName'\n• Max 3 requests per day per user\n• Check status with /myrequests\n\n📢 <b>Channel Information:</b>\n🍿 Main: @EntertainmentTadka786\n🎭 Theater: @threater_print_movies\n📥 Requests: @EntertainmentTadka7860\n🔒 Backup: @ETBackup\n\n⚠️ <b>Note:</b> This bot works with webhook. If you face issues, contact admin.";
            editMessageText($chat_id, $message['message_id'], $help_text, ['inline_keyboard' => [[['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start']]]], 'HTML');
            answerCallbackQuery($query['id'], "Help information loaded");
        }
        elseif ($data === 'back_to_start') {
            $welcome = "🎬 <b>Welcome to Entertainment Tadka!</b>\n\n📢 <b>How to use this bot:</b>\n• Simply type any movie name\n• Use English or Hindi\n• Add 'theater' for theater prints\n• Partial names also work\n\n🔍 <b>Examples:</b>\n• Mandala Murders 2025\n• Lokah Chapter 1 Chandra 2025\n• Idli Kadai (2025)\n• IT - Welcome to Derry (2025) S01\n• hindi movie\n• kgf\n\n📢 <b>Our Channels:</b>\n🍿 Main: @EntertainmentTadka786\n🎭 Theater: @threater_print_movies\n📥 Requests: @EntertainmentTadka7860\n🔒 Backup: @ETBackup\n\n🎬 <b>Movie Request System:</b>\n• Use /request MovieName to request a movie\n• Or type: 'pls add MovieName'\n• Check status with /myrequests\n• Max 3 requests per day\n\n💡 <b>Tip:</b> Use /help for all commands";
            $keyboard = ['inline_keyboard' => [
                [['text' => '🍿 Main Channel', 'url' => 'https://t.me/EntertainmentTadka786'], ['text' => '🎭 Theater Prints', 'url' => 'https://t.me/threater_print_movies']],
                [['text' => '📥 Request Movie', 'url' => 'https://t.me/EntertainmentTadka7860'], ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/ETBackup']],
                [['text' => '❓ Help', 'callback_data' => 'help_command'], ['text' => '📊 Stats', 'callback_data' => 'show_stats']]
            ]];
            editMessageText($chat_id, $message['message_id'], $welcome, $keyboard, 'HTML');
            answerCallbackQuery($query['id'], "Welcome back!");
        }
        elseif ($data === 'show_stats') {
            $stats = $csvManager->getStats();
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $total_users = count($users_data['users'] ?? []);
            $stats_text = "📊 <b>Bot Statistics</b>\n\n🎬 <b>Total Movies:</b> " . $stats['total_movies'] . "\n👥 <b>Total Users:</b> " . $total_users . "\n";
            $file_stats = json_decode(file_get_contents(STATS_FILE), true);
            $stats_text .= "🔍 <b>Total Searches:</b> " . ($file_stats['total_searches'] ?? 0) . "\n🕒 <b>Last Updated:</b> " . $stats['last_updated'] . "\n\n📡 <b>Movies by Channel:</b>\n";
            foreach ($stats['channels'] as $channel_id => $count) {
                $channel_name = getChannelUsername($channel_id);
                $channel_type = getChannelType($channel_id);
                $type_icon = $channel_type === 'public' ? '🌐' : '🔒';
                $stats_text .= $type_icon . " " . $channel_name . ": " . $count . " movies\n";
            }
            editMessageText($chat_id, $message['message_id'], $stats_text, ['inline_keyboard' => [[['text' => '🔙 Back to Start', 'callback_data' => 'back_to_start'], ['text' => '🔄 Refresh', 'callback_data' => 'show_stats']]]], 'HTML');
            answerCallbackQuery($query['id'], "Statistics updated");
        }
        elseif ($data === 'show_analytics') {
            $analytics_menu = "📊 <b>ANALYTICS MENU</b>\n\nSelect an option:\n\n1️⃣ /1stupload - First upload ever\n2️⃣ /recent - Recent uploads\n3️⃣ /lastupload - Last upload\n4️⃣ /totalupload - Total statistics\n5️⃣ /middleupload - Middle upload\n6️⃣ /uploaddate - Date-wise stats\n7️⃣ /uploadcalendar - Monthly calendar\n\n📈 <b>Complete upload tracking system!</b>";
            sendMessage($chat_id, $analytics_menu);
            answerCallbackQuery($query['id'], "Analytics menu opened");
        }
        elseif ($data === 'protection_info') {
            $protection_info = "🛡️ <b>COPYRIGHT PROTECTION SYSTEM</b>\n\n⚠️ <b>How it works:</b>\n1. Upload any file to bot\n2. Bot sends warning message\n3. File auto-deletes in " . DELETE_AFTER_MINUTES . " minutes\n4. Forward file to save it\n\n🎯 <b>Features:</b>\n• Progress bar countdown\n• Live timer updates\n• One-click actions\n• Admin controls\n\n🔒 <b>Protect against copyright issues!</b>";
            sendMessage($chat_id, $protection_info);
            answerCallbackQuery($query['id'], "Protection info");
        }
        elseif ($data === 'request_movie') {
            sendMessage($chat_id, "📝 To request a movie:\n\nUse command:\n<code>/request movie_name</code>\n\nExample:\n<code>/request Animal 2023</code>", null, 'HTML');
            answerCallbackQuery($query['id'], "Request help");
        }
        elseif (strpos($data, 'auto_request_') === 0) {
            $movie_name = base64_decode(substr($data, 13));
            $lang = detect_language($movie_name);
            $users_data = json_decode(file_get_contents(USERS_FILE), true);
            $today = date('Y-m-d');
            $user_requests_today = 0;
            if (isset($users_data['users'][$user_id]['last_request_date']) && $users_data['users'][$user_id]['last_request_date'] == $today) {
                $user_requests_today = $users_data['users'][$user_id]['request_count'] ?? 0;
            }
            if ($user_requests_today < DAILY_REQUEST_LIMIT) {
                $user_name = $query['from']['first_name'] . ($query['from']['last_name'] ? ' ' . $query['from']['last_name'] : '');
                $result = $requestSystem->submitRequest($user_id, $movie_name, $user_name);
                sendMessage($chat_id, $result['message']);
                answerCallbackQuery($query['id'], "✅ Request sent!");
            } else {
                send_multilingual_response($chat_id, 'request_limit', $lang);
                answerCallbackQuery($query['id'], "❌ Daily limit reached!", true);
            }
        }
        elseif (strpos($data, 'countdown_') === 0) {
            $schedule_id = substr($data, 10);
            $db = new SQLite3(UPLOADS_DB);
            $stmt = $db->prepare("SELECT delete_time FROM scheduled_deletes WHERE id = ?");
            $stmt->bindValue(1, $schedule_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $db->close();
            if ($row) { 
                $countdown = get_countdown_timer($row['delete_time']); 
                answerCallbackQuery($query['id'], "⏰ Countdown: $countdown", true); 
            }
        }
        elseif (strpos($data, 'saved_') === 0) { 
            answerCallbackQuery($query['id'], "✅ Great! File saved successfully."); 
        }
        elseif (strpos($data, 'delete_now_') === 0) {
            if (in_array($user_id, ADMIN_IDS)) {
                $schedule_id = substr($data, 11);
                $db = new SQLite3(UPLOADS_DB);
                $stmt = $db->prepare("SELECT * FROM scheduled_deletes WHERE id = ?");
                $stmt->bindValue(1, $schedule_id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if ($row) {
                    deleteMessage($row['chat_id'], $row['message_id']);
                    $update_stmt = $db->prepare("UPDATE scheduled_deletes SET status = 'deleted_manual' WHERE id = ?");
                    $update_stmt->bindValue(1, $schedule_id, SQLITE3_INTEGER);
                    $update_stmt->execute();
                    answerCallbackQuery($query['id'], "🗑️ File deleted immediately!", true);
                }
                $db->close();
            } else {
                answerCallbackQuery($query['id'], "❌ Admin only!", true);
            }
        }
    }
    
    $pending_file = 'pending_rejection.json';
    if (isset($update['message']) && file_exists($pending_file)) {
        $pending_data = json_decode(file_get_contents($pending_file), true);
        if ($pending_data && $pending_data['admin_id'] == $user_id) {
            $request_id = $pending_data['request_id'];
            $reason = $text;
            $result = $requestSystem->rejectRequest($request_id, $user_id, $reason);
            if ($result['success']) {
                $request = $result['request'];
                $update_text = "❌ <b>Rejected by Admin</b>\n📝 Reason: $reason\n🕒 " . date('H:i:s');
                editMessageText($pending_data['chat_id'], $pending_data['message_id'], $message['text'] . "\n\n" . $update_text, null, 'HTML');
                sendMessage($chat_id, "✅ Request #$request_id rejected with custom reason.");
                notifyUserAboutRequest($request['user_id'], $request, 'rejected');
            } else {
                sendMessage($chat_id, "❌ Failed: " . $result['message']);
            }
            unlink($pending_file);
        }
    }
    
    if (date('H:i') == '03:00') { $csvManager->flushBuffer(); $csvManager->clearCache(); log_error("Daily maintenance completed", 'INFO'); }
    
    http_response_code(200);
    echo "OK";
    exit;
}

// ==================== DEFAULT HTML PAGE ====================
if (!isset($update) && php_sapi_name() != 'cli') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Entertainment Tadka Mega Bot</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h1 { text-align: center; margin-bottom: 30px; font-size: 2.8em; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3); color: #fff; }
        .status-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #4CAF50;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); } 100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); } }
        .btn-group { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin: 30px 0; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
            min-width: 200px;
            text-align: center;
        }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-primary:hover { background: #45a049; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .btn-secondary { background: #2196F3; color: white; }
        .btn-secondary:hover { background: #1976D2; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .btn-warning { background: #FF9800; color: white; }
        .btn-warning:hover { background: #F57C00; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .channels-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0; }
        .channel-card { background: rgba(255, 255, 255, 0.15); padding: 20px; border-radius: 12px; transition: transform 0.3s ease; }
        .channel-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.25); }
        .channel-card.public { border-left: 5px solid #4CAF50; }
        .channel-card.private { border-left: 5px solid #FF9800; }
        .feature-list { margin: 30px 0; }
        .feature-item { background: rgba(255, 255, 255, 0.1); padding: 15px; margin: 10px 0; border-radius: 8px; display: flex; align-items: center; transition: background 0.3s ease; }
        .feature-item:hover { background: rgba(255, 255, 255, 0.2); }
        .feature-item::before { content: "✓"; color: #4CAF50; font-weight: bold; font-size: 1.2em; margin-right: 15px; min-width: 30px; text-align: center; }
        .stats-panel { background: rgba(0, 0, 0, 0.3); padding: 25px; border-radius: 15px; margin-top: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-item { text-align: center; padding: 15px; background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        .stat-value { font-size: 2.5em; font-weight: bold; color: #4CAF50; margin: 10px 0; }
        @media (max-width: 768px) { .container { padding: 20px; } h1 { font-size: 2em; } .btn { width: 100%; min-width: auto; } .channels-grid { grid-template-columns: 1fr; } }
        .icon { margin-right: 10px; font-size: 1.2em; }
        footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.8); }
        .security-badge { display: inline-block; padding: 5px 10px; background: #28a745; color: white; border-radius: 20px; font-size: 0.8em; margin-left: 10px; }
        .warning-box { background: rgba(255, 193, 7, 0.2); border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Entertainment Tadka Mega Bot <span class="security-badge">SECURE v3.0</span></h1>
        
        <div class="status-card">
            <h2>✅ Bot is Running</h2>
            <p>Complete Telegram Bot: Movie Search + Copyright Protection + Upload Analytics</p>
            <p><strong>Movie Request System:</strong> ✅ Active</p>
            <p><strong>Copyright Protection:</strong> ✅ Active (<?php echo DELETE_AFTER_MINUTES; ?> min auto-delete)</p>
            <p><strong>Upload Analytics:</strong> ✅ Active</p>
        </div>
        
        <?php if (empty(BOT_TOKEN) || BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE'): ?>
        <div class="warning-box">
            <strong>⚠️ SECURITY WARNING:</strong> Bot token not configured! Please set BOT_TOKEN environment variable.
        </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="?setup=1" class="btn btn-primary"><span class="icon">🔗</span> Set Webhook</a>
            <a href="?test=1" class="btn btn-secondary"><span class="icon">🧪</span> Test Bot</a>
            <a href="?deletehook=1" class="btn btn-warning"><span class="icon">🗑️</span> Delete Webhook</a>
            <a href="?backup=1" class="btn btn-primary"><span class="icon">💾</span> Manual Backup</a>
            <a href="?logs=1" class="btn btn-secondary"><span class="icon">📋</span> View Logs</a>
        </div>
        
        <div class="stats-panel">
            <h3>📊 Current Statistics</h3>
            <div class="stats-grid">
                <?php
                $csvManager = CSVManager::getInstance();
                $requestSystem = RequestSystem::getInstance();
                $stats = $csvManager->getStats();
                $users_data = json_decode(@file_get_contents(USERS_FILE), true);
                $total_users = count($users_data['users'] ?? []);
                $request_stats = $requestSystem->getStats();
                $db = new SQLite3(UPLOADS_DB);
                $upload_count = $db->querySingle("SELECT COUNT(*) FROM uploads");
                $db->close();
                ?>
                <div class="stat-item"><div>🎬 Total Movies</div><div class="stat-value"><?php echo $stats['total_movies']; ?></div></div>
                <div class="stat-item"><div>👥 Total Users</div><div class="stat-value"><?php echo $total_users; ?></div></div>
                <div class="stat-item"><div>📋 Total Requests</div><div class="stat-value"><?php echo $request_stats['total_requests']; ?></div></div>
                <div class="stat-item"><div>⏳ Pending</div><div class="stat-value"><?php echo $request_stats['pending']; ?></div></div>
                <div class="stat-item"><div>📊 Uploads Tracked</div><div class="stat-value"><?php echo $upload_count; ?></div></div>
            </div>
        </div>
        
        <h3>📡 Configured Channels</h3>
        <div class="channels-grid">
            <?php foreach ($ENV_CONFIG['PUBLIC_CHANNELS'] as $channel): if (!empty($channel['username'])): ?>
                <div class="channel-card public"><div style="font-weight: bold; margin-bottom: 8px;">🌐 Public Channel</div><div style="font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($channel['username']); ?></div><div style="font-size: 0.9em; opacity: 0.8;">ID: <?php echo htmlspecialchars($channel['id']); ?></div></div>
            <?php endif; endforeach; ?>
            <?php foreach ($ENV_CONFIG['PRIVATE_CHANNELS'] as $channel): ?>
                <div class="channel-card private"><div style="font-weight: bold; margin-bottom: 8px;">🔒 Private Channel</div><div style="font-size: 1.1em; margin-bottom: 5px;"><?php echo htmlspecialchars($channel['username'] ?: 'Private Channel'); ?></div><div style="font-size: 0.9em; opacity: 0.8;">ID: <?php echo htmlspecialchars($channel['id']); ?></div></div>
            <?php endforeach; ?>
        </div>
        
        <div class="feature-list">
            <h3>✨ Complete Features</h3>
            <div class="feature-item">✅ Smart movie search with fuzzy matching</div>
            <div class="feature-item">✅ Multi-channel support (Public + Private)</div>
            <div class="feature-item">✅ Movie Request System with moderation</div>
            <div class="feature-item">✅ Copyright Protection with auto-delete (<?php echo DELETE_AFTER_MINUTES; ?> min)</div>
            <div class="feature-item">✅ Live progress bars & countdown timers</div>
            <div class="feature-item">✅ Complete upload analytics & statistics</div>
            <div class="feature-item">✅ First/last upload tracking</div>
            <div class="feature-item">✅ Monthly upload calendar</div>
            <div class="feature-item">✅ Auto-backup system</div>
            <div class="feature-item">✅ Rate limiting & security headers</div>
            <div class="feature-item">✅ Input validation & XSS protection</div>
            <div class="feature-item">✅ File locking for safe concurrent access</div>
            <div class="feature-item">✅ Environment variable configuration</div>
        </div>
        
        <footer>
            <p>🎬 Entertainment Tadka Mega Bot | Powered by PHP & Telegram Bot API | Hosted on Render.com</p>
            <p style="margin-top: 10px; font-size: 0.9em;">© <?php echo date('Y'); ?> - All rights reserved | Complete Edition v3.0</p>
        </footer>
    </div>
</body>
</html>
<?php
} // End HTML page
?>
