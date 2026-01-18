<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ProjectResource\RelationManagers\MembersRelationManager;
use App\Models\Project;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('description')
                ->maxLength(500),
            Select::make('workspace_id')
                ->relationship('workspace', 'name')
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('workspace.name')->label('Workspace'),
                TextColumn::make('created_at')->dateTime(),
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
            ->whereHas('workspace.members', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
