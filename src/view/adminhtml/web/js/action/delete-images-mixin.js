/**
 * Announces which files the media gallery has just deleted.
 *
 * Core only triggers a jQuery event with a message, which says nothing about what was deleted and
 * cannot be heard from outside the gallery's own jQuery. The Hyvä editor hosts the gallery in an
 * iframe and keeps the picked file in its own state, so without this a deleted file stays in the
 * picker sidebar and in the field it was picked for, unless the page listens for this event.
 *
 * The paths are worked out the same way core's delete does it, from each tile's thumbnail src,
 * and read before the request is sent because the grid reloads without the deleted tiles.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    /**
     * @param {String} id
     * @returns {String|null} URL path of the file, e.g. /media/wysiwyg/foo.mp4
     */
    function getPath(id) {
        var src = $('div[data-id="' + id + '"]').find('img').attr('src');

        try {
            return src ? new URL(src.replace('.thumbs', ''), window.location.href).pathname : null;
        } catch (error) {
            return null;
        }
    }

    return function (deleteImages) {
        return function (ids, deleteUrl, confirmationContent) {
            var recordIds = Object.values(ids || {}).map(String),
                paths = recordIds.map(getPath).filter(Boolean);

            return deleteImages.apply(this, arguments).then(function (result) {

                if (typeof result === 'string') {
                    window.dispatchEvent(new CustomEvent('mediaGalleryAssetsDeleted', {
                        detail: { ids: recordIds, paths: paths }
                    }));
                }

                return result;
            });
        };
    };
});
