describe('Autorización visual por rol', () => {
    beforeEach(() => {
        cy.task('seedE2E');
        cy.login('supervisor@cypress.test', 'password123');
    });

    it('supervisor puede consultar pero no administrar estanques', () => {
        cy.url().should('include', '/dashboard');
        cy.contains('h1', 'Dashboard').should('be.visible');

        cy.get('[data-cy="nav-ponds"]').click();
        cy.get('[data-cy="new-pond"]').should('not.exist');
        cy.contains('tr', 'Estanque Cypress')
            .find('[data-cy="pond-details"]')
            .click();

        cy.contains('h1', 'Estanque Cypress').should('be.visible');
        cy.get('[data-cy="register-device-form"]').should('not.exist');
        cy.get('[data-cy="threshold-form"]').should('not.exist');

        cy.visit('/ponds/create', { failOnStatusCode: false });
        cy.contains('403').should('be.visible');
        cy.get('[data-cy="pond-name"]').should('not.exist');
    });
});
