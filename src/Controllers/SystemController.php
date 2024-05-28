<?php

namespace Tabel\Controllers;

use Tabel\Core\Modules\Logger;
use Tabel\Controllers\MainController;

class SystemController extends MainController {

    public function __construct() {
        //   $this->middleware('auth');
    }
    public function index() {
        return view('_log', [
            'logs' => Logger::getLogs(),
        ]);
    }

    public function deleteLogs() {
        $this->json("Deleting Logs...");
        if (request('_delete_logs') !== md5(session_get('email'))) {
            logger("Warning", "System: Someone is trying to force delete logs" . session_get('email'));
            return redirect(':system:/logs');
        }
        $this->actuallyDeleteLogs();
    }

    public function actuallyDeleteLogs() {

        if (!Logger::deleteLogs(session_get('email'))) {
            $this->json("Unable to delete", 500);
        }
        $this->json("Logs Deleted");
    }
}
