<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\User;
use Illuminate\Http\Request;

final class ListUsersAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = User::query()
            ->select('id', 'user_real_name', 'user_job_title', 'name', 'email', 'user_type', 'user_access', 'created_at', 'updated_at')
            ->orderBy('name');

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['user_name', 'user_real_name', 'user_job_title', 'user_email_address', 'user_type', 'user_access'],
            [
                'user_id' => 'id',
                'user_real_name' => 'user_real_name',
                'user_job_title' => 'user_job_title',
                'user_name' => 'name',
                'user_email_address' => 'email',
                'user_type' => 'user_type',
                'user_access' => 'user_access',
                'created_at_dt_format' => 'created_at',
                'updated_at_dt_format' => 'updated_at',
            ],
        );

        $users = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $users->map(fn (User $user): array => [
                'user_id' => $user->id,
                'user_real_name' => $user->user_real_name,
                'user_job_title' => $user->user_job_title,
                'user_name' => $user->name,
                'user_email_address' => $user->email,
                'user_type' => $user->user_type,
                'user_access' => $user->user_access ?? 'Selected',
                'created_at_dt_format' => $user->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $user->updated_at?->format('Y-m-d H:i:s'),
                'action' => $this->buildActionColumn($user->id, $user->user_access ?? 'Selected'),
            ])->toArray(),
        ];
    }

    private function buildActionColumn(int $userId, ?string $userAccess): string
    {
        $editButton = '<a href="#" data-id="'.$userId.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editUser" title="Update User Information"></a>';
        $deleteButton = '<a href="#" data-id="'.$userId.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteUser" title="Delete User Information"></a>';

        if (($userAccess ?? 'Selected') === 'ALL') {
            return '<div align="center" class="action_table_menu_switch">'.$editButton.' '.$deleteButton.'</div>';
        }

        $siteAccessButton = '<a href="#" data-id="'.$userId.'" class="bi bi-building btn_icon_table btn_icon_table_view" id="UserAccess" onclick="UpdateUserAccess('.$userId.')" title="Add User Site Access"></a>';

        return '<div align="center" class="action_table_menu_switch">'.$siteAccessButton.' '.$editButton.' '.$deleteButton.'</div>';
    }
}
