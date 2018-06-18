# Rave Joomla-VirtueMart-3
Rave for Joomla VirtueMart

## Requirements
- Joomla installation
- Virtue Mart 3 Installation
- A Rave Account

> Sign up for a `live` account [here](https://rave.flutterwave.com/)

> Sign up for a `test` account [here](https://ravesandbox.flutterwave.com/)

## Instructions
1. Download the Zip Folder
2. Install the plugin using normal Joomla extension installation `Extensions->Manage->Install`.
3. Go to `Extensions->Plugin Manager` and search for `VM Payment - Rave`
4. Click on the plugin name and enable the plugin
5. Go to `Components->Virtuemart->Payment methods`.
6. Click on `New`.
7. Fill the form and select Payment Method: `VM Payment - Rave` then `apply/save`.
8. Click on the `Configuration` tab, fill the parameters
9. Set `Staging Mode` to `Yes` when you are testing.

### Info
Enable Test Mode for testing and Live mode for transaction

>Test Card

```bash
5438898014560229
cvv 789
Expiry Month 09
Expiry Year 19
Pin 3310
otp 12345
```

>Test Bank Account

```bash
Access Bank
Account number: 0690000004
otp: 12345
```

```bash
Providus Bank
Account number: 5900102340, 5900002567
otp: 12345
```

For [More Test Cards](https://flutterwavedevelopers.readme.io/docs/test-cards)
For [More Test Bank Accounts](https://flutterwavedevelopers.readme.io/docs/test-bank-accounts)