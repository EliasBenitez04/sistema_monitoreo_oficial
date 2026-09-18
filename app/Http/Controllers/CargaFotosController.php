<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\CargaFotos;

class CargaFotosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:carga_fotos index')->only('index');
        $this->middleware('permission:carga_fotos create')->only('create', 'store');
        $this->middleware('permission:carga_fotos edit')->only('edit', 'update');
        $this->middleware('permission:carga_fotos destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $buscar = trim($request->get('search'));

        $fotos = CargaFotos::with(['linea', 'user'])
            ->when($buscar, function ($query, $buscar) {

                $words = explode(' ', $buscar);

                $query->where(function ($q) use ($words) {

                    foreach ($words as $word) {

                        $q->orWhere('fot_ot', 'ILIKE', "%{$word}%")
                            ->orWhere('fot_desc', 'ILIKE', "%{$word}%")

                            ->orWhereHas('linea', function ($q2) use ($word) {
                                $q2->where('linea_desc', 'ILIKE', "%{$word}%");
                            })

                            ->orWhereHas('user', function ($q3) use ($word) {
                                $q3->where('name', 'ILIKE', "%{$word}%");
                            });
                    }
                });
            })
            ->orderBy('fot_fecha', 'desc')
            ->paginate(5);

        $fotos->appends($request->all());

        if ($request->ajax()) {
            return view('carga_fotos.table', compact('fotos'))->render();
        }

        return view('carga_fotos.index', compact('fotos'));
    }

    public function create()
    {
        $lineas = DB::table('linea')
            ->orderBy('linea_desc', 'asc')
            ->get();

        return view('carga_fotos.create', compact('lineas'));
    }

    public function store(Request $request)
    {
        try {

            $request->merge([
                'fot_ot' => trim($request->fot_ot)
            ]);

            $request->validate([

                'fot_ot'    => 'unique:carga_fotos,fot_ot|required|numeric|digits:5',
                'fot_costo' => 'required|numeric',
                'fot_venta' => 'required|numeric',
                'fot_desc'  => 'required|string|max:120',
                'linea_cod' => 'required|integer|exists:linea,linea_cod',
                'fot_img'   => 'required|image|max:4096',

            ], [

                'fot_ot.unique' => 'La OT Ingresada ya fue cargada, Ingrese Uno Nuevo!',
                'fot_costo.required' => 'El campo Costo es obligatorio.',
                'fot_venta.required' => 'El campo Venta es obligatorio.',
                'fot_desc.required'  => 'El campo Descripción es obligatorio.',
                'linea_cod.required' => 'Debe seleccionar una Línea.',
                'fot_img.required'   => 'Debe seleccionar una imagen.',
                'fot_img.image'      => 'El archivo debe ser una imagen.',
                'fot_img.max'        => 'La imagen no puede superar los 4MB.',

            ]);

            if ($request->hasFile('fot_img')) {

                $file = $request->file('fot_img');

                $filename = time() . '_' . uniqid() . '.' . $file->extension();

                $file->storeAs('public/fotos', $filename);
            } else {

                Alert::error('Error', 'No se pudo cargar la imagen.');
                return redirect()->back()->withInput();
            }

            DB::table('carga_fotos')->insert([

                'fot_fecha' => now(),
                'fot_ot'    => strtoupper($request->fot_ot),
                'fot_costo' => $request->fot_costo,
                'fot_venta' => $request->fot_venta,
                'fot_desc'  => strtoupper($request->fot_desc),
                'linea_cod' => $request->linea_cod,
                'fot_img'   => $filename,
                'user_id'   => Auth::id(),

            ]);

            Alert::success('Éxito', 'Imagen cargada correctamente.');

            return redirect()->route('carga_fotos.index');
        } catch (\Illuminate\Validation\ValidationException $e) {

            $errors = $e->validator->errors()->all();
            $errorText = implode('<br>', $errors);

            Alert::info('Error al cargar la imagen', $errorText);

            return redirect()->back()->withInput();
        } catch (\Exception $e) {

            Alert::error('Error', 'Ocurrió un error al guardar la imagen.');

            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $foto = CargaFotos::find($id);

        if (!$foto) {

            Alert::error('Error', 'Foto no encontrada!');

            return redirect()->route('carga_fotos.index');
        }

        $lineas = DB::table('linea')
            ->orderBy('linea_desc', 'asc')
            ->get();

        return view('carga_fotos.edit', compact('foto', 'lineas'));
    }

    public function update(Request $request, $id)
    {
        try {

            $foto = CargaFotos::findOrFail($id);

            $request->merge([
                'fot_ot' => trim($request->fot_ot)
            ]);

            $request->validate([
                'fot_ot' => [
                    'required',
                    'numeric',
                    'digits:5',
                    Rule::unique('carga_fotos', 'fot_ot')->ignore($foto->fot_cod, 'fot_cod')
                ],
                'fot_costo' => 'required|numeric',
                'fot_venta' => 'required|numeric',
                'fot_desc' => 'required|string|max:120',
                'linea_cod' => 'required|integer|exists:linea,linea_cod',
                'fot_img' => 'nullable|image|max:4096',
            ], [
                'fot_ot.unique' => 'La OT ingresada ya fue cargada, ingrese uno nuevo!',
                'fot_ot.required' => 'El campo OT es obligatorio.',
                'fot_ot.numeric' => 'El OT debe ser un número.',
                'fot_ot.digits' => 'El OT debe tener exactamente 5 dígitos.',
            ]);

            $foto->fot_ot = strtoupper($request->fot_ot);
            $foto->fot_costo = $request->fot_costo;
            $foto->fot_venta = $request->fot_venta;
            $foto->fot_desc  = strtoupper($request->fot_desc);
            $foto->linea_cod = $request->linea_cod;

            if ($request->hasFile('fot_img')) {

                if ($foto->fot_img && Storage::exists('public/fotos/' . $foto->fot_img)) {

                    Storage::delete('public/fotos/' . $foto->fot_img);
                }

                $file = $request->file('fot_img');

                $filename = time() . '_' . uniqid() . '.' . $file->extension();

                $file->storeAs('public/fotos', $filename);

                $foto->fot_img = $filename;
            }

            $foto->save();

            Alert::success('Éxito', 'Imagen Actualizada Correctamente!');

            return redirect()->route('carga_fotos.index');
        } catch (\Illuminate\Validation\ValidationException $e) {

            $errors = $e->validator->errors()->all();
            $errorText = implode('<br>', $errors);

            Alert::info('Error al actualizar', $errorText);

            return redirect()->back()->withInput();
        } catch (\Exception $e) {

            Alert::error('Error', 'Ocurrió un error al actualizar la imagen.');

            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $foto = CargaFotos::with(['linea', 'user'])->find($id);

        if (!$foto) {

            Alert::error('Error', 'Foto no encontrada!');

            return redirect()->route('carga_fotos.index');
        }

        return view('carga_fotos.show', compact('foto'));
    }

    public function destroy($id)
    {
        $foto = DB::table('carga_fotos')
            ->where('fot_cod', $id)
            ->first();

        if (!$foto) {

            Alert::error('Error', 'Foto no encontrada.');

            return redirect()->route('carga_fotos.index');
        }

        if ($foto->fot_img && Storage::exists('public/fotos/' . $foto->fot_img)) {

            Storage::delete('public/fotos/' . $foto->fot_img);
        }

        DB::table('carga_fotos')
            ->where('fot_cod', $id)
            ->delete();

        Alert::success('Éxito', 'Imagen Eliminada Correctamente!');

        return redirect()->route('carga_fotos.index');
    }
}
