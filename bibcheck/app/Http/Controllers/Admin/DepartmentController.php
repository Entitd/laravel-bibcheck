<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRequirement;
use App\Models\BibtexTypeEntry;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        // Получаем требования для всех 4 курсов
        $requirements = CourseRequirement::orderBy('course_number')->get();

        // Получаем типы записей с их обязательными полями
        $types = BibtexTypeEntry::with('fields')->get();

        return view('admin.department.index', compact('requirements', 'types'));
    }

    public function updateRequirements(Request $request)
    {
        $data = $request->validate([
            'req.*.min_total_quantity' => 'required|integer|min:0',
            'req.*.min_foreign_lang' => 'required|integer|min:0',
            'req.*.min_current_periodicals' => 'required|integer|min:0',
            'req.*.min_21st_century' => 'required|integer|min:0',
        ]);

        foreach ($request->req as $id => $values) {
            CourseRequirement::where('id', $id)->update($values);
        }

        return back()->with('success', 'Параметры курсов обновлены');
    }
}
