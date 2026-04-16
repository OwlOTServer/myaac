<?php
/**
 * MyAAC Docker setup script
 * Runs on container start to prepare the database
 */

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "[myaac] ERROR: Cannot connect to database: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

function hasColumn(PDO $pdo, string $table, string $column, string $db): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$db, $table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function hasTable(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}

function addColumn(PDO $pdo, string $table, string $column, string $type, string $db): void {
    if (!hasColumn($pdo, $table, $column, $db)) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $type");
        echo "[myaac] Added column $table.$column" . PHP_EOL;
    }
}

// Import myaac schema if tables don't exist
$result = $pdo->query("SHOW TABLES LIKE 'myaac_config'");
if ($result->rowCount() === 0) {
    echo '[myaac] Importing schema...' . PHP_EOL;
    $pdo->exec(file_get_contents('/var/www/html/install/includes/schema.sql'));
    $pdo->prepare("INSERT IGNORE INTO myaac_config (name, value) VALUES ('database_version', 46)")->execute();
    echo '[myaac] Schema imported (version 46).' . PHP_EOL;
} else {
    echo '[myaac] Schema already exists.' . PHP_EOL;
}

// Add required columns to accounts table
addColumn($pdo, 'accounts', 'web_lastlogin',  "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'web_flags',      "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'email_verified', "TINYINT(1) NOT NULL DEFAULT 0",  $name);
addColumn($pdo, 'accounts', 'email_new',      "VARCHAR(255) NOT NULL DEFAULT ''", $name);
addColumn($pdo, 'accounts', 'email_new_time', "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'email_code',     "VARCHAR(255) NOT NULL DEFAULT ''", $name);
addColumn($pdo, 'accounts', 'email_next',     "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'premium_points', "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'key',            "VARCHAR(64) NOT NULL DEFAULT ''", $name);
addColumn($pdo, 'accounts', 'created',        "INT(11) NOT NULL DEFAULT 0",     $name);
addColumn($pdo, 'accounts', 'rlname',         "VARCHAR(255) NOT NULL DEFAULT ''", $name);
addColumn($pdo, 'accounts', 'location',       "VARCHAR(255) NOT NULL DEFAULT ''", $name);
addColumn($pdo, 'accounts', 'country',        "VARCHAR(3) NOT NULL DEFAULT ''",  $name);

// Add required columns to players table
if (hasTable($pdo, 'players')) {
    addColumn($pdo, 'players', 'created', "INT(11) NOT NULL DEFAULT 0",       $name);
    addColumn($pdo, 'players', 'hide',    "TINYINT(1) NOT NULL DEFAULT 0",    $name);
    addColumn($pdo, 'players', 'comment', "VARCHAR(5000) NOT NULL DEFAULT ''", $name);
}

// Add required columns to guilds table
if (hasTable($pdo, 'guilds')) {
    addColumn($pdo, 'guilds', 'motd',        "VARCHAR(255) NOT NULL DEFAULT ''",   $name);
    addColumn($pdo, 'guilds', 'description', "VARCHAR(5000) NOT NULL DEFAULT ''",  $name);
    addColumn($pdo, 'guilds', 'logo_name',   "VARCHAR(255) NOT NULL DEFAULT 'default.gif'", $name);
}

// Set template to tibiacom in myaac_settings (name='core', key='template')
$pdo->exec("DELETE FROM myaac_settings WHERE `name` = '' AND `key` = 'core.template'"); // remove wrong old entry
$check = $pdo->query("SELECT COUNT(*) FROM myaac_settings WHERE `name` = 'core' AND `key` = 'template'");
if ((int)$check->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO myaac_settings (`name`, `key`, `value`) VALUES ('core', 'template', 'tibiacom')");
    echo '[myaac] Template set to tibiacom.' . PHP_EOL;
}

// Set web_flags = 3 (super admin) on the god account
$stmt = $pdo->query("SELECT id, name, web_flags FROM accounts WHERE name = 'god' LIMIT 1");
$god = $stmt->fetch(PDO::FETCH_ASSOC);
if ($god && (int)$god['web_flags'] === 0) {
    $pdo->exec("UPDATE accounts SET web_flags = 3 WHERE name = 'god'");
    echo '[myaac] Admin privileges set on account: god' . PHP_EOL;
}

// Populate tibiacom menu entries if empty
$menuCount = (int)$pdo->query("SELECT COUNT(*) FROM myaac_menu WHERE template = 'tibiacom'")->fetchColumn();
if ($menuCount === 0) {
    $menus = [
        // MENU_CATEGORY_NEWS = 1
        1 => ['Latest News' => 'news', 'News Archive' => 'news/archive', 'Changelog' => 'change-log'],
        // MENU_CATEGORY_ACCOUNT = 2
        2 => ['Account Management' => 'account/manage', 'Create Account' => 'account/create', 'Lost Account?' => 'account/lost', 'Server Rules' => 'rules', 'Downloads' => 'downloads'],
        // MENU_CATEGORY_COMMUNITY = 3
        3 => ['Characters' => 'characters', 'Who is Online?' => 'online', 'Highscores' => 'highscores', 'Last Kills' => 'last-kills', 'Houses' => 'houses', 'Guilds' => 'guilds', 'Polls' => 'polls', 'Bans' => 'bans', 'Support List' => 'team'],
        // MENU_CATEGORY_FORUM = 4
        4 => ['Forum' => 'forum'],
        // MENU_CATEGORY_LIBRARY = 5
        5 => ['Monsters' => 'monsters', 'Spells' => 'spells', 'Commands' => 'commands', 'Exp Stages' => 'exp-stages', 'Gallery' => 'gallery', 'Server Info' => 'ots-info', 'Exp Table' => 'exp-table', 'FAQ' => 'faq'],
        // MENU_CATEGORY_SHOP = 6
        6 => ['Buy Points' => 'points', 'Shop Offer' => 'gifts', 'Shop History' => 'gifts/history'],
    ];

    $stmt = $pdo->prepare("INSERT INTO myaac_menu (name, link, template, category, ordering, enabled) VALUES (?, ?, 'tibiacom', ?, ?, 1)");
    foreach ($menus as $category => $items) {
        $order = 1;
        foreach ($items as $name => $link) {
            $stmt->execute([$name, $link, $category, $order++]);
        }
    }
    echo '[myaac] Menu entries created for tibiacom.' . PHP_EOL;
}

echo '[myaac] Database setup complete.' . PHP_EOL;
