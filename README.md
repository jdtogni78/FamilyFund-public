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
** DB Server
** App Server: unix, java or php
* Project/Framework setup
* Test setup with coverage analysis.
* App/API server setup

#### Assets
* Data will be manually set on the database.
* Have name, type (stock/digital coin/other), last price, last price date
* source_feed(where data comes from), feed_id(id within the data feed)
* Assets have a price history (asset, price, date)
* Asset changes should be tracked on the change historical log (added asset, changed asset field)
* Assets are global, independent of funds and portfolios

#### Asset Price Update API
* Sets asset price/date
* Inputs: source_feed, feed_id, price, date

#### Portfolio
* Data will be manually set on the database.
* Has assets
* Each asset 
    * has id, quantity/shares
    * can calculate % of portfolio
* Can calculate total value
* Assets can only be registered once in a portfolio

#### Funds (Investment Fund)
* Data will be manually set on the database.
* Have name, goal(long text), total shares (double)
* All changes should be tracked in a historical change log (added fund,  changed fund field)
* Has one portfolio
* It can calculate its total value based on its portfolio
* It can calculate the fund share price

### V2

#### Funds (Updates)
* Have sale restriction (%)
* Has matching rules
* It can calculate the unallocated funds (that don’t belong to Fund Holders)

#### Matching Rules
* Has Name, $ range, date period, match(%)
* Matching rules are global, independent of funds and portfolios

#### Fund Share Holders
* Name, email, type (person/reserve)
* CC email list (comma separated text, optional)

#### Fund Holder Shares
* Has Fund Holder, share type (purchase, borrow, sale), portfolio asset id, shares (double)
* Have shares of Funds (not assets)
    * All changes should be tracked in a Fund Holder share history
* Have borrowed shares of Funds - and a historical table

### V3

#### Infrastructure
* SMS Setup
* PDF Generation

#### Fund Holder Quarterly Report
* Report will be triggered by command line
* Output is PDF
* Will be sent via email 
* Matching rule status (if borrowing or sales last year, shows as on not available)
* Borrowing status
* Sale Restriction % 
* Overall Fund Performance (includes line graph)
* Last Year Fund Performance
* Total shares/value

#### Fund Share Transactions
* Fund Holders can borrow or purchase shares
* Generates email
* This actions will be performed by command line
* Purchases
    * Apply the matching rules - validate qualification, add extra shares
        * Matching is reduced by last years sales/borrowing amount
    * Shares will be moved from the unallocated pool
    * There must be enough unallocated shares on the fund
* Sales
    * Restricted by the Fund (max %)
* Borrowing
    * Reduce Fund Holder shares, add borrowing shares

