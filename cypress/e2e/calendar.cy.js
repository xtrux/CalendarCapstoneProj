// Weeks 6–7: calendar grid, marking availability (F4), colours (F4.4),
// month navigation (F4.2), user filter and date search (F4.1, F4.3).

describe('Calendar grid and navigation', () => {
  beforeEach(() => {
    cy.registerAndLogin();
    cy.createGroup(`Cal ${Date.now()}`).as('groupId');
  });

  const visitMonth = (year, month) => {
    cy.get('@groupId').then((id) => cy.visit(`/group/${id}/calendar?year=${year}&month=${month}`));
  };

  it('shows the month with Sun–Sat headers and the right days (Week 6)', () => {
    visitMonth(2026, 10);

    cy.contains('h1', 'October 2026');
    cy.get('.cal-dayname')
      .then(($d) => [...$d].map((el) => el.innerText.trim()))
      .should('deep.equal', ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']);

    // October 2026 starts on a Thursday: 4 empty days, then 31 clickable days.
    cy.get('.cal-empty').should('have.length', 4);
    cy.get('.calendar-cell').should('have.length', 31);
    cy.get('.calendar-cell .cal-daynum').first().should('have.text', '1');
    cy.get('.calendar-cell .cal-daynum').last().should('have.text', '31');
  });

  it('pads the end of a month that finishes early in the week', () => {
    // November 2026 ends on a Monday: 5 empty days after it.
    visitMonth(2026, 11);

    cy.contains('h1', 'November 2026');
    cy.get('.calendar-cell').should('have.length', 30);
    cy.get('.cal-empty').should('have.length', 5);
  });

  it('pages forward across a year boundary (F4.2)', () => {
    visitMonth(2026, 12);
    cy.get('a[aria-label="Next month"]').click();

    cy.contains('h1', 'January 2027');
    cy.url().should('include', 'year=2027').and('include', 'month=1');
  });

  it('pages back across a year boundary (F4.2)', () => {
    visitMonth(2026, 1);
    cy.get('a[aria-label="Previous month"]').click();

    cy.contains('h1', 'December 2025');
    cy.url().should('include', 'year=2025').and('include', 'month=12');
  });

  it('shows the colour legend', () => {
    visitMonth(2026, 11);

    cy.get('.legend-swatch.status-allFree');
    cy.get('.legend-swatch.status-half');
    cy.get('.legend-swatch.status-allBusy');
  });

  it('blocks users who are not in the group', () => {
    cy.get('@groupId').then((id) => {
      cy.logout();
      cy.registerAndLogin();
      cy.request({ url: `/group/${id}/calendar`, failOnStatusCode: false })
        .its('status')
        .should('eq', 403);
    });
  });
});

describe('Marking availability (F4)', () => {
  beforeEach(() => {
    cy.registerAndLogin();
    cy.createGroup(`Cal ${Date.now()}`).then((id) => {
      cy.visit(`/group/${id}/calendar?year=2026&month=11`);
    });
  });

  it('marks a day busy without reloading the page', () => {
    // If the page reloads, this marker disappears.
    cy.window().then((win) => { win.noReloadMarker = true; });

    cy.dayCell('2026-11-10').click();

    cy.dayCell('2026-11-10')
      .should('have.attr', 'aria-pressed', 'true')
      .and('have.class', 'status-allBusy')
      .and('contain', "You're busy")
      .and('contain', '1/1 busy');
    cy.window().its('noReloadMarker').should('eq', true);
  });

  it('keeps the busy mark after a reload (persistence)', () => {
    cy.dayCell('2026-11-10').click();
    cy.dayCell('2026-11-10').should('contain', "You're busy");

    cy.reload();

    cy.dayCell('2026-11-10')
      .should('contain', "You're busy")
      .and('have.class', 'status-allBusy');
  });

  it('clicking a busy day marks it free again', () => {
    cy.dayCell('2026-11-10').click();
    cy.dayCell('2026-11-10').should('contain', "You're busy");

    cy.dayCell('2026-11-10').click();

    cy.dayCell('2026-11-10')
      .should('have.attr', 'aria-pressed', 'false')
      .and('have.class', 'status-allFree')
      .and('not.contain', "You're busy")
      .and('contain', '0/1 busy');
  });
});

describe('Colours, filter and date search with two members (F4.4, F4.1, F4.3)', () => {
  // Member marks Nov 10 busy; admin is free. Ends logged in as the admin
  // on November 2026.
  beforeEach(() => {
    cy.setupGroupWithMember().then((ctx) => {
      cy.wrap(ctx).as('ctx');

      cy.logout();
      cy.loginAs(ctx.member.email, ctx.member.password);
      cy.visit(`/group/${ctx.groupId}/calendar?year=2026&month=11`);
      cy.dayCell('2026-11-10').click();
      cy.dayCell('2026-11-10').should('contain', "You're busy");
      cy.logout();

      cy.loginAs(ctx.admin.email, ctx.admin.password);
      cy.visit(`/group/${ctx.groupId}/calendar?year=2026&month=11`);
    });
  });

  it('colours days by how many members are busy (test cases 17–19)', () => {
    cy.dayCell('2026-11-09').should('have.class', 'status-allFree').and('contain', '0/2 busy');
    cy.dayCell('2026-11-10').should('have.class', 'status-half').and('contain', '1/2 busy');

    // Admin marks the same day: now everyone is busy.
    cy.dayCell('2026-11-10').click();
    cy.dayCell('2026-11-10').should('have.class', 'status-allBusy').and('contain', '2/2 busy');
  });

  it('filters the calendar to selected users (F4.1)', () => {
    cy.get('@ctx').then(({ member }) => {
      cy.contains('button', 'Filter users').click();
      cy.contains('label', member.username).click(); // untick the member
      cy.contains('.dropdown-menu button', 'Apply').click();

      cy.url().should('include', 'users');
      cy.contains('button', 'Filter users (1)');
      cy.contains('All selected members free');
      cy.dayCell('2026-11-10').should('have.class', 'status-allFree').and('contain', '0/1 busy');

      // Filter survives changing month.
      cy.get('a[aria-label="Next month"]').click();
      cy.contains('h1', 'December 2026');
      cy.url().should('include', 'users');

      // "Show everyone" clears it.
      cy.contains('button', 'Filter users').click();
      cy.contains('a', 'Show everyone').click();
      cy.url().should('not.include', 'users');
      cy.contains('button', /^\s*Filter users\s*$/);
    });
  });

  it('searches a date: jumps to its month and lists who is free (F4.3)', () => {
    cy.get('@ctx').then(({ admin, member }) => {
      cy.get('#date-search').type('2027-03-15');
      cy.get('form[role="search"]').contains('button', 'Go').click();

      cy.contains('h1', 'March 2027');
      cy.dayCell('2027-03-15').should('have.class', 'is-selected');
      cy.contains('Monday, March 15');
      cy.contains('li', admin.username).should('contain', 'Free');
      cy.contains('li', member.username).should('contain', 'Free');
    });
  });

  it('day panel shows who is busy on a searched date', () => {
    cy.get('@ctx').then(({ admin, member }) => {
      cy.get('#date-search').type('2026-11-10');
      cy.get('form[role="search"]').contains('button', 'Go').click();

      cy.contains('Tuesday, November 10');
      cy.contains('li', member.username).should('contain', 'Busy');
      cy.contains('li', admin.username).should('contain', 'Free');
    });
  });

  it('rejects an impossible date', () => {
    cy.get('@ctx').then(({ groupId }) => {
      cy.visit(`/group/${groupId}/calendar?date=2026-02-31`);
      cy.get('.text-danger').should('be.visible');
    });
  });
});
