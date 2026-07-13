<h1>Change your password</h1>

@include partials/errors;

<form method="post" action="/password">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      Current password
      <input type="password" name="current" required>
   </label>
   <label>
      New password
      <input type="password" name="password" minlength="8" required>
   </label>
   <label>
      Confirm new password
      <input type="password" name="password_confirmation" minlength="8" required>
   </label>
   <button type="submit">Change password</button>
</form>

<p>Changing your password signs out every other device.</p>
