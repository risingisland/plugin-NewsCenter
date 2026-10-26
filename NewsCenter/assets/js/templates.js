/**
 * NewsCenter - Template Editor JavaScript (CodeMirror Bridge)
 * Vanilla ES6 - No jQuery dependency.
 */

document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('nc-template-code');
    const form = document.getElementById('nc-template-form');
    if (!textarea || !window.CodeMirror) return;

    const cm = window.CodeMirror.fromTextArea(textarea, {
        mode: 'htmlmixed',
        lineNumbers: true,
        lineWrapping: true,
        tabSize: 2,
        indentWithTabs: false,
        theme: 'default'
    });

    let isDirty = false;
    let isSubmitting = false;

    cm.on('change', () => {
        isDirty = true;
    });

    if (form) {
        form.addEventListener('submit', () => {
            isSubmitting = true;
            textarea.value = cm.getValue();
        });
    }

    window.addEventListener('beforeunload', (e) => {
        if (isDirty && !isSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});
