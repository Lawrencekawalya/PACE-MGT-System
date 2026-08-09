<?php

namespace App\Services;

use App\Models\User;
use App\RoleName;

/**
 * @phpstan-type GuideWorkflow array{
 *     title: string,
 *     outcome: string,
 *     steps: list<string>
 * }
 * @phpstan-type RoleGuide array{
 *     role: string,
 *     label: string,
 *     summary: string,
 *     workflows: list<GuideWorkflow>,
 *     boundaries: list<string>
 * }
 */
class RoleGuideService
{
    /** @return list<RoleGuide> */
    public function for(User $user): array
    {
        $roles = $user->hasRole(RoleName::Administrator)
            ? RoleName::cases()
            : array_filter(
                [RoleName::Teacher, RoleName::PaceOfficer, RoleName::Accountant],
                fn (RoleName $role): bool => $user->hasRole($role),
            );
        $guides = $this->guides();

        return array_values(array_map(
            fn (RoleName $role): array => $guides[$role->value],
            $roles,
        ));
    }

    /** @return array<string, RoleGuide> */
    private function guides(): array
    {
        return [
            RoleName::Administrator->value => [
                'role' => RoleName::Administrator->value,
                'label' => RoleName::Administrator->label(),
                'summary' => 'Configure the school, control access, supervise academic and stock operations, and close each academic cycle.',
                'workflows' => [
                    [
                        'title' => 'Prepare the school',
                        'outcome' => 'The platform is ready for staff and student work.',
                        'steps' => [
                            'Confirm the school identity, timezone, date format, assessment pass marks, retry limits, and minimum PACEs per subject per term in School settings.',
                            'Create the academic year and its terms, then keep exactly one year and one term active.',
                            'Create learning centers, assign each grade to one center, and attach the teachers responsible for that center.',
                        ],
                    ],
                    [
                        'title' => 'Set up staff access',
                        'outcome' => 'Every staff member has only the access needed for their work.',
                        'steps' => [
                            'Create staff accounts and assign the Administrator, Teacher, PACE Officer, or Accountant role.',
                            'Assign both Teacher and PACE Officer roles only when one person performs both duties.',
                            'Review active accounts, reset passwords when authorized, and remove access by deactivating accounts instead of deleting history.',
                        ],
                    ],
                    [
                        'title' => 'Maintain the academic catalogue',
                        'outcome' => 'Grades, courses, PACEs, and prescribed curriculum remain accurate.',
                        'steps' => [
                            'Use Catalogue setup to review levels, subjects, courses, and individual PACEs.',
                            'Use Catalogue imports for controlled workbook updates and verify the reconciliation result before committing.',
                            'Use Curriculum to prescribe courses and PACE sequences for each grade.',
                        ],
                    ],
                    [
                        'title' => 'Oversee students and learning',
                        'outcome' => 'Students are correctly placed and their work remains traceable.',
                        'steps' => [
                            'Register a student in a grade that belongs to an active learning center and create the current enrollment.',
                            'Confirm the automatically prescribed courses, then record the diagnostic starting PACE where prior history is not being entered.',
                            'Monitor PACE assignments, assessment attempts, retry approvals, repeated PACEs, and append-only score corrections.',
                        ],
                    ],
                    [
                        'title' => 'Control stock and procurement',
                        'outcome' => 'PACE stock can be issued, replenished, and audited.',
                        'steps' => [
                            'Maintain suppliers, inventory items, reorder points, and target stock levels.',
                            'Review the reorder queue, create draft purchase orders, and approve or reject submitted orders.',
                            'Track orders through approval, sending, partial receipt, and full receipt while preserving the stock ledger.',
                        ],
                    ],
                    [
                        'title' => 'Review and promote students',
                        'outcome' => 'End-of-year decisions are deliberate and connected to the next enrollment.',
                        'steps' => [
                            'Review academic progress and assessment history before making a decision.',
                            'Record promotion, retention, transfer, or programme completion manually.',
                            'For promoted or retained students, confirm the destination grade, learning center, academic period, and prescribed curriculum.',
                        ],
                    ],
                    [
                        'title' => 'Monitor and support operations',
                        'outcome' => 'Staff can be trained and operational problems are detected early.',
                        'steps' => [
                            'Use academic, issuing, and inventory reports with the appropriate period and export only the data required.',
                            'Use System status to review infrastructure, catalogue, stock-ledger, ownership, and active-period checks.',
                            'Use the Teacher, PACE Officer, and Accountant guides on this page when training or supporting those users.',
                        ],
                    ],
                ],
                'boundaries' => [
                    'Do not delete academic attempts, stock movements, or historical decisions; use the supported correction and status workflows.',
                    'Promotion remains a manual administrative decision and is not triggered automatically by scores or PACE completion.',
                    'Only one academic year and one term should be active at a time.',
                ],
            ],
            RoleName::Teacher->value => [
                'role' => RoleName::Teacher->value,
                'label' => RoleName::Teacher->label(),
                'summary' => 'Register and guide students in assigned learning centers from placement through assessment and academic reporting.',
                'workflows' => [
                    [
                        'title' => 'Confirm your learning center access',
                        'outcome' => 'You work only with the students and grades assigned to you.',
                        'steps' => [
                            'Confirm that the administrator has attached you to the correct learning center.',
                            'Check that the grades you supervise are attached to that center.',
                            'Report missing students or grades to the administrator instead of creating records under the wrong center.',
                        ],
                    ],
                    [
                        'title' => 'Register and enroll students',
                        'outcome' => 'A student has an active enrollment and prescribed courses.',
                        'steps' => [
                            'Register the student and select only a grade available in your assigned learning center.',
                            'Create the enrollment in the active academic year and term.',
                            'Review the courses automatically prescribed from the grade curriculum.',
                        ],
                    ],
                    [
                        'title' => 'Place and assign PACEs',
                        'outcome' => 'The student starts at the correct point without inventing earlier completion history.',
                        'steps' => [
                            'Use the diagnostic result to select the starting PACE for each enrolled course.',
                            'Assign the next required PACE from the student progress view.',
                            'After the subject term target is reached, continue assigning the next PACE when the student is academically ready; payment is checked at physical issue.',
                            'Send the physical issue request to the PACE Officer; issuance moves the assignment into progress.',
                        ],
                    ],
                    [
                        'title' => 'Monitor learning progress',
                        'outcome' => 'Every active PACE has a current and understandable status.',
                        'steps' => [
                            'Use the PACE work queue to review assigned, in-progress, awaiting-test, completed, and exception work.',
                            'Review each subject term target; four completed PACEs is the default minimum, and students may continue with additional PACEs.',
                            'Update the assignment status when the student reaches the next learning stage.',
                            'Review the student progress tab before assigning another PACE in the same course.',
                        ],
                    ],
                    [
                        'title' => 'Record assessments and retries',
                        'outcome' => 'Self Tests, final PACE Tests, failures, and retries remain traceable.',
                        'steps' => [
                            'Record each Self Test attempt and use the configured pass mark to determine readiness for the final test.',
                            'Record the final PACE Test as a separate assessment and retain failed attempts.',
                            'Request or approve retries within your authority; attempts beyond the configured limit require administrator approval.',
                            'When the whole PACE must be repeated, create a repeat assignment so a new consumable booklet can be issued.',
                        ],
                    ],
                    [
                        'title' => 'Review academic results',
                        'outcome' => 'Student progress can be discussed and handed to administration for decisions.',
                        'steps' => [
                            'Use academic reports for the students visible through your learning-center assignments.',
                            'Filter reports by the correct academic period before viewing or exporting.',
                            'Escalate promotion, transfer, completion, and historical score-correction decisions to the administrator.',
                        ],
                    ],
                ],
                'boundaries' => [
                    'You can manage only students in grades attached to your assigned learning centers.',
                    'You cannot physically issue PACEs, adjust stock, or receive purchase orders unless you also hold the PACE Officer role.',
                    'You cannot manage staff, school setup, suppliers, purchase-order approval, or promotions.',
                ],
            ],
            RoleName::PaceOfficer->value => [
                'role' => RoleName::PaceOfficer->value,
                'label' => RoleName::PaceOfficer->label(),
                'summary' => 'Issue consumable PACEs, maintain accountable stock, and replenish inventory through controlled purchase orders.',
                'workflows' => [
                    [
                        'title' => 'Prepare the issuing queue',
                        'outcome' => 'Only valid academic assignments are ready for physical issue.',
                        'steps' => [
                            'Open PACE issuing and filter the queue by learning center, student, course, or PACE.',
                            'Confirm the student, academic period, assignment, inventory item, and available quantity.',
                            'Do not issue an unassigned PACE; ask the teacher or administrator to create the academic assignment first.',
                        ],
                    ],
                    [
                        'title' => 'Issue a PACE',
                        'outcome' => 'The booklet is permanently assigned and stock is deducted once.',
                        'steps' => [
                            'Select the assignment and issue the matching PACE booklet physically.',
                            'Confirm that the issue record identifies the student, PACE, quantity, academic period, and issuing staff member.',
                            'The system deducts stock and moves the assignment to In progress; do not create a manual deduction for the same issue.',
                        ],
                    ],
                    [
                        'title' => 'Maintain inventory',
                        'outcome' => 'On-hand quantities agree with the append-only stock ledger.',
                        'steps' => [
                            'Use Inventory to review booklets, Score Keys, stock levels, and item status.',
                            'Receive or adjust stock with an accurate reference and reason.',
                            'Correct mistakes through a reversing correction instead of editing or deleting a posted movement.',
                        ],
                    ],
                    [
                        'title' => 'Configure replenishment',
                        'outcome' => 'The reorder queue reflects practical stock requirements.',
                        'steps' => [
                            'Set the reorder level at the quantity that should trigger attention.',
                            'Set the target stock at the quantity the school wants after replenishment.',
                            'Use bulk stock settings by selected items, course, item type, or the active catalogue, then review unusually large suggested totals.',
                        ],
                    ],
                    [
                        'title' => 'Create and receive purchase orders',
                        'outcome' => 'Shortages move through a controlled order and delivery trail.',
                        'steps' => [
                            'Select reorder suggestions, choose an active supplier, review quantities, and create the draft order.',
                            'Submit the order for administrator approval, then mark an approved order as sent.',
                            'Record each delivery against the sent order, including partial receipts, delivery reference, date, and received quantities.',
                            'Confirm that received quantities increase stock and outstanding quantities decrease.',
                        ],
                    ],
                    [
                        'title' => 'Report and reconcile',
                        'outcome' => 'Issuing and stock activity can be verified for any required period.',
                        'steps' => [
                            'Run the issuing report for the required date range and review student, PACE, and staff details.',
                            'Use inventory reports and the movement ledger to investigate stock differences.',
                            'Export the filtered report when a printable or shareable record is required.',
                        ],
                    ],
                ],
                'boundaries' => [
                    'You cannot register students, prescribe courses, assign PACEs academically, or record tests unless you also hold the Teacher role.',
                    'You can prepare and receive purchase orders, but administrator approval is required before an order is sent.',
                    'A consumable PACE issue is permanent; corrections must preserve the original issuance and stock audit trail.',
                ],
            ],
            RoleName::Accountant->value => [
                'role' => RoleName::Accountant->value,
                'label' => RoleName::Accountant->label(),
                'summary' => 'Set the uniform PACE cost, record student payments, and monitor the credit available for physical PACE issuing.',
                'workflows' => [
                    [
                        'title' => 'Set the PACE cost',
                        'outcome' => 'Every future physical PACE issue uses the current approved price.',
                        'steps' => [
                            'Open PACE accounts and review the current uniform PACE cost.',
                            'Enter the new cost in Uganda shillings and save it before PACE issuing begins.',
                            'Confirm the updated cost; previously issued PACEs retain the price charged at their issue time.',
                        ],
                    ],
                    [
                        'title' => 'Find a student account',
                        'outcome' => 'The correct student and current available balance are identified.',
                        'steps' => [
                            'Filter students by academic year, learning center, grade, balance status, name, or admission number.',
                            'Review whether the balance covers at least one PACE at the current cost.',
                            'Expand the student row to inspect recent payments, issue charges, reversals, references, and running balances.',
                        ],
                    ],
                    [
                        'title' => 'Record a PACE payment',
                        'outcome' => 'The student has additional auditable credit available for PACE issues.',
                        'steps' => [
                            'Enter the amount received and payment date for the selected student.',
                            'Add the receipt or payment reference and an internal note where available.',
                            'Save the payment and confirm the new balance and number of PACEs currently covered.',
                        ],
                    ],
                    [
                        'title' => 'Monitor issuing readiness',
                        'outcome' => 'Students without enough credit are funded before physical handover.',
                        'steps' => [
                            'Use the dashboard and balance filters to find students with zero or insufficient credit.',
                            'Record supported payments before the student reaches the physical issuing desk.',
                            'Use the account ledger to reconcile each payment against automatic PACE issue deductions.',
                        ],
                    ],
                ],
                'boundaries' => [
                    'The PACE account records prepaid PACE credit only; it does not manage tuition invoices, expenses, or full financial statements.',
                    'You cannot register students, assign or issue PACEs, record assessments, adjust inventory, or make promotion decisions.',
                    'Never overwrite a balance; record payments and retain automatic issue charges and reversals in the ledger.',
                ],
            ],
        ];
    }
}
