<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use RuntimeException;

final class LicenseProviderService
{
    public function apply(Model $owner, Authenticatable $actor, string $businessName): LicenseProvider
    {
        $businessName = trim($businessName);

        if (! $owner->exists || $owner->getKey() === null
            || $businessName === '' || mb_strlen($businessName) > 150
        ) {
            throw new InvalidArgumentException('Provider owner and business name are required.');
        }

        if (! Gate::forUser($actor)->allows('applyForLicenseProvider', $owner)) {
            throw new AuthorizationException('Cannot submit a reseller application.');
        }

        return LicenseProvider::query()->firstOrCreate([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => (string) $owner->getKey(),
        ], [
            'business_name' => $businessName,
            'status' => 'pending',
        ]);
    }

    public function approve(LicenseProvider $provider, Authenticatable $admin): LicenseProvider
    {
        return $this->transition($provider, $admin, 'approved');
    }

    public function suspend(LicenseProvider $provider, Authenticatable $admin): LicenseProvider
    {
        return $this->transition($provider, $admin, 'suspended');
    }

    private function transition(LicenseProvider $provider, Authenticatable $admin, string $status): LicenseProvider
    {
        if (! Gate::forUser($admin)->allows('manageLicenseProviders', $provider)) {
            throw new AuthorizationException('Only an authorized billing administrator may approve resellers.');
        }

        return DB::transaction(function () use ($provider, $status): LicenseProvider {
            $record = LicenseProvider::query()->lockForUpdate()->findOrFail($provider->getKey());

            if (! in_array($record->status, ['pending', 'approved', 'suspended'], true)) {
                throw new RuntimeException('Provider state cannot be changed.');
            }

            $record->update([
                'status' => $status,
                'approved_at' => $status === 'approved' ? ($record->approved_at ?? now()) : $record->approved_at,
                'suspended_at' => $status === 'suspended' ? now() : null,
            ]);

            return $record;
        });
    }
}
