import { test } from '@fixtures/AcceptanceTest';

test('As a customer, I must be able to change email via account.', { tag: '@Account' }, async ({
    ShopCustomer,
    StorefrontAccountLogin,
    StorefrontAccount,
    IdProvider,
    Register,
    StorefrontAccountProfile,

}) => {

    const customer = { email: IdProvider.getIdPair().uuid + '@test.com' , password: IdProvider.getIdPair().uuid };
    const invalidEmail = 'invalidEmailWithoutAtSymbol';
    const changedEmail = { email: IdProvider.getIdPair().uuid + '@test.com' };

    await test.step('Register a valid account', async () => {
        await ShopCustomer.goesTo(StorefrontAccountLogin.url());
        await ShopCustomer.attemptsTo(Register(customer));
        await ShopCustomer.expects(StorefrontAccount.page.getByText(customer.email, { exact: true })).toBeVisible();
    });

    await test.step('Attempt to change email to an invalid address', async () => {
        await ShopCustomer.goesTo(StorefrontAccountProfile.url());
        await StorefrontAccountProfile.changeEmailButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.emailAddressInput).toBeVisible();
        await StorefrontAccountProfile.emailAddressInput.fill(invalidEmail);
        await StorefrontAccountProfile.emailAddressConfirmInput.fill(invalidEmail);
        await StorefrontAccountProfile.emailConfirmPasswordInput.fill(customer.password);
        await StorefrontAccountProfile.saveEmailAddressButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.emailValidationAlert).toBeVisible();
    });

    await test.step('Attempt to change email to same address', async () => {
        await ShopCustomer.goesTo(StorefrontAccountProfile.url());
        await StorefrontAccountProfile.changeEmailButton.click();
        await StorefrontAccountProfile.emailAddressInput.fill(customer.email);
        await StorefrontAccountProfile.emailAddressConfirmInput.fill(customer.email);
        await StorefrontAccountProfile.emailConfirmPasswordInput.fill(customer.password);
        await StorefrontAccountProfile.saveEmailAddressButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.emailUpdateFailureAlert).toBeVisible();
    });

    await test.step('Attempt to change email to a new valid address', async () => {
        await ShopCustomer.goesTo(StorefrontAccountProfile.url());
        await StorefrontAccountProfile.changeEmailButton.click();
        await StorefrontAccountProfile.emailAddressInput.fill(changedEmail.email);
        await StorefrontAccountProfile.emailAddressConfirmInput.fill(changedEmail.email);
        await StorefrontAccountProfile.emailConfirmPasswordInput.fill(customer.password);
        await StorefrontAccountProfile.saveEmailAddressButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.emailUpdateMessage).toBeVisible();
        await ShopCustomer.expects(StorefrontAccountProfile.loginDataEmailAddress).toContainText(changedEmail.email);
    });

    await test.step('Try to login with the old email address', async () => {
        await StorefrontAccountLogin.logoutLink.click();
        await ShopCustomer.expects(StorefrontAccountLogin.successAlert).toBeVisible();
        await StorefrontAccountLogin.emailInput.fill(customer.email);
        await StorefrontAccountLogin.passwordInput.fill(customer.password);
        await StorefrontAccountLogin.loginButton.click();
        await ShopCustomer.expects(StorefrontAccountLogin.invalidCredentialsAlert).toBeVisible();
    });
    
    await test.step('Verify login with the new email address', async () => {
        await StorefrontAccountLogin.emailInput.fill(changedEmail.email);
        await StorefrontAccountLogin.passwordInput.fill(customer.password);
        await StorefrontAccountLogin.loginButton.click();
        await ShopCustomer.expects(StorefrontAccount.personalDataCardTitle).toBeVisible();
    });
});

test('As a customer, I must be able to change password via account.', { tag: '@Account' }, async ({
    ShopCustomer,
    StorefrontAccountLogin,
    StorefrontAccount,
    IdProvider,
    Register,
    StorefrontAccountProfile,

}) => {

    const customer = { email: IdProvider.getIdPair().uuid + '@test.com' , password: IdProvider.getIdPair().uuid };
    const invalidPassword = { password: 'short' }; // Invalid: less than 8 characters
    const changedPassword = { password: IdProvider.getIdPair().uuid };

    await test.step('Register a new account', async () => {
        await ShopCustomer.goesTo(StorefrontAccountLogin.url());
        await ShopCustomer.attemptsTo(Register(customer));
        await ShopCustomer.expects(StorefrontAccount.page.getByText(customer.email, { exact: true })).toBeVisible();
    });

    await test.step('Attempt to change password to an invalid (short) password', async () => {
        await ShopCustomer.goesTo(StorefrontAccountProfile.url());
        await StorefrontAccountProfile.changePasswordButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.newPasswordInput).toBeVisible();
        await StorefrontAccountProfile.newPasswordInput.fill(invalidPassword.password);
        await StorefrontAccountProfile.newPasswordConfirmInput.fill(invalidPassword.password);
        await StorefrontAccountProfile.currentPasswordInput.fill(customer.password);
        await StorefrontAccountProfile.saveNewPasswordButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.passwordUpdateFailureAlert).toBeVisible();
    });

    await test.step('Successfully change password to a valid password', async () => {
        await ShopCustomer.goesTo(StorefrontAccountProfile.url());
        await StorefrontAccountProfile.changePasswordButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.newPasswordInput).toBeVisible();
        await StorefrontAccountProfile.newPasswordInput.fill(changedPassword.password);
        await StorefrontAccountProfile.newPasswordConfirmInput.fill(changedPassword.password);
        await StorefrontAccountProfile.currentPasswordInput.fill(customer.password);
        await StorefrontAccountProfile.saveNewPasswordButton.click();
        await ShopCustomer.expects(StorefrontAccountProfile.passwordUpdateMessage).toBeVisible();
    });

    await test.step('Try to login with old password', async () => {
        await StorefrontAccountLogin.logoutLink.click();
        await ShopCustomer.expects(StorefrontAccountLogin.successAlert).toBeVisible();
        await StorefrontAccountLogin.emailInput.fill(customer.email);
        await StorefrontAccountLogin.passwordInput.fill(customer.password);
        await StorefrontAccountLogin.loginButton.click();
        await ShopCustomer.expects(StorefrontAccountLogin.invalidCredentialsAlert).toBeVisible();
    });

    await test.step('Login with changed password to verify', async () => {
        await ShopCustomer.goesTo(StorefrontAccountLogin.url());
        await StorefrontAccountLogin.emailInput.fill(customer.email);
        await StorefrontAccountLogin.passwordInput.fill(changedPassword.password);
        await StorefrontAccountLogin.loginButton.click();
        await ShopCustomer.expects(StorefrontAccount.personalDataCardTitle).toBeVisible();
    });
});
