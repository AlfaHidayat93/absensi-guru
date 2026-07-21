<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function __construct(protected GoogleSheetService $gas) {}

    public function index()
    {
        $response = $this->gas->getInitialData();
        $subjects = [];
        $error = null;

        if ($response['success'] ?? false) {
            $data = $response['data'] ?? [];
            $subjects = collect($data['mapel'] ?? [])
                ->map(fn ($item) => [
                    'id' => $item['ID_Mapel'] ?? $item['id'] ?? '',
                    'name' => trim((string)($item['Mata_Pelajaran'] ?? '')),
                ])
                ->filter(fn ($item) => $item['name'] !== '')
                ->values()->all();
        } else {
            $error = $response['message'] ?? 'Tidak dapat memuat data mata pelajaran.';
        }

        return view('subjects', compact('subjects', 'error'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $response = $this->gas->addSubject(['name' => $validated['name']]);

        return $response['success']
            ? back()->with('success', $response['message'])
            : back()->withInput()->with('error', $response['message']);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $response = $this->gas->editSubject([
            'id' => $id,
            'name' => $validated['name'],
        ]);

        return $response['success']
            ? back()->with('success', $response['message'])
            : back()->withInput()->with('error', $response['message']);
    }

    public function destroy(string $id)
    {
        $response = $this->gas->deleteSubject(['id' => $id]);

        return $response['success']
            ? back()->with('success', $response['message'])
            : back()->with('error', $response['message']);
    }
}
