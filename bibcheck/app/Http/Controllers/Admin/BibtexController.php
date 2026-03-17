<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BibtexTypeEntry;
use App\Models\BibtexField;
class BibtexController extends Controller
{
    public function index()
    {
        // Загружаем типы вместе с их полями (Eager Loading)
        $types = BibtexTypeEntry::with('fields')->get();
        $allFields = BibtexField::all(); // для формы создания

        return view('admin.bibtex', compact('types', 'allFields'));
    }
}
