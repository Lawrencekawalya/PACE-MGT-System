# Graph Report - PMS  (2026-07-22)

## Corpus Check
- 426 files · ~67,048 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1876 nodes · 3289 edges · 162 communities (136 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 60 edges (avg confidence: 0.78)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `498eb677`
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
- DropdownMenuRadioGroup.vue
- DropdownMenuSub.vue
- DropdownMenuSubTrigger.vue
- Input.vue
- autoload-dev
- keywords
- eslint.config.js
- DropdownMenuSeparator.vue
- clsx
- @inertiajs/vite
- @inertiajs/vue3
- tailwindcss
- vue
- vue-sonner
- README.md
- PlaceholderPattern.vue
- vue-shims.d.ts
- cache.php
- Welcome.vue
- web.php
- ProfileController.php
- SheetContent.vue
- UpdateStaffRequest
- Index.vue
- tw-animate-css
- DropdownMenuLabel.vue
- DropdownMenuRadioGroup.vue
- DropdownMenuSub.vue
- DropdownMenuSubTrigger.vue
- eslint-config-prettier
- eslint-import-resolver-typescript
- eslint-plugin-import
- eslint-plugin-vue
- prettier
- prettier-plugin-tailwindcss
- @stylistic/eslint-plugin
- @tailwindcss/vite
- StoreStudentRequest
- tw-animate-css

## God Nodes (most connected - your core abstractions)
1. `cn()` - 93 edges
2. `User` - 79 edges
3. `ActivityLogger` - 50 edges
4. `Course` - 38 edges
5. `Controller` - 35 edges
6. `PaceAssignment` - 34 edges
7. `Level` - 33 edges
8. `Student` - 32 edges
9. `StudentCourse` - 28 edges
10. `SchoolSetting` - 26 edges

## Surprising Connections (you probably didn't know these)
- `enrollmentFixture()` --calls--> `AcademicYear`  [INFERRED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/AcademicYear.php
- `paceAssignmentFixture()` --calls--> `CurriculumRequirement`  [INFERRED]
  tests/Feature/PaceAssignmentTest.php → app/Models/CurriculumRequirement.php
- `paceAssignmentFixture()` --calls--> `Pace`  [INFERRED]
  tests/Feature/PaceAssignmentTest.php → app/Models/Pace.php
- `enrollmentFixture()` --calls--> `Pace`  [INFERRED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/Pace.php
- `createStaffWithRole()` --calls--> `Role`  [INFERRED]
  tests/Pest.php → app/Models/Role.php

## Import Cycles
- 3-file cycle: `resources/js/components/ui/sidebar/SidebarMenuButton.vue -> resources/js/components/ui/sidebar/SidebarMenuButtonChild.vue -> resources/js/components/ui/sidebar/index.ts -> resources/js/components/ui/sidebar/SidebarMenuButton.vue`

## Communities (162 total, 26 thin omitted)

### Community 0 - "lib/utils.ts"
Cohesion: 0.06
Nodes (29): props, SidebarMenuButtonVariants, props, props, props, props, props, props (+21 more)

### Community 1 - "ProfileController.php"
Cohesion: 0.17
Nodes (8): ApplySchoolSettings, EnsureUserIsActive, HandleAppearance, HandleInertiaRequests, Closure, Illuminate\Foundation\Configuration\Middleware, Inertia\Middleware, Symfony\Component\HttpFoundation\Response

### Community 2 - "scripts"
Cohesion: 0.05
Nodes (40): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+32 more)

### Community 3 - "devDependencies"
Cohesion: 0.05
Nodes (39): concurrently, eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-vue, @laravel/vite-plugin-wayfinder (+31 more)

### Community 4 - "select/index.ts"
Cohesion: 0.05
Nodes (25): emits, forwarded, props, delegatedProps, emits, forwarded, props, props (+17 more)

### Community 5 - "dialog/index.ts"
Cohesion: 0.06
Nodes (23): emits, forwarded, props, props, delegatedProps, emits, forwarded, props (+15 more)

### Community 6 - "Sidebar.vue"
Cohesion: 0.10
Nodes (14): emits, forwarded, props, props, delegatedProps, props, props, props (+6 more)

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
Cohesion: 0.11
Nodes (17): AccessOption, passwordInput, Props, { verify, isLoading, error, isSupported }, inputRef, props, showPassword, Props (+9 more)

### Community 11 - "useAppearance.ts"
Cohesion: 0.12
Nodes (16): { appearance, updateAppearance }, tabs, appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), prefersDark() (+8 more)

### Community 12 - "navigation-menu/index.ts"
Cohesion: 0.06
Nodes (27): navigationMenuTriggerStyle, delegatedProps, emits, forwarded, props, delegatedProps, emits, forwarded (+19 more)

### Community 13 - "User.php"
Cohesion: 0.13
Nodes (5): Illuminate\Notifications\Notifiable, Illuminate\Support\Facades\Notification, Laravel\Fortify\Contracts\PasskeyUser, Laravel\Fortify\PasskeyAuthenticatable, Laravel\Fortify\TwoFactorAuthenticatable

### Community 14 - "types/index.ts"
Cohesion: 0.07
Nodes (28): className, Props, auth, { isCurrentUrl, whenCurrentUrl }, mainNavItems, page, Props, rightNavItems (+20 more)

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
Cohesion: 0.12
Nodes (17): class-variance-authority, @laravel/passkeys, laravel-vite-plugin, @lucide/vue, dependencies, class-variance-authority, @laravel/passkeys, laravel-vite-plugin (+9 more)

### Community 19 - "sidebar/index.ts"
Cohesion: 0.21
Nodes (4): ResetUserPassword, PasswordUpdateRequest, ProfileDeleteRequest, Laravel\Fortify\Contracts\ResetsUserPasswords

### Community 20 - "aliases"
Cohesion: 0.12
Nodes (15): aliases, components, composables, lib, ui, utils, iconLibrary, $schema (+7 more)

### Community 21 - "TwoFactorSetupModal.vue"
Cohesion: 0.09
Nodes (10): AcademicPeriodController, TermController, SaveTermRequest, AcademicYear, Term, AcademicPeriodService, PaceAssignmentFactory, StudentEnrollmentFactory (+2 more)

### Community 22 - "breadcrumb/index.ts"
Cohesion: 0.13
Nodes (7): props, props, props, props, props, props, props

### Community 23 - "card/index.ts"
Cohesion: 0.09
Nodes (14): Props, props, props, props, props, props, props, props (+6 more)

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
Cohesion: 0.25
Nodes (4): props, delegatedProps, props, props

### Community 28 - "TwoFactorChallenge.vue"
Cohesion: 0.15
Nodes (8): Props, Props, { hasSetupData, clearTwoFactorAuthData }, Props, showSetupModal, StudentValues, Props, Student

### Community 29 - "TwoFactorRecoveryCodes.vue"
Cohesion: 0.08
Nodes (21): Props, uniqueErrors, isRecoveryCodesVisible, recoveryCodeSectionRef, { recoveryCodesList, fetchRecoveryCodes, errors }, code, { copy, copied }, isOpen (+13 more)

### Community 30 - "UserInfo.vue"
Cohesion: 0.20
Nodes (8): { isMobile, state }, page, user, { getInitials }, Props, showAvatar, Props, User

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

### Community 36 - "Layout.vue"
Cohesion: 0.07
Nodes (13): NotifyStalePaceAssignments, PaceAssignmentStatusController, TransitionPaceAssignmentRequest, PaceAssignment, StalePaceAssignmentNotification, PaceAssignmentService, PaceIssueService, PaceStatusEventFactory (+5 more)

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
Cohesion: 0.18
Nodes (3): AppServiceProvider, FortifyServiceProvider, Illuminate\Support\ServiceProvider

### Community 46 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, build, build:ssr, dev, format, format:check, lint, lint:check (+1 more)

### Community 47 - "Collapsible.vue"
Cohesion: 0.22
Nodes (5): emits, forwarded, props, props, props

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
Cohesion: 0.18
Nodes (4): SaveCurriculumRequirementRequest, SavePaceRequest, UpdateStudentRequest, Illuminate\Foundation\Http\FormRequest

### Community 55 - "composer.json"
Cohesion: 0.25
Nodes (7): description, license, minimum-stability, name, prefer-stable, $schema, type

### Community 56 - "require"
Cohesion: 0.22
Nodes (9): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php (+1 more)

### Community 57 - "PasskeyRegister.vue"
Cohesion: 0.10
Nodes (13): emit, handleSubmit(), name, { register, isLoading, error, isSupported }, showForm, Assignment, Event, confirmStatus() (+5 more)

### Community 58 - "Alert.vue"
Cohesion: 0.29
Nodes (4): props, props, props, AlertVariants

### Community 60 - "dropdown-menu/index.ts"
Cohesion: 0.20
Nodes (6): props, emits, forwarded, props, forwardedProps, props

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
Cohesion: 0.18
Nodes (8): Assignment, courseId, dateFrom, dateTo, exceptions, props, search, status

### Community 68 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 70 - "AppSidebar.vue"
Cohesion: 0.16
Nodes (5): StaffController, Permission, Role, AccessControlSeeder, Illuminate\Database\Eloquent\Relations\BelongsToMany

### Community 71 - "SidebarMenuSkeleton.vue"
Cohesion: 0.29
Nodes (4): props, width, props, SkeletonProps

### Community 72 - "Configuration Best Practices"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 73 - "laravel-best-practices/SKILL.md"
Cohesion: 0.07
Nodes (13): ActivityLog, CatalogueImportRow, PaceStatusEvent, StudentCourse, StudentEnrollment, StudentCourseFactory, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model (+5 more)

### Community 74 - "FortifyServiceProvider"
Cohesion: 0.18
Nodes (3): SchoolSetting, SchoolSettingPolicy, self

### Community 75 - "TestCase"
Cohesion: 0.29
Nodes (3): Illuminate\Foundation\Testing\TestCase, createStaffWithRole(), TestCase

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
Cohesion: 0.25
Nodes (5): DatabaseSeeder, PaceCatalogueSeeder, SchoolSettingSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 81 - "Badge.vue"
Cohesion: 0.09
Nodes (17): delegatedProps, props, BadgeVariants, Import, CatalogueImport, ImportRow, courseId, levelId (+9 more)

### Community 83 - "DropdownMenuContent.vue"
Cohesion: 0.27
Nodes (6): CreateNewUser, emailRules(), nameRules(), profileRules(), ProfileUpdateRequest, Laravel\Fortify\Contracts\CreatesNewUsers

### Community 84 - "DropdownMenuRadioItem.vue"
Cohesion: 0.05
Nodes (17): LevelController, CatalogueImport, Course, Level, Subject, AcademicYearFactory, CatalogueImportFactory, CatalogueImportRowFactory (+9 more)

### Community 85 - "DropdownMenuSubContent.vue"
Cohesion: 0.25
Nodes (7): availablePaces, Course, Level, Pace, props, Requirement, selectedCourse

### Community 86 - "NavigationMenuLink.vue"
Cohesion: 0.14
Nodes (4): User, StudentPolicy, UserPolicy, Illuminate\Foundation\Auth\User

### Community 87 - "package.json"
Cohesion: 0.50
Nodes (3): private, $schema, type

### Community 88 - "button/index.ts"
Cohesion: 0.11
Nodes (6): StudentController, StudentEnrollmentController, SaveStudentEnrollmentRequest, Student, StudentEnrollmentService, StudentRegistrationService

### Community 90 - "DropdownMenuItem.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

### Community 92 - "DropdownMenuRadioGroup.vue"
Cohesion: 0.33
Nodes (3): SecurityController, TwoFactorAuthenticationRequest, Laravel\Fortify\InteractsWithTwoFactorState

### Community 94 - "DropdownMenuSubTrigger.vue"
Cohesion: 0.16
Nodes (9): Props, ButtonVariants, Course, Level, Subject, AccessOption, page, props (+1 more)

### Community 95 - "Input.vue"
Cohesion: 0.50
Nodes (3): emits, modelValue, props

### Community 96 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 97 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 104 - "DropdownMenuSeparator.vue"
Cohesion: 0.40
Nodes (4): delegatedProps, emits, forwarded, props

### Community 106 - "clsx"
Cohesion: 0.40
Nodes (4): delegatedProps, emits, forwarded, props

### Community 107 - "@inertiajs/vite"
Cohesion: 0.40
Nodes (4): delegatedProps, emits, forwarded, props

### Community 108 - "@inertiajs/vue3"
Cohesion: 0.40
Nodes (4): delegatedProps, emits, forwarded, props

### Community 109 - "tailwindcss"
Cohesion: 0.18
Nodes (8): Enrollment, levelId, Option, props, search, status, Student, yearId

### Community 112 - "vue-sonner"
Cohesion: 0.25
Nodes (3): CurriculumController, CurriculumRequirement, enrollmentFixture()

### Community 117 - "cache.php"
Cohesion: 0.60
Nodes (4): getInitial(), getInitials(), useInitials(), UseInitialsReturn

### Community 128 - "web.php"
Cohesion: 0.09
Nodes (15): CatalogueImportController, CatalogueSetupController, CourseController, SchoolSettingController, StaffPasswordController, SubjectController, Controller, DashboardController (+7 more)

### Community 134 - "ProfileController.php"
Cohesion: 0.33
Nodes (3): delegatedProps, props, props

### Community 135 - "SheetContent.vue"
Cohesion: 0.22
Nodes (7): delegatedProps, emits, forwarded, props, SheetContentProps, delegatedProps, props

### Community 136 - "UpdateStaffRequest"
Cohesion: 0.40
Nodes (3): props, search, StaffMember

### Community 142 - "tw-animate-css"
Cohesion: 0.50
Nodes (3): emits, forwarded, props

### Community 144 - "DropdownMenuLabel.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

### Community 145 - "DropdownMenuRadioGroup.vue"
Cohesion: 0.50
Nodes (3): emits, forwarded, props

### Community 147 - "DropdownMenuSubTrigger.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

## Knowledge Gaps
- **786 isolated node(s):** `$schema`, `style`, `config`, `css`, `baseColor` (+781 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `cn()` connect `lib/utils.ts` to `select/index.ts`, `dialog/index.ts`, `ProfileController.php`, `SheetContent.vue`, `Sidebar.vue`, `InputError.vue`, `navigation-menu/index.ts`, `types/index.ts`, `InputOTPSlot.vue`, `DropdownMenuLabel.vue`, `DropdownMenuSubTrigger.vue`, `breadcrumb/index.ts`, `card/index.ts`, `TooltipContent.vue`, `AppHeader.vue`, `SidebarProvider.vue`, `Alert.vue`, `SidebarMenuSkeleton.vue`, `Checkbox.vue`, `Badge.vue`, `DropdownMenuItem.vue`, `DropdownMenuSubTrigger.vue`, `Input.vue`, `DropdownMenuSeparator.vue`, `clsx`, `@inertiajs/vite`, `@inertiajs/vue3`?**
  _High betweenness centrality (0.066) - this node is a cross-community bridge._
- **Why does `User` connect `NavigationMenuLink.vue` to `web.php`, `Layout.vue`, `AppSidebar.vue`, `laravel-best-practices/SKILL.md`, `FortifyServiceProvider`, `TestCase`, `User.php`, `FortifyServiceProvider.php`, `vue`, `DatabaseSeeder.php`, `DropdownMenuCheckboxItem.vue`, `sidebar/index.ts`, `DropdownMenuContent.vue`, `DropdownMenuRadioItem.vue`, `TwoFactorSetupModal.vue`, `button/index.ts`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **Why does `Course` connect `DropdownMenuRadioItem.vue` to `web.php`, `laravel-best-practices/SKILL.md`, `vue-sonner`, `TwoFactorSetupModal.vue`, `button/index.ts`, `DropdownMenu.vue`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Are the 7 inferred relationships involving `User` (e.g. with `.handle()` and `.__invoke()`) actually correct?**
  _`User` has 7 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `style`, `config` to the rest of the system?**
  _786 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `lib/utils.ts` be split into smaller, more focused modules?**
  _Cohesion score 0.05731523378582202 - nodes in this community are weakly interconnected._
- **Should `scripts` be split into smaller, more focused modules?**
  _Cohesion score 0.052564102564102565 - nodes in this community are weakly interconnected._