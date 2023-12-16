<?php

use MyApp\helper\Assets;

?><!doctype html>
<html lang="en-US">
<head>
    <title>Example PHP Site</title>
    <meta charset="utf-8" />

    <meta name="author" content="Michael Butler: https://butlerpc.net">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

    <?=Assets::outputCssFiles()?>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon Stuff -->
    <meta name="theme-color" content="#ffffff">

    <script>var jsFunctions = [];</script>
    <?php echo getTrackers(); ?>
</head>
<body>

<div class="container-md">

    <div class="row">
        <div class="col">
            <header>
                <h1>Example PHP Site</h1>
            </header>
        </div>
    </div>

    <?=($data['body_content'] ?? '')?>

</div>

<!-- Footer Start -->
<div class="footer-wrap">
    <div class="container-md">
        <div class="row">
            <div class="col footer_column">    
                Footer
            </div>

            <div class="col footer_column">
                Footer
            </div>

            <div class="col footer_column">
                Footer
            </div>
        </div>
    </div>
</div><!-- end footer-wrap -->

<?=Assets::outputJsFiles()?>

</body>
</html>
