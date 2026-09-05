/**
 * TICH Word-style CMS editor for blog body content.
 */
(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getSelectionHtml() {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            return '';
        }
        var container = document.createElement('div');
        container.appendChild(sel.getRangeAt(0).cloneContents());
        return container.innerHTML;
    }

    function wrapSelection(tagName, className) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            document.execCommand('formatBlock', false, tagName);
            if (className) {
                var block = closestBlock(sel.anchorNode);
                if (block) {
                    block.className = className;
                }
            }
            return;
        }

        var range = sel.getRangeAt(0);
        var wrapper = document.createElement(tagName);
        if (className) {
            wrapper.className = className;
        }
        try {
            range.surroundContents(wrapper);
        } catch (e) {
            document.execCommand('formatBlock', false, tagName);
            var block = closestBlock(sel.anchorNode);
            if (block && className) {
                block.className = className;
            }
        }
    }

    function closestBlock(node) {
        while (node && node.nodeType === 3) {
            node = node.parentNode;
        }
        while (node && node !== document.body) {
            if (/^(P|H1|H2|H3|H4|H5|H6|BLOCKQUOTE|DIV)$/i.test(node.nodeName)) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    function transformSelectedText(mode) {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
            return;
        }
        var range = sel.getRangeAt(0);
        var text = range.toString();
        var next = text;
        if (mode === 'uppercase') {
            next = text.toUpperCase();
        } else if (mode === 'lowercase') {
            next = text.toLowerCase();
        } else if (mode === 'titlecase') {
            next = text.replace(/\w\S*/g, function (w) {
                return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
            });
        }
        range.deleteContents();
        range.insertNode(document.createTextNode(next));
    }

    function insertHtml(html) {
        document.execCommand('insertHTML', false, html);
    }

    function shapeHtml(kind) {
        var map = {
            circle: '<span class="tich-shape tich-shape--circle" contenteditable="false" aria-hidden="true"></span>',
            square: '<span class="tich-shape tich-shape--square" contenteditable="false" aria-hidden="true"></span>',
            rounded: '<span class="tich-shape tich-shape--rounded" contenteditable="false" aria-hidden="true"></span>',
            triangle: '<span class="tich-shape tich-shape--triangle" contenteditable="false" aria-hidden="true"></span>',
            diamond: '<span class="tich-shape tich-shape--diamond" contenteditable="false" aria-hidden="true"></span>',
            star: '<span class="tich-shape tich-shape--star" contenteditable="false" aria-hidden="true">★</span>',
            arrow: '<span class="tich-shape tich-shape--arrow" contenteditable="false" aria-hidden="true">➜</span>',
            line: '<hr class="tich-prose-hr">',
        };
        return map[kind] || '';
    }

    function openFindDialog(surface) {
        var existing = document.querySelector('.tich-cms-find');
        if (existing) {
            existing.remove();
            return;
        }

        var panel = document.createElement('div');
        panel.className = 'tich-cms-find';
        panel.innerHTML =
            '<div class="tich-cms-find__row">' +
            '<input type="search" class="tich-input" data-find-q placeholder="Find…">' +
            '<input type="text" class="tich-input" data-find-r placeholder="Replace with…">' +
            '</div>' +
            '<div class="tich-cms-find__actions">' +
            '<button type="button" class="tich-btn tich-btn-secondary" data-find-next>Find next</button>' +
            '<button type="button" class="tich-btn tich-btn-secondary" data-find-replace>Replace</button>' +
            '<button type="button" class="tich-btn tich-btn-primary" data-find-all>Replace all</button>' +
            '<button type="button" class="tich-btn tich-btn-ghost" data-find-close>Close</button>' +
            '</div>';

        surface.parentNode.insertBefore(panel, surface);

        var q = panel.querySelector('[data-find-q]');
        var r = panel.querySelector('[data-find-r]');
        var lastIndex = 0;

        function findNext() {
            var needle = q.value;
            if (!needle) {
                return;
            }
            var text = surface.innerText || '';
            var idx = text.toLowerCase().indexOf(needle.toLowerCase(), lastIndex);
            if (idx === -1) {
                lastIndex = 0;
                idx = text.toLowerCase().indexOf(needle.toLowerCase(), 0);
            }
            if (idx === -1) {
                return;
            }
            lastIndex = idx + needle.length;
            selectTextInSurface(surface, idx, needle.length);
        }

        function replaceOne() {
            var sel = window.getSelection();
            if (!sel || sel.isCollapsed || sel.toString().toLowerCase() !== (q.value || '').toLowerCase()) {
                findNext();
                sel = window.getSelection();
            }
            if (sel && !sel.isCollapsed) {
                document.execCommand('insertText', false, r.value || '');
            }
        }

        function replaceAll() {
            var needle = q.value;
            if (!needle) {
                return;
            }
            var html = surface.innerHTML;
            var safe = needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var re = new RegExp(safe, 'gi');
            surface.innerHTML = html.replace(re, r.value || '');
        }

        panel.querySelector('[data-find-next]').addEventListener('click', findNext);
        panel.querySelector('[data-find-replace]').addEventListener('click', replaceOne);
        panel.querySelector('[data-find-all]').addEventListener('click', replaceAll);
        panel.querySelector('[data-find-close]').addEventListener('click', function () {
            panel.remove();
        });
        q.focus();
    }

    function selectTextInSurface(surface, start, length) {
        var walker = document.createTreeWalker(surface, NodeFilter.SHOW_TEXT, null);
        var count = 0;
        var node;
        while ((node = walker.nextNode())) {
            var next = count + node.nodeValue.length;
            if (start >= count && start < next) {
                var range = document.createRange();
                var offset = start - count;
                range.setStart(node, offset);
                range.setEnd(node, Math.min(offset + length, node.nodeValue.length));
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                surface.focus();
                return;
            }
            count = next;
        }
    }

    function syncInput(editor) {
        var surface = editor.querySelector('[data-cms-surface]');
        var inputId = editor.getAttribute('data-input-id');
        var input = inputId ? document.getElementById(inputId) : null;
        if (surface && input) {
            var html = surface.innerHTML.trim();
            if (html === '<br>' || html === '<div><br></div>') {
                html = '';
            }
            input.value = html;
        }
    }

    function uploadImage(editor, file, callback) {
        var url = editor.getAttribute('data-upload-url');
        if (!url || !file) {
            return;
        }
        var body = new FormData();
        body.append('image', file);
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: body,
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Upload failed');
                }
                return res.json();
            })
            .then(function (data) {
                if (data && data.url) {
                    callback(data.url);
                }
            })
            .catch(function () {
                window.alert('Image upload failed. Please try a smaller JPG/PNG/WebP file.');
            });
    }

    function initEditor(editor) {
        if (!editor || editor.getAttribute('data-cms-ready') === '1') {
            return;
        }
        editor.setAttribute('data-cms-ready', '1');

        var surface = editor.querySelector('[data-cms-surface]');
        var imageInput = editor.querySelector('[data-cms-image-input]');
        if (!surface) {
            return;
        }

        document.execCommand('defaultParagraphSeparator', false, 'p');
        document.execCommand('styleWithCSS', false, true);

        editor.querySelectorAll('[data-cmd]').forEach(function (el) {
            var cmd = el.getAttribute('data-cmd');
            if (!cmd) {
                return;
            }

            if (el.tagName === 'SELECT') {
                el.addEventListener('change', function () {
                    surface.focus();
                    if (!el.value) {
                        return;
                    }
                    document.execCommand(cmd, false, el.value);
                    el.selectedIndex = 0;
                    syncInput(editor);
                });
                return;
            }

            if (el.type === 'color') {
                el.addEventListener('input', function () {
                    surface.focus();
                    document.execCommand(cmd, false, el.value);
                    syncInput(editor);
                });
                return;
            }

            el.addEventListener('click', function (event) {
                event.preventDefault();
                surface.focus();
                document.execCommand(cmd, false, null);
                syncInput(editor);
            });
        });

        editor.querySelectorAll('[data-action]').forEach(function (el) {
            var action = el.getAttribute('data-action');

            if (el.tagName === 'SELECT') {
                el.addEventListener('change', function () {
                    surface.focus();
                    var value = el.value;
                    el.selectedIndex = 0;
                    if (!value) {
                        return;
                    }
                    if (action === 'style') {
                        applyStyle(value);
                    } else if (action === 'shape') {
                        insertHtml(shapeHtml(value) + '&nbsp;');
                    }
                    syncInput(editor);
                });
                return;
            }

            el.addEventListener('click', function (event) {
                event.preventDefault();
                surface.focus();
                if (action === 'uppercase' || action === 'lowercase' || action === 'titlecase') {
                    transformSelectedText(action);
                } else if (action === 'link') {
                    var url = window.prompt('Link URL', 'https://');
                    if (url) {
                        document.execCommand('createLink', false, url);
                    }
                } else if (action === 'image') {
                    imageInput && imageInput.click();
                } else if (action === 'table') {
                    insertHtml(
                        '<table class="tich-prose-table"><thead><tr><th>Heading</th><th>Heading</th></tr></thead>' +
                            '<tbody><tr><td>Cell</td><td>Cell</td></tr><tr><td>Cell</td><td>Cell</td></tr></tbody></table><p><br></p>'
                    );
                } else if (action === 'hr') {
                    insertHtml('<hr class="tich-prose-hr">');
                } else if (action === 'find') {
                    openFindDialog(surface);
                }
                syncInput(editor);
            });
        });

        function applyStyle(value) {
            if (value === 'p') {
                document.execCommand('formatBlock', false, 'p');
                var block = closestBlock(window.getSelection().anchorNode);
                if (block) {
                    block.removeAttribute('class');
                }
                return;
            }
            if (value === 'title') {
                wrapSelection('h1', 'tich-prose-title');
                return;
            }
            if (value === 'quote') {
                wrapSelection('blockquote', 'tich-prose-quote');
                return;
            }
            if (value === 'intense-quote') {
                wrapSelection('blockquote', 'tich-prose-quote tich-prose-quote--intense');
                return;
            }
            if (/^h[1-5]$/.test(value)) {
                wrapSelection(value, '');
            }
        }

        if (imageInput) {
            imageInput.addEventListener('change', function () {
                var file = imageInput.files && imageInput.files[0];
                imageInput.value = '';
                if (!file) {
                    return;
                }
                surface.focus();
                uploadImage(editor, file, function (url) {
                    insertHtml('<img src="' + url + '" alt="" class="tich-prose-image">');
                    syncInput(editor);
                });
            });
        }

        surface.addEventListener('input', function () {
            syncInput(editor);
        });
        surface.addEventListener('blur', function () {
            syncInput(editor);
        });
        surface.addEventListener('paste', function (event) {
            event.preventDefault();
            var text = (event.clipboardData || window.clipboardData).getData('text/html')
                || (event.clipboardData || window.clipboardData).getData('text/plain');
            if (text) {
                if (text.indexOf('<') === -1) {
                    document.execCommand('insertText', false, text);
                } else {
                    var cleaned = text
                        .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
                        .replace(/\son\w+="[^"]*"/gi, '')
                        .replace(/\son\w+='[^']*'/gi, '');
                    insertHtml(cleaned);
                }
            }
            syncInput(editor);
        });

        var form = editor.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                syncInput(editor);
            });
        }

        syncInput(editor);
    }

    function fillEditorFromInput(input) {
        if (!input) {
            return;
        }
        var editor = input.closest('[data-cms-editor]');
        if (!editor) {
            editor = document.querySelector('[data-cms-editor][data-input-id="' + input.id + '"]');
        }
        if (!editor) {
            return;
        }
        initEditor(editor);
        var surface = editor.querySelector('[data-cms-surface]');
        if (surface) {
            surface.innerHTML = input.value || '';
        }
        syncInput(editor);
    }

    function setEditorHtml(inputId, html) {
        var input = document.getElementById(inputId);
        if (!input) {
            return;
        }
        input.value = html || '';
        fillEditorFromInput(input);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cms-editor]').forEach(initEditor);
    });

    window.tichCmsEditor = {
        init: initEditor,
        sync: syncInput,
        fillFromInput: fillEditorFromInput,
        setHtml: setEditorHtml,
    };
})();
