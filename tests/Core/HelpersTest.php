<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;

/**
 * HelpersTest
 *
 * Covers the pure helpers added for HTML-first apps: e(), asset_url(),
 * absolute_url(). partial()/render() need a booted App, so they are exercised
 * by the application's own tests rather than here.
 */
class HelpersTest extends TestCase {

    private array $server;

    protected function setUp(): void {
        $this->server = $_SERVER;
        putenv('APP_URL');
        unset($_ENV['APP_URL'], $_SERVER['HTTPS']);
    }

    protected function tearDown(): void {
        $_SERVER = $this->server;
        putenv('APP_URL');
        unset($_ENV['APP_URL']);
    }

    public function testEscapesHtmlInTextAndAttributes(): void {
        $this->assertSame('&lt;b&gt;R&amp;D&lt;/b&gt;', e('<b>R&D</b>'));
        $this->assertSame('say &quot;hi&quot; &amp; &#039;bye&#039;', e('say "hi" & \'bye\''));
    }

    public function testEscapeAcceptsNullAndNumbers(): void {
        $this->assertSame('', e(null));
        $this->assertSame('42', e(42));
    }

    public function testAssetUrlIsRootRelative(): void {
        $this->assertSame('/does/not/exist.css', asset_url('does/not/exist.css'));
        $this->assertSame('/does/not/exist.css', asset_url('/does/not/exist.css'));
    }

    public function testAssetUrlFingerprintsExistingFiles(): void {
        $dir  = APP_ROOT . '/public/tmp-asset-test';
        $file = $dir . '/a.css';
        @mkdir($dir, 0777, true);
        file_put_contents($file, 'a{}');
        touch($file, 1700000000);

        try {
            $this->assertSame('/tmp-asset-test/a.css?v=1700000000', asset_url('tmp-asset-test/a.css'));
        } finally {
            @unlink($file);
            @rmdir($dir);
            @rmdir(APP_ROOT . '/public');
        }
    }

    public function testAssetEchoesTheSameUrl(): void {
        ob_start();
        asset('x/y.js');
        $this->assertSame(asset_url('x/y.js'), ob_get_clean());
    }

    public function testAbsoluteUrlPrefersAppUrlFromEnv(): void {
        putenv('APP_URL=https://erp.example.com/');

        $this->assertSame('https://erp.example.com/imgs/card.jpg', absolute_url('/imgs/card.jpg'));
        $this->assertSame('https://erp.example.com/', absolute_url());
    }

    public function testAbsoluteUrlFallsBackToHost(): void {
        $_SERVER['HTTP_HOST'] = 'localhost:8000';

        $this->assertSame('http://localhost:8000/apps/books', absolute_url('apps/books'));

        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('https://localhost:8000/', absolute_url('/'));
    }

    public function testAbsoluteUrlRefusesAHostileHostHeader(): void {
        // Host is client-controlled; never reflect it into og:image / canonical.
        $_SERVER['HTTP_HOST'] = "evil.com\r\nX-Injected: 1";

        $this->assertSame('http://localhost/', absolute_url('/'));
    }
}
