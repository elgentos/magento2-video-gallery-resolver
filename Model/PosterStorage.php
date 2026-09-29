<?php

declare(strict_types=1);

namespace Elgentos\MediaGalleryVideo\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\MediaGalleryApi\Api\DeleteAssetsByPathsInterface;
use Magento\MediaGallerySynchronizationApi\Model\ImportFilesInterface;
use Elgentos\MediaGalleryVideo\Model\JpegDimensions;
use Elgentos\MediaGalleryVideo\Model\VideoLocator;

/**
 * Writes a video's poster frame next to the video.
 *
 * The poster is the video's thumbnail and nothing more, so it is not registered as a gallery asset
 * of its own (see Plugin\SkipPosterImport). The gallery shows it on the video's tile instead.
 */
class PosterStorage
{
    /**
     * Guards against a malformed or oversized data URI being written to disk. A JPEG frame of a
     * 1080p video is well under this.
     */
    private const int MAX_POSTER_BYTES = 4194304;

    private const string DATA_URI_PREFIX = 'data:image/jpeg;base64,';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly VideoLocator $videoLocator,
        private readonly JpegDimensions $jpegDimensions,
        private readonly ImportFilesInterface $importFiles,
        private readonly DeleteAssetsByPathsInterface $deleteAssetsByPaths
    ) {
    }

    /**
     * @param string $videoPath Media relative path of the video the poster belongs to.
     * @param string $dataUri   The captured frame as a base64 JPEG data URI.
     *
     * @return array{src: string, url: string}
     *
     * @throws LocalizedException When the data URI is not an acceptable JPEG.
     * @throws FileSystemException When the poster cannot be written.
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    public function save(string $videoPath, string $dataUri): array
    {
        $contents = $this->decode($dataUri);
        $posterPath = $this->videoLocator->getPosterPath($videoPath);

        $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->writeFile($posterPath, $contents);

        // Posters captured before they were kept out of the gallery were imported as images.
        $this->deleteAssetsByPaths->execute([$posterPath]);

        // Re-import the video so its asset takes the real dimensions from the poster, in place of
        // the 16:9 default it was given on upload.
        $this->importFiles->execute([$videoPath]);

        return [
            'src' => $posterPath,
            'url' => $this->videoLocator->getMediaUrl($posterPath),
        ];
    }

    /**
     * @throws LocalizedException
     */
    private function decode(string $dataUri): string
    {
        if (!str_starts_with($dataUri, self::DATA_URI_PREFIX)) {
            throw new LocalizedException(__('The poster must be a base64 encoded JPEG.'));
        }

        $encoded = substr($dataUri, strlen(self::DATA_URI_PREFIX));

        // Base64 is four characters per three bytes, so an oversized poster is refused before it
        // is decoded.
        if (strlen($encoded) > 4 * (int) ceil(self::MAX_POSTER_BYTES / 3)) {
            throw new LocalizedException(__('The poster is too large.'));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- decoding a posted data URI, the framework has no alternative
        $contents = base64_decode($encoded, true);

        if ($contents === false || $contents === '') {
            throw new LocalizedException(__('The poster could not be decoded.'));
        }

        // Reject anything that is not actually a JPEG, whatever the data URI claimed.
        if ($this->jpegDimensions->get($contents) === null) {
            throw new LocalizedException(__('The poster is not a valid image.'));
        }

        return $contents;
    }
}
