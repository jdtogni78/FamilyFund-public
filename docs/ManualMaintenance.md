# Manual Fund Maintenance

* Investments
* Fund
* Beneficiary Accounts
* Transactions

# Investments

The investment strategy of the fund is to maintain chosen percentages for selected assets and rebalance every 3 months.
There are 3 major categories of assets: Growth, Stability and Crypto.
Growth assets are currently SP500, technology and semiconductors sectors, but instead of the plain index, we use direxion 3x leveraged ETFs.
Stability assets are currently gold (IAU) and cash.
Crypto assets are currently Bitcoin and Ethereum.

Use latest reports for more details on the specific percentages.

All investment accounts are held at Interactive Brokers, which allows for API driven investments.

# Fund

The fund value is the value of the underlying investment account. The number of shares is maintained and adjusted accourding to the transactions, using the share price calculated from the previous day's value.

# Beneficiary Accounts

The beneficiary accounts are the accounts that maintain shares of the fund.
Beneficiaries can deposit and withdraw shares from the fund.
Refer to the transactions document for the details on how transactions affect the fund and beneficiary accounts.

# Reports

The fund sends quarterly reports to the beneficiaries.
There are 2 reports:
* A overall fund report, which shows the value of the fund, the number of shares, history of transactions, and overall fund performance.
  * There is an Administrative version of the report, which is sent to admins monthly, and reports all accounts and balances.
* A beneficiary report, which shows the value of the beneficiary's account, the number of shares, history of transactions, and beneficiary account performance.

# Maintenance

## Investment Maintenance

Every 3 months, the investment accounts should be rebalanced to the chosen percentages.
Every year the inventment board and advisors should meet and review the investment strategy and percentages.

## Grantor Support

Grantors may make deposits to the fund and choose the allocation of the funds to beneficiaries or leave it unallocated.
Grantors may sponsor matching for beneficiaries. See the transactions document for more details.

## Beneficiary Support

Beneficiaries may make deposits to their accounts and withdrawals from the fund, given the rules and restrictions of the fund.
Transactions must be recorded and the fund's ledger, and the beneficiary's ledger must be updated accordingly.

