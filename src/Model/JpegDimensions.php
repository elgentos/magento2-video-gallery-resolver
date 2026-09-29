<?php

declare(strict_types=1);

namespace Elgentos\GalleryMp4Solver\Model;

/**
 * Reads the pixel size of a JPEG held in memory.
 *
 * Only the header is parsed, so a JPEG that declares enormous dimensions costs nothing to inspect,
 * unlike decoding it with GD. getimagesizefromstring() raises a warning on data it cannot parse at
 * all, which Magento's error handler turns into an exception, so anything that does not start with
 * the JPEG start-of-image marker is turned away before it gets there.
 */
class JpegDimensions
{
    private const string START_OF_IMAGE = "\xFF\xD8\xFF";

    /**
     * @param string $contents Raw file contents.
     *
     * @return array{width: int, height: int}|null Null when the data is not a readable JPEG.
     */
    public function get(string $contents): ?array
    {
        if (!str_starts_with($contents, self::START_OF_IMAGE)) {
            return null;
        }

        $size = getimagesizefromstring($contents);

        if ($size === false || $size[2] !== IMAGETYPE_JPEG || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }
}
