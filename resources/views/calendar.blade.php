<x-layout :group="$group">
  <style>
    .calendar-cell {
      display: block;
      width: 100%;
      height: 100%;
      min-height: 80px;
      text-align: left;
      background: #fff;
      border-radius: 0;
    }

    .calendar-cell:hover {
      background: #f1f3f5;
    }

    .calendar-cell:focus-visible {
      outline: 2px solid #0d6efd;
      outline-offset: -2px;
    }

    .calendar-cell.is-busy {
      background: #f8d7da;
    }

    .calendar-cell .cell-status {
      color: #842029;
    }
  </style>
  <div class="p-4">
    <h2>{{ $group->name }}</h2>
    <p class="text-muted">Invite code: {{ $group->invite_code }}</p>


    {{-- claude generated the buttons for me --}}
    <a class="btn btn-outline-secondary btn-sm" href="/group/{{ $group->id }}" aria-label="Group settings">
      Group settings
    </a>
    <div class="d-flex justify-content-between align-items-center my-3">

      <a class="btn btn-outline-secondary btn-sm"
        href="/group/{{ $group->id }}/calendar?year={{ $prev->year }}&month={{ $prev->month }}">&laquo; Prev</a>
      <h5 class="mb-0">{{ $first->format('F Y') }}</h5>
      <a class="btn btn-outline-secondary btn-sm"
        href="/group/{{ $group->id }}/calendar?year={{ $next->year }}&month={{ $next->month }}">Next &raquo;</a>
    </div>


    <h6 class="mt-4">Calendar</h6>
    <div style="display: grid ;grid-template-columns: repeat(7, 1fr)">
      @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
        <div class="text-center fw-bold small">{{ $dayName }}</div>
      @endforeach
      @foreach ($cells as $cell)
        @if ($cell['inMonth'])
          <form action="/group/{{ $group->id }}/availability" method="POST" class="availability-toggle m-0">
            @csrf
            <input type="hidden" name="date" value="{{ $cell['date']->format('Y-m-d') }}">
            <button type="submit" class="calendar-cell border p-2 {{ $cell['meBusy'] ? 'is-busy' : '' }}"
              aria-pressed="{{ $cell['meBusy'] ? 'true' : 'false' }}">
              <span class="d-block">{{ $cell['date']->day }}</span>
              <small class="cell-status">{{ $cell['meBusy'] ? 'Busy' : '' }}</small>
            </button>
          </form>
        @else
          <div class="border p-2 bg-light text-muted" style="min-height: 80px;">
            <p class="mb-0">{{ $cell['date']->day }}</p>
          </div>
        @endif
      @endforeach
      {{-- @foreach ($cells as $cell)
      <div class="border p-2 {{ $cell['inMonth'] ? 'bg-white' : 'bg-light text-muted' }}" style="min-height: 80px;">
        <p class="mb-0">{{$cell['date']->day }}</p>

      </div>


      @endforeach --}}
    </div>
  </div>
  <script>
    if (!window.availabilityToggleBound) {
      window.availabilityToggleBound = true;

      document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form.availability-toggle');
        if (!form) return;

        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;

        try {
          const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new FormData(form),
          });
          if (!response.ok) throw new Error(response.status);

          const data = await response.json();
          // button.textContent = data.busy ? 'Busy' : 'Free';
          button.classList.toggle('is-busy', data.busy);
          button.querySelector('.cell-status').textContent = data.busy ? 'Busy' : '';
          button.setAttribute('aria-pressed', data.busy);
        } catch (error) {
          location.reload(); // something went wrong: reload to show the real state
        } finally {
          button.disabled = false;
        }
      });
    }
  </script>
</x-layout>