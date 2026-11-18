<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Core\Router;

/**
 * RouterTest
 *
 * Covers route registration, {placeholder} extraction and group prefixes.
 * The 404 path calls abort(), which renders a view, so it is not covered here.
 */
class RouterTest extends TestCase {

    protected function setUp(): void {
        Router::$routes = ['GET' => [], 'POST' => []];
    }

    private function dispatch(string $uri, string $method = 'GET'): mixed {
        return (new Router)->direct($uri, $method);
    }

    public function testStaticRouteMatches(): void {
        Router::get('/about', fn() => 'about');

        $this->assertSame('about', $this->dispatch('/about'));
    }

    public function testRootRouteMatchesEmptyAndSlash(): void {
        Router::get('/', fn() => 'home');

        $this->assertSame('home', $this->dispatch('/'));
        $this->assertSame('home', $this->dispatch(''));
    }

    public function testPlaceholderIsPassedAsPositionalArgument(): void {
        Router::get('/users/{id}', fn(string $id) => "user:$id");

        $this->assertSame('user:42', $this->dispatch('/users/42'));
    }

    public function testPlaceholderDoesNotSpanSlashes(): void {
        // Regression: {param} used to compile to (.+), so the first
        // registered route swallowed every deeper URL.
        Router::get('/apps/{app}', fn(string $app) => "app:$app");
        Router::get('/apps/{app}/{section}', fn(string $app, string $section) => "section:$app/$section");

        $this->assertSame('app:books', $this->dispatch('/apps/books'));
        $this->assertSame('section:books/invoices', $this->dispatch('/apps/books/invoices'));
    }

    public function testMethodsAreSeparate(): void {
        Router::get('/save', fn() => 'get');
        Router::post('/save', fn() => 'post');

        $this->assertSame('get', $this->dispatch('/save', 'GET'));
        $this->assertSame('post', $this->dispatch('/save', 'POST'));
    }

    public function testGroupPrefixIsApplied(): void {
        Router::group(['prefix' => 'admin'], function () {
            Router::get('/users/{id}', fn(string $id) => "admin-user:$id");
        });

        $this->assertSame('admin-user:7', $this->dispatch('/admin/users/7'));
    }

    public function testNestedGroupPrefixesCombine(): void {
        Router::prefix('api', function () {
            Router::prefix('v1', function () {
                Router::get('/ping', fn() => 'pong');
            });
        });

        $this->assertSame('pong', $this->dispatch('/api/v1/ping'));
    }

    public function testResourceInsideGroupDoesNotDoublePrefix(): void {
        // Regression: resource() built the prefixed URI, then get()/post()
        // prefixed it again -> "admin/admin/posts".
        Router::group(['prefix' => 'admin'], function () {
            Router::resource('/posts', 'PostController');
        });

        $this->assertArrayHasKey('admin/posts', Router::$routes['GET']);
        $this->assertArrayHasKey('admin/posts/([^/]+)', Router::$routes['GET']);
        $this->assertArrayHasKey('admin/posts/update/([^/]+)', Router::$routes['POST']);
        $this->assertArrayHasKey('admin/posts/delete/([^/]+)', Router::$routes['POST']);
        $this->assertArrayNotHasKey('admin/admin/posts', Router::$routes['GET']);
    }

    public function testHandWrittenRegexInARouteIsLeftAlone(): void {
        Router::get('/files/(.+)', fn(string $path) => "file:$path");

        $this->assertSame('file:a/b/c.txt', $this->dispatch('/files/a/b/c.txt'));
    }

    public function testCacheRefusesClosures(): void {
        Router::get('/closure', fn() => 'x');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("closures aren't cacheable");

        Router::cache(sys_get_temp_dir() . '/tabel-routes-' . uniqid() . '.php');
    }
}
