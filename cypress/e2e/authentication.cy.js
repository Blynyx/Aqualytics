describe('Autenticación', () => {
    beforeEach(() => {
        cy.task('seedE2E');
    });

    it('permite iniciar sesión como administrador', () => {
        cy.login('admin@cypress.test', 'password123');

        cy.url().should('include', '/dashboard');
        cy.get('[data-cy="brand"]').should('be.visible').and('contain', 'Aqualytics');
        cy.get('[data-cy="fish-farm-name"]')
            .should('be.visible')
            .and('contain', 'Piscigranja Cypress');
        cy.get('[data-cy="user-role"]')
            .should('be.visible')
            .and('contain', 'Administrador');
    });
});
