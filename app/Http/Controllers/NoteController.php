<?php

namespace App\Http\Controllers;

use App\Enums\NoteType as EnumsNoteType;
use App\Models\Note;
use App\Models\NoteTranslation;
use Illuminate\Http\Request;

class NoteController extends Controller
{

    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_notes'])->only('index');
        $this->middleware(['permission:add_note'])->only('create');
        $this->middleware(['permission:edit_note'])->only('edit');
        $this->middleware(['permission:delete_note'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes  = Note::latest()->paginate(15);
        return view('backend.note.index', compact('notes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = EnumsNoteType::cases();
        return view('backend.note.create', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $note = new Note();
        $note->note_type = $request->note_type;
        $note->description = $request->description;
        $note->save();

        $note_translation = NoteTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'note_id' => $note->id]);
        $note_translation->description = $request->description;
        $note_translation->save();

        flash(translate('Note has been created successfully!'))->success();
        return redirect()->route('note.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $lang   = $request->lang;
        $types = EnumsNoteType::cases();
        $note  = Note::findOrFail($id);
        return view('backend.note.edit', compact('note', 'types', 'lang'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);
        $note->note_type = $request->note_type;
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $note->description = $request->description;
        }
        $note->save();

        $note_translation = NoteTranslation::firstOrNew(['lang' => $request->lang, 'note_id' => $note->id]);
        $note_translation->description = $request->description;
        $note_translation->save();

        flash(translate('Note has been updated successfully!'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {   
        $note = Note::findOrFail($note->id);
        $note->note_translations()->delete();
        $note->delete();
        flash(translate('Note has been deleted successfully!'))->success();
        return back();
    }

    public function getNotes(Request $request)
    {
        $noteType = $request->note_type;
        $notes = Note::where('note_type', $noteType)->get();
        return view('backend.note.get_notes', compact('notes', 'noteType'));
    }
}
