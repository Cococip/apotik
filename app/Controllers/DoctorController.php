<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;

class DoctorController extends CrudController
{
    protected string $modelClass = \App\Models\Doctor::class;
    protected string $viewIndex = 'doctors.index';
    protected string $routeBase = '/doctors';
    protected string $auditModule = 'doctor';
    protected array $searchColumns = ['name', 'code', 'specialization'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Dokter';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => 'required|max:150',
            'phone' => 'max:30',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'code' => $data['code'] ?: $this->nextCode(),
            'name' => $data['name'],
            'sip_number' => $data['sip_number'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }

    private function nextCode(): string
    {
        $count = (int) Database::connection()->query('SELECT COUNT(*) AS c FROM doctors')->fetch()['c'];
        return 'DOK' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
