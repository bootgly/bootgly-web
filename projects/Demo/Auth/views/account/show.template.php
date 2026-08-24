<h1>Your account</h1>

<?php if ($verified === false): ?>
<div class="banner">
   <p>Your e-mail is not verified yet. Check your inbox — or resend the link:</p>
   <form method="post" action="/verify" class="inline">
      <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">
      <button type="submit">Resend verification link</button>
   </form>
</div>
<?php endif; ?>

<dl>
   <dt>E-mail</dt>
   <dd>
      <?= htmlspecialchars((string) $email) ?>
      <?php if ($verified): ?><span class="badge">verified</span><?php endif; ?>
   </dd>
</dl>

<p><a href="/password">Change password</a></p>
