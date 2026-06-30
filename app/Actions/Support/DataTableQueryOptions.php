<?php

declare(strict_types=1);

namespace App\Actions\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

final class DataTableQueryOptions
{
    public function __construct(private readonly Request $request) {}

    /**
     * Apply DataTables request options to a query and return response metadata.
     *
     * @param  array<int, string>  $searchColumns
     * @param  array<string, string>  $orderColumns
     * @return array<string, int>
     */
    public function apply(Builder|QueryBuilder $query, array $searchColumns, array $orderColumns): array
    {
        $recordsTotal = (clone $query)->count();

        $searchValue = trim((string) $this->request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($query) use ($searchColumns, $searchValue): void {
                foreach ($searchColumns as $searchColumn) {
                    $query->orWhere($searchColumn, 'like', '%'.$searchValue.'%');
                }
            });
        }

        $recordsFiltered = $query->count();

        $orderColumn = $this->resolveOrderColumn($orderColumns);
        if ($orderColumn !== null) {
            $query->reorder($orderColumn, $this->resolveOrderDirection());
        }

        $start = max(0, (int) $this->request->integer('start', 0));
        $length = (int) $this->request->integer('length', -1);

        if ($length >= 0) {
            $query->skip($start)->take($length);
        }

        return [
            'draw' => (int) $this->request->input('draw', 0),
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
        ];
    }

    private function resolveOrderDirection(): string
    {
        $direction = strtolower((string) $this->request->input('order.0.dir', 'asc'));

        return $direction === 'desc' ? 'desc' : 'asc';
    }

    private function resolveOrderColumn(array $orderColumns): ?string
    {
        $orderColumnIndex = $this->request->input('order.0.column');
        if ($orderColumnIndex === null || $orderColumnIndex === '') {
            return null;
        }

        $orderIndex = (int) $orderColumnIndex;
        $columns = $this->request->input('columns', []);

        if (is_array($columns) && array_key_exists((string) $orderIndex, $columns)) {
            $column = $columns[$orderIndex];
            if (is_array($column) && isset($column['data']) && is_string($column['data'])) {
                $data = trim($column['data']);

                if ($data !== '' && array_key_exists($data, $orderColumns)) {
                    return $orderColumns[$data];
                }
            }
        }

        $orderedColumns = array_values($orderColumns);
        if (array_key_exists($orderIndex, $orderedColumns)) {
            return $orderedColumns[$orderIndex];
        }

        return null;
    }
}
