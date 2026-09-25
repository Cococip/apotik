<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditLogger;

class BackupController extends Controller
{
    private function backupDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/backups';
    }

    public function index(Request $request): void
    {
        $db = Database::connection();
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $tableCount = (int) $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE()"
        )->fetch()['c'];

        $sizeRow = $db->query(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()"
        )->fetch();

        $files = glob($this->backupDir() . '/*.sql') ?: [];
        rsort($files);
        $backups = array_map(function ($path) {
            return [
                'name' => basename($path),
                'size' => filesize($path),
                'created_at' => filemtime($path),
            ];
        }, $files);

        $this->view('backup.index', [
            'title' => 'Backup Database',
            'databaseName' => $config['database'],
            'tableCount' => $tableCount,
            'sizeMb' => $sizeRow['size_mb'] ?? 0,
            'backups' => $backups,
        ]);
    }

    public function create(Request $request): void
    {
        if (!$this->requireCsrf()) {
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dir = $this->backupDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup-' . date('Ymd-His') . '.sql';
        $path = $dir . '/' . $filename;

        $passwordArg = $config['password'] !== '' ? '-p' . escapeshellarg($config['password']) : '';
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s > %s 2>%s',
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['username']),
            $passwordArg,
            escapeshellarg($config['database']),
            escapeshellarg($path),
            escapeshellarg($path . '.err')
        );

        exec($command, $output, $exitCode);

        $errLog = $path . '.err';
        $errContent = is_file($errLog) ? trim((string) file_get_contents($errLog)) : '';
        if (is_file($errLog)) {
            unlink($errLog);
        }

        if ($exitCode !== 0 || !is_file($path) || filesize($path) === 0) {
            \App\Core\Logger::error('Backup failed: ' . $errContent);
            if (is_file($path)) {
                unlink($path);
            }
            Session::flash('error', 'Backup gagal dibuat. Silakan hubungi administrator sistem.');
            $this->redirect('/backup');
            return;
        }

        AuditLogger::log('create', 'backup', null, null, ['filename' => $filename, 'size' => filesize($path)]);
        Session::flash('success', "Backup {$filename} berhasil dibuat.");
        $this->redirect('/backup');
    }

    public function download(Request $request, string $filename): void
    {
        $safeName = basename($filename);
        if (!preg_match('/^backup-\d{8}-\d{6}\.sql$/', $safeName)) {
            Session::flash('error', 'Nama file backup tidak valid.');
            $this->redirect('/backup');
            return;
        }

        $path = $this->backupDir() . '/' . $safeName;
        if (!is_file($path)) {
            Session::flash('error', 'File backup tidak ditemukan.');
            $this->redirect('/backup');
            return;
        }

        AuditLogger::log('read', 'backup', null, null, ['filename' => $safeName]);
        Response::download($path, $safeName);
    }
}
