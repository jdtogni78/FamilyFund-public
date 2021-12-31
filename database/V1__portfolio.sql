use familyfund;

create table assets
(
    id           bigint unsigned auto_increment
        primary key,
    name            varchar(128)   not null,
    type            varchar(3)     not null,
    source_feed     varchar(50)    not null,
    feed_id         varchar(128)   not null,
    last_price      decimal(19, 2) not null,
    last_price_date date           not null,
    deactivated     datetime       null,
    created    datetime default current_timestamp() not null,
    updated    datetime                             null
);

create table asset_prices
(
    id           bigint unsigned auto_increment
        primary key,
    asset_id    bigint unsigned null,
    price       decimal(13, 2) not null,
    created     datetime default current_timestamp() not null,
    constraint asset_prices_assets_id_fk
        foreign key (asset_id) references familyfund.assets (id)
);

create table funds
(
    id           bigint unsigned auto_increment
        primary key,
    name         varchar(30)                          not null,
    goal         varchar(1024)                        null,
    total_shares decimal(20, 4)                       not null,
    created      datetime default current_timestamp() not null,
    updated      datetime                             null
);

use familyfund;

create or replace table portfolios
(
    id           bigint unsigned auto_increment
        primary key,
    fund_id    bigint unsigned                      not null,
    last_total      decimal(13, 2) not null,
    last_total_date datetime       not null,
    created    datetime default current_timestamp() not null,
    updated    datetime                             null,
    constraint portfolios_fund_id_fk
        foreign key (fund_id) references funds (id)
);

create table portfolio_assets
(
    id           bigint unsigned auto_increment
        primary key,
    portfolio_id bigint unsigned                       not null,
    asset_id     bigint unsigned                       not null,
    shares       decimal(21, 8)                       not null,
    created      datetime default current_timestamp() not null,
    updated      datetime                             null,
    constraint portfolio_assets_assets_id_fk
        foreign key (asset_id) references assets (id),
    constraint portfolio_assets_portfolios_id_fk
        foreign key (portfolio_id) references portfolios (id)
);
