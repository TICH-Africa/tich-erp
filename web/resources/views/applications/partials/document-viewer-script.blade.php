<script>
(function () {
    var items = document.querySelectorAll('.doc-viewer__item');
    var stage = document.getElementById('doc-viewer-stage');
    var title = document.getElementById('doc-viewer-title');
    var filename = document.getElementById('doc-viewer-filename');
    var download = document.getElementById('doc-viewer-download');
    var openTab = document.getElementById('doc-viewer-open-tab');

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
            var previewable = item.dataset.docPreviewable === '1';
            var mime = item.dataset.docMime || '';

            title.textContent = label;
            filename.textContent = name;

            if (download) {
                download.href = downloadUrl;
            }

            if (openTab) {
                openTab.href = viewUrl;
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
                image.id = 'doc-viewer-image';
                image.className = 'doc-viewer__image';
                image.src = viewUrl;
                image.alt = label;
                stage.appendChild(image);
                return;
            }

            var frame = document.createElement('iframe');
            frame.id = 'doc-viewer-frame';
            frame.className = 'doc-viewer__frame';
            frame.src = viewUrl;
            frame.title = label;
            stage.appendChild(frame);
        });
    });
})();
</script>
