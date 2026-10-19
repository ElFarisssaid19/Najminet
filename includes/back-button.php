<?php
/**
 * includes/back-button.php
 * --------------------------------------------------------------------
 * Shared "Back" link, used everywhere the site needs one instead of a
 * one-off button. Set $backHref before including (required: where it
 * goes if there's nothing useful to go back to) and optionally
 * $backLabel (visible text, default "Back") and $backAriaLabel
 * (defaults to $backLabel).
 *
 * It's a real <a href="<?= $backHref ?>">, so it always works with no
 * JS. assets/js/buttons.js progressively enhances it: when the
 * browser really came from another page of this same site, a tap calls
 * history.back() instead of following the fallback.
 *
 * A page that is itself the result of a form POST (validation failed,
 * so the same URL re-rendered) is rendered WITHOUT that enhancement
 * automatically, so a tap can never land back on a resubmitted form --
 * it always takes the explicit fallback. Pass $backForceFallback
 * explicitly only to override that default.
 *
 * It is a button of the shared system (assets/css/buttons.css: secondary,
 * small, arrow icon); assets/js/buttons.js adds the history.back()
 * behaviour. Both are loaded with the same ?v= cache-busting as every
 * other asset. Wrap the include in a .back-btn-row to place it top-left,
 * under the header (the auth pages put it at the right of their top bar
 * instead, next to the logo).
 * --------------------------------------------------------------------
 */
$backHref      = $backHref ?? '/landing.php';
$backLabel     = $backLabel ?? 'Back';
$backAriaLabel = $backAriaLabel ?? $backLabel;
if (!isset($backForceFallback)) {
    // Rendered as the direct response to a POST (e.g. a form that failed
    // validation and re-rendered itself): never offer history.back() here,
    // or "Back" could walk into the same resubmitted request.
    $backForceFallback = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
?>
<a class="nn-btn nn-btn--secondary nn-btn--sm back-btn" href="<?= htmlspecialchars($backHref) ?>" aria-label="<?= htmlspecialchars($backAriaLabel) ?>"<?= $backForceFallback ? '' : ' data-back-smart' ?>>
  <span class="nn-icon nn-icon--arrow-left" aria-hidden="true"></span>
  <span><?= htmlspecialchars($backLabel) ?></span>
</a>
<?php
// Defensive: never let one include's fallback leak into a second
// include() of this file later on the same page.
unset($backHref, $backLabel, $backAriaLabel, $backForceFallback);
