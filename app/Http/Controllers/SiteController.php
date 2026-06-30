<?php

namespace App\Http\Controllers;

use App\Actions\Site\CreateSiteAction;
use App\Actions\Site\DeleteSiteAction;
use App\Actions\Site\GetSiteAction;
use App\Actions\Site\ListSitesAction;
use App\Actions\Site\UpdateSiteAction;
use App\Http\Requests\Site\CreateSiteRequest;
use App\Http\Requests\Site\UpdateSiteRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class SiteController extends Controller
{
    public function __construct(
        private readonly ListSitesAction $listSitesAction,
        private readonly GetSiteAction $getSiteAction,
        private readonly CreateSiteAction $createSiteAction,
        private readonly UpdateSiteAction $updateSiteAction,
        private readonly DeleteSiteAction $deleteSiteAction,
    ) {}

    private function legacyUser(): User
    {
        $legacyUser = request()->attributes->get('legacyUser');

        if (! $legacyUser instanceof User) {
            abort(403, 'Unauthorized');
        }

        return $legacyUser;
    }

    public function site()
    {
        $sitePayload = $this->listSitesAction->execute(request(), false, $this->legacyUser());
        $sites = $sitePayload['data'] ?? [];

        return Inertia::render('Site', [
            'sites' => is_array($sites) ? $sites : [],
            'title' => 'Site Management',
        ]);
    }

    public function siteList(Request $request)
    {
        return response()->json($this->listSitesAction->execute($request, false, $this->legacyUser()));
    }

    public function siteUserList(Request $request)
    {
        return response()->json($this->listSitesAction->execute($request, true, $this->legacyUser()));
    }

    public function createSitePost(CreateSiteRequest $request)
    {
        $this->createSiteAction->execute(
            (string) $request->string('building_code'),
            (string) $request->string('building_description'),
            (int) $request->integer('division_id'),
            (int) $request->integer('company_id'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Building Information Successfully Created!']);
    }

    public function siteInfo(Request $request)
    {
        $request->validate([
            'siteID' => ['required', 'integer'],
        ]);

        $site = $this->getSiteAction->execute((int) $request->input('siteID'));

        return response()->json($site);
    }

    public function updateSitePost(UpdateSiteRequest $request)
    {
        $this->updateSiteAction->execute(
            (int) $request->integer('SiteID'),
            (string) $request->string('building_code'),
            (string) $request->string('building_description'),
            (int) $request->integer('division_id'),
            (int) $request->integer('company_id'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Building Information Successfully Updated!']);
    }

    public function deleteSiteConfirmed(Request $request)
    {
        $request->validate([
            'siteID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteSiteAction->execute((int) $request->integer('siteID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }

    public function siteDetails(int $siteID)
    {
        $site = $this->getSiteAction->execute($siteID);

        return Inertia::render('Site', [
            'viewSite' => $site->toArray(),
            'title' => 'Site Details',
            'sites' => [],
        ]);
    }
}
