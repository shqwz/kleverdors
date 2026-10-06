<?php
/** Первый экран по утверждённому владельцем макету. */
defined('_JEXEC') or die;

return <<<'HTML'
<section class="kdc-reference-hero" aria-labelledby="kdc-reference-title">
	<picture>
		<source media="(max-width: 1199px) and (orientation: portrait)" srcset="/images/hero/green-interior-mobile-20261006.png" width="941" height="1672">
		<img class="kdc-reference-image" src="/images/hero/green-interior-20261006.png" width="1672" height="941" alt="" fetchpriority="high" decoding="async">
	</picture>
	<div class="kdc-reference-brand kdc-reference-logo" aria-label="Клевердорс" role="img">
		<img class="kdc-reference-mark" src="/images/hero/kleverdors-mark.svg" width="197" height="264" alt="">
		<img class="kdc-reference-wordmark" src="/images/hero/kleverdors-wordmark-smooth-20261006.svg" width="600" height="175" alt="">
	</div>
	<div class="kdc-reference-copy">
		<h1 id="kdc-reference-title">Двери для<br>вашего интерьера.</h1>
		<p>Из наших коллекций или по вашему эскизу.<br>Размер, цвет и детали — под ваш проект.</p>
	</div>
</section>
HTML;
