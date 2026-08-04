<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name', 'asc')->get();
        $error = null;

        return view('subjects', compact('subjects', 'error'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:subjects,name',
        ]);

        Subject::create(['name' => trim($validated['name'])]);

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:subjects,name,' . $subject->id,
        ]);

        $subject->update(['name' => trim($validated['name'])]);

        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
