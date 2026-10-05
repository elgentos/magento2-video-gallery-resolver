# Gallery MP4 Solver for Magento 2

MP4 support for the Magento 2 media gallery: upload, sync, poster thumbnails and clean deletion of video assets.

## Requirements

- PHP 8.3 or 8.4
- Magento 2.4 with the media gallery modules enabled

## Installation

The package is not on Packagist. Add this repository to your project as a VCS repository, then require it:

```bash
composer require elgentos/magento2-gallery-mp4-solver:^1.0.6
bin/magento setup:upgrade
```

## Media on a separate host

When the media base URL points at another host, such as a CDN, the admin still loads videos and posters from its own host (`/media/...`, or `/pub/media/...` when that is the media URL path). Browsers cannot capture a poster frame from a cross-origin video, and a poster that was just written to disk may not be on the CDN yet. So the admin host must serve the media directory itself. The storefront keeps using the configured media URL.

## License

MIT, see [LICENSE](LICENSE).
