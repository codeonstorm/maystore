<?php

namespace Zoyica\ZoyicaVisitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorEvent extends Model
{
    protected $table = 'zv_events';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id', 'session_id');
    }
}
