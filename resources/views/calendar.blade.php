<x-layout :group="$group">
  <style>
    .cal-card {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    /* 1px gaps over a grey background draw the divider lines between cells */
    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 1px;
      background: #e5e7eb;
    }

    .cal-dayname {
      background: #fff;
      text-align: center;
      padding: 0.75rem 0;
      font-weight: 500;
      color: #374151;
    }

    .cal-empty {
      background: #f8fafc;
      min-height: 110px;
    }

    .calendar-cell {
      display: block;
      width: 100%;
      height: 100%;
      min-height: 110px;
      padding: 0.75rem;
      border: 0;
      border-radius: 0;
      text-align: left;
      background: #fff;
      color: #111827;
    }

    .calendar-cell:hover { filter: brightness(0.96); }

    .calendar-cell:focus-visible {
      outline: 3px solid #0d6efd;
      outline-offset: -3px;
    }

    .cal-daynum {
      font-size: 1.4rem;
      font-weight: 700;
      line-height: 1;
    }

    .cell-count {
      display: block;
      margin-top: 0.4rem;
      font-size: 0.75rem;
      color: #374151;
    }

    .cell-status {
      display: inline-block;
      margin-top: 0.25rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #842029;
    }

    /* Group availability colours */
    .status-allFree { background: #bbf7d0; }
    .status-half    { background: #fef08a; }
    .status-allBusy { background: #fecaca; }

    .legend-swatch {
      display: inline-block;
      width: 28px;
      height: 28px;
      border-radius: 4px;
      border: 1px solid #e5e7eb;
      vertical-align: middle;
    }

    .icon-btn {
      width: 38px;
      height: 38px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }
  </style>

  <div class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <h1 class="h2 fw-bold mb-0">{{ $first->format('F Y') }}</h1>

      <div class="d-flex align-items-center gap-2">
        {{-- Week 7: search and Filter/Sort Users go here. Week 10: Create Event. --}}
        <a class="btn btn-outline-secondary icon-btn" aria-label="Previous month"
          href="/group/{{ $group->id }}/calendar?year={{ $prev->year }}&month={{ $prev->month }}">&lsaquo;</a>
        <a class="btn btn-outline-secondary icon-btn" aria-label="Next month"
          href="/group/{{ $group->id }}/calendar?year={{ $next->year }}&month={{ $next->month }}">&rsaquo;</a>
        <a class="btn btn-outline-secondary icon-btn" aria-label="Group settings"
          href="/group/{{ $group->id }}">&#9776;</a>
      </div>
    </div>

    <div class="cal-card">
      <div class="cal-grid">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
          <div class="cal-dayname">{{ $dayName }}</div>
        @endforeach

        @foreach ($cells as $cell)
          @if ($cell['inMonth'])
            <form action="/group/{{ $group->id }}/availability" method="POST" class="availability-toggle m-0">
              @csrf
              <input type="hidden" name="date" value="{{ $cell['date']->format('Y-m-d') }}">
              <button type="submit" class="calendar-cell status-{{ $cell['status'] }}"
                aria-pressed="{{ $cell['meBusy'] ? 'true' : 'false' }}"
                aria-label="{{ $cell['date']->format('F j') }}">
                <span class="cal-daynum">{{ $cell['date']->day }}</span>
                <span class="cell-count">{{ $cell['busyCount'] }}/{{ $members->count() }} busy</span>
                <span class="cell-status">{{ $cell['meBusy'] ? "You're busy" : '' }}</span>
              </button>
            </form>
          @else
            <div class="cal-empty" aria-hidden="true"></div>
          @endif
        @endforeach
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-center gap-4 mt-4 small">
      <span><span class="legend-swatch status-allFree me-2"></span>All members free</span>
      <span><span class="legend-swatch status-half me-2"></span>Some members busy</span>
      <span><span class="legend-swatch status-allBusy me-2"></span>All members busy</span>
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
          button.classList.remove('status-allFree', 'status-half', 'status-allBusy');
          button.classList.add('status-' + data.status);
          button.querySelector('.cell-count').textContent = `${data.busyCount}/${data.memberCount} busy`;
          button.querySelector('.cell-status').textContent = data.busy ? "You're busy" : '';
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