<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Company\CreateCompanyAction;
use App\Actions\Company\DeleteCompanyAction;
use App\Actions\Company\GetCompanyAction;
use App\Actions\Company\ListCompaniesAction;
use App\Actions\Company\UpdateCompanyAction;
use App\Http\Requests\Company\CreateCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CompanyController extends Controller
{
    public function __construct(
        private readonly ListCompaniesAction $listCompaniesAction,
        private readonly CreateCompanyAction $createCompanyAction,
        private readonly GetCompanyAction $getCompanyAction,
        private readonly UpdateCompanyAction $updateCompanyAction,
        private readonly DeleteCompanyAction $deleteCompanyAction,
    ) {}

    public function company()
    {
        $companiesPayload = $this->listCompaniesAction->execute(request());
        $companies = $companiesPayload['data'] ?? [];

        return Inertia::render('Company', [
            'companies' => is_array($companies) ? $companies : [],
            'title' => 'Company List',
        ]);
    }

    public function companyList(Request $request)
    {
        return response()->json($this->listCompaniesAction->execute($request));
    }

    public function createCompanyPost(CreateCompanyRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->createCompanyAction->execute(
            (string) $request->string('company_name'),
            (string) $request->input('company_code', ''),
            $loginId,
        );

        return response()->json(['success' => 'Company Information Successfully Created!']);
    }

    public function companyInfo(Request $request)
    {
        $request->validate([
            'CompanyID' => ['required', 'integer'],
        ]);

        $company = $this->getCompanyAction->execute((int) $request->input('CompanyID'));

        return response()->json($company);
    }

    public function updateCompanyPost(UpdateCompanyRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->updateCompanyAction->execute(
            (int) $request->integer('CompanyID'),
            (string) $request->string('company_name'),
            $loginId,
        );

        return response()->json(['success' => 'Company Information Successfully Updated!']);
    }

    public function deleteCompanyConfirmed(Request $request)
    {
        $request->validate([
            'CompanyID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteCompanyAction->execute((int) $request->integer('CompanyID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }
}
