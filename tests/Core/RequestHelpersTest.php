<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Core\Request;

/**
 * RequestHelpersTest
 *
 * Covers the htmx helpers and the same-origin guard. They only read $_SERVER,
 * so each test sets what it needs and setUp()/tearDown() restore it.
 */
class RequestHelpersTest extends TestCase {

    private array $server;

    protected function setUp(): void {
        $this->server = $_SERVER;
        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'HTTP_')) {
                unset($_SERVER[$key]);
            }
        }
    }

    protected function tearDown(): void {
        $_SERVER = $this->server;
        $_GET = [];
    }

    public function testIsHtmxNeedsTheExactHeaderValue(): void {
        $this->assertFalse(Request::isHtmx());

        $_SERVER['HTTP_HX_REQUEST'] = 'true';
        $this->assertTrue(Request::isHtmx());

        $_SERVER['HTTP_HX_REQUEST'] = 'false';
        $this->assertFalse(Request::isHtmx());
    }

    public function testIsBoosted(): void {
        $this->assertFalse(Request::isBoosted());

        $_SERVER['HTTP_HX_BOOSTED'] = 'true';
        $this->assertTrue(Request::isBoosted());
    }

    public function testHtmxTarget(): void {
        $this->assertNull(Request::htmxTarget());

        $_SERVER['HTTP_HX_TARGET'] = '';
        $this->assertNull(Request::htmxTarget());

        $_SERVER['HTTP_HX_TARGET'] = 'windows';
        $this->assertSame('windows', Request::htmxTarget());
    }

    public function testQueryReturnsRawUnescapedValues(): void {
        $_GET['q'] = 'R&D <b>';

        $this->assertSame('R&D <b>', (new Request)->query('q'));
        $this->assertSame('fallback', (new Request)->query('missing', 'fallback'));
    }

    public function testSameOriginTrustsSecFetchSite(): void {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
        $this->assertTrue(Request::isSameOrigin());

        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'none'; // typed URL / bookmark
        $this->assertTrue(Request::isSameOrigin());

        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
        $this->assertFalse(Request::isSameOrigin());

        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-site'; // sibling subdomain
        $this->assertFalse(Request::isSameOrigin());
    }

    public function testSameOriginFallsBackToOriginVersusHost(): void {
        $_SERVER['HTTP_HOST'] = 'erp.example.com';

        $_SERVER['HTTP_ORIGIN'] = 'https://erp.example.com';
        $this->assertTrue(Request::isSameOrigin());

        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example.net';
        $this->assertFalse(Request::isSameOrigin());

        $_SERVER['HTTP_ORIGIN'] = 'null';
        $this->assertFalse(Request::isSameOrigin());
    }

    public function testSameOriginComparesThePortToo(): void {
        $_SERVER['HTTP_HOST'] = 'localhost:8000';

        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000';
        $this->assertTrue(Request::isSameOrigin());

        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:9999';
        $this->assertFalse(Request::isSameOrigin());
    }

    public function testSameOriginAllowsClientsThatSendNeitherHeader(): void {
        $this->assertTrue(Request::isSameOrigin());
    }
}
