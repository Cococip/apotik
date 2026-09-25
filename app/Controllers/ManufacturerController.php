<?php

namespace App\Controllers;

use App\Core\CrudController;

class ManufacturerController extends CrudController
{
    protected string $modelClass = \App\Models\Manufacturer::class;
    protected string $viewIndex = 'manufacturers.index';
    protected string $routeBase = '/manufacturers';
    protected string $auditModule = 'manufacturer';
    protected array $searchColumns = ['name'];
    protected string $orderBy = 'name';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Produsen';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return ['name' => 'required|max:150'];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
        ];
    }
}
