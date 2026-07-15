<!DOCTYPE html>
<html>
<head>
    <title>View Document</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="icon" type="image/png" href="/public/assets/theme/src/images/logo/favicon-icon.png">
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
</style>
</head>
<body>
<div id="viewer">
  <iframe src="{{ $file_url }}"></iframe>
</div>
</body>
</html>
