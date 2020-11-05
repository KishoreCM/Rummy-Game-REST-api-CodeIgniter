CREATE TABLE users(id INT PRIMARY KEY AUTO_INCREMENT,
     name VARCHAR(100) NOT NULL,
     email VARCHAR(100) not null,
     total_score int not null default 0,
     balance decimal(10, 2) not null default 0.00,
     no_of_matches_played int not null default 0,
     win_count int not null default 0,
     lose_count int not null default 0,
     password text not null,
     updated_at timestamp not null
     );


create table game( bet_amount decimal(10, 2) default 0.00, score int default 0, card_values json,
winner boolean default false, id varchar(100) not null, user_id int, foreign key (user_id) references users(id) );

ALTER TABLE game ADD bet_closed BOOLEAN;

ALTER TABLE game ALTER bet_closed SET DEFAULT FALSE;
