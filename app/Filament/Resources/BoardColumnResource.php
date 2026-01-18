<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoardColumnResource\Pages\CreateBoardColumn;
use App\Filament\Resources\BoardColumnResource\Pages\EditBoardColumn;
use App\Filament\Resources\BoardColumnResource\Pages\ListBoardColumns;
use App\Models\BoardColumn;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class BoardColumnResource extends Resource
{
    protected static ?string $model = BoardColumn::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-view-columns';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('board_id')
                ->relationship('board', 'name')
                ->searchable()
                ->required(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('key')
                ->required()
                ->maxLength(255),
            TextInput::make('position')
                ->numeric()
                ->required(),
            TextInput::make('wip_limit')
                ->numeric()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('key'),
                TextColumn::make('position'),
                TextColumn::make('wip_limit'),
            TextColumn::make('board.name')->label('Board'),
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
            ->whereHas('board.project.workspace.members', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBoardColumns::route('/'),
            'create' => CreateBoardColumn::route('/create'),
            'edit' => EditBoardColumn::route('/{record}/edit'),
        ];
    }
}
