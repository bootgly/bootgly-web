<h1>Posts</h1>

<?php foreach ($Posts as $Post): ?>
<article>
   <h2><a href="/posts/<?= $Post->id ?>"><?= htmlspecialchars($Post->title) ?></a></h2>
   <p><?= htmlspecialchars(mb_strimwidth($Post->body, 0, 120, '…')) ?></p>
</article>
<?php endforeach; ?>

<?php if ($Posts === []): ?>
<p>No posts yet. <a href="/posts/create">Write the first one →</a></p>
<?php endif; ?>

<nav class="pages">Page <?= (int) $page ?> of <?= (int) $pages ?></nav>
