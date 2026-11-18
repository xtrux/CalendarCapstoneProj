// Week 5: groups (F2 – F2.5).
// Cypress accepts window.confirm() automatically, so the "Are you sure?"
// prompts on the group settings page don't block these tests.

describe('Groups', () => {
  it('creates a group and makes the creator its admin (F2.2)', () => {
    const name = `Group ${Date.now()}`;

    cy.registerAndLogin().then((admin) => {
      cy.createGroup(name);

      cy.contains('h2', name);
      cy.contains('Invite code:');
      cy.contains('li', admin.username).should('contain', 'Admin');
      cy.get('#groupSidebar').should('contain', name);
    });
  });

  it('opens the calendar from the sidebar and returns from settings', () => {
    const name = `Group ${Date.now()}`;

    cy.registerAndLogin();
    cy.createGroup(name).then((groupId) => {
      cy.contains('Back to calendar').click();
      cy.location('pathname').should('eq', `/group/${groupId}/calendar`);

      cy.visit('/');
      cy.get('#groupSidebar').contains('a', name).click();
      cy.location('pathname').should('eq', `/group/${groupId}/calendar`);
      cy.get('#groupSidebar').contains('a', name).should('have.class', 'active');
    });
  });

  it('lets a second user join with the invite code (F2)', () => {
    cy.setupGroupWithMember().then(({ member, groupId }) => {
      cy.visit(`/group/${groupId}`);
      cy.contains('li', member.username);
    });
  });

  it('shows an error for an invite code that does not exist (F2)', () => {
    cy.registerAndLogin();
    cy.joinGroup('ZZZZZZZZ');

    cy.get('#joinGroupModal').should('be.visible');
    cy.get('#joinGroupModal .text-danger').should('be.visible');
    cy.location('pathname').should('not.match', /^\/group\/\d+/);
  });

  it('shows admin buttons only to the admin', () => {
    cy.setupGroupWithMember().then(({ member, groupId }) => {
      cy.visit(`/group/${groupId}`);
      cy.contains('button', 'New invite code');
      cy.contains('button', 'Delete group');
      cy.contains('li', member.username).contains('button', 'Remove');

      cy.logout();
      cy.loginAs(member.email, member.password);
      cy.visit(`/group/${groupId}`);
      cy.contains('button', 'Leave group');
      cy.contains('button', 'Delete group').should('not.exist');
      cy.contains('button', 'New invite code').should('not.exist');
      cy.contains('button', 'Remove').should('not.exist');
    });
  });

  it('lets a member leave the group (F2.1)', () => {
    cy.setupGroupWithMember().then(({ member, groupId, groupName }) => {
      cy.logout();
      cy.loginAs(member.email, member.password);
      cy.visit(`/group/${groupId}`);

      cy.contains('button', 'Leave group').click();

      cy.url().should('eq', Cypress.config().baseUrl + '/');
      cy.get('#groupSidebar').should('not.contain', groupName);
    });
  });

  it('lets the admin remove a member (F2.4)', () => {
    cy.setupGroupWithMember().then(({ member, groupId }) => {
      cy.visit(`/group/${groupId}`);

      cy.contains('li', member.username).within(() => {
        cy.contains('button', 'Remove').click();
      });

      cy.location('pathname').should('eq', `/group/${groupId}`);
      cy.contains('li', member.username).should('not.exist');
    });
  });

  it('lets the admin regenerate the invite code (F2.5)', () => {
    cy.registerAndLogin();
    cy.createGroup(`Group ${Date.now()}`).then((groupId) => {
      cy.readInviteCode(groupId).then((oldCode) => {
        cy.contains('button', 'New invite code').click();
        cy.readInviteCode(groupId).should('not.eq', oldCode);
      });
    });
  });

  it('lets the admin delete the group (F2.3)', () => {
    const name = `Group ${Date.now()}`;

    cy.registerAndLogin();
    cy.createGroup(name).then((groupId) => {
      cy.contains('button', 'Delete group').click();

      cy.url().should('eq', Cypress.config().baseUrl + '/');
      cy.get('#groupSidebar').should('not.contain', name);
      cy.request({ url: `/group/${groupId}`, failOnStatusCode: false })
        .its('status')
        .should('eq', 404);
    });
  });
});
