describe('Route protection', () => {
  it('redirects unauthenticated visitors away from /settings', () => {
    cy.visit('/settings');
    cy.url().should('include', '/login');
  });

  it('allows an authenticated user to view /settings', () => {
    cy.registerAndLogin().then(() => {
      cy.visit('/settings');
      cy.url().should('include', '/settings');
    });
  });

  it('shows Sign In / Sign Up links when logged out', () => {
    cy.visit('/');
    cy.contains(/sign in|log in/i);
    cy.contains(/sign up|register/i);
  });

  it('shows Settings / Logout when logged in', () => {
    cy.registerAndLogin().then(() => {
      cy.visit('/');
      cy.contains(/settings/i);
      cy.contains(/log ?out/i);
    });
  });
});
