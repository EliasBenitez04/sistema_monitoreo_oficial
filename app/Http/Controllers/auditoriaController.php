<?php

namespace App\Http\Controllers;

use App\Models\Auditoria; // Asegúrate de tener tu modelo importado
use Illuminate\Http\Request;

class auditoriaController extends Controller
{

    public function index(Request $request)
    {
        $query = Auditoria::query();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('tabla', 'LIKE', "%{$searchTerm}%");
        }

        $query->orderBy('fecha', 'desc');

        // Obtener resultados (o paginación)
        $auditorias = $query->get(); // o ->paginate(10);

        return view('auditorias.index', compact('auditorias'));
    }
}
