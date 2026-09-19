<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BibtexTypeEntry;
use App\Models\BibtexField;
use Inertia\Inertia;

class BibtexController extends Controller
{
    public function index()
    {
        $types = BibtexTypeEntry::with('fields')->get();
        $fields = BibtexField::orderBy('name_field')->get(); // Для списка полей и для чекбоксов

        // Используем одну вьюху, которую ты скинул
        return Inertia::render('admin/bibtex/index', [
            'types' => $types,
            'fields' => $fields,
            'allFields' => $fields // Для формы создания типа
        ]);
    }

    public function destroy(BibtexTypeEntry $type)
    {
        // Связи в pivot-таблице удалятся автоматически, если в миграции стоит onDelete('cascade')
        // Если нет, Laravel сделает это сам при вызове detach()
        $type->fields()->detach();
        $type->delete();

        return back()->with('success', 'Тип записи удален.');
    }

    public function store(Request $request)
    {
        // 1. Валидация
        // Мы ожидаем название типа и массив ID полей, которые к нему относятся
        $validated = $request->validate([
            'name_type_entry' => 'required|string|max:255|unique:bibtex_type_entries,name_type_entry',
            'field_ids' => 'required|array', // Массив ID из таблицы bibtex_fields
            'field_ids.*' => 'exists:bibtex_fields,id',
        ]);

        try {
            \DB::beginTransaction();

            // 2. Создаем сам тип записи
            $typeEntry = BibtexTypeEntry::create([
                'name_type_entry' => strtolower($validated['name_type_entry']),
            ]);

            // 3. Привязываем поля через pivot-таблицу
            // Метод sync удобен тем, что он заполняет связующую таблицу
            $typeEntry->fields()->sync($validated['field_ids']);

            \DB::commit();

            return redirect()
                ->route('admin.bibtex.index')
                ->with('success', "Тип записи '{$typeEntry->name_type_entry}' успешно создан.");

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ошибка: ' . $e->getMessage()]);
        }
    }
}
