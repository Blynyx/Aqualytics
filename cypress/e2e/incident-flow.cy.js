describe('Flujo de incidencias', () => {
    beforeEach(() => {
        cy.task('seedE2E');
    });

    it('supervisor asigna una incidencia y especialista la resuelve', () => {
        const resolutionNotes = 'Se realizó recambio parcial del agua y se verificó nuevamente el pH.';

        cy.login('supervisor@cypress.test', 'password123');
        cy.get('[data-cy="nav-ponds"]').click();
        cy.contains('tr', 'Estanque Cypress')
            .find('[data-cy="pond-details"]')
            .click();

        cy.get('[data-cy="active-alert"]')
            .should('be.visible')
            .and('contain', 'pH por debajo del rango configurado');
        cy.get('[data-cy="alert-status"]').should('contain', 'active');
        cy.get('[data-cy="specialist-select"]').select('Especialista Cypress');
        cy.get('[data-cy="assign-incident"]').click();

        cy.get('[data-cy="alert-status"]').should('contain', 'assigned');
        cy.get('[data-cy="assigned-specialist"]')
            .should('be.visible')
            .and('contain', 'Especialista Cypress');

        cy.get('[data-cy="logout"]').click();
        cy.url().should('include', '/login');

        cy.login('specialist@cypress.test', 'password123');
        cy.get('[data-cy="nav-ponds"]').click();
        cy.contains('tr', 'Estanque Cypress')
            .find('[data-cy="pond-details"]')
            .click();

        cy.get('[data-cy="alert-status"]').should('contain', 'assigned');
        cy.get('[data-cy="assigned-specialist"]').should('contain', 'Especialista Cypress');
        cy.get('[data-cy="resolution-notes"]').type(resolutionNotes);
        cy.get('[data-cy="resolve-incident"]').click();

        cy.get('[data-cy="no-active-alerts"]').should('be.visible');
        cy.get('[data-cy="incident-history"]').should('be.visible');
        cy.get('[data-cy="resolved-incident"]')
            .should('contain', 'Especialista Cypress')
            .and('contain', resolutionNotes);
    });
});
