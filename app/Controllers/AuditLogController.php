<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $module = trim((string) $request->query('module', ''));
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        $where = [];
        $params = [];
        if ($module) {
            $where[] = 'a.module = :module';
            $params['module'] = $module;
        }
        if ($search) {
            $where[] = '(u.full_name LIKE :search1 OR a.action LIKE :search2)';
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }
        if ($dateFrom) {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo) {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $db = Database::connection();
        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $perPage = 20;
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT a.*, u.full_name AS user_name
             FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             {$whereSql}
             ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        $modules = $db->query('SELECT DISTINCT module FROM audit_logs ORDER BY module ASC')->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('audit_log.index', [
            'title' => 'Audit Log',
            'rows' => $stmt->fetchAll(),
            'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages],
            'module' => $module,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'modules' => $modules,
        ]);
    }
}
