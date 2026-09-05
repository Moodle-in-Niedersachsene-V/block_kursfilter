// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD module for block_kursfilter.
 *
 * Tags werden als Rohwert gesendet (z. B. "Oberstufe") –
 * kein Prefix-Format, da Moodle Tags ohne Präfix speichert.
 *
 * @module     block_kursfilter/filter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {
    'use strict';

    /**
     * Block state constructor.
     *
     * @param {number} blockid  Block instance ID.
     * @param {Object} config   Configuration object from PHP.
     */
    function BlockState(blockid, config) {
        this.blockid = blockid;
        this.config = config;
        this.kursbereich = '';
        this.schulform = '';
        this.fach = '';
        this.niveaustufe = '';
        this.kursname = '';
        this.debounce = null;
    }

    /**
     * Get element by partial ID.
     *
     * @param {string} id
     * @returns {HTMLElement|null}
     */
    BlockState.prototype.el = function(id) {
        return document.getElementById('kf-' + id + '-' + this.blockid);
    };

    /**
     * Get block root element.
     *
     * @returns {HTMLElement|null}
     */
    BlockState.prototype.block = function() {
        return document.getElementById('kf-block-' + this.blockid);
    };

    /**
     * Initialise event listeners.
     */
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

        // Chip-Gruppen.
        ['schulform', 'fach', 'niveaustufe'].forEach(function(filter) {
            var wrap = document.getElementById('kf-' + filter + '-chips-' + self.blockid);
            if (!wrap) {
                return;
            }
            wrap.querySelectorAll('.kf-chip').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var val = this.dataset.value;
                    if (self[filter] === val) {
                        self[filter] = '';
                        this.classList.remove('kf-chip-active');
                    } else {
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

        // Suchbegriff-Freitext.
        var searchEl = self.el('search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                self.kursname = this.value.trim();
                self.triggerSearch();
            });
        }

        // Reset.
        var resetBtn = self.block().querySelector('.kf-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                self.kursbereich = '';
                self.schulform = '';
                self.fach = '';
                self.niveaustufe = '';
                self.kursname = '';
                if (selectEl) {
                    selectEl.value = '';
                }
                if (searchEl) {
                    searchEl.value = '';
                }
                self.block().querySelectorAll('.kf-chip').forEach(function(b) {
                    b.classList.remove('kf-chip-active');
                });
                var results = self.el('results');
                if (results) {
                    results.innerHTML = '<div class="text-center text-muted small py-3">'
                        + 'Filter setzen, um Kurse zu suchen.</div>';
                }
                var count = self.el('count');
                if (count) {
                    count.textContent = '\u2013';
                }
            });
        }
    };

    /**
     * Check if at least one filter is active.
     *
     * @returns {boolean}
     */
    BlockState.prototype.hasActiveFilter = function() {
        return this.kursbereich !== '' ||
               this.schulform !== '' ||
               this.fach !== '' ||
               this.niveaustufe !== '' ||
               this.kursname !== '';
    };

    /**
     * Trigger search with debounce.
     */
    BlockState.prototype.triggerSearch = function() {
        var self = this;
        clearTimeout(self.debounce);
        self.debounce = setTimeout(function() {
            self.runSearch();
        }, 350);
    };

    /**
     * Execute AJAX search.
     */
    BlockState.prototype.runSearch = function() {
        var self = this;
        var spinner = self.el('spinner');
        var results = self.el('results');
        var count = self.el('count');

        if (!self.hasActiveFilter()) {
            if (results) {
                results.innerHTML = '<div class="text-center text-muted small py-3">'
                    + 'Filter setzen, um Kurse zu suchen.</div>';
            }
            if (count) {
                count.textContent = '\u2013';
            }
            return;
        }

        if (spinner) {
            spinner.classList.remove('d-none');
        }
        if (results) {
            results.innerHTML = '';
        }

        // Tags direkt als Rohwert senden – KEIN "schulform:"-Prefix.
        Ajax.call([{
            methodname: 'block_kursfilter_search_courses',
            args: {
                kursbereich:  parseInt(self.kursbereich, 10) || 0,
                schulform:    self.schulform,
                fach:         self.fach,
                niveaustufe:  self.niveaustufe,
                tag:          '',
                kursname:     self.kursname,
                contextid:    self.config.contextid || 1,
                limit:        100,
            },
            done: function(result) {
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                self.renderResults(result.courses || []);
            },
            fail: function(err) {
                if (spinner) {
                    spinner.classList.add('d-none');
                }
                if (results) {
                    results.innerHTML = '<div class="alert alert-warning small p-2">'
                        + escHtml(err.message || 'Suche fehlgeschlagen') + '</div>';
                }
            },
        }]);
    };

    /**
     * Render course result cards.
     *
     * @param {Array} courses
     */
    BlockState.prototype.renderResults = function(courses) {
        var self = this;
        var results = self.el('results');
        var count = self.el('count');

        if (count) {
            count.textContent = courses.length
                + ' Kurs' + (courses.length !== 1 ? 'e' : '') + ' gefunden';
        }

        if (!courses.length) {
            if (results) {
                results.innerHTML = '<div class="text-center text-muted small py-3">'
                    + 'Keine Kurse gefunden.</div>';
            }
            return;
        }

        var html = '';
        courses.forEach(function(c) {
            var tagPills = (c.tags || []).map(function(t) {
                return '<span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px">'
                     + escHtml(t) + '</span>';
            }).join(' ');

            // Download-Button: Kurssicherung (.mbz) fuer alle wenn Backup vorhanden.
            var exportBtn = c.hasexport
                ? '<a href="' + escHtml(c.exporturl) + '"'
                  + ' class="btn btn-sm btn-outline-secondary kf-export-btn"'
                  + ' title="Kurssicherung herunterladen (.mbz)"'
                  + ' download>'
                  + '<i class="fa fa-download"></i></a>'
                : '';

            // Vorschau-Button: Kurs als Trainer ohne Bearbeitungsrecht ansehen.
            var previewUrl = M.cfg.wwwroot + '/blocks/kursfilter/guest_login.php?courseid=' + c.id;
            var previewBtn = '<a href="' + escHtml(previewUrl) + '"'
                  + ' class="btn btn-sm btn-outline-primary kf-preview-btn"'
                  + ' title="Kurs als Trainer ohne Bearbeitungsrecht ansehen">'
                  + '<i class="fa fa-eye me-1"></i>Kurs ansehen</a>';

            html += '<div class="kf-item p-2 mb-2 rounded" data-courseid="' + c.id + '">'
                  + '<div class="d-flex justify-content-between align-items-start gap-1">'
                  + '<a href="' + escHtml(c.courseurl) + '"'
                  + ' class="fw-semibold small text-decoration-none kf-course-link"'
                  + ' target="_blank" rel="noopener noreferrer">'
                  + escHtml(c.fullname) + '</a>'
                  + '<div class="d-flex gap-1 flex-shrink-0">' + previewBtn + exportBtn + '</div>'
                  + '</div>';

            if (c.categoryname) {
                html += '<div class="text-muted" style="font-size:11px">'
                      + '<i class="fa fa-folder-o me-1"></i>'
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

        if (results) {
            results.innerHTML = html;
        }
    };

    /**
     * Escape HTML special characters.
     *
     * @param {*} s
     * @returns {string}
     */
    function escHtml(s) {
        if (s === null || s === undefined) {
            return '';
        }
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return {
        /**
         * Initialise block.
         *
         * @param {Object} config
         */
        init: function(config) {
            var state = new BlockState(config.blockid, config);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                state.init();
            });
            } else {
                state.init();
            }
        },
    };
});
