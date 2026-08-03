(function () {
    function getRoot(viewerId) {
        return document.getElementById(viewerId);
    }

    function bindDocumentItems(root) {
        var items = root.querySelectorAll('.doc-viewer__item');
        var stage = root.querySelector('[data-doc-viewer-stage]');
        var title = root.querySelector('[data-doc-viewer-title]');
        var filename = root.querySelector('[data-doc-viewer-filename]');
        var download = root.querySelector('[data-doc-viewer-download]');
        var external = root.querySelector('[data-doc-viewer-external]');
        var printButton = root.querySelector('[data-doc-viewer-print]');

        if (!items.length || !stage) {
            return;
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                items.forEach(function (button) {
                    button.classList.remove('is-active');
                });
                item.classList.add('is-active');

                var label = item.dataset.docLabel || 'Document';
                var name = item.dataset.docFilename || '';
                var viewUrl = item.dataset.docViewUrl;
                var downloadUrl = item.dataset.docDownloadUrl;
                var externalUrl = item.dataset.docExternalUrl;
                var previewable = item.dataset.docPreviewable === '1';
                var mime = item.dataset.docMime || '';

                if (title) {
                    title.textContent = label;
                }

                if (filename) {
                    filename.textContent = name;
                }

                if (download) {
                    download.href = downloadUrl;
                }

                if (external) {
                    external.href = externalUrl;
                }

                stage.innerHTML = '';

                if (!previewable) {
                    stage.innerHTML =
                        '<div class="doc-viewer__fallback">' +
                        '<p class="tich-text">This file type cannot be previewed in the browser.</p>' +
                        '<a href="' + downloadUrl + '" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>' +
                        '</div>';
                    return;
                }

                if (mime.indexOf('image/') === 0) {
                    var image = document.createElement('img');
                    image.dataset.docViewerImage = '1';
                    image.className = 'doc-viewer__image';
                    image.src = viewUrl;
                    image.alt = label;
                    stage.appendChild(image);
                    return;
                }

                var frame = document.createElement('iframe');
                frame.dataset.docViewerFrame = '1';
                frame.className = 'doc-viewer__frame';
                frame.src = viewUrl;
                frame.title = label;
                stage.appendChild(frame);
            });
        });

        if (printButton) {
            printButton.addEventListener('click', function () {
                var frame = root.querySelector('[data-doc-viewer-frame]');
                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    return;
                }

                var externalLink = root.querySelector('[data-doc-viewer-external]');
                if (externalLink && externalLink.href) {
                    window.open(externalLink.href, '_blank', 'noopener');
                }
            });
        }
    }

    function renderDocuments(root, documents) {
        var list = root.querySelector('.doc-viewer__list');
        var stage = root.querySelector('[data-doc-viewer-stage]');
        var title = root.querySelector('[data-doc-viewer-title]');
        var filename = root.querySelector('[data-doc-viewer-filename]');
        var download = root.querySelector('[data-doc-viewer-download]');
        var external = root.querySelector('[data-doc-viewer-external]');

        if (!list || !stage || !documents.length) {
            if (list) {
                list.innerHTML = '';
            }
            if (stage) {
                stage.innerHTML = '<div class="doc-viewer__fallback"><p class="tich-text">No documents uploaded for this application.</p></div>';
            }
            if (title) {
                title.textContent = 'Document';
            }
            if (filename) {
                filename.textContent = '';
            }
            if (download) {
                download.removeAttribute('href');
            }
            if (external) {
                external.removeAttribute('href');
            }
            return;
        }

        list.innerHTML = documents.map(function (document, index) {
            return (
                '<button type="button" class="doc-viewer__item' + (index === 0 ? ' is-active' : '') + '"' +
                ' data-doc-key="' + document.key + '"' +
                ' data-doc-label="' + document.label + '"' +
                ' data-doc-filename="' + document.filename + '"' +
                ' data-doc-previewable="' + (document.is_previewable ? '1' : '0') + '"' +
                ' data-doc-mime="' + document.mime_type + '"' +
                ' data-doc-view-url="' + document.view_url + '"' +
                ' data-doc-download-url="' + document.download_url + '"' +
                ' data-doc-external-url="' + document.external_url + '">' +
                '<span class="doc-viewer__item-label">' + document.label + '</span>' +
                '<span class="doc-viewer__item-file">' + document.filename + '</span>' +
                '</button>'
            );
        }).join('');

        bindDocumentItems(root);

        var firstItem = list.querySelector('.doc-viewer__item');
        if (firstItem) {
            firstItem.click();
        }
    }

    function init(viewerId) {
        var root = getRoot(viewerId);
        if (!root) {
            return;
        }

        bindDocumentItems(root);
    }

    function initApplicationSwitcher(viewerId, selectId, dataId) {
        var root = getRoot(viewerId);
        var select = document.getElementById(selectId);
        var dataNode = document.getElementById(dataId);

        if (!root || !select || !dataNode) {
            return;
        }

        var payload = JSON.parse(dataNode.textContent || '[]');
        var applicationsById = {};

        payload.forEach(function (entry) {
            applicationsById[String(entry.id)] = entry.documents || [];
        });

        select.addEventListener('change', function () {
            renderDocuments(root, applicationsById[String(select.value)] || []);
        });

        bindDocumentItems(root);
    }

    window.TichHrDocumentViewer = {
        init: init,
        initApplicationSwitcher: initApplicationSwitcher,
    };
})();
