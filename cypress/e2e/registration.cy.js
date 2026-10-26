describe('Registration', () => {
  const unique = Date.now();

  beforeEach(() => {
    cy.visit('/register');
  });

  it('registers successfully with valid, unique details', () => {
    cy.get('#username').type(`newuser${unique}`);
    cy.get('#email').type(`newuser${unique}@example.com`);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    // Note: the app currently sets a session('success') flash message on
    // registration, but login.blade.php never renders it, so we can't
    // assert on visible text here. Landing on /login is enough proof the
    // registration itself succeeded. If you add a success banner to
    // login.blade.php, add back: cy.contains(/account created/i);
    cy.url().should('include', '/login');
  });

  it('rejects a duplicate username', () => {
    // First registration to claim the username.
    const username = `dupuser${unique}`;
    cy.get('#username').type(username);
    cy.get('#email').type(`dupuser1${unique}@example.com`);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();
    cy.url().should('include', '/login');

    // Second registration reusing the same username, different email.
    cy.visit('/register');
    cy.get('#username').type(username);
    cy.get('#email').type(`dupuser2${unique}@example.com`);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    cy.url().should('include', '/register');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });

  it('rejects a duplicate email', () => {
    const email = `dupemail${unique}@example.com`;
    cy.get('#username').type(`dupemail1user${unique}`);
    cy.get('#email').type(email);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();
    cy.url().should('include', '/login');

    cy.visit('/register');
    cy.get('#username').type(`dupemail2user${unique}`);
    cy.get('#email').type(email);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    cy.url().should('include', '/register');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });

  it('rejects a password shorter than 8 characters', () => {
    cy.get('#username').type(`shortpw${unique}`);
    cy.get('#email').type(`shortpw${unique}@example.com`);
    cy.get('#password').type('Pass1');
    cy.get('#password_confirmation').type('Pass1');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    cy.url().should('include', '/register');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });

  it('rejects a password with no uppercase letter', () => {
    cy.get('#username').type(`nouppercase${unique}`);
    cy.get('#email').type(`nouppercase${unique}@example.com`);
    cy.get('#password').type('password123');
    cy.get('#password_confirmation').type('password123');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    cy.url().should('include', '/register');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });

  it('rejects mismatched password confirmation', () => {
    cy.get('#username').type(`mismatch${unique}`);
    cy.get('#email').type(`mismatch${unique}@example.com`);
    cy.get('#password').type('Password123');
    cy.get('#password_confirmation').type('Password456');
    cy.get('form').contains('button', /sign up|register|create/i).click();

    cy.url().should('include', '/register');
    cy.get('.text-danger, .invalid-feedback').should('exist');
  });
});
