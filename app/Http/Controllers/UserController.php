<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\GetUserAction;
use App\Actions\User\ListUsersAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UserAccountRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class UserController extends Controller
{
    public function __construct(
        private readonly ListUsersAction $listUsersAction,
        private readonly GetUserAction $getUserAction,
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly DeleteUserAction $deleteUserAction,
    ) {}

    public function user()
    {
        $userPayload = $this->listUsersAction->execute(request());

        return Inertia::render('User', [
            'users' => $userPayload['data'] ?? [],
            'title' => 'User List',
        ]);
    }

    public function userList(Request $request)
    {
        return response()->json($this->listUsersAction->execute($request));
    }

    public function createUserPost(CreateUserRequest $request)
    {
        $user = $this->createUserAction->execute(
            (string) $request->input('user_real_name'),
            (string) $request->input('user_name'),
            (string) $request->input('user_email_address'),
            (string) $request->input('user_password'),
            (string) $request->input('user_type'),
            (string) $request->input('user_access', 'Selected'),
            (string) $request->input('user_job_title', ''),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'User Information successfully created!', 'user_id' => $user->id]);
    }

    public function userInfo(Request $request)
    {
        $request->validate([
            'UserID' => ['required', 'integer'],
        ]);

        return response()->json($this->getUserAction->execute((int) $request->input('UserID')));
    }

    public function updateUserPost(UpdateUserRequest $request)
    {
        $this->updateUserAction->execute(
            (int) $request->integer('userID'),
            (string) $request->input('user_real_name'),
            (string) $request->input('user_name'),
            (string) $request->input('user_email_address'),
            (string) $request->input('user_type'),
            $request->filled('user_password') ? (string) $request->input('user_password') : null,
            (string) $request->input('user_job_title', ''),
            (string) $request->input('user_access', 'Selected'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'User Information successfully updated!']);
    }

    public function userAccountPost(UserAccountRequest $request)
    {
        $this->updateUserAction->execute(
            (int) $request->integer('userID'),
            (string) $request->input('user_real_name'),
            (string) $request->input('user_name'),
            (string) $request->input('user_email_address', ''),
            (string) $request->input('user_type', 'User'),
            $request->filled('user_password') ? (string) $request->input('user_password') : null,
            null,
            'Selected',
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Account Information Successfully Updated!']);
    }

    public function deleteUserConfirmed(Request $request)
    {
        $request->validate([
            'userID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteUserAction->execute((int) $request->integer('userID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }
}
