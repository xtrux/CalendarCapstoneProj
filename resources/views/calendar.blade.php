<x-layout :group="$group">
  <div class="p-4">
    <h2>{{ $group->name }}</h2>
    <p class="text-muted">Invite code: {{ $group->invite_code }}</p>


    {{-- claude generated the buttons for me --}}
    <div class="d-flex justify-content-between align-items-center my-3">
      <a class="btn btn-outline-secondary btn-sm"
        href="/group/{{ $group->id }}/calendar?year={{ $prev->year }}&month={{ $prev->month }}">&laquo; Prev</a>
      <h5 class="mb-0">{{ $first->format('F Y') }}</h5>
      <a class="btn btn-outline-secondary btn-sm"
        href="/group/{{ $group->id }}/calendar?year={{ $next->year }}&month={{ $next->month }}">Next &raquo;</a>
    </div>

      
    <h6 class="mt-4">Calendar</h6>
    <div style="display: grid ;grid-template-columns: repeat(7, 1fr)">
    @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName )
      <div class="text-center fw-bold small">{{ $dayName }}</div>
    @endforeach
    @foreach ($cells as $cell)
    <div class="border p-2 {{ $cell['inMonth'] ?'bg-white' : 'bg-light text-muted' }}"style="min-height: 80px;">
      <p class="cellText">{{$cell['date']->day }}</p>

    </div>

      
    @endforeach
    </div>
  </div>
</x-layout>