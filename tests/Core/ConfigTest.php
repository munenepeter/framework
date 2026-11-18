<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Core\Config;

/**
 * ConfigTest
 *
 * Covers .env parsing only (quotes, comments, ${interpolation}). load() also
 * writes the file cache, so it is deliberately not exercised here.
 */
class ConfigTest extends TestCase {

    private string $file;

    protected function setUp(): void {
        $this->file = tempnam(sys_get_temp_dir(), 'tabel-env-');
    }

    protected function tearDown(): void {
        @unlink($this->file);
    }

    private function parse(string $contents): array {
        file_put_contents($this->file, $contents);

        $prop = new \ReflectionProperty(Config::class, 'env_file');
        $prop->setValue(null, $this->file);

        $method = new \ReflectionMethod(Config::class, 'parseFile');
        return $method->invoke(null);
    }

    public function testParsesSectionAndKey(): void {
        $config = $this->parse("APP_ENV=development\nDB_HOST=127.0.0.1\n");

        $this->assertSame('development', $config['app']['env']);
        $this->assertSame('127.0.0.1', $config['db']['host']);
    }

    public function testStripsSurroundingQuotes(): void {
        // Regression: the quotes used to stay in the value.
        $config = $this->parse("APP_NAME=\"Tabel\"\nAPP_TIMEZONE='Africa/Nairobi'\n");

        $this->assertSame('Tabel', $config['app']['name']);
        $this->assertSame('Africa/Nairobi', $config['app']['timezone']);
    }

    public function testKeepsQuotesThatDoNotMatch(): void {
        $config = $this->parse("APP_NOTE=\"unbalanced\n");

        $this->assertSame('"unbalanced', $config['app']['note']);
    }

    public function testValuesMayContainEqualsSigns(): void {
        $config = $this->parse("DB_PASSWORD=ab=cd==\n");

        $this->assertSame('ab=cd==', $config['db']['password']);
    }

    public function testCommentsAndBlankLinesAreIgnored(): void {
        // Regression: "# DB_HOST=x" became the key "# DB" and leaked into config.
        $config = $this->parse("# DB_HOST=commented\n\nDB_HOST=real\n   # APP_ENV=nope\n");

        $this->assertSame('real', $config['db']['host']);
        $this->assertArrayNotHasKey('# db', $config);
        $this->assertArrayNotHasKey('app', $config);
    }

    public function testInterpolatesEarlierValues(): void {
        $config = $this->parse("APP_NAME=Tabel\nMAIL_FROM=\"\${APP_NAME} <no-reply@example.com>\"\n");

        $this->assertSame('Tabel <no-reply@example.com>', $config['mail']['from']);
    }
}
