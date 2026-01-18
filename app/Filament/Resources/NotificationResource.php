<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->searchable(),
                TextColumn::make('user.email')->label('User'),
                TextColumn::make('read_at')->dateTime(),
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
            ->whereHas('user.workspaces', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereIn('workspace_user.role', ['owner', 'admin']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
