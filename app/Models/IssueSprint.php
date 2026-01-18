<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueSprint extends Model
{
    protected $table = 'issue_sprint';

    public $incrementing = false;

    protected $primaryKey = 'issue_id';

    protected $keyType = 'string';

    protected $fillable = [
        'issue_id',
        'sprint_id',
        'position',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }
}
