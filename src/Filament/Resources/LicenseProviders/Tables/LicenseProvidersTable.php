<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\Tables;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Services\LicenseProviderService;

final class LicenseProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('business_name')->searchable(),
            TextColumn::make('owner_type')->label('Owner type'),
            TextColumn::make('owner_id')->label('Owner ID'),
            TextColumn::make('status')->badge(),
            TextColumn::make('approved_at')->dateTime(),
            TextColumn::make('created_at')->dateTime(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'suspended' => 'Suspended',
                ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->requiresConfirmation()
                    ->visible(fn (LicenseProvider $record): bool => $record->status !== 'approved')
                    ->action(fn (LicenseProvider $record) => app(LicenseProviderService::class)->approve($record, Filament::auth()->user())),
                Action::make('suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LicenseProvider $record): bool => $record->status === 'approved')
                    ->action(fn (LicenseProvider $record) => app(LicenseProviderService::class)->suspend($record, Filament::auth()->user())),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
