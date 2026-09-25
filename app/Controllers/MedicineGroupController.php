<?php

namespace App\Controllers;

use App\Core\CrudController;

class MedicineGroupController extends CrudController
{
    protected string $modelClass = \App\Models\MedicineGroup::class;
    protected string $viewIndex = 'medicine_groups.index';
    protected string $routeBase = '/medicine-groups';
    protected string $auditModule = 'medicine_group';
    protected array $searchColumns = ['name'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Golongan Obat';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return ['name' => 'required|max:100'];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'requires_prescription' => !empty($data['requires_prescription']) ? 1 : 0,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }
}
