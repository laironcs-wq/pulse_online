<?php

session_start();
header('Content-Type: application/json');

// Конфигурация
define('DATA_DIR', __DIR__ . '/data');
define('USERS_DIR', DATA_DIR . '/users');
define('CHATS_DIR', DATA_DIR . '/chats');
define('MESSAGES_DIR', DATA_DIR . '/messages');
define('AVATARS_DIR', DATA_DIR . '/avatars');
define('NOTIFICATIONS_DIR', DATA_DIR . '/notifications');
define('SESSIONS_DIR', DATA_DIR . '/sessions');
define('NEWS_DIR', DATA_DIR . '/news');
define('STORIES_DIR', DATA_DIR . '/stories');
define('STORIES_MEDIA_DIR', STORIES_DIR . '/media');
define('CALLS_DIR', DATA_DIR . '/calls');
define('WS_BACKEND_HOST', '127.0.0.1');
define('WS_BACKEND_PORT', 8091);

// Создаем необходимые директории
createDirectories();

function createDirectories() {
    $dirs = [DATA_DIR, USERS_DIR, CHATS_DIR, MESSAGES_DIR, AVATARS_DIR, NOTIFICATIONS_DIR, SESSIONS_DIR, NEWS_DIR, STORIES_DIR, STORIES_MEDIA_DIR, CALLS_DIR];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Основной обработчик запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'check_username':
            checkUsername();
            break;
        case 'register_login':
            registerLogin();
            break;
        case 'verify_2fa':
            verifyTwoFactorCode();
            break;
        case 'get_current_user':
            getCurrentUser();
            break;
        case 'complete_setup':
            completeSetup();
            break;
        case 'get_chats':
            getChats();
            break;
        case 'create_private_chat':
            createPrivateChat();
            break;
        case 'get_messages':
            getMessages();
            break;
        case 'send_message':
            sendMessage();
            break;
        case 'forward_to_favorites':
            forwardToFavorites();
            break;
        case 'forward_message':
            forwardMessage();
            break;
        case 'edit_message':
            editMessage();
            break;
        case 'delete_message':
            deleteMessage();
            break;
        case 'delete_chat':
            deleteChat();
            break;
        case 'search_users':
            searchUsers();
            break;
        case 'get_user_profile':
            getUserProfile();
            break;
        case 'update_profile':
            updateProfile();
            break;
        case 'set_emoji_status':
            setEmojiStatus();
            break;
        case 'set_message_reaction':
            setMessageReaction();
            break;
        case 'toggle_two_factor':
            toggleTwoFactor();
            break;
        case 'buy_gift':
            buyGift();
            break;
        case 'buy_premium':
            buyPremium();
            break;
        case 'send_gift':
            sendGift();
            break;
        case 'send_gift_direct':
            sendGiftDirect();
            break;
        case 'sell_gift':
            sellGift();
            break;
        case 'create_story':
            createStory();
            break;
        case 'get_stories_feed':
            getStoriesFeed();
            break;
        case 'get_user_stories':
            getUserStories();
            break;
        case 'get_notifications':
            getNotifications();
            break;
        case 'get_calls':
            getCalls();
            break;
        case 'create_call':
            createCall();
            break;
        case 'mark_notification_read':
            markNotificationRead();
            break;
        case 'clear_notifications':
            clearNotifications();
            break;
        case 'create_channel':
            createChannel();
            break;
        case 'create_group':
            createGroup();
            break;
        case 'get_channels':
            getChannels();
            break;
        case 'get_groups':
            getGroups();
            break;
        case 'join_channel':
            joinChannel();
            break;
        case 'join_group':
            joinGroup();
            break;
        case 'search_channels_groups':
            searchChannelsGroups();
            break;
        case 'get_channel_group_by_username':
            getChannelGroupByUsername();
            break;
        case 'leave_channel':
            leaveChannel();
            break;
        case 'leave_group':
            leaveGroup();
            break;
        case 'logout':
            logout();
            break;
        case 'get_channel_settings':
            getChannelSettings();
            break;
        case 'update_channel_settings':
            updateChannelSettings();
            break;
        case 'add_channel_admin':
            addChannelAdmin();
            break;
        case 'remove_channel_admin':
            removeChannelAdmin();
            break;
        case 'get_channel_members':
            getChannelMembers();
            break;
        case 'get_active_sessions':
            getActiveSessions();
            break;
        case 'create_news':
            createNews();
            break;
        case 'get_news':
            getNews();
            break;
        case 'delete_news':
            deleteNews();
            break;
        // Новые функции для блокировки
        case 'block_user':
            blockUser();
            break;
        case 'unblock_user':
            unblockUser();
            break;
        case 'get_blocked_users':
            getBlockedUsers();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
    }
}

// Функции для работы с пользователями
function getUserByUsername($username) {
    $userFile = USERS_DIR . "/{$username}.json";
    if (file_exists($userFile)) {
        return json_decode(file_get_contents($userFile), true);
    }
    return null;
}

function getUserById($userId) {
    $users = scandir(USERS_DIR);
    foreach ($users as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $user = json_decode(file_get_contents(USERS_DIR . "/{$file}"), true);
            if ($user && $user['id'] === $userId) {
                return $user;
            }
        }
    }
    return null;
}

function saveUser($user) {
    $userFile = USERS_DIR . "/{$user['username']}.json";
    file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT));
}

function touchUserPresence($userId, $isOnline = true) {
    $user = getUserById($userId);
    if (!$user) return;
    $user['last_seen'] = time();
    $user['is_online'] = $isOnline;
    saveUser($user);
}

function generateId($prefix = 'user') {
    return $prefix . '_' . uniqid() . '_' . rand(1000, 9999);
}

// Функции для уведомлений
function saveNotification($notification) {
    $userId = $notification['user_id'];
    $notificationDir = NOTIFICATIONS_DIR . "/{$userId}";
    
    if (!file_exists($notificationDir)) {
        mkdir($notificationDir, 0777, true);
    }
    
    $notificationFile = $notificationDir . "/{$notification['id']}.json";
    file_put_contents($notificationFile, json_encode($notification, JSON_PRETTY_PRINT));
}

function getCallsFile($userId) {
    return CALLS_DIR . "/{$userId}.json";
}

function getUserCallsRaw($userId) {
    $file = getCallsFile($userId);
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveUserCallsRaw($userId, $calls) {
    file_put_contents(getCallsFile($userId), json_encode($calls, JSON_PRETTY_PRINT));
}

function appendCallForUser($userId, $callEntry) {
    $calls = getUserCallsRaw($userId);
    $calls[] = $callEntry;
    if (count($calls) > 500) {
        $calls = array_slice($calls, -500);
    }
    saveUserCallsRaw($userId, $calls);
}

function getUserNotifications($userId) {
    $notificationDir = NOTIFICATIONS_DIR . "/{$userId}";
    $notifications = [];
    
    if (!file_exists($notificationDir)) {
        return $notifications;
    }
    
    $files = scandir($notificationDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $notification = json_decode(file_get_contents($notificationDir . "/{$file}"), true);
            if ($notification) {
                $notifications[] = $notification;
            }
        }
    }
    
    // Сортируем по времени (новые сверху)
    usort($notifications, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });
    
    return $notifications;
}

// Функции для сессий
function saveUserSession($userId, $sessionData) {
    $sessionFile = SESSIONS_DIR . "/{$userId}.json";
    $sessions = [];
    
    if (file_exists($sessionFile)) {
        $sessions = json_decode(file_get_contents($sessionFile), true) ?: [];
    }
    
    // Проверяем, существует ли уже такая сессия
    $sessionExists = false;
    foreach ($sessions as &$session) {
        if ($session['session_id'] === $sessionData['session_id']) {
            // Обновляем существующую сессию
            $session = $sessionData;
            $sessionExists = true;
            break;
        }
    }
    
    if (!$sessionExists) {
        // Добавляем новую сессию
        $sessions[] = $sessionData;
    }
    
    // Ограничиваем количество хранимых сессий (последние 10)
    $sessions = array_slice($sessions, -10);
    
    file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));
}

function getUserSessions($userId) {
    $sessionFile = SESSIONS_DIR . "/{$userId}.json";
    
    if (file_exists($sessionFile)) {
        return json_decode(file_get_contents($sessionFile), true) ?: [];
    }
    
    return [];
}

function getCurrentSessionInfo() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $deviceType = 'Unknown';
    
    // Определяем тип устройства
    if (strpos($userAgent, 'Mobile') !== false) {
        $deviceType = 'Mobile';
    } else if (strpos($userAgent, 'Tablet') !== false) {
        $deviceType = 'Tablet';
    } else {
        $deviceType = 'Desktop';
    }
    
    // Определяем браузер
    $browser = 'Unknown';
    if (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } else if (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } else if (strpos($userAgent, 'Safari') !== false) {
        $browser = 'Safari';
    } else if (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Edge';
    }
    
    return [
        'session_id' => session_id(),
        'ip' => $ip,
        'user_agent' => $userAgent,
        'device_type' => $deviceType,
        'browser' => $browser,
        'login_time' => time(),
        'last_activity' => time()
    ];
}

function getStoriesFilePath($userId) {
    return STORIES_DIR . "/{$userId}.json";
}

function getUserStoriesList($userId) {
    $file = getStoriesFilePath($userId);
    if (!file_exists($file)) {
        return [];
    }
    $stories = json_decode(file_get_contents($file), true);
    return is_array($stories) ? $stories : [];
}

function saveUserStoriesList($userId, $stories) {
    file_put_contents(getStoriesFilePath($userId), json_encode($stories, JSON_PRETTY_PRINT));
}

function getActiveStories($stories, $now = null) {
    $now = $now ?? time();
    $ttl = 24 * 60 * 60;
    return array_values(array_filter($stories, function($story) use ($now, $ttl) {
        return ($story['created_at'] ?? 0) + $ttl > $now;
    }));
}

function getPrivateChatPartnerIds($userId) {
    $ids = [];
    $chatFiles = scandir(CHATS_DIR);
    foreach ($chatFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'json' || $file === 'channels.json' || $file === 'groups.json') {
            continue;
        }
        if (strpos($file, 'security_bot_') === 0) {
            continue;
        }
        $chat = json_decode(file_get_contents(CHATS_DIR . "/{$file}"), true);
        if (!$chat) continue;
        if (($chat['type'] ?? 'private') !== 'private') continue;
        $participants = $chat['participants'] ?? [];
        if (count($participants) !== 2 || !in_array($userId, $participants, true)) continue;
        $other = $participants[0] === $userId ? $participants[1] : $participants[0];
        if ($other && str_starts_with($other, 'user_')) {
            $ids[$other] = true;
        }
    }
    return array_keys($ids);
}

// Функции для бота безопасности
function createSecurityBotMessage($userId, $message, $type = 'security') {
    $user = getUserById($userId);
    if (!$user) return;
    
    // Создаем чат с ботом безопасности, если его нет
    $botChatId = 'security_bot_' . $userId;
    $botChatFile = CHATS_DIR . "/{$botChatId}.json";
    
    if (!file_exists($botChatFile)) {
        $botChat = [
            'id' => $botChatId,
            'participants' => [$userId, 'security_bot'],
            'created_at' => time(),
            'type' => 'private',
            'is_bot_chat' => true
        ];
        file_put_contents($botChatFile, json_encode($botChat, JSON_PRETTY_PRINT));
        
        // Добавляем чат с ботом пользователю
        if (!isset($user['channels'])) {
            $user['channels'] = [];
        }
        if (!in_array($botChatId, $user['channels'])) {
            $user['channels'][] = $botChatId;
        }
        saveUser($user);
    }
    
    // Создаем сообщение от бота
    $messageId = generateId('msg');
    $botMessage = [
        'id' => $messageId,
        'chat_id' => $botChatId,
        'sender_id' => 'security_bot',
        'sender_name' => '🤖 Бот безопасности',
        'sender_avatar' => '/data/avatars/security_bot.png',
        'content' => $message,
        'timestamp' => time(),
        'type' => $type
    ];
    
    // Сохраняем сообщение
    $messageDir = MESSAGES_DIR . "/{$botChatId}";
    if (!file_exists($messageDir)) {
        mkdir($messageDir, 0777, true);
    }
    file_put_contents($messageDir . "/{$messageId}.json", json_encode($botMessage, JSON_PRETTY_PRINT));
    
    // Создаем уведомление
    createNotification($userId, 'Бот безопасности', $message, 'security');
    
    return $botMessage;
}

// Функции для каналов и групп
function getChannelsList() {
    $channelsFile = CHATS_DIR . "/channels.json";
    if (file_exists($channelsFile)) {
        return json_decode(file_get_contents($channelsFile), true) ?: [];
    }
    return [];
}

function getGroupsList() {
    $groupsFile = CHATS_DIR . "/groups.json";
    if (file_exists($groupsFile)) {
        return json_decode(file_get_contents($groupsFile), true) ?: [];
    }
    return [];
}

function saveChannelsList($channels) {
    $channelsFile = CHATS_DIR . "/channels.json";
    file_put_contents($channelsFile, json_encode($channels, JSON_PRETTY_PRINT));
}

function saveGroupsList($groups) {
    $groupsFile = CHATS_DIR . "/groups.json";
    file_put_contents($groupsFile, json_encode($groups, JSON_PRETTY_PRINT));
}

function generateUsername($name, $type) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    $username = $base;
    $counter = 1;
    
    if ($type === 'channel') {
        $existing = getChannelsList();
        while (in_array($username, array_column($existing, 'username'))) {
            $username = $base . $counter;
            $counter++;
        }
    } else {
        $existing = getGroupsList();
        while (in_array($username, array_column($existing, 'username'))) {
            $username = $base . $counter;
            $counter++;
        }
    }
    
    return $username;
}

// Функции для новостей
function createNews() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    if (!$user || !$user['is_moderator']) {
        echo json_encode(['success' => false, 'error' => 'Только модераторы могут создавать новости']);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Заголовок и содержание новости не могут быть пустыми']);
        return;
    }
    
    $newsId = generateId('news');
    $news = [
        'id' => $newsId,
        'title' => $title,
        'content' => $content,
        'author_id' => $userId,
        'author_name' => $user['name'],
        'author_avatar' => $user['avatar'],
        'created_at' => time(),
        'views' => 0
    ];
    
    // Сохраняем новость
    $newsFile = NEWS_DIR . "/{$newsId}.json";
    file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT));
    
    // Создаем уведомление для всех пользователей о новой новости
    $allUsers = scandir(USERS_DIR);
    foreach ($allUsers as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $userData = json_decode(file_get_contents(USERS_DIR . "/{$file}"), true);
            if ($userData && $userData['id'] !== $userId) {
                createNotification($userData['id'], '📰 Новая новость', $title, 'news');
            }
        }
    }
    
    echo json_encode(['success' => true, 'news_id' => $newsId]);
}

function getNews() {
    $news = [];
    $newsFiles = scandir(NEWS_DIR);
    
    foreach ($newsFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $newsItem = json_decode(file_get_contents(NEWS_DIR . "/{$file}"), true);
            if ($newsItem) {
                $news[] = $newsItem;
            }
        }
    }
    
    // Сортируем по времени (новые сверху)
    usort($news, function($a, $b) {
        return $b['created_at'] <=> $a['created_at'];
    });
    
    echo json_encode(['success' => true, 'news' => $news]);
}

function deleteNews() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    if (!$user || !$user['is_moderator']) {
        echo json_encode(['success' => false, 'error' => 'Только модераторы могут удалять новости']);
        return;
    }
    
    $newsId = $_POST['news_id'] ?? '';
    
    if (empty($newsId)) {
        echo json_encode(['success' => false, 'error' => 'Не указана новость']);
        return;
    }
    
    $newsFile = NEWS_DIR . "/{$newsId}.json";
    
    if (file_exists($newsFile)) {
        unlink($newsFile);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Новость не найдена']);
    }
}

// ФУНКЦИИ ДЛЯ БЛОКИРОВКИ ПОЛЬЗОВАТЕЛЕЙ

// Проверка заблокирован ли пользователь
function isUserBlocked($userId) {
    $user = getUserById($userId);
    return $user && isset($user['blocked']) && !empty($user['blocked']);
}

// Блокировка пользователя
function blockUser() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $targetUserId = $_POST['target_user_id'] ?? '';
    $reason = trim($_POST['reason'] ?? 'Нарушение правил');
    
    if (empty($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан пользователь']);
        return;
    }
    
    $currentUser = getUserById($userId);
    if (!$currentUser || !$currentUser['is_moderator']) {
        echo json_encode(['success' => false, 'error' => 'Только модераторы могут блокировать пользователей']);
        return;
    }
    
    $targetUser = getUserById($targetUserId);
    if (!$targetUser) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    
    // Добавляем блокировку
    if (!isset($targetUser['blocked'])) {
        $targetUser['blocked'] = [];
    }
    
    $blockId = generateId('block');
    $targetUser['blocked'][$blockId] = [
        'blocked_by' => $userId,
        'reason' => $reason,
        'timestamp' => time(),
        'blocker_name' => $currentUser['name']
    ];
    
    saveUser($targetUser);
    
    // Создаем уведомление для заблокированного пользователя
    createNotification($targetUserId, '🔴 Аккаунт заблокирован', 
        "Ваш аккаунт заблокирован модератором. Причина: {$reason}. Обратитесь в поддержку для разблокировки.", 
        'block'
    );
    
    // Сообщение от бота безопасности
    createSecurityBotMessage($targetUserId,
        "🔴 ВАШ АККАУНТ ЗАБЛОКИРОВАН\n\n" .
        "Причина: {$reason}\n" .
        "Модератор: {$currentUser['name']}\n" .
        "Время: " . date('d.m.Y H:i:s') . "\n\n" .
        "Вы больше не можете отправлять сообщения и совершать другие действия.\n" .
        "Для разблокировки обратитесь к администрации."
    );
    
    echo json_encode(['success' => true]);
}

// Разблокировка пользователя
function unblockUser() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $targetUserId = $_POST['target_user_id'] ?? '';
    
    if (empty($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан пользователь']);
        return;
    }
    
    $currentUser = getUserById($userId);
    if (!$currentUser || !$currentUser['is_moderator']) {
        echo json_encode(['success' => false, 'error' => 'Только модераторы могут разблокировать пользователей']);
        return;
    }
    
    $targetUser = getUserById($targetUserId);
    if (!$targetUser) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    
    // Снимаем блокировку
    if (isset($targetUser['blocked'])) {
        unset($targetUser['blocked']);
        saveUser($targetUser);
        
        // Создаем уведомление о разблокировке
        createNotification($targetUserId, '🟢 Аккаунт разблокирован', 
            "Ваш аккаунт разблокирован модератором. Теперь вы можете снова использовать все функции.", 
            'unblock'
        );
        
        // Сообщение от бота безопасности
        createSecurityBotMessage($targetUserId,
            "🟢 ВАШ АККАУНТ РАЗБЛОКИРОВАН\n\n" .
            "Модератор: {$currentUser['name']}\n" .
            "Время: " . date('d.m.Y H:i:s') . "\n\n" .
            "Теперь вы можете снова использовать все функции приложения."
        );
    }
    
    echo json_encode(['success' => true]);
}

// Получение списка заблокированных пользователей
function getBlockedUsers() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $currentUser = getUserById($userId);
    
    if (!$currentUser || !$currentUser['is_moderator']) {
        echo json_encode(['success' => false, 'error' => 'Только для модераторов']);
        return;
    }
    
    $blockedUsers = [];
    $userFiles = scandir(USERS_DIR);
    
    foreach ($userFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $user = json_decode(file_get_contents(USERS_DIR . "/{$file}"), true);
            if ($user && isset($user['blocked']) && !empty($user['blocked'])) {
                $lastBlock = end($user['blocked']);
                $blockedUsers[] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'avatar' => $user['avatar'],
                    'block_reason' => $lastBlock['reason'],
                    'blocked_by' => $lastBlock['blocker_name'],
                    'blocked_at' => $lastBlock['timestamp'],
                    'blocked_at_formatted' => date('d.m.Y H:i', $lastBlock['timestamp'])
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'blocked_users' => $blockedUsers]);
}

// Основные функции API
function checkUsername() {
    $username = strtolower(trim($_POST['username'] ?? ''));
    
    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'error' => 'Логин должен содержать минимум 3 символа']);
        return;
    }
    
    $user = getUserByUsername($username);
    
    echo json_encode([
        'success' => true,
        'exists' => $user !== null
    ]);
}

function registerLogin() {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $skipTwoFactorForCached = ($_POST['skip_2fa_for_cached'] ?? '') === '1';
    
    if (strlen($username) < 3 || strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Неверные данные']);
        return;
    }
    
    $user = getUserByUsername($username);
    
    if ($user) {
        // Проверка блокировки
        if (isUserBlocked($user['id'])) {
            $lastBlock = end($user['blocked']);
            $reason = $lastBlock['reason'] ?? 'Нарушение правил';
            $blocker = $lastBlock['blocker_name'] ?? 'Модератор';
            $time = date('d.m.Y H:i', $lastBlock['timestamp']);
            
            echo json_encode([
                'success' => false, 
                'error' => "Аккаунт заблокирован. Причина: {$reason}. Заблокировал: {$blocker}. Время: {$time}"
            ]);
            return;
        }
        
        // Вход
        if (password_verify($password, $user['password_hash'])) {
            if (!empty($user['two_factor_enabled']) && !$skipTwoFactorForCached) {
                unset($_SESSION['user_id']);
                $code = (string) random_int(100000, 999999);
                $_SESSION['pending_2fa_user_id'] = $user['id'];
                $_SESSION['pending_2fa_code'] = $code;
                $_SESSION['pending_2fa_expires_at'] = time() + 300;
                $_SESSION['pending_2fa_attempts'] = 0;

                createSecurityBotMessage(
                    $user['id'],
                    "🔐 Код подтверждения входа: {$code}\n\nКод действует 5 минут. Никому его не сообщайте.",
                    'security'
                );

                echo json_encode([
                    'success' => true,
                    'status' => '2fa_required',
                    'two_factor_required' => true,
                    'name' => $user['name'] ?? $user['username']
                ]);
                return;
            }

            completeUserLogin($user);
            echo json_encode([
                'success' => true,
                'status' => 'login',
                'name' => $user['name'] ?? $user['username']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверный пароль']);
        }
    } else {
        // Регистрация
        $userId = generateId('user');
        $newUser = [
            'id' => $userId,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => '',
            'avatar' => '/data/avatars/default.png',
            'stars' => 0,
            'is_premium' => false,
            'gifts' => [],
            'bio' => '',
            'is_moderator' => false,
            'created_at' => time(),
            'last_seen' => time(),
            'is_online' => true,
            'emoji_status' => '',
            'setup_completed' => false,
            'two_factor_enabled' => false,
            'channels' => [],
            'groups' => []
        ];
        
        saveUser($newUser);
        $_SESSION['user_id'] = $userId;
        
        // Сохраняем информацию о сессии
        $currentSessionInfo = getCurrentSessionInfo();
        saveUserSession($userId, $currentSessionInfo);
        
        echo json_encode([
            'success' => true,
            'status' => 'register_new'
        ]);
    }
}

function completeUserLogin($user) {
    $_SESSION['user_id'] = $user['id'];

    $previousSessions = getUserSessions($user['id']);
    $currentSessionInfo = getCurrentSessionInfo();

    $isNewSession = true;
    foreach ($previousSessions as $session) {
        if (($session['session_id'] ?? '') === $currentSessionInfo['session_id']) {
            $isNewSession = false;
            break;
        }
    }

    saveUserSession($user['id'], $currentSessionInfo);

    if (count($previousSessions) === 0) {
        createSecurityBotMessage($user['id'],
            "👋 Добро пожаловать в Pulse! Это ваш первый вход в аккаунт. Бот безопасности будет уведомлять вас о подозрительной активности."
        );
    } else if ($isNewSession) {
        $deviceInfo = "{$currentSessionInfo['device_type']} • {$currentSessionInfo['browser']}";
        $ipInfo = $currentSessionInfo['ip'];
        createSecurityBotMessage($user['id'],
            "🔐 Выполнен вход в аккаунт с нового устройства:\n" .
            "• Устройство: {$deviceInfo}\n" .
            "• IP-адрес: {$ipInfo}\n" .
            "• Время: " . date('d.m.Y H:i:s') . "\n\n" .
            "Если это были не вы, немедленно смените пароль!"
        );
    }
}

function verifyTwoFactorCode() {
    $code = trim($_POST['code'] ?? '');
    $pendingUserId = $_SESSION['pending_2fa_user_id'] ?? '';
    $pendingCode = $_SESSION['pending_2fa_code'] ?? '';
    $expiresAt = (int) ($_SESSION['pending_2fa_expires_at'] ?? 0);
    $attempts = (int) ($_SESSION['pending_2fa_attempts'] ?? 0);

    if ($pendingUserId === '' || $pendingCode === '') {
        echo json_encode(['success' => false, 'error' => 'Сессия подтверждения не найдена. Войдите заново.']);
        return;
    }
    if (time() > $expiresAt) {
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires_at'], $_SESSION['pending_2fa_attempts']);
        echo json_encode(['success' => false, 'error' => 'Код устарел. Выполните вход снова.']);
        return;
    }
    if (!preg_match('/^\d{6}$/', $code)) {
        echo json_encode(['success' => false, 'error' => 'Код должен состоять из 6 цифр.']);
        return;
    }
    if ($attempts >= 5) {
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires_at'], $_SESSION['pending_2fa_attempts']);
        echo json_encode(['success' => false, 'error' => 'Превышено число попыток. Войдите заново.']);
        return;
    }

    if (!hash_equals((string)$pendingCode, $code)) {
        $_SESSION['pending_2fa_attempts'] = $attempts + 1;
        echo json_encode(['success' => false, 'error' => 'Неверный код подтверждения.']);
        return;
    }

    $user = getUserById($pendingUserId);
    if (!$user) {
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires_at'], $_SESSION['pending_2fa_attempts']);
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден.']);
        return;
    }

    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code'], $_SESSION['pending_2fa_expires_at'], $_SESSION['pending_2fa_attempts']);
    completeUserLogin($user);

    echo json_encode([
        'success' => true,
        'status' => 'login',
        'name' => $user['name'] ?? $user['username']
    ]);
}

function toggleTwoFactor() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    $enabledRaw = $_POST['enabled'] ?? null;
    $enabled = $enabledRaw === 'true' || $enabledRaw === '1' || $enabledRaw === 1 || $enabledRaw === true;

    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }

    $user['two_factor_enabled'] = $enabled;
    saveUser($user);

    createSecurityBotMessage(
        $user['id'],
        $enabled
            ? "🛡️ Двухфакторная защита включена. Теперь при входе потребуется код от бота безопасности."
            : "⚪ Двухфакторная защита отключена.",
        'security'
    );

    echo json_encode(['success' => true, 'enabled' => $enabled]);
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => false]);
        return;
    }
    
    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        session_destroy();
        echo json_encode(['logged_in' => false]);
        return;
    }
    
    touchUserPresence($user['id'], true);
    $user = getUserById($_SESSION['user_id']) ?: $user;

    // Обновляем время последней активности в текущей сессии
    $currentSessionInfo = getCurrentSessionInfo();
    saveUserSession($user['id'], $currentSessionInfo);
    
    // Убираем пароль из ответа
    unset($user['password_hash']);
    
    echo json_encode([
        'logged_in' => true,
        'setup_needed' => !$user['setup_completed'],
        'user' => $user
    ]);
}

function getActiveSessions() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $sessions = getUserSessions($userId);
    
    // Фильтруем только активные сессии (последние 24 часа)
    $activeSessions = [];
    $currentTime = time();
    $currentSessionId = session_id();
    
    // Используем массив для отслеживания уникальных сессий
    $uniqueSessions = [];
    
    foreach ($sessions as $session) {
        // Сессия считается активной, если была активна в последние 24 часа
        if ($currentTime - $session['last_activity'] < 86400) {
            $sessionKey = $session['session_id'] . '_' . $session['ip'] . '_' . $session['user_agent'];
            
            // Проверяем, не была ли уже добавлена эта сессия
            if (!isset($uniqueSessions[$sessionKey])) {
                $session['is_current'] = ($session['session_id'] === $currentSessionId);
                $session['last_activity_formatted'] = date('d.m.Y H:i', $session['last_activity']);
                $session['login_time_formatted'] = date('d.m.Y H:i', $session['login_time']);
                $activeSessions[] = $session;
                $uniqueSessions[$sessionKey] = true;
            }
        }
    }
    
    // Сортируем по времени последней активности (новые сверху)
    usort($activeSessions, function($a, $b) {
        return $b['last_activity'] <=> $a['last_activity'];
    });
    
    echo json_encode([
        'success' => true,
        'sessions' => $activeSessions
    ]);
}

function completeSetup() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Имя не может быть пустым']);
        return;
    }
    
    $user['name'] = $name;
    $user['setup_completed'] = true;
    
    // Обработка аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarFile = AVATARS_DIR . "/{$user['id']}.png";
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarFile);
        $user['avatar'] = "/data/avatars/{$user['id']}.png";
    }
    
    saveUser($user);
    
    // Отправляем сообщение от бота после завершения настройки
    createSecurityBotMessage($user['id'],
        "✅ Настройка профиля завершена!\n" .
        "Теперь вы можете полноценно использовать Pulse.\n\n" .
        "Ваши данные:\n" .
        "• Имя: {$name}\n" .
        "• Логин: @{$user['username']}\n\n" .
        "Бот безопасности будет уведомлять вас о важных событиях."
    );
    
    echo json_encode([
        'success' => true,
        'name' => $user['name'],
        'new_avatar' => $user['avatar'] ?? null
    ]);
}

function getChats() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    $chats = [];
    
    // Избранное (персональный чат пользователя)
    $favoritesChatId = 'favorites_' . $userId;
    $favoritesLastMessage = getLastMessage($favoritesChatId);
    $chats[] = [
        'id' => $favoritesChatId,
        'name' => 'Избранное',
        'type' => 'favorites',
        'avatar' => '/data/avatars/favorites.png',
        'last_message' => $favoritesLastMessage['content'] ?? 'Сохраняйте здесь важные сообщения и заметки',
        'last_timestamp' => $favoritesLastMessage['timestamp'] ?? time()
    ];
    
    // Чат с ботом безопасности (всегда первый в списке)
    $botChatId = 'security_bot_' . $userId;
    $botChatFile = CHATS_DIR . "/{$botChatId}.json";
    if (file_exists($botChatFile)) {
        $lastMessage = getLastMessage($botChatId);
        $chats[] = [
            'id' => $botChatId,
            'name' => '🤖 Бот безопасности',
            'type' => 'bot',
            'avatar' => '/data/avatars/security_bot.png',
            'last_message' => $lastMessage['content'] ?? 'Бот безопасности будет уведомлять вас о важных событиях',
            'last_timestamp' => $lastMessage['timestamp'] ?? time(),
            'is_bot_chat' => true
        ];
    }
    
    // Каналы пользователя
    if (isset($user['channels']) && is_array($user['channels'])) {
        $allChannels = getChannelsList();
        foreach ($user['channels'] as $channelId) {
            // Пропускаем чат с ботом, так как он уже добавлен
            if ($channelId === $botChatId) continue;
            
            foreach ($allChannels as $channel) {
                if ($channel['id'] === $channelId) {
                    $lastMessage = getLastMessage($channelId);
                    $chats[] = [
                        'id' => $channelId,
                        'name' => $channel['name'],
                        'type' => 'channel',
                        'avatar' => $channel['avatar'] ?? '/data/avatars/channel.png',
                        'last_message' => $lastMessage['content'] ?? 'Нет сообщений',
                        'last_timestamp' => $lastMessage['timestamp'] ?? $channel['created_at'],
                        'member_count' => count($channel['members'] ?? []),
                        'username' => $channel['username'] ?? '',
                        'is_admin' => in_array($userId, $channel['admins'] ?? [])
                    ];
                    break;
                }
            }
        }
    }
    
    // Группы пользователя
    if (isset($user['groups']) && is_array($user['groups'])) {
        $allGroups = getGroupsList();
        foreach ($user['groups'] as $groupId) {
            foreach ($allGroups as $group) {
                if ($group['id'] === $groupId) {
                    $lastMessage = getLastMessage($groupId);
                    $chats[] = [
                        'id' => $groupId,
                        'name' => $group['name'],
                        'type' => 'group',
                        'avatar' => $group['avatar'] ?? '/data/avatars/group.png',
                        'last_message' => $lastMessage['content'] ?? 'Нет сообщений',
                        'last_timestamp' => $lastMessage['timestamp'] ?? $group['created_at'],
                        'member_count' => count($group['members'] ?? []),
                        'username' => $group['username'] ?? ''
                    ];
                    break;
                }
            }
        }
    }
    
    // Приватные чаты
    $chatFiles = scandir(CHATS_DIR);
    foreach ($chatFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json' && $file !== 'channels.json' && $file !== 'groups.json') {
            // Пропускаем чат с ботом, так как он уже добавлен
            if (strpos($file, 'security_bot_') === 0) continue;
            
            $chat = json_decode(file_get_contents(CHATS_DIR . "/{$file}"), true);
            if ($chat && in_array($userId, $chat['participants'])) {
                // Получаем последнее сообщение
                $lastMessage = getLastMessage($chat['id']);
                $otherUserId = $chat['participants'][0] === $userId ? $chat['participants'][1] : $chat['participants'][0];
                $otherUser = getUserById($otherUserId);
                
                $chats[] = [
                    'id' => $chat['id'],
                    'name' => $otherUser['name'] ?? 'Неизвестный',
                    'type' => 'private',
                    'avatar' => $otherUser['avatar'] ?? '/avatars/default.png',
                    'last_message' => $lastMessage['content'] ?? 'Нет сообщений',
                    'last_timestamp' => $lastMessage['timestamp'] ?? $chat['created_at'],
                    'other_id' => $otherUserId,
                    'other_avatar' => $otherUser['avatar'] ?? '/avatars/default.png',
                    'emoji_status' => $otherUser['emoji_status'] ?? '',
                    'is_online' => (bool)($otherUser['is_online'] ?? false),
                    'last_seen' => (int)($otherUser['last_seen'] ?? 0)
                ];
            }
        }
    }
    
    usort($chats, function($a, $b) {
        return ((int)($b['last_timestamp'] ?? 0)) <=> ((int)($a['last_timestamp'] ?? 0));
    });
    echo json_encode(['success' => true, 'chats' => $chats]);
}

function createPrivateChat() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $currentUserId = $_SESSION['user_id'];
    $targetUserId = $_POST['target_user_id'] ?? '';
    
    if (empty($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан пользователь']);
        return;
    }
    
    // Если передан username вместо ID
    if (!str_starts_with($targetUserId, 'user_')) {
        $targetUser = getUserByUsername($targetUserId);
        if (!$targetUser) {
            echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
            return;
        }
        $targetUserId = $targetUser['id'];
    }
    
    // Проверяем, существует ли уже чат
    $chatFiles = scandir(CHATS_DIR);
    foreach ($chatFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json' && $file !== 'channels.json' && $file !== 'groups.json') {
            $chat = json_decode(file_get_contents(CHATS_DIR . "/{$file}"), true);
            if ($chat && in_array($currentUserId, $chat['participants']) && in_array($targetUserId, $chat['participants'])) {
                echo json_encode(['success' => true, 'chat_id' => $chat['id']]);
                return;
            }
        }
    }
    
    // Создаем новый чат
    $chatId = generateId('chat');
    $chat = [
        'id' => $chatId,
        'participants' => [$currentUserId, $targetUserId],
        'created_at' => time(),
        'type' => 'private'
    ];
    
    file_put_contents(CHATS_DIR . "/{$chatId}.json", json_encode($chat, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'chat_id' => $chatId]);
}

function getOrCreatePrivateChatId($firstUserId, $secondUserId) {
    if (empty($firstUserId) || empty($secondUserId) || $firstUserId === $secondUserId) {
        return null;
    }

    $chatFiles = scandir(CHATS_DIR);
    foreach ($chatFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'json' || $file === 'channels.json' || $file === 'groups.json') {
            continue;
        }
        $chat = json_decode(file_get_contents(CHATS_DIR . "/{$file}"), true);
        if (!$chat) {
            continue;
        }
        if (($chat['type'] ?? 'private') !== 'private') {
            continue;
        }
        $participants = $chat['participants'] ?? [];
        if (count($participants) !== 2) {
            continue;
        }
        if (in_array($firstUserId, $participants, true) && in_array($secondUserId, $participants, true)) {
            return $chat['id'];
        }
    }

    $chatId = generateId('chat');
    $chat = [
        'id' => $chatId,
        'participants' => [$firstUserId, $secondUserId],
        'created_at' => time(),
        'type' => 'private'
    ];
    file_put_contents(CHATS_DIR . "/{$chatId}.json", json_encode($chat, JSON_PRETTY_PRINT));
    return $chatId;
}

function getMessages() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $chatId = $_POST['chat_id'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (empty($chatId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан чат']);
        return;
    }
    
    // Для избранного
    if (str_starts_with($chatId, 'favorites_')) {
        if ($chatId !== 'favorites_' . $userId) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к избранному']);
            return;
        }
        $messages = getFavoritesMessages($userId);
        echo json_encode(['success' => true, 'messages' => $messages]);
        return;
    }
    
    // Для каналов
    if (str_starts_with($chatId, 'channel_')) {
        $channels = getChannelsList();
        $channelExists = false;
        foreach ($channels as $channel) {
            if ($channel['id'] === $chatId && in_array($userId, $channel['members'])) {
                $channelExists = true;
                break;
            }
        }
        
        if (!$channelExists) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к каналу']);
            return;
        }
        
        $messages = getChannelMessages($chatId);
        echo json_encode(['success' => true, 'messages' => $messages]);
        return;
    }
    
    // Для групп
    if (str_starts_with($chatId, 'group_')) {
        $groups = getGroupsList();
        $groupExists = false;
        foreach ($groups as $group) {
            if ($group['id'] === $chatId && in_array($userId, $group['members'])) {
                $groupExists = true;
                break;
            }
        }
        
        if (!$groupExists) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к группе']);
            return;
        }
        
        $messages = getGroupMessages($chatId);
        echo json_encode(['success' => true, 'messages' => $messages]);
        return;
    }
    
    // Для приватного чата
    $chatFile = CHATS_DIR . "/{$chatId}.json";
    if (!file_exists($chatFile)) {
        echo json_encode(['success' => false, 'error' => 'Чат не найден']);
        return;
    }
    
    $chat = json_decode(file_get_contents($chatFile), true);
    if (!in_array($userId, $chat['participants'])) {
        echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
        return;
    }
    
    $messagesDir = MESSAGES_DIR . "/{$chatId}";
    $messages = [];
    
    if (file_exists($messagesDir)) {
        $messageFiles = scandir($messagesDir);
        foreach ($messageFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $message = json_decode(file_get_contents($messagesDir . "/{$file}"), true);
                if ($message) {
                    $messages[] = $message;
                }
            }
        }
    }
    
    // Сортируем по времени
    usort($messages, function($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });

    // В личных чатах отмечаем входящие сообщения как прочитанные
    if (($chat['type'] ?? 'private') === 'private') {
        $otherUserId = $chat['participants'][0] === $userId ? $chat['participants'][1] : $chat['participants'][0];
        $updatedIds = [];
        foreach ($messages as &$msg) {
            if (($msg['sender_id'] ?? '') === $userId) {
                continue;
            }
            $readBy = $msg['read_by'] ?? [];
            if (!in_array($userId, $readBy, true)) {
                $readBy[] = $userId;
                $msg['read_by'] = array_values(array_unique($readBy));
                $msgId = $msg['id'] ?? '';
                if ($msgId !== '') {
                    $messageFile = MESSAGES_DIR . "/{$chatId}/{$msgId}.json";
                    if (file_exists($messageFile)) {
                        file_put_contents($messageFile, json_encode($msg, JSON_PRETTY_PRINT));
                    }
                    $updatedIds[] = $msgId;
                }
            }
        }
        unset($msg);
        if (!empty($updatedIds)) {
            publishWebSocketEvent([
                'type' => 'message_read',
                'chat_id' => $chatId,
                'reader_id' => $userId,
                'message_ids' => $updatedIds,
                'recipient_ids' => [$otherUserId],
                'timestamp' => time()
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $chatId = $_POST['chat_id'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $replyTo = trim($_POST['reply_to'] ?? '');
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    if (empty($chatId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан чат']);
        return;
    }
    
    // Проверка блокировки пользователя
    if (isUserBlocked($userId)) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете отправлять сообщения.']);
        return;
    }
    
    if (empty($content) && empty($_FILES['photo']) && empty($_FILES['audio']) && empty($_FILES['video_note'])) {
        echo json_encode(['success' => false, 'error' => 'Сообщение не может быть пустым']);
        return;
    }
    
    // Проверка прав для каналов
    if (str_starts_with($chatId, 'channel_')) {
        $channels = getChannelsList();
        $canWrite = false;
        
        foreach ($channels as $channel) {
            if ($channel['id'] === $chatId) {
                // Проверяем, является ли пользователь администратором
                if (in_array($userId, $channel['admins'] ?? [])) {
                    $canWrite = true;
                } else {
                    // Проверяем настройки канала
                    if ($channel['settings']['allow_all_write'] ?? false) {
                        $canWrite = true;
                    }
                }
                break;
            }
        }
        
        if (!$canWrite) {
            echo json_encode(['success' => false, 'error' => 'Только администраторы могут писать в этот канал']);
            return;
        }
    }
    
    $messageId = generateId('msg');
    $timestamp = time();
    
    $message = [
        'id' => $messageId,
        'chat_id' => $chatId,
        'sender_id' => $userId,
        'sender_name' => $user['name'],
        'sender_avatar' => $user['avatar'],
        'content' => $content,
        'timestamp' => $timestamp,
        'type' => 'text',
        'read_by' => [$userId]
    ];

    if ($replyTo !== '') {
        $replyMessage = findMessageByIdInChat($chatId, $replyTo);
        if ($replyMessage) {
            $message['reply_to'] = $replyTo;
            $message['reply_preview'] = [
                'sender_name' => $replyMessage['sender_name'] ?? 'Пользователь',
                'content' => mb_substr($replyMessage['content'] ?? '', 0, 120)
            ];
        }
    }
    
    // Обработка фото
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoDir = MESSAGES_DIR . "/photos";
        if (!file_exists($photoDir)) {
            mkdir($photoDir, 0777, true);
        }
        
        $photoFile = $photoDir . "/{$messageId}.png";
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoFile);
        
        $message['type'] = 'photo';
        $message['file_path'] = "data/messages/photos/{$messageId}.png";
        $message['content'] = '📷 Фото';
    }

    // Обработка голосового сообщения
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $audioDir = MESSAGES_DIR . "/audio";
        if (!file_exists($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        $audioMime = $_FILES['audio']['type'] ?? '';
        $audioExt = str_contains($audioMime, 'ogg') ? 'ogg' : 'webm';
        $audioFile = $audioDir . "/{$messageId}.{$audioExt}";
        move_uploaded_file($_FILES['audio']['tmp_name'], $audioFile);

        $message['type'] = 'audio';
        $message['file_path'] = "data/messages/audio/{$messageId}.{$audioExt}";
        $message['content'] = '🎤 Голосовое сообщение';
    }

    // Обработка видео-кружка
    if (isset($_FILES['video_note']) && $_FILES['video_note']['error'] === UPLOAD_ERR_OK) {
        $videoDir = MESSAGES_DIR . "/video_notes";
        if (!file_exists($videoDir)) {
            mkdir($videoDir, 0777, true);
        }

        $videoMime = $_FILES['video_note']['type'] ?? '';
        $videoExt = str_contains($videoMime, 'mp4') ? 'mp4' : 'webm';
        $videoFile = $videoDir . "/{$messageId}.{$videoExt}";
        move_uploaded_file($_FILES['video_note']['tmp_name'], $videoFile);

        $message['type'] = 'video_note';
        $message['file_path'] = "data/messages/video_notes/{$messageId}.{$videoExt}";
        $message['content'] = '🎥 Видео-кружок';
    }
    
    // Сохраняем сообщение
    if (str_starts_with($chatId, 'favorites_')) {
        if ($chatId !== 'favorites_' . $userId) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к избранному']);
            return;
        }
        saveFavoriteMessage($userId, $message);
    } elseif (str_starts_with($chatId, 'channel_')) {
        saveChannelMessage($message);
        
        // Создаем уведомления для участников канала
        $channels = getChannelsList();
        foreach ($channels as $channel) {
            if ($channel['id'] === $chatId) {
                foreach ($channel['members'] as $memberId) {
                    if ($memberId !== $userId) {
                        createNotification($memberId, 'Новое сообщение в канале', "{$user['name']} отправил(а) сообщение в канале {$channel['name']}", 'channel_message');
                    }
                }
                break;
            }
        }
    } elseif (str_starts_with($chatId, 'group_')) {
        saveGroupMessage($message);
        
        // Создаем уведомления для участников группы
        $groups = getGroupsList();
        foreach ($groups as $group) {
            if ($group['id'] === $chatId) {
                foreach ($group['members'] as $memberId) {
                    if ($memberId !== $userId) {
                        createNotification($memberId, 'Новое сообщение в группе', "{$user['name']} отправил(а) сообщение в группе {$group['name']}", 'group_message');
                    }
                }
                break;
            }
        }
    } else {
        $messageDir = MESSAGES_DIR . "/{$chatId}";
        if (!file_exists($messageDir)) {
            mkdir($messageDir, 0777, true);
        }
        file_put_contents($messageDir . "/{$messageId}.json", json_encode($message, JSON_PRETTY_PRINT));
        
        // Создаем уведомление для собеседника
        $chat = json_decode(file_get_contents(CHATS_DIR . "/{$chatId}.json"), true);
        $otherUserId = $chat['participants'][0] === $userId ? $chat['participants'][1] : $chat['participants'][0];
        createNotification($otherUserId, 'Новое сообщение', "{$user['name']} отправил(а) вам сообщение", 'private_message');
    }
    
    $recipientIds = getChatRecipientIds($chatId, $userId);
    publishWebSocketEvent([
        'type' => 'message_created',
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'sender_id' => $userId,
        'recipient_ids' => $recipientIds,
        'timestamp' => time()
    ]);

    echo json_encode(['success' => true, 'message_id' => $messageId]);
}

function forwardToFavorites() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    $userId = $_SESSION['user_id'];
    $sourceChatId = $_POST['source_chat_id'] ?? '';
    $sourceMessageId = $_POST['source_message_id'] ?? '';

    if ($sourceChatId === '' || $sourceMessageId === '') {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
        return;
    }

    if (str_starts_with($sourceChatId, 'favorites_')) {
        if ($sourceChatId !== 'favorites_' . $userId) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
            return;
        }
    } elseif (str_starts_with($sourceChatId, 'channel_')) {
        $allowed = false;
        foreach (getChannelsList() as $channel) {
            if ($channel['id'] === $sourceChatId && in_array($userId, $channel['members'] ?? [])) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
            return;
        }
    } elseif (str_starts_with($sourceChatId, 'group_')) {
        $allowed = false;
        foreach (getGroupsList() as $group) {
            if ($group['id'] === $sourceChatId && in_array($userId, $group['members'] ?? [])) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
            return;
        }
    } else {
        $chatFile = CHATS_DIR . "/{$sourceChatId}.json";
        if (!file_exists($chatFile)) {
            echo json_encode(['success' => false, 'error' => 'Чат не найден']);
            return;
        }
        $chat = json_decode(file_get_contents($chatFile), true);
        if (!$chat || !in_array($userId, $chat['participants'] ?? [])) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
            return;
        }
    }

    $sourceMessage = findMessageByIdInChat($sourceChatId, $sourceMessageId);
    if (!$sourceMessage) {
        echo json_encode(['success' => false, 'error' => 'Сообщение не найдено']);
        return;
    }

    // Запрещаем пересылку системных уведомлений
    if (($sourceMessage['type'] ?? '') === 'system' || ($sourceMessage['type'] ?? '') === 'system_gift') {
        echo json_encode(['success' => false, 'error' => 'Системные сообщения нельзя пересылать']);
        return;
    }

    $favoritesChatId = 'favorites_' . $userId;
    $messageId = generateId('msg');
    $forwarded = [
        'id' => $messageId,
        'chat_id' => $favoritesChatId,
        'sender_id' => $userId,
        'sender_name' => 'Вы',
        'sender_avatar' => (getUserById($userId)['avatar'] ?? '/data/avatars/default.png'),
        'content' => $sourceMessage['content'] ?? '',
        'timestamp' => time(),
        'type' => $sourceMessage['type'] ?? 'text',
        'forwarded_from' => [
            'chat_id' => $sourceChatId,
            'sender_name' => $sourceMessage['sender_name'] ?? 'Пользователь'
        ]
    ];

    if (!empty($sourceMessage['file_path'])) {
        $forwarded['file_path'] = $sourceMessage['file_path'];
    }
    if (!empty($sourceMessage['reply_preview'])) {
        $forwarded['reply_preview'] = $sourceMessage['reply_preview'];
    }

    saveFavoriteMessage($userId, $forwarded);

    publishWebSocketEvent([
        'type' => 'message_created',
        'chat_id' => $favoritesChatId,
        'message_id' => $messageId,
        'sender_id' => $userId,
        'recipient_ids' => [$userId],
        'timestamp' => time()
    ]);

    echo json_encode(['success' => true, 'message_id' => $messageId]);
}

function findMessageByIdInChat($chatId, $messageId) {
    if (str_starts_with($chatId, 'favorites_')) {
        $favoritesFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($favoritesFile) ? (json_decode(file_get_contents($favoritesFile), true) ?: []) : [];
        foreach ($messages as $message) {
            if (($message['id'] ?? '') === $messageId) {
                return $message;
            }
        }
        return null;
    }

    if (str_starts_with($chatId, 'channel_') || str_starts_with($chatId, 'group_')) {
        $storeFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($storeFile) ? (json_decode(file_get_contents($storeFile), true) ?: []) : [];
        foreach ($messages as $message) {
            if (($message['id'] ?? '') === $messageId) {
                return $message;
            }
        }
        return null;
    }

    $messageFile = MESSAGES_DIR . "/{$chatId}/{$messageId}.json";
    if (!file_exists($messageFile)) {
        return null;
    }
    return json_decode(file_get_contents($messageFile), true) ?: null;
}

function getChatRecipientIds($chatId, $senderId) {
    if (str_starts_with($chatId, 'favorites_')) {
        $favoritesOwnerId = substr($chatId, strlen('favorites_'));
        return $favoritesOwnerId !== '' ? [$favoritesOwnerId] : [$senderId];
    }

    if (str_starts_with($chatId, 'channel_')) {
        $channels = getChannelsList();
        foreach ($channels as $channel) {
            if ($channel['id'] === $chatId) {
                return array_values(array_unique($channel['members'] ?? []));
            }
        }
        return [];
    }

    if (str_starts_with($chatId, 'group_')) {
        $groups = getGroupsList();
        foreach ($groups as $group) {
            if ($group['id'] === $chatId) {
                return array_values(array_unique($group['members'] ?? []));
            }
        }
        return [];
    }

    $chatFile = CHATS_DIR . "/{$chatId}.json";
    if (!file_exists($chatFile)) {
        return [$senderId];
    }
    $chat = json_decode(file_get_contents($chatFile), true);
    if (!$chat) {
        return [$senderId];
    }
    return array_values(array_unique($chat['participants'] ?? [$senderId]));
}

function publishWebSocketEvent($event) {
    $logFile = DATA_DIR . '/ws_events.log';
    $line = json_encode($event, JSON_UNESCAPED_UNICODE);
    if ($line === false) {
        return;
    }
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

    $socket = @stream_socket_client(
        'tcp://' . WS_BACKEND_HOST . ':' . WS_BACKEND_PORT,
        $errno,
        $errstr,
        0.2
    );
    if ($socket !== false) {
        @fwrite($socket, $line . PHP_EOL);
        @fclose($socket);
    }
}

function editMessage() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    $chatId = $_POST['chat_id'] ?? '';
    $messageId = $_POST['message_id'] ?? '';
    $newContent = trim($_POST['content'] ?? '');
    $userId = $_SESSION['user_id'];

    if ($chatId === '' || $messageId === '' || $newContent === '') {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных для редактирования']);
        return;
    }

    $updated = updateMessageInStorage($chatId, $messageId, $userId, function(&$message) use ($newContent) {
        if (($message['type'] ?? 'text') !== 'text') {
            return ['success' => false, 'error' => 'Можно редактировать только текстовые сообщения'];
        }
        $message['content'] = mb_substr($newContent, 0, 200);
        $message['edited'] = true;
        $message['edited_at'] = time();
        return ['success' => true];
    });

    if (!$updated['success']) {
        echo json_encode($updated);
        return;
    }

    $recipientIds = getChatRecipientIds($chatId, $userId);
    publishWebSocketEvent([
        'type' => 'message_updated',
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'recipient_ids' => $recipientIds,
        'timestamp' => time()
    ]);

    echo json_encode(['success' => true]);
}

function deleteMessage() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    $chatId = $_POST['chat_id'] ?? '';
    $messageId = $_POST['message_id'] ?? '';
    $userId = $_SESSION['user_id'];

    if ($chatId === '' || $messageId === '') {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных для удаления']);
        return;
    }

    $deleted = removeMessageFromStorage($chatId, $messageId, $userId);
    if (!$deleted['success']) {
        echo json_encode($deleted);
        return;
    }

    $recipientIds = getChatRecipientIds($chatId, $userId);
    publishWebSocketEvent([
        'type' => 'message_deleted',
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'recipient_ids' => $recipientIds,
        'timestamp' => time()
    ]);

    echo json_encode(['success' => true]);
}

function updateMessageInStorage($chatId, $messageId, $userId, $modifier) {
    if (str_starts_with($chatId, 'favorites_')) {
        if ($chatId !== 'favorites_' . $userId) {
            return ['success' => false, 'error' => 'Нет доступа к избранному'];
        }
        $favoritesFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($favoritesFile) ? (json_decode(file_get_contents($favoritesFile), true) ?: []) : [];
        foreach ($messages as &$message) {
            if (($message['id'] ?? '') === $messageId) {
                if (($message['sender_id'] ?? '') !== $userId) {
                    return ['success' => false, 'error' => 'Можно редактировать только свои сообщения'];
                }
                $result = $modifier($message);
                if (!$result['success']) return $result;
                file_put_contents($favoritesFile, json_encode($messages, JSON_PRETTY_PRINT));
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }

    if (str_starts_with($chatId, 'channel_') || str_starts_with($chatId, 'group_')) {
        $storeFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($storeFile) ? (json_decode(file_get_contents($storeFile), true) ?: []) : [];
        foreach ($messages as &$message) {
            if (($message['id'] ?? '') === $messageId) {
                if (($message['sender_id'] ?? '') !== $userId) {
                    return ['success' => false, 'error' => 'Можно редактировать только свои сообщения'];
                }
                $result = $modifier($message);
                if (!$result['success']) return $result;
                file_put_contents($storeFile, json_encode($messages, JSON_PRETTY_PRINT));
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }

    $messageFile = MESSAGES_DIR . "/{$chatId}/{$messageId}.json";
    if (!file_exists($messageFile)) {
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }
    $message = json_decode(file_get_contents($messageFile), true);
    if (!$message) {
        return ['success' => false, 'error' => 'Сообщение повреждено'];
    }
    if (($message['sender_id'] ?? '') !== $userId) {
        return ['success' => false, 'error' => 'Можно редактировать только свои сообщения'];
    }
    $result = $modifier($message);
    if (!$result['success']) return $result;
    file_put_contents($messageFile, json_encode($message, JSON_PRETTY_PRINT));
    return ['success' => true];
}

function removeMessageFromStorage($chatId, $messageId, $userId) {
    if (str_starts_with($chatId, 'favorites_')) {
        if ($chatId !== 'favorites_' . $userId) {
            return ['success' => false, 'error' => 'Нет доступа к избранному'];
        }
        $favoritesFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($favoritesFile) ? (json_decode(file_get_contents($favoritesFile), true) ?: []) : [];
        foreach ($messages as $idx => $message) {
            if (($message['id'] ?? '') === $messageId) {
                if (($message['sender_id'] ?? '') !== $userId) {
                    return ['success' => false, 'error' => 'Можно удалять только свои сообщения'];
                }
                array_splice($messages, $idx, 1);
                file_put_contents($favoritesFile, json_encode($messages, JSON_PRETTY_PRINT));
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }

    if (str_starts_with($chatId, 'channel_') || str_starts_with($chatId, 'group_')) {
        $storeFile = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($storeFile) ? (json_decode(file_get_contents($storeFile), true) ?: []) : [];
        foreach ($messages as $idx => $message) {
            if (($message['id'] ?? '') === $messageId) {
                if (($message['sender_id'] ?? '') !== $userId) {
                    return ['success' => false, 'error' => 'Можно удалять только свои сообщения'];
                }
                array_splice($messages, $idx, 1);
                file_put_contents($storeFile, json_encode($messages, JSON_PRETTY_PRINT));
                return ['success' => true];
            }
        }
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }

    $messageFile = MESSAGES_DIR . "/{$chatId}/{$messageId}.json";
    if (!file_exists($messageFile)) {
        return ['success' => false, 'error' => 'Сообщение не найдено'];
    }
    $message = json_decode(file_get_contents($messageFile), true);
    if (!$message || ($message['sender_id'] ?? '') !== $userId) {
        return ['success' => false, 'error' => 'Можно удалять только свои сообщения'];
    }
    @unlink($messageFile);
    return ['success' => true];
}

function searchUsers() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $query = strtolower(trim($_POST['query'] ?? ''));
    $currentUserId = $_SESSION['user_id'];
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        return;
    }
    
    $users = [];
    $userFiles = scandir(USERS_DIR);
    
    foreach ($userFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $user = json_decode(file_get_contents(USERS_DIR . "/{$file}"), true);
            if ($user && $user['id'] !== $currentUserId && $user['setup_completed']) {
                // Пропускаем заблокированных пользователей
                if (isUserBlocked($user['id'])) {
                    continue;
                }
                
                $usernameMatch = stripos($user['username'], $query) !== false;
                $nameMatch = stripos($user['name'], $query) !== false;
                
                if ($usernameMatch || $nameMatch) {
                    unset($user['password_hash']);
                    $users[] = $user;
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
}

// Функции для подарков
function buyGift() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете покупать подарки.']);
        return;
    }
    
    $giftKey = $_POST['gift_key'] ?? '';
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    // Определяем стоимость подарков
    $giftPrices = [
        'bear' => 10, 'heart' => 15, 'champagne' => 20, 
        'casino' => 8000, 'cat' => 50, 'lemon' => 200,
        'duck' => 20, 'burger' => 500, 'santa' => 100,
        'halloween' => 300, 'agermester' => 500, 
        'tablet' => 40, 'graphic' => 30
    ];
    
    $giftEmojis = [
        'bear' => '🐻', 'heart' => '❤️', 'champagne' => '🍾',
        'casino' => '🎰', 'cat' => '😻', 'lemon' => '🍋',
        'duck' => '🦆', 'burger' => '🍔', 'santa' => '🎅',
        'halloween' => '🎃', 'agermester' => '🥃',
        'tablet' => '💻', 'graphic' => '📈'
    ];
    
    $giftNames = [
        'bear' => 'Мишка', 'heart' => 'Сердце', 'champagne' => 'Шампанское',
        'casino' => 'Казино', 'cat' => 'Котик', 'lemon' => 'Лимон',
        'duck' => 'Утка', 'burger' => 'Бургер', 'santa' => 'Санта',
        'halloween' => 'Буу...', 'agermester' => 'Ягерместер',
        'tablet' => 'Ноутбук', 'graphic' => 'График'
    ];
    
    if (!isset($giftPrices[$giftKey])) {
        echo json_encode(['success' => false, 'error' => 'Неизвестный подарок']);
        return;
    }
    
    $price = $giftPrices[$giftKey];
    
    if ($user['stars'] < $price) {
        echo json_encode(['success' => false, 'error' => 'Недостаточно звезд']);
        return;
    }
    
    // Списываем звезды и сразу кладем подарок в инвентарь пользователя
    $user['stars'] -= $price;
    if (!isset($user['gifts']) || !is_array($user['gifts'])) {
        $user['gifts'] = [];
    }
    $giftId = generateId('gift');
    $user['gifts'][$giftId] = $giftEmojis[$giftKey];
    saveUser($user);
    
    echo json_encode([
        'success' => true,
        'new_stars' => $user['stars'],
        'gift_id' => $giftId,
        'gift_key' => $giftKey,
        'gift_emoji' => $giftEmojis[$giftKey],
        'gift_name' => $giftNames[$giftKey]
    ]);
}

function buyPremium() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован.']);
        return;
    }

    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }

    $premiumPrice = 300;
    if (($user['is_premium'] ?? false) === true) {
        echo json_encode(['success' => false, 'error' => 'Pulse Premium уже активен']);
        return;
    }
    if (($user['stars'] ?? 0) < $premiumPrice) {
        echo json_encode(['success' => false, 'error' => 'Недостаточно звезд']);
        return;
    }

    $user['stars'] -= $premiumPrice;
    $user['is_premium'] = true;
    $user['premium_since'] = time();
    saveUser($user);

    createSecurityBotMessage($user['id'], "✨ Pulse Premium активирован. Теперь вы можете публиковать истории.");

    echo json_encode([
        'success' => true,
        'new_stars' => $user['stars'],
        'is_premium' => true
    ]);
}

function forwardMessage() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    $userId = $_SESSION['user_id'];
    $sourceChatId = $_POST['source_chat_id'] ?? '';
    $sourceMessageId = $_POST['source_message_id'] ?? '';
    $targetChatId = $_POST['target_chat_id'] ?? '';

    if ($sourceChatId === '' || $sourceMessageId === '' || $targetChatId === '') {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
        return;
    }
    if (str_starts_with($targetChatId, 'security_bot_')) {
        echo json_encode(['success' => false, 'error' => 'Нельзя переслать сообщение боту безопасности']);
        return;
    }

    $sourceMessage = findMessageByIdInChat($sourceChatId, $sourceMessageId);
    if (!$sourceMessage) {
        echo json_encode(['success' => false, 'error' => 'Сообщение не найдено']);
        return;
    }
    if (($sourceMessage['type'] ?? '') === 'system' || ($sourceMessage['type'] ?? '') === 'system_gift') {
        echo json_encode(['success' => false, 'error' => 'Системные сообщения нельзя пересылать']);
        return;
    }

    // Проверяем доступ и право отправки в целевой чат
    if (str_starts_with($targetChatId, 'favorites_')) {
        if ($targetChatId !== 'favorites_' . $userId) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к избранному']);
            return;
        }
    } elseif (str_starts_with($targetChatId, 'channel_')) {
        $targetChannel = null;
        foreach (getChannelsList() as $channel) {
            if ($channel['id'] === $targetChatId) {
                $targetChannel = $channel;
                break;
            }
        }
        if (!$targetChannel || !in_array($userId, $targetChannel['members'] ?? [])) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к каналу']);
            return;
        }
        $isAdmin = in_array($userId, $targetChannel['admins'] ?? []);
        $canWrite = $isAdmin || ($targetChannel['settings']['allow_all_write'] ?? false);
        if (!$canWrite) {
            echo json_encode(['success' => false, 'error' => 'Только администраторы могут писать в этот канал']);
            return;
        }
    } elseif (str_starts_with($targetChatId, 'group_')) {
        $allowed = false;
        foreach (getGroupsList() as $group) {
            if ($group['id'] === $targetChatId && in_array($userId, $group['members'] ?? [])) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к группе']);
            return;
        }
    } else {
        $chatFile = CHATS_DIR . "/{$targetChatId}.json";
        if (!file_exists($chatFile)) {
            echo json_encode(['success' => false, 'error' => 'Чат не найден']);
            return;
        }
        $chat = json_decode(file_get_contents($chatFile), true);
        if (!$chat || !in_array($userId, $chat['participants'] ?? [])) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
            return;
        }
    }

    $user = getUserById($userId);
    $messageId = generateId('msg');
    $forwarded = [
        'id' => $messageId,
        'chat_id' => $targetChatId,
        'sender_id' => $userId,
        'sender_name' => $user['name'] ?? 'Вы',
        'sender_avatar' => $user['avatar'] ?? '/data/avatars/default.png',
        'content' => $sourceMessage['content'] ?? '',
        'timestamp' => time(),
        'type' => $sourceMessage['type'] ?? 'text',
        'forwarded_from' => [
            'chat_id' => $sourceChatId,
            'sender_name' => $sourceMessage['sender_name'] ?? 'Пользователь'
        ]
    ];
    if (!empty($sourceMessage['file_path'])) {
        $forwarded['file_path'] = $sourceMessage['file_path'];
    }

    if (str_starts_with($targetChatId, 'favorites_')) {
        saveFavoriteMessage($userId, $forwarded);
    } elseif (str_starts_with($targetChatId, 'channel_')) {
        saveChannelMessage($forwarded);
    } elseif (str_starts_with($targetChatId, 'group_')) {
        saveGroupMessage($forwarded);
    } else {
        $messageDir = MESSAGES_DIR . "/{$targetChatId}";
        if (!file_exists($messageDir)) {
            mkdir($messageDir, 0777, true);
        }
        file_put_contents($messageDir . "/{$messageId}.json", json_encode($forwarded, JSON_PRETTY_PRINT));
    }

    $recipientIds = getChatRecipientIds($targetChatId, $userId);
    publishWebSocketEvent([
        'type' => 'message_created',
        'chat_id' => $targetChatId,
        'message_id' => $messageId,
        'sender_id' => $userId,
        'recipient_ids' => $recipientIds,
        'timestamp' => time()
    ]);

    echo json_encode(['success' => true, 'message_id' => $messageId, 'target_chat_id' => $targetChatId]);
}

function sendGiftDirect() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }

    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете отправлять подарки.']);
        return;
    }

    $senderId = $_SESSION['user_id'];
    $recipientId = $_POST['recipient_id'] ?? '';
    $giftKey = $_POST['gift_key'] ?? '';

    if (empty($recipientId) || empty($giftKey)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны данные']);
        return;
    }
    if ($recipientId === $senderId) {
        echo json_encode(['success' => false, 'error' => 'Нельзя отправить подарок самому себе через этот режим']);
        return;
    }

    $giftPrices = [
        'bear' => 10, 'heart' => 15, 'champagne' => 20,
        'casino' => 8000, 'cat' => 50, 'lemon' => 200,
        'duck' => 20, 'burger' => 500, 'santa' => 100,
        'halloween' => 300, 'agermester' => 500,
        'tablet' => 40, 'graphic' => 30
    ];
    $giftEmojis = [
        'bear' => '🐻', 'heart' => '❤️', 'champagne' => '🍾',
        'casino' => '🎰', 'cat' => '😻', 'lemon' => '🍋',
        'duck' => '🦆', 'burger' => '🍔', 'santa' => '🎅',
        'halloween' => '🎃', 'agermester' => '🥃',
        'tablet' => '💻', 'graphic' => '📈'
    ];
    $giftNames = [
        'bear' => 'Мишка', 'heart' => 'Сердце', 'champagne' => 'Шампанское',
        'casino' => 'Казино', 'cat' => 'Котик', 'lemon' => 'Лимон',
        'duck' => 'Утка', 'burger' => 'Бургер', 'santa' => 'Санта',
        'halloween' => 'Буу...', 'agermester' => 'Ягерместер',
        'tablet' => 'Ноутбук', 'graphic' => 'График'
    ];

    if (!isset($giftPrices[$giftKey])) {
        echo json_encode(['success' => false, 'error' => 'Неизвестный подарок']);
        return;
    }

    $sender = getUserById($senderId);
    $recipient = getUserById($recipientId);
    if (!$sender || !$recipient) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }

    $price = $giftPrices[$giftKey];
    if (($sender['stars'] ?? 0) < $price) {
        echo json_encode(['success' => false, 'error' => 'Недостаточно звезд']);
        return;
    }

    $sender['stars'] -= $price;
    saveUser($sender);

    $giftId = generateId('gift');
    $recipient['gifts'][$giftId] = $giftEmojis[$giftKey];
    saveUser($recipient);

    $privateChatId = getOrCreatePrivateChatId($senderId, $recipientId);
    if ($privateChatId) {
        $chatSystemMessageId = generateId('msg');
        $chatSystemMessage = [
            'id' => $chatSystemMessageId,
            'chat_id' => $privateChatId,
            'sender_id' => 'system',
            'sender_name' => 'Система',
            'content' => "🎁 {$sender['name']} подарил(а) {$giftEmojis[$giftKey]} {$giftNames[$giftKey]}",
            'timestamp' => time(),
            'type' => 'system_gift'
        ];
        $messageDir = MESSAGES_DIR . "/{$privateChatId}";
        if (!file_exists($messageDir)) {
            mkdir($messageDir, 0777, true);
        }
        file_put_contents($messageDir . "/{$chatSystemMessageId}.json", json_encode($chatSystemMessage, JSON_PRETTY_PRINT));
        publishWebSocketEvent([
            'type' => 'message_created',
            'chat_id' => $privateChatId,
            'message_id' => $chatSystemMessageId,
            'sender_id' => 'system',
            'recipient_ids' => [$senderId, $recipientId],
            'timestamp' => time()
        ]);
    }

    createNotification($recipientId, 'Новый подарок!', "{$sender['name']} отправил(а) вам {$giftEmojis[$giftKey]} {$giftNames[$giftKey]}", 'gift');

    echo json_encode([
        'success' => true,
        'gift_emoji' => $giftEmojis[$giftKey],
        'gift_name' => $giftNames[$giftKey],
        'recipient_name' => $recipient['name'],
        'new_stars' => $sender['stars'],
        'chat_id' => $privateChatId
    ]);
}

function createStory() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    if (empty($user['is_premium'])) {
        echo json_encode(['success' => false, 'error' => 'Истории доступны только с Pulse Premium']);
        return;
    }

    $caption = trim($_POST['content'] ?? '');
    if ($caption === '' && (!isset($_FILES['story_media']) || $_FILES['story_media']['error'] !== UPLOAD_ERR_OK)) {
        echo json_encode(['success' => false, 'error' => 'Добавьте фото/видео или подпись']);
        return;
    }
    $caption = mb_substr($caption, 0, 200);

    $mediaPath = '';
    $mediaType = 'text';
    if (isset($_FILES['story_media']) && $_FILES['story_media']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['story_media']['tmp_name'];
        $size = (int)($_FILES['story_media']['size'] ?? 0);
        if ($size > 15 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Файл слишком большой (макс. 15MB)']);
            return;
        }
        $mime = mime_content_type($tmp) ?: '';
        if (str_starts_with($mime, 'image/')) {
            $mediaType = 'image';
            $ext = '.jpg';
        } elseif (str_starts_with($mime, 'video/')) {
            $mediaType = 'video';
            $ext = '.mp4';
        } else {
            echo json_encode(['success' => false, 'error' => 'Поддерживаются только фото и видео']);
            return;
        }
        $fileName = generateId('story_media') . $ext;
        $target = STORIES_MEDIA_DIR . "/{$fileName}";
        if (!move_uploaded_file($tmp, $target)) {
            echo json_encode(['success' => false, 'error' => 'Не удалось загрузить файл истории']);
            return;
        }
        $mediaPath = "/data/stories/media/{$fileName}";
    }

    $stories = getUserStoriesList($userId);
    $stories[] = [
        'id' => generateId('story'),
        'user_id' => $userId,
        'content' => $caption,
        'media_path' => $mediaPath,
        'media_type' => $mediaType,
        'created_at' => time()
    ];
    // Keep last 100 stories only
    if (count($stories) > 100) {
        $stories = array_slice($stories, -100);
    }
    saveUserStoriesList($userId, $stories);

    echo json_encode(['success' => true]);
}

function getStoriesFeed() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $userId = $_SESSION['user_id'];
    $now = time();
    $ids = getPrivateChatPartnerIds($userId);
    $ids[] = $userId;
    $ids = array_values(array_unique($ids));

    $feed = [];
    foreach ($ids as $id) {
        $user = getUserById($id);
        if (!$user) continue;
        $stories = getActiveStories(getUserStoriesList($id), $now);
        if (!count($stories)) continue;
        usort($stories, function($a, $b) {
            return ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0);
        });
        $feed[] = [
            'user_id' => $id,
            'name' => $user['name'] ?: $user['username'],
            'avatar' => $user['avatar'] ?? '/data/avatars/default.png',
            'stories' => $stories,
            'last_story_at' => $stories[0]['created_at'] ?? 0
        ];
    }

    usort($feed, function($a, $b) {
        return ($b['last_story_at'] ?? 0) <=> ($a['last_story_at'] ?? 0);
    });

    echo json_encode(['success' => true, 'stories' => $feed]);
}

function getUserStories() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $viewerId = $_SESSION['user_id'];
    $targetUserId = $_POST['user_id'] ?? '';
    if ($targetUserId === '') {
        $targetUserId = $viewerId;
    }

    if ($targetUserId !== $viewerId) {
        $allowed = in_array($targetUserId, getPrivateChatPartnerIds($viewerId), true);
        if (!$allowed) {
            echo json_encode(['success' => false, 'error' => 'Нет доступа к историям пользователя']);
            return;
        }
    }

    $targetUser = getUserById($targetUserId);
    if (!$targetUser) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }

    $stories = getActiveStories(getUserStoriesList($targetUserId));
    usort($stories, function($a, $b) {
        return ($a['created_at'] ?? 0) <=> ($b['created_at'] ?? 0);
    });

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $targetUser['id'],
            'name' => $targetUser['name'] ?: $targetUser['username'],
            'avatar' => $targetUser['avatar'] ?? '/data/avatars/default.png'
        ],
        'stories' => $stories
    ]);
}

function sendGift() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете отправлять подарки.']);
        return;
    }
    
    $senderId = $_SESSION['user_id'];
    $recipientId = $_POST['recipient_id'] ?? '';
    $giftKey = $_POST['gift_key'] ?? '';
    $giftEmoji = $_POST['gift_emoji'] ?? '';
    $giftName = $_POST['gift_name'] ?? '';
    
    if (empty($recipientId) || empty($giftKey)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны данные']);
        return;
    }
    
    $recipient = getUserById($recipientId);
    $sender = getUserById($senderId);
    
    if (!$recipient) {
        echo json_encode(['success' => false, 'error' => 'Получатель не найден']);
        return;
    }
    
    $giftId = generateId('gift');
    $isSelf = $senderId === $recipientId;
    
    // Добавляем подарок получателю
    $recipient['gifts'][$giftId] = $giftEmoji;
    saveUser($recipient);
    
    if (!$isSelf) {
        $privateChatId = getOrCreatePrivateChatId($senderId, $recipientId);
        if ($privateChatId) {
            $chatSystemMessageId = generateId('msg');
            $chatSystemMessage = [
                'id' => $chatSystemMessageId,
                'chat_id' => $privateChatId,
                'sender_id' => 'system',
                'sender_name' => 'Система',
            'content' => "🎁 {$sender['name']} подарил(а) {$giftEmoji} {$giftName}",
                'timestamp' => time(),
                'type' => 'system_gift'
            ];
            $messageDir = MESSAGES_DIR . "/{$privateChatId}";
            if (!file_exists($messageDir)) {
                mkdir($messageDir, 0777, true);
            }
            file_put_contents($messageDir . "/{$chatSystemMessageId}.json", json_encode($chatSystemMessage, JSON_PRETTY_PRINT));
            publishWebSocketEvent([
                'type' => 'message_created',
                'chat_id' => $privateChatId,
                'message_id' => $chatSystemMessageId,
                'sender_id' => 'system',
                'recipient_ids' => [$senderId, $recipientId],
                'timestamp' => time()
            ]);
        }

        // Создаем уведомление для получателя
        createNotification($recipientId, 'Новый подарок!', "{$sender['name']} отправил(а) вам {$giftEmoji} {$giftName}", 'gift');
    }
    
    echo json_encode([
        'success' => true,
        'is_self' => $isSelf,
        'gift_emoji' => $giftEmoji,
        'recipient_name' => $recipient['name']
    ]);
}

// Функции для уведомлений
function createNotification($userId, $title, $message, $type = 'info') {
    $notification = [
        'id' => generateId('notif'),
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'read' => false,
        'timestamp' => time()
    ];
    
    saveNotification($notification);
    publishWebSocketEvent([
        'type' => 'notification_created',
        'recipient_ids' => [$userId],
        'title' => $title,
        'message' => $message,
        'notification_type' => $type,
        'timestamp' => $notification['timestamp']
    ]);
    return $notification;
}

function getNotifications() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $notifications = getUserNotifications($userId);
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

function getCalls() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $userId = $_SESSION['user_id'];
    $calls = getUserCallsRaw($userId);
    usort($calls, function($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });
    echo json_encode(['success' => true, 'calls' => $calls]);
}

function createCall() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $callerId = $_SESSION['user_id'];
    $chatId = trim($_POST['chat_id'] ?? '');
    $callType = trim($_POST['call_type'] ?? 'voice');
    if ($chatId === '') {
        echo json_encode(['success' => false, 'error' => 'Не указан чат']);
        return;
    }
    if (!in_array($callType, ['voice', 'video'], true)) {
        $callType = 'voice';
    }
    if (str_starts_with($chatId, 'security_bot_')) {
        echo json_encode(['success' => false, 'error' => 'Звонки боту безопасности недоступны']);
        return;
    }
    $chatFile = CHATS_DIR . "/{$chatId}.json";
    if (!file_exists($chatFile)) {
        echo json_encode(['success' => false, 'error' => 'Чат не найден']);
        return;
    }
    $chat = json_decode(file_get_contents($chatFile), true);
    if (!$chat || ($chat['type'] ?? 'private') !== 'private') {
        echo json_encode(['success' => false, 'error' => 'Звонки доступны только в личных чатах']);
        return;
    }
    $participants = $chat['participants'] ?? [];
    if (!in_array($callerId, $participants, true) || count($participants) !== 2) {
        echo json_encode(['success' => false, 'error' => 'Нет доступа к звонку']);
        return;
    }
    $calleeId = $participants[0] === $callerId ? $participants[1] : $participants[0];
    if ($calleeId === 'security_bot') {
        echo json_encode(['success' => false, 'error' => 'Звонки боту безопасности недоступны']);
        return;
    }

    $caller = getUserById($callerId);
    $callee = getUserById($calleeId);
    if (!$caller || !$callee) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }

    $timestamp = time();
    $directionLabel = $callType === 'video' ? 'видеозвонок' : 'звонок';
    $callId = generateId('call');

    appendCallForUser($callerId, [
        'id' => $callId,
        'chat_id' => $chatId,
        'type' => $callType,
        'direction' => 'outgoing',
        'timestamp' => $timestamp,
        'peer_id' => $callee['id'],
        'peer_name' => $callee['name'] ?: $callee['username'],
        'peer_avatar' => $callee['avatar'] ?? '/data/avatars/default.png'
    ]);
    appendCallForUser($calleeId, [
        'id' => $callId,
        'chat_id' => $chatId,
        'type' => $callType,
        'direction' => 'incoming',
        'timestamp' => $timestamp,
        'peer_id' => $caller['id'],
        'peer_name' => $caller['name'] ?: $caller['username'],
        'peer_avatar' => $caller['avatar'] ?? '/data/avatars/default.png'
    ]);

    createNotification($calleeId, 'Входящий звонок', "{$caller['name']} пытается сделать {$directionLabel}", 'call');
    publishWebSocketEvent([
        'type' => 'call_created',
        'chat_id' => $chatId,
        'call_id' => $callId,
        'recipient_ids' => [$callerId, $calleeId],
        'timestamp' => $timestamp
    ]);

    echo json_encode([
        'success' => true,
        'call_id' => $callId,
        'chat_id' => $chatId,
        'peer_id' => $callee['id'],
        'peer_name' => $callee['name'] ?: $callee['username']
    ]);
}

function markNotificationRead() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $notificationId = $_POST['notification_id'] ?? '';
    
    if (empty($notificationId)) {
        echo json_encode(['success' => false, 'error' => 'Не указано уведомление']);
        return;
    }
    
    $notificationDir = NOTIFICATIONS_DIR . "/{$userId}";
    $notificationFile = $notificationDir . "/{$notificationId}.json";
    
    if (file_exists($notificationFile)) {
        $notification = json_decode(file_get_contents($notificationFile), true);
        $notification['read'] = true;
        file_put_contents($notificationFile, json_encode($notification, JSON_PRETTY_PRINT));
    }
    
    echo json_encode(['success' => true]);
}

function clearNotifications() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $notificationDir = NOTIFICATIONS_DIR . "/{$userId}";
    
    if (file_exists($notificationDir)) {
        $files = scandir($notificationDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                unlink($notificationDir . "/{$file}");
            }
        }
    }
    
    echo json_encode(['success' => true]);
}

// Функции для каналов
function createChannel() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете создавать каналы.']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Название канала не может быть пустым']);
        return;
    }
    
    $channels = getChannelsList();
    $channelId = generateId('channel');
    $username = generateUsername($name, 'channel');
    
    $newChannel = [
        'id' => $channelId,
        'name' => $name,
        'username' => $username,
        'description' => $description,
        'creator_id' => $userId,
        'members' => [$userId],
        'admins' => [$userId], // Создатель автоматически становится администратором
        'created_at' => time(),
        'avatar' => '/data/avatars/channel.png',
        'type' => 'channel',
        'settings' => [
            'allow_all_write' => false, // По умолчанию только админы могут писать
            'public_join' => true
        ]
    ];
    
    $channels[] = $newChannel;
    saveChannelsList($channels);
    
    // Добавляем канал пользователю
    $user = getUserById($userId);
    if (!isset($user['channels'])) {
        $user['channels'] = [];
    }
    $user['channels'][] = $channelId;
    saveUser($user);
    
    echo json_encode(['success' => true, 'channel_id' => $channelId, 'username' => $username]);
}

function createGroup() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете создавать группы.']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Название группы не может быть пустым']);
        return;
    }
    
    $groups = getGroupsList();
    $groupId = generateId('group');
    $username = generateUsername($name, 'group');
    
    $newGroup = [
        'id' => $groupId,
        'name' => $name,
        'username' => $username,
        'description' => $description,
        'creator_id' => $userId,
        'members' => [$userId],
        'created_at' => time(),
        'avatar' => '/data/avatars/group.png',
        'type' => 'group'
    ];
    
    $groups[] = $newGroup;
    saveGroupsList($groups);
    
    // Добавляем группу пользователю
    $user = getUserById($userId);
    if (!isset($user['groups'])) {
        $user['groups'] = [];
    }
    $user['groups'][] = $groupId;
    saveUser($user);
    
    echo json_encode(['success' => true, 'group_id' => $groupId, 'username' => $username]);
}

function getChannels() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channels = getChannelsList();
    $userChannels = [];
    
    foreach ($channels as $channel) {
        if (in_array($userId, $channel['members'])) {
            $userChannels[] = $channel;
        }
    }
    
    echo json_encode(['success' => true, 'channels' => $userChannels]);
}

function getGroups() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $groups = getGroupsList();
    $userGroups = [];
    
    foreach ($groups as $group) {
        if (in_array($userId, $group['members'])) {
            $userGroups[] = $group;
        }
    }
    
    echo json_encode(['success' => true, 'groups' => $userGroups]);
}

function joinChannel() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете присоединяться к каналам.']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    $channelUsername = $_POST['channel_username'] ?? '';
    
    if (empty($channelId) && empty($channelUsername)) {
        echo json_encode(['success' => false, 'error' => 'Не указан канал']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = false;
    
    foreach ($channels as &$channel) {
        if (($channelId && $channel['id'] === $channelId) || ($channelUsername && $channel['username'] === $channelUsername)) {
            if (!in_array($userId, $channel['members'])) {
                $channel['members'][] = $userId;
                $channelId = $channel['id'];
            }
            $channelFound = true;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    saveChannelsList($channels);
    
    // Добавляем канал пользователю
    $user = getUserById($userId);
    if (!isset($user['channels'])) {
        $user['channels'] = [];
    }
    if (!in_array($channelId, $user['channels'])) {
        $user['channels'][] = $channelId;
    }
    saveUser($user);
    
    echo json_encode(['success' => true]);
}

function joinGroup() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    // Проверка блокировки
    if (isUserBlocked($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован. Вы не можете присоединяться к группам.']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $groupId = $_POST['group_id'] ?? '';
    $groupUsername = $_POST['group_username'] ?? '';
    
    if (empty($groupId) && empty($groupUsername)) {
        echo json_encode(['success' => false, 'error' => 'Не указана группа']);
        return;
    }
    
    $groups = getGroupsList();
    $groupFound = false;
    
    foreach ($groups as &$group) {
        if (($groupId && $group['id'] === $groupId) || ($groupUsername && $group['username'] === $groupUsername)) {
            if (!in_array($userId, $group['members'])) {
                $group['members'][] = $userId;
                $groupId = $group['id'];
            }
            $groupFound = true;
            break;
        }
    }
    
    if (!$groupFound) {
        echo json_encode(['success' => false, 'error' => 'Группа не найдена']);
        return;
    }
    
    saveGroupsList($groups);
    
    // Добавляем группу пользователю
    $user = getUserById($userId);
    if (!isset($user['groups'])) {
        $user['groups'] = [];
    }
    if (!in_array($groupId, $user['groups'])) {
        $user['groups'][] = $groupId;
    }
    saveUser($user);
    
    echo json_encode(['success' => true]);
}

function searchChannelsGroups() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $query = strtolower(trim($_POST['query'] ?? ''));
    $userId = $_SESSION['user_id'];
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'results' => []]);
        return;
    }
    
    $results = [];
    
    // Поиск в каналах
    $channels = getChannelsList();
    foreach ($channels as $channel) {
        $nameMatch = stripos($channel['name'], $query) !== false;
        $usernameMatch = stripos($channel['username'], $query) !== false;
        
        if (($nameMatch || $usernameMatch) && !in_array($userId, $channel['members'])) {
            $results[] = $channel;
        }
    }
    
    // Поиск в группах
    $groups = getGroupsList();
    foreach ($groups as $group) {
        $nameMatch = stripos($group['name'], $query) !== false;
        $usernameMatch = stripos($group['username'], $query) !== false;
        
        if (($nameMatch || $usernameMatch) && !in_array($userId, $group['members'])) {
            $results[] = $group;
        }
    }
    
    echo json_encode(['success' => true, 'results' => $results]);
}

function getChannelGroupByUsername() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $username = strtolower(trim($_POST['username'] ?? ''));
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Не указан username']);
        return;
    }
    
    // Поиск в каналах
    $channels = getChannelsList();
    foreach ($channels as $channel) {
        if ($channel['username'] === $username) {
            echo json_encode(['success' => true, 'type' => 'channel', 'data' => $channel]);
            return;
        }
    }
    
    // Поиск в группах
    $groups = getGroupsList();
    foreach ($groups as $group) {
        if ($group['username'] === $username) {
            echo json_encode(['success' => true, 'type' => 'group', 'data' => $group]);
            return;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Канал или группа не найдены']);
}

function leaveChannel() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан канал']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = false;
    
    foreach ($channels as &$channel) {
        if ($channel['id'] === $channelId) {
            $channel['members'] = array_diff($channel['members'], [$userId]);
            // Удаляем из администраторов, если пользователь был админом
            if (in_array($userId, $channel['admins'] ?? [])) {
                $channel['admins'] = array_diff($channel['admins'], [$userId]);
            }
            $channelFound = true;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    saveChannelsList($channels);
    
    // Удаляем канал у пользователя
    $user = getUserById($userId);
    if (isset($user['channels'])) {
        $user['channels'] = array_diff($user['channels'], [$channelId]);
        saveUser($user);
    }
    
    echo json_encode(['success' => true]);
}

function leaveGroup() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $groupId = $_POST['group_id'] ?? '';
    
    if (empty($groupId)) {
        echo json_encode(['success' => false, 'error' => 'Не указана группа']);
        return;
    }
    
    $groups = getGroupsList();
    $groupFound = false;
    
    foreach ($groups as &$group) {
        if ($group['id'] === $groupId) {
            $group['members'] = array_diff($group['members'], [$userId]);
            $groupFound = true;
            break;
        }
    }
    
    if (!$groupFound) {
        echo json_encode(['success' => false, 'error' => 'Группа не найдена']);
        return;
    }
    
    saveGroupsList($groups);
    
    // Удаляем группу у пользователя
    $user = getUserById($userId);
    if (isset($user['groups'])) {
        $user['groups'] = array_diff($user['groups'], [$groupId]);
        saveUser($user);
    }
    
    echo json_encode(['success' => true]);
}

// Функции для управления правами каналов
function getChannelSettings() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан канал']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = null;
    
    foreach ($channels as $channel) {
        if ($channel['id'] === $channelId) {
            // Проверяем, является ли пользователь администратором
            if (!in_array($userId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Только администраторы могут просматривать настройки']);
                return;
            }
            $channelFound = $channel;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    // Получаем информацию об администраторах
    $admins = [];
    foreach ($channelFound['admins'] as $adminId) {
        $adminUser = getUserById($adminId);
        if ($adminUser) {
            $admins[] = [
                'id' => $adminUser['id'],
                'name' => $adminUser['name'],
                'username' => $adminUser['username'],
                'avatar' => $adminUser['avatar']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $channelFound['settings'] ?? [],
        'admins' => $admins,
        'members_count' => count($channelFound['members'] ?? [])
    ]);
}

function updateChannelSettings() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    $allowAllWrite = $_POST['allow_all_write'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан канал']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = false;
    
    foreach ($channels as &$channel) {
        if ($channel['id'] === $channelId) {
            // Проверяем, является ли пользователь администратором
            if (!in_array($userId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Только администраторы могут изменять настройки']);
                return;
            }
            
            // Обновляем настройки
            if (!isset($channel['settings'])) {
                $channel['settings'] = [];
            }
            
            $channel['settings']['allow_all_write'] = ($allowAllWrite === 'true');
            $channelFound = true;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    saveChannelsList($channels);
    
    echo json_encode(['success' => true]);
}

function addChannelAdmin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    $targetUserId = $_POST['target_user_id'] ?? '';
    
    if (empty($channelId) || empty($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны данные']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = false;
    
    foreach ($channels as &$channel) {
        if ($channel['id'] === $channelId) {
            // Проверяем, является ли пользователь администратором
            if (!in_array($userId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Только администраторы могут добавлять других администраторов']);
                return;
            }
            
            // Проверяем, что целевой пользователь является участником канала
            if (!in_array($targetUserId, $channel['members'])) {
                echo json_encode(['success' => false, 'error' => 'Пользователь не является участником канала']);
                return;
            }
            
            // Проверяем, что пользователь еще не администратор
            if (in_array($targetUserId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Пользователь уже является администратором']);
                return;
            }
            
            // Добавляем администратора
            if (!isset($channel['admins'])) {
                $channel['admins'] = [];
            }
            $channel['admins'][] = $targetUserId;
            $channelFound = true;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    saveChannelsList($channels);
    
    // Создаем уведомление для нового администратора
    $currentUser = getUserById($userId);
    $targetUser = getUserById($targetUserId);
    createNotification($targetUserId, 'Новые права в канале', "{$currentUser['name']} назначил(а) вас администратором канала", 'channel_admin');
    
    echo json_encode(['success' => true]);
}

function removeChannelAdmin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    $targetUserId = $_POST['target_user_id'] ?? '';
    
    if (empty($channelId) || empty($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'Не указаны данные']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = false;
    
    foreach ($channels as &$channel) {
        if ($channel['id'] === $channelId) {
            // Проверяем, является ли пользователь администратором
            if (!in_array($userId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Только администраторы могут удалять администраторов']);
                return;
            }
            
            // Нельзя удалить самого себя, если это последний администратор
            if ($targetUserId === $userId && count($channel['admins'] ?? []) <= 1) {
                echo json_encode(['success' => false, 'error' => 'Нельзя удалить последнего администратора канала']);
                return;
            }
            
            // Проверяем, что целевой пользователь является администратором
            if (!in_array($targetUserId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Пользователь не является администратором']);
                return;
            }
            
            // Удаляем администратора
            $channel['admins'] = array_diff($channel['admins'], [$targetUserId]);
            $channelFound = true;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    saveChannelsList($channels);
    
    // Создаем уведомление для бывшего администратора
    if ($targetUserId !== $userId) {
        $currentUser = getUserById($userId);
        $targetUser = getUserById($targetUserId);
        createNotification($targetUserId, 'Изменение прав в канале', "{$currentUser['name']} снял(а) вас с должности администратора канала", 'channel_admin');
    }
    
    echo json_encode(['success' => true]);
}

function getChannelMembers() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $channelId = $_POST['channel_id'] ?? '';
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан канал']);
        return;
    }
    
    $channels = getChannelsList();
    $channelFound = null;
    
    foreach ($channels as $channel) {
        if ($channel['id'] === $channelId) {
            // Проверяем, является ли пользователь администратором
            if (!in_array($userId, $channel['admins'] ?? [])) {
                echo json_encode(['success' => false, 'error' => 'Только администраторы могут просматривать список участников']);
                return;
            }
            $channelFound = $channel;
            break;
        }
    }
    
    if (!$channelFound) {
        echo json_encode(['success' => false, 'error' => 'Канал не найден']);
        return;
    }
    
    // Получаем информацию об участниках
    $members = [];
    foreach ($channelFound['members'] as $memberId) {
        $memberUser = getUserById($memberId);
        if ($memberUser) {
            $members[] = [
                'id' => $memberUser['id'],
                'name' => $memberUser['name'],
                'username' => $memberUser['username'],
                'avatar' => $memberUser['avatar'],
                'is_admin' => in_array($memberId, $channelFound['admins'] ?? [])
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'members' => $members
    ]);
}

function getChannelMessages($channelId) {
    $channelFile = MESSAGES_DIR . "/{$channelId}.json";
    if (!file_exists($channelFile)) {
        return [];
    }
    
    $messages = json_decode(file_get_contents($channelFile), true) ?: [];
    
    // Сортируем по времени
    usort($messages, function($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });
    
    return $messages;
}

function getGroupMessages($groupId) {
    $groupFile = MESSAGES_DIR . "/{$groupId}.json";
    if (!file_exists($groupFile)) {
        return [];
    }
    
    $messages = json_decode(file_get_contents($groupFile), true) ?: [];
    
    // Сортируем по времени
    usort($messages, function($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });
    
    return $messages;
}

function saveChannelMessage($message) {
    $channelFile = MESSAGES_DIR . "/{$message['chat_id']}.json";
    $messages = [];
    
    if (file_exists($channelFile)) {
        $messages = json_decode(file_get_contents($channelFile), true) ?: [];
    }
    
    $messages[] = $message;
    
    // Ограничиваем количество сообщений (последние 100)
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    file_put_contents($channelFile, json_encode($messages, JSON_PRETTY_PRINT));
}

function saveGroupMessage($message) {
    $groupFile = MESSAGES_DIR . "/{$message['chat_id']}.json";
    $messages = [];
    
    if (file_exists($groupFile)) {
        $messages = json_decode(file_get_contents($groupFile), true) ?: [];
    }
    
    $messages[] = $message;
    
    // Ограничиваем количество сообщений (последние 100)
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    file_put_contents($groupFile, json_encode($messages, JSON_PRETTY_PRINT));
}

// Вспомогательные функции
function getLastMessage($chatId) {
    if (str_starts_with($chatId, 'favorites_')) {
        $favoritesFile = MESSAGES_DIR . "/{$chatId}.json";
        if (!file_exists($favoritesFile)) {
            return null;
        }
        $messages = json_decode(file_get_contents($favoritesFile), true) ?: [];
        return !empty($messages) ? end($messages) : null;
    }

    if (str_starts_with($chatId, 'channel_')) {
        $messages = getChannelMessages($chatId);
        return !empty($messages) ? end($messages) : null;
    }
    
    if (str_starts_with($chatId, 'group_')) {
        $messages = getGroupMessages($chatId);
        return !empty($messages) ? end($messages) : null;
    }
    
    $messageDir = MESSAGES_DIR . "/{$chatId}";
    if (!file_exists($messageDir)) {
        return null;
    }
    
    $messageFiles = scandir($messageDir);
    $lastMessage = null;
    
    foreach ($messageFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $message = json_decode(file_get_contents($messageDir . "/{$file}"), true);
            if ($message && (!$lastMessage || $message['timestamp'] > $lastMessage['timestamp'])) {
                $lastMessage = $message;
            }
        }
    }
    
    return $lastMessage;
}

function getFavoritesMessages($userId) {
    $favoritesFile = MESSAGES_DIR . "/favorites_{$userId}.json";
    if (!file_exists($favoritesFile)) {
        return [];
    }
    
    $messages = json_decode(file_get_contents($favoritesFile), true) ?: [];
    
    // Сортируем по времени
    usort($messages, function($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });
    
    return $messages;
}

function saveFavoriteMessage($userId, $message) {
    $favoritesFile = MESSAGES_DIR . "/favorites_{$userId}.json";
    $messages = [];
    
    if (file_exists($favoritesFile)) {
        $messages = json_decode(file_get_contents($favoritesFile), true) ?: [];
    }
    
    $messages[] = $message;
    
    // Ограничиваем количество сообщений (последние 100)
    if (count($messages) > 100) {
        $messages = array_slice($messages, -100);
    }
    
    file_put_contents($favoritesFile, json_encode($messages, JSON_PRETTY_PRINT));
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        touchUserPresence($_SESSION['user_id'], false);
    }
    session_destroy();
    echo json_encode(['success' => true]);
}

// Остальные функции
function deleteChat() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $chatId = $_POST['chat_id'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (empty($chatId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан чат']);
        return;
    }
    
    $chatFile = CHATS_DIR . "/{$chatId}.json";
    if (file_exists($chatFile)) {
        $chat = json_decode(file_get_contents($chatFile), true);
        if ($chat && in_array($userId, $chat['participants'])) {
            unlink($chatFile);
            
            // Удаляем сообщения
            $messageDir = MESSAGES_DIR . "/{$chatId}";
            if (file_exists($messageDir)) {
                array_map('unlink', glob("$messageDir/*.*"));
                rmdir($messageDir);
            }
            
            echo json_encode(['success' => true]);
            return;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Чат не найден или нет доступа']);
}

function getUserProfile() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $targetUserId = $_POST['user_id'] ?? '';
    $user = getUserById($targetUserId);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    
    unset($user['password_hash']);
    
    echo json_encode(['success' => true, 'user' => $user]);
}

function updateProfile() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Имя не может быть пустым']);
        return;
    }
    
    $user['name'] = $name;
    $user['bio'] = $bio;
    
    // Обработка аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarFile = AVATARS_DIR . "/{$user['id']}.png";
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarFile);
        $user['avatar'] = "/data/avatars/{$user['id']}.png";
    }
    
    saveUser($user);
    
    echo json_encode([
        'success' => true,
        'name' => $user['name'],
        'bio' => $user['bio'],
        'new_avatar' => $user['avatar'] ?? null,
        'emoji_status' => $user['emoji_status'] ?? ''
    ]);
}

function setEmojiStatus() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $user = getUserById($_SESSION['user_id']);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
        return;
    }
    if (empty($user['is_premium'])) {
        echo json_encode(['success' => false, 'error' => 'Emoji-статус доступен только с Pulse Premium']);
        return;
    }
    $emoji = trim($_POST['emoji_status'] ?? '');
    if ($emoji !== '') {
        // Разрешаем только ровно один emoji (без букв/слов)
        $isSingleEmoji = preg_match('/^\p{Extended_Pictographic}(?:\x{FE0F}|\x{200D}\p{Extended_Pictographic})*$/u', $emoji) === 1;
        if (!$isSingleEmoji) {
            echo json_encode(['success' => false, 'error' => 'Разрешен только один emoji без текста']);
            return;
        }
    }
    $user['emoji_status'] = $emoji;
    saveUser($user);
    echo json_encode(['success' => true, 'emoji_status' => $emoji]);
}

function setMessageReaction() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    $chatId = trim($_POST['chat_id'] ?? '');
    $messageId = trim($_POST['message_id'] ?? '');
    $emoji = trim($_POST['emoji'] ?? '');
    $userId = $_SESSION['user_id'];
    if ($chatId === '' || $messageId === '' || $emoji === '') {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
        return;
    }
    $isSingleEmoji = preg_match('/^\p{Extended_Pictographic}(?:\x{FE0F}|\x{200D}\p{Extended_Pictographic})*$/u', $emoji) === 1;
    if (!$isSingleEmoji) {
        echo json_encode(['success' => false, 'error' => 'Некорректная реакция']);
        return;
    }

    $allowed = false;
    if (str_starts_with($chatId, 'favorites_')) {
        $allowed = $chatId === ('favorites_' . $userId);
    } elseif (str_starts_with($chatId, 'channel_')) {
        foreach (getChannelsList() as $channel) {
            if ($channel['id'] === $chatId && in_array($userId, $channel['members'] ?? [], true)) {
                $allowed = true;
                break;
            }
        }
    } elseif (str_starts_with($chatId, 'group_')) {
        foreach (getGroupsList() as $group) {
            if ($group['id'] === $chatId && in_array($userId, $group['members'] ?? [], true)) {
                $allowed = true;
                break;
            }
        }
    } else {
        $chatFile = CHATS_DIR . "/{$chatId}.json";
        if (file_exists($chatFile)) {
            $chat = json_decode(file_get_contents($chatFile), true);
            $allowed = $chat && in_array($userId, $chat['participants'] ?? [], true);
        }
    }
    if (!$allowed) {
        echo json_encode(['success' => false, 'error' => 'Нет доступа к чату']);
        return;
    }

    $updated = false;
    $updatedMessage = null;
    $applyReaction = function (&$message) use ($userId, $emoji, &$updated, &$updatedMessage) {
        $reactions = is_array($message['reactions'] ?? null) ? $message['reactions'] : [];
        // У пользователя только одна реакция на сообщение: снимаем предыдущую
        foreach ($reactions as $key => $userList) {
            if (!is_array($userList)) continue;
            $reactions[$key] = array_values(array_filter($userList, fn($uid) => $uid !== $userId));
            if (empty($reactions[$key])) unset($reactions[$key]);
        }
        // Тоггл: если была эта же реакция - снимаем полностью, иначе ставим
        $alreadyHad = false;
        $current = $message['reactions'][$emoji] ?? [];
        if (is_array($current) && in_array($userId, $current, true)) {
            $alreadyHad = true;
        }
        if (!$alreadyHad) {
            $reactions[$emoji] = $reactions[$emoji] ?? [];
            if (!in_array($userId, $reactions[$emoji], true)) {
                $reactions[$emoji][] = $userId;
            }
        }
        $message['reactions'] = $reactions;
        $updated = true;
        $updatedMessage = $message;
    };

    if (str_starts_with($chatId, 'favorites_')) {
        $file = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        foreach ($messages as &$message) {
            if (($message['id'] ?? '') === $messageId) {
                $applyReaction($message);
                break;
            }
        }
        unset($message);
        if ($updated) file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
    } elseif (str_starts_with($chatId, 'channel_') || str_starts_with($chatId, 'group_')) {
        $file = MESSAGES_DIR . "/{$chatId}.json";
        $messages = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        foreach ($messages as &$message) {
            if (($message['id'] ?? '') === $messageId) {
                $applyReaction($message);
                break;
            }
        }
        unset($message);
        if ($updated) file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
    } else {
        $file = MESSAGES_DIR . "/{$chatId}/{$messageId}.json";
        if (file_exists($file)) {
            $message = json_decode(file_get_contents($file), true);
            if ($message) {
                $applyReaction($message);
                if ($updated) file_put_contents($file, json_encode($message, JSON_PRETTY_PRINT));
            }
        }
    }

    if (!$updated) {
        echo json_encode(['success' => false, 'error' => 'Сообщение не найдено']);
        return;
    }

    $recipientIds = getChatRecipientIds($chatId, $userId);
    publishWebSocketEvent([
        'type' => 'message_reaction',
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'recipient_ids' => $recipientIds,
        'timestamp' => time()
    ]);
    echo json_encode(['success' => true, 'reactions' => $updatedMessage['reactions'] ?? []]);
}

function sellGift() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        return;
    }
    
    $giftId = $_POST['gift_id'] ?? '';
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
    
    if (empty($giftId) || !isset($user['gifts'][$giftId])) {
        echo json_encode(['success' => false, 'error' => 'Подарок не найден']);
        return;
    }
    
    // Определяем стоимость возврата (50% от исходной цены)
    $giftEmoji = $user['gifts'][$giftId];
    $giftRefunds = [
        '🐻' => 5, '❤️' => 7, '🍾' => 10, '🎰' => 4000,
        '😻' => 25, '🍋' => 100, '🦆' => 10, '🍔' => 250,
        '🎅' => 50, '🎃' =>150
    ];
    
    $refund = $giftRefunds[$giftEmoji] ?? 5;
    
    // Удаляем подарок и начисляем звезды
    unset($user['gifts'][$giftId]);
    $user['stars'] += $refund;
    saveUser($user);
    
    echo json_encode([
        'success' => true,
        'new_stars' => $user['stars'],
        'gift_emoji' => $giftEmoji
    ]);
}

?>