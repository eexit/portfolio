<?php
date_default_timezone_set('Europe/Paris');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8">
        <meta charset="utf-8">
        <title>Joris Berthelot Photography</title>
        <meta name="author" content="Joris Berthelot <photography@eexit.net>">
        <style type="text/css">
            * {
                font-family: Georgia, serif;
                color: #222;
            }
            html {
                margin: 0;
                padding: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background-color: #ddd;
            }

            header {
                position: fixed;
                width: 20em;
                height: 20em;
                background-color: #222;
                top: 50%;
                bottom: 3em;
                left: 50%;
                z-index: 100;
                margin-left: -10em;
                margin-top: -10em;
            }

            header h1 {
                margin-top: 4.2em;
                font-size: 1em;
                font-weight: normal;
                line-height: 1.2em;
            }

            header *,
            header a,
            header a:hover,
            header a:active,
            header a:visited,
            header a:link {
                color: #eee;
                display: block;
                text-align: center;
            }

            header p {
                position: relative;
                top: 18%;
            }
        </style>
    </head>
    <body>
        <header>
            <h1><a href="/">Joris Berthelot<br>Photography</a></h1>
            <p>Looks like a kick-ass update<br> is being released!</p>
            <p>Be right back...</p>
        </header>
    </body>
</html>
