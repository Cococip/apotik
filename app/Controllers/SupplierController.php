<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;

class SupplierController extends CrudController
{
    protected string $modelClass = \App\Models\Supplier::class;
    protected string $viewIndex = 'suppliers.index';
    protected string $routeBase = '/suppliers';
    protected string $auditModule = 'supplier';
    protected array $searchColumns = ['name', 'code', 'contact_person'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Supplier';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => 'required|max:150',
            'email' => 'email',
            'payment_term_days' => 'integer',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'code' => $data['code'] ?: $this->nextCode(),
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'npwp' => $data['npwp'] ?? null,
            'payment_term_days' => (int) ($data['payment_term_days'] ?? 0),
            'status' => $data['status'] ?? 'active',
        ];
    }

    private function nextCode(): string
    {
        $count = (int) Database::connection()->query('SELECT COUNT(*) AS c FROM suppliers')->fetch()['c'];
        return 'SUP' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
