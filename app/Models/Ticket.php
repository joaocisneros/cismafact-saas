<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['user_id', 'company_id', 'subject', 'message', 'status', 'priority'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->oldest('created_at');
    }
}
