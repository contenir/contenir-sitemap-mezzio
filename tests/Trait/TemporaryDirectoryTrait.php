<?php

declare(strict_types=1);

namespace Contenir\Sitemap\Mezzio\Tests\Trait;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_reverse;
use function chdir;
use function chmod;
use function getcwd;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function umask;
use function uniqid;
use function unlink;

/**
 * A private scratch directory per test, removed afterwards even when a test
 * left read-only directories or files, or symlinks out of it, behind. Symlinks
 * are removed, never followed.
 *
 * The umask is fixed at 022 for the test and restored afterwards.
 *
 * The test also runs with the scratch directory as its working directory,
 * restored afterwards, so a relative path (a service default, or one a
 * mutant leaves without its directory) is written there and removed with
 * it, never into the project root.
 */
trait TemporaryDirectoryTrait
{
    private string $tmpDir;

    private int $previousUmask;

    private string $previousWorkingDirectory;

    protected function setUpTemporaryDirectory(): void
    {
        $this->previousUmask            = umask(0o022);
        $this->previousWorkingDirectory = (string) getcwd();
        $this->tmpDir                   = sys_get_temp_dir() . '/contenir-sitemap-' . uniqid(more_entropy: true);
        mkdir($this->tmpDir, permissions: 0o777, recursive: true);
        chdir($this->tmpDir);
    }

    protected function tearDownTemporaryDirectory(): void
    {
        umask($this->previousUmask);
        chdir($this->previousWorkingDirectory);

        if (! is_dir($this->tmpDir)) {
            return;
        }

        chmod($this->tmpDir, permissions: 0o755);
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var list<SplFileInfo> $found */
        $found = [];
        /** @var SplFileInfo $item */
        foreach ($items as $item) {
            if (! $item->isLink()) {
                chmod($item->getPathname(), permissions: $item->isDir() ? 0o755 : 0o644);
            }

            $found[] = $item;
        }

        foreach (array_reverse($found) as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->tmpDir);
    }
}
