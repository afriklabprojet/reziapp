<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerDocument;
use App\Services\OwnerDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerDocumentController extends Controller
{
    public function __construct(
        private OwnerDocumentService $documentService,
    ) {
    }

    public function index(Request $request): View
    {
        $user      = $request->user();
        $documents = $this->documentService->getDocuments($user, $request->only(['category', 'residence_id', 'search']));
        $expiring  = $this->documentService->getExpiringDocuments($user);
        $expired   = $this->documentService->getExpiredDocuments($user);
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.documents.index', compact('documents', 'expiring', 'expired', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();
        $categories = OwnerDocument::CATEGORIES;

        return view('owner.documents.create', compact('residences', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'residence_id' => ['nullable', 'integer', 'exists:residences,id'],
            'category'     => ['required', 'in:'.implode(',', array_keys(OwnerDocument::CATEGORIES))],
            'name'         => ['required', 'string', 'max:200'],
            'file'         => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'expiry_date'  => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $this->documentService->upload($request->user(), $data, $request->file('file'));

        return redirect()->route('owner.documents.index')
            ->with('success', 'Document ajouté avec succès.');
    }

    public function download(OwnerDocument $document): StreamedResponse
    {
        abort_unless($document->owner_id === auth()->id(), 403);

        return Storage::disk('private')->download(
            $document->file_path,
            $document->name.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION),
        );
    }

    public function destroy(OwnerDocument $document): RedirectResponse
    {
        abort_unless($document->owner_id === auth()->id(), 403);
        $this->documentService->delete($document);

        return redirect()->route('owner.documents.index')
            ->with('success', 'Document supprimé.');
    }
}
