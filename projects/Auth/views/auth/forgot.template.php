<h1>Reset your password</h1>

@include partials/errors;

<p>Tell us your e-mail and we will send you a reset link.</p>

<form method="post" action="/forgot">
   <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
   <label>
      E-mail
      <input type="email" name="email" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>" maxlength="254" required>
   </label>
   <button type="submit">Send reset link</button>
</form>

<p><a href="/login">Back to sign in</a></p>
