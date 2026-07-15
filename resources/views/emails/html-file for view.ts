<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Annual Report View</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; text-align: center; }
  
  #viewer-wrapper {
    position: relative;
    display: inline-block;
    text-align: center;
  }

  #pdf-container {
    display: inline-block; /* so canvas can center inside #viewer-wrapper */
    margin: 0 auto;
  }

  #pdf-container canvas { 
    border: 1px solid #ccc; 
    margin-bottom: 20px; 
    display: block; 
    margin-left: auto; 
    margin-right: auto; 
  }

  .watermark {
    position: absolute;
    color: rgba(200, 200, 200, 0.3);
    font-size: 40px;
    transform: rotate(-30deg);
    pointer-events: none;
    top: 50px;
    left: 50%;
    transform: translateX(-50%) rotate(-30deg);
  }
</style>
</head>
<body>

<h2>Annual Report Viewer</h2>
<div id="viewer-wrapper">
  <div id="pdf-container"></div>
  <div class="watermark">CONFIDENTIAL</div>
</div>

<script>
  let url = 'https://.com/financial-reporting-pdf/APPL_FS_FY_2024-2025.pdf';



// Disable right-click
document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
  alert('Right-click is disabled on this page.');
});

// Disable common keyboard shortcuts
document.addEventListener('keydown', function(e) {
  // Ctrl + P (Print)
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
    e.preventDefault();
    alert('Printing is disabled on this page.');
  }

  // Ctrl + S (Save Page)
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
    e.preventDefault();
    alert('Saving this page is disabled.');
  }

  // Ctrl + U (View source)
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'u') {
    e.preventDefault();
    alert('Viewing source is disabled.');
  }

  // Ctrl + Shift + I / J (DevTools)
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i', 'j', 'c'].includes(e.key.toLowerCase())) {
    e.preventDefault();
    alert('Developer tools are disabled.');
  }

  // F12 key
  if (e.key === 'F12') {
    e.preventDefault();
    alert('Developer tools are disabled.');
  }
});

// Optional: Overlay to discourage screenshots (not foolproof)
const overlay = document.createElement('div');
overlay.style.position = 'fixed';
overlay.style.top = '0';
overlay.style.left = '0';
overlay.style.width = '100%';
overlay.style.height = '100%';
overlay.style.pointerEvents = 'none';
overlay.style.background = 'rgba(0,0,0,0.01)'; // almost invisible
document.body.appendChild(overlay);

// PDF rendering
pdfjsLib.getDocument(url).promise.then(async pdf => {
  const container = document.getElementById('pdf-container');

  for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
    const page = await pdf.getPage(pageNum);
    const viewport = page.getViewport({ scale: 1.5 });
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    container.appendChild(canvas);
    await page.render({ canvasContext: context, viewport: viewport }).promise;
  }
}).catch(err => {
  console.error(err);
  document.getElementById('pdf-container').innerHTML = "<p style='color:red;'>Unable to load PDF. Please check the file URL.</p>";
});
</script>