<h1>Choose a new password</h1>

@include partials/errors;

<form method="post" action="/reset/<?= htmlspecialchars((string) $selector) ?>/<?= htmlspecialchars((string) $verifier) ?>">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      New password
      <input type="password" name="password" minlength="8" required>
   </label>
   <label>
      Confirm new password
      <input type="password" name="password_confirmation" minlength="8" required>
   </label>
   <button type="submit">Reset password</button>
</form>
