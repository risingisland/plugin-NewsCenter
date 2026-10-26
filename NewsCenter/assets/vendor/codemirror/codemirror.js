/* CodeMirror Lightweight Standalone Wrapper */
window.CodeMirror = window.CodeMirror || {
  fromTextArea: function(textarea, options) {
    textarea.style.display = 'block';
    textarea.style.width = '100%';
    textarea.style.height = '400px';
    textarea.style.fontFamily = 'monospace';
    return {
      getValue: function() { return textarea.value; },
      setValue: function(val) { textarea.value = val; },
      on: function(event, callback) {
        if (event === 'change') {
          textarea.addEventListener('input', callback);
        }
      }
    };
  }
};
