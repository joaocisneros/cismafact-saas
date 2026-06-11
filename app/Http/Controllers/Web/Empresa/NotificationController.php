<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);

        // Marcar como leidas al verlas.
        Auth::user()->unreadNotifications->markAsRead();

        return view('empresa.notifications.index', compact('notifications'));
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('empresa.notifications.index');

        return redirect($url);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }
}
