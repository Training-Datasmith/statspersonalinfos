<?php

$expectedTests = 38;
$expectedFiles = array(
    'FrontControllerGuardTest.php',
    'GetDataAgeTest.php',
    'GetDataCountryTest.php',
    'GetDataCurrencyTest.php',
    'GetDataGenderTest.php',
    'GetDataLanguageTest.php',
    'GetDataOptionTest.php',
    'HookDisplayAdminStatsModulesTest.php',
    'InstallAndUpgradeTest.php',
    'ModuleMetadataTest.php',
);

$diagnosticPattern = '/(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:)/';

$testsDir = dirname(__FILE__);
$repoRoot = dirname($testsDir);

function stats_fail($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function stats_php_binary()
{
    $env = getenv('PHP_BINARY');
    if ($env && $env !== '' && is_executable($env)) {
        return $env;
    }
    if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_executable(PHP_BINARY)) {
        return PHP_BINARY;
    }
    if (defined('PHP_BINDIR')) {
        $candidate = PHP_BINDIR . '/php';
        if (is_executable($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function stats_lint_tree($testsDir, $repoRoot)
{
    $php = stats_php_binary();
    $paths = array(
        $repoRoot . '/statspersonalinfos.php',
        $repoRoot . '/upgrade/upgrade-2.0.4.php',
        $repoRoot . '/index.php',
        $testsDir . '/index.php',
        $repoRoot . '/translations/index.php',
        $repoRoot . '/upgrade/index.php',
        $testsDir . '/bootstrap.php',
        $testsDir . '/run-suite.php',
        $testsDir . '/Support/PrestaShopHarness.php',
        $testsDir . '/Support/Fixture.php',
        $testsDir . '/Support/StatsTestCase.php',
        $testsDir . '/Support/StatsPersonalInfosProbe.php',
    );
    foreach (glob($testsDir . '/*Test.php') as $testFile) {
        $paths[] = $testFile;
    }
    foreach ($paths as $path) {
        $cmd = escapeshellarg($php) . ' -l ' . escapeshellarg($path) . ' 2>&1';
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            stats_fail('Lint failed for ' . $path . ': ' . implode("\n", $output));
        }
    }
}

function stats_verify_file_list($testsDir, $expectedFiles)
{
    $found = array();
    foreach (glob($testsDir . '/*Test.php') as $path) {
        $found[] = basename($path);
    }
    sort($found);
    $expected = $expectedFiles;
    sort($expected);
    if ($found !== $expected) {
        stats_fail('Test file drift. expected=' . implode(',', $expected) . ' found=' . implode(',', $found));
    }
}

function stats_phar_path()
{
    $path = getenv('PHPUNIT_PHAR');
    if (!$path) {
        $path = getenv('HOME') ? getenv('HOME') . '/.cache/phpunit-4.8.36.phar' : '/tmp/phpunit-4.8.36.phar';
    }

    return $path;
}

function stats_assert_no_diagnostics($output)
{
    global $diagnosticPattern;
    if (preg_match($diagnosticPattern, $output)) {
        stats_fail("PHP diagnostic detected in output:\n" . $output);
    }
}

function stats_run_phpunit_shuffle($testsDir, $seed, $expectedTests)
{
    global $diagnosticPattern;
    $php = stats_php_binary();
    $files = glob($testsDir . '/*Test.php');
    $baseNames = array();
    foreach ($files as $file) {
        $baseNames[] = basename($file);
    }
    sort($baseNames);

    $shuffled = $baseNames;
    srand($seed);
    shuffle($shuffled);

    $tmpDir = sys_get_temp_dir() . '/stats-phpunit-' . getmypid() . '-' . $seed;
    if (!mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
        stats_fail('Cannot create temp dir ' . $tmpDir);
    }

    $cases = '';
    foreach ($shuffled as $name) {
        $cases .= '      <file>' . htmlspecialchars($testsDir . '/' . $name, ENT_QUOTES, 'UTF-8') . "</file>\n";
    }

    $xmlPath = $tmpDir . '/phpunit.xml';
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="' . htmlspecialchars($testsDir . '/bootstrap.php', ENT_QUOTES, 'UTF-8') . '"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         backupGlobals="false"
         backupStaticAttributes="false">
  <testsuites>
    <testsuite name="module-tests">
' . $cases . '    </testsuite>
  </testsuites>
</phpunit>
';
    file_put_contents($xmlPath, $xml);

    $phar = stats_phar_path();
    if (!file_exists($phar)) {
        stats_fail('PHPUnit PHAR not found at ' . $phar);
    }

    $cmd = escapeshellarg($php)
        . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 '
        . escapeshellarg($phar) . ' -c ' . escapeshellarg($xmlPath)
        . ' --log-junit ' . escapeshellarg($tmpDir . '/junit.xml') . ' 2>&1';
    $output = array();
    exec($cmd, $output, $exitCode);
    $joined = implode("\n", $output);
    stats_assert_no_diagnostics($joined);

    if ($exitCode !== 0) {
        stats_fail("PHPUnit failed (seed {$seed}):\n" . $joined);
    }

    $summary = null;
    foreach ($output as $line) {
        if (strpos($line, 'OK (') === 0) {
            $summary = $line;
            break;
        }
    }
    if ($summary === null) {
        stats_fail('PHPUnit summary missing for seed ' . $seed . ":\n" . $joined);
    }
    if (!preg_match('/^OK \(' . $expectedTests . ' tests, ([0-9]+) assertions\)$/', $summary, $m)) {
        stats_fail('Unexpected summary for seed ' . $seed . ': ' . $summary);
    }
  if ((int) $m[1] < 1) {
        stats_fail('Assertion count must be >= 1 for seed ' . $seed);
    }

    echo $summary . "\n";
}

stats_lint_tree($testsDir, $repoRoot);
stats_verify_file_list($testsDir, $expectedFiles);
stats_run_phpunit_shuffle($testsDir, 20261108, $expectedTests);
stats_run_phpunit_shuffle($testsDir, 20261109, $expectedTests);

exit(0);
