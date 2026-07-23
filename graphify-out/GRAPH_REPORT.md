# Graph Report - PMS  (2026-07-23)

## Corpus Check
- 532 files · ~93,095 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2411 nodes · 4764 edges · 165 communities (147 shown, 18 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 149 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `8f420643`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- lib/utils.ts
- ProfileController.php
- scripts
- devDependencies
- select/index.ts
- dialog/index.ts
- Sidebar.vue
- AGENTS.md
- compilerOptions
- Inertia v3 Features
- InputError.vue
- useAppearance.ts
- navigation-menu/index.ts
- User.php
- types/index.ts
- InputOTPSlot.vue
- Pest Testing 4
- Laravel Fortify Development
- dependencies
- sidebar/index.ts
- aliases
- TwoFactorSetupModal.vue
- breadcrumb/index.ts
- card/index.ts
- Tailwind CSS Development
- TooltipContent.vue
- optionalDependencies
- AppHeader.vue
- TwoFactorChallenge.vue
- TwoFactorRecoveryCodes.vue
- UserInfo.vue
- Architecture Best Practices
- Security Best Practices
- require-dev
- ManageTwoFactor.vue
- Queue & Job Best Practices
- Layout.vue
- Advanced Query Patterns
- Database Performance Best Practices
- Events & Notifications Best Practices
- Wayfinder Development
- SidebarProvider.vue
- Caching Best Practices
- Eloquent Best Practices
- Migration Best Practices
- FortifyServiceProvider.php
- scripts
- Collapsible.vue
- NavigationMenu.vue
- global.d.ts
- Blade & Views Best Practices
- Error Handling Best Practices
- Task Scheduling Best Practices
- Testing Best Practices
- ProfileValidationRules.php
- composer.json
- require
- PasskeyRegister.vue
- Alert.vue
- AvatarFallback.vue
- dropdown-menu/index.ts
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing & Controllers Best Practices
- Conventions & Style
- Validation & Forms Best Practices
- PasswordValidationRules.php
- config
- UserFactory
- AppSidebar.vue
- SidebarMenuSkeleton.vue
- Configuration Best Practices
- laravel-best-practices/SKILL.md
- FortifyServiceProvider
- TestCase
- Checkbox.vue
- Separator.vue
- psr-4
- laravel
- DatabaseSeeder.php
- Badge.vue
- DropdownMenuCheckboxItem.vue
- DropdownMenuContent.vue
- DropdownMenuRadioItem.vue
- DropdownMenuSubContent.vue
- NavigationMenuLink.vue
- package.json
- button/index.ts
- DropdownMenu.vue
- DropdownMenuItem.vue
- DropdownMenuLabel.vue
- DropdownMenuSub.vue
- DropdownMenuSubTrigger.vue
- Input.vue
- autoload-dev
- keywords
- eslint.config.js
- DropdownMenuSeparator.vue
- artisan
- clsx
- @inertiajs/vite
- @inertiajs/vue3
- tailwindcss
- SidebarMenuButton.vue
- Separator.vue
- README.md
- PlaceholderPattern.vue
- vue-shims.d.ts
- test
- queue.php
- concurrently
- web.php
- eslint-plugin-import
- UpdateStaffRequest
- tw-animate-css
- DropdownMenuRadioGroup.vue
- eslint-plugin-import
- prettier
- @stylistic/eslint-plugin
- @stylistic/eslint-plugin
- vite
- Illuminate\Console\Command
- DashboardReportService

## God Nodes (most connected - your core abstractions)
1. `User` - 161 edges
2. `cn()` - 93 edges
3. `ActivityLogger` - 68 edges
4. `PaceAssignment` - 60 edges
5. `Course` - 50 edges
6. `Controller` - 49 edges
7. `StudentCourse` - 43 edges
8. `Student` - 42 edges
9. `Level` - 39 edges
10. `Pace` - 39 edges

## Surprising Connections (you probably didn't know these)
- `inventoryAssignmentFixture()` --calls--> `InventoryItem`  [INFERRED]
  tests/Feature/InventoryTest.php → app/Models/InventoryItem.php
- `createReportFixture()` --calls--> `PaceAssignment`  [INFERRED]
  tests/Pest.php → app/Models/PaceAssignment.php
- `createStaffWithRole()` --calls--> `Role`  [INFERRED]
  tests/Pest.php → app/Models/Role.php
- `assessmentFixture()` --calls--> `Student`  [INFERRED]
  tests/Feature/PaceAssessmentTest.php → app/Models/Student.php
- `paceAssignmentFixture()` --calls--> `Student`  [INFERRED]
  tests/Feature/PaceAssignmentTest.php → app/Models/Student.php

## Import Cycles
- 3-file cycle: `resources/js/components/ui/sidebar/SidebarMenuButton.vue -> resources/js/components/ui/sidebar/SidebarMenuButtonChild.vue -> resources/js/components/ui/sidebar/index.ts -> resources/js/components/ui/sidebar/SidebarMenuButton.vue`

## Communities (165 total, 18 thin omitted)

### Community 0 - "lib/utils.ts"
Cohesion: 0.13
Nodes (3): Student, StudentPolicy, StudentRegistrationService

### Community 1 - "ProfileController.php"
Cohesion: 0.06
Nodes (10): CatalogueImportRow, PaceAssignment, PaceAttemptCorrection, PaceStatusEvent, StudentCourse, StudentEnrollment, CatalogueImportRowFactory, StudentCourseFactory (+2 more)

### Community 2 - "scripts"
Cohesion: 0.11
Nodes (19): scripts, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-update-cmd, pre-package-uninstall, types:check (+11 more)

### Community 3 - "devDependencies"
Cohesion: 0.06
Nodes (31): concurrently, eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-vue, devDependencies (+23 more)

### Community 4 - "select/index.ts"
Cohesion: 0.05
Nodes (25): emits, forwarded, props, delegatedProps, emits, forwarded, props, props (+17 more)

### Community 5 - "dialog/index.ts"
Cohesion: 0.06
Nodes (23): emits, forwarded, props, props, delegatedProps, emits, forwarded, props (+15 more)

### Community 6 - "Sidebar.vue"
Cohesion: 0.08
Nodes (17): emits, forwarded, props, props, delegatedProps, emits, forwarded, props (+9 more)

### Community 7 - "AGENTS.md"
Cohesion: 0.06
Nodes (30): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+22 more)

### Community 8 - "compilerOptions"
Cohesion: 0.07
Nodes (28): DOM, DOM.Iterable, ESNext, resources/js/**/*.d.ts, resources/js/**/*.ts, resources/js/**/*.tsx, resources/js/**/*.vue, vite/client (+20 more)

### Community 9 - "Inertia v3 Features"
Cohesion: 0.07
Nodes (27): Basic Link Component, Basic Usage, Client-Side Navigation, Common Pitfalls, Deferred Props, Documentation, Form Component (Recommended), Form Component Reset Props (+19 more)

### Community 10 - "InputError.vue"
Cohesion: 0.09
Nodes (22): AccessOption, passwordInput, Props, { verify, isLoading, error, isSupported }, inputRef, props, showPassword, StudentValues (+14 more)

### Community 11 - "useAppearance.ts"
Cohesion: 0.12
Nodes (16): { appearance, updateAppearance }, tabs, appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), prefersDark() (+8 more)

### Community 12 - "navigation-menu/index.ts"
Cohesion: 0.06
Nodes (27): navigationMenuTriggerStyle, delegatedProps, emits, forwarded, props, delegatedProps, emits, forwarded (+19 more)

### Community 14 - "types/index.ts"
Cohesion: 0.07
Nodes (29): className, Props, auth, { isCurrentUrl, whenCurrentUrl }, mainNavItems, page, Props, rightNavItems (+21 more)

### Community 15 - "InputOTPSlot.vue"
Cohesion: 0.11
Nodes (14): delegatedProps, emits, forwarded, props, delegatedProps, forwarded, props, forwarded (+6 more)

### Community 16 - "Pest Testing 4"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 17 - "Laravel Fortify Development"
Cohesion: 0.12
Nodes (16): Available Features, Best Practices, Custom Authentication Logic, Documentation, Email Verification Setup, Key Endpoints, Laravel Fortify Development, Passkeys Setup (+8 more)

### Community 18 - "dependencies"
Cohesion: 0.06
Nodes (31): class-variance-authority, clsx, @inertiajs/vite, @inertiajs/vue3, @laravel/passkeys, laravel-vite-plugin, @lucide/vue, dependencies (+23 more)

### Community 19 - "sidebar/index.ts"
Cohesion: 0.17
Nodes (5): PaceRetryApproval, PaceRetryApprovalRequestedNotification, StalePaceAssignmentNotification, Illuminate\Bus\Queueable, Illuminate\Notifications\Notification

### Community 20 - "aliases"
Cohesion: 0.12
Nodes (15): aliases, components, composables, lib, ui, utils, iconLibrary, $schema (+7 more)

### Community 21 - "TwoFactorSetupModal.vue"
Cohesion: 0.11
Nodes (3): StockMovement, StockMovementPolicy, StockMovementType

### Community 22 - "breadcrumb/index.ts"
Cohesion: 0.15
Nodes (3): NotifyStalePaceAssignments, PaceStatusEventFactory, Illuminate\Database\Eloquent\Model

### Community 23 - "card/index.ts"
Cohesion: 0.05
Nodes (41): props, props, props, props, props, props, props, props (+33 more)

### Community 24 - "Tailwind CSS Development"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 25 - "TooltipContent.vue"
Cohesion: 0.14
Nodes (9): emits, forwarded, props, delegatedProps, emits, forwarded, props, props (+1 more)

### Community 26 - "optionalDependencies"
Cohesion: 0.15
Nodes (13): lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, optionalDependencies, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu (+5 more)

### Community 27 - "AppHeader.vue"
Cohesion: 0.06
Nodes (18): AcademicYear, Course, CurriculumRequirement, Level, Pace, Subject, Term, CourseFactory (+10 more)

### Community 28 - "TwoFactorChallenge.vue"
Cohesion: 0.25
Nodes (7): Academic, academicMetrics, Inventory, inventoryMetrics, page, props, setupItems

### Community 29 - "TwoFactorRecoveryCodes.vue"
Cohesion: 0.07
Nodes (24): Props, uniqueErrors, { hasSetupData, clearTwoFactorAuthData }, Props, showSetupModal, isRecoveryCodesVisible, recoveryCodeSectionRef, { recoveryCodesList, fetchRecoveryCodes, errors } (+16 more)

### Community 30 - "UserInfo.vue"
Cohesion: 0.10
Nodes (16): { isMobile, state }, page, user, props, delegatedProps, props, props, { getInitials } (+8 more)

### Community 31 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 32 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 33 - "require-dev"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 35 - "Queue & Job Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 37 - "Advanced Query Patterns"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 38 - "Database Performance Best Practices"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 39 - "Events & Notifications Best Practices"
Cohesion: 0.20
Nodes (9): Always Queue Notifications, Events & Notifications Best Practices, Implement `HasLocalePreference` on Notifiable Models, Rely on Event Discovery, Route Notification Channels to Dedicated Queues, Run `event:cache` in Production Deploy, Use `afterCommit()` on Notifications in Transactions, Use On-Demand Notifications for Non-User Recipients (+1 more)

### Community 40 - "Wayfinder Development"
Cohesion: 0.20
Nodes (9): Common Methods, Common Pitfalls, Documentation, Generate Routes, Import Patterns, Quick Reference, Verification, Wayfinder Development (+1 more)

### Community 41 - "SidebarProvider.vue"
Cohesion: 0.24
Nodes (9): emits, isMobile, open, openMobile, props, setOpen(), setOpenMobile(), state (+1 more)

### Community 42 - "Caching Best Practices"
Cohesion: 0.22
Nodes (8): Caching Best Practices, Configure Failover Cache Stores in Production, Use `Cache::add()` for Atomic Conditional Writes, Use `Cache::flexible()` for Stale-While-Revalidate, Use `Cache::memo()` to Avoid Redundant Hits Within a Request, Use `Cache::remember()` Instead of Manual Get/Put, Use Cache Tags to Invalidate Related Groups, Use `once()` for Per-Request Memoization

### Community 43 - "Eloquent Best Practices"
Cohesion: 0.22
Nodes (8): Apply Global Scopes Sparingly, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Define Attribute Casts, Eloquent Best Practices, Use Correct Relationship Types, Use Local Scopes for Reusable Queries, Use `whereBelongsTo()` for Relationship Queries

### Community 44 - "Migration Best Practices"
Cohesion: 0.22
Nodes (8): Add Indexes in the Migration, Generate Migrations with Artisan, Keep Migrations Focused, Migration Best Practices, Mirror Defaults in Model `$attributes`, Never Modify Deployed Migrations, Use `constrained()` for Foreign Keys, Write Reversible `down()` Methods by Default

### Community 45 - "FortifyServiceProvider.php"
Cohesion: 0.08
Nodes (12): CreateNewUser, ResetUserPassword, emailRules(), nameRules(), profileRules(), ProfileDeleteRequest, ProfileUpdateRequest, AppServiceProvider (+4 more)

### Community 46 - "scripts"
Cohesion: 0.15
Nodes (12): private, $schema, scripts, build, build:ssr, dev, format, format:check (+4 more)

### Community 47 - "Collapsible.vue"
Cohesion: 0.22
Nodes (5): emits, forwarded, props, props, props

### Community 48 - "NavigationMenu.vue"
Cohesion: 0.13
Nodes (5): AcademicPeriodController, TermController, SaveAcademicYearRequest, SaveTermRequest, AcademicPeriodService

### Community 49 - "global.d.ts"
Cohesion: 0.11
Nodes (17): emit, handleDelete(), isDeleting, props, authConfigContent, code, showRecoveryInput, Auth (+9 more)

### Community 50 - "Blade & Views Best Practices"
Cohesion: 0.25
Nodes (7): Blade & Views Best Practices, Prefer Blade Components Over `@include`, Use `$attributes->merge()` in Component Templates, Use `@aware` for Deeply Nested Component Props, Use Blade Fragments for Partial Re-Renders (htmx/Turbo), Use `@pushOnce` for Per-Component Scripts, Use View Composers for Shared View Data

### Community 51 - "Error Handling Best Practices"
Cohesion: 0.25
Nodes (7): Add Context to Exception Classes, Enable `dontReportDuplicates()`, Error Handling Best Practices, Exception Reporting and Rendering, Force JSON Error Rendering for API Routes, Throttle High-Volume Exceptions, Use `ShouldntReport` for Exceptions That Should Never Log

### Community 52 - "Task Scheduling Best Practices"
Cohesion: 0.25
Nodes (7): Task Scheduling Best Practices, Use `environments()` to Restrict Tasks, Use `onOneServer()` on Multi-Server Deployments, Use `runInBackground()` for Concurrent Long Tasks, Use Schedule Groups for Shared Configuration, Use `takeUntilTimeout()` for Time-Bounded Processing, Use `withoutOverlapping()` on Variable-Duration Tasks

### Community 53 - "Testing Best Practices"
Cohesion: 0.25
Nodes (7): Call `Event::fake()` After Factory Setup, Testing Best Practices, Use `Exceptions::fake()` to Assert Exception Reporting, Use Factory States and Sequences, Use `LazilyRefreshDatabase` Over `RefreshDatabase`, Use Model Assertions Over Raw Database Assertions, Use `recycle()` to Share Relationship Instances Across Factories

### Community 54 - "ProfileValidationRules.php"
Cohesion: 0.11
Nodes (6): CatalogueImportController, LevelController, SaveLevelRequest, StoreCatalogueImportRequest, CatalogueImport, Illuminate\Http\RedirectResponse

### Community 55 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 56 - "require"
Cohesion: 0.22
Nodes (9): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php (+1 more)

### Community 57 - "PasskeyRegister.vue"
Cohesion: 0.08
Nodes (18): emit, handleSubmit(), name, { register, isLoading, error, isSupported }, showForm, confirmDecision(), confirmCorrection(), confirmCorrection() (+10 more)

### Community 58 - "Alert.vue"
Cohesion: 0.40
Nodes (3): props, props, AlertVariants

### Community 59 - "AvatarFallback.vue"
Cohesion: 0.07
Nodes (14): CreateAdministrator, StaffController, Permission, Role, CatalogueImportService, AccessControlSeeder, DatabaseSeeder, InventoryItemSeeder (+6 more)

### Community 60 - "dropdown-menu/index.ts"
Cohesion: 0.04
Nodes (40): emits, forwarded, props, delegatedProps, emits, forwarded, props, delegatedProps (+32 more)

### Community 61 - "Collection Best Practices"
Cohesion: 0.29
Nodes (6): Choose `cursor()` vs. `lazy()` Correctly, Collection Best Practices, Use `#[CollectedBy]` for Custom Collection Classes, Use Higher-Order Messages for Simple Operations, Use `lazyById()` When Updating Records While Iterating, Use `toQuery()` for Bulk Operations on Collections

### Community 62 - "HTTP Client Best Practices"
Cohesion: 0.29
Nodes (6): Always Set Explicit Timeouts, Fake HTTP Calls in Tests, Handle Errors Explicitly, HTTP Client Best Practices, Use Request Pooling for Concurrent Requests, Use Retry with Backoff for External APIs

### Community 63 - "Mail Best Practices"
Cohesion: 0.29
Nodes (6): Implement `ShouldQueue` on the Mailable Class, Mail Best Practices, Separate Content Tests from Sending Tests, Use `afterCommit()` on Mailables Inside Transactions, Use `assertQueued()` Not `assertSent()` for Queued Mailables, Use Markdown Mailables for Transactional Emails

### Community 64 - "Routing & Controllers Best Practices"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 65 - "Conventions & Style"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 66 - "Validation & Forms Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 67 - "PasswordValidationRules.php"
Cohesion: 0.13
Nodes (7): props, props, props, props, props, props, props

### Community 68 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 69 - "UserFactory"
Cohesion: 0.20
Nodes (8): courseId, levelId, Option, Pace, props, search, status, subjectId

### Community 70 - "AppSidebar.vue"
Cohesion: 0.07
Nodes (10): SecurityController, ResetStaffPasswordRequest, StoreStaffRequest, UpdateStaffRequest, PasswordUpdateRequest, TwoFactorAuthenticationRequest, StoreStudentRequest, UpdateStudentRequest (+2 more)

### Community 71 - "SidebarMenuSkeleton.vue"
Cohesion: 0.29
Nodes (4): props, width, props, SkeletonProps

### Community 72 - "Configuration Best Practices"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 74 - "FortifyServiceProvider"
Cohesion: 0.18
Nodes (3): SchoolSetting, SchoolSettingPolicy, self

### Community 76 - "Checkbox.vue"
Cohesion: 0.33
Nodes (4): delegatedProps, emits, forwarded, props

### Community 77 - "Separator.vue"
Cohesion: 0.08
Nodes (25): AcademicYear, activeYear, applyCurriculum(), Course, coursePaces(), Enrollment, formDefinition, isOverride (+17 more)

### Community 78 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 79 - "laravel"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 80 - "DatabaseSeeder.php"
Cohesion: 0.08
Nodes (7): ActivityLog, Illuminate\Database\Eloquent\Relations\MorphTo, Illuminate\Notifications\Notifiable, Illuminate\Support\Facades\Notification, Laravel\Fortify\Contracts\PasskeyUser, Laravel\Fortify\PasskeyAuthenticatable, Laravel\Fortify\TwoFactorAuthenticatable

### Community 81 - "Badge.vue"
Cohesion: 0.13
Nodes (9): Props, Props, Course, Level, Subject, Props, Teacher, Student (+1 more)

### Community 82 - "DropdownMenuCheckboxItem.vue"
Cohesion: 0.09
Nodes (11): PaceAttemptController, PaceAttemptCorrectionController, PaceRetryApprovalController, DecidePaceRetryApprovalRequest, StorePaceAttemptCorrectionRequest, StorePaceAttemptRequest, StorePaceRetryApprovalRequest, PaceAssessmentService (+3 more)

### Community 83 - "DropdownMenuContent.vue"
Cohesion: 0.22
Nodes (3): StudentEnrollmentController, SaveStudentEnrollmentRequest, StudentEnrollmentService

### Community 84 - "DropdownMenuRadioItem.vue"
Cohesion: 0.08
Nodes (12): AcademicYearFactory, LevelFactory, PaceAttemptCorrectionFactory, PaceAttemptFactory, PaceFactory, PaceRetryApprovalFactory, SchoolSettingFactory, StockMovementFactory (+4 more)

### Community 85 - "DropdownMenuSubContent.vue"
Cohesion: 0.25
Nodes (7): availablePaces, Course, Level, Pace, props, Requirement, selectedCourse

### Community 86 - "NavigationMenuLink.vue"
Cohesion: 0.12
Nodes (5): User, PaceAssignmentPolicy, UserPolicy, static, Illuminate\Foundation\Auth\User

### Community 88 - "button/index.ts"
Cohesion: 0.16
Nodes (9): AddSecurityHeaders, ApplySchoolSettings, EnsureUserIsActive, HandleAppearance, HandleInertiaRequests, Closure, Illuminate\Foundation\Configuration\Middleware, Inertia\Middleware (+1 more)

### Community 89 - "DropdownMenu.vue"
Cohesion: 0.20
Nodes (8): APP_ROOT, CURRENT, HEALTH_URL, KEEP_RELEASES, RELEASES, REPOSITORY, production.sh script, SHARED

### Community 90 - "DropdownMenuItem.vue"
Cohesion: 0.21
Nodes (9): Assignment, Attempt, attemptsFor(), Correction, hasApprovedRetry(), hasOpenApproval(), latestAttempt(), props (+1 more)

### Community 93 - "DropdownMenuSub.vue"
Cohesion: 0.13
Nodes (8): CatalogueSetupController, CurriculumController, StaffPasswordController, SystemStatusController, Controller, ReportExportDownloadController, SaveCurriculumRequirementRequest, Symfony\Component\HttpFoundation\StreamedResponse

### Community 94 - "DropdownMenuSubTrigger.vue"
Cohesion: 0.17
Nodes (13): activeIndex, clear(), emit, filteredOptions, handleKeydown(), open, Option, props (+5 more)

### Community 95 - "Input.vue"
Cohesion: 0.16
Nodes (9): applyFilters(), currentLabel, Export, Filters, props, query(), Row, selectedYear (+1 more)

### Community 96 - "autoload-dev"
Cohesion: 0.25
Nodes (5): Approval, Assignment, props, search, Student

### Community 97 - "keywords"
Cohesion: 0.11
Nodes (11): PaceAssignmentStatusController, StockMovementController, StockMovementCorrectionController, StudentStatusController, CorrectStockMovementRequest, StoreStockMovementRequest, TransitionPaceAssignmentRequest, UpdateStudentStatusRequest (+3 more)

### Community 104 - "DropdownMenuSeparator.vue"
Cohesion: 0.17
Nodes (7): active, Item, itemType, PaceOption, props, search, stock

### Community 105 - "artisan"
Cohesion: 0.29
Nodes (4): CatalogueImportFactory, Illuminate\Http\UploadedFile, paceWorkbook(), UploadedFile

### Community 107 - "@inertiajs/vite"
Cohesion: 0.18
Nodes (8): Assignment, courseId, dateFrom, dateTo, exceptions, props, search, status

### Community 108 - "@inertiajs/vue3"
Cohesion: 0.09
Nodes (14): delegatedProps, props, BadgeVariants, Import, CatalogueImport, ImportRow, canManage, Pace (+6 more)

### Community 109 - "tailwindcss"
Cohesion: 0.18
Nodes (8): Enrollment, levelId, Option, props, search, status, Student, yearId

### Community 111 - "SidebarMenuButton.vue"
Cohesion: 0.32
Nodes (6): SidebarMenuButtonVariants, delegatedProps, { isMobile, state }, props, props, SidebarMenuButtonProps

### Community 112 - "Separator.vue"
Cohesion: 0.33
Nodes (3): delegatedProps, props, props

### Community 113 - "README.md"
Cohesion: 0.07
Nodes (24): Backup and Recovery, Incident recovery, Manual backup, Policy, Restore drill, Deployment and Rollback, First deployment, Platform prerequisites (+16 more)

### Community 117 - "test"
Cohesion: 0.40
Nodes (5): test, @lint:check, @php artisan config:clear --ansi, @php artisan test, @types:check

### Community 122 - "queue.php"
Cohesion: 0.07
Nodes (14): ReportController, ReportExportController, StoreReportExportRequest, GenerateReportExport, QueueHeartbeat, ReportDataService, ReportExportGenerator, ReportExportFactory (+6 more)

### Community 128 - "web.php"
Cohesion: 0.10
Nodes (9): AssessmentController, DashboardController, InventoryController, PaceAssignmentController, ProfileController, StudentController, StorePaceAssignmentRequest, Illuminate\Http\Request (+1 more)

### Community 136 - "UpdateStaffRequest"
Cohesion: 0.25
Nodes (8): ci:check, dev, Composer\\Config::disableProcessTimeout, npm run format:check, npm run lint:check, npm run types:check, @php artisan dev, @test

### Community 142 - "tw-animate-css"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 145 - "DropdownMenuRadioGroup.vue"
Cohesion: 0.25
Nodes (6): dateFrom, dateTo, Movement, props, search, type

### Community 151 - "eslint-plugin-import"
Cohesion: 0.20
Nodes (3): InventoryItemController, StoreInventoryItemRequest, UpdateInventoryItemRequest

### Community 175 - "Illuminate\Console\Command"
Cohesion: 0.06
Nodes (14): BackupDatabase, PruneOperationalData, PruneReportExports, ReconcileCatalogue, RestoreDatabase, SystemCheck, SystemHeartbeat, ValidateSystemData (+6 more)

## Knowledge Gaps
- **860 isolated node(s):** `$schema`, `style`, `config`, `css`, `baseColor` (+855 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **18 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `cn()` connect `card/index.ts` to `PasswordValidationRules.php`, `select/index.ts`, `dialog/index.ts`, `Sidebar.vue`, `SidebarMenuSkeleton.vue`, `SidebarProvider.vue`, `InputError.vue`, `@inertiajs/vue3`, `Checkbox.vue`, `navigation-menu/index.ts`, `InputOTPSlot.vue`, `Separator.vue`, `SidebarMenuButton.vue`, `TooltipContent.vue`, `Alert.vue`, `dropdown-menu/index.ts`, `UserInfo.vue`?**
  _High betweenness centrality (0.067) - this node is a cross-community bridge._
- **Why does `User` connect `NavigationMenuLink.vue` to `web.php`, `ProfileController.php`, `lib/utils.ts`, `TwoFactorSetupModal.vue`, `breadcrumb/index.ts`, `AppHeader.vue`, `@stylistic/eslint-plugin`, `ManageTwoFactor.vue`, `Layout.vue`, `FortifyServiceProvider.php`, `DashboardReportService`, `ProfileValidationRules.php`, `AvatarFallback.vue`, `AppSidebar.vue`, `laravel-best-practices/SKILL.md`, `FortifyServiceProvider`, `DatabaseSeeder.php`, `DropdownMenuCheckboxItem.vue`, `DropdownMenuRadioItem.vue`, `package.json`, `DropdownMenuRadioGroup.vue`, `DropdownMenuSub.vue`, `queue.php`?**
  _High betweenness centrality (0.055) - this node is a cross-community bridge._
- **Why does `ActivityLogger` connect `keywords` to `web.php`, `lib/utils.ts`, `AppSidebar.vue`, `DropdownMenuLabel.vue`, `User.php`, `NavigationMenu.vue`, `PlaceholderPattern.vue`, `DropdownMenuCheckboxItem.vue`, `DropdownMenuContent.vue`, `ProfileValidationRules.php`, `eslint-plugin-import`, `prettier`, `AvatarFallback.vue`, `DropdownMenuSub.vue`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Are the 16 inferred relationships involving `User` (e.g. with `.handle()` and `.handle()`) actually correct?**
  _`User` has 16 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `style`, `config` to the rest of the system?**
  _860 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `lib/utils.ts` be split into smaller, more focused modules?**
  _Cohesion score 0.13157894736842105 - nodes in this community are weakly interconnected._
- **Should `ProfileController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.06196078431372549 - nodes in this community are weakly interconnected._