describe('Dashboard operativo por rol', () => {
    beforeEach(() => {
        cy.task('seedE2E');
    });

    it('HOME muestra contexto doméstico y no bloques Farm', () => {
        cy.login('home@cypress.test', 'password123');

        cy.get('[data-cy="dashboard-context"]')
            .should('be.visible')
            .and('have.attr', 'data-dashboard-context', 'home');
        cy.contains('Estado de tu pecera').should('be.visible');
        cy.contains('Pecera Cypress').should('be.visible');
        cy.get('[data-cy="dashboard-active-alerts"]').should('be.visible');
        cy.get('[data-cy="dashboard-my-incidents"]').should('not.exist');
        cy.get('[data-cy="dashboard-pending-incidents"]').should('not.exist');
        cy.get('[data-cy="dashboard-user-management"]').should('not.exist');
    });

    it('admin Farm ve indicadores globales de su cuenta', () => {
        cy.login('admin@cypress.test', 'password123');

        cy.get('[data-cy="dashboard-context"]')
            .should('be.visible')
            .and('have.attr', 'data-dashboard-context', 'farm_admin');
        cy.get('[data-cy="dashboard-active-alerts"]').should('be.visible');
        cy.get('[data-cy="dashboard-pending-incidents"]').should('be.visible');
        cy.contains('Estanque Cypress').should('be.visible');
    });

    it('supervisor ve alertas e incidencias operativas', () => {
        cy.login('supervisor@cypress.test', 'password123');

        cy.get('[data-cy="dashboard-context"]')
            .should('be.visible')
            .and('have.attr', 'data-dashboard-context', 'farm_supervisor');
        cy.get('[data-cy="dashboard-active-alerts"]').should('be.visible');
        cy.contains('Incidencias operativas').should('be.visible');
        cy.contains('pH por debajo del rango configurado').should('be.visible');
        cy.get('[data-cy="dashboard-user-management"]').should('not.exist');
    });

    it('especialista A ve solo sus incidencias', () => {
        cy.login('specialist@cypress.test', 'password123');

        cy.get('[data-cy="dashboard-context"]')
            .should('be.visible')
            .and('have.attr', 'data-dashboard-context', 'farm_specialist');
        cy.get('[data-cy="dashboard-my-incidents"]')
            .should('be.visible')
            .and('contain', 'Incidencia del especialista A')
            .and('not.contain', 'Incidencia del especialista B');
    });

    it('el dashboard es usable en viewport móvil', () => {
        cy.viewport(375, 667);
        cy.login('admin@cypress.test', 'password123');

        cy.get('[data-cy="dashboard-context"]').should('be.visible');
        cy.get('main').should('be.visible');
        cy.get('[data-cy="dashboard-active-alerts"]').should('be.visible');
    });
});
