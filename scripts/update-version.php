<?php

/**
 * Version Update Script for DVIDS PHP SDK
 * 
 * This script updates the SDK version constant in the Version.php file.
 * It can be used manually or by CI/CD systems during releases.
 * 
 * Usage:
 *   php scripts/update-version.php 2.1.0
 *   php scripts/update-version.php --current (shows current version)
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DvidsApi\Version;

function showUsage(): void
{
    echo "Usage:\n";
    echo "  php scripts/update-version.php <version>     Update version\n";
    echo "  php scripts/update-version.php --current     Show current version\n";
    echo "  php scripts/update-version.php --help        Show this help\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php scripts/update-version.php 2.1.0\n";
    echo "  php scripts/update-version.php 1.0.0-beta.1\n";
}

function validateSemanticVersion(string $version): bool
{
    // Allow semantic versions with optional pre-release and build metadata
    // Examples: 1.0.0, 1.0.0-alpha, 1.0.0-beta.1, 1.0.0+build.1
    $pattern = '/^(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z\-\.]+))?(?:\+([0-9A-Za-z\-\.]+))?$/';
    return preg_match($pattern, $version) === 1;
}

function getCurrentVersion(): string
{
    return Version::SDK_VERSION;
}

function updateVersionInFile(string $newVersion): bool
{
    $versionFile = __DIR__ . '/../src/Version.php';
    
    if (!file_exists($versionFile)) {
        echo "Error: Version file not found at $versionFile\n";
        return false;
    }
    
    $content = file_get_contents($versionFile);
    if ($content === false) {
        echo "Error: Could not read version file\n";
        return false;
    }
    
    // Update the version constant
    $pattern = "/(public const SDK_VERSION = ')([^']+)(')/";
    $replacement = "$1$newVersion$3";
    $newContent = preg_replace($pattern, $replacement, $content);
    
    if ($newContent === null || $newContent === $content) {
        echo "Error: Could not update version in file\n";
        return false;
    }
    
    $result = file_put_contents($versionFile, $newContent);
    if ($result === false) {
        echo "Error: Could not write updated version file\n";
        return false;
    }
    
    return true;
}

function main(): void
{
    global $argv;
    
    if (count($argv) < 2) {
        showUsage();
        exit(1);
    }
    
    $argument = $argv[1];
    
    switch ($argument) {
        case '--help':
        case '-h':
            showUsage();
            exit(0);
            
        case '--current':
        case '-c':
            echo "Current version: " . getCurrentVersion() . "\n";
            exit(0);
            
        default:
            $newVersion = $argument;
            
            if (!validateSemanticVersion($newVersion)) {
                echo "Error: Invalid version format '$newVersion'\n";
                echo "Version must follow semantic versioning (e.g., 1.0.0, 1.0.0-beta.1)\n";
                exit(1);
            }
            
            $currentVersion = getCurrentVersion();
            echo "Current version: $currentVersion\n";
            echo "New version: $newVersion\n";
            
            if ($currentVersion === $newVersion) {
                echo "Version is already set to $newVersion\n";
                exit(0);
            }
            
            if (updateVersionInFile($newVersion)) {
                echo "✓ Successfully updated version to $newVersion\n";
                
                // Verify the update worked
                // Clear the autoloader cache and reload
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
                
                // Re-require the updated file to check
                $versionFile = __DIR__ . '/../src/Version.php';
                $updatedContent = file_get_contents($versionFile);
                if (strpos($updatedContent, "SDK_VERSION = '$newVersion'") !== false) {
                    echo "✓ Version update verified\n";
                } else {
                    echo "⚠ Warning: Could not verify version update\n";
                }
                
                exit(0);
            } else {
                echo "✗ Failed to update version\n";
                exit(1);
            }
    }
}

main();