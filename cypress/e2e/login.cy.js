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
      // 5 failed attempts in a row. The 5th is the one that actually sets
      // locked_until server-side (failed_login_attempts reaches 5 inside
      // that request), so the lock takes effect starting with attempt #6.
      for (let i = 0; i < 5; i++) {
        cy.loginAs(user.email, 'WrongPassword1');
        cy.url().should('include', '/login');
      }

      // A 6th attempt, even with the CORRECT password, should now be
      // rejected because the account is locked.
      cy.loginAs(user.email, user.password);
      cy.url().should('include', '/login');

      // NOTE: AuthController sets withErrors(['locked_until' => '...']),
      // but login.blade.php currently has no @error('locked_until') block,
      // so nothing actually renders on the page to confirm *why* it failed.
      // Add this to login.blade.php to make the message visible, then you
      // can assert on it directly:
      //   @error('locked_until')
      //     <div class="text-danger small mt-1">{{ $message }}</div>
      //   @enderror
      // Once that's added, uncomment the line below:
      // cy.contains(/locked/i);
    });
  });
});
