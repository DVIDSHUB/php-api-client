<?php

declare(strict_types=1);

namespace DvidsApi;

/**
 * Version information for the DVIDS API SDK
 */
class Version
{
    /**
     * Current version of the DVIDS PHP SDK
     * 
     * This version will be automatically updated by the GitHub release workflow
     */
    public const SDK_VERSION = '0.0.1';
    
    /**
     * Get the full User-Agent string for HTTP requests
     */
    public static function getUserAgent(): string
    {
        return 'DVIDS-PHP-Client/' . self::SDK_VERSION;
    }
}