# Family Fund
A simple system to manage fund shares and composition.

## Objective and Goals

The objective is to create a first MVP of a fund shares management system.
* First goal in to have minimal functionality. 
* No graphical interface (manual inputs for most, some command line parameters and one API).
* Focus on code quality, good OO/separation of concepts 
* Use Java or PHP.
* Use Frameworks/ORM: Java Spring/Hibernate or some PHP Framework (Symfony/Laravel/Composer).
* Documentation with JavaDoc for Java (https://dzone.com/articles/best-practices-of-code-documentation-in-java) or similar (Doxygen?) for PHP.
* The database must be  MariaDB or MySQL. Use TLS 1.2 connection.
* Unit tests with good coverage, using TestNG or similar.
* Deliverable should be Docker-compose setup 
 * With separate DB and app servers
 * Ability to run tests & coverage analysis
* The delivery should be in phases, allowing for review.

## Versions
### V1 
#### Infrastructure:
* Docker setup
 * DB Server
 * App Server: unix, java or php
* Project/Framework setup
* Test setup with coverage analysis.
* App server setup

#### API ACL
* What: controls access to API
* Data Input: Manual
* Fields: Has roles, permissions (read/write), api/page/command line flag, URI 
* Roles: admin, price update, account holder

#### Assets
* What: Represents a stock ticker, digital currency name, real estate or other asset. Has historical prices.
* Data Input:
 * API CRUD (where delete deactivates), no price on API (via price history api)
* Fields: 
 * name, type (stock/digital coin/other), last price, last price date, active
 *  source_feed(where data comes from), feed_id(id within the data feed)
* Has price history
* Has an asset changelog
* Assets are global, independent of funds and portfolios

#### Asset Changelog
* Input: only through system activity
* Asset changes should be tracked on the change historical log 
* Tracks added asset, changed asset field (no price changes)

#### Asset Price History
* What: a log of price changes
* Data Input: only through API
* Assets have a price history (asset, price, date)

#### Asset Price Update API
* What: Sets asset price/date (adding to history)
* Inputs: source_feed, feed_id, price, date

#### Portfolio
* What: represents a collection of investments on assets
* Input: CRUD API
* Has assets
* Each asset, for a portfolio: 
 * has id, quantity/shares
 * can calculate % of portfolio
* Can calculate total value
* Assets can only be registered once in a portfolio
* Changes are tracked on fund changelog or 

#### Funds (Investment Fund)
* What: Associates a portfolio to fund holders and their shares.
* Input: CRUD API
* Have name, goal(long text), total shares (double)
* Has one portfolio
* It can calculate its total value based on its portfolio
* It can calculate the fund share price

#### Fund Changelog
* Input: only through activity
* All changes to funds and portfolio should be tracked 
* Track: changes to funds, changes to portfolios

### V2

#### Funds (Updates)
* Has matching rules
* It can calculate the unallocated funds (that don’t belong to Fund Holders)

#### Matching Rules
* What: Rules that represent an incentive to share holders, additional shares to be added to fund share holders that purchase shares within a year.
* Input: manual
* Fields: Has Name, $ range, date period, restriction period, match(%)
* Restriction period: if balance of sales or borrows is negative within this period, reduce from matching amount
* Matching rules are global, independent of funds and portfolios

#### Share Holders
* Input: manual
* Name, email, type (person/reserve)
* CC email list (comma separated text, optional)

#### Fund Shares Balance
* Input: only through fund transactions
* What: Represent how many shares each account holder owns and borrowed.
* Fields: Has Fund Holder, share type (own, borrowed), Fund id, amount of shares (double), last update

### V3
#### Infrastructure
* SMS Setup
* PDF Generation

#### Funds (Updates)
* Have sale restriction (%) - maximum amount of the fund that can be sold, further amounts must be borrowed

#### Fund Holder Quarterly Report
* Report will be triggered by API
* Output is an email with an attached PDF
* PDF Content
 * Matching rule status (if borrowing or sales last year, shows as on not available)
 * Borrowing status
 * Sale Restriction % 
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

