<h1>Sign in</h1>

@include partials/errors;

<form method="post" action="/login">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      E-mail
      <input type="email" name="email" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" maxlength="254" required>
   </label>
   <label>
      Password
      <input type="password" name="password" required>
   </label>
   <label class="checkbox">
      <input type="checkbox" name="remember" value="1"> Remember me on this device
   </label>
   <button type="submit">Sign in</button>
</form>

<p><a href="/forgot">Forgot your password?</a> · <a href="/register">Create an account</a></p>
