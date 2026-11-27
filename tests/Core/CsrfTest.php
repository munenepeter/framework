<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Modules\Csrf;

class CsrfTest extends TestCase {

    protected function setUp(): void {
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_AUTHORIZATION']);
        Csrf::$override = null;
    }

    protected function tearDown(): void {
        Csrf::$override = null;
        $_POST = [];
    }

    public function testTokenIsStablePerSessionAndRandom(): void {
        $a = Csrf::token();
        $this->assertSame(64, strlen($a));
        $this->assertSame($a, Csrf::token());

        $_SESSION = [];
        $this->assertNotSame($a, Csrf::token());
    }

    public function testFieldContainsTheEscapedToken(): void {
        $html = Csrf::field();
        $this->assertStringContainsString('name="_csrf"', $html);
        $this->assertStringContainsString(Csrf::token(), $html);
    }

    public function testDisabledByDefaultWhenNoConfig(): void {
        $this->assertFalse(Csrf::enabled());
        $this->assertFalse(Csrf::shouldVerify('POST'));
    }

    public function testOnlyStateChangingMethodsAreChecked(): void {
        Csrf::$override = true;
        $this->assertTrue(Csrf::shouldVerify('POST'));
        $this->assertTrue(Csrf::shouldVerify('delete'));
        $this->assertFalse(Csrf::shouldVerify('GET'));
    }

    public function testBearerRequestsAreExempt(): void {
        Csrf::$override = true;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc';
        $this->assertFalse(Csrf::shouldVerify('POST'));
    }

    public function testVerifyAcceptsFieldOrHeaderAndRejectsOthers(): void {
        $token = Csrf::token();

        $_POST['_csrf'] = $token;
        Csrf::verify();
        $this->addToAssertionCount(1);

        $_POST = [];
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        Csrf::verify();
        $this->addToAssertionCount(1);

        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(419);
        Csrf::verify();
    }

    public function testMissingTokenIsRejected(): void {
        Csrf::token();
        $this->expectExceptionCode(419);
        Csrf::verify();
    }
}
