PRAGMA journal_mode=WAL;

CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  total REAL NOT NULL,
  created_at TEXT NOT NULL
);

-- adding user_id,created_at index to orders table
CREATE INDEX idx_orders_user_created
ON orders(user_id, created_at);

CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  sku TEXT NOT NULL,
  qty INTEGER NOT NULL DEFAULT 1
);

-- index for order_id because most of times order_items is queried with order_id filter
CREATE INDEX idx_order_items_order_id
ON order_items(order_id);


CREATE TABLE IF NOT EXISTS payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  method TEXT,
  status TEXT
);

-- index for order_id 
CREATE INDEX idx_payments_order_id
ON payments(order_id);