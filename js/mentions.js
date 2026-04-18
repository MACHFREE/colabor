/**
 * Colabor Mentions System — in-text @mention detection con tokens visuales
 *
 * Modos:
 *   - CKEditor 4: inserta <span class="mention">
 *   - Textarea:   reemplaza el textarea con un contenteditable div que
 *                 muestra tokens visuales; sincroniza al textarea oculto
 *                 en formato @[Nombre|tipo:id] para envío del formulario.
 *
 * @version 4.17
 */
(function ($) {
    'use strict';

    var ColaborMentions = {

        ajaxUrl:        '',
        socid:          0,
        _timer:         null,
        _mentionStart:  -1,
        _cke:           null,
        _ckeNode:       null,
        _ckeNodeOffset: 0,
        _$div:          null,
        _$ta:           null,
        _divNode:       null,
        _scMoving:      false,   // guard contra bucles de selectionchange

        // ── Init ──────────────────────────────────────────────────────────────

        init: function (options) {
            this.ajaxUrl = options.ajaxUrl || '';
            this.socid   = options.socid   || 0;
            this._bind();
        },

        // ── Detección del editor ──────────────────────────────────────────────

        _bind: function () {
            var self = this;
            var $ta  = $('#description');

            if (typeof CKEDITOR === 'undefined') {
                if ($ta.length) self._bindTextarea($ta);
                return;
            }

            var tryBind = function (ed) {
                if (self._cke) return;
                self._cke = ed;
                self._bindCKEditor(ed);
            };

            var ed0 = CKEDITOR.instances && CKEDITOR.instances['description'];
            if (ed0 && ed0.status === 'ready') { tryBind(ed0); return; }

            CKEDITOR.on('instanceReady', function (evt) {
                if (evt.editor.name === 'description') tryBind(evt.editor);
            });

            var attempts = 0;
            var poll = setInterval(function () {
                attempts++;
                var ed = CKEDITOR.instances && CKEDITOR.instances['description'];
                if (ed && ed.status === 'ready') {
                    clearInterval(poll); tryBind(ed); return;
                }
                if (attempts > 20) {
                    clearInterval(poll);
                    if (!self._cke && $ta.length) self._bindTextarea($ta);
                }
            }, 100);
        },

        // ── Textarea → ContentEditable visual editor ──────────────────────────

        _bindTextarea: function ($ta) {
            var self = this;

            var $div = $('<div>')
                .attr({ 'id': 'description-visual', 'contenteditable': 'true', 'spellcheck': 'false' })
                .addClass('colabor-visual-editor');

            $ta.after($div).hide();
            self._$div = $div;
            self._$ta  = $ta;

            var initial = $ta.val();
            // If the stored content is HTML (from CKEditor — contains tags),
            // convert it to plain @[...] format so _plainToVisual can process it.
            // This handles the fallback case where CKEditor was used before but is
            // not available now.
            var plainInitial = (initial && /<[a-z]/i.test(initial))
                ? self._ckeHtmlToPlain(initial)
                : initial;
            $div.html(plainInitial ? self._plainToVisual(plainInitial) : '<br>');

            // If content ends with a mention span, append \u00A0 so the cursor
            // has a text node to land on (browsers can't place caret after a span
            // that has no following sibling text node).
            if (plainInitial) {
                var divEl = self._$div[0];
                if (divEl.lastChild && divEl.lastChild.nodeName === 'SPAN' &&
                    divEl.lastChild.classList.contains('mention')) {
                    divEl.appendChild(document.createTextNode('\u00A0'));
                }
            }

            // Position cursor at end. _scMoving prevents selectionchange from
            // interfering while we programmatically move the selection.
            if (plainInitial) {
                setTimeout(function () {
                    try {
                        var divEl2 = self._$div[0];
                        self._scMoving = true;
                        divEl2.focus();
                        var sel   = window.getSelection();
                        var range = document.createRange();
                        range.selectNodeContents(divEl2);
                        range.collapse(false);
                        sel.removeAllRanges();
                        sel.addRange(range);
                        setTimeout(function () { self._scMoving = false; }, 50);
                    } catch (ex) { self._scMoving = false; }
                }, 0);
            }

            // ── selectionchange: sacar el cursor de dentro de un span.mention ──
            // Se usa selectionchange (en lugar de click/ArrowKey) porque captura
            // TODOS los métodos de mover el cursor (clic, teclado, drag…).
            document.addEventListener('selectionchange', function () {
                if (self._scMoving) return;
                if (!self._$div) return;
                var div = self._$div[0];
                var sel = window.getSelection();
                if (!sel || !sel.rangeCount) return;
                var range = sel.getRangeAt(0);
                if (!range.collapsed) return;                  // no actuar sobre selecciones
                var node = range.startContainer;
                if (!div.contains(node)) return;               // fuera de nuestro div

                // ¿Está el cursor dentro de un span.mention?
                var par = node.nodeType === Node.TEXT_NODE ? node.parentNode : node;
                if (par && par.nodeName === 'SPAN' &&
                    par.classList && par.classList.contains('mention')) {

                    self._scMoving = true;
                    var newR = document.createRange();
                    newR.setStartAfter(par);  // mover DESPUÉS del token
                    newR.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(newR);
                    setTimeout(function () { self._scMoving = false; }, 0);
                }
            });

            // ── keyup: limpiar spans vacíos, sincronizar, detectar @ ──────────
            $div.on('keyup.colMentions', function (e) {
                if (e.key === 'Escape') { self._close(); return; }

                // Eliminar spans de mención que hayan quedado vacíos
                self._$div.find('span.mention').each(function () {
                    if (!(this.textContent || '').replace(/^@\s*/, '').trim()) {
                        this.parentNode.removeChild(this);
                    }
                });

                self._syncToTextarea();

                var info = self._getSelectionInfo();
                if (info) {
                    self._divNode = info.node;
                    self._process(info.text, info.offset);
                } else {
                    self._divNode = null;
                    self._close();
                }
            });

            // ── keydown: Backspace / Delete + navegación del dropdown ─────────
            $div.on('keydown.colMentions', function (e) {
                var sel2 = window.getSelection();
                var r2   = sel2 && sel2.rangeCount ? sel2.getRangeAt(0) : null;

                // Backspace: borrar span completo cuando el cursor está justo después
                if (e.key === 'Backspace' && r2 && r2.collapsed && r2.startOffset === 0) {
                    var prevSib = r2.startContainer.previousSibling;
                    if (prevSib && prevSib.nodeName === 'SPAN' &&
                        prevSib.classList && prevSib.classList.contains('mention')) {
                        e.preventDefault();
                        prevSib.parentNode.removeChild(prevSib);
                        self._syncToTextarea();
                        return;
                    }
                }

                // Delete: borrar span cuando el cursor está justo antes
                if (e.key === 'Delete' && r2 && r2.collapsed) {
                    var nc   = r2.startContainer;
                    var noff = r2.startOffset;
                    if (nc.nodeType === Node.TEXT_NODE && noff === (nc.textContent || '').length) {
                        var nextSib = nc.nextSibling;
                        if (nextSib && nextSib.nodeName === 'SPAN' &&
                            nextSib.classList && nextSib.classList.contains('mention')) {
                            e.preventDefault();
                            nextSib.parentNode.removeChild(nextSib);
                            self._syncToTextarea();
                            return;
                        }
                    }
                }

                self._navKey(e);
            });

            // ── paste: sincronizar después del paste ──────────────────────────
            $div.on('paste.colMentions', function () {
                setTimeout(function () { self._syncToTextarea(); }, 0);
            });

            // ── submit: sincronizar antes de enviar ────────────────────────────
            $ta.closest('form').on('submit.colMentions', function () {
                self._syncToTextarea();
            });
        },

        // Devuelve {node, text, offset} del text node donde está el cursor,
        // o null si el cursor está dentro de un span.mention (no procesar @)
        _getSelectionInfo: function () {
            var div = this._$div ? this._$div[0] : null;
            if (!div) return null;
            var sel = window.getSelection();
            if (!sel || !sel.rangeCount) return null;
            var range = sel.getRangeAt(0);
            var node  = range.startContainer;
            if (!div.contains(node)) return null;
            if (node.nodeType !== Node.TEXT_NODE) return null;
            // No procesar si el cursor está dentro de un span de mención
            if (node.parentNode && node.parentNode.classList &&
                node.parentNode.classList.contains('mention')) return null;
            return { node: node, text: node.textContent || '', offset: range.startOffset };
        },

        // Convierte @[Name|type:id] → HTML con spans (sin contenteditable="false"
        // porque usamos selectionchange para controlar el cursor)
        _plainToVisual: function (text) {
            if (!text) return '<br>';
            var escaped = this._esc(text).replace(/\n/g, '<br>');
            return escaped.replace(
                /@\[([^\|\]]+)\|([a-z_]+):(\d+)\]/g,
                function (m, name, type, id) {
                    return '<span class="mention" data-type="' + type +
                           '" data-id="' + id + '">@' + name + '</span>';
                }
            ) || '<br>';
        },

        // Convierte el div visual → @[Name|type:id] texto plano → textarea
        _syncToTextarea: function () {
            if (!this._$div || !this._$ta) return;

            var tmp = $('<div>').html(this._$div.html());

            tmp.find('span.mention').each(function () {
                var type = this.getAttribute('data-type') || '';
                var id   = this.getAttribute('data-id')   || '';
                var name = (this.textContent || '').replace(/^@/, '');
                var tn   = document.createTextNode('@[' + name + '|' + type + ':' + id + ']');
                this.parentNode.replaceChild(tn, this);
            });

            var plain = (tmp[0].innerText || tmp[0].textContent || '')
                .replace(/\u00a0/g, ' ')
                .trim();

            this._$ta.val(plain);
        },

        // ── Binding CKEditor ──────────────────────────────────────────────────

        _bindCKEditor: function (ed) {
            var self = this;

            try { ed.filter.allow('span(mention)[data-type,data-id]', 'colaborMentions'); } catch (ex) {}

            // Re-load content from the ORIGINAL textarea value (CKEditor has not
            // overwritten it yet — it only does so on form submit / updateElement).
            // This is necessary because CKEditor runs ACF on initial load BEFORE
            // our filter.allow rule is registered, stripping <span class="mention">.
            // By calling setData() here (after filter.allow), the spans survive ACF.
            // Also converts any @[...] plain markers to visual spans.
            try {
                var origVal = $('#description').val();
                if (origVal) {
                    var loadContent = (origVal.indexOf('@[') !== -1)
                        ? self._plainToVisualCKE(origVal)
                        : origVal;
                    ed.setData(loadContent);
                }
            } catch (ex) {}

            // Move cursor to end after CKEditor finishes rendering.
            // Uses setTimeout instead of setData callback for broader compatibility.
            setTimeout(function () {
                try {
                    ed.focus();
                    var range = ed.createRange();
                    range.moveToElementEditEnd(ed.editable());
                    ed.getSelection().selectRanges([range]);
                } catch (ex) {}
            }, 150);

            var CKE_CSS = [
                '.mention{background:#e3f2fd;border:1px solid #2196F3;border-radius:4px;',
                'padding:2px 6px;margin:0 2px;display:inline-block;white-space:nowrap;',
                'font-size:.92em;line-height:1.6;cursor:default;}',
                '.mention[data-type="user"]{border-color:#4CAF50;background:#f1f8e9;color:#2e7d32;}',
                '.mention[data-type="contact"]{border-color:#FF9800;background:#fff3e0;color:#e65100;}',
                '.mention[data-type="propal"],.mention[data-type="commande"],.mention[data-type="facture"]',
                '{border-color:#9C27B0;background:#f3e5f5;color:#6a1b9a;}',
                '.mention[data-type="project"]{border-color:#E91E63;background:#fce4ec;color:#880e4f;}',
                '.mention[data-type="product"]{border-color:#FF9800;background:#fff3e0;color:#e65100;}',
                '.mention[data-type="service"]{border-color:#00BCD4;background:#e0f7fa;color:#006064;}',
                '.mention[data-type="branch"]{border-color:#795548;background:#efebe9;color:#4e342e;}',
            ].join('');

            try {
                var s = ed.document.$.createElement('style');
                s.textContent = CKE_CSS;
                ed.document.$.head.appendChild(s);
            } catch (ex) {}

            // CKEditor updates its textarea automatically on form submit —
            // no custom submit handler needed. The full HTML (with formatting
            // and <span class="mention"> tokens) is sent as-is.

            setTimeout(function () { self._updateBadgesFromCKE(ed); }, 300);
            ed.on('change', function () { setTimeout(function () { self._updateBadgesFromCKE(ed); }, 0); });

            ed.on('key', function (e) {
                if (!$('#colabor-mention-dropdown').is(':visible')) return;
                var key = e.data.domEvent.$.key;
                if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].indexOf(key) !== -1) {
                    e.cancel();
                    self._navKey({ key: key, preventDefault: function () {} });
                    return false;
                }
            }, null, null, 1);

            ed.on('key', function (e) {
                var key = e.data.domEvent.$.key;
                if (key === 'Escape') { self._close(); return; }
                setTimeout(function () {
                    try {
                        var sel  = ed.getSelection();
                        if (!sel) return;
                        var rngs = sel.getRanges();
                        if (!rngs || !rngs.length) return;
                        var rng  = rngs[0];
                        var node = rng.startContainer;
                        if (!node || node.type !== CKEDITOR.NODE_TEXT) { self._close(); return; }
                        var text   = node.$.textContent || node.$.nodeValue || '';
                        var offset = rng.startOffset;
                        self._ckeNode       = node;
                        self._ckeNodeOffset = offset;
                        self._process(text, offset);
                    } catch (ex) { self._close(); }
                }, 0);
            });
        },

        // ── Detectar @contexto ────────────────────────────────────────────────

        _process: function (text, cursorPos) {
            var ctx = this._getContext(text, cursorPos);
            if (!ctx) { this._close(); return; }
            this._mentionStart = ctx.start;
            var self = this;
            clearTimeout(this._timer);
            this._timer = setTimeout(function () { self._search(ctx.query); }, 200);
        },

        _getContext: function (text, pos) {
            for (var i = pos - 1; i >= 0; i--) {
                var c = text[i];
                if (c === '@') {
                    var prev = i > 0 ? text[i - 1] : '';
                    if (i === 0 || /[\s\n\r]/.test(prev)) {
                        return { start: i, query: text.substring(i + 1, pos) };
                    }
                    return null;
                }
                if (/[\s\n\r]/.test(c)) return null;
            }
            return null;
        },

        // ── AJAX ──────────────────────────────────────────────────────────────

        _search: function (q) {
            var self = this;
            var data = { q: q };
            if (self.socid) data.socid = self.socid;
            $.ajax({
                url: this.ajaxUrl, method: 'GET', data: data, dataType: 'json',
                success: function (data) { self._show(data, q); },
                error:   function ()     { self._close(); }
            });
        },

        // ── Dropdown ──────────────────────────────────────────────────────────

        _show: function (results, query) {
            var self = this;
            var $dd  = $('#colabor-mention-dropdown');
            $dd.empty();

            if (!results || !results.length) {
                if (query && query.length > 0) {
                    $dd.html('<div class="colabor-mention-empty">Sin resultados</div>').show();
                } else {
                    $dd.hide();
                }
                return;
            }

            $.each(results, function (i, item) {
                var icon;
                switch (item.type) {
                    case 'user':
                    case 'contact':  icon = '&#128100;'; break;
                    case 'product':  icon = '&#128717;'; break;
                    case 'service':  icon = '&#128295;'; break;
                    case 'branch':   icon = '&#127970;'; break;
                    default:         icon = '&#128196;'; break;
                }
                var $opt = $('<div class="colabor-mention-opt">')
                    .data('item', item)
                    .html(
                        '<span class="m-icon">' + icon + '</span>' +
                        '<span class="m-label">' + self._esc(item.label) + '</span>' +
                        '<span class="m-sub">' + self._esc(item.sublabel || '') +
                        ' <em class="m-type">(' + self._esc(item.typeLabel) + ')</em></span>'
                    );
                $opt.on('mousedown', function (e) {
                        e.preventDefault();
                        self._insert(item);
                    })
                    .on('mouseenter', function () {
                        $dd.find('.colabor-mention-opt').removeClass('active');
                        $(this).addClass('active');
                    });
                $dd.append($opt);
            });

            $dd.show();

            $(document).off('click.colMentionsClose').on('click.colMentionsClose', function (e) {
                if (!$(e.target).closest('#colabor-editor-container').length) {
                    self._close();
                    $(document).off('click.colMentionsClose');
                }
            });
        },

        _close: function () { $('#colabor-mention-dropdown').hide().empty(); },

        // ── Teclado ───────────────────────────────────────────────────────────

        _navKey: function (e) {
            var $dd = $('#colabor-mention-dropdown');
            if (!$dd.is(':visible')) return;
            var $items  = $dd.find('.colabor-mention-opt');
            var $active = $items.filter('.active');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var $next = $active.length ? $active.removeClass('active').next('.colabor-mention-opt') : $items.first();
                if (!$next.length) $next = $items.first();
                $next.addClass('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var $prev = $active.length ? $active.removeClass('active').prev('.colabor-mention-opt') : $items.last();
                if (!$prev.length) $prev = $items.last();
                $prev.addClass('active');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if ($active.length) this._insert($active.data('item'));
            } else if (e.key === 'Escape') {
                this._close();
            }
        },

        // ── Inserción ─────────────────────────────────────────────────────────

        _insert: function (item) {
            this._close();
            if (this._cke) {
                this._insertCKEditor(item);
            } else {
                this._insertDiv(item);
            }
        },

        _insertDiv: function (item) {
            var self  = this;
            var node  = this._divNode;
            var start = this._mentionStart;

            if (!node || !node.parentNode) { self._syncToTextarea(); return; }

            // No insertar dentro de un span de mención existente
            if (node.parentNode.classList && node.parentNode.classList.contains('mention')) {
                self._syncToTextarea(); return;
            }

            var sel = window.getSelection();
            if (!sel || !sel.rangeCount) { self._syncToTextarea(); return; }

            var endOffset = sel.getRangeAt(0).startOffset;

            // Crear el span visual del token
            var span = document.createElement('span');
            span.className = 'mention';
            span.setAttribute('data-type', item.type);
            span.setAttribute('data-id',   String(item.id));
            span.textContent = '@' + item.label;

            var textBefore = node.textContent.substring(0, start);
            var textAfter  = node.textContent.substring(endOffset);
            var parent     = node.parentNode;
            var next       = node.nextSibling;

            node.textContent = textBefore;

            // Usar \u00A0 (non-breaking space) como separador después del span:
            // - nunca es invisible al final de la línea (a diferencia del espacio regular)
            // - el cursor queda visible y posicionable
            var spaceNode = document.createTextNode('\u00A0');

            if (next) {
                parent.insertBefore(span,      next);
                parent.insertBefore(spaceNode, next);
                if (textAfter) parent.insertBefore(document.createTextNode(textAfter), next);
            } else {
                parent.appendChild(span);
                parent.appendChild(spaceNode);
                if (textAfter) parent.appendChild(document.createTextNode(textAfter));
            }

            // Posicionar cursor DESPUÉS del \u00A0 — sin llamar focus()
            // (el div ya tiene foco por mousedown.preventDefault en el dropdown)
            try {
                self._scMoving = true;   // evitar que selectionchange lo mueva dentro del span
                var newRange = document.createRange();
                newRange.setStart(spaceNode, spaceNode.length);
                newRange.collapse(true);
                sel.removeAllRanges();
                sel.addRange(newRange);
                setTimeout(function () { self._scMoving = false; }, 0);
            } catch (ex) {
                self._scMoving = false;
            }

            self._divNode = null;
            self._syncToTextarea();
        },

        _insertCKEditor: function (item) {
            var ed           = this._cke;
            var self         = this;
            var mentionStart = this._mentionStart;
            var savedNode    = this._ckeNode;
            var savedOffset  = this._ckeNodeOffset;

            var spanHtml = '<span class="mention" data-type="' + item.type +
                           '" data-id="' + item.id + '">@' + self._esc(item.label) + '</span>&nbsp;';

            try {
                var sel = ed.getSelection();
                if (savedNode && savedNode.$ && savedNode.type === CKEDITOR.NODE_TEXT && savedNode.$.parentNode) {
                    var replRng = ed.createRange();
                    replRng.setStart(savedNode, mentionStart);
                    replRng.setEnd(savedNode, savedOffset);
                    sel.selectRanges([replRng]);
                }
                ed.insertHtml(spanHtml, 'unfiltered_html');
            } catch (ex) {
                try { ed.insertHtml(spanHtml, 'unfiltered_html'); } catch (ex2) {}
            }

            self._ckeNode       = null;
            self._ckeNodeOffset = 0;
            setTimeout(function () { self._updateBadgesFromCKE(ed); }, 80);
        },

        // ── Badges (solo CKEditor) ────────────────────────────────────────────

        _updateBadgesFromCKE: function (ed) {
            var $badges = $('#colabor-mentions-badges');
            $badges.empty();
            var self = this;
            try {
                var spans = ed.document.$.querySelectorAll('span.mention');
                var seen  = {};
                for (var i = 0; i < spans.length; i++) {
                    var sp   = spans[i];
                    var type = sp.getAttribute('data-type') || '';
                    var id   = sp.getAttribute('data-id')   || '';
                    var name = (sp.textContent || '').replace(/^@/, '');
                    var key  = type + ':' + id;
                    if (seen[key]) continue;
                    seen[key] = true;
                    var icon;
                    switch (type) {
                        case 'user':
                        case 'contact':  icon = '&#128100;'; break;
                        case 'product':  icon = '&#128717;'; break;
                        case 'service':  icon = '&#128295;'; break;
                        case 'branch':   icon = '&#127970;'; break;
                        default:         icon = '&#128196;'; break;
                    }
                    var css  = (type === 'user') ? 'colabor-badge is-user' : 'colabor-badge';
                    $badges.append($('<span class="' + css + '">').html(icon + ' ' + self._esc(name)));
                }
            } catch (ex) {}
        },

        // ── Helpers ───────────────────────────────────────────────────────────

        // Converts @[Name|type:id] markers to <span class="mention"> HTML for CKEditor display.
        _plainToVisualCKE: function (text) {
            if (!text) return text;
            return text.replace(
                /@\[([^\|\]]+)\|([a-z_]+):(\d+)\]/g,
                function (m, name, type, id) {
                    return '<span class="mention" data-type="' + type +
                           '" data-id="' + id + '">@' + name + '</span>';
                }
            );
        },

        // Converts CKEditor HTML (with <span class="mention">) to @[Name|type:id] plain text.
        // Handles any attribute order (data-id/data-type can appear in any order).
        _ckeHtmlToPlain: function (html) {
            // Replace mention spans first (any attribute order)
            var result = html.replace(
                /<span([^>]*class="mention"[^>]*)>@?([^<]*)<\/span>/gi,
                function (m, attrs, inner) {
                    var type = (attrs.match(/data-type="([^"]+)"/) || [])[1] || '';
                    var id   = (attrs.match(/data-id="(\d+)"/)    || [])[1] || '';
                    var name = (inner || '').replace(/^@/, '').trim();
                    if (!type || !id || !name) return name || '';
                    return '@[' + name + '|' + type + ':' + id + ']';
                }
            );
            // Decode common HTML entities
            var entities = {
                nbsp: ' ', amp: '&', lt: '<', gt: '>',
                aacute: 'á', eacute: 'é', iacute: 'í', oacute: 'ó', uacute: 'ú', ntilde: 'ñ',
                Aacute: 'Á', Eacute: 'É', Iacute: 'Í', Oacute: 'Ó', Uacute: 'Ú', Ntilde: 'Ñ'
            };
            result = result
                .replace(/&#(\d+);/g, function (m, n) { return String.fromCharCode(parseInt(n, 10)); })
                .replace(/&([a-zA-Z]+);/g, function (m, e) { return entities[e] !== undefined ? entities[e] : m; })
                .replace(/<br\s*\/?>/gi, '\n')
                .replace(/<\/p>/gi, '\n')
                .replace(/<p[^>]*>/gi, '')
                .replace(/<[^>]+>/g, '')   // strip remaining tags
                .replace(/\n{3,}/g, '\n\n')
                .trim();
            return result;
        },

        _esc: function (t) {
            return String(t || '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    };

    window.ColaborMentions = ColaborMentions;

}(jQuery));

