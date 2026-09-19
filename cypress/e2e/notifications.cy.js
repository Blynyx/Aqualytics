describe('Notificaciones internas', () => {
    beforeEach(() => {
        cy.task('seedE2E');
    });

    it('admin ve la campana, abre la bandeja y marca como leída', () => {
        cy.login('admin@cypress.test', 'password123');

        cy.get('[data-cy="nav-notifications"]').should('be.visible');
        cy.get('[data-cy="notification-unread-count"]')
            .should('be.visible')
            .and('contain', '1');

        cy.get('[data-cy="nav-notifications"]').click();
        cy.url().should('include', '/notifications');

        cy.get('[data-cy="notification-list"]').should('be.visible');
        cy.get('[data-cy="notification-item"]').should('have.length.at.least', 1);
        cy.get('[data-cy="notification-unread"]')
            .should('be.visible')
            .and('contain', 'pH por debajo del rango configurado');
        cy.get('[data-cy="notification-source-link"]').should('be.visible');

        cy.get('[data-cy="notification-mark-read"]').click();

        cy.get('[data-cy="notification-item"]').should('be.visible');
        cy.get('[data-cy="notification-read"]').should('be.visible');
        cy.get('[data-cy="notification-unread"]').should('not.exist');
        cy.get('[data-cy="notification-mark-read"]').should('not.exist');
        cy.get('[data-cy="notification-unread-count"]').should('not.exist');
    });
});
