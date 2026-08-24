<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bootgly Auth</title>
   <link rel="icon" type="image/png" href="/statics/favicon.png">
   <link rel="stylesheet" href="/statics/auth.css">
</head>
<body>
   <header>
      <strong>Bootgly Auth</strong>
      <nav>
         <?php if (\Bootgly\WPI\Nodes\HTTP_Server_CLI::$Request->Session->check('identity')): ?>
         <a href="/account">Account</a>
         <a href="/password">Change password</a>
         <form method="post" action="/logout" class="inline">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF::mask((string) \Bootgly\WPI\Nodes\HTTP_Server_CLI::$Request->Session->get('_csrf_token', ''))) ?>">
            <button type="submit" class="link">Sign out</button>
         </form>
         <?php else: ?>
         <a href="/login">Sign in</a>
         <a href="/register">Sign up</a>
         <?php endif; ?>
      </nav>
   </header>
   <main>
      @include partials/flash;
      @yield content;
   </main>
   <footer>Powered by the Bootgly Web platform</footer>
</body>
</html>
