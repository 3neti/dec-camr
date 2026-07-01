<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildDashboardDataContractAction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildDashboardDataContractAction $buildDashboardDataContractAction,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard', $this->buildDashboardDataContractAction->execute($user));
    }
}
