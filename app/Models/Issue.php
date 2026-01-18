<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    use HasFactory, HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'description',
        'position',
        'board_id',
        'column_id',
        'assignee_id',
        'priority',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'column_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'issue_label');
    }

    public function sprintAssignment(): HasOne
    {
        return $this->hasOne(IssueSprint::class);
    }

    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'issue_sprint')
            ->withPivot('position')
            ->withTimestamps();
    }
}
