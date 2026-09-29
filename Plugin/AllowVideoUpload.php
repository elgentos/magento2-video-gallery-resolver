<?php

declare(strict_types=1);

namespace Elgentos\MediaGalleryVideo\Plugin;

use Magento\Framework\UrlInterface;
use Magento\MediaGalleryUi\Ui\Component\ImageUploader;
use Elgentos\MediaGalleryVideo\Model\VideoLocator;

/**
 * Lets the media gallery's own uploader accept video files.
 *
 * The component hard codes its accepted types to images. The upload URL is also repointed from
 * type=image to type=media: the controller passes that through to Storage::uploadFile(), which
 * resolves it against the matching allow list, and only the "media" list has been widened to
 * cover images plus video (see etc/di.xml). Leaving it on type=image would mean widening the
 * image list, which also drives Storage::isImage() and would have the gallery treat videos as
 * images everywhere else.
 */
class AllowVideoUpload
{
    public function __construct(
        private readonly UrlInterface $url,
        private readonly VideoLocator $videoLocator
    ) {
    }

    /**
     * ImageUploader::prepare() returns void, so there is no $result to take; Magento allows an after
     * plugin to declare only the subject.
     *
     * @param ImageUploader $subject
     *
     * @return void
     */
    public function afterPrepare(ImageUploader $subject): void
    {
        $config = (array) $subject->getData('config');
        $extensions = $this->videoLocator->getAllowedExtensions();

        $config['imageUploadUrl'] = $this->url->getUrl('media_gallery/image/upload', ['type' => 'media']);
        $config['allowedExtensions'] = trim(
            ($config['allowedExtensions'] ?? '') . ' ' . implode(' ', $extensions)
        );
        $config['acceptFileTypes'] = sprintf('/(\.|\/)(gif|jpe?g|png|%s)$/i', implode('|', $extensions));

        $subject->setData('config', $config);
    }
}
