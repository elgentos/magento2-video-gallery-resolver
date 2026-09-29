<?php

declare(strict_types=1);

namespace Elgentos\MediaGalleryVideo\Model\MediaGallery;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\MediaGalleryApi\Api\Data\AssetInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterfaceFactory;
use Magento\MediaGallerySynchronization\Model\CreateAssetFromFile as ImageAssetFactory;
use Magento\MediaGallerySynchronizationApi\Model\CreateAssetFromFileInterface;
use Elgentos\MediaGalleryVideo\Model\JpegDimensions;
use Elgentos\MediaGalleryVideo\Model\VideoLocator;

/**
 * Builds media gallery assets, adding support for video files.
 *
 * The core implementation reads every file through getimagesizefromstring() and hard codes the
 * content type to image/<extension>, so it cannot describe a video. Image files are delegated to
 * it unchanged; video files are described here instead.
 */
class CreateAssetFromFile implements CreateAssetFromFileInterface
{
    /**
     * Fallback dimensions for a video, used until a poster has been captured.
     *
     * The gallery grid lays tiles out from width/height (Magento_Ui/js/grid/masonry.js), so an
     * asset stored as 0x0 produces a NaN tile width and breaks the row it sits in. A 16:9 default
     * keeps the layout correct; PosterStorage re-imports the video with the poster's real size.
     */
    private const int DEFAULT_WIDTH = 1280;
    private const int DEFAULT_HEIGHT = 720;

    public function __construct(
        private readonly ImageAssetFactory $imageAssetFactory,
        private readonly Filesystem $filesystem,
        private readonly AssetInterfaceFactory $assetFactory,
        private readonly VideoLocator $videoLocator,
        private readonly JpegDimensions $jpegDimensions
    ) {
    }

    /**
     * @param string $path Media relative path of the file.
     *
     * @return AssetInterface
     *
     * @throws FileSystemException
     * @throws ValidatorException
     */
    public function execute(string $path): AssetInterface
    {
        if (!$this->videoLocator->isVideoFile($path)) {
            return $this->imageAssetFactory->execute($path);
        }

        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $stat = $media->stat($path);
        [$width, $height] = $this->getDimensions($path);

        return $this->assetFactory->create(
            [
                'id' => null,
                'path' => $path,
                'title' => $this->videoLocator->getFileName($path),
                'width' => $width,
                'height' => $height,
                // Hashed from the file handle rather than its contents: the core helper takes the
                // whole file as a string, which for a video means loading it all into memory.
                'hash' => (string) sha1_file($media->getAbsolutePath($path)),
                'size' => (int) ($stat['size'] ?? 0),
                'contentType' => $this->videoLocator->getMimeType($path),
                'source' => 'Local',
            ]
        );
    }

    /**
     * Take the dimensions from the poster image when one sits next to the video.
     *
     * @return array{int, int}
     *
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function getDimensions(string $path): array
    {
        $poster = $this->videoLocator->getPosterPath($path);
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        if ($media->isFile($poster)) {
            $size = $this->jpegDimensions->get($media->readFile($poster));

            if ($size !== null) {
                return [$size['width'], $size['height']];
            }
        }

        return [self::DEFAULT_WIDTH, self::DEFAULT_HEIGHT];
    }
}
