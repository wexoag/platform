import { test } from '@fixtures/AcceptanceTest';

test('As a new customer, I must be able to change email and password via account.', { tag: '@Account' }, async ({
    ShopCustomer,
    StorefrontAccountLogin,
    StorefrontAccount,
    IdProvider,
    Register,
    StorefrontAccountProfile,

}) => {

    const customer = { email: IdProvider.getIdPair().uuid + '@test.com' , password: IdProvider.getIdPair().uuid };
    const changedEmail = { email: IdProvider.getIdPair().uuid + '@test.com' };


    //customer login
    await ShopCustomer.goesTo(StorefrontAccountLogin.url());
    await ShopCustomer.attemptsTo(Register(customer));
    await ShopCustomer.expects(StorefrontAccount.page.getByText(customer.email, { exact: true })).toBeVisible();

    //open profile
    await ShopCustomer.goesTo(StorefrontAccountProfile.url());
    await StorefrontAccountProfile.changeEmailButton.click();
    await ShopCustomer.expects(StorefrontAccountProfile.emailAddressInput).toBeVisible();
    await StorefrontAccountProfile.emailAddressInput.fill(changedEmail.email);
    await StorefrontAccountProfile.emailAddressConfirmInput.fill(changedEmail.email);
    await StorefrontAccountProfile.emailConfirmPasswordInput.fill(customer.password);
    // eslint-disable-next-line no-console
    await console.log(customer.password);
    await StorefrontAccountProfile.saveEmailAddressButton.click();



    //change email
    //change password
    //verify change

});
