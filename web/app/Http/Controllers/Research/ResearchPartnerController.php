<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\ResearchPartner;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResearchPartnerController extends Controller
{
    public function __construct(
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        return view('research.partners.index', [
            'partners' => ResearchPartner::query()
                ->orderByRaw('name IS NULL, name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('research.partners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = new ResearchPartner;
        $partner->fill($this->validated($request));
        $partner->created_by = $request->user()?->id;
        $partner->save();

        return redirect()
            ->route('research.partners.index')
            ->with('status', 'Partner added.');
    }

    public function edit(ResearchPartner $partner): View
    {
        return view('research.partners.edit', [
            'partner' => $partner,
        ]);
    }

    public function update(Request $request, ResearchPartner $partner): RedirectResponse
    {
        $partner->fill($this->validated($request, $partner));
        $partner->updated_by = $request->user()?->id;
        $partner->save();

        return redirect()
            ->route('research.partners.index')
            ->with('status', 'Partner updated.');
    }

    public function destroy(ResearchPartner $partner): RedirectResponse
    {
        $partner->delete();

        return redirect()
            ->route('research.partners.index')
            ->with('status', 'Partner removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ResearchPartner $partner = null): array
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $name = trim($request->string('name')->toString());
        $keepsLogo = $request->hasFile('logo')
            || ($partner?->logo_path && ! $request->boolean('remove_logo'));

        // A partner with neither a name nor a logo would post an empty card to the website.
        if ($name === '' && ! $keepsLogo) {
            $request->validate(
                ['name' => ['required']],
                ['name.required' => 'Type the partner name, or upload the logo that carries it.'],
            );
        }

        $data = [
            'name' => $name === '' ? null : $name,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->files->replace(
                $partner?->logo_path,
                $request->file('logo'),
                'research/partners',
                'public',
                null,
                true,
            );
        } elseif ($partner?->logo_path) {
            if ($request->boolean('remove_logo')) {
                $this->files->delete($partner->logo_path, 'public');
                $data['logo_path'] = null;
            } else {
                // Saving the name without picking a file keeps the logo already on record.
                $data['logo_path'] = $partner->logo_path;
            }
        }

        return $data;
    }
}
