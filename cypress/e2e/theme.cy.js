// Week 8: dark mode toggle and saved preference (NF7).

describe('Dark mode', () => {
  const theme = () => cy.get('html').invoke('attr', 'data-bs-theme');

  it('starts in light mode', () => {
    cy.registerAndLogin();

    theme().should('eq', 'light');
    cy.get('#theme-toggle-form button').should('contain', 'Dark mode');
  });

  it('switches to dark mode instantly, without reloading', () => {
    cy.registerAndLogin();
    cy.window().then((win) => { win.noReloadMarker = true; });

    cy.get('#theme-toggle-form button').click();

    theme().should('eq', 'dark');
    cy.get('#theme-toggle-form button').should('contain', 'Light mode');
    cy.window().its('noReloadMarker').should('eq', true);
  });

  it('remembers dark mode after a reload and after logging in again', () => {
    cy.registerAndLogin().then((user) => {
      cy.get('#theme-toggle-form button').click();
      theme().should('eq', 'dark');
      // The label only changes once the server has saved it, so wait for it.
      cy.get('#theme-toggle-form button').should('contain', 'Light mode');

      cy.reload();
      theme().should('eq', 'dark');

      cy.logout();
      cy.loginAs(user.email, user.password);
      theme().should('eq', 'dark');
      cy.get('#theme-toggle-form button').should('contain', 'Light mode');
    });
  });

  it('switches back to light mode and remembers that too', () => {
    cy.registerAndLogin();

    cy.get('#theme-toggle-form button').click();
    theme().should('eq', 'dark');
    cy.get('#theme-toggle-form button').should('contain', 'Light mode');

    cy.get('#theme-toggle-form button').click();
    theme().should('eq', 'light');
    cy.get('#theme-toggle-form button').should('contain', 'Dark mode');

    cy.reload();
    theme().should('eq', 'light');
  });

  it('shows guests the light theme', () => {
    cy.visit('/login');
    theme().should('eq', 'light');
  });
});
