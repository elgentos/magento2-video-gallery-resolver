<?php

declare(strict_types=1);

namespace Elgentos\MediaGalleryVideo\Plugin;

use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Elgentos\MediaGalleryVideo\Model\VideoLocator;

/**
 * Stops Magento generating an image thumbnail for a video file.
 *
 * Storage::resizeFile() hands the file to the image adapter, which throws on anything that is not
 * an image. It is called for every synced file (MediaGalleryUi\Plugin\CreateThumbnails) and after
 * every upload (Storage::uploadFile), in the latter case only once the file is already written -
 * so without this, uploading a video saves the file and then reports a failure.
 */
class SkipVideoResize
{
    public function __construct(
        private readonly VideoLocator $videoLocator
    ) {
    }

    /**
     * @param Storage  $subject
     * @param callable $proceed
     * @param string   $source    Path of the file to resize.
     * @param bool     $keepRatio Keep the aspect ratio.
     *
     * @return string|bool The resized file path, or false when nothing was resized.
     */
    public function aroundResizeFile(
        Storage $subject,
        callable $proceed,
        string $source,
        bool $keepRatio = true
    ): string|bool {
        if ($this->videoLocator->isVideoFile($source)) {
            return false;
        }

        return $proceed($source, $keepRatio);
    }
}
