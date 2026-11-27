<?php

namespace Tabel\Core;

class Middleware {

    protected $middleware = [
        'auth', 'admin'
    ];

    public function middleware(string $middleware) {
        if (!in_array($middleware, $this->middleware)) {
            throw new \Exception("This {$middleware} middleware doesn't exist", 500);
        }
        $this->$middleware();
    }

    /** Signed-in users only. Guests go to AUTH_LOGIN_PATH (default /signin). */
    private function auth() {
        if (!auth()) {
            return redirect($this->loginPath());
        }
    }

    /** Signed-in users whose role is admin or superadmin. Others get a 403. */
    private function admin() {
        $this->auth();
        if (!Auth::hasRole(['admin', 'superadmin'])) {
            throw new \Exception('You do not have permission to view this page.', 403);
        }
    }

    private function loginPath(): string {
        try {
            return (string) (app()->get('config.auth.login_path') ?: '/signin');
        } catch (\Throwable $e) {
            return '/signin';
        }
    }
}
