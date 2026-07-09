<h1>New post</h1>

<form method="post" action="/posts">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      Title
      <input type="text" name="title" maxlength="160" required>
   </label>
   <label>
      Body
      <textarea name="body" rows="8" required></textarea>
   </label>
   <button type="submit">Publish</button>
</form>
