<article>
   <h1><?= htmlspecialchars((string) $post['title']) ?></h1>
   <p class="meta"><?= htmlspecialchars((string) $post['created_at']) ?></p>
   <div><?= nl2br(htmlspecialchars((string) $post['body'])) ?></div>
</article>

<nav class="actions">
   <a href="/posts">← All posts</a>
   <a href="/posts/<?= (int) $post['id'] ?>/edit">Edit</a>
   <form method="post" action="/posts/<?= (int) $post['id'] ?>/delete">
      <input type="hidden" name="_token" value="<?= htmlspecialchars((string) ($token ?? '')) ?>">
      <button type="submit">Delete</button>
   </form>
</nav>
