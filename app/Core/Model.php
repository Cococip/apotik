<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static bool $softDeletes = false;

    protected static function db(): PDO
    {
        return Database::connection();
    }

    protected static function notDeletedSql(): string
    {
        return static::$softDeletes ? ' WHERE deleted_at IS NULL' : '';
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id';
        if (static::$softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $stmt = static::db()->prepare($sql . ' LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findWithTrashed(int $id): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $sql = 'SELECT * FROM ' . static::$table . ' WHERE ' . self::sanitizeColumn($column) . ' = :value';
        if (static::$softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $stmt = static::db()->prepare($sql . ' LIMIT 1');
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(?string $orderBy = null, string $direction = 'ASC'): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if (static::$softDeletes) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        if ($orderBy) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= ' ORDER BY ' . self::sanitizeColumn($orderBy) . ' ' . $direction;
        }
        return static::db()->query($sql)->fetchAll();
    }

    public static function where(array $conditions): array
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        $sql = 'SELECT * FROM ' . static::$table . ($whereSql ? ' WHERE ' . $whereSql : '');
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $data = self::filterFillable($data);
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', array_map([self::class, 'sanitizeColumn'], $columns)),
            implode(', ', $placeholders)
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute($data);

        return (int) static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $data = self::filterFillable($data);
        if (empty($data)) {
            return true;
        }
        $set = implode(', ', array_map(fn($c) => self::sanitizeColumn($c) . ' = :' . $c, array_keys($data)));

        $sql = sprintf('UPDATE %s SET %s WHERE %s = :__id', static::$table, $set, static::$primaryKey);
        $data['__id'] = $id;

        $stmt = static::db()->prepare($sql);
        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        if (static::$softDeletes) {
            $stmt = static::db()->prepare('UPDATE ' . static::$table . ' SET deleted_at = NOW() WHERE ' . static::$primaryKey . ' = :id');
            return $stmt->execute(['id' => $id]);
        }

        $stmt = static::db()->prepare('DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function restore(int $id): bool
    {
        if (!static::$softDeletes) {
            return false;
        }
        $stmt = static::db()->prepare('UPDATE ' . static::$table . ' SET deleted_at = NULL WHERE ' . static::$primaryKey . ' = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function count(array $conditions = []): int
    {
        [$whereSql, $params] = self::buildWhere($conditions);
        if (static::$softDeletes) {
            $whereSql = $whereSql ? $whereSql . ' AND deleted_at IS NULL' : 'deleted_at IS NULL';
        }
        $sql = 'SELECT COUNT(*) AS total FROM ' . static::$table . ($whereSql ? ' WHERE ' . $whereSql : '');
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Paginate with optional raw search across $searchColumns and extra $conditions.
     */
    public static function paginate(int $page, int $perPage, array $conditions = [], ?string $search = null, array $searchColumns = [], string $orderBy = 'id', string $direction = 'DESC'): array
    {
        [$whereSql, $params] = self::buildWhere($conditions);

        $clauses = $whereSql ? [$whereSql] : [];
        if (static::$softDeletes) {
            $clauses[] = 'deleted_at IS NULL';
        }
        if ($search !== null && $search !== '' && !empty($searchColumns)) {
            $searchParts = [];
            foreach ($searchColumns as $i => $col) {
                $key = 'search_' . $i;
                $searchParts[] = self::sanitizeColumn($col) . ' LIKE :' . $key;
                $params[$key] = '%' . $search . '%';
            }
            $clauses[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $orderBy = self::sanitizeColumn($orderBy);

        $countSql = 'SELECT COUNT(*) AS total FROM ' . static::$table . $where;
        $stmt = static::db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetch()['total'];

        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM ' . static::$table . $where . " ORDER BY {$orderBy} {$direction} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    protected static function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $paramKey = 'w_' . str_replace('.', '_', $column);
            if ($value === null) {
                $parts[] = self::sanitizeColumn($column) . ' IS NULL';
                continue;
            }
            $parts[] = self::sanitizeColumn($column) . ' = :' . $paramKey;
            $params[$paramKey] = $value;
        }

        return [implode(' AND ', $parts), $params];
    }

    protected static function filterFillable(array $data): array
    {
        if (empty(static::$fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected static function sanitizeColumn(string $column): string
    {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
            throw new \InvalidArgumentException("Nama kolom tidak valid: {$column}");
        }
        return $column;
    }

    public static function table(): string
    {
        return static::$table;
    }
}
