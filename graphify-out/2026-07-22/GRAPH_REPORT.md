# Graph Report - PMS  (2026-07-22)

## Corpus Check
- 405 files · ~62,096 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1767 nodes · 2969 edges · 157 communities (140 shown, 17 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 39 edges (avg confidence: 0.76)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `26ffd517`
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

## God Nodes (most connected - your core abstractions)
1. `cn()` - 93 edges
2. `User` - 61 edges
3. `ActivityLogger` - 44 edges
4. `Course` - 34 edges
5. `Controller` - 33 edges
6. `Level` - 31 edges
7. `Student` - 31 edges
8. `SchoolSetting` - 26 edges
9. `AcademicYear` - 21 edges
10. `Pace` - 20 edges

## Surprising Connections (you probably didn't know these)
- `enrollmentFixture()` --calls--> `AcademicYear`  [INFERRED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/AcademicYear.php
- `enrollmentFixture()` --calls--> `CurriculumRequirement`  [INFERRED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/CurriculumRequirement.php
- `createStaffWithRole()` --calls--> `Role`  [INFERRED]
  tests/Pest.php → app/Models/Role.php
- `enrollmentFixture()` --calls--> `Course`  [EXTRACTED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/Course.php
- `enrollmentFixture()` --calls--> `Level`  [EXTRACTED]
  tests/Feature/StudentEnrollmentTest.php → app/Models/Level.php

## Import Cycles
- 3-file cycle: `resources/js/components/ui/sidebar/SidebarMenuButton.vue -> resources/js/components/ui/sidebar/SidebarMenuButtonChild.vue -> resources/js/components/ui/sidebar/index.ts -> resources/js/components/ui/sidebar/SidebarMenuButton.vue`

## Communities (157 total, 17 thin omitted)

### Community 0 - "lib/utils.ts"
Cohesion: 0.06
Nodes (26): props, SidebarProps, { isMobile, state, openMobile, setOpenMobile }, props, props, props, props, props (+18 more)

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
Cohesion: 0.11
Nodes (11): emits, forwarded, props, props, delegatedProps, props, props, props (+3 more)

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
Cohesion: 0.21
Nodes (11): { appearance, updateAppearance }, tabs, appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), prefersDark() (+3 more)

### Community 12 - "navigation-menu/index.ts"
Cohesion: 0.06
Nodes (27): navigationMenuTriggerStyle, delegatedProps, emits, forwarded, props, delegatedProps, emits, forwarded (+19 more)

### Community 13 - "User.php"
Cohesion: 0.13
Nodes (4): Illuminate\Notifications\Notifiable, Laravel\Fortify\Contracts\PasskeyUser, Laravel\Fortify\PasskeyAuthenticatable, Laravel\Fortify\TwoFactorAuthenticatable

### Community 14 - "types/index.ts"
Cohesion: 0.19
Nodes (9): className, Props, Props, Props, Props, Props, { breadcrumbs = [] }, BreadcrumbItem (+1 more)

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
Nodes (17): class-variance-authority, laravel-vite-plugin, @lucide/vue, dependencies, class-variance-authority, laravel-vite-plugin, @lucide/vue, reka-ui (+9 more)

### Community 19 - "sidebar/index.ts"
Cohesion: 0.07
Nodes (11): ResetUserPassword, SecurityController, ResetStaffPasswordRequest, SaveCourseRequest, SaveCurriculumRequirementRequest, PasswordUpdateRequest, ProfileDeleteRequest, TwoFactorAuthenticationRequest (+3 more)

### Community 20 - "aliases"
Cohesion: 0.12
Nodes (15): aliases, components, composables, lib, ui, utils, iconLibrary, $schema (+7 more)

### Community 21 - "TwoFactorSetupModal.vue"
Cohesion: 0.11
Nodes (6): AcademicPeriodController, TermController, SaveAcademicYearRequest, SaveTermRequest, AcademicYear, AcademicPeriodService

### Community 22 - "breadcrumb/index.ts"
Cohesion: 0.13
Nodes (7): props, props, props, props, props, props, props

### Community 23 - "card/index.ts"
Cohesion: 0.08
Nodes (16): Props, delegatedProps, props, props, props, props, props, props (+8 more)

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
Cohesion: 0.14
Nodes (9): auth, { isCurrentUrl, whenCurrentUrl }, mainNavItems, page, Props, rightNavItems, Props, props (+1 more)

### Community 28 - "TwoFactorChallenge.vue"
Cohesion: 0.17
Nodes (9): Props, Props, emit, handleDelete(), isDeleting, props, Props, Passkey (+1 more)

### Community 29 - "TwoFactorRecoveryCodes.vue"
Cohesion: 0.07
Nodes (24): Props, uniqueErrors, { hasSetupData, clearTwoFactorAuthData }, Props, showSetupModal, isRecoveryCodesVisible, recoveryCodeSectionRef, { recoveryCodesList, fetchRecoveryCodes, errors } (+16 more)

### Community 30 - "UserInfo.vue"
Cohesion: 0.15
Nodes (12): { isMobile, state }, page, user, { getInitials }, Props, showAvatar, Props, getInitial() (+4 more)

### Community 31 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 32 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 33 - "require-dev"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 34 - "ManageTwoFactor.vue"
Cohesion: 0.16
Nodes (5): CatalogueImportController, DashboardController, StoreCatalogueImportRequest, Illuminate\Http\Request, Inertia\Response

### Community 35 - "Queue & Job Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 36 - "Layout.vue"
Cohesion: 0.19
Nodes (10): mainNavItems, page, { isCurrentUrl }, currentUrlReactive, page, useCurrentUrl(), UseCurrentUrlReturn, { isCurrentOrParentUrl } (+2 more)

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

### Community 46 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, build, build:ssr, dev, format, format:check, lint, lint:check (+1 more)

### Community 47 - "Collapsible.vue"
Cohesion: 0.22
Nodes (5): emits, forwarded, props, props, props

### Community 48 - "NavigationMenu.vue"
Cohesion: 0.21
Nodes (4): LevelController, ProfileController, SaveLevelRequest, Illuminate\Http\RedirectResponse

### Community 49 - "global.d.ts"
Cohesion: 0.25
Nodes (8): Auth, ComponentCustomProperties, ImportMeta, ImportMetaEnv, InertiaConfig, @inertiajs/core, vite/client, vue

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

### Community 55 - "composer.json"
Cohesion: 0.25
Nodes (7): description, license, minimum-stability, name, prefer-stable, $schema, type

### Community 56 - "require"
Cohesion: 0.22
Nodes (9): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php (+1 more)

### Community 57 - "PasskeyRegister.vue"
Cohesion: 0.25
Nodes (4): emit, name, { register, isLoading, error, isSupported }, showForm

### Community 58 - "Alert.vue"
Cohesion: 0.29
Nodes (4): props, props, props, AlertVariants

### Community 60 - "dropdown-menu/index.ts"
Cohesion: 0.33
Nodes (3): props, forwardedProps, props

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
Cohesion: 0.20
Nodes (8): courseId, levelId, Option, Pace, props, search, status, subjectId

### Community 68 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 70 - "AppSidebar.vue"
Cohesion: 0.15
Nodes (4): StaffController, StoreStaffRequest, UpdateStaffRequest, Role

### Community 71 - "SidebarMenuSkeleton.vue"
Cohesion: 0.29
Nodes (4): props, width, props, SkeletonProps

### Community 72 - "Configuration Best Practices"
Cohesion: 0.17
Nodes (10): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets, Consistency First, Decision Rules, How to Apply (+2 more)

### Community 73 - "laravel-best-practices/SKILL.md"
Cohesion: 0.10
Nodes (6): CatalogueImportRow, CurriculumRequirement, StudentCourse, StudentEnrollment, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Relations\BelongsTo

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
Cohesion: 0.13
Nodes (9): AccessControlSeeder, DatabaseSeeder, PaceCatalogueSeeder, SchoolSettingSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Http\UploadedFile, paceWorkbook() (+1 more)

### Community 81 - "Badge.vue"
Cohesion: 0.10
Nodes (14): delegatedProps, props, BadgeVariants, Import, CatalogueImport, ImportRow, canManage, Pace (+6 more)

### Community 82 - "DropdownMenuCheckboxItem.vue"
Cohesion: 0.18
Nodes (3): Level, CurriculumRequirementFactory, LevelFactory

### Community 83 - "DropdownMenuContent.vue"
Cohesion: 0.27
Nodes (6): CreateNewUser, emailRules(), nameRules(), profileRules(), ProfileUpdateRequest, Laravel\Fortify\Contracts\CreatesNewUsers

### Community 84 - "DropdownMenuRadioItem.vue"
Cohesion: 0.13
Nodes (6): Course, Subject, CourseFactory, PaceFactory, SubjectFactory, Illuminate\Database\Eloquent\Relations\HasMany

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
Cohesion: 0.13
Nodes (5): StudentController, StoreStudentRequest, UpdateStudentRequest, Student, StudentRegistrationService

### Community 89 - "DropdownMenu.vue"
Cohesion: 0.14
Nodes (6): ActivityLog, Pace, Permission, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Database\Eloquent\Relations\MorphTo

### Community 90 - "DropdownMenuItem.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

### Community 92 - "DropdownMenuRadioGroup.vue"
Cohesion: 0.25
Nodes (3): StudentEnrollmentController, SaveStudentEnrollmentRequest, StudentEnrollmentService

### Community 94 - "DropdownMenuSubTrigger.vue"
Cohesion: 0.12
Nodes (11): StudentValues, Props, ButtonVariants, Course, Level, Subject, AccessOption, page (+3 more)

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

### Community 111 - "vue"
Cohesion: 0.32
Nodes (6): SidebarMenuButtonVariants, delegatedProps, { isMobile, state }, props, props, SidebarMenuButtonProps

### Community 112 - "vue-sonner"
Cohesion: 0.29
Nodes (3): Term, StudentEnrollmentFactory, enrollmentFixture()

### Community 117 - "cache.php"
Cohesion: 0.12
Nodes (8): AcademicYearFactory, CatalogueImportFactory, CatalogueImportRowFactory, SchoolSettingFactory, StudentCourseFactory, StudentFactory, TermFactory, Illuminate\Database\Eloquent\Factories\Factory

### Community 126 - "Welcome.vue"
Cohesion: 0.24
Nodes (5): { title = '', description = '' }, initializeFlashToast(), Appearance, FlashToast, ResolvedAppearance

### Community 128 - "web.php"
Cohesion: 0.12
Nodes (10): CatalogueSetupController, CourseController, CurriculumController, StaffPasswordController, SubjectController, Controller, StudentStatusController, SaveSubjectRequest (+2 more)

### Community 134 - "ProfileController.php"
Cohesion: 0.33
Nodes (3): delegatedProps, props, props

### Community 135 - "SheetContent.vue"
Cohesion: 0.22
Nodes (7): delegatedProps, emits, forwarded, props, SheetContentProps, delegatedProps, props

### Community 136 - "UpdateStaffRequest"
Cohesion: 0.40
Nodes (3): props, search, StaffMember

### Community 137 - "Index.vue"
Cohesion: 0.40
Nodes (3): authConfigContent, code, showRecoveryInput

### Community 142 - "tw-animate-css"
Cohesion: 0.50
Nodes (3): emits, forwarded, props

### Community 144 - "DropdownMenuLabel.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

### Community 145 - "DropdownMenuRadioGroup.vue"
Cohesion: 0.50
Nodes (3): emits, forwarded, props

### Community 146 - "DropdownMenuSub.vue"
Cohesion: 0.50
Nodes (3): emits, forwarded, props

### Community 147 - "DropdownMenuSubTrigger.vue"
Cohesion: 0.50
Nodes (3): delegatedProps, forwardedProps, props

## Knowledge Gaps
- **777 isolated node(s):** `$schema`, `style`, `config`, `css`, `baseColor` (+772 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **17 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `cn()` connect `lib/utils.ts` to `select/index.ts`, `dialog/index.ts`, `ProfileController.php`, `SheetContent.vue`, `Sidebar.vue`, `InputError.vue`, `navigation-menu/index.ts`, `InputOTPSlot.vue`, `DropdownMenuLabel.vue`, `DropdownMenuSubTrigger.vue`, `eslint-config-prettier`, `breadcrumb/index.ts`, `card/index.ts`, `TooltipContent.vue`, `SidebarProvider.vue`, `Alert.vue`, `SidebarMenuSkeleton.vue`, `Checkbox.vue`, `Badge.vue`, `DropdownMenuItem.vue`, `DropdownMenuSubTrigger.vue`, `Input.vue`, `DropdownMenuSeparator.vue`, `clsx`, `@inertiajs/vite`, `@inertiajs/vue3`, `vue`?**
  _High betweenness centrality (0.057) - this node is a cross-community bridge._
- **Why does `User` connect `NavigationMenuLink.vue` to `web.php`, `ManageTwoFactor.vue`, `AppSidebar.vue`, `laravel-best-practices/SKILL.md`, `FortifyServiceProvider`, `DropdownMenuLabel.vue`, `TestCase`, `User.php`, `DatabaseSeeder.php`, `PlaceholderPattern.vue`, `sidebar/index.ts`, `DropdownMenuContent.vue`, `DropdownMenuCheckboxItem.vue`, `button/index.ts`, `DropdownMenu.vue`, `AvatarFallback.vue`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Why does `Controller` connect `web.php` to `ManageTwoFactor.vue`, `AppSidebar.vue`, `NavigationMenu.vue`, `sidebar/index.ts`, `TwoFactorSetupModal.vue`, `ProfileValidationRules.php`, `button/index.ts`, `DropdownMenuRadioGroup.vue`, `DropdownMenuSub.vue`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **Are the 4 inferred relationships involving `User` (e.g. with `.__invoke()` and `.configureAuthentication()`) actually correct?**
  _`User` has 4 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `style`, `config` to the rest of the system?**
  _777 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `lib/utils.ts` be split into smaller, more focused modules?**
  _Cohesion score 0.061170212765957445 - nodes in this community are weakly interconnected._
- **Should `scripts` be split into smaller, more focused modules?**
  _Cohesion score 0.052564102564102565 - nodes in this community are weakly interconnected._