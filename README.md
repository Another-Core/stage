# Shift Assignment Service

A small backend service built with **plain PHP and MySQL**. It checks whether an employee can be assigned to a shift and finds eligible employees. It implements the four required rules: **SKILL, OVERLAP, REST** (11 hours), and **CONTRACT_HOURS**.

## How to run

1. Start **Apache** and **MySQL** in XAMPP.
2. Place the files in `C:\xampp\htdocs\stage`.
3. Import `database.sql` through phpMyAdmin (`http://localhost/phpmyadmin/`) into a fresh database. If needed, update the connection settings in `db.php`.

## How to test

**Unit tests (no database required):** Open the VS Code terminal in the project folder and run:

```bat
C:\xampp\php\php.exe tests.php
```

The test script prints `PASS` or `FAIL` for each rule test. The provided suite contains 14 tests. A successful run should show 14 `PASS` results and no `FAIL` results.

**Manual API tests (Thunder Client in VS Code):** Start Apache and MySQL first. With the original seed data:

| Method | URL / JSON body | Expected result |
| --- | --- | --- |
| GET | `http://localhost/stage/shifts/s5/eligible-employees` | `200`: Bram and Anna |
| POST | `http://localhost/stage/shifts/s5/assign` with body `{"employeeId":"e2"}` | `200`: `s5` assigned to Bram |
| POST again | Same URL and body | `409`: shift already assigned |
| POST | `http://localhost/stage/shifts/s6/assign` with body `{"employeeId":"e2"}` | `422`: `SKILL` |
| GET | `http://localhost/stage/shifts/s999/eligible-employees` | `404`: shift not found |

For POST requests, choose **Body → JSON** in Thunder Client. Use **phpMyAdmin → shift_assignment → shifts** to verify saved assignments. Reset `s5.assignedEmployeeId` to SQL `NULL` before repeating independent tests, or reimport the seed data into a fresh database. Manual tests change database state; unit tests do not.

## Decisions and trade-offs

I chose PHP and MySQL because I am familiar with them. I kept the assignment rules in `rules.php`, separate from database access and HTTP handling, so the logic can be tested without MySQL. I use ISO 8601 timestamps and the **Europe/Amsterdam** timezone. For weekly contract hours, the whole shift counts toward the ISO week in which it starts.

## Time limit and next steps

The assignment had a strict **two-hour limit**, so I prioritized the two required API endpoints, the four rules, and unit tests. I did not build a separate interface for creating or editing shifts because it was not required. For manual testing, I used **Thunder Client** and managed test data in **phpMyAdmin**. I also tested the project on two different computers: a lower-performance laptop and a more powerful desktop PC.

With more time, I would organize the code into separate folders, improve input validation, add end-to-end tests and build a simple interface for managing shifts.

**Concurrent assignments:** Two planners could check the same employee at the same time and assign them to different overlapping shifts. The current conditional `UPDATE` prevents assigning the *same shift* twice, but not this case. In production, I would use a database transaction, lock the employee row with `SELECT ... FOR UPDATE`, recheck the rules, then save and commit.

## Use of AI

I used **ChatGPT** as a learning and development assistant. It helped provide an initial code example, review my PHP code for errors and readability, explain ISO 8601 dates and ISO weeks (which were new to me), suggest small code improvements and troubleshoot problems. It also helped create the `.htaccess` routing and `tests.php`, and draft this README. Because of the two-hour limit, I used AI assistance for testing and documentation tasks while prioritizing understanding and implementing the required backend logic. I reviewed the code and can explain how it works.

**Development time:** 03 November 2026, 20:15–22:10 (1 hour 55 minutes).
