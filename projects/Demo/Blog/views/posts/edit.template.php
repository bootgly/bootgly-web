<h1>Edit post</h1>

<form method="post" action="/posts/<?= (int) $post['id'] ?>">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      Title
      <input type="text" name="title" maxlength="160" value="<?= htmlspecialchars((string) $post['title']) ?>" required>
   </label>
   <label>
      Body
      <textarea name="body" rows="8" required><?= htmlspecialchars((string) $post['body']) ?></textarea>
   </label>
   <button type="submit">Save</button>
</form>
