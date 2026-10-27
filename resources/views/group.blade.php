
<x-layout :group="$group">
  <div class="p-4">
    <h2>{{ $group->name }}</h2>
    <p class="text-muted">Invite code: {{ $group->invite_code }}</p>

    <h6 class="mt-4">Members</h6>
    <ul class="list-group">
      @foreach ($members as $m)
        <li class="list-group-item d-flex justify-content-between">
          {{ $m->user->username ?? $m->user->email }}
          @if ($m->role === 'admin')
            <span class="badge bg-primary">Admin</span>
          @endif
        </li>
      @endforeach
    </ul>
  </div>
</x-layout>
