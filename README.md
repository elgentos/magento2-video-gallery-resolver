# Media Gallery Video for Magento 2

MP4 support for the Magento 2 media gallery: upload, sync, poster thumbnails and clean deletion of video assets.

## Requirements

- PHP 8.3 or 8.4
- Magento 2.4 with the media gallery modules enabled

## Installation

The package is not on Packagist. Add this repository to your project as a VCS repository, then require it:

```bash
composer config repositories.media-gallery-video vcs https://github.com/moods777/video-resolver-magento.git
composer require elgentos/module-media-gallery-video:^1.0
bin/magento setup:upgrade
```

To install the latest commit instead of a release, require `dev-main`.

## License

MIT, see [LICENSE](LICENSE).
