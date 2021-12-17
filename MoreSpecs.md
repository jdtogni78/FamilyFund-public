## OUT OF SCOPE

### V2

#### Funds (Updates)
* Has matching rules
* It can calculate the unallocated funds (that don’t belong to Fund Holders)

#### Matching Rules
* What: Rules that represent an incentive to share holders, additional shares to be added to fund share holders that purchase shares within a year.
* Input: manual
* Fields: Has Name, $ range, date period, restriction/clearance (days) , match(%)
* Restriction/Clearance: if balance of sales or borrows is negative within the previous X days, reduce from matching amount
* Matching rules are global, independent of funds and portfolios

#### Share Holders
* Input: manual
* Name, email, type (person/reserve)
* CC email list (comma separated text, optional)

#### Share Holders Balance
* Input: only through fund transactions
* What: Represent how many shares each account holder owns and borrowed.
* Fields: Has Fund Holder, share type (own, borrowed), Fund id, amount of shares (double), last update

#### Fund Trading Rules
* Determine sale restrictions, further amounts must be borrowed
* Sales can only apply to the amount the fund grew last year
* Per year, sale restrictions are any combination of:
    * max % sale of increase - cant sell more than % of last year performance growth
    * min % fund performance - restrict (growth - min fund perf)

Sales Restriction Examples:
| Fund | Growth | Max % Sale of Increase | Min Fund Increase | Can Sell |
|-----:|-------:|-----------------------:|------------------:|---------:|
| 1000 | 0      | 3%                     |                   | 0        |
| 1000 | 10     | 3%                     |                   | 10       |
| 1000 | 100    | 3%                     |                   | 30       |
| 1000 | 0      |                        | 3%                | 0        |
| 1000 | 10     |                        | 3%                | 0        |
| 1000 | 100    |                        | 3%                | 70       |
| 1000 | 0      | 3%                     | 3%                | 0        |
| 1000 | 10     | 3%                     | 3%                | 0        |
| 1000 | 100    | 3%                     | 3%                | 30       |
| 1000 | 100    | 6%                     | 6%                | 40       |

### V3
#### Infrastructure
* SMS Setup
* PDF Generation

#### Fund Holder Quarterly Report
* Report will be triggered by API
* Output is an email with an attached PDF
* PDF Content
    * Matching rule status (if borrowing or sales last year, shows as on not available)
    * Borrowing status
    * Fund Trading Rules 
    * Overall Fund Performance (includes line graph)
    * Last Year Fund Performance
    * Total shares/value

#### Fund Share Transactions
* What: Represent each change in fund shares
* Input: API for adding transactions, which updates fund shares balance
* Fields: Has Share Holder, share type (purchase, sale, borrow, repay), Fund id, amount of shares (double), date
* Generates email with the change details
* Purchases
    * Apply the matching rules - validate qualification, add extra shares
        * Matching is reduced by last years sales/borrowing amount
    * Shares will be moved from the unallocated pool
    * There must be enough unallocated shares on the fund
* Sales
    * Restricted by the Fund (max %)
* Borrowing
 * Reduce Fund Holder shares, add borrowing shares

Purchase Examples:
|  Matching Name | Range | Period | Restriction | Match | Borrowed 2021 | Deposited 2022 | Match 2022 | Total Deposit 2022 | Borrow Balance |
|---------------:|------:|-------:|------------:|------:|---------------|----------------|------------|--------------------|----------------|
| 100% up to 100 | 0-100 | 2021   | 365         | 100   | 0             | 105            | 100        | 105+100            | 0              |
| 100% up to 100 | 0-100 | 2021   | 365         | 100   | 10            | 55             | 45         | 55+45-10=90        | 10-10=0        |
| 100% up to 100 | 0-100 | 2021   | 365         | 100   | 55            | 160            | 100        | 160+100-55=205     | 55-55=0        |
| 100% up to 100 | 0-100 | 2021   | 365         | 100   | 0             | 150            | 100        | 250                |                |
| 200% up to 50  | 0-50  | 2021   | 365         | 200   | 0             | 55             | 100        | 55+200%*50=155     |                |
| 200% up to 50  | 0-50  | 2021   | 365         | 200   | 55            | 45             | 0          | 0                  | 55-45=10       |

### V4

* API Should be documented with Swagger
* Documentation with JavaDoc for Java (https://dzone.com/articles/best-practices-of-code-documentation-in-java) or similar (Doxygen?) for PHP.

#### API ACL
* What: controls access to API
* Data Input: Manual
* Fields: Has roles, permissions (read/write), api/page/command line flag, URI 
* Roles: admin, price update, account holder

#### Login/Logout
* Login/Logout pages
* Security via tokens (TBD)

#### Portfolio Updates
* What: Change asset allocations and propagate to automated investment tool
* TBD

#### Changelog APIs
