<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bootgly Blog</title>
   <link rel="icon" type="image/png" href="/statics/favicon.png">
   <link rel="stylesheet" href="/statics/blog.css">
</head>
<body>
   <header>
      <strong>Bootgly Blog</strong>
      <nav>
         <a href="/posts">Posts</a>
         <a href="/posts/create">New post</a>
      </nav>
   </header>
   <main>
      @include partials/flash;
      @yield content;
   </main>
   <footer>Powered by the Bootgly Web platform</footer>
</body>
</html>
