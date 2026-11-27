<?php

namespace Tabel\Tests\Core;

use PHPUnit\Framework\TestCase;
use Tabel\Core\Request;

/** Raw input vs escaped input, nested arrays, and the _method override. */
class RequestFixesTest extends TestCase {

    protected function setUp(): void {
        $_POST = ['name' => '  Tom & "Jerry" <b>  ', 'lines' => [['qty' => ' 2 ', 'note' => 'a&b']]];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    }

    protected function tearDown(): void {
        $_POST = [];
    }

    private function request(): Request {
        $ref = new \ReflectionClass(Request::class);
        $r = $ref->newInstanceWithoutConstructor();
        $prop = $ref->getProperty('data');
        $prop->setAccessible(true);
        $prop->setValue($r, $_POST);
        return $r;
    }

    public function testRawReturnsTrimmedUnescapedValues(): void {
        $this->assertSame('Tom & "Jerry" <b>', $this->request()->raw('name'));
        $this->assertSame([['qty' => '2', 'note' => 'a&b']], $this->request()->raw('lines'));
        $this->assertSame('d', $this->request()->raw('missing', 'd'));
    }

    public function testInputStillEscapesForBackwardCompatibility(): void {
        $this->assertSame('Tom &amp; &quot;Jerry&quot; &lt;b&gt;', $this->request()->input('name'));
    }

    public function testInputHandlesNestedArraysWithoutWarnings(): void {
        $this->assertSame([['qty' => '2', 'note' => 'a&amp;b']], $this->request()->input('lines'));
    }

    public function testMethodOverrideFromForm(): void {
        $_POST['_method'] = 'delete';
        if (Request::isCli()) {
            $this->markTestSkipped('Request::method() returns CLI under phpunit');
        }
        $this->assertSame('DELETE', Request::method());
    }
}
