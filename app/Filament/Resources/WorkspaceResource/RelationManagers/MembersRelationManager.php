<?php

namespace App\Filament\Resources\WorkspaceResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('role')
                ->options([
                    'owner' => 'Owner',
                    'admin' => 'Admin',
                    'member' => 'Member',
                ])
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('pivot.role')->label('Role'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->form([
                        Select::make('role')
                            ->options([
                                'owner' => 'Owner',
                                'admin' => 'Admin',
                                'member' => 'Member',
                            ])
                            ->required(),
                    ]),
            ])
            ->actions([
                DetachAction::make(),
            ]);
    }
}
