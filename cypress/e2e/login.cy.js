describe('Login', () => {
  it('logs in successfully with correct credentials', () => {
    cy.registerNewUser().then((user) => {
      cy.loginAs(user.email, user.password);
      cy.url().should('eq', Cypress.config().baseUrl + '/');
      cy.contains(user.username);
    });
  });

  it('rejects an incorrect password', () => {
    cy.registerNewUser().then((user) => {
      cy.loginAs(user.email, 'WrongPassword1');
      cy.url().should('include', '/login');
      cy.get('.text-danger, .invalid-feedback').should('exist');
    });
  });

  it('rejects a non-existent email', () => {
    cy.loginAs(`doesnotexist${Date.now()}@example.com`, 'Password123');
    cy.url().should('include', '/login');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });

  it('locks the account after 5 failed login attempts', () => {
    cy.registerNewUser().then((user) => {
      // 5 failed attempts in a row.
      for (let i = 0; i < 5; i++) {
        cy.loginAs(user.email, 'WrongPassword1');
        cy.url().should('include', '/login');
      }

      // A 6th attempt, even with the CORRECT password, should now be locked.
      cy.loginAs(user.email, user.password);
      cy.url().should('include', '/login');
      cy.contains(/locked/i);
    });
  });
});
