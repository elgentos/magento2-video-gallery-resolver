/**
 * Extends the media gallery's tile column.
 *
 * Captures a poster frame for a video straight from its gallery tile. A video with no poster yet is
 * rendered as a <video> with #t=0.5 (see the tile template). Once that frame is decoded it is drawn
 * to a canvas and posted to the poster controller, so a freshly uploaded video gets its thumbnail
 * without first having to be picked anywhere.
 *
 * Also makes a click on a tile always select it inside the Hyvä editor's picker (see clickOnImage).
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (Column) {
        return Column.extend({
            /**
             * Core toggles: a click on the selected record deselects it. Hyvä's picker, which hosts
             * this grid in an iframe, adds and removes the "selected" class on the tiles directly
             * and never tells this component, so the record it believes is selected is often not
             * the one highlighted. The first click on the tile picked last time then only
             * deselected it, and it took a second click to select it and fill the picker sidebar.
             *
             * In the picker a click always selects. Clearing first makes the "selected" class
             * go on afresh, which is the change the picker watches for. The standalone gallery
             * keeps core's toggle.
             *
             * @param {Object} record
             * @param {Boolean} collapsibleOpened
             */
            clickOnImage: function (record, collapsibleOpened) {
                if (window.frameElement && !collapsibleOpened && !this.massaction().massActionMode()) {
                    this.selected(null);
                }

                return this._super(record, collapsibleOpened);
            },

            /**
             * @param {Object} record
             * @param {Event} event
             */
            captureVideoPoster: function (record, event) {
                var video = event.target,
                    canvas,
                    image;

                if (record.video_poster_url || record.videoPosterRequested ||
                    !record.video_poster_save_url || !video.videoWidth || !video.videoHeight
                ) {
                    return;
                }

                record.videoPosterRequested = true;

                try {
                    canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    image = canvas.toDataURL('image/jpeg', 0.8);
                } catch (error) {
                    return;
                }

                $.ajax({
                    url: record.video_poster_save_url,
                    type: 'POST',
                    dataType: 'json',
                    showLoader: false,
                    data: {
                        src: record.path,
                        image: image,
                        'form_key': window.FORM_KEY
                    }
                }).done(function (response) {
                    if (response && response.success) {
                        record.video_poster_url = response.poster.url;
                    } else {
                        record.videoPosterRequested = false;
                    }
                }).fail(function () {
                    record.videoPosterRequested = false;
                });
            }
        });
    };
});
