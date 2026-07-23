<?php
// Intentionally bad controller for code review (no need to run).
class OrderController {
    public function list() {
        // Medium: Creates PDO directly inside the controller (no dependency injection).
        // Medium: Hardcoded database path reduces portability across environments.
        $db = new PDO('sqlite:/tmp/production.sqlite');

        // High: No authentication or authorization checks before accessing order data.
        // Low: Defaulting to user ID 1 may unintentionally expose another user's orders.
        $userId = $_GET['user'] ?? 1;

        // High: Excessive default page size can lead to memory/performance issues.
        $per = $_GET['per'] ?? 1000;
        // Medium: Inputs are not validated or cast to integers.
        $page = $_GET['page'] ?? 1;

        // High: SQL injection vulnerability due to string interpolation.
        $sql = "SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC";
        // High: fetchAll() loads all matching rows into memory before pagination.
        // Medium: No error handling around database operations.
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // High: Pagination is performed in PHP instead of SQL (LIMIT/OFFSET).
        $offset = ($page-1)*$per;
        $slice = array_slice($rows, $offset, $per);

        // High: N+1 Query problem 
        foreach ($slice as &$r) {
            // High: Secondary SQL injection risk through concatenation.
            $items = $db->query("SELECT * FROM order_items WHERE order_id = ".$r['id'])->fetchAll(PDO::FETCH_ASSOC);
            // Medium: no try catch for pdo errors
            $r['items'] = $items;
        }
        // Medium: Using a reference (&$r) can cause subtle bugs if reused later.

        // Low: Cache disabled without explanation or documented requirement.
        header('Cache-Control: no-store');

        // Low: Missing Content-Type: application/json header.
        // Medium: No handling for json_encode() failures.
        // Medium: Returning JSON instead of outputting/responding may not work in all frameworks.
        return json_encode($slice);
    }
}


/*

PRIORITIZED REVIEW SUMMARY

HIGH
1. SQL injection in orders query.
2. SQL injection in order_items query.
3. Missing authentication.
4. Missing authorization.
5. Excessive default page size (1000).
6. In-memory pagination using fetchAll() + array_slice().
7. N+1 query problem.
8. No input validation for user, page, and per.

MEDIUM
9. PDO instantiated directly in controller.
10. Hardcoded SQLite path.
11. No database exception handling.
12. No json_encode() error handling.
13. Reference variable (&$r) may introduce side effects.

LOW
14. Default user ID of 1 may leak data.
15. Missing Content-Type: application/json header.
16. Returning JSON string instead of sending a response object/output.
17. Unexplained Cache-Control: no-store usage.

RECOMMENDED FIXES
- Use prepared statements for all SQL queries.
- Implement authentication and authorization checks.
- Validate and cast all request parameters.
- Apply SQL-level pagination using LIMIT and OFFSET.
- Replace N+1 queries with JOINs or batched retrieval.
- Inject the database connection via dependency injection.
- Add try/catch blocks around database operations.
- Return a proper JSON response with appropriate headers.
*/
