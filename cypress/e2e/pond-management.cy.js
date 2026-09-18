describe('Gestión de estanques', () => {
    beforeEach(() => {
        cy.task('seedE2E');
        cy.login('admin@cypress.test', 'password123');
    });

    it('administrador puede crear un estanque', () => {
        cy.get('[data-cy="nav-ponds"]').click();
        cy.url().should('include', '/ponds');

        cy.get('[data-cy="new-pond"]').should('be.visible').click();
        cy.get('[data-cy="pond-name"]').type('Estanque E2E');
        cy.get('[data-cy="pond-code"]').type('CYP-E2E');
        cy.get('[data-cy="pond-species"]').type('Tilapia');
        cy.get('[data-cy="pond-location"]').type('Zona E2E');
        cy.get('[data-cy="submit-pond"]').click();

        cy.url().should('match', /\/ponds\/\d+$/);
        cy.contains('h1', 'Estanque E2E').should('be.visible');
        cy.contains('CYP-E2E').should('be.visible');
        cy.contains('Tilapia').should('be.visible');
        cy.contains('Zona E2E').should('be.visible');

        cy.get('[data-cy="register-device-form"]').should('be.visible');
        cy.get('[data-cy="device-name"]').type('ESP32 E2E');
        cy.get('[data-cy="device-uid"]').type('CYP-E2E-ESP32');
        cy.get('[data-cy="submit-device"]').click();

        cy.get('[data-cy="device-card"]').should('contain', 'ESP32 E2E');
        cy.get('[data-cy="device-token-issued"]').should('be.visible');
        cy.get('[data-cy="issued-device-uid"]').should('contain', 'CYP-E2E-ESP32');
        cy.get('[data-cy="device-token-value"]').invoke('text').should('have.length', 64);
        cy.get('[data-cy="regenerate-device-token"]').should('be.visible');
    });
});
