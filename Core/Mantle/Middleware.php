<?php

namespace Tabel\Core\Mantle;

class Middleware {

    protected $middleware = [
        'auth', 'admin'
    ];

    public function middleware(String $middleware) {
        if (!in_array($middleware, $this->middleware)) {
            throw new \Exception("This {$middleware} middleware doesn't exist", 500);
        }
        $this->$middleware();
    }

    private function auth() {
        if (!auth()) {
            return redirect('/signin');
        }
    }
    private function admin() {
        if (!isAdmin()) {
            return redirectback();
        }
    }
}
