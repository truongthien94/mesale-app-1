<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonInterface;

class ReferralOnboardingService
{
    public const ELIGIBILITY_HOURS = 72;

    /**
     * Referral fields for a newly registered member.
     *
     * @return array{referral_prompt_decided_at: CarbonInterface|null, referral_code_eligible_until: CarbonInterface|null}
     */
    public function registrationAttributes(?int $referredBy = null): array
    {
        if ($referredBy !== null) {
            return [
                'referral_prompt_decided_at' => now(),
                'referral_code_eligible_until' => null,
            ];
        }

        return [
            'referral_prompt_decided_at' => null,
            'referral_code_eligible_until' => now()->addHours(self::ELIGIBILITY_HOURS),
        ];
    }

    /**
     * Server-authoritative state exposed to mobile clients.
     *
     * @return array{feature_enabled: bool, linked: bool, has_deadline: bool, expired: bool, eligible: bool, pending: bool, expires_at: string|null}
     */
    public function stateFor(User $user, ?CarbonInterface $at = null): array
    {
        $at ??= now();
        $expiresAt = $user->referral_code_eligible_until;
        $featureEnabled = Setting::getVal('referral_enabled', '1') === '1';
        $linked = ! is_null($user->referred_by);
        $hasDeadline = $expiresAt instanceof CarbonInterface;
        $expired = $hasDeadline && ! $at->lt($expiresAt);
        $eligible = $featureEnabled && ! $linked && $hasDeadline && ! $expired;

        return [
            'feature_enabled' => $featureEnabled,
            'linked' => $linked,
            'has_deadline' => $hasDeadline,
            'expired' => $expired,
            'eligible' => $eligible,
            'pending' => $eligible && is_null($user->referral_prompt_decided_at),
            'expires_at' => $hasDeadline ? $expiresAt->toIso8601String() : null,
        ];
    }

    /**
     * @return array{referral_prompt_pending: bool, referral_code_eligible: bool, referral_code_expires_at: string|null}
     */
    public function apiFields(User $user): array
    {
        $state = $this->stateFor($user);

        return [
            'referral_prompt_pending' => $state['pending'],
            'referral_code_eligible' => $state['eligible'],
            'referral_code_expires_at' => $state['expires_at'],
        ];
    }
}
