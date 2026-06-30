<?php

namespace App\Http\Controllers;

use App\Actions\User\ListUserSiteAccessAction;
use App\Actions\User\UpdateUserSiteAccessAction;
use App\Http\Requests\User\UserSiteAccessRequest;
use Illuminate\Http\Request;

final class UserSiteAccessController extends Controller
{
    public function __construct(
        private readonly ListUserSiteAccessAction $listUserSiteAccessAction,
        private readonly UpdateUserSiteAccessAction $updateUserSiteAccessAction,
    ) {}

    public function getUserSiteAccess(Request $request)
    {
        $request->validate([
            'UserID' => ['required', 'integer'],
        ]);

        $userId = (int) $request->integer('UserID');

        return response()->json($this->listUserSiteAccessAction->execute($request, $userId));
    }

    public function addUserAccessPost(UserSiteAccessRequest $request)
    {
        $saved = $this->updateUserSiteAccessAction->execute(
            (int) $request->integer('userID'),
            $request->input('site_items'),
            (int) session('loginID', 0),
        );

        if (! $saved) {
            return response()->json(['success' => 'User Site Access Information'], 500);
        }

        if (trim((string) $request->input('site_items')) === '') {
            return response()->json(['success' => 'User Site Access Removed!']);
        }

        return response()->json(['success' => 'User Site Access Updated!']);
    }
}
