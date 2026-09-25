<?php

namespace App\Controllers;

use App\Core\CrudController;

class MedicineTypeController extends CrudController
{
    protected string $modelClass = \App\Models\MedicineType::class;
    protected string $viewIndex = 'medicine_types.index';
    protected string $routeBase = '/medicine-types';
    protected string $auditModule = 'medicine_type';
    protected array $searchColumns = ['name'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Jenis Obat';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return ['name' => 'required|max:100'];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }
}
