<?php

declare(strict_types=1);

if (! class_exists('ERP_OMD_Period_Service')) {
    final class ERP_OMD_Period_Service
    {
        public const STATUS_LIVE = 'LIVE';
        public const STATUS_DO_ROZLICZENIA = 'DO_ROZLICZENIA';
        public const STATUS_ZAMKNIETY = 'ZAMKNIETY';

        /**
         * @return array{ready: bool, blockers: array<int, string>}
         */
        public function build_readiness_checklist(array $checks): array
        {
            $blockers = [];

            foreach ($checks as $key => $is_ok) {
                if (! $is_ok) {
                    $blockers[] = (string) $key;
                }
            }

            return [
                'ready' => $blockers === [],
                'blockers' => $blockers,
            ];
        }

        public function can_transition(string $current_status, string $target_status): bool
        {
            if ($current_status === self::STATUS_LIVE && $target_status === self::STATUS_DO_ROZLICZENIA) {
                return true;
            }

            if ($current_status === self::STATUS_DO_ROZLICZENIA && $target_status === self::STATUS_ZAMKNIETY) {
                return true;
            }

            return false;
        }

        public function assert_transition_allowed(string $current_status, string $target_status, bool $ready_for_settlement): void
        {
            if (! $this->can_transition($current_status, $target_status)) {
                throw new InvalidArgumentException('Niedozwolone przejście statusu miesiąca.');
            }

            if (
                $current_status === self::STATUS_LIVE &&
                $target_status === self::STATUS_DO_ROZLICZENIA &&
                ! $ready_for_settlement
            ) {
                throw new InvalidArgumentException('Miesiąc nie spełnia checklisty gotowości do rozliczenia.');
            }
        }

        /**
         * @return array{closed_at: string, correction_window_until: string}
         */
        public function build_closure_timestamps(DateTimeImmutable $closed_at): array
        {
            $window_until = $closed_at->modify('+72 hours');

            return [
                'closed_at' => $closed_at->format('Y-m-d H:i:s'),
                'correction_window_until' => $window_until->format('Y-m-d H:i:s'),
            ];
        }

        public function is_month_locked_for_regular_user(string $status): bool
        {
            return $status === self::STATUS_DO_ROZLICZENIA || $status === self::STATUS_ZAMKNIETY;
        }

        public function is_emergency_adjustment_required(DateTimeImmutable $now, DateTimeImmutable $correction_window_until): bool
        {
            return $now > $correction_window_until;
        }
    }
}
