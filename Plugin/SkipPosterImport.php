<?php

declare(strict_types=1);

namespace Plugin;

use Magento\MediaGallerySynchronizationApi\Model\ImportFilesComposite;
use Model\VideoLocator;

/**
 * Keeps video posters out of the media gallery as assets of their own.
 *
 * A poster is a plain .jpg next to its video, so everything that imports files into the gallery -
 * a manual media-gallery:sync, an upload, a rename - would otherwise register it as an image and
 * show it as a second tile beside the video. The poster is only ever the video's thumbnail (see
 * VideoThumbnail), so it is dropped from every import here. Sorted ahead of core's CreateThumbnails
 * plugin, so no .thumbs copy is generated for it either.
 */
class SkipPosterImport
{
    public function __construct(
        private readonly VideoLocator $videoLocator
    ) {
    }

    /**
     * @param ImportFilesComposite $subject
     * @param string[]             $paths   Media relative paths about to be imported.
     *
     * @return array{string[]}
     */
    public function beforeExecute(ImportFilesComposite $subject, array $paths): array
    {
        $paths = array_filter(
            $paths,
            fn (string $path): bool => !$this->videoLocator->isPosterFile($path)
        );

        return [array_values($paths)];
    }
}
