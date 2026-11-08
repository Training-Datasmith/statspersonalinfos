<?php

if (!defined('PHP_BINARY')) {
    define('PHP_BINARY', PHP_BINDIR . '/php');
}

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_PS_USE_SQL_SLAVE_')) {
    define('_PS_USE_SQL_SLAVE_', false);
}

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$testsDir = dirname(__FILE__);
$repoRoot = dirname($testsDir);

require_once $testsDir . '/Support/PrestaShopHarness.php';
require_once $testsDir . '/Support/Fixture.php';
require_once $testsDir . '/Support/StatsTestCase.php';

function stats_wait_for_database()
{
    $deadline = microtime(true) + 20.0;
    $lastError = 'unknown';

    while (microtime(true) < $deadline) {
        try {
            Db::resetConnection();
            Db::getInstance();
            return;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            Db::resetConnection();
            usleep(100000);
        }
    }

    fwrite(STDERR, "Database not ready after 20s: {$lastError}\n");
    exit(1);
}

stats_wait_for_database();
Fixture::ensureSchema();

$db = Db::getInstance();
$db->executeS("SET time_zone = '+00:00'");
$db->executeS("SET TIMESTAMP = UNIX_TIMESTAMP('2016-06-15 12:00:00')");

$curdate = $db->getValue('SELECT CURDATE()');
if ($curdate !== '2016-06-15') {
    fwrite(STDERR, "Frozen CURDATE() probe failed: expected 2016-06-15, got {$curdate}\n");
    exit(1);
}

$age = $db->getValue(
    "SELECT (YEAR(CURDATE()) - YEAR('1998-06-16')) - (RIGHT(CURDATE(), 5) < RIGHT('1998-06-16', 5))"
);
if ((string) $age !== '17') {
    fwrite(STDERR, "Age expression probe failed: expected 17, got {$age}\n");
    exit(1);
}

require_once $repoRoot . '/statspersonalinfos.php';
require_once $repoRoot . '/upgrade/upgrade-2.0.4.php';
require_once $testsDir . '/Support/StatsPersonalInfosProbe.php';
