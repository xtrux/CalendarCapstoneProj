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
  @livewireStyles

</head>

<body class="d-flex flex-column min-vh-100">
  @unless(isset($hideNav))
    <nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm border-bottom">
      <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
          @auth
            <button class="navbar-toggler d-inline-flex" type="button" data-bs-toggle="collapse"
              data-bs-target="#groupSidebar" aria-expanded="true" aria-controls="groupSidebar"
              title="Toggle groups sidebar">
              <span class="navbar-toggler-icon"></span>
            </button>
          @endauth
          <a class="navbar-brand fw-bold text-primary mb-0" href="/">Calendar App</a>




          <button class="navbar-toggler " type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
          <div class="d-flex align-items-center gap-2 ms-auto mt-3 mt-lg-0">
            @auth
              @if (auth()->user()->profile_picture)
                <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}" alt="Profile picture"
                  class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
              @else
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                  style="width: 36px; height: 36px; color: white; font-size: 0.85rem;">
                  {{ auth()->user()->initials() }}
                </div>
              @endif

              <form action="/logout" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill">Log Out</button>
              </form>
              <a href="/settings" class="btn btn-primary btn-sm rounded-pill">Settings</a>
            @else
              <a href="/login" class="btn btn-outline-secondary btn-sm rounded-pill">Sign In</a>
              <a href="/register" class="btn btn-primary btn-sm rounded-pill">Sign Up</a>
            @endauth
          </div>
        </div>
      </div>
    </nav>
  @endunless


  <div class="d-flex flex-grow-1">
    @auth
      @unless(isset($hideNav))
        <div class="collapse collapse-horizontal show" id="groupSidebar">
          <aside class="border-end bg-light p-3 h-100" style="width: 220px;">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">My Groups</h6>
            <div class="list-group list-group-flush mb-3">
              @foreach (auth()->user()->groups as $g)
                <a href="{{ '/group/' . $g->id }} "wire:navigate
                  class="list-group-item list-group-item-action border-0 rounded mb-1 {{ (isset($group) && $group->id === $g->id) ? 'active' : '' }}">
                  {{ $g->name }}
                </a>
              @endforeach
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal"
              data-bs-target="#createGroupModal">
              + Create Group
            </button>
            


          </aside>
        </div>
      @endunless
    @endauth
    <main class="flex-grow-1 d-flex flex-column">
      {{ $slot }}
    </main>
  </div>

  @unless(isset($hideFooter))
    <footer class="text-center py-4 bg-light small">
      <div>
        <p>© 2026 Calendar App</p>
      </div>
    </footer>
  @endunless
  @if ($errors->has('name'))
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('createGroupModal')).show();
      });
    </script>
  @endif
            <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel"
              aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form action="/group/create" method="POST">
                    @csrf
                    <div class="modal-header">
                      <h5 class="modal-title" id="createGroupModalLabel">Create a Group</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <label for="groupName" class="form-label">Group name</label>
                      <input type="text" id="groupName" name="name" class="form-control" placeholder="e.g. Study Group"
                        required autofocus>
                      @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
@livewireScripts
</body>

</html>