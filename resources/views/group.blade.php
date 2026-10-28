<x-layout :group="$group">
  <div class="p-4">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h2>{{ $group->name }}</h2>
        <p class="text-muted mb-1">Invite code: {{ $group->invite_code }}</p>
      </div>
      <div class="d-flex gap-2">
        @if ($membership->role === 'admin')
          <form action="/group/regenerate-invite" method="POST"
            onsubmit="return confirm('Generate a new invite code? The old one will stop working.');">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <button class="btn btn-outline-secondary btn-sm">New invite code</button>
          </form>
          <form action="/group/delete" method="POST"
            onsubmit="return confirm('Delete this group for everyone? This cannot be undone.');">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <button class="btn btn-danger btn-sm">Delete group</button>
          </form>
        @else
          <form action="/group/leave" method="POST" onsubmit="return confirm('Leave this group?');">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <button class="btn btn-outline-danger btn-sm">Leave group</button>
          </form>
        @endif
      </div>
    </div>
    @error('failed')
      <div class="alert alert-danger mt-3">{{ $message }}</div>
    @enderror
    <h6 class="mt-4">Members</h6>
    <ul class="list-group">
      @foreach ($members as $m)
        <li class="list-group-item d-flex justify-content-between align-items-center">
          {{ $m->user->username ?? $m->user->email }}
          @if ($m->role === 'admin')
            <span class="badge bg-primary">Admin</span>
          @endif
           @if ($membership->role === 'admin' && $m->user_id !== auth()->id())
            <form action="/group/remove-user" method="POST"
              onsubmit="return confirm('Remove {{ $m->user->username }} from this group?');">
              @csrf
              <input type="hidden" name="group_id" value="{{ $group->id }}">
              <input type="hidden" name="removed_id" value="{{ $m->user_id }}">
              <button class="btn btn-outline-danger btn-sm">Remove</button>
            </form>
          @endif



        </li>
      @endforeach
    </ul>
  </div>
  </div>
</x-layout>