<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Services\LogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = Auth::user()->company;

        return view('empresa.company.edit', compact('company'));
    }

    public function update(Request $request, LogoService $logos)
    {
        $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            // Sin SVG: se guarda en el disco publico (un SVG con <script> es XSS
            // almacenado) y las plantillas PDF solo saben incrustar PNG/JPEG.
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $company = Auth::user()->company;
        $data = $request->only(['razon_social', 'nombre_comercial', 'direccion', 'telefono', 'email', 'web']);

        if ($request->hasFile('logo')) {
            // La columna es 'logo_path', no 'logo'. Guardar en 'logo' no hacia
            // nada: no esta en el $fillable del modelo, asi que Laravel lo
            // descartaba en silencio y el PDF —que lee logo_path— seguia sin
            // logo.
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            // Se reduce al guardar: el logo va incrustado en cada PDF, y uno de
            // 1 MB convierte cada comprobante en un archivo de 1 MB.
            $data['logo_path'] = $logos->guardar($request->file('logo'), $company->id);
        }

        $company->update($data);

        return back()->with('success', 'Datos de la empresa actualizados.');
    }
}
