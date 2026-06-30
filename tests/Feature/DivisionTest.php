<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->division = Division::factory()->create(['division_code' => 'DIV', 'division_name' => 'Characterization Division']);
});

test('legacy division page requires legacy login session', function () {
    $this->get('/division')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy division page renders through inertia when loginID exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/division')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Division')
            ->where('title', 'Division List')
            ->where('divisions.0.division_name', $this->division->division_name)
        );
});

test('legacy division list contract uses records total and action links', function () {
    $admin = User::factory()->create();
    Division::factory()->create(['division_code' => 'DIV2', 'division_name' => 'Second Division']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/division_list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 2)
        ->assertJsonFragment(['division_name' => $this->division->division_name])
        ->assertJsonFragment([
            'action' => '<a href="#" data-id="'.$this->division->division_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editDivision" title="Update Division Information"></a> <a href="#" data-id="'.$this->division->division_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteDivision" title="Delete Division Information"></a>',
        ]);
});

test('legacy division create validates required fields and persists on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_division_post', ['division_code' => '', 'division_name' => ''])
        ->assertSessionHasErrors([
            'division_code' => 'Division Code is Required',
            'division_name' => 'Division Name is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_division_post', ['division_code' => 'DIV-NEW', 'division_name' => 'Seeded Division'])
        ->assertOk()
        ->assertJson(['success' => 'Division Information Successfully Created!']);

    expect(Division::query()->where('division_code', 'DIV-NEW')->exists())->toBeTrue();
    expect(Division::query()->where('division_name', 'Seeded Division')->exists())->toBeTrue();
});

test('legacy division create rejects duplicate code and name with validation', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_division_post', ['division_code' => $this->division->division_code, 'division_name' => 'Another'])
        ->assertSessionHasErrors(['division_code' => 'The division code has already been taken.']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_division_post', ['division_code' => 'UNIQUE', 'division_name' => $this->division->division_name])
        ->assertSessionHasErrors(['division_name' => 'The division name has already been taken.']);
});

test('legacy division info endpoint returns record payload by legacy id key', function () {
    $admin = User::factory()->create();

    $response = $this->withSession(['loginID' => $admin->id])
        ->postJson('/division_info', ['DivisionID' => $this->division->division_id])
        ->assertOk()
        ->json();

    expect($response['division_code'])->toBe($this->division->division_code);
    expect($response['division_name'])->toBe($this->division->division_name);
});

test('legacy division update validates required fields and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_division_post', ['DivisionID' => $this->division->division_id, 'division_code' => '', 'division_name' => ''])
        ->assertSessionHasErrors([
            'division_code' => 'Division Code is Required',
            'division_name' => 'Division Name is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_division_post', [
            'DivisionID' => $this->division->division_id,
            'division_code' => 'DIV-U',
            'division_name' => 'Updated Division',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Division Information Successfully Updated!']);

    expect($this->division->refresh()->division_name)->toBe('Updated Division');
    expect($this->division->refresh()->division_code)->toBe('DIV-U');
});

test('legacy division delete returns deleted confirmation', function () {
    $admin = User::factory()->create();

    $target = Division::factory()->create(['division_code' => 'DEL', 'division_name' => 'Disposable Division']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_division_confirmed', ['DivisionID' => $target->division_id])
        ->assertOk();

    expect(Division::query()->find($target->division_id))->toBeNull();
});
