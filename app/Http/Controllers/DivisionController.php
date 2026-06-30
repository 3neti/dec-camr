<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Division\CreateDivisionAction;
use App\Actions\Division\DeleteDivisionAction;
use App\Actions\Division\GetDivisionAction;
use App\Actions\Division\ListDivisionsAction;
use App\Actions\Division\UpdateDivisionAction;
use App\Http\Requests\Division\CreateDivisionRequest;
use App\Http\Requests\Division\UpdateDivisionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class DivisionController extends Controller
{
    public function __construct(
        private readonly ListDivisionsAction $listDivisionsAction,
        private readonly CreateDivisionAction $createDivisionAction,
        private readonly GetDivisionAction $getDivisionAction,
        private readonly UpdateDivisionAction $updateDivisionAction,
        private readonly DeleteDivisionAction $deleteDivisionAction,
    ) {}

    public function division()
    {
        $divisionsPayload = $this->listDivisionsAction->execute(request());
        $divisions = $divisionsPayload['data'] ?? [];

        return Inertia::render('Division', [
            'divisions' => is_array($divisions) ? $divisions : [],
            'title' => 'Division List',
        ]);
    }

    public function divisionList(Request $request)
    {
        return response()->json($this->listDivisionsAction->execute($request));
    }

    public function createDivisionPost(CreateDivisionRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->createDivisionAction->execute(
            (string) $request->string('division_code'),
            (string) $request->string('division_name'),
            $loginId,
        );

        return response()->json(['success' => 'Division Information Successfully Created!']);
    }

    public function divisionInfo(Request $request)
    {
        $request->validate([
            'DivisionID' => ['required', 'integer'],
        ]);

        $division = $this->getDivisionAction->execute((int) $request->integer('DivisionID'));

        return response()->json($division);
    }

    public function updateDivisionPost(UpdateDivisionRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->updateDivisionAction->execute(
            (int) $request->integer('DivisionID'),
            (string) $request->string('division_code'),
            (string) $request->string('division_name'),
            $loginId,
        );

        return response()->json(['success' => 'Division Information Successfully Updated!']);
    }

    public function deleteDivisionConfirmed(Request $request)
    {
        $request->validate([
            'DivisionID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteDivisionAction->execute((int) $request->integer('DivisionID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }
}
