<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bootgly SPA</title>
   <link rel="icon" type="image/svg+xml" href="/statics/favicon.svg">
   <link rel="stylesheet" href="/statics/landing.css">
</head>
<body>
   <main>
      <h1>Bootgly SPA</h1>
      <p>
         Two single-page builds — React and Vue, both made by Vite — served by
         <code>Web\App\SPA</code> on their own prefixes, over one JSON API at
         <code>/api/tasks</code>. Reload any page inside them: the server answers
         the build's <code>index.html</code> and the client router takes over.
      </p>

      <div class="cards">
<?php foreach (['react' => 'React', 'vue' => 'Vue'] as $name => $label): ?>
         <section class="card <?= $name ?>">
            <h2><?= $label ?></h2>
<?php if ($builds[$name] === true): ?>
            <p><a href="/<?= $name ?>/">Open /<?= $name ?>/ →</a></p>
<?php else: ?>
            <p class="missing">Not built yet — <code>/<?= $name ?>/</code> answers 503.</p>
<?php endif; ?>
         </section>
<?php endforeach; ?>
      </div>

<?php if (in_array(false, $builds, true)): ?>
      <p class="hint">
         Builds are made on start when missing or older than their sources (Node.js and npm required).
         By hand: <code>cd projects/Demo/SPA &amp;&amp; npm install &amp;&amp; npm run build</code>.
      </p>
<?php endif; ?>

      <p class="hint">API: <a href="/api/tasks">/api/tasks</a></p>
   </main>
</body>
</html>
