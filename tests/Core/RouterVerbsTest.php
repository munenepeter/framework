<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Core\Router;
use Tabel\Modules\Csrf;

class RouterVerbsTest extends TestCase {

    protected function setUp(): void {
        Router::$routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => []];
        $_SESSION = [];
        $_POST = [];
        Csrf::$override = null;
    }

    protected function tearDown(): void {
        Csrf::$override = null;
    }

    public function testPutPatchDeleteRoutesDispatch(): void {
        Router::put('/items/{id}', fn($id) => "put {$id}");
        Router::patch('/items/{id}', fn($id) => "patch {$id}");
        Router::delete('/items/{id}', fn($id) => "delete {$id}");

        $r = new Router;
        $this->assertSame('put 7', $r->direct('/items/7', 'PUT'));
        $this->assertSame('patch 7', $r->direct('/items/7', 'PATCH'));
        $this->assertSame('delete 7', $r->direct('/items/7', 'DELETE'));
    }

    public function testPostWithoutTokenIsRejectedWhenCsrfIsOn(): void {
        Csrf::$override = true;
        Router::post('/save', fn() => 'saved');

        $this->expectExceptionCode(419);
        (new Router)->direct('/save', 'POST');
    }

    public function testPostWithTokenPassesAndGetIsNeverChecked(): void {
        Csrf::$override = true;
        Router::post('/save', fn() => 'saved');
        Router::get('/form', fn() => 'form');

        $_POST['_csrf'] = Csrf::token();
        $this->assertSame('saved', (new Router)->direct('/save', 'POST'));
        $this->assertSame('form', (new Router)->direct('/form', 'GET'));
    }

    public function testCsrfOffByDefaultKeepsOldBehaviour(): void {
        Router::post('/save', fn() => 'saved');
        $this->assertSame('saved', (new Router)->direct('/save', 'POST'));
    }
}
