<?php

namespace Zoyica\ZoyicaVisitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    protected $table = 'zv_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'is_converted'  => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(VisitorEvent::class, 'session_id', 'session_id');
    }
}
