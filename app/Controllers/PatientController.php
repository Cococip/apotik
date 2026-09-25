<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Database;

class PatientController extends CrudController
{
    protected string $modelClass = \App\Models\Patient::class;
    protected string $viewIndex = 'patients.index';
    protected string $routeBase = '/patients';
    protected string $auditModule = 'patient';
    protected array $searchColumns = ['name', 'patient_number', 'phone'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Pasien';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => 'required|max:150',
            'email' => 'email',
            'phone' => 'max:30',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'patient_number' => $data['patient_number'] ?: $this->nextNumber(),
            'name' => $data['name'],
            'birth_date' => $data['birth_date'] ?: null,
            'gender' => $data['gender'] ?: null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function nextNumber(): string
    {
        $count = (int) Database::connection()->query('SELECT COUNT(*) AS c FROM customers')->fetch()['c'];
        return 'PSN' . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
