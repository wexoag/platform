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
    const changedEmail = { email: IdProvider.getIdPair().uuid + '@test.com' };

    await ShopCustomer.goesTo(StorefrontAccountLogin.url());
    await ShopCustomer.attemptsTo(Register(customer));
    await ShopCustomer.expects(StorefrontAccount.page.getByText(customer.email, { exact: true })).toBeVisible();
    await ShopCustomer.goesTo(StorefrontAccountProfile.url());
    await StorefrontAccountProfile.changeEmailButton.click();
    await ShopCustomer.expects(StorefrontAccountProfile.emailAddressInput).toBeVisible();
    await StorefrontAccountProfile.emailAddressInput.fill(changedEmail.email);
    await StorefrontAccountProfile.emailAddressConfirmInput.fill(changedEmail.email);
    await StorefrontAccountProfile.emailConfirmPasswordInput.fill(customer.password);
    await StorefrontAccountProfile.saveEmailAddressButton.click();
    await ShopCustomer.expects(StorefrontAccountProfile.emailUpdateMessage).toBeVisible();
    await ShopCustomer.expects(StorefrontAccountProfile.loginDataEmailAddress).toContainText(changedEmail.email);

    await StorefrontAccountLogin.logoutLink.click();
    await ShopCustomer.expects(StorefrontAccountLogin.successAlert).toBeVisible();
    await StorefrontAccountLogin.emailInput.fill(changedEmail.email);
    await StorefrontAccountLogin.passwordInput.fill(customer.password);
    await StorefrontAccountLogin.loginButton.click();
    await ShopCustomer.expects(StorefrontAccount.personalDataCardTitle).toBeVisible();
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
    const changedPassword = { password: IdProvider.getIdPair().uuid };

    await ShopCustomer.goesTo(StorefrontAccountLogin.url());
    await ShopCustomer.attemptsTo(Register(customer));
    await ShopCustomer.expects(StorefrontAccount.page.getByText(customer.email, { exact: true })).toBeVisible();
    await ShopCustomer.goesTo(StorefrontAccountProfile.url());
    await StorefrontAccountProfile.changePasswordButton.click();
    await ShopCustomer.expects(StorefrontAccountProfile.newPasswordInput).toBeVisible();
    await StorefrontAccountProfile.newPasswordInput.fill(changedPassword.password);
    await StorefrontAccountProfile.newPasswordConfirmInput.fill(changedPassword.password);
    await StorefrontAccountProfile.currentPasswordInput.fill(customer.password);
    await StorefrontAccountProfile.saveNewPasswordButton.click();
    await ShopCustomer.expects(StorefrontAccountProfile.passwordUpdateMessage).toBeVisible();

    await StorefrontAccountLogin.logoutLink.click();
    await ShopCustomer.expects(StorefrontAccountLogin.successAlert).toBeVisible();
    await StorefrontAccountLogin.emailInput.fill(customer.email);
    await StorefrontAccountLogin.passwordInput.fill(changedPassword.password);
    await StorefrontAccountLogin.loginButton.click();
    await ShopCustomer.expects(StorefrontAccount.personalDataCardTitle).toBeVisible();
});
