// Custom Cypress commands shared across spec files.
//
// NOTE: These assume the following field `id`/`name` attributes on your Blade
// forms. Adjust the selectors below if your actual markup differs:
//
//   register.blade.php : #username, #email, #password, #password_confirmation
//   login.blade.php    : #email, #password
//   settings.blade.php : #username (changeUsername form)
//                         #email (changeEmail form)
//                         #oldPass, #newPass, #newPass_confirmation (changePassword form)
//                         #password (delete account form)

/**
 * Registers a brand-new user through the UI (not via API), since there's no
 * API endpoint for this app — everything goes through the web routes.
 * Returns the generated credentials so the caller can use them.
 */
Cypress.Commands.add('registerNewUser', () => {
  const unique = Date.now();
  const user = {
    username: `testuser${unique}`,
    email: `testuser${unique}@example.com`,
    password: 'Password123',
  };

  cy.visit('/register');
  cy.get('#username').type(user.username);
  cy.get('#email').type(user.email);
  cy.get('#password').type(user.password);
  cy.get('#password_confirmation').type(user.password);
  cy.get('form').contains('button', /sign up|register|create/i).click();

  // Should land back on /login with a success message.
  cy.url().should('include', '/login');

  return cy.wrap(user);
});

/**
 * Logs in through the UI with the given credentials.
 */
Cypress.Commands.add('loginAs', (email, password) => {
  cy.visit('/login');
  cy.get('#email').type(email);
  cy.get('#password').type(password);
  cy.get('form').contains('button', /sign in|log in|login/i).click();
});

/**
 * Convenience: registers a fresh user AND logs them in, for specs that need
 * an authenticated session (e.g. settings tests) but don't care about the
 * registration/login flow itself.
 */
Cypress.Commands.add('registerAndLogin', () => {
  return cy.registerNewUser().then((user) => {
    cy.loginAs(user.email, user.password);
    cy.url().should('eq', Cypress.config().baseUrl + '/');
    return cy.wrap(user);
  });
});
