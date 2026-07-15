<!DOCTYPE html>
<html>
<head>
<style>
  html, body {
    margin: 0;
    padding: 0;
    overflow-y: auto;   /* page scrollbar */
    overflow-x: hidden;
    height: 100%;
  }

  #viewer {
    position: relative;
    height: 100vh;
    overflow: hidden;   /* prevents iframe scrollbar */
  }

  iframe {
    width: 100%;
    height: 100vh;
    border: none;
  }

  .overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: transparent;
    z-index: 10;
    pointer-events: none; /* ALLOW scrolling and clicks to pass through */
  }
</style>
</head>
<body>

<div id="viewer">
  <iframe src="{{ $file_url }}"></iframe>
  <div class="overlay"></div>
</div>

<script>
// Disable right click on PAGE only (won't affect iframe content)
document.addEventListener('contextmenu', e => {
    e.preventDefault();
}, true);

// Disable keyboard shortcuts on PAGE only
document.addEventListener('keydown', e => {
    // Disable common dev tools keys and ctrl/meta combos
    if (
        e.ctrlKey || e.metaKey ||
        e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && ['I', 'J'].includes(e.key.toUpperCase())) ||
        (e.ctrlKey && e.key.toUpperCase() === 'U')
    ) {
        e.preventDefault();
    }
}, true);

// Disable mouse down on PAGE only
document.addEventListener('mousedown', e => {
    e.preventDefault();
}, true);
</script>

</body>
</html>
