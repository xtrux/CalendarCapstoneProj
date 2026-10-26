describe('Settings', () => {
  it('changes the username successfully', () => {
    cy.registerAndLogin().then(() => {
      const newUsername = `changedname${Date.now()}`;
      cy.visit('/settings');
      cy.get('#username').clear().type(newUsername);
      cy.get('form')
        .filter((_, form) => form.action.endsWith('/changeUsername'))
        .submit();

      cy.contains(newUsername);
    });
  });

  it('changes the email successfully', () => {
    cy.registerAndLogin().then(() => {
      const newEmail = `changedemail${Date.now()}@example.com`;
      cy.visit('/settings');
      cy.get('#email').clear().type(newEmail);
      cy.get('form')
        .filter((_, form) => form.action.endsWith('/changeEmail'))
        .submit();

      // Confirm it stuck by logging out and back in with the new email.
      cy.visit('/logout');
      cy.get('#email').type(newEmail);
    });
  });

  it('changes the password successfully and can log in with the new one', () => {
    cy.registerAndLogin().then((user) => {
      const newPassword = 'NewPassword123';
      cy.visit('/settings');
      cy.get('#oldPass').type(user.password);
      cy.get('#newPass').type(newPassword);
      cy.get('#newPass_confirmation').type(newPassword);
      cy.get('form')
        .filter((_, form) => form.action.endsWith('/changePassword'))
        .submit();

      // Log out and confirm the new password actually works.
      cy.contains('a, button', /log ?out/i).click();
      cy.loginAs(user.email, newPassword);
      cy.url().should('eq', Cypress.config().baseUrl + '/');
    });
  });

  it('rejects changing the password with an incorrect current password', () => {
    cy.registerAndLogin().then(() => {
      cy.visit('/settings');
      cy.get('#oldPass').type('TotallyWrongPassword1');
      cy.get('#newPass').type('NewPassword123');
      cy.get('#newPass_confirmation').type('NewPassword123');
      cy.get('form')
        .filter((_, form) => form.action.endsWith('/changePassword'))
        .submit();

      cy.get('.text-danger, .invalid-feedback').should('exist');
    });
  });

  it('deletes the account and the user can no longer log in', () => {
    cy.registerAndLogin().then((user) => {
      cy.visit('/settings');
      cy.get('#password').type(user.password);

      cy.on('window:confirm', () => true); // accept the confirm() dialog
      cy.get('form')
        .filter((_, form) => form.action.endsWith('/deleteUser'))
        .submit();

      // Should land back on a public page (home/login), not settings.
      cy.url().should('not.include', '/settings');

      // Trying to log back in with the deleted account should fail.
      cy.loginAs(user.email, user.password);
      cy.url().should('include', '/login');
      cy.get('.text-danger, .invalid-feedback').should('exist');
    });
  });
});
