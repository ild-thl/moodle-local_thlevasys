/**
 * Persist evaluation request selection via AJAX.
 *
 * @module     local_thlevasys/toggle_request
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    checkbox: 'input.local-thlevasys-select[type="checkbox"]',
    group: 'select.local-thlevasys-group',
    language: 'select.local-thlevasys-language',
};

/**
 * Read group and language values for a table row.
 *
 * @param {string} rowkey Row key (courseid_teacherid).
 * @returns {{groupid: number, lang: string}}
 */
const getRowValues = (rowkey) => {
    const groupSelect = document.getElementById(`group_${rowkey}`);
    const languageSelect = document.getElementById(`language_${rowkey}`);

    return {
        groupid: groupSelect ? parseInt(groupSelect.value, 10) || 0 : 0,
        lang: languageSelect ? languageSelect.value : 'de',
    };
};

/**
 * Call the external toggle webservice.
 *
 * @param {HTMLInputElement} checkbox Checkbox element.
 * @param {boolean} selected Whether the request should exist.
 * @returns {Promise}
 */
const toggleRequest = (checkbox, selected) => {
    const rowkey = checkbox.dataset.rowkey;
    const values = getRowValues(rowkey);

    checkbox.disabled = true;

    return Ajax.call([{
        methodname: 'local_thlevasys_toggle_request',
        args: {
            courseid: parseInt(checkbox.dataset.courseid, 10),
            editingteacher: parseInt(checkbox.dataset.editingteacher, 10),
            groupid: values.groupid,
            lang: values.lang,
            selected: selected,
        },
    }])[0]
        .then((response) => {
            checkbox.checked = !!response.selected;
            return response;
        })
        .catch((error) => {
            checkbox.checked = !selected;
            Notification.exception(error);
        })
        .always(() => {
            checkbox.disabled = false;
        });
};

/**
 * Initialise checkbox and select change handlers.
 */
export const init = () => {
    document.addEventListener('change', (event) => {
        const checkbox = event.target.closest(SELECTORS.checkbox);
        if (checkbox) {
            toggleRequest(checkbox, checkbox.checked);
            return;
        }

        const select = event.target.closest(`${SELECTORS.group}, ${SELECTORS.language}`);
        if (!select) {
            return;
        }

        const rowkey = select.dataset.rowkey;
        if (!rowkey) {
            return;
        }

        const relatedCheckbox = document.getElementById(`selected_${rowkey}`);
        if (relatedCheckbox && relatedCheckbox.checked) {
            toggleRequest(relatedCheckbox, true);
        }
    });
};
