<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')->searchable(),
                TextColumn::make('workspace.name')->label('Workspace'),
                TextColumn::make('actor.name')->label('Actor'),
                TextColumn::make('subject_type')->label('Subject'),
                TextColumn::make('subject_id')->label('Subject ID'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->whereHas('workspace.members', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
