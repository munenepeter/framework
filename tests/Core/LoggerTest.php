<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Modules\Logger;

/**
 * LoggerTest
 *
 * Covers writing the buffered log lines to disk, in particular the first write
 * on a fresh checkout.
 */
class LoggerTest extends TestCase {

    private string $root;
    private string $origFile;

    protected function setUp(): void {
        $this->root = sys_get_temp_dir() . '/tabel-logger-' . uniqid();
        $prop = new \ReflectionProperty(Logger::class, 'logFile');
        $this->origFile = $prop->getValue();
    }

    protected function tearDown(): void {
        (new \ReflectionProperty(Logger::class, 'logFile'))->setValue(null, $this->origFile);
        (new \ReflectionProperty(Logger::class, 'logBuffer'))->setValue(null, []);
        foreach (glob($this->root . '/*/*') ?: [] as $f) { @unlink($f); }
        foreach (glob($this->root . '/*') ?: [] as $d) { @rmdir($d); }
        @rmdir($this->root);
    }

    private function writeTo(string $file): void {
        (new \ReflectionProperty(Logger::class, 'logFile'))->setValue(null, $file);
        (new \ReflectionProperty(Logger::class, 'logBuffer'))->setValue(null, ['line one']);
        Logger::flush();
    }

    public function testFirstWriteWhenTheFolderExistsButTheFileDoesNot(): void {
        // Regression: this combination (a fresh checkout) raised a mkdir() warning.
        mkdir($this->root . '/logs', 0755, true);
        $file = $this->root . '/logs/tabel.log';

        set_error_handler(function (int $no, string $msg) { throw new \ErrorException($msg); });
        try {
            $this->writeTo($file);
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('line one', file_get_contents($file));
    }

    public function testCreatesTheFolderWhenItIsMissing(): void {
        $file = $this->root . '/logs/tabel.log';

        $this->writeTo($file);

        $this->assertFileExists($file);
    }

    public function testAppendsToAnExistingLog(): void {
        mkdir($this->root . '/logs', 0755, true);
        $file = $this->root . '/logs/tabel.log';
        file_put_contents($file, "earlier\n");

        $this->writeTo($file);

        $this->assertSame("earlier\nline one\n", file_get_contents($file));
    }
}
