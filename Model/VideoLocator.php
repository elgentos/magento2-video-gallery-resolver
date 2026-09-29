<?php

declare(strict_types=1);

namespace Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves video files inside the Magento media directory.
 *
 * Videos are ordinary media gallery assets, so browsing and uploading are the gallery's job. What
 * is left here is turning a stored media relative path back into a usable file, and the small
 * facts (extension, MIME type, poster path) that the gallery integration needs.
 */
class VideoLocator
{
    /**
     * Appended to a video's path to get its poster image.
     */
    public const string POSTER_SUFFIX = '.jpg';

    /**
     * Covers the poster image extensions as well as the video ones, since a poster is resolved
     * through the same lookup as the video it belongs to.
     */
    private const array MIME_TYPES = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * @param Filesystem            $filesystem
     * @param StoreManagerInterface $storeManager
     * @param File                  $file
     * @param string[]              $extensions   Allowed video extensions, lower case and without a leading dot.
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly StoreManagerInterface $storeManager,
        private readonly File $file,
        private readonly array $extensions = ['mp4']
    ) {
    }

    /**
     * Turn a stored media relative path into the data used by the field and the frontend.
     *
     * @param string $path         Media relative path.
     * @param bool   $requireVideo False to resolve a companion file such as a poster image.
     *
     * @return array{src: string, name: string, size: int, type: string, url: string}|null Null when
     *     the path is not an existing, allowed file.
     *
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    public function getFile(string $path, bool $requireVideo = true): ?array
    {
        $path = $this->normalizePath($path, $requireVideo);

        if ($path === null) {
            return null;
        }

        $media = $this->getMediaDirectory();

        if (!$media->isExist($path) || !$media->isFile($path)) {
            return null;
        }

        $stat = $media->stat($path);

        return [
            'src' => $path,
            'name' => $this->getFileName($path),
            'size' => (int) ($stat['size'] ?? 0),
            'type' => $this->getMimeType($path),
            'url' => $this->getMediaUrl($path),
        ];
    }

    /**
     * Whether a path points at one of the supported video types, by extension.
     */
    public function isVideoFile(string $path): bool
    {
        return in_array($this->getExtension($path), $this->extensions, true);
    }

    /**
     * MIME type for a single file, by extension.
     */
    public function getMimeType(string $path): string
    {
        return self::MIME_TYPES[$this->getExtension($path)] ?? 'application/octet-stream';
    }

    /**
     * Media relative path of the poster image belonging to a video.
     *
     * The poster sits next to the video with the same name plus a .jpg suffix, so the pair stays
     * together and the poster is discoverable without storing a second reference.
     */
    public function getPosterPath(string $videoPath): string
    {
        return $videoPath . self::POSTER_SUFFIX;
    }

    /**
     * Whether a path is the poster image belonging to a video, by name.
     */
    public function isPosterFile(string $path): bool
    {
        return str_ends_with(strtolower($path), self::POSTER_SUFFIX)
            && $this->isVideoFile(substr($path, 0, -strlen(self::POSTER_SUFFIX)));
    }

    /**
     * File name of a path, without its directories.
     */
    public function getFileName(string $path): string
    {
        return (string) ($this->file->getPathInfo($path)['basename'] ?? '');
    }

    /**
     * Public URL of a media relative path.
     *
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    public function getMediaUrl(string $path): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions(): array
    {
        return array_values($this->extensions);
    }

    /**
     * Validate a media relative path.
     *
     * Only guards against traversing out of the media directory. It does not check the media
     * gallery's allowed folders (system/media_storage_configuration/allowed_resources).
     */
    private function normalizePath(string $path, bool $requireVideo): ?string
    {
        $path = ltrim(trim($path), '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return !$requireVideo || $this->isVideoFile($path) ? $path : null;
    }

    private function getExtension(string $path): string
    {
        return strtolower((string) ($this->file->getPathInfo($path)['extension'] ?? ''));
    }

    private function getMediaDirectory(): ReadInterface
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }
}
