-- lets reset the database from test entries:
delete from account_balances where account_id > 300;
delete from transaction_matchings where transaction_id > 1000;
delete from transactions where account_id > 300;
delete from deposit_requests where id > 200;
delete from cash_deposits where id > 300;
delete from transactions where id > 2000;
delete from account_matching_rules where account_id > 300;
delete from accounts where fund_id > 300;
delete from funds where id > 300;
delete from portfolio_assets where portfolio_id > 300;
delete from portfolios where id > 300;
delete from users where id > 300;

