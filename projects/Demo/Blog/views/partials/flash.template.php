<?php $flash = \Bootgly\WPI\Nodes\HTTP_Server_CLI::$Request->Session->pull('flash'); ?>
<?php if ($flash !== null): ?>
<p class="flash"><?= htmlspecialchars((string) $flash) ?></p>
<?php endif; ?>
