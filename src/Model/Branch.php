<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * US Armed Forces branches
 */
enum Branch: string
{
    case ARMY = 'army';
    case NAVY = 'navy';
    case AIR_FORCE = 'air-force';
    case MARINES = 'marines';
    case COAST_GUARD = 'coast-guard';
    case SPACE_FORCE = 'space-force';
    case JOINT = 'joint';
    case CIVILIAN = 'civilian';

    /**
     * Get the display name for the branch
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ARMY => 'Army',
            self::NAVY => 'Navy',
            self::AIR_FORCE => 'Air Force',
            self::MARINES => 'Marines',
            self::COAST_GUARD => 'Coast Guard',
            self::SPACE_FORCE => 'Space Force',
            self::JOINT => 'Joint',
            self::CIVILIAN => 'Civilian'
        };
    }
}