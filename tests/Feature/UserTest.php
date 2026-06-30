<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->allUser = User::factory()->create([
        'name' => 'admin',
        'user_real_name' => 'Admin User',
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $this->selectedUser = User::factory()->create([
        'name' => 'selected',
        'user_real_name' => 'Selected User',
        'user_type' => 'User',
        'user_access' => 'Selected',
    ]);
});

test('legacy user page requires legacy login session', function () {
    $this->get('/user')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy user page renders through inertia when loginID exists', function () {
    $this->withSession(['loginID' => $this->allUser->id])
        ->get('/user')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User')
            ->where('title', 'User List')
            ->where('users.0.user_name', $this->allUser->name)
        );
});

test('legacy user list contract uses records total and action links', function () {
    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/user_list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonFragment(['user_name' => 'admin'])
        ->assertJsonFragment([
            'action' => '<div align="center" class="action_table_menu_switch"><a href="#" data-id="'.$this->allUser->id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editUser" title="Update User Information"></a> <a href="#" data-id="'.$this->allUser->id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteUser" title="Delete User Information"></a></div>',
        ])
        ->assertJsonFragment([
            'action' => '<div align="center" class="action_table_menu_switch"><a href="#" data-id="'.$this->selectedUser->id.'" class="bi bi-building btn_icon_table btn_icon_table_view" id="UserAccess" onclick="UpdateUserAccess('.$this->selectedUser->id.')" title="Add User Site Access"></a> <a href="#" data-id="'.$this->selectedUser->id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editUser" title="Update User Information"></a> <a href="#" data-id="'.$this->selectedUser->id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteUser" title="Delete User Information"></a></div>',
        ]);
});

test('legacy user create validates required fields and creates a user', function () {
    $this->withSession(['loginID' => $this->allUser->id])
        ->post('/create_user_post', ['user_name' => '', 'user_real_name' => '', 'user_email_address' => '', 'user_password' => '', 'user_type' => ''])
        ->assertSessionHasErrors([
            'user_real_name' => 'Name is Required',
            'user_name' => 'User Name is Required',
            'user_email_address' => 'Email Address is Required',
            'user_password' => 'Password is Required',
            'user_type' => 'User Type is Required',
        ]);

    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/create_user_post', [
            'user_real_name' => 'New User',
            'user_name' => 'new-user',
            'user_email_address' => 'new-user@example.test',
            'user_password' => 'secret123',
            'user_type' => 'User',
            'user_access' => 'Selected',
            'user_job_title' => 'Technician',
        ])
        ->assertOk()
        ->assertJson(['success' => 'User Information successfully created!']);

    expect(User::query()->where('name', 'new-user')->exists())->toBeTrue();
    expect(User::query()->where('user_real_name', 'New User')->exists())->toBeTrue();
    expect(User::query()->where('email', 'new-user@example.test')->exists())->toBeTrue();
});

test('legacy user create blocks duplicate user_name', function () {
    $this->withSession(['loginID' => $this->allUser->id])
        ->post('/create_user_post', [
            'user_real_name' => 'Another Name',
            'user_name' => $this->allUser->name,
            'user_email_address' => 'other@example.test',
            'user_password' => 'secret123',
            'user_type' => 'User',
        ])
        ->assertSessionHasErrors(['user_name']);
});

test('legacy user info endpoint returns payload by id', function () {
    $payload = $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/user_info', ['UserID' => $this->selectedUser->id])
        ->assertOk()
        ->json();

    expect($payload['user_name'])->toBe($this->selectedUser->name);
    expect($payload['user_real_name'])->toBe($this->selectedUser->user_real_name);
    expect($payload['user_email_address'])->toBe($this->selectedUser->email);
    expect((int) $payload['user_id'])->toBe($this->selectedUser->id);
});

test('legacy user update validates required fields and updates user', function () {
    $this->withSession(['loginID' => $this->allUser->id])
        ->post('/update_user_post', [
            'userID' => $this->selectedUser->id,
            'user_real_name' => '',
            'user_name' => '',
            'user_email_address' => '',
            'user_type' => '',
            'user_access' => 'Selected',
        ])
        ->assertSessionHasErrors([
            'user_real_name' => 'Name is Required',
            'user_name' => 'User Name is Required',
            'user_email_address' => 'Email Address is Required',
            'user_type' => 'User Type is Required',
        ]);

    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/update_user_post', [
            'userID' => $this->selectedUser->id,
            'user_real_name' => 'Updated Name',
            'user_name' => 'updated-selected',
            'user_email_address' => 'updated-selected@example.test',
            'user_password' => 'updated123',
            'user_type' => 'User',
            'user_access' => 'Selected',
        ])
        ->assertOk()
        ->assertJson(['success' => 'User Information successfully updated!']);

    expect($this->selectedUser->refresh()->user_real_name)->toBe('Updated Name');
    expect($this->selectedUser->refresh()->name)->toBe('updated-selected');
    expect(Hash::check('updated123', $this->selectedUser->refresh()->password))->toBeTrue();
});

test('legacy user delete returns deleted confirmation', function () {
    $target = User::factory()->create([
        'name' => 'to-delete',
        'user_real_name' => 'To Delete',
    ]);

    $this->withSession(['loginID' => $this->allUser->id])
        ->post('/delete_user_confirmed', ['userID' => $target->id])
        ->assertOk()
        ->assertSee('Deleted');

    expect(User::query()->find($target->id))->toBeNull();
});

test('legacy user account update updates account without requiring password', function () {
    $target = User::factory()->create([
        'name' => 'acct-user',
        'user_real_name' => 'Account User',
    ]);
    $originalPassword = $target->password;

    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/user_account_post', [
            'userID' => $target->id,
            'user_real_name' => 'Account User Updated',
            'user_name' => 'acct-user-updated',
            'user_email_address' => $target->email,
        ])
        ->assertOk()
        ->assertJson(['success' => 'Account Information Successfully Updated!']);

    $target->refresh();

    expect($target->user_real_name)->toBe('Account User Updated');
    expect($target->name)->toBe('acct-user-updated');
    expect($target->password)->toBe($originalPassword);
});

test('legacy user site access list returns mapped checkbox state', function () {
    $siteA = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Accessible Building',
    ]);

    $siteB = Site::factory()->create([
        'site_code' => 'SITEB',
        'building_description' => 'Restricted Building',
    ]);

    DB::table('user_access_group')->insert([
        'user_idx' => (string) $this->selectedUser->id,
        'site_idx' => $siteA->site_id,
        'created_by_user_idx' => $this->allUser->id,
        'access_list_src' => 'CAMR',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->withSession(['loginID' => $this->allUser->id])
        ->getJson('/user_site_access?UserID='.$this->selectedUser->id)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonFragment([
            'site_id' => $siteA->site_id,
            'action' => "<input type='checkbox' name='site_checklist' onclick='enableUpdateUserAccess();' value='".$siteA->site_id."' id='CheckboxGroup1_".$siteA->site_id."' checked='checked'/>",
        ])
        ->assertJsonFragment([
            'site_id' => $siteB->site_id,
            'action' => "<input type='checkbox' name='site_checklist' onclick='enableUpdateUserAccess();' value='".$siteB->site_id."' id='CheckboxGroup1_".$siteB->site_id."'/>",
        ]);
});

test('legacy user site access save clears and rewrites assignments', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/add_user_access_post', [
            'userID' => $this->selectedUser->id,
            'site_items' => $siteA->site_id.','.$siteB->site_id,
        ])
        ->assertOk()
        ->assertJson(['success' => 'User Site Access Updated!']);

    expect(DB::table('user_access_group')->where('user_idx', (string) $this->selectedUser->id)->count())->toBe(2);

    $this->withSession(['loginID' => $this->allUser->id])
        ->postJson('/add_user_access_post', [
            'userID' => $this->selectedUser->id,
            'site_items' => '',
        ])
        ->assertOk()
        ->assertJson(['success' => 'User Site Access Removed!']);

    expect(DB::table('user_access_group')->where('user_idx', (string) $this->selectedUser->id)->count())->toBe(0);
});
