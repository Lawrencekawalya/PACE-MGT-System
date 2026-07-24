# Role and Acceptance Guide

## Administrator

Administrators manage school settings, staff and permissions, academic periods, the PACE catalogue, students and teacher assignments, assessments and approvals, inventory, reports, and System status. Before a term starts, confirm exactly one active year and term, reconcile the approved catalogue, assign every student to a teacher, and enter approved opening stock through stock receipts.

## Teacher

Teachers register students, who are assigned to them automatically, and manage only students assigned to them by an administrator. They enroll and place those students, assign and progress PACEs, record Self Tests and final tests, handle ordinary retry approvals, and view academic reports. Teachers cannot manage stock or another teacher's students through either screens or direct URLs.

## PACE Officer

PACE Officers receive and adjust stock, physically issue assigned PACEs, and view inventory reports. They do not register students, academically assign PACEs, enter results, or view academic reports. A staff member may hold both Teacher and PACE Officer roles.

## MVP acceptance script

Use named test accounts and record pass/fail evidence for each step.

1. Administrator creates Teacher and PACE Officer accounts and confirms restricted navigation and direct URL denial.
2. Administrator creates the active academic year and term, imports the approved workbook, and obtains a clean `catalogue:reconcile` result.
3. Teacher registers a student and confirms automatic ownership. Administrator registers another student, assigns a teacher, and confirms only that teacher can open the record.
4. Teacher enrolls and places the student in multiple courses, then assigns the correct next PACE.
5. PACE Officer receives stock and issues the assigned booklet. Verify stock decreases only at physical issue and the movement is immutable.
6. Teacher moves the PACE through in-progress and awaiting-test states, records a passing score, and verifies the next recommendation.
7. Record a failed Self Test, verify attempt history and the configured two-attempt default, and obtain the required approval beyond the limit.
8. Record a final-test retry and verify no new stock issue. Approve a full PACE repeat and verify a new issue reduces stock.
9. With one copy on hand, attempt two concurrent issues and verify only one succeeds.
10. Run academic and inventory reports, download normal exports immediately, and verify large queued exports complete under the worker.
11. Verify loading, empty, validation, unauthorized, and conflict states with keyboard-only operation on desktop, tablet, and mobile widths.
12. Administrator reviews System status, activity logs, failed jobs, backup evidence, catalogue totals, and opening stock totals.

FICA representatives should sign the final result with their name, role, date, approved catalogue checksum, opening-stock totals, accepted exceptions, and release decision.
