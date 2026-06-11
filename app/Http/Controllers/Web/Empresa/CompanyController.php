<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
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

    public function update(Request $request)
    {
        $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $company = Auth::user()->company;
        $data = $request->only(['razon_social', 'nombre_comercial', 'direccion', 'telefono', 'email', 'web']);

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete('logos/' . $company->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);

        return back()->with('success', 'Datos de la empresa actualizados.');
    }
}
