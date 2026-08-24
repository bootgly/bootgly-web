<h1>Create your account</h1>

@include partials/errors;

<form method="post" action="/register">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      E-mail
      <input type="email" name="email" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" maxlength="254" required>
   </label>
   <label>
      Password
      <input type="password" name="password" minlength="8" required>
   </label>
   <label>
      Confirm password
      <input type="password" name="password_confirmation" minlength="8" required>
   </label>
   <button type="submit">Sign up</button>
</form>

<p>Already have an account? <a href="/login">Sign in</a>.</p>
