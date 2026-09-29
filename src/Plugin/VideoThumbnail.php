<?php

declare(strict_types=1);

namespace Elgentos\GalleryMp4Solver\Plugin;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\MediaGalleryUi\Model\GetDetailsByAssetId;
use Magento\MediaGalleryUi\Ui\Component\Listing\Columns\Url;
use Elgentos\GalleryMp4Solver\Model\VideoLocator;

/**
 * Points the media gallery at a video's poster wherever it would otherwise show an image.
 *
 * The gallery builds a thumbnail URL with Storage::getThumbnailUrl($path, $checkFile = false),
 * which composes a .thumbs/... path without checking that anything is there. Videos never get a
 * thumbnail generated (see SkipVideoResize), so the tile requests a file that does not exist and
 * falls back to a placeholder. The captured poster is used instead.
 */
class VideoThumbnail
{
    public function __construct(
        private readonly VideoLocator $videoLocator,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * Grid tiles: add the video's URL and its poster alongside the row, for the tile template, plus
     * where to post a poster captured on the tile when the video does not have one yet.
     *
     * The thumbnail_url is deliberately left as core computed it. It points at the (non-existent)
     * .thumbs path for the video, and deleteImages.js derives the delete target from it by
     * stripping ".thumbs" off the tile image's src. Substituting the poster there would delete
     * the poster instead of the video, and blanking it produced `new URL('')` - the "Invalid URL"
     * crash. The template keeps that value on a hidden image and shows the poster separately.
     *
     * @param Url                  $subject
     * @param array<string, mixed> $result  UI component data source.
     *
     * @return array<string, mixed>
     *
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    public function afterPrepareDataSource(Url $subject, array $result): array
    {
        if (!isset($result['data']['items']) || !is_array($result['data']['items'])) {
            return $result;
        }

        $posterSaveUrl = null;

        foreach ($result['data']['items'] as &$item) {
            $path = (string) ($item['path'] ?? '');

            if (!$this->videoLocator->isVideoFile($path)) {
                continue;
            }

            $posterSaveUrl ??= $this->url->getUrl('elgentos_gallerymp4solver/video/poster');

            $item['video_url'] = $this->videoLocator->getFile($path)['url'] ?? '';
            $item['video_poster_url'] = $this->getPosterUrl($path) ?? '';
            $item['video_poster_save_url'] = $posterSaveUrl;
        }

        unset($item);

        return $result;
    }

    /**
     * Details panel: the same swap, so the preview beside a selected video is its poster rather
     * than an <img> pointed at an mp4.
     *
     * @param GetDetailsByAssetId                      $subject
     * @param array<int|string, array<string, mixed>> $result  Asset details keyed by asset id.
     *
     * @return array<int|string, array<string, mixed>>
     *
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    public function afterExecute(GetDetailsByAssetId $subject, array $result): array
    {
        foreach ($result as &$details) {
            $path = (string) ($details['path'] ?? '');
            $poster = $this->videoLocator->isVideoFile($path) ? $this->getPosterUrl($path) : null;

            if ($poster !== null) {
                $details['image_url'] = $poster;
            }
        }

        unset($details);

        return $result;
    }

    /**
     * @throws NoSuchEntityException When the current store cannot be resolved.
     */
    private function getPosterUrl(string $videoPath): ?string
    {
        $poster = $this->videoLocator->getFile($this->videoLocator->getPosterPath($videoPath), false);

        return $poster['url'] ?? null;
    }
}
