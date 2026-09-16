/**
 * Auto-submit the category filter when the autocomplete selection changes.
 *
 * @module     local_thlevasys/category_filter
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Navigate to the request page with the selected category filter.
 *
 * @param {string} categoryid Selected category id ("0" or "" = all).
 * @param {string} search Optional current search term to preserve.
 */
const navigateToCategory = (categoryid, search) => {
    const url = new URL(window.location.href);
    url.search = '';
    if (categoryid && categoryid !== '0') {
        url.searchParams.set('categoryid', categoryid);
    }
    if (search) {
        url.searchParams.set('search', search);
    }
    window.location.assign(url.toString());
};

/**
 * Initialise auto-submit for the category autocomplete filter.
 */
export const init = () => {
    const select = document.getElementById('id_categoryid');
    if (!select) {
        return;
    }

    let lastValue = select.value;
    let navigating = false;

    select.addEventListener('change', () => {
        if (navigating || select.value === lastValue) {
            return;
        }

        navigating = true;
        lastValue = select.value;

        // Blur first so Moodle can close the suggestions list without the aria-hidden focus warning.
        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }

        const search = new URL(window.location.href).searchParams.get('search') || '';

        // Defer navigation until after autocomplete finishes its async close/update work.
        window.setTimeout(() => {
            navigateToCategory(select.value, search);
        }, 50);
    });
};
