/**
 * Maps our copy of the media gallery grid tile template over the core one, so video assets render
 * their poster rather than a broken <img>. Done from the module rather than a theme so it applies
 * whichever admin theme is active.
 *
 * The key is the template path without the text! prefix: RequireJS applies map to a plugin's
 * resource name, not to the full "text!..." id, so a prefixed key never matches.
 */
var config = {
    map: {
        '*': {
            'Magento_MediaGalleryUi/template/grid/columns/image.html':
                'Elgentos_GalleryMp4Solver/template/grid/columns/image.html'
        }
    },
    config: {
        mixins: {
            'Magento_MediaGalleryUi/js/grid/columns/image': {
                'Elgentos_GalleryMp4Solver/js/grid/columns/image-mixin': true
            },
            'Magento_MediaGalleryUi/js/action/deleteImages': {
                'Elgentos_GalleryMp4Solver/js/action/delete-images-mixin': true
            }
        }
    }
};
