-- docker run --detach --name ff-mariadb --env MARIADB_USER=ff --env MARIADB_PASSWORD=1324 --env MARIADB_ROOT_PASSWORD=123456  mariadb:latest
use familyfund;

create table matching_rules
(
    id           bigint unsigned auto_increment
        primary key,
    name               varchar(50)                 not null,
    dollar_range_start decimal(13, 2) default 0.00 null,
    dollar_range_end   decimal(13, 2)              null,
    date_start         date                        not null,
    date_end           date                        not null,
    match_percent      decimal(5, 2)               not null,
    created            datetime       default current_timestamp() not null,
    updated            datetime                    null
);

create table trading_rules
(
    id           bigint unsigned auto_increment
        primary key,
    name                      varchar(30)                          not null,
    max_sale_increase_pcnt    decimal(5, 2)                        null,
    min_fund_performance_pcnt decimal(5, 2)                        null,
    created                   datetime default current_timestamp() not null,
    updated                   datetime                             null
);

-- create table account_holders
-- (
--     id           bigint unsigned auto_increment
--         primary key,
--     first_name varchar(50)                          not null,
--     last_name  varchar(50)                          not null,
--     email      varchar(120)                         not null,
--     type       varchar(1)                           not null,
--     created    datetime default current_timestamp() not null,
--     updated    datetime                             null
-- );

create table accounts
(
    id           bigint unsigned auto_increment
        primary key,
    code     varchar(15)                          not null,
    nickname varchar(15)                          null,
    email_cc varchar(1024)                        null,
    user_id  bigint unsigned                      not null,
    fund_id  bigint unsigned                      not null,
    created  datetime default current_timestamp() not null,
    updated  datetime                             null,
    constraint accounts_fund_id_fk
        foreign key (fund_id) references funds (id),
    constraint accounts_users_id_fk
        foreign key (user_id) references users (id)
);

create table account_matching_rules
(
    id           bigint unsigned auto_increment
        primary key,
    account_id  bigint unsigned                      not null,
    matching_id bigint unsigned                      not null,
    created     datetime default current_timestamp() not null,
    updated     datetime                             null,
    constraint account_matching_rules_account_id_fk
        foreign key (account_id) references accounts (id),
    constraint account_matching_rules_matching_rules_id_fk
        foreign key (matching_id) references matching_rules (id)
);

create table account_trading_rules
(
    id           bigint unsigned auto_increment
        primary key,
    account_id      bigint unsigned                      not null,
    trading_rule_id bigint unsigned                      not null,
    created         datetime default current_timestamp() not null,
    updated         datetime                             null,
    constraint account_trading_rules_accounts_id_fk
        foreign key (account_id) references accounts (id),
    constraint account_trading_rules_trading_rules_id_fk
        foreign key (trading_rule_id) references trading_rules (id)
);

create table transactions
(
    id           bigint unsigned auto_increment
        primary key,
    source      varchar(1)                           null,
    type        varchar(1)                           null,
    shares      decimal(19, 4)                       null,
    account_id  bigint unsigned                      not null,
    matching_id bigint unsigned                      null,
    created     datetime default current_timestamp() not null,
    updated     datetime                             null,
    constraint transactions_account_id_fk
        foreign key (account_id) references accounts (id),
    constraint transactions_matching_id_fk
        foreign key (matching_id) references matching_rules (id)
);

create table account_balances
(
    id           bigint unsigned auto_increment
        primary key,
    type       varchar(1)                           null,
    shares     decimal(19, 4)                       null,
    account_id bigint unsigned                      not null,
    tran_id    bigint unsigned                      null,
    created    datetime default current_timestamp() not null,
    updated    datetime                             null,
    constraint account_balances_accounts_id_fk
        foreign key (account_id) references accounts (id),
    constraint account_balances_transactions_id_fk
        foreign key (tran_id) references transactions (id)
);

