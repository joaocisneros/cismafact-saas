<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ConsultaLlave;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Da y quita el acceso al panel a los titulares de RUC y DNI.
 *
 * El acceso cuelga del titular y no de la llave: quien tiene dos llaves de
 * produccion entra una sola vez y ve las dos. Atarlo a la llave le habria dado
 * dos contraseñas para lo mismo.
 *
 * La contraseña la pone quien crea el acceso y se enseña una vez, para
 * pasarsela. No se manda por correo: eso ataria el alta a que el correo del
 * sistema este configurado, y el dia que falle nadie podria entrar.
 */
class AccesoConsultasController extends Controller
{
    public function crear(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:150', 'unique:users,email'],
            'clave' => ['required', 'string', 'min:8'],
            'llaves' => ['required', 'array', 'min:1'],
            'llaves.*' => ['integer', 'exists:consulta_llaves,id'],
        ], [
            'correo.unique' => 'Ese correo ya tiene una cuenta en el sistema.',
            'clave.min' => 'La contraseña necesita al menos 8 caracteres.',
        ]);

        $rol = Role::where('name', 'cliente_consultas')->firstOrFail();

        $usuario = User::create([
            'name' => $datos['nombre'],
            'email' => $datos['correo'],
            'password' => Hash::make($datos['clave']),
            'role_id' => $rol->id,
            'company_id' => null,
            'user_type' => 'user',
            'active' => true,
        ]);

        // Solo las de produccion: las de Sandbox son para probar y no dan
        // acceso al sistema, asi que aunque llegaran en la peticion no se
        // asignan.
        ConsultaLlave::whereIn('id', $datos['llaves'])
            ->where('entorno', 'produccion')
            ->update(['usuario_id' => $usuario->id, 'titular_email' => $datos['correo']]);

        return back()->with('acceso_creado', [
            'titular' => $datos['nombre'],
            'correo' => $datos['correo'],
            'clave' => $datos['clave'],
        ]);
    }

    /**
     * Le pone una contraseña nueva y la enseña una vez.
     *
     * Sirve para cuando la pierde: no hay forma de recuperar la que tenia
     * —esta cifrada de ida y vuelta pero no se guarda en claro— asi que lo que
     * se hace es cambiarla.
     */
    public function nuevaClave(int $usuario)
    {
        $cliente = $this->clienteDeConsultas($usuario);
        $clave = Str::password(12, symbols: false);

        $cliente->update(['password' => Hash::make($clave)]);

        return back()->with('acceso_creado', [
            'titular' => $cliente->name,
            'correo' => $cliente->email,
            'clave' => $clave,
        ]);
    }

    /**
     * Le quita el acceso al panel.
     *
     * Sus llaves siguen funcionando: la API no se corta. Para cortarle el
     * servicio se bloquea la llave, en «Mis APIs». Son cosas distintas y
     * mezclarlas dejaria sin servicio a quien solo queria dejar de mirar.
     */
    public function quitar(int $usuario)
    {
        $cliente = $this->clienteDeConsultas($usuario);

        ConsultaLlave::where('usuario_id', $cliente->id)->update(['usuario_id' => null]);
        $cliente->delete();

        return back()->with('success', "«{$cliente->name}» ya no puede entrar. Sus llaves siguen funcionando.");
    }

    /**
     * El usuario, comprobando que sea de los de consultas.
     *
     * Sin esto, estas rutas serian una puerta para borrarle la cuenta a
     * cualquiera del sistema pasando su id.
     */
    private function clienteDeConsultas(int $id): User
    {
        $usuario = User::findOrFail($id);

        abort_unless($usuario->esClienteDeConsultas(), 403,
            'Ese usuario no es un cliente de RUC y DNI.');

        return $usuario;
    }
}
