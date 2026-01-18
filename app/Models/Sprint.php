<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sprint extends Model
{
    use HasFactory, HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'name',
        'goal',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_sprint')
            ->withPivot('position')
            ->withTimestamps();
    }
}
