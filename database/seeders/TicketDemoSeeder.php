<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        $admin = User::where('email', 'admin@sunatapi.com')->first();
        $empresaUser = User::where('company_id', $company->id)->first();

        if (!$empresaUser) return;

        $tickets = [
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'Error al enviar factura a SUNAT',
                'message' => 'Buenos días, estoy intentando enviar una factura pero me sale el error "CREDENCIALES INVALIDAS". Ya verifiqué las credenciales SOL y están correctas. ¿Podrían ayudarme?',
                'status' => 'open',
                'priority' => 'high',
                'created_at' => now()->subDays(2),
            ],
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'Consulta sobre notas de crédito',
                'message' => 'Necesito saber cómo emitir una nota de crédito por descuento. ¿El monto debe ser negativo o positivo? No encuentro esa información en la documentación.',
                'status' => 'open',
                'priority' => 'medium',
                'created_at' => now()->subDays(1),
            ],
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'No puedo subir certificado digital',
                'message' => 'Cuando intento subir el certificado .pfx me sale error de formato. El certificado lo descargué del portal de SUNAT. ¿Qué puedo hacer?',
                'status' => 'in_progress',
                'priority' => 'high',
                'created_at' => now()->subDays(3),
            ],
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'Solicitud de aumento de límite API',
                'message' => 'Buenas tardes, necesito que me aumenten el límite de llamadas a la API. Actualmente estamos procesando más de 100 facturas diarias y el límite nos está quedando corto.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'created_at' => now()->subDays(4),
            ],
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'Cómo configurar el modo producción',
                'message' => 'Hola, ya probé todo en modo beta y funciona correctamente. Ahora quiero pasar a producción pero no encuentro dónde activar el modo. ¿Me pueden guiar?',
                'status' => 'closed',
                'priority' => 'low',
                'created_at' => now()->subDays(5),
            ],
            [
                'user_id' => $empresaUser->id,
                'company_id' => $company->id,
                'subject' => 'Reporte de lentitud en respuestas',
                'message' => 'Desde hace dos días las respuestas de la API tardan más de 10 segundos. ¿Hay algún problema con los servidores? Necesito que sea urgente porque afecta我们的 operaciones.',
                'status' => 'closed',
                'priority' => 'high',
                'created_at' => now()->subDays(6),
            ],
        ];

        foreach ($tickets as $ticketData) {
            $ticket = Ticket::create($ticketData);

            // Add some replies for in_progress and closed tickets
            if ($ticket->status === 'in_progress' || $ticket->status === 'closed') {
                DB::table('ticket_replies')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'message' => 'Hola, estamos revisando tu caso. Te responderemos a la brevedad.',
                    'is_admin' => true,
                    'created_at' => $ticket->created_at->addHours(2),
                    'updated_at' => $ticket->created_at->addHours(2),
                ]);

                DB::table('ticket_replies')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => $empresaUser->id,
                    'message' => 'Gracias, quedo pendiente.',
                    'is_admin' => false,
                    'created_at' => $ticket->created_at->addHours(4),
                    'updated_at' => $ticket->created_at->addHours(4),
                ]);
            }

            if ($ticket->status === 'closed') {
                DB::table('ticket_replies')->insert([
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'message' => 'Tu caso ha sido resuelto. Si tienes otro problema, no dudes en crear un nuevo ticket.',
                    'is_admin' => true,
                    'created_at' => $ticket->created_at->addDay(),
                    'updated_at' => $ticket->created_at->addDay(),
                ]);
            }
        }
    }
}
