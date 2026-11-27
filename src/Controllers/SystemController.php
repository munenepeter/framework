<?php

namespace Tabel\Controllers;

use Tabel\Core\Auth;
use Tabel\Modules\Logger;

/**
 * Log viewer. Restricted to signed-in users with the admin or superadmin role (users.role),
 * because logs can contain emails, queries and stack traces.
 */
class SystemController extends MainController {

    public function __construct() {
        parent::__construct();
        $this->middleware('admin');
    }

    public function index() {
        return view_internal('_log', [
            'logs' => Logger::getLogs(),
        ]);
    }

    /** POST /system/logs/delete - protected by CSRF when APP_CSRF is on. */
    public function deleteLogs() {
        $user = Auth::user();
        if (!Logger::deleteLogs($user->email ?? 'unknown')) {
            logger('Error', 'System: log deletion failed');
        }
        return redirect('/system/logs');
    }
}
