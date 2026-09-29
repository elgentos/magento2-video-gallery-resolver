<?php

declare(strict_types=1);

namespace Elgentos\MediaGalleryVideo\Controller\Adminhtml\Video;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Elgentos\MediaGalleryVideo\Model\PosterStorage;
use Elgentos\MediaGalleryVideo\Model\VideoLocator;
use Psr\Log\LoggerInterface;

/**
 * Stores a poster frame captured from a video in the browser.
 *
 * There is no ffmpeg on the stack, so the frame cannot be extracted server side. The browser grabs
 * it from a <video> element onto a canvas - on the video's gallery tile, or in any editor field
 * that shows the video - and posts the result here, where it is written next to the video.
 *
 * Login, the ACL check on ADMIN_RESOURCE and the form key check are done by the backend framework
 * before execute() runs.
 */
class Poster extends Action implements HttpPostActionInterface
{
    /**
     * Writing a poster is writing a file into the gallery, so it takes the same permission as an
     * upload.
     */
    public const string ADMIN_RESOURCE = 'Magento_MediaGalleryUiApi::upload_assets';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly VideoLocator $videoLocator,
        private readonly PosterStorage $posterStorage,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        $src = (string) $this->getRequest()->getParam('src');
        $image = (string) $this->getRequest()->getParam('image');

        try {
            if ($this->videoLocator->getFile($src) === null) {
                throw new LocalizedException(__('The video could not be found in the media gallery.'));
            }

            return $result->setData(['success' => true, 'poster' => $this->posterStorage->save($src, $image)]);
        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->logger->error('Could not store the video poster: ' . $e->getMessage(), ['exception' => $e]);

            return $result->setData(['success' => false, 'message' => (string) __('The poster could not be stored.')]);
        }
    }
}
