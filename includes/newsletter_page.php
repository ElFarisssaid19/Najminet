<?php
declare(strict_types=1);

/**
 * Renders the small standalone page shown after a visitor follows a
 * confirmation or unsubscribe link.
 */
function newsletter_render_page(string $heading, string $body, bool $ok = true): string
{
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $mark = $ok ? '✓' : '!';
    $markColour = $ok ? '#16a34a' : '#dc2626';

    return sprintf(
        '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="robots" content="noindex">
<title>%s — NajmiNet</title>
<link rel="stylesheet" href="/assets/fonts/fonts-1.css"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin>
<style>
  *,*::before,*::after{box-sizing:border-box;}
  body{margin:0;min-height:100vh; min-height:100dvh;display:grid;place-items:center;padding:24px 16px;
       font-family:Sora,system-ui,sans-serif;background:#0f1020;color:#ececf5;}
  .card{max-width:520px;width:100%%;background:#191a2e;border:1px solid #2a2b45;
        border-radius:16px;padding:40px 32px;text-align:center;}
  .mark{font-size:2.6rem;line-height:1;margin-bottom:18px;color:%s;}
  h1{font-size:clamp(1.2rem,1rem + 1vw,1.35rem);margin:0 0 12px;}
  p{margin:0 0 26px;color:#a9aac4;font-size:.95rem;line-height:1.6;}
  a.home{display:inline-flex;align-items:center;justify-content:center;min-height:44px;background:linear-gradient(135deg,#7c5cff,#4f8dff);color:#fff;
         text-decoration:none;padding:12px 26px;border-radius:10px;font-size:.9rem;}
  @media (max-width:479.98px){ .card{padding:32px 20px;} }
</style>
</head>
<body>
  <main class="card">
    <div class="mark" aria-hidden="true">%s</div>
    <h1>%s</h1>
    <p>%s</p>
    <a class="home" href="/landing.php">%s</a>
  </main>
</body>
</html>',
        $escape($heading),
        $markColour,
        $mark,
        $escape($heading),
        $escape($body),
        'Go Home'
    );
}
