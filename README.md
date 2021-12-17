# Family Fund
A simple system to manage fund shares and composition.

## Objective and Goals

The objective is to create a first MVP of a fund shares management system.
* First goal in to have minimal functionality. 
* No graphical interface (manual inputs for some, API for others).
* Focus on code quality, good OO/separation of concepts 
* Use Java or PHP.
* Use Frameworks/ORM: Java Spring/Hibernate or some PHP Framework (Symfony/Laravel/Composer).
* Documentation with JavaDoc for Java (https://dzone.com/articles/best-practices-of-code-documentation-in-java) or similar (Doxygen?) for PHP.
* The database must be  MariaDB or MySQL. Use TLS 1.2 connection.
* Unit tests with good coverage, using TestNG or similar.
* API Tests (Karate)
* Deliverable should be Docker-compose setup 
 * With separate DB and app servers
 * Ability to run tests & coverage analysis
* The delivery should be in phases, allowing for review.

## MVP 1
### V1 
#### Infrastructure:
* Docker setup
    * DB Server
    * App Server: unix, java or php
* Project/Framework setup
* Unit Test setup with coverage analysis.
* App server setup
* API test setup

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
* What: Adds price log and updates asset price
* Data Input: API (add/delete by id)
* Fields: source_feed, feed_id, price, date
* Causes asset price to be updated on Assets table
* No change in Asset Changelog

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




