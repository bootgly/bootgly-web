<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Bootgly Site</title>
   <link rel="icon" type="image/png" href="/statics/favicon.png">
   <link rel="stylesheet" href="/statics/site.css">
</head>
<body>
   <header>
      <strong>Bootgly</strong>
      <nav>
         <a href="/">Home</a>
         <a href="/about">About</a>
      </nav>
   </header>
   <main>
      @yield content;
   </main>
   <footer>Powered by the Bootgly Web platform</footer>
</body>
</html>
