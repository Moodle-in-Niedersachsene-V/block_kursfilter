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
 * Course search, result cards and star rating of the Course Filter block.
 *
 * @module     block_kursfilter/filter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';
import Config from 'core/config';

/** Delay after the last input before the search runs, in milliseconds. */
const SEARCH_DELAY = 350;

/** Number of stars of a rating. */
const MAX_STARS = 5;

/**
 * Initialise one block instance.
 *
 * @param {number} blockid Block instance ID.
 */
export const init = (blockid) => {
    const root = document.getElementById(`kf-block-${blockid}`);
    if (!root) {
        return;
    }
    const results = root.querySelector('[data-region="results"]');
    const count = root.querySelector('[data-region="count"]');
    const spinner = root.querySelector('[data-region="spinner"]');
    const category = root.querySelector('[data-filter="category"]');
    const searchterm = root.querySelector('[data-filter="searchterm"]');
    const emptyResults = results.innerHTML;
    const emptyCount = count.textContent;
    const chosen = {schooltype: '', subject: '', level: ''};
    let timer = null;

    const filters = () => ({
        category: parseInt(category.value, 10) || 0,
        ...chosen,
        searchterm: searchterm.value.trim(),
    });

    const showEmpty = () => {
        results.innerHTML = emptyResults;
        count.textContent = emptyCount;
    };

    const search = () => {
        const args = filters();
        if (!args.category && !args.schooltype && !args.subject && !args.level && !args.searchterm) {
            showEmpty();
            return;
        }
        spinner.classList.remove('d-none');
        Ajax.call([{methodname: 'block_kursfilter_search_courses', args}])[0]
            .then((result) => renderResults(result.courses))
            .catch((error) => {
                results.innerHTML = '';
                return Notification.exception(error);
            })
            .finally(() => spinner.classList.add('d-none'));
    };

    const renderResults = async(courses) => {
        const context = {courses: courses.map((course) => ({...course, hastags: course.tags.length > 0}))};
        const {html, js} = await Templates.renderForPromise('block_kursfilter/results', context);
        Templates.replaceNodeContents(results, html, js);
        count.textContent = await getString('results_count', 'block_kursfilter', courses.length);
        results.querySelectorAll('.kf-stars').forEach(initStars);
    };

    const scheduleSearch = () => {
        clearTimeout(timer);
        timer = setTimeout(search, SEARCH_DELAY);
    };

    root.addEventListener('click', (e) => {
        const chip = e.target.closest('.kf-chip');
        if (chip) {
            const filter = chip.dataset.filter;
            const isActive = chosen[filter] === chip.dataset.value;
            root.querySelectorAll(`.kf-chip[data-filter="${filter}"]`).forEach((other) => setChip(other, false));
            setChip(chip, !isActive);
            chosen[filter] = isActive ? '' : chip.dataset.value;
            scheduleSearch();
        } else if (e.target.closest('[data-action="reset"]')) {
            clearTimeout(timer);
            category.value = '';
            searchterm.value = '';
            Object.keys(chosen).forEach((filter) => {
                chosen[filter] = '';
            });
            root.querySelectorAll('.kf-chip').forEach((other) => setChip(other, false));
            showEmpty();
        }
    });
    category.addEventListener('change', scheduleSearch);
    searchterm.addEventListener('input', scheduleSearch);
};

/**
 * Show a chip as chosen or not.
 *
 * @param {HTMLElement} chip Chip button.
 * @param {boolean} isActive Whether the chip is chosen.
 */
const setChip = (chip, isActive) => {
    chip.classList.toggle('kf-chip-active', isActive);
    chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
};

/**
 * Fill a star widget: fixed if this visitor rated the course, otherwise clickable.
 *
 * @param {HTMLElement} widget Star widget of a result card.
 */
const initStars = async(widget) => {
    if (widget.dataset.alreadyrated === '1') {
        await showRated(widget, parseInt(widget.dataset.userrating, 10) || 0);
        return;
    }
    const labels = await Promise.all(
        Array.from({length: MAX_STARS}, (_, i) => getString('rating_star', 'block_kursfilter', i + 1))
    );
    widget.replaceChildren(...labels.map((label, i) => {
        const star = document.createElement('button');
        star.type = 'button';
        star.className = 'kf-star';
        star.textContent = '☆';
        star.setAttribute('aria-label', label);
        star.addEventListener('mouseenter', () => highlightStars(widget, i + 1));
        star.addEventListener('mouseleave', () => highlightStars(widget, 0));
        star.addEventListener('click', () => rate(widget, i + 1));
        return star;
    }));
};

/**
 * Send a rating to rate.php and show the result.
 *
 * @param {HTMLElement} widget Star widget.
 * @param {number} stars Rating 1 to 5.
 */
const rate = async(widget, stars) => {
    const body = new URLSearchParams({courseid: widget.dataset.courseid, stars, sesskey: Config.sesskey});
    try {
        const response = await fetch(`${Config.wwwroot}/blocks/kursfilter/rate.php`, {method: 'POST', body});
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error);
        }
        await showRated(widget, stars);
    } catch {
        Notification.addNotification({
            message: await getString('error_rating_failed', 'block_kursfilter'),
            type: 'error',
        });
    }
};

/**
 * Show a fixed rating.
 *
 * @param {HTMLElement} widget Star widget.
 * @param {number} stars Rating 1 to 5.
 */
const showRated = async(widget, stars) => {
    const note = document.createElement('span');
    note.className = 'kf-star-note';
    note.textContent = await getString('rating_done', 'block_kursfilter');
    const fixedStars = Array.from({length: MAX_STARS}, (_, i) => {
        const star = document.createElement('span');
        star.className = 'kf-star kf-star-fixed' + (i < stars ? ' kf-star-filled' : '');
        star.textContent = i < stars ? '★' : '☆';
        star.setAttribute('aria-hidden', 'true');
        return star;
    });
    widget.replaceChildren(...fixedStars, note);
};

/**
 * Highlight the stars up to a value while hovering.
 *
 * @param {HTMLElement} widget Star widget.
 * @param {number} value Highlight up to this star (0 = none).
 */
const highlightStars = (widget, value) => {
    widget.querySelectorAll('.kf-star').forEach((star, i) => {
        star.classList.toggle('kf-star-filled', i < value);
        star.textContent = i < value ? '★' : '☆';
    });
};
