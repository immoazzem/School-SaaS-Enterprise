<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolDocument;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SchoolDocumentController extends Controller
{
    public function __construct(private readonly PlanLimitService $planLimitService) {}

    public function index(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::in($this->categories())],
            'is_public' => ['nullable', 'boolean'],
            'related_model_type' => ['nullable', 'string', 'max:160'],
            'related_model_id' => ['nullable', 'integer'],
        ]);

        $canManage = $this->canManage($request, $school);
        $documents = $school->documents()
            ->with('uploader:id,name,email')
            ->when($validated['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when(array_key_exists('is_public', $validated), fn ($query) => $query->where('is_public', $validated['is_public']))
            ->when($validated['related_model_type'] ?? null, fn ($query, string $type) => $query->where('related_model_type', $type))
            ->when($validated['related_model_id'] ?? null, fn ($query, int $id) => $query->where('related_model_id', $id))
            ->when(! $canManage, fn ($query) => $query->where(function ($query) use ($request): void {
                $query->where('is_public', true)->orWhere('uploader_id', $request->user()->id);
            }))
            ->orderByDesc('uploaded_at')
            ->paginate($this->perPage($request));

        return response()->json($this->paginated($documents));
    }

    public function store(Request $request, School $school): JsonResponse
    {
        $this->authorizeManage($request, $school);
        $validated = $request->validate([
            'category' => ['required', Rule::in($this->categories())],
            'title' => ['required', 'string', 'max:160'],
            'file' => ['required', 'file', 'max:20480'],
            'is_public' => ['nullable', 'boolean'],
            'related_model_type' => ['nullable', 'string', 'max:160'],
            'related_model_id' => ['nullable', 'integer'],
        ]);

        $file = $validated['file'];
        $this->planLimitService->assertCanStoreDocument($school, $file->getSize());

        $extension = $file->extension() ?: $file->guessExtension() ?: 'bin';
        $storedName = Str::uuid()->toString().'.'.$extension;
        $directory = "schools/{$school->id}/docs";
        $path = $file->storeAs($directory, $storedName, 'local');

        $document = $school->documents()->create([
            'uploader_id' => $request->user()->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size_bytes' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'related_model_type' => $validated['related_model_type'] ?? null,
            'related_model_id' => $validated['related_model_id'] ?? null,
            'uploaded_at' => now(),
        ]);

        $this->recordAudit($request, $school, 'document.uploaded', $document, [
            'new' => $document->makeHidden('file_path')->toArray(),
        ]);

        return response()->json(['data' => $document->load('uploader:id,name,email')], 201);
    }

    public function show(Request $request, School $school, SchoolDocument $document): JsonResponse
    {
        abort_unless($document->school_id === $school->id, 404);
        $this->authorizeView($request, $school, $document);

        return response()->json([
            'data' => [
                ...$document->load('uploader:id,name,email')->toArray(),
                'download_url' => URL::temporarySignedRoute(
                    'schools.documents.download',
                    now()->addMinutes(15),
                    ['school' => $school->id, 'document' => $document->id]
                ),
            ],
        ]);
    }

    public function destroy(Request $request, School $school, SchoolDocument $document): JsonResponse
    {
        abort_unless($document->school_id === $school->id, 404);
        $this->authorizeManage($request, $school);
        $document->delete();

        $this->recordAudit($request, $school, 'document.deleted', $document, [
            'old' => $document->makeHidden('file_path')->toArray(),
        ]);

        return response()->json(status: 204);
    }

    public function download(Request $request, School $school, SchoolDocument $document)
    {
        abort_unless($document->school_id === $school->id, 404);
        $this->authorizeView($request, $school, $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    private function authorizeView(Request $request, School $school, SchoolDocument $document): void
    {
        abort_unless(
            $document->is_public
            || $document->uploader_id === $request->user()->id
            || $this->canManage($request, $school),
            403
        );
    }

    private function authorizeManage(Request $request, School $school): void
    {
        abort_unless($this->canManage($request, $school), 403);
    }

    private function canManage(Request $request, School $school): bool
    {
        return $request->user()->hasSchoolPermission($school, 'documents.manage');
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        return ['circular', 'student_document', 'employee_document', 'financial_document', 'other'];
    }
}
