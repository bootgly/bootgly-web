<?php if (empty($errors) === false): ?>
<ul class="errors">
   <?php foreach ($errors as $field => $messages): ?>
      <?php foreach ((array) $messages as $message): ?>
   <li><?= htmlspecialchars((string) $message) ?></li>
      <?php endforeach; ?>
   <?php endforeach; ?>
</ul>
<?php endif; ?>
