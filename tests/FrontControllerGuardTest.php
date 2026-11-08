<?php

class FrontControllerGuardTest extends StatsTestCase
{
    private $diagnosticPattern = '/(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:)/';

    private function phpBinary()
    {
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

    private function runGuardedInclude($relativePath, $definePsVersion)
    {
        $repoRoot = dirname(dirname(__FILE__));
        $target = $repoRoot . '/' . $relativePath;
        $sentinel = tempnam(sys_get_temp_dir(), 'guard-sentinel-');
        $wrapper = tempnam(sys_get_temp_dir(), 'guard-wrapper-');
        $this->assertNotSame(false, $sentinel);
        $this->assertNotSame(false, $wrapper);

        $defineLine = $definePsVersion
            ? "if (!defined('_PS_VERSION_')) { define('_PS_VERSION_', '1.7.8.0'); }\n"
            : '';

        $wrapperCode = '<?php
$GLOBALS[\'guardSentinel\'] = ' . var_export($sentinel, true) . ';
error_reporting(-1);
ini_set(\'display_errors\', \'1\');
ini_set(\'display_startup_errors\', \'1\');
ini_set(\'log_errors\', \'0\');
register_shutdown_function(function () {
    file_put_contents($GLOBALS[\'guardSentinel\'], "shutdown\n", FILE_APPEND);
});
' . $defineLine . 'include ' . var_export($target, true) . ';
file_put_contents($GLOBALS[\'guardSentinel\'], "fell-through\n", FILE_APPEND);
';

        file_put_contents($wrapper, $wrapperCode);
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $cmd = escapeshellarg($this->phpBinary())
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($wrapper);
        $process = proc_open($cmd, $descriptorSpec, $pipes, $repoRoot);
        $this->assertInternalType('resource', $process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        $sentinelContents = file_get_contents($sentinel);
        @unlink($wrapper);
        @unlink($sentinel);

        return array(
            'exit' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'sentinel' => $sentinelContents,
        );
    }

    /**
     * @dataProvider directoryGuardProvider
     */
    public function testDirectoryGuardExits($relativePath)
    {
        $result = $this->runGuardedInclude($relativePath, true);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertNotRegExp($this->diagnosticPattern, $result['stderr']);
        $this->assertContains('shutdown', $result['sentinel']);
        $this->assertNotContains('fell-through', $result['sentinel']);
    }

    public function directoryGuardProvider()
    {
        return array(
            array('index.php'),
            array('tests/index.php'),
            array('translations/index.php'),
            array('upgrade/index.php'),
        );
    }

    public function testModuleFileExitsWhenPsVersionIsUndefined()
    {
        $result = $this->runGuardedInclude('statspersonalinfos.php', false);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertNotRegExp($this->diagnosticPattern, $result['stderr']);
        $this->assertContains('shutdown', $result['sentinel']);
        $this->assertNotContains('fell-through', $result['sentinel']);
    }
}
