<?php

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->company = Company::factory()->create(['company_name' => 'Characterization Company']);
});

test('legacy company page requires legacy login session', function () {
    $this->get('/company')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy company page renders through inertia when loginID exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/company')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company')
            ->where('title', 'Company List')
            ->where('companies.0.company_name', $this->company->company_name)
        );
});

test('legacy company list contract uses records total and action links', function () {
    $admin = User::factory()->create();
    Company::factory()->create(['company_name' => 'Another Company']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/company_list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 2)
        ->assertJsonFragment(['company_name' => $this->company->company_name])
        ->assertJsonFragment(['action' => '<a href="#" data-id="'.$this->company->company_id.'" class="btn_icon_table btn_icon_table_edit" id="editCompany" title="Update Company Information"></a> <a href="#" data-id="'.$this->company->company_id.'" class="btn_icon_table btn_icon_table_delete" id="deleteCompany" title="Delete Company Information"></a>']);
});

test('legacy company list supports datatables search, pagination, and draw', function () {
    $admin = User::factory()->create();
    Company::factory()->create(['company_name' => 'AAA Company']);
    Company::factory()->create(['company_name' => 'ZZZ Company']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/company_list', [
            'draw' => 9,
            'start' => 0,
            'length' => 1,
            'search' => ['value' => 'Company'],
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'company_name'],
                ['data' => 'company_code'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('draw', 9)
        ->assertJsonPath('recordsTotal', 3)
        ->assertJsonPath('recordsFiltered', 3)
        ->assertJsonCount(1, 'data');
});

test('legacy company create validates required name and persists on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->from('/company')
        ->post('/create_company_post', ['company_name' => ''])
        ->assertRedirect('/company')
        ->assertSessionHasErrors(['company_name' => 'Company Name is Required']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_company_post', ['company_name' => 'Seeded Company'])
        ->assertOk()
        ->assertJson(['success' => 'Company Information Successfully Created!']);

    expect(Company::query()->where('company_name', 'Seeded Company')->exists())->toBeTrue();
});

test('legacy company create rejects duplicates with validation message', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_company_post', ['company_name' => $this->company->company_name])
        ->assertSessionHasErrors(['company_name' => 'The company name has already been taken.']);
});

test('legacy company info endpoint returns record payload by legacy id key', function () {
    $admin = User::factory()->create();

    $response = $this->withSession(['loginID' => $admin->id])
        ->postJson('/company_info', ['CompanyID' => $this->company->company_id])
        ->assertOk()
        ->json();

    expect($response['company_name'])->toBe($this->company->company_name);
    expect($response['company_code'])->toBe($this->company->company_code);
});

test('legacy company update validates and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_company_post', ['CompanyID' => $this->company->company_id, 'company_name' => ''])
        ->assertSessionHasErrors(['company_name' => 'Company Name is Required']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_company_post', ['CompanyID' => $this->company->company_id, 'company_name' => 'Updated Company'])
        ->assertOk()
        ->assertJson(['success' => 'Company Information Successfully Updated!']);

    expect($this->company->refresh()->company_name)->toBe('Updated Company');
});

test('legacy company delete returns deleted confirmation', function () {
    $admin = User::factory()->create();

    $target = Company::factory()->create(['company_name' => 'Disposable Company']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_company_confirmed', ['CompanyID' => $target->company_id])
        ->assertOk();

    expect(Company::query()->find($target->company_id))->toBeNull();
});

test('legacy company delete is blocked while dependent sites exist', function () {
    $admin = User::factory()->create();
    $division = Division::factory()->create();
    $target = Company::factory()->create(['company_name' => 'Scoped Delete Company']);

    Site::factory()->create([
        'company_idx' => $target->company_id,
        'division_idx' => $division->division_id,
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_company_confirmed', ['CompanyID' => $target->company_id])
        ->assertStatus(500)
        ->assertJson(['error' => 'Delete Failed!']);

    expect(Company::query()->find($target->company_id))->not->toBeNull();
});
