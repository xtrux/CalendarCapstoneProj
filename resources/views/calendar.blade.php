
<x-layout :group="$group">
  <div class="p-4">
    <h2>{{ $group->name }}</h2>
    <p class="text-muted">Invite code: {{ $group->invite_code }}</p>

    <h6 class="mt-4">Calendar</h6>
    <div grid-template-columns: repeat(7, 1fr)>
        
    </div>
  </div>
</x-layout>
