<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ isset($title) ? $title . ' - Calendar' : 'Calendar' }}</title>
  <link rel="preconnect" href="<https://fonts.bunny.net>">
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="d-flex flex-column min-vh-100">
  @unless(isset($hideNav))
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
      <div class="container-fluid">
        <a class="navbar-brand" href="/">Calendar App</a>
        {{-- i had claude code write this button --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        {{-- end of claude --}}
        <div class="collapse navbar-collapse" id="navbarNav">
          <div class="d-flex gap-2 ms-auto">
            @auth
                    <form action="/logout" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-outline-secondary btn-sm">Log Out</button>
              </form>
            @else
              <a href="/login" class="btn btn-outline-secondary btn-sm">Sign In</a>
              <a href="/register" class="btn btn-primary btn-sm">Sign Up</a>
            @endauth

          </div>
        </div>
      </div>
    </nav>
  @endunless

  <main class="">
    {{ $slot }}
  </main>
  @unless(isset($hideFooter))
    <footer class="text-center py-4 bg-light small">
      <div>
        <p>© 2026 Calendar App</p>
      </div>
    </footer>
  @endunless
</body>

</html>