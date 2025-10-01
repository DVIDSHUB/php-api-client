<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Photo status in the system
 */
enum PhotoStatus: string
{
    case UPLOADED = 'uploaded';
    case PENDING_PROCESSING = 'pending-processing';
    case ARCHIVED = 'archived';
    case NEEDS_APPROVAL = 'needs-approval';
    case PUBLISHED = 'published';

    /**
     * Get the display name for the status
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::UPLOADED => 'Uploaded',
            self::PENDING_PROCESSING => 'Pending Processing',
            self::ARCHIVED => 'Archived',
            self::NEEDS_APPROVAL => 'Needs Approval',
            self::PUBLISHED => 'Published'
        };
    }
}