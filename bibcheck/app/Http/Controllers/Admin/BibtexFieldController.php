<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BibtexField;
use Illuminate\Http\Request;

class BibtexFieldController extends Controller
{
    // Просмотр всех полей и форма создания
    public function index()
    {
        $fields = BibtexField::orderBy('name_field')->get();
        return view('admin.bibtex', compact('fields'));
    }

    // Сохранение нового поля
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_field' => 'required|string|max:100|unique:bibtex_fields,name_field|regex:/^[a-z_]+$/',
        ], [
            'name_field.unique' => ' Такое поле уже существует.',
            'name_field.regex' => ' Используйте только строчные латинские буквы и подчеркивание.',
        ]);

        BibtexField::create($validated);

        return back()->with('success', 'Поле успешно добавлено!');
    }

    // Удаление поля
    public function destroy(BibtexField $field)
    {
        // Проверяем, не используется ли поле в типах записей (опционально)
        if ($field->typeEntries()->exists()) {
            return back()->with('error', 'Нельзя удалить поле, так как оно используется в типах записей.');
        }

        $field->delete();
        return back()->with('success', 'Поле удалено.');
    }
}
