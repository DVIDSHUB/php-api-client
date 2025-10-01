<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Graphic status in the system
 */
enum GraphicStatus: string
{
    case UPLOADED = 'uploaded';
    case PENDING_PROCESSING = 'pending-processing';
    case NEEDS_APPROVAL = 'needs-approval';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get the display name for the status
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::UPLOADED => 'Uploaded',
            self::PENDING_PROCESSING => 'Pending Processing',
            self::NEEDS_APPROVAL => 'Needs Approval',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived'
        };
    }
}