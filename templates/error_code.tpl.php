<?php


?>

<div class="error-404-page">

<?php if ($data['code'] === 404): ?>
    <div class="top-banner">
        <div class="inner">
            <h1>Error 404: Page Not Found</h1>
            <p>This page doesn't seem to exist, at least anymore...</p>
        </div>
    </div>
<?php else: ?>
    <div class="top-banner">
        <div class="inner">
            <h1>Error <?=h($data['code'])?>: We're sorry, but an error occurred!</h1>
        </div>
    </div>
<?php endif; ?>


</div>
