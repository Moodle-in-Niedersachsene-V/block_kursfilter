// block_kursfilter/amd/src/filter.js
define(['core/ajax'], function(Ajax) {
    'use strict';

    // ---------------------------------------------------------------
    function BlockState(blockid, config) {
        this.blockid   = blockid;
        this.config    = config;
        this.kursbereich  = '';
        this.schulform    = '';  // aktiver Chip-Wert
        this.fach         = '';
        this.niveaustufe  = '';
        this.kursname     = '';
        this.debounce     = null;
    }

    BlockState.prototype.el = function(id) {
        return document.getElementById('kf-' + id + '-' + this.blockid);
    };
    BlockState.prototype.block = function() {
        return document.getElementById('kf-block-' + this.blockid);
    };

    // ---------------------------------------------------------------
    // Init
    // ---------------------------------------------------------------
    BlockState.prototype.init = function() {
        var self = this;

        // Kursbereich-Select.
        var selectEl = self.el('kursbereich');
        if (selectEl) {
            selectEl.addEventListener('change', function() {
                self.kursbereich = this.value;
                self.triggerSearch();
            });
        }

        // Chip-Gruppen: Schulform, Fach, Niveaustufe.
        ['schulform', 'fach', 'niveaustufe'].forEach(function(filter) {
            var wrap = document.getElementById('kf-' + filter + '-chips-' + self.blockid);
            if (!wrap) return;
            wrap.querySelectorAll('.kf-chip').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var val = this.dataset.value;
                    // Toggle: gleicher Wert → deaktivieren.
                    if (self[filter] === val) {
                        self[filter] = '';
                        this.classList.remove('kf-chip-active');
                    } else {
                        // Anderen Chip in dieser Gruppe deaktivieren.
                        wrap.querySelectorAll('.kf-chip').forEach(function(b) {
                            b.classList.remove('kf-chip-active');
                        });
                        self[filter] = val;
                        this.classList.add('kf-chip-active');
                    }
                    self.triggerSearch();
                });
            });
        });

        // Kursname-Freitext.
        var searchEl = self.el('search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                self.kursname = this.value.trim();
                self.triggerSearch();
            });
        }

        // Reset-Button.
        var resetBtn = self.block().querySelector('.kf-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                self.kursbereich = '';
                self.schulform   = '';
                self.fach        = '';
                self.niveaustufe = '';
                self.kursname    = '';

                // UI zurücksetzen.
                if (selectEl) selectEl.value = '';
                if (searchEl) searchEl.value = '';
                self.block().querySelectorAll('.kf-chip').forEach(function(b) {
                    b.classList.remove('kf-chip-active');
                });

                var results = self.el('results');
                if (results) results.innerHTML = '<div class="text-center text-muted small py-3">Filter setzen, um Kurse zu suchen.</div>';
                var count = self.el('count');
                if (count) count.textContent = '–';
            });
        }
    };

    // ---------------------------------------------------------------
    // Suche
    // ---------------------------------------------------------------
    BlockState.prototype.hasActiveFilter = function() {
        return this.kursbereich !== '' ||
               this.schulform   !== '' ||
               this.fach        !== '' ||
               this.niveaustufe !== '' ||
               this.kursname    !== '';
    };

    BlockState.prototype.triggerSearch = function() {
        var self = this;
        clearTimeout(self.debounce);
        self.debounce = setTimeout(function() { self.runSearch(); }, 350);
    };

    BlockState.prototype.runSearch = function() {
        var self    = this;
        var spinner = self.el('spinner');
        var results = self.el('results');
        var count   = self.el('count');

        if (!self.hasActiveFilter()) {
            if (results) results.innerHTML = '<div class="text-center text-muted small py-3">Filter setzen, um Kurse zu suchen.</div>';
            if (count) count.textContent = '–';
            return;
        }

        if (spinner) spinner.classList.remove('d-none');
        if (results) results.innerHTML = '';

        // Tag-Werte mit Prefix zusammenbauen
        // (Tags liegen als "schulform:Gymnasium" usw. in der DB).
        var schulformTag   = self.schulform   ? 'schulform:'   + self.schulform   : '';
        var fachTag        = self.fach        ? 'fach:'        + self.fach        : '';
        var niveaustufeTag = self.niveaustufe ? 'niveaustufe:' + self.niveaustufe : '';

        Ajax.call([{
            methodname: 'block_kursfilter_search_courses',
            args: {
                kursbereich:  parseInt(self.kursbereich, 10) || 0,
                schulform:    schulformTag,
                fach:         fachTag,
                niveaustufe:  niveaustufeTag,
                tag:          '',
                kursname:     self.kursname,
                contextid:    self.config.contextid || 1,
                limit:        100,
            },
            done: function(result) {
                if (spinner) spinner.classList.add('d-none');
                self.renderResults(result.courses || []);
            },
            fail: function(err) {
                if (spinner) spinner.classList.add('d-none');
                if (results) results.innerHTML = '<div class="alert alert-warning small p-2">'
                    + escHtml(err.message || 'Suche fehlgeschlagen') + '</div>';
            },
        }]);
    };

    // ---------------------------------------------------------------
    // Ergebnisse rendern
    // ---------------------------------------------------------------
    BlockState.prototype.renderResults = function(courses) {
        var self    = this;
        var results = self.el('results');
        var count   = self.el('count');

        if (count) {
            count.textContent = courses.length
                + ' Kurs' + (courses.length !== 1 ? 'e' : '') + ' gefunden';
        }

        if (!courses.length) {
            if (results) results.innerHTML =
                '<div class="text-center text-muted small py-3">Keine Kurse gefunden.</div>';
            return;
        }

        var html = '';
        courses.forEach(function(c) {
            var tagPills = (c.tags || []).map(function(t) {
                return '<span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px">'
                     + escHtml(t) + '</span>';
            }).join(' ');

            var exportBtn = c.hasexport
                ? '<a href="' + escHtml(c.exporturl) + '" class="btn btn-sm btn-outline-secondary kf-export-btn"'
                  + ' title="Kurs exportieren" target="_blank" rel="noopener noreferrer">'
                  + '<i class="fa fa-download"></i></a>'
                : '';

            html += '<div class="kf-item p-2 mb-2 rounded" data-courseid="' + c.id + '">'
                  + '<div class="d-flex justify-content-between align-items-start gap-1">'
                  + '<a href="' + escHtml(c.courseurl) + '" class="fw-semibold small text-decoration-none kf-course-link" target="_blank" rel="noopener noreferrer">'
                  + escHtml(c.fullname) + '</a>'
                  + exportBtn
                  + '</div>';

            if (c.categoryname) {
                html += '<div class="text-muted" style="font-size:11px"><i class="fa fa-folder-o me-1"></i>'
                      + escHtml(c.categoryname) + '</div>';
            }
            if (c.summary) {
                html += '<p class="small mb-1 mt-1 kf-summary">' + escHtml(c.summary) + '</p>';
            }
            if (tagPills) {
                html += '<div class="mt-1">' + tagPills + '</div>';
            }
            html += '</div>';
        });

        if (results) results.innerHTML = html;
    };

    // ---------------------------------------------------------------
    function escHtml(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return {
        init: function(config) {
            var state = new BlockState(config.blockid, config);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { state.init(); });
            } else {
                state.init();
            }
        },
    };
});
