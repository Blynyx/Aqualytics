describe('Historial gráfico de telemetría', () => {
    beforeEach(() => {
        cy.task('seedE2E');
        cy.login('admin@cypress.test', 'password123');
    });

    it('muestra el historial del estanque y permite cambiar el rango', () => {
        cy.get('[data-cy="nav-ponds"]').click();
        cy.contains('tr', 'Estanque Cypress')
            .find('[data-cy="pond-details"]')
            .click();

        cy.get('[data-cy="parameter-history"]').should('be.visible');
        cy.contains('Historial del estanque').should('be.visible');

        cy.get('[data-cy="history-range-24h"]').should('be.visible');
        cy.get('[data-cy="history-range-7d"]').should('be.visible');
        cy.get('[data-cy="history-range-30d"]').should('be.visible');
        cy.get('[data-cy="history-range-90d"]').should('be.visible');

        cy.get('[data-cy="history-chart-temperature"]').should('be.visible');
        cy.get('[data-cy="history-chart-ph"]').should('be.visible');
        cy.get('[data-cy="history-chart-turbidity"]').should('be.visible');
        cy.get('[data-cy="history-chart-water-level"]').should('be.visible');
        cy.get('[data-cy="history-error"]').should('not.be.visible');

        cy.get('[data-cy="history-range-7d"]').click();
        cy.get('[data-cy="history-range-7d"]').should('have.attr', 'aria-pressed', 'true');
        cy.get('[data-cy="history-chart-temperature"]').should('be.visible');
        cy.get('[data-cy="history-error"]').should('not.be.visible');
        cy.get('[data-cy="active-alert"]').should('be.visible');
    });
});
