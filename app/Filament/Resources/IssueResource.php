<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssueResource\Pages\ListIssues;
use App\Models\Issue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('board.project.workspace.name')->label('Workspace'),
                TextColumn::make('board.project.name')->label('Project'),
                TextColumn::make('board.name')->label('Board'),
                TextColumn::make('column.name')->label('Column'),
                TextColumn::make('assignee.name')->label('Assignee'),
                TextColumn::make('priority'),
                TextColumn::make('due_at')->dateTime(),
                TextColumn::make('updated_at')->dateTime(),
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
            ->whereHas('board.project.workspace.members', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssues::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
