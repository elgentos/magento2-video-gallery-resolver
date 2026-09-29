<?php

declare(strict_types=1);

namespace Elgentos\GalleryMp4Solver\Plugin;

use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\Exception\LocalizedException;
use Elgentos\GalleryMp4Solver\Model\VideoLocator;

/**
 * Deletes a video's poster along with the video.
 *
 * The poster is not a gallery asset (see SkipPosterImport), so nothing in the gallery would ever
 * offer to delete it and it would be left behind on disk. Storage::deleteFile() is what the
 * gallery's delete action ends up calling for every asset.
 */
class DeleteVideoPoster
{
    public function __construct(
        private readonly VideoLocator $videoLocator
    ) {
    }

    /**
     * @param Storage $subject
     * @param Storage $result  Storage::deleteFile() returns the storage itself.
     * @param string  $target  Absolute path of the deleted file.
     *
     * @return Storage
     *
     * @throws LocalizedException
     */
    public function afterDeleteFile(Storage $subject, Storage $result, string $target): Storage
    {
        if ($this->videoLocator->isVideoFile($target)) {
            // Through Storage again, so the poster's .thumbs copy goes with it.
            $subject->deleteFile($this->videoLocator->getPosterPath($target));
        }

        return $result;
    }
}
