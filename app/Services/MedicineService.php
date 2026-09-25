<?php

namespace App\Services;

use App\Core\Database;

class MedicineService
{
    public static function paginateList(int $page, int $perPage, ?string $search, ?int $categoryId, ?string $status): array
    {
        $where = ['m.deleted_at IS NULL'];
        $params = [];

        if ($search) {
            $where[] = '(m.name LIKE :search1 OR m.code LIKE :search2 OR m.generic_name LIKE :search3 OR m.barcode LIKE :search4)';
            $like = '%' . $search . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
        }
        if ($categoryId) {
            $where[] = 'm.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($status) {
            $where[] = 'm.status = :status';
            $params['status'] = $status;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM medicines m {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT m.*, c.name AS category_name, mt.name AS type_name, mg.name AS group_name,
                       mg.requires_prescription, u.symbol AS unit_symbol,
                       COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active'), 0) AS stock
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                LEFT JOIN medicine_types mt ON mt.id = m.medicine_type_id
                LEFT JOIN medicine_groups mg ON mg.id = m.medicine_group_id
                LEFT JOIN units u ON u.id = m.unit_id
                {$whereSql}
                ORDER BY m.name ASC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function findDetailed(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.*, COALESCE((SELECT SUM(mb.available_qty) FROM medicine_batches mb WHERE mb.medicine_id = m.id AND mb.status = 'active'), 0) AS stock
             FROM medicines m WHERE m.id = :id AND m.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function nextCode(): string
    {
        $count = (int) Database::connection()->query('SELECT COUNT(*) AS c FROM medicines')->fetch()['c'];
        return 'OBT' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function batchesFor(int $medicineId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM medicine_batches WHERE medicine_id = :id ORDER BY expired_date ASC'
        );
        $stmt->execute(['id' => $medicineId]);
        return $stmt->fetchAll();
    }

    public static function unitConversionsFor(int $medicineId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT uc.*, fu.name AS from_unit_name, tu.name AS to_unit_name
             FROM unit_conversions uc
             JOIN units fu ON fu.id = uc.from_unit_id
             JOIN units tu ON tu.id = uc.to_unit_id
             WHERE uc.medicine_id = :id ORDER BY uc.id ASC'
        );
        $stmt->execute(['id' => $medicineId]);
        return $stmt->fetchAll();
    }
}
