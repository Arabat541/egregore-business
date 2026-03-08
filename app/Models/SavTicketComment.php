<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavTicketComment extends Model
{
    protected $fillable = [
        'sav_ticket_id',
        'user_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(SavTicket::class, 'sav_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
