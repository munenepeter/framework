<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Modules\Session;

class SessionTest extends TestCase {

    protected function setUp(): void {
        $_SESSION = [];
    }

    public function testMakeDoesNotOverwriteButSetDoes(): void {
        Session::make('a', 1);
        Session::make('a', 2);
        $this->assertSame(1, Session::get('a'));

        Session::set('a', 3);
        $this->assertSame(3, Session::get('a'));
    }

    public function testGetDefaultHasAndPull(): void {
        $this->assertNull(Session::get('missing'));
        $this->assertSame('x', Session::get('missing', 'x'));
        $this->assertFalse(Session::has('missing'));

        Session::set('flash', 'hello');
        $this->assertTrue(Session::has('flash'));
        $this->assertSame('hello', Session::pull('flash'));
        $this->assertFalse(Session::has('flash'));
    }

    public function testPushAppendsAndCreatesTheArray(): void {
        $this->assertSame(['a'], Session::push('list', 'a'));
        $this->assertSame(['a', 'b'], Session::push('list', 'b'));
        $this->assertSame(['a', 'b'], $_SESSION['list']);
    }

    public function testDestroyEmptiesTheSessionArray(): void {
        Session::set('user_id', 5);
        Session::destroy();
        $this->assertSame([], $_SESSION);
    }

    public function testStartIsANoOpInCli(): void {
        $this->assertFalse(Session::start());
    }
}
