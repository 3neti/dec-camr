<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\UserSiteAccess;
use Illuminate\Support\Facades\DB;

final class UpdateUserSiteAccessAction
{
    public function execute(int $userId, ?string $siteItems, int $createdByUserId): bool
    {
        DB::table('user_access_group')->where('user_idx', (string) $userId)->delete();

        $trimmedItems = trim((string) $siteItems);
        if ($trimmedItems === '') {
            return true;
        }

        $siteIds = array_filter(
            array_unique(array_map('intval', explode(',', $trimmedItems))),
            static fn (int $id): bool => $id > 0
        );

        $insertRows = [];
        foreach ($siteIds as $siteId) {
            $insertRows[] = [
                'user_idx' => (string) $userId,
                'site_idx' => $siteId,
                'created_by_user_idx' => $createdByUserId,
                'access_list_src' => 'CAMR',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($insertRows === []) {
            return true;
        }

        UserSiteAccess::query()->insert($insertRows);

        return true;
    }
}
