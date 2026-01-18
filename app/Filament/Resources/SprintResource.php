<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SprintResource\Pages\CreateSprint;
use App\Filament\Resources\SprintResource\Pages\EditSprint;
use App\Filament\Resources\SprintResource\Pages\ListSprints;
use App\Models\Sprint;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class SprintResource extends Resource
{
    protected static ?string $model = Sprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('project_id')
                ->relationship('project', 'name')
                ->searchable()
                ->required(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('goal')
                ->maxLength(500),
            Select::make('status')
                ->options([
                    'planned' => 'Planned',
                    'active' => 'Active',
                    'completed' => 'Completed',
                ])
                ->required(),
            DateTimePicker::make('starts_at'),
            DateTimePicker::make('ends_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('status'),
                TextColumn::make('starts_at')->dateTime(),
                TextColumn::make('ends_at')->dateTime(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->whereHas('project.workspace.members', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSprints::route('/'),
            'create' => CreateSprint::route('/create'),
            'edit' => EditSprint::route('/{record}/edit'),
        ];
    }
}
