<?php

namespace App\Controllers;

use App\Core\CrudController;

class RackController extends CrudController
{
    protected string $modelClass = \App\Models\Rack::class;
    protected string $viewIndex = 'racks.index';
    protected string $routeBase = '/racks';
    protected string $auditModule = 'rack';
    protected array $searchColumns = ['code', 'name'];
    protected string $orderBy = 'code';
    protected string $orderDirection = 'ASC';
    protected string $entityLabel = 'Rak/Lokasi';

    protected function rules(bool $isUpdate, ?int $id = null): array
    {
        return [
            'code' => 'required|max:30',
            'name' => 'required|max:100',
        ];
    }

    protected function beforeSave(array $data, bool $isUpdate, ?int $id = null): array
    {
        return [
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ];
    }
}
